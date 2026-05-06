<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$tipo = isset($_SESSION['tipo']) ? trim(strtolower($_SESSION['tipo'])) : '';
?>

<div class="overlay" id="overlay" onclick="closeMenu()"></div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h3 style="margin:0;"><?php echo htmlspecialchars($_SESSION['nome'] ?? 'Usuário'); ?></h3>
        <small style="opacity: 0.7;"><?php echo htmlspecialchars($_SESSION['tipo'] ?? ''); ?></small>
    </div>

    <nav class="sidebar-nav">
        <?php if (isset($_SESSION['usuario_id'])): ?>
            <a href="agendamento.php" class="sidebar-link">📅 Agendamentos</a>
            <a href="perfil.php" class="sidebar-link">👤 Meu Perfil</a>
            <a href="pedidos.php" class="sidebar-link">📋 Meus Pedidos</a>
            <a href="reportar_problemas.php" class="sidebar-link">🆘 Reportar Problema</a>

            <?php if ($tipo === 'administrador'): ?>
                <div class="sidebar-divider" style="display: block !important;"></div>
                <div class="sidebar-section-title" style="display: block !important;">Painel Administrativo</div>
                <a href="painel_administrador.php" class="sidebar-link admin-link">👥 Gerenciar Usuários</a>
                <a href="../controller/gerenciar_itens.php" class="sidebar-link admin-link">🛠️ Gerenciar Serviços</a>
            
            <?php elseif ($tipo === 'terapeuta'): ?>
                <div class="sidebar-divider" style="display: block !important;"></div>
                <div class="sidebar-section-title" style="display: block !important;">Área Profissional</div>
                <a href="perfil_profissional.php" class="sidebar-link admin-link">👨‍⚕️ Perfil Profissional</a>
                <a href="../controller/gerenciar_itens.php" class="sidebar-link admin-link">🛠️ Meus Serviços</a>
                <a href="disponibilidade.php" class="sidebar-link admin-link">⏰ Gerenciar Disponibilidade</a>
            <?php endif; ?> <div class="sidebar-divider"></div>
            <a href="login.php" class="sidebar-link" style="color: #ff9999 !important;">🚪 Sair da Conta</a>

        <?php else: ?> <a href="login.php" class="sidebar-link">🔐 Entrar</a>
        <?php endif; ?> </nav>
</div>

<script>
  function toggleMenu() {
    document.getElementById('sidebar').classList.add('active');
    document.getElementById('overlay').classList.add('active');
  }
  function closeMenu() {
    document.getElementById('sidebar').classList.remove('active');
    document.getElementById('overlay').classList.remove('active');
  }
</script>