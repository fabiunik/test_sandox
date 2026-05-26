<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php"); // Redireciona para login se não estiver autenticado
    exit;
}

// Mensagens de feedback
$mensagem_sucesso = $_SESSION['success'] ?? null;
$mensagem_erro = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reportar Problema — Aqui tem Terapia</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
 
<div class="site">
    <?php include 'header.php'; ?>

    <main>
      <section class="content" style="max-width: 600px; margin: 0 auto;">
        <h1>Reportar Problema</h1>
        <p class="lead">Por favor, descreva o problema que você está enfrentando. Nossa equipe de suporte entrará em contato.</p>

        <?php if ($mensagem_sucesso): ?>
          <div class="alert alert-success">✓ <?php echo htmlspecialchars($mensagem_sucesso); ?></div>
        <?php endif; ?>

        <?php if ($mensagem_erro): ?>
          <div class="alert alert-error">✗ <?php echo htmlspecialchars($mensagem_erro); ?></div>
        <?php endif; ?>

        <form action="../controller/processar_suporte.php" method="post" class="form">
          <div class="input-group">
            <label for="assunto">Assunto</label>
            <input type="text" id="assunto" name="assunto" placeholder="Ex: Problema com agendamento, Erro no login" required>
          </div>

          <div class="input-group">
            <label for="descricao">Descreva o problema:</label>
            <textarea id="descricao" name="descricao" rows="8" placeholder="Descreva o problema aqui. Informe o que estava tentando fazer e qual mensagem de erro, se houver" required></textarea>
          </div>

          <button type="submit" class="btn-primary">Enviar Reporte</button>
        </form>
      </section>
    </main>

    <?php include $view_path . 'footer.php'; ?>
  </div>
</body>
</html>