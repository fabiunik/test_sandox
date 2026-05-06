<?php
require_once __DIR__ . '/../controller/conectarBD.php';
require_once __DIR__ . '/../model/Usuario.php';

$usuarioModel = new Usuario($pdo);
$terapeutas = $usuarioModel->listarTerapeutas();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Profissionais — Aqui tem Terapia</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="site">
    <?php include 'header.php'; ?>

    <main>
      <section class="services-wrapper" aria-label="Lista de profissionais">
        <h1 class="page-title">Nossos profissionais</h1>
        <p class="lead">Equipe multidisciplinar qualificada — conheça nossos especialistas e agende seu atendimento.</p>

        <div class="profissionais-cards">
          <?php
          if (!empty($terapeutas)) {
              foreach ($terapeutas as $terapeuta) {
                  $id = $terapeuta['id'];
                  $nome = htmlspecialchars($terapeuta['nome']);
                  $descricao = isset($terapeuta['descricao']) ? htmlspecialchars(substr($terapeuta['descricao'], 0, 150)) . '...' : 'Conheça nosso profissional.';
                  ?>
                  <article class="service-card" aria-labelledby="p-<?php echo $id; ?>">
                      <img class="avatar" 
                          src="<?php echo !empty($terapeuta['imagem']) ? '../' . $terapeuta['imagem'] : 'https://placehold.co/239x239'; ?>" 
                          alt="<?php echo $nome; ?>">
                    <h3 id="p-<?php echo $id; ?>"><?php echo $nome; ?></h3>
                    <p><?php echo $descricao; ?></p>
                    <a class="cta" href="perfil_profissional.php?id=<?php echo $id; ?>">Ver perfil</a>
                  </article>
                  <?php
              }
          } else {
              echo '<p>Nenhum profissional disponível no momento.</p>';
          }
          ?>
        </div>
      </section>

      <footer>© 2025 Aqui tem Terapia! | Contato via WhatsApp</footer>
    </main>
  </div>
</body>
</html>
