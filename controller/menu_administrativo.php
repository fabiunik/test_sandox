<?php if (isset($_SESSION['tipo'])): ?>
    <nav>
        <ul>
            <?php if ($_SESSION['tipo'] === 'administrador'): ?>
                <li><a href="gerenciar_itens.php">Gerenciar Serviços</a></li>
                <li><a href="gerenciar_usuarios.php">Gerenciar Usuários</a></li>
                <li><a href="gerenciar_perfil.php">Gerenciar Perfis</a></li>
            <?php elseif ($_SESSION['tipo'] === 'terapeuta'): ?>
                <li><a href="gerenciar_itens.php">Gerenciar Serviços</a></li>
                <li><a href="gerenciar_perfil.php">Meu Perfil</a></li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>
