<?php
session_start();
$token = $_GET['token'] ?? '';
$mensagem_erro = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

if (empty($token)) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Redefinir Senha — Aqui tem Terapia</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="site">
    <?php include 'header.php'; ?>

    <main class="login-centered">
      <section class="content" style="max-width: 450px; margin: 0 auto;">
        <h1>Nova Senha</h1>
        <p class="lead">Crie uma nova senha segura para sua conta.</p>

        <?php if ($mensagem_erro): ?>
          <div class="alert alert-error">✗ <?php echo htmlspecialchars($mensagem_erro); ?></div>
        <?php endif; ?>

        <form class="form" action="../controller/gerenciar_usuarios.php" method="post">
          <input type="hidden" name="acao" value="redefinir_senha_token">
          <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
          
          <div class="input-group">
            <label for="nova_senha">Nova Senha</label>
            <input type="password" id="nova_senha" name="nova_senha" placeholder="••••••••" required>
          </div>
          <div class="input-group">
            <label for="confirma_senha">Confirmar Nova Senha</label>
            <input type="password" id="confirma_senha" name="confirma_senha" placeholder="••••••••" required>
          </div>
          <button type="submit" class="btn-primary">Atualizar Senha</button>
        </form>
      </section>
    </main>
  </div>
  <script>
    document.querySelector('.form').onsubmit = function(e) {
        if (document.getElementById('nova_senha').value !== document.getElementById('confirma_senha').value) {
            alert('As senhas não coincidem.');
            e.preventDefault();
        }
    };
  </script>
</body>
</html>