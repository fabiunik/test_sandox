<?php
// Previne que qualquer erro ou aviso "suje" a resposta enviada ao Mercado Pago
ob_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/conectarBD.php';
require_once __DIR__ . '/../model/Pedido.php';
require_once __DIR__ . '/../model/Pagamento.php';
require_once __DIR__ . '/../model/Agendamento.php';

// Captura a notificação tanto via URL (IPN) quanto via Body (Webhooks JSON)
$json_input = file_get_contents('php://input');
$data_input = json_decode($json_input, true);

// Captura o ID: Pode vir como 'id' ou 'data.id'
$id = $_GET['id'] ?? ($data_input['data']['id'] ?? ($data_input['id'] ?? null));

// Captura o Tópico: Pode vir como 'topic', 'type' ou 'action'
$topic = $_GET['topic'] ?? ($_GET['type'] ?? ($data_input['type'] ?? ($data_input['action'] ?? null)));

// Normalização de tópicos para suportar diferentes versões da API
if ($topic && (strpos($topic, 'payment') !== false)) {
    $topic = 'payment';
} elseif ($topic && (strpos($topic, 'merchant_order') !== false)) {
    $topic = 'merchant_order';
}

error_log("Notificação recebida - ID: $id - Tópico: $topic");

if ($id && ($topic === 'payment' || $topic === 'merchant_order')) {
    $access_token = getenv('MP_ACCESS_TOKEN') ?: ($_ENV['MP_ACCESS_TOKEN'] ?? null);
    
    if (!$access_token) {
        error_log("Erro: MP_ACCESS_TOKEN não encontrado no ambiente.");
        http_response_code(500);
        exit;
    }

    // Define a URL de consulta baseada no tópico
    $url = ($topic === 'payment') 
        ? "https://api.mercadopago.com/v1/payments/$id" 
        : "https://api.mercadopago.com/merchant_orders/$id";

    error_log("Consultando detalhes em: $url");

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $access_token"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'AquiTemTerapia/1.0'); // Identificação opcional para evitar bloqueios
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        error_log("Erro na notificação MP: " . curl_error($ch));
    }

    if ($http_code !== 200) {
        error_log("Erro API Mercado Pago (HTTP $http_code): " . $response);
    }
    
    // Log temporário para conferir o que o Mercado Pago está devolvendo de fato
    error_log("Resposta bruta da API MP: " . $response);

    $payment_info = json_decode($response, true);
    curl_close($ch);

    // Se for merchant_order, precisamos extrair o último pagamento
    $metodo_pagamento = 'N/A';
    $valor_pago = 0;

    if ($topic === 'merchant_order' && isset($payment_info['payments'])) {
        $last_payment = end($payment_info['payments']);
        $id = $last_payment['id'] ?? $id;
        $status_mp = $last_payment['status'] ?? 'unknown';
        $pedido_id = intval($payment_info['external_reference'] ?? 0);
        $metodo_pagamento = $last_payment['payment_method_id'] ?? 'N/A';
        $valor_pago = $last_payment['transaction_amount'] ?? 0;
    } else {
        $status_mp = $payment_info['status'] ?? 'unknown';
        $pedido_id = intval($payment_info['external_reference'] ?? 0);
        $metodo_pagamento = $payment_info['payment_method_id'] ?? 'N/A';
        $valor_pago = $payment_info['transaction_amount'] ?? 0;
    }

    // Log para verificar se o ID do pedido foi recuperado
    error_log("Dados extraídos - Pedido ID: $pedido_id, Status MP: $status_mp");

    if ($pedido_id === 0) {
        error_log("Dados insuficientes para processar Pedido. ID MP: $id, Pedido: $pedido_id");
        http_response_code(200); 
        exit;
    }

    $pedidoModel = new Pedido($pdo);
    $pagamentoModel = new Pagamento($pdo);
    $agendamentoModel = new Agendamento($pdo);

    try {
        // Verifica se já registramos esse pagamento para evitar erro de duplicidade
        $pagamentoExistente = $pagamentoModel->buscarPorTransacaoId($id);
        if (!$pagamentoExistente) {
            $pagamentoModel->registrarTransacao(
                $pedido_id, 
                $id, 
                $metodo_pagamento, 
                $valor_pago, 
                $status_mp
            );
            error_log("Transação $id registrada na tabela pagamento.");
        } else if ($pagamentoExistente['status'] !== $status_mp) {
            $pagamentoModel->atualizarStatus($pagamentoExistente['id'], $status_mp);
            error_log("Status da transação $id atualizado para $status_mp.");
        }

        // 2. Se aprovado, atualiza o pedido e os agendamentos vinculados
        if ($status_mp === 'approved') {
            $pedidoModel->atualizarStatus($pedido_id, 'pago');
            error_log("Status do Pedido #$pedido_id atualizado para 'pago' no banco de dados.");
            $pdo->prepare("UPDATE agendamento SET status = 'confirmado' WHERE pedido_id = ?")
                ->execute([$pedido_id]);

            // NOVO: Buscar e-mails e detalhes para envio de confirmação
            $sqlInfo = "SELECT 
                            a.data, a.horario, i.nome as servico_nome,
                            u_cli.nome as cliente_nome, u_cli.email as cliente_email,
                            u_ter.nome as terapeuta_nome, u_ter.email as terapeuta_email
                        FROM agendamento a
                        JOIN itens i ON a.itens_id = i.id
                        JOIN usuario u_cli ON a.usuario_id = u_cli.id
                        JOIN usuario u_ter ON a.terapeuta_id = u_ter.id
                        WHERE a.pedido_id = ?";
            
            $stmtInfo = $pdo->prepare($sqlInfo);
            $stmtInfo->execute([$pedido_id]);
            $detalhesNotificacao = $stmtInfo->fetchAll(PDO::FETCH_ASSOC);

            // Tenta carregar o PHPMailer apenas se o autoload existir no servidor
            $autoloadPath = __DIR__ . '/../vendor/autoload.php';
            if (file_exists($autoloadPath)) {
                require_once $autoloadPath;

                $smtp_host = getenv('MAILTRAP_HOST');
                if (!$smtp_host) {
                    error_log("Aviso: Variáveis MAILTRAP_HOST não configuradas. Pulando envio de e-mail.");
                } else {
                    $mail = new PHPMailer(true);
                    foreach ($detalhesNotificacao as $info) {
                        try {
                            // Configurações do Servidor SMTP
                            $mail->isSMTP();
                            $mail->Host       = $smtp_host;
                            $mail->SMTPAuth   = true;
                            $mail->Username   = getenv('MAILTRAP_USERNAME');
                            $mail->Password   = getenv('MAILTRAP_PASSWORD');
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port       = getenv('MAILTRAP_PORT');
                            $mail->CharSet    = 'UTF-8';

                            // Remetente
                            $mail->setFrom(getenv('MAILTRAP_FROM_EMAIL'), getenv('MAILTRAP_FROM_NAME'));

                            // --- E-mail para o Cliente ---
                            $mail->addAddress($info['cliente_email'], $info['cliente_nome']);
                            $mail->isHTML(true);
                            $mail->Subject = 'Confirmação de Agendamento — Aqui tem Terapia';
                            
                            $dataFmt = date('d/m/Y', strtotime($info['data']));
                            $horaFmt = substr($info['horario'], 0, 5);
                            
                            $mail->Body = "<h1>Olá, {$info['cliente_nome']}!</h1>
                                           <p>Seu pagamento foi aprovado e seu agendamento de <strong>{$info['servico_nome']}</strong> está confirmado.</p>
                                           <p><strong>Data:</strong> $dataFmt<br><strong>Horário:</strong> $horaFmt<br><strong>Terapeuta:</strong> {$info['terapeuta_nome']}</p>";
                            
                            $mail->send();
                            
                            // --- E-mail para o Terapeuta ---
                            $mail->clearAddresses(); // Limpa o destinatário anterior
                            $mail->addAddress($info['terapeuta_email'], $info['terapeuta_nome']);
                            $mail->Subject = 'Novo Agendamento Confirmado — Aqui tem Terapia';
                            $mail->Body = "<h1>Olá, {$info['terapeuta_nome']}!</h1>
                                           <p>Você tem um novo atendimento confirmado: <strong>{$info['servico_nome']}</strong> com o cliente <strong>{$info['cliente_nome']}</strong> para o dia $dataFmt às $horaFmt.</p>";
                            $mail->send();
                        } catch (Exception $e) {
                            error_log("Erro ao enviar e-mail: {$mail->ErrorInfo}");
                        }
                    }
                }
            } else {
                error_log("Aviso: vendor/autoload.php não encontrado. O status do pedido foi atualizado, mas os e-mails não foram enviados.");
            }
        }
    } catch (\Throwable $e) {
        error_log("Erro crítico no processamento da notificação: " . $e->getMessage());
        // Não enviamos erro 500 para o MP para evitar retentativas infinitas de um erro de lógica
    }
}

// Limpa qualquer output acidental e envia o 200
ob_end_clean();
http_response_code(200); // Responde OK para o Mercado Pago parar de enviar a notificação