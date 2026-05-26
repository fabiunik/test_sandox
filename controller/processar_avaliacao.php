<?php
session_start();
require_once __DIR__ . '/conectarBD.php';
require_once __DIR__ . '/../model/Avaliacao.php';
require_once __DIR__ . '/../model/Agendamento.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];
    $agendamento_id = intval($_POST['agendamento_id']);
    $terapeuta_id = intval($_POST['terapeuta_id']);
    $nota = intval($_POST['nota']);
    $comentario = $_POST['comentario'] ?? '';

    $agendamentoModel = new Agendamento($pdo);
    $ag = $agendamentoModel->buscarPorId($agendamento_id);
    
    if ($ag && $ag['usuario_id'] == $usuario_id) {
        $avaliacaoModel = new Avaliacao($pdo);
        $item_id = $ag['itens_id'];
        
        if ($avaliacaoModel->salvar($usuario_id, $item_id, $terapeuta_id, $nota, $comentario, $agendamento_id)) {
            $_SESSION['success'] = "🌟 Muito obrigado! Sua avaliação foi recebida e nos ajuda a melhorar cada vez mais nossos serviços.";
        } else {
            $_SESSION['error'] = "Erro ao salvar avaliação.";
        }
    }
}
header("Location: ../view/meus_agendamentos.php");
exit;