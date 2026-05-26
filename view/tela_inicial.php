<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Aqui tem terapia!!</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="site">
    <?php include 'header.php'; ?>

    <main>
      <section class="hero" aria-labelledby="hero-title">
        <h1 id="hero-title">Seu portal de<br>saúde e bem-estar</h1>
        <p class="lead">
          Encontre profissionais, marque atendimentos e acompanhe seu progresso.
        </p>
        <a class="cta" href="agendamento.php">AGENDAR CONSULTA</a>
      </section>

      <aside class="visual" >
        <div class="card" role="img" aria-label="Ilustração de atendimento terapêutico">
          <img src="img/black in peace.jpg" alt="Imagem ilustrativa de terapia">
        </div>
      </aside>
    </main>
    <footer>© <?php echo date('Y'); ?> Aqui tem Terapia! | Contato via WhatsApp</footer>
  </div>
</body>
</html>