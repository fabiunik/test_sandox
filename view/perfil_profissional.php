<?php
require_once __DIR__ . '/../controller/gerenciar_perfil_profissional.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Perfil do Profissional — Aqui tem Terapia</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="gerenciar-perfil">
  <div class="site">
    <?php include 'header.php'; ?>

    <main class="services-wrapper">
        <?php if ($perfil): ?>
          <div class="profile-header">
            <div class="avatar-cont" style="width: 180px; height: 180px;">
              <img src="<?php echo htmlspecialchars($fotoUrl); ?>" alt="<?php echo htmlspecialchars($perfil['nome']); ?>">
            </div>
            <div>
              <h1><?php echo htmlspecialchars($perfil['nome']); ?></h1>
              <?php if ($modoEdicao): ?>
                <p>Atualize suas informações profissionais e faça upload da sua foto.</p>
              <?php else: ?>
                <p>Conheça o perfil profissional deste terapeuta.</p>
              <?php endif; ?>
            </div>
          </div>
        
        <?php if ($mensagemPerfil): ?>
          <div class="alert alert-success"><?php echo htmlspecialchars($mensagemPerfil); ?></div>
        <?php endif; ?>

        <?php if ($erroPerfil): ?>
          <div class="alert alert-error"><?php echo htmlspecialchars($erroPerfil); ?></div>
        <?php endif; ?>

        <?php if ($modoEdicao): ?>
          <div class="profile-section">
            <form method="post" enctype="multipart/form-data" class="gerenciar-perfil">
              <input type="hidden" name="acao" value="salvar_perfil">

              <div class="form-group">
                <label for="foto">Foto de perfil</label>
                <input type="file" name="foto" id="foto" accept="image/*" style="padding: 10px; border: 1px dashed #ccc;">
              </div>

              <div class="form-group">
                <label for="especialidades">Especialidades</label>
                <input type="text" name="especialidades" id="especialidades"
                       value="<?php echo htmlspecialchars($perfil['especialidades'] ?? ''); ?>"
                       placeholder="Ex: Reiki, Terapia Floral">
              </div>

              <div class="form-group">
                <label for="experiencia">Experiência</label>
                <textarea name="experiencia" id="experiencia" rows="6"
                          placeholder="Conte sua experiência profissional..."><?php echo htmlspecialchars($perfil['experiencia'] ?? ''); ?></textarea>
              </div>

              <div class="form-group">
                <label for="descricao">Descrição biográfica</label>
                <textarea name="descricao" id="descricao" rows="10"
                          placeholder="Apresente-se aos clientes..."><?php echo htmlspecialchars($perfil['descricao'] ?? ''); ?></textarea>
              </div>

              <button type="submit" class="btn-primary">Salvar Perfil</button>
            </form>
          </div>
        <?php else: ?>
          <div class="profile-section" style="margin-top: 30px; border-top: 1px solid #eee; pt: 20px;">
            <h2>Especialidades</h2>
            <p style="margin-bottom: 25px; font-size: 1.1rem;"><?php echo nl2br(htmlspecialchars($perfil['especialidades'] ?? 'Não informado')); ?></p>
            
            <h2>Experiência</h2>
            <p style="margin-bottom: 25px; font-size: 1.1rem;"><?php echo nl2br(htmlspecialchars($perfil['experiencia'] ?? 'Não informado')); ?></p>
            
            <h2>Descrição</h2>
            <p style="font-size: 1.1rem; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($perfil['descricao'] ?? 'Não informado')); ?></p>
          </div>
        <?php endif; ?>

        <div style="margin-top: 40px; border-top: 1px solid #eee; padding-top: 20px;">
            <a class="cta" href="profissionais.php">← Voltar para lista de profissionais</a>
        </div>
      <?php else: ?>
        <div class="alert alert-error">
          <p>Profissional não encontrado.</p>
        </div>
        <a class="cta" href="profissionais.php" style="margin-top: 20px; display: inline-block;">← Voltar</a>
        <?php endif; ?>
    </main>
    <footer style="margin-top: 40px;">© 2025 Aqui tem Terapia! | Contato via WhatsApp</footer>
  </div>
</body>
</html>
