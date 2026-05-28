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
      <!-- SEÇÃO 1: HERO MELHORADO -->
      <section class="hero-container" aria-labelledby="hero-title">
        <div class="hero-content">
          <h1 id="hero-title">Conecte-se com Profissionais de Saúde Especializados</h1>
          <p class="lead">
            Agende consultas online com terapeutas e outros profissionais de bem-estar.<br>
            Rápido, seguro e acessível.
          </p>
          <div class="cta-group">
            <a class="cta cta-primary" href="profissionais.php">Explorar Profissionais</a>
            <a class="cta cta-secondary" href="itens.php">Ver Serviços</a>
          </div>
        </div>
        <div class="hero-image">
          <div class="card" role="img" aria-label="Ilustração de atendimento terapêutico">
            <img src="img/black in peace.jpg" alt="Imagem ilustrativa de terapia">
          </div>
        </div>
      </section>

      <!-- SEÇÃO 2: POR QUE AQUI TEM TERAPIA? -->
      <section class="benefits-section" aria-labelledby="benefits-title">
        <h2 id="benefits-title">Por que Aqui tem Terapia?</h2>
        <div class="benefits-grid">
          <div class="benefit-card">
            <div class="benefit-icon">🔒</div>
            <h3>Profissionais Verificados</h3>
            <p>Todos os profissionais passam por verificação completa de credenciais e experiência.</p>
          </div>
          <div class="benefit-card">
            <div class="benefit-icon">📅</div>
            <h3>Agendamento Rápido</h3>
            <p>Marque suas consultas em poucos cliques, escolhendo data, hora e profissional.</p>
          </div>
          <div class="benefit-card">
            <div class="benefit-icon">✅</div>
            <h3>Plataforma Confiável</h3>
            <p>Atendimento seguro, privado e em conformidade com regulamentações de privacidade.</p>
          </div>
        </div>
      </section>

      <!-- SEÇÃO 3: COMO FUNCIONA? -->
      <section class="how-it-works-section" aria-labelledby="how-title">
        <h2 id="how-title">Como Funciona?</h2>
        <div class="steps-container">
          <div class="step">
            <div class="step-number">1</div>
            <h3>Explore</h3>
            <p>Navegue por serviços e profissionais especializados disponíveis na plataforma.</p>
          </div>
          <div class="step-arrow">↓</div>
          
          <div class="step">
            <div class="step-number">2</div>
            <h3>Escolha</h3>
            <p>Veja perfis, especialidades, horários disponíveis e valores de cada profissional.</p>
          </div>
          <div class="step-arrow">↓</div>
          
          <div class="step">
            <div class="step-number">3</div>
            <h3>Agende</h3>
            <p>Selecione a data e hora desejada, confirme sua escolha e faça seu pagamento. Receba confirmação de seu agendamento na plataforma.</p>
          </div>
          <div class="step-arrow">↓</div>
          
          <div class="step">
            <div class="step-number">4</div>
            <h3>Atendimento</h3>
            <p>Na data e horário confirmados, seu atendimento será realizado na modalidade escolhida com segurança e privacidade garantidas.</p>
          </div>
        </div>
      </section>
    </main>
    <?php include $view_path . 'footer.php'; ?>
  </div>
</body>
</html>