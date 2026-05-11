<?php
session_start();
require_once __DIR__ . '/conectarBD.php';
require_once __DIR__ . '/../model/Pedido.php';
require_once __DIR__ . '/../model/Agendamento.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['usuario_id'])) {
    $acao = $_POST['acao'] ?? '';
    $pedido_id = intval($_POST['pedido_id'] ?? 0);

    if ($acao === 'cancelar_pedido' && $pedido_id > 0) {
        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE pedido SET status = 'cancelado' WHERE id = ? AND usuario_id = ?")->execute([$pedido_id, $_SESSION['usuario_id']]);
            $pdo->prepare("UPDATE agendamento SET status = 'cancelado' WHERE pedido_id = ?")->execute([$pedido_id]);
            $pdo->commit();
            $_SESSION['success'] = "Pedido cancelado com sucesso.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Erro ao cancelar pedido.";
        }
    }
}
header("Location: ../view/pedidos.php");
exit;