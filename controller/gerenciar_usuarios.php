<?php
// Ativa exibição de erros

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

session_start();

// Carrega o autoloader do Composer no início para garantir que o PHPMailer esteja disponível
$tentativasAutoload = [
    __DIR__ . '/../vendor/autoload.php',           // Caminho relativo local
    dirname(__DIR__, 2) . '/vendor/autoload.php',  // Subindo dois níveis
    '/app/vendor/autoload.php'                     // Padrão do Railway/Nixpacks
];

$autoloadPath = null;
foreach ($tentativasAutoload as $caminho) {
    error_log("Tentando carregar autoload em: " . $caminho);
    if (file_exists($caminho)) {
        require_once $caminho;
        $autoloadPath = $caminho;
        error_log("Sucesso! Autoload carregado de: " . $caminho);
        break;
    }
}

if (!$autoloadPath) {
    error_log("CRÍTICO: Nenhum arquivo vendor/autoload.php foi encontrado. Verifique a estrutura de pastas no Railway.");
}

require_once __DIR__ . '/conectarBD.php';
require_once __DIR__ . '/../model/Usuario.php';

$usuario = new Usuario($pdo);

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
            $usuario->criar(
                $_POST['nome'],
                $_POST['cpf'],
                $_POST['dtnas'],
                $_POST['telefone'],
                $_POST['email'],
                $_POST['senha'],
                $_POST['tipo'] ?? 'usuario'
            );
            $_SESSION['success'] = "Usuário cadastrado com sucesso!";
            header("Location: ../view/sucesso_cadastro.php");
            exit;

        } elseif ($acao === 'login') {
            // Login público
            $email = $_POST['email'];
            $senha = $_POST['senha'];

            if ($usuario->login($email, $senha)) {
                $_SESSION['success'] = "Login realizado com sucesso!";
                header("Location: ../view/perfil.php");
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

                        // Configurações do Servidor SMTP (Mailtrap)
                        $mail->isSMTP();
                        $mail->Host       = getenv('MAILTRAP_HOST');
                        $mail->SMTPAuth   = true;
                        $mail->Username   = getenv('MAILTRAP_USERNAME');
                        $mail->Password   = getenv('MAILTRAP_PASSWORD');
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
                        $mail->Port       = getenv('MAILTRAP_PORT') ?: 2525;
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
                        $caminhoTentado = $autoloadPath ?: "NENHUM";
                        $vendorExiste = is_dir(dirname($caminhoTentado)) ? "SIM" : "NÃO";
                        
                        error_log("Erro: Classe PHPMailer não encontrada.");
                        error_log("Autoload usado: " . $caminhoTentado);
                        error_log("Pasta vendor existe: " . $vendorExiste);
                        error_log("PHP Executando em: " . getcwd());
                        error_log("Estrutura da pasta atual: " . implode(', ', scandir(getcwd())));
                        
                        error_log("Erro: PHPMailer não carregado. Autoload não encontrado em: " . $caminhoTentado);
                        // Fallback para o log caso o e-mail não possa ser enviado
                        error_log("Link de recuperação (Simulação): https://testsandox-staging.up.railway.app/view/redefinir_senha.php?token=$token");
                    }
                } catch (Exception $e) {
                    error_log("Erro PHPMailer ao enviar para $email: " . $e->getMessage() . " | Info: " . ($mail->ErrorInfo ?? 'N/A'));
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

            $hash = password_hash($senha_nova, PASSWORD_DEFAULT);
            $usuario->atualizarSenhaPerfil($usuario_id, $hash);

            $_SESSION['success'] = "Senha alterada com sucesso!";
            header("Location: ../view/perfil.php");
            exit;
        }
        /** Sair */
        elseif ($acao === 'logout') {
                    session_start();
                    session_destroy();
                    header("Location: ../view/login.php");
                    exit;
        } else {
            throw new Exception("Ação inválida.");
        }
    } catch (Exception $e) {
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
