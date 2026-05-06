<?php
session_start();
if (isset($_SESSION['usuario_id'])) {
    header("Location: perfil.php");
    exit;
}

$mensagem_sucesso = $_SESSION['success'] ?? null;
$mensagem_erro = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login — Aqui tem Terapia</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="site">
    <?php include 'header.php'; ?>

    <main class="login-centered" style="justify-content: center; width: 100%;">
      <section class="content" style="max-width: 450px; margin: 0 auto;" aria-labelledby="login-title">
        <h1 id="login-title">Faça seu login</h1>
        <p class="lead">Acesse sua conta para gerenciar suas consultas.</p>

        <?php if ($mensagem_sucesso): ?>
          <div class="alert alert-success">✓ <?php echo htmlspecialchars($mensagem_sucesso); ?></div>
        <?php endif; ?>
        <?php if ($mensagem_erro): ?>
          <div class="alert alert-error">✗ <?php echo htmlspecialchars($mensagem_erro); ?></div>
        <?php endif; ?>

        <form class="form" action="../controller/gerenciar_usuarios.php" method="post" aria-label="Formulário de login">
          <input type="hidden" name="acao" value="login">
          <div class="input-group">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" placeholder="seu@email.com" required>
          </div>
          <div class="input-group">
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" placeholder="••••••••" required>
          </div>
          <button type="submit" class="btn-primary">Entrar</button>
          <p style="text-align:center;margin-top:16px;color:var(--muted)">
            Não tem conta? <a href="cadastro.php" style="color:var(--cta-bg);font-weight:700;text-decoration:none">Cadastre-se aqui</a>
          </p>
          <p style="text-align:center;margin-top:16px;color:var(--muted)">
            Esqueceu a senha? <a href="recuperar_senha.php" style="color:var(--cta-bg);font-weight:700;text-decoration:none">Recuperar senha</a>
          </p>
        </form>
      </section>
    </main>
    <footer>© 2025 Aqui tem Terapia!</footer>
  </div>
</body>
</html>