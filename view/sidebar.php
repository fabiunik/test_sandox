<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$tipo = isset($_SESSION['tipo']) ? trim(strtolower($_SESSION['tipo'])) : '';

// Ajuste de caminhos dinâmicos
$no_controller = (basename(dirname($_SERVER['PHP_SELF'])) === 'controller');
$view_path = $no_controller ? '../view/' : '';
$cont_path = $no_controller ? '' : '../controller/';
?>

<div class="overlay" id="overlay" onclick="closeMenu()"></div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h3 style="margin:0;"><?php echo htmlspecialchars($_SESSION['nome'] ?? 'Usuário'); ?></h3>
        <small style="opacity: 0.7;"><?php echo htmlspecialchars($_SESSION['tipo'] ?? ''); ?></small>
    </div>

    <nav class="sidebar-nav">
        <?php if (isset($_SESSION['usuario_id'])): ?>
            <a href="<?php echo $view_path; ?>agendamento.php" class="sidebar-link">➕ Novo Agendamento</a>
            <a href="<?php echo $view_path; ?>meus_agendamentos.php" class="sidebar-link">📅 Minha Agenda</a>
            <a href="<?php echo $view_path; ?>perfil.php" class="sidebar-link">👤 Meu Perfil</a>
            <a href="<?php echo $view_path; ?>pedidos.php" class="sidebar-link">📋 Meus Pedidos</a>
            <a href="<?php echo $view_path; ?>reportar_problemas.php" class="sidebar-link">🆘 Reportar Problema</a>

            <?php if ($tipo === 'administrador'): ?>
                <div class="sidebar-divider" style="display: block !important;"></div>
                <div class="sidebar-section-title" style="display: block !important;">Painel Administrativo</div>
                <a href="<?php echo $view_path; ?>painel_administrador.php" class="sidebar-link admin-link">👥 Gerenciar Usuários</a>
                <a href="<?php echo $view_path; ?>relatorios.php" class="sidebar-link admin-link">📊 Relatórios</a>
                <a href="<?php echo $cont_path; ?>gerenciar_itens.php" class="sidebar-link admin-link">🛠️ Gerenciar Serviços</a>
            
            <?php elseif ($tipo === 'terapeuta'): ?>
                <div class="sidebar-divider" style="display: block !important;"></div>
                <div class="sidebar-section-title" style="display: block !important;">Área Profissional</div>
                <a href="<?php echo $view_path; ?>perfil_profissional.php" class="sidebar-link">👨‍⚕️ Perfil Profissional</a>
                <a href="<?php echo $view_path; ?>relatorios.php" class="sidebar-link">📊 Meus Relatórios</a>
                <a href="<?php echo $cont_path; ?>gerenciar_itens.php" class="sidebar-link">🛠️ Meus Serviços</a>
                <a href="<?php echo $view_path; ?>disponibilidade.php" class="sidebar-link">⏰ Gerenciar Disponibilidade</a>
            <?php endif; ?> <div class="sidebar-divider"></div>
            <a href="<?php echo $view_path; ?>logout.php" class="sidebar-link" style="color: #ff9999 !important;">🚪 Sair da Conta</a>

        <?php else: ?> <a href="<?php echo $view_path; ?>login.php" class="sidebar-link">🔐 Entrar</a>
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