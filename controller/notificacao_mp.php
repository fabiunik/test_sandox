<?php
// Previne que qualquer erro ou aviso "suje" a resposta enviada ao Mercado Pago
ob_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

// Carrega o autoloader do Composer para que o PHPMailer e outras libs funcionem
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

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
    // Ignora verificação de SSL para garantir conectividade no Railway
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
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

    if ($topic === 'merchant_order') {
        $pedido_id = intval($payment_info['external_reference'] ?? 0);
        if (!empty($payment_info['payments'])) {
            $last_payment = end($payment_info['payments']);
            $id = $last_payment['id'] ?? $id;
            $status_mp = $last_payment['status'] ?? 'unknown';
            $metodo_pagamento = $last_payment['payment_method_id'] ?? 'N/A';
            $valor_pago = $last_payment['transaction_amount'] ?? 0;
        } else {
            // Se não há pagamentos, usamos o status da ordem (ex: 'opened')
            $status_mp = $payment_info['status'] ?? 'opened';
        }
    } elseif ($topic === 'payment') {
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
        // 1. Processamento de Banco de Dados (Transacional)
        $pdo->beginTransaction();

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

        $processarNotificacoes = false;
        // 2. Se aprovado, atualiza o pedido e os agendamentos vinculados
        if ($status_mp === 'approved') {
            $stmtCheck = $pdo->prepare("UPDATE pedido SET status = 'pago' WHERE id = ? AND status = 'pendente'");
            $stmtCheck->execute([$pedido_id]);

            // Usamos 'pago' também para agendamento para evitar erro de tamanho de coluna (Data truncated)
            // e garantir consistência com a tabela pedido.
            $pdo->prepare("UPDATE agendamento SET status = 'pago' WHERE pedido_id = ?")
                ->execute([$pedido_id]);
            
            if ($stmtCheck->rowCount() > 0) {
                $processarNotificacoes = true;
            }
        }
        $pdo->commit();

        // 3. Envio de Notificações (E-mail) - Fora da transação principal
        if ($processarNotificacoes) {
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
            
            // Com o autoloader carregado no topo, apenas verificamos se a classe está disponível
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                $smtp_host = getenv('MAILTRAP_HOST');
                if (empty($smtp_host)) {
                    error_log("Aviso: Variável MAILTRAP_HOST não configurada. Pulando envio de e-mail.");
                } else {
                    $mail = new PHPMailer(true);
                    
                    // Configurações do Servidor SMTP (feitas uma única vez antes do loop para melhor performance)
                    $mail->isSMTP();
                    $mail->Host       = gethostbyname($smtp_host ?: 'mailpit.railway.internal');
                    $encryption       = getenv('MAILTRAP_ENCRYPTION');
                    $mail->SMTPAuth   = ($encryption !== 'none');
                    $mail->Username   = getenv('MAILTRAP_USERNAME');
                    $mail->Password   = getenv('MAILTRAP_PASSWORD');
                    $mail->SMTPSecure = ($encryption === 'none') ? '' : PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = (int)(getenv('MAILTRAP_PORT') ?: 2525);
                    $mail->Timeout    = 30;
                    $mail->SMTPOptions = [
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        ]
                    ];
                    $mail->CharSet    = 'UTF-8';
                    $mail->setFrom(getenv('MAILTRAP_FROM_EMAIL') ?: 'contato@teste.com', getenv('MAILTRAP_FROM_NAME') ?: 'Aqui tem Terapia');

                    foreach ($detalhesNotificacao as $info) {
                        try {
                            $mail->clearAddresses();

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
                            error_log("E-mail de confirmação enviado para cliente: {$info['cliente_email']}");
                            
                            // --- E-mail para o Terapeuta ---
                            $mail->clearAddresses(); // Limpa o destinatário anterior
                            $mail->addAddress($info['terapeuta_email'], $info['terapeuta_nome']);
                            $mail->Subject = 'Novo Agendamento Confirmado — Aqui tem Terapia';
                            $mail->Body = "<h1>Olá, {$info['terapeuta_nome']}!</h1>
                                           <p>Você tem um novo atendimento confirmado: <strong>{$info['servico_nome']}</strong> com o cliente <strong>{$info['cliente_nome']}</strong> para o dia $dataFmt às $horaFmt.</p>";
                            $mail->send();
                            error_log("E-mail de notificação enviado para terapeuta: {$info['terapeuta_email']}");
                        } catch (PHPMailerException $e) {
                            error_log("Erro ao enviar e-mail: {$mail->ErrorInfo}");
                        }
                    }
                }
            } else {
                error_log("Erro: PHPMailer não carregado. Verifique se o 'composer install' foi executado no deploy.");
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