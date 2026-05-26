<?php
session_start();
require_once __DIR__ . '/../controller/conectarBD.php';
require_once __DIR__ . '/../model/Usuario.php';

try {
    $busca = $_GET['busca'] ?? null;
    $usuarioModel = new Usuario($pdo);
    $terapeutas = $usuarioModel->listarTerapeutas($busca);
} catch (Exception $e) {
    $erro_db = $e->getMessage();
}
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

        <?php if (isset($erro_db)): ?>
            <div class="alert alert-error">Erro ao carregar dados: <?php echo htmlspecialchars($erro_db); ?></div>
        <?php endif; ?>

        <?php if ($busca): ?>
          <p>Mostrando profissionais que combinam com: <strong>"<?php echo htmlspecialchars($busca); ?>"</strong> | <a href="profissionais.php">Limpar busca</a></p>
        <?php endif; ?>

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
    </main>
    <?php include $view_path . 'footer.php'; ?>
  </div>
</body>
</html>
