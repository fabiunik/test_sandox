<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contato — Aqui tem Terapia</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="site">
    <?php include 'header.php'; ?>

    <main class="services-wrapper">
      <h1>Contato</h1>
      <div class="hero">
        <div class="lead">
          <p><strong>TELEFONE/WHATSAPP:</strong><br>(123) 456-7890</p>
          <p><strong>EMAIL:</strong><br>contato@aquitemterapia.com.br</p>
          <p><strong>SOCIAL:</strong><br>
            <a href="#" class="social-link">Facebook</a>
            <a href="#" class="social-link">Instagram</a>
            <a href="#" class="social-link">LinkedIn</a>
          </p>
        </div>
        <aside class="visual" >
        <div class="card">
          <img src="img/zap_contato.webp" alt="Imagem do WhatsApp">
        </div>
      </aside>
    </main>
    <footer>© 2025 Aqui tem Terapia!</footer>
  </div>
</body>
</html>