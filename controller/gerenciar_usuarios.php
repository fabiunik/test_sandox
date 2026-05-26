<?php
// Ativa exibição de erros

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

session_start();

// Carrega o autoloader do Composer no início para garantir que o PHPMailer esteja disponível
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    error_log("ERRO: Autoload não encontrado em: " . $autoloadPath);
}

require_once __DIR__ . '/conectarBD.php';
require_once __DIR__ . '/../model/Usuario.php';

$usuario = new Usuario($pdo);

// Tratamento de confirmação de e-mail via GET
if (isset($_GET['acao']) && $_GET['acao'] === 'confirmar_email' && isset($_GET['token'])) {
    if ($usuario->confirmarEmail($_GET['token'])) {
        $_SESSION['success'] = "E-mail confirmado com sucesso! Agora você pode fazer login.";
    } else {
        $_SESSION['error'] = "Token de confirmação inválido ou já utilizado.";
    }
    header("Location: ../view/login.php");
    exit;
}

// Processamento do formulário

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    try {
        if ($acao === 'criar') {
            // Validação de confirmação de senha
            if ($_POST['senha'] !== $_POST['confirma_senha']) {
                throw new Exception("As senhas não conferem.");
            }
            // Cadastro público
            $tokenConfirmacao = $usuario->criar(
                $_POST['nome'],
                $_POST['cpf'],
                $_POST['dtnas'],
                $_POST['telefone'],
                $_POST['email'],
                $_POST['senha'],
                $_POST['tipo'] ?? 'usuario'
            );

            // Envio de E-mail de Confirmação
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = gethostbyname(getenv('MAILTRAP_HOST') ?: 'mailpit.railway.internal');
                $mail->SMTPAuth = (getenv('MAILTRAP_ENCRYPTION') !== 'none');
                $mail->Username = getenv('MAILTRAP_USERNAME');
                $mail->Password = getenv('MAILTRAP_PASSWORD');
                $mail->SMTPSecure = (getenv('MAILTRAP_ENCRYPTION') === 'none') ? '' : PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = (int)(getenv('MAILTRAP_PORT') ?: 1025);
                $mail->CharSet = 'UTF-8';

                $mail->setFrom(getenv('MAILTRAP_FROM_EMAIL') ?: 'contato@teste.com', 'Aqui tem Terapia');
                $mail->addAddress($_POST['email'], $_POST['nome']);

                $mail->isHTML(true);
                $mail->Subject = 'Confirme seu cadastro — Aqui tem Terapia';
                $link = "https://testsandox-staging.up.railway.app/controller/gerenciar_usuarios.php?acao=confirmar_email&token=$tokenConfirmacao";
                
                $mail->Body = "<h1>Quase lá!</h1><p>Clique no link abaixo para confirmar seu e-mail e ativar sua conta:</p><a href='$link'>Ativar minha conta</a>";
                $mail->send();
            }

            $_SESSION['success'] = "Cadastro realizado! Por favor, verifique seu e-mail para ativar sua conta.";
            header("Location: ../view/login.php");
            exit;
        } elseif ($acao === 'login') {
            // Login público
            $email = $_POST['email'];
            $senha = $_POST['senha'];

            if ($usuario->login($email, $senha)) {
                $_SESSION['success'] = "Login realizado com sucesso!";
                if (isset($_SESSION['pending_agendamento'])) {
                    header("Location: ../view/agendamento.php");
                } else {
                    header("Location: ../view/perfil.php");
                }
                exit;
            } else {
                throw new Exception("E-mail ou senha inválidos.");
            }

        } elseif ($acao === 'editar') {
            // Usuário pode editar o próprio perfil
            if (!isset($_SESSION['usuario_id'])) {
                throw new Exception("Você precisa estar logado para editar seu perfil.");
            }
            if ($_SESSION['usuario_id'] != $_POST['usuario_id']) {
                throw new Exception("Você só pode editar o seu próprio perfil.");
            }

            $usuario->editar(
                intval($_POST['usuario_id']),
                $_POST['nome'],
                $_POST['telefone'],
                $_POST['email']
            );
            $_SESSION['success'] = "Perfil atualizado com sucesso!";
            header("Location: ../view/perfil.php");
            exit;

        } elseif ($acao === 'excluir') {
            // Usuário pode excluir o próprio perfil
            if (!isset($_SESSION['usuario_id'])) {
                throw new Exception("Você precisa estar logado para excluir seu perfil.");
            }
            if ($_SESSION['usuario_id'] != $_POST['usuario_id']) {
                throw new Exception("Você só pode excluir o seu próprio perfil.");
            }

            $usuario->deletar(intval($_POST['usuario_id']));
            session_destroy();
            $_SESSION['success'] = "Perfil excluído com sucesso!";
            header("Location: ../view/login.php");
            exit;

        } elseif ($acao === 'editar_tipo') {
            // Apenas administradores podem alterar tipo de usuário
            if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'administrador') {
                throw new Exception("Acesso negado. Apenas administradores podem realizar esta ação.");
            }
            $usuario->atualizarTipo(intval($_POST['usuario_id']), $_POST['novo_tipo']);
            $_SESSION['success'] = "Tipo de usuário atualizado com sucesso!";
            header("Location: ../view/painel_administrador.php");
            exit;

        } elseif ($acao === 'alterar_status') {
            // Apenas administradores podem inativar usuários
            if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'administrador') {
                throw new Exception("Acesso negado.");
            }
            $usuario->atualizarStatus(intval($_POST['usuario_id']), $_POST['status']);
            $_SESSION['success'] = "Status do usuário atualizado!";
            header("Location: ../view/painel_administrador.php");
            exit;

            // Fluxo: recuperar senha 
        } elseif ($acao === 'recuperar_senha') {
            $email = $_POST['email'] ?? '';

            $dadosUsuario = $usuario->buscarPorEmail($email);

            if ($dadosUsuario) {
                error_log("Iniciando processo de recuperação para: $email");
                $token = $usuario->gerarTokenRecuperacao($dadosUsuario['id']);
                
                // --- INÍCIO: Substituição do error_log pelo envio real de e-mail com PHPMailer ---
                try {
                    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                        $mail = new PHPMailer(true); // Habilita exceções para tratamento de erros

                        // Configurações do Servidor SMTP
                        $mail->SMTPDebug = SMTP::DEBUG_OFF; // Desativa debug detalhado para produção

                        $mail->isSMTP();
                        
                        $rawHost = getenv('MAILTRAP_HOST') ?: 'mailpit.railway.internal';
                        $rawPort = getenv('MAILTRAP_PORT') ?: 1025;
                        $mail->Host       = gethostbyname($rawHost);
                        $encryption       = getenv('MAILTRAP_ENCRYPTION');
                        $mail->SMTPAuth   = ($encryption !== 'none'); 
                        $mail->Username   = getenv('MAILTRAP_USERNAME');
                        $mail->Password   = getenv('MAILTRAP_PASSWORD');
                        $mail->SMTPSecure = ($encryption === 'none') ? '' : ($encryption ?: PHPMailer::ENCRYPTION_STARTTLS);
                        $mail->Port       = (int)$rawPort;
                        $mail->Timeout    = 30;
                        // SMTPOptions ajuda em conexões onde o certificado do host falha na verificação peer
                        $mail->SMTPOptions = [
                            'ssl' => [
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                                'allow_self_signed' => true
                            ]
                        ];
                        $mail->CharSet    = 'UTF-8';

                        if (empty($mail->Host) || empty($mail->Username)) {
                            error_log("ERRO: Configurações de e-mail ausentes no ambiente Railway.");
                            throw new Exception("Configurações SMTP não encontradas.");
                        }

                        // Remetente e Destinatário
                        $mail->setFrom(getenv('MAILTRAP_FROM_EMAIL') ?: 'contato@teste.com', getenv('MAILTRAP_FROM_NAME') ?: 'Aqui tem Terapia');
                        $mail->addAddress($email);

                        // Conteúdo do E-mail
                        $mail->isHTML(true);
                        $mail->Subject = 'Recuperacao de Senha - Aqui tem Terapia!';
                        $recoveryLink = "https://testsandox-staging.up.railway.app/view/redefinir_senha.php?token=$token";
                        
                        $mail->Body    = "Olá,<br><br>Recebemos uma solicitação para redefinir sua senha. Por favor, clique no link abaixo para criar uma nova senha:<br><br><a href=\"$recoveryLink\">$recoveryLink</a><br><br>Se você não solicitou isso, pode ignorar este e-mail.<br><br>Atenciosamente,<br>Equipe Aqui tem Terapia!";
                        $mail->AltBody = "Olá,\n\nRecebemos uma solicitação para redefinir sua senha. Por favor, copie e cole o link abaixo em seu navegador para criar uma nova senha:\n\n$recoveryLink\n\nSe você não solicitou isso, pode ignorar este e-mail.\n\nAtenciosamente,\nEquipe Aqui tem Terapia!";

                        $mail->send();
                        error_log("Email de recuperação enviado com sucesso para $email.");
                    } else {
                        error_log("ERRO CRÍTICO: Classe PHPMailer não encontrada.");
                        error_log("Verifique se o 'composer install' foi executado corretamente e se a pasta vendor existe em: " . dirname($autoloadPath));

                        // Fallback para o log caso o e-mail não possa ser enviado
                        error_log("Link de recuperação (Simulação): https://testsandox-staging.up.railway.app/view/redefinir_senha.php?token=$token");
                    }
                } catch (\Throwable $e) {
                    error_log("Erro no processamento do PHPMailer para $email: " . $e->getMessage());
                }
            } else {
                error_log("Tentativa de recuperação falhou: e-mail $email não encontrado no banco.");
            }

            $_SESSION['success'] = "Se o e-mail estiver cadastrado, você receberá um link de recuperação em breve.";
            header("Location: ../view/login.php");
            exit;
        } elseif ($acao === 'redefinir_senha_token') {
            $token = $_POST['token'] ?? '';
            $novaSenha = $_POST['nova_senha'] ?? '';
            $confirmaSenha = $_POST['confirma_senha'] ?? '';

            if ($novaSenha !== $confirmaSenha) {
                $_SESSION['error'] = "As senhas não conferem.";
                header("Location: ../view/redefinir_senha.php?token=$token");
                exit;
            }

            $dadosUsuarioEnc = $usuario->buscarPorToken($token);
            if ($dadosUsuarioEnc) {
                $usuario->atualizarSenhaToken($dadosUsuarioEnc['id'], password_hash($novaSenha, PASSWORD_DEFAULT));
                $_SESSION['success'] = "Senha redefinida com sucesso!";
                header("Location: ../view/login.php");
                exit;
            } else {
                $_SESSION['error'] = "Token inválido ou expirado.";
                header("Location: ../view/recuperar_senha.php");
                exit;
            }
        }
    // Fluxo: alterar senha no perfil (usuário logado)
        elseif ($acao === 'redefinir_senha') {
            $usuario_id     = $_POST['usuario_id'] ?? '';
            $senha_atual    = $_POST['senha_atual'] ?? '';
            $senha_nova     = $_POST['senha_nova'] ?? '';
            $senha_confirma = $_POST['senha_confirma'] ?? '';

            if ($senha_nova !== $senha_confirma) {
                $_SESSION['error'] = "Nova senha e confirmação não conferem.";
                header("Location: ../view/perfil.php");
                exit;
            }

            $dadosUsuarioEnc = $usuario->obterPorId($usuario_id);

            if (!$dadosUsuarioEnc) {
                $_SESSION['error'] = "Usuário não encontrado.";
                header("Location: ../view/perfil.php");
                exit;
            }

            if (!password_verify($senha_atual, $dadosUsuarioEnc['senha'])) {
                $_SESSION['error'] = "Senha atual incorreta.";
                header("Location: ../view/perfil.php");
                exit;
            }

            $hash = password_hash($senha_nova, PASSWORD_ARGON2ID);
            $usuario->atualizarSenhaPerfil($usuario_id, $hash);

            $_SESSION['success'] = "Senha alterada com sucesso!";
            header("Location: ../view/perfil.php");
            exit;
        }
        /** Sair */
        elseif ($acao === 'logout') {
            session_destroy();
            header("Location: ../view/login.php");
            exit;
        } else {
            throw new Exception("Ação inválida.");
        }
    } catch (\Throwable $e) {
        error_log("ERRO CRÍTICO em gerenciar_usuarios.php: " . $e->getMessage() . " em " . $e->getFile() . " na linha " . $e->getLine());
        $_SESSION['error'] = $e->getMessage();
        header("Location: ../view/login.php");
        exit;
    }
}
// Se o usuário está logado, buscar seus dados
if (isset($_SESSION['usuario_id'])) {
    $dadosUsuario = $usuario->buscarPorId($_SESSION['usuario_id']);
}

// Listagem de usuários só deve aparecer para administradores
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'administrador') {
    $usuarios = []; // vazio para quem não é admin
} else {
    $usuarios = $usuario->listar();
}
?>