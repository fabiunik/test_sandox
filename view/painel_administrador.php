<?php
session_start();

// ====== VALIDAÇÃO DE ADMIN ======
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'administrador') {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../controller/conectarBD.php';
require_once __DIR__ . '/../model/Usuario.php';

$usuarioModel = new Usuario($pdo);
$usuarioAtual = $usuarioModel->obterPorId($_SESSION['usuario_id']);

// ====== PROCESSAMENTO DE AÇÕES ======
$mensagem_sucesso = $_SESSION['success'] ?? null;
$mensagem_erro = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Buscar/filtrar usuários
$busca = $_GET['busca'] ?? '';
$filtro_tipo = $_GET['tipo'] ?? '';

if (!empty($busca) || !empty($filtro_tipo)) {
    $usuarios = $usuarioModel->listar();

    // Filtrar por busca (nome ou email)
    if (!empty($busca)) {
        $usuarios = array_filter($usuarios, function($u) use ($busca) {
            $termo = strtolower($busca);
            return strpos(strtolower($u['nome']), $termo) !== false ||
                   strpos(strtolower($u['email']), $termo) !== false;
        });
    }

    // Filtrar por tipo
    if (!empty($filtro_tipo)) {
        $usuarios = array_filter($usuarios, function($u) use ($filtro_tipo) {
            return $u['tipo'] === $filtro_tipo;
        });
    }
} else {
    $usuarios = $usuarioModel->listar();
}

// Calcular estatísticas
$total_usuarios = count($usuarios);
$total_admins = count(array_filter($usuarios, fn($u) => $u['tipo'] === 'administrador'));
$total_terapeutas = count(array_filter($usuarios, fn($u) => $u['tipo'] === 'terapeuta'));
$total_usuarios_comuns = count(array_filter($usuarios, fn($u) => $u['tipo'] === 'usuario'));
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Painel Administrativo — Aqui tem Terapia</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="gerenciar-usuarios">
  <div class="site">
    <?php include 'header.php'; ?>

    <main class="container">
        <!-- ALERTAS -->
        <?php if ($mensagem_sucesso): ?>
            <div class="alert alert-success">✓ <?php echo htmlspecialchars($mensagem_sucesso); ?></div>
        <?php endif; ?>

        <?php if ($mensagem_erro): ?>
            <div class="alert alert-error">✗ <?php echo htmlspecialchars($mensagem_erro); ?></div>
        <?php endif; ?>

        <!-- HEADER -->
        <div class="header-section">
            <h2>🔑 Painel Administrativo</h2>
            <p class="subtitle">Bem-vindo, <?php echo htmlspecialchars($usuarioAtual['nome']); ?></p>
        </div>

        <!-- DASHBOARD ESTATÍSTICAS -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <h3><?php echo $total_usuarios; ?></h3>
                <p>Total de Usuários</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_admins; ?></h3>
                <p>Administradores</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_terapeutas; ?></h3>
                <p>Terapeutas</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_usuarios_comuns; ?></h3>
                <p>Usuários</p>
            </div>
        </div>

        <!-- FILTROS E BUSCA -->
        <div class="filter-section">
            <form action="" method="get" class="filter-form">
                <input type="text" name="busca" placeholder="Buscar por nome ou e-mail..."
                       value="<?php echo htmlspecialchars($busca); ?>" class="filter-input">

                <select name="tipo" class="filter-select">
                    <option value="">Filtrar por tipo...</option>
                    <option value="usuario" <?php echo $filtro_tipo === 'usuario' ? 'selected' : ''; ?>>Usuário</option>
                    <option value="terapeuta" <?php echo $filtro_tipo === 'terapeuta' ? 'selected' : ''; ?>>Terapeuta</option>
                    <option value="administrador" <?php echo $filtro_tipo === 'administrador' ? 'selected' : ''; ?>>Administrador</option>
                </select>

                <button type="submit">🔍 Buscar</button>
                <a href="?" class="filter-link">Limpar</a>
            </form>
        </div>

        <!-- TABELA DE USUÁRIOS -->
        <div class="container">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Telefone</th>
                        <th>Tipo</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($usuarios) > 0): ?>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($usuario['nome']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['telefone']); ?></td>
                                <td>
                                    <?php if ($usuario['tipo'] === 'administrador'): ?>
                                        <span class="badge badge-adm">Administrador</span>
                                    <?php elseif ($usuario['tipo'] === 'terapeuta'): ?>
                                        <span class="badge badge-prof">Terapeuta</span>
                                    <?php else: ?>
                                        <span class="badge badge-user">Usuário</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn-action btn-edit"
                                            onclick="openEditModal(<?php echo htmlspecialchars(json_encode($usuario)); ?>)">
                                        ✏️ Editar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty-message">
                                Nenhum usuário encontrado
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <footer>© 2025 Aqui tem Terapia! | Contato via WhatsApp</footer>
  </div>

  <!-- MODAL DE EDIÇÃO -->
  <div class="modal" id="editModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Editar Usuário</h2>
            <button class="close-modal" onclick="closeEditModal()">✕</button>
        </div>

        <form action="../controller/gerenciar_usuarios.php" method="post">
            <input type="hidden" name="acao" value="editar_tipo">
            <input type="hidden" name="usuario_id" id="edit_usuario_id">

            <div class="form-group">
                <label>Nome</label>
                <input type="text" id="edit_nome" readonly>
            </div>

            <div class="form-group">
                <label>E-mail</label>
                <input type="email" id="edit_email" readonly>
            </div>

            <div class="form-group">
                <label for="edit_tipo">Tipo de Usuário</label>
                <select name="novo_tipo" id="edit_tipo" required>
                    <option value="usuario">Usuário</option>
                    <option value="terapeuta">Terapeuta</option>
                    <option value="administrador">Administrador</option>
                </select>
            </div>

            <div class="modal-buttons">
                <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancelar</button>
                <button type="submit" class="btn-save">Salvar Alterações</button>
            </div>
        </form>
    </div>
  </div>

  <script>
    function openEditModal(usuario) {
        document.getElementById('edit_usuario_id').value = usuario.id;
        document.getElementById('edit_nome').value = usuario.nome;
        document.getElementById('edit_email').value = usuario.email;
        document.getElementById('edit_tipo').value = usuario.tipo;
        document.getElementById('editModal').classList.add('active');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.remove('active');
    }

    // Fechar modal ao clicar fora
    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });

    // Fechar com ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeEditModal();
        }
    });
  </script>
</body>
</html>
