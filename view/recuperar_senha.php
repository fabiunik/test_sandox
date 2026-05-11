<?php
session_start();
$mensagem_erro = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Recuperar Senha — Aqui tem Terapia</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="site">
    <?php include 'header.php'; ?>

    <main class="login-centered">
      <section class="content" style="max-width: 450px; margin: 0 auto;">
        <h1>Recuperar Senha</h1>
        <p class="lead">Insira seu e-mail para receber as instruções de redefinição.</p>

        <?php if ($mensagem_erro): ?>
          <div class="alert alert-error">✗ <?php echo htmlspecialchars($mensagem_erro); ?></div>
        <?php endif; ?>

        <form class="form" action="../controller/gerenciar_usuarios.php" method="post">
          <input type="hidden" name="acao" value="recuperar_senha">
          <div class="input-group">
            <label for="email">E-mail Cadastrado</label>
            <input type="email" id="email" name="email" placeholder="seu@email.com" required>
          </div>
          <button type="submit" class="btn-primary">Enviar Link de Recuperação</button>
        </form>
      </section>
    </main>
  </div>
</body>
</html>