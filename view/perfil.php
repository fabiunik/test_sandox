<?php
session_start();

// ====== VALIDAÇÃO DE AUTENTICAÇÃO ======
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// ====== CARREGAMENTO DE DADOS ======
require_once __DIR__ . '/../controller/conectarBD.php';
require_once __DIR__ . '/../model/Usuario.php';

$usuarioModel = new Usuario($pdo);
$usuario = $usuarioModel->obterPorId($_SESSION['usuario_id']);

if (!$usuario) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Descriptografar dados sensíveis se necessário
// (Dependendo da sua implementação)

$mensagem_sucesso = $_SESSION['success'] ?? null;
$mensagem_erro = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Meu Perfil — Aqui tem Terapia</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .alert {
      padding: 12px 16px;
      margin-bottom: 16px;
      border-radius: 4px;
      font-size: 14px;
    }
    .alert-success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    .alert-error {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
  </style>
</head>
<body>
  <div class="site">
    <?php include 'header.php'; ?>
    <main class="services-wrapper" aria-label="Perfil do usuário">
      <section class="content">
        <h1 class="page-title">Meu Perfil</h1>
        <p class="lead">Gerencie suas informações pessoais e preferências.</p>

        <!-- MENSAGENS DE FEEDBACK -->
        <?php if ($mensagem_sucesso): ?>
          <div class="alert alert-success">✓ <?php echo htmlspecialchars($mensagem_sucesso); ?></div>
        <?php endif; ?>

        <?php if ($mensagem_erro): ?>
          <div class="alert alert-error">✗ <?php echo htmlspecialchars($mensagem_erro); ?></div>
        <?php endif; ?>

        <!-- SEÇÃO: INFORMAÇÕES PESSOAIS -->
        <h2 id="profile-info">Informações Pessoais</h2>
        <form class="form" action="../controller/gerenciar_usuarios.php" method="post">
          <input type="hidden" name="acao" value="editar">
          <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">

          <div class="input-group">
            <label for="nome">Nome Completo</label>
            <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
          </div>

          <div class="input-group">
            <label for="email-profile">E-mail</label>
            <input type="email" id="email-profile" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
          </div>

          <div class="input-group">
            <label for="telefone">Telefone</label>
            <input type="tel" id="telefone" name="telefone" value="<?php echo htmlspecialchars($usuario['telefone']); ?>" required>
          </div>

          <div class="input-group">
            <label for="cpf">CPF</label>
            <input type="text" id="cpf" name="cpf" value="<?php echo htmlspecialchars($usuario['cpf']); ?>" disabled>
            <small style="color: #999;">CPF não pode ser alterado</small>
          </div>

          <div class="input-group">
            <label for="data-nasc">Data de Nascimento</label>
            <input type="date" id="data-nasc" name="dtnas" value="<?php echo htmlspecialchars($usuario['dtnas']); ?>" required>
          </div>

          <button type="submit" class="btn-primary">Salvar Alterações</button>
        </form>

        <!-- SEÇÃO: SEGURANÇA -->
        <section class="profile-card" aria-labelledby="security-info">
          <h2 id="security-info">Segurança</h2>
          <form class="form" action="../controller/gerenciar_usuarios.php" method="post">
            <input type="hidden" name="acao" value="redefinir_senha">
            <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">

            <div class="input-group">
              <label for="senha-atual">Senha Atual</label>
              <input type="password" id="senha-atual" name="senha_atual" placeholder="••••••••" required>
            </div>

            <div class="input-group">
              <label for="senha-nova">Nova Senha</label>
              <input type="password" id="senha-nova" name="senha_nova" placeholder="••••••••" required>
            </div>

            <div class="input-group">
              <label for="senha-confirma">Confirmar Senha</label>
              <input type="password" id="senha-confirma" name="senha_confirma" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-primary">Alterar Senha</button>
          </form>
        </section>

        <!-- SEÇÃO: ZONA DE RISCO -->
        <section class="profile-card danger" aria-labelledby="danger-info">
          <h2 id="danger-info">Zona de Risco: Deseja excluir a sua conta?</h2>
          <p style="margin-bottom:16px;color:#dc3545">Ações irreversíveis.</p>
          <form action="../controller/gerenciar_usuarios.php" method="post" style="display:inline;">
            <input type="hidden" name="acao" value="excluir">
            <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
            <button type="submit" class="btn-danger" onclick="return confirm('Tem certeza que deseja deletar sua conta? Esta ação é irreversível!');">Deletar Conta</button>
          </form>
        </section>

        <!-- BOTÃO DE LOGOUT -->
        <section style="margin-top: 32px; text-align: center;">
          <form action="../controller/gerenciar_usuarios.php" method="post" style="display:inline;">
            <input type="hidden" name="acao" value="logout">
            <button type="submit" class="btn-primary" style="background-color: #6c757d;">Sair</button>
          </form>
        </section>
      </section>
    </main>

    <footer>© 2025 Aqui tem Terapia! | Contato via WhatsApp</footer>
  </div>
</body>
</html>
