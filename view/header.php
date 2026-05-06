<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define a página atual para marcar o link como ativo, se desejar
$pagina_atual = basename($_SERVER['PHP_SELF']);

// Lógica de links baseada no tipo de usuário
$usuario_logado = isset($_SESSION['usuario_id']);
$tipo_usuario = isset($_SESSION['tipo']) ? trim(strtolower($_SESSION['tipo'])) : '';
?>
<header>
  <div class="logo">
    <a href="tela_inicial.php" style="text-decoration: none; color: inherit;">Aqui tem Terapia!</a>
  </div>
  
  <nav>
    <!-- Links Públicos -->
    <a class="cta" href="tela_inicial.php">Início</a>
    <a class="cta" href="itens.php">Serviços</a>
    <a class="cta" href="profissionais.php">Profissionais</a>

    <?php if ($usuario_logado): ?>
        <!-- Links para Usuários Autenticados -->
        <a class="cta" href="agendamento.php">Agendar</a>
        <a class="cta" href="perfil.php">Meu Perfil</a>
        
        <?php if ($tipo_usuario === 'administrador'): ?>
            <a class="cta active-admin" href="painel_administrador.php">Painel Admin</a>
            <a class="cta" href="relatorios.php">Relatórios</a>
        <?php elseif ($tipo_usuario === 'terapeuta'): ?>
            <a class="cta active-prof" href="perfil_profissional.php">Área Profissional</a>
            <a class="cta" href="relatorios.php">Meus Relatórios</a>
        <?php endif; ?>

        <!-- Botão de Logout rápido (Opcional, já existe na sidebar) -->
    <?php else: ?>
        <!-- Links para Visitantes -->
        <a class="cta <?php echo ($pagina_atual === 'login.php') ? 'active' : ''; ?>" href="login.php">Entrar</a>
    <?php endif; ?>

    <!-- Botão que abre a Sidebar (mantendo sua funcionalidade atual) -->
    <button class="menu-toggle" onclick="toggleMenu()" aria-label="Abrir menu">
      <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="#333" viewBox="0 0 16 16">
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