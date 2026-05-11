<?php
session_start();
require_once __DIR__ . '/conectarBD.php';
require_once __DIR__ . '/../model/Agendamento.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['usuario_id'])) {
    $acao = $_POST['acao'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    $agendamentoModel = new Agendamento($pdo);

    if ($acao === 'cancelar' && $id > 0) {
        $ag = $agendamentoModel->buscarPorId($id);
        if ($ag && $ag['usuario_id'] == $_SESSION['usuario_id']) {
            if ($agendamentoModel->cancelar($id)) {
                $_SESSION['success'] = "Agendamento cancelado com sucesso.";
            } else {
                $_SESSION['error'] = "Não foi possível cancelar o agendamento.";
            }
        }
    }
}
header("Location: ../view/meus_agendamentos.php");
exit;