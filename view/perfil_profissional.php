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
    <header>
      <div class="logo">Aqui tem Terapia!</div>
      <nav>
        <a class="cta" href="tela_inicial.html">Home</a>
        <a class="cta" href="agendamento.html">Agendar</a>
        <a class="cta" href="itens.html">Serviços</a>
        <a class="cta" href="contato.html">Contato</a>
        <button class="menu-toggle" onclick="toggleMenu()" aria-label="Abrir menu">
          <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="#333" viewBox="0 0 16 16">
            <path d="M2 4h12v2H2V4zm0 4h12v2H2V8zm0 4h12v2H2v-2z"/>
          </svg>
        </button>
      </nav>
    </header>

    <main>
      <div class="container">
      <?php if ($perfil): ?>
        <div class="card">
          <div class="profile-header">
            <div class="avatar-cont">
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
        </div>

        <?php if ($mensagemPerfil): ?>
          <div class="alert alert-success"><?php echo htmlspecialchars($mensagemPerfil); ?></div>
        <?php endif; ?>

        <?php if ($erroPerfil): ?>
          <div class="alert alert-error"><?php echo htmlspecialchars($erroPerfil); ?></div>
        <?php endif; ?>

        <?php if ($modoEdicao): ?>
          <div class="card">
            <form method="post" enctype="multipart/form-data" class="gerenciar-perfil">
              <input type="hidden" name="acao" value="salvar_perfil">

              <div class="form-group">
                <label for="foto">Foto de perfil</label>
                <div class="avatar-cont" style="width: 180px; height: 180px; margin-bottom: 15px;">
                  <img src="<?php echo htmlspecialchars($fotoUrl); ?>" alt="Foto de perfil">
                </div>
                <input type="file" name="foto" id="foto" accept="image/*">
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
          <div class="card">
            <div class="profile-header">
              <div class="avatar-cont">
                <img src="<?php echo htmlspecialchars($fotoUrl); ?>" alt="<?php echo htmlspecialchars($perfil['nome']); ?>">
              </div>
              <div>
                <h2>Especialidades</h2>
                <p><?php echo nl2br(htmlspecialchars($perfil['especialidades'] ?? 'Não informado')); ?></p>
                <h2>Experiência</h2>
                <p><?php echo nl2br(htmlspecialchars($perfil['experiencia'] ?? 'Não informado')); ?></p>
                <h2>Descrição</h2>
                <p><?php echo nl2br(htmlspecialchars($perfil['descricao'] ?? 'Não informado')); ?></p>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <a class="cta" href="profissionais.php">← Voltar para lista de profissionais</a>
      <?php else: ?>
        <div class="card">
          <p>Profissional não encontrado.</p>
          <a class="cta" href="profissionais.php">← Voltar para lista de profissionais</a>
        </div>
      <?php endif; ?>
      </div>
    </main>
  </div>
</body>
</html>
