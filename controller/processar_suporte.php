<?php
session_start();
require_once __DIR__ . '/conectarBD.php';
require_once __DIR__ . '/../model/Suporte.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];
    $assunto = $_POST['assunto'] ?? '';
    $descricao = $_POST['descricao'] ?? '';

    $suporteModel = new Suporte($pdo);
    
    if ($suporteModel->criarTicket($usuario_id, $assunto, $descricao)) {
        // Sugestão: Notificação do Admin
        // Aqui você poderia disparar um e-mail usando a função mail() ou uma biblioteca como PHPMailer
        // Exemplo simples de log que pode ser monitorado:
        error_log("NOVO TICKET DE SUPORTE: Usuário #$usuario_id reportou: $assunto");
        
        $_SESSION['success'] = "Seu problema foi reportado com sucesso. O administrador será notificado.";
    } else {
        $_SESSION['error'] = "Erro ao enviar reporte. Tente novamente.";
    }
    
    header("Location: ../view/reportar_problemas.php");
    exit;
}

header("Location: ../view/login.html");
exit;