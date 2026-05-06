<?php
require_once __DIR__ . '/conectarBD.php';
require_once __DIR__ . '/../model/Pedido.php';
require_once __DIR__ . '/../model/Pagamento.php';
require_once __DIR__ . '/../model/Agendamento.php';

// Recebe o ID da notificação enviado pelo Mercado Pago
$id = $_GET['id'] ?? ($_POST['data']['id'] ?? null);
$topic = $_GET['topic'] ?? ($_POST['type'] ?? null);

if ($id && $topic === 'payment') {
    $access_token = $_ENV['MP_ACCESS_TOKEN'];
    
    // Consulta os detalhes do pagamento na API do Mercado Pago
    $ch = curl_init("https://api.mercadopago.com/v1/payments/$id");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $access_token"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $payment_info = json_decode($response, true);
    
    if (curl_errno($ch)) {
        error_log("Erro na notificação MP: " . curl_error($ch));
    }
    
    curl_close($ch);

    if ($payment_info && isset($payment_info['status'])) {
        $pedido_id = intval($payment_info['external_reference']);
        $status_mp = $payment_info['status']; // 'approved', 'pending', etc.
        
        $pedidoModel = new Pedido($pdo);
        $pagamentoModel = new Pagamento($pdo);
        $agendamentoModel = new Agendamento($pdo);

        // Tradução de status
        $novo_status_pedido = ($status_mp === 'approved') ? 'pago' : 'pendente';
        $novo_status_agendamento = ($status_mp === 'approved') ? 'confirmado' : 'pendente';

        // 1. Registra ou atualiza a transação na tabela pagamento
        $pagamentoModel->registrarTransacao(
            $pedido_id, 
            $id, 
            $payment_info['payment_method_id'], 
            $payment_info['transaction_amount'], 
            $status_mp
        );

        // 2. Se aprovado, atualiza o pedido e os agendamentos vinculados
        if ($status_mp === 'approved') {
            $pedidoModel->atualizarStatus($pedido_id, 'pago');
            $pdo->prepare("UPDATE agendamento SET status = 'confirmado' WHERE pedido_id = ?")
                ->execute([$pedido_id]);
        }
    }
}

http_response_code(200); // Responde OK para o Mercado Pago parar de enviar a notificação