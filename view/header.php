<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define a página atual para marcar o link como ativo, se desejar
$pagina_atual = basename($_SERVER['PHP_SELF']);

// Lógica de links baseada no tipo de usuário
$usuario_logado = isset($_SESSION['usuario_id']);
$tipo_usuario = isset($_SESSION['tipo']) ? trim(strtolower($_SESSION['tipo'])) : '';

// Ajuste de caminhos dinâmicos
$no_controller = (basename(dirname($_SERVER['PHP_SELF'])) === 'controller');
$view_path = $no_controller ? '../view/' : '';
$cont_path = $no_controller ? '' : '../controller/';
?>
<header>
  <div class="logo">
    <a href="<?php echo $view_path; ?>tela_inicial.php" style="text-decoration: none; color: inherit;">Aqui tem Terapia!</a>
  </div>
  
  <nav>
    <!-- Barra de Pesquisa Global -->
    <form action="<?php echo $cont_path; ?>pesquisa_global.php" method="GET" class="search-form-header">
      <input type="text" name="termo" placeholder="Buscar serviço ou profissional..." required>
      <button type="submit">🔍</button>
    </form>

    <!-- Links Públicos -->
    <a class="cta nav-desktop" href="<?php echo $view_path; ?>tela_inicial.php">Início</a>
    <a class="cta nav-desktop" href="<?php echo $view_path; ?>itens.php">Serviços</a>
    <a class="cta nav-desktop" href="<?php echo $view_path; ?>profissionais.php">Profissionais</a>

    <?php if (!$usuario_logado): ?>
        <!-- Links para Visitantes -->
        <a class="cta nav-desktop <?php echo ($pagina_atual === 'login.php') ? 'active' : ''; ?>" href="<?php echo $view_path; ?>login.php">Entrar</a>
    <?php endif; ?>

    <a class="cta nav-desktop" href="<?php echo $view_path; ?>contato.php">Contato</a>
    <a class="cta nav-desktop" href="<?php echo $view_path; ?>reportar_problemas.php">Reportar Problema</a>

    <!-- Botão que abre a Sidebar (mantendo sua funcionalidade atual) -->
    <button class="menu-toggle" onclick="toggleMenu()" aria-label="Abrir menu">
      <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="#fff" viewBox="0 0 16 16">
        <path d="M2 4h12v2H2V4zm0 4h12v2H2V8zm0 4h12v2H2v-2z"/>
      </svg>
    </button>
  </nav>
</header>

<?php 
/** 
 * Importante: A Sidebar deve vir logo após o header para que o 
 * z-index e o overlay funcionem corretamente em todas as páginas.
 */
include __DIR__ . '/sidebar.php'; 
?>