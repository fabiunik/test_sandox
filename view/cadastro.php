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
  <title>Cadastro — Aqui tem Terapia</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="site">
    <?php include 'header.php'; ?>

    <main class="login-centered">
      <section class="content" style="max-width: 500px; margin: 0 auto;" aria-labelledby="cadastro-title">
        <h1 id="cadastro-title">Crie sua conta</h1>
        <p class="lead">Junte-se a nós e comece sua jornada de bem-estar.</p>

        <?php if ($mensagem_erro): ?>
          <div class="alert alert-error">✗ <?php echo htmlspecialchars($mensagem_erro); ?></div>
        <?php endif; ?>

        <form class="form" action="../controller/gerenciar_usuarios.php" method="post">
          <input type="hidden" name="acao" value="criar">
          
          <div class="input-group">
            <label for="nome">Nome Completo</label>
            <input type="text" id="nome" name="nome" placeholder="Seu nome completo" required>
          </div>
          <div class="input-group">
            <label for="cpf">CPF</label>
            <input type="text" id="cpf" name="cpf" placeholder="000.000.000-00" required>
          </div>
          <div class="input-group">
            <label for="dtnas">Data de Nascimento</label>
            <input type="date" id="dtnas" name="dtnas" required>
          </div>
          <div class="input-group">
            <label for="telefone">Telefone</label>
            <input type="tel" id="telefone" name="telefone" placeholder="(00) 00000-0000" required>
          </div>
          <div class="input-group">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" placeholder="seu@email.com" required>
          </div>
          <div class="input-group">
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" placeholder="••••••••" required>
          </div>

          <button type="submit" class="btn-primary">Finalizar Cadastro</button>
          
          <p style="text-align:center;margin-top:16px;color:var(--muted)">
            Já tem uma conta? <a href="login.php" style="color:var(--cta-bg);font-weight:700;text-decoration:none">Faça login</a>
          </p>
        </form>
      </section>
    </main>
    <footer>© 2025 Aqui tem Terapia!</footer>
  </div>
</body>
</html>