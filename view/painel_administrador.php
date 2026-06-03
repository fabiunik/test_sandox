<?php
session_start();

// ====== VALIDAÇÃO DE ADMIN ======
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'administrador') {
    header("Location: login.php");
    exit;
}

// ====== INICIALIZAR CSRF TOKEN ======
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../controller/conectarBD.php';
require_once __DIR__ . '/../model/Usuario.php';


$no_controller = (basename(dirname($_SERVER['PHP_SELF'])) === 'controller');
$view_path = $no_controller ? '../view/' : '';

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

// ====== LÓGICA DE PAGINAÇÃO ======
$itensPorPagina = 6;
$totalUsuariosTotal = count($usuarios);
$totalPaginas = ceil($totalUsuariosTotal / $itensPorPagina);
$paginaAtual = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$offset = ($paginaAtual - 1) * $itensPorPagina;
$usuariosExibir = array_slice($usuarios, $offset, $itensPorPagina);
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
            <h1 class="page-title">🔑 Painel Administrativo</h1>
            <p class="subtitle">Bem-vindo, <?php echo htmlspecialchars($usuarioAtual['nome']); ?></p>

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
                <p>Usuários Comuns</p>
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

                <button type="submit" class="btn-search">🔍 Buscar</button>
                <a href="?" class="filter-link">Limpar</a>
            </form>
        </div>

        <!-- TABELA DE USUÁRIOS -->
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($usuariosExibir) > 0): ?>
                        <?php foreach ($usuariosExibir as $usuario): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($usuario['nome']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                <td>
                                    <?php if ($usuario['tipo'] === 'administrador'): ?>
                                        <span class="badge badge-adm">Administrador</span>
                                    <?php elseif ($usuario['tipo'] === 'terapeuta'): ?>
                                        <span class="badge badge-prof">Terapeuta</span>
                                    <?php else: ?>
                                        <span class="badge badge-user">Usuário</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $usuario['status'] === 'ativo' ? 'status-confirmado' : 'status-pendente'; ?>">
                                        <?php echo ucfirst($usuario['status']); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-actions-group">
                                        <button class="btn-action btn-edit" onclick="openEditModal('<?php echo htmlspecialchars(json_encode($usuario), ENT_QUOTES, 'UTF-8'); ?>')" type="button">✏️ Cargo</button>
                                        <form action="../controller/gerenciar_usuarios.php" method="POST" class="btn-form">
                                            <input type="hidden" name="acao" value="alterar_status">
                                            <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <?php if($usuario['status'] === 'ativo'): ?>
                                                <input type="hidden" name="status" value="inativo">
                                                <button type="submit" class="btn-action" style="color:#dc3545; border-color:#f5c6cb;">🚫 Inativar</button>
                                            <?php else: ?>
                                                <input type="hidden" name="status" value="ativo">
                                                <button type="submit" class="btn-action" style="color:#28a745; border-color:#c3e6cb;">✅ Ativar</button>
                                            <?php endif; ?>
                                        </form>
                                    </div>
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

            <!-- CONTROLES DE PAGINAÇÃO -->
            <?php if ($totalPaginas > 1): ?>
                <div class="pagination">
                    <?php if ($paginaAtual > 1): ?>
                        <a href="?p=<?php echo $paginaAtual - 1; ?>&busca=<?php echo urlencode($busca); ?>&tipo=<?php echo urlencode($filtro_tipo); ?>" class="btn-secondary">← Anterior</a>
                    <?php endif; ?>
                    
                    <span>Página <?php echo $paginaAtual; ?> de <?php echo $totalPaginas; ?></span>

                    <?php if ($paginaAtual < $totalPaginas): ?>
                        <a href="?p=<?php echo $paginaAtual + 1; ?>&busca=<?php echo urlencode($busca); ?>&tipo=<?php echo urlencode($filtro_tipo); ?>" class="btn-secondary">Próxima →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>
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
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

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
    function openEditModal(usuarioJson) {
        try {
            const usuario = JSON.parse(usuarioJson);
            document.getElementById('edit_usuario_id').value = usuario.id;
            document.getElementById('edit_nome').value = usuario.nome;
            document.getElementById('edit_email').value = usuario.email;
            document.getElementById('edit_tipo').value = usuario.tipo;
            document.getElementById('editModal').classList.add('active');
        } catch(e) {
            console.error('Erro ao abrir modal:', e);
            alert('Erro ao abrir modal de edição');
        }
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
