<?php
session_start();
require_once '../controller/conectarBD.php';
require_once '../model/Item.php';

// Verificação de Segurança
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.html");
    exit;
}

$usuarioLogadoId = $_SESSION['usuario_id'];
$usuarioLogadoTipo = $_SESSION['tipo'] ?? '';

if ($usuarioLogadoTipo !== 'terapeuta') {
    echo "<p>Acesso negado. Apenas terapeutas podem gerenciar itens.</p>";
    exit;
}

$mensagem_sucesso = "";
$mensagem_erro = "";

// Instanciar ItemModel
$itemModel = new Item($pdo);

// --- PROCESSAMENTO DE FORMULÁRIOS (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. CADASTRAR NOVO SERVIÇO
    if (isset($_POST['acao']) && $_POST['acao'] === 'cadastrar') {
        $nome = $_POST['nome'];
        $descricao = $_POST['descricao'];
        $valor = $_POST['valor'];
        $imagem = null;

        if (!empty($_FILES['imagem']['name'])) {
            $targetDir = "../uploads/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            
            $fileName = time() . "_" . basename($_FILES["imagem"]["name"]);
            $targetFile = $targetDir . $fileName;
            
            if (move_uploaded_file($_FILES["imagem"]["tmp_name"], $targetFile)) {
                $imagem = "uploads/" . $fileName;
            }
        }

        if ($itemModel->cadastrar($usuarioLogadoId, $nome, $descricao, $valor, $imagem)) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?msg=cadastrado");
            exit;
        }

    // 2. EDITAR SERVIÇO (COM TRATAMENTO DE IMAGEM ANTIGA)
    } elseif (isset($_POST['acao']) && $_POST['acao'] === 'editar') {
        $id = $_POST['id'];
        $nome = $_POST['nome'];
        $descricao = $_POST['descricao'];
        $valor = $_POST['valor'];

        if (!empty($_FILES['imagem']['name'])) {
            // Busca imagem antiga para deletar do servidor
            $stmtImg = $pdo->prepare("SELECT imagem FROM itens WHERE id = ? AND terapeuta_id = ?");
            $stmtImg->execute([$id, $usuarioLogadoId]);
            $itemAntigo = $stmtImg->fetch();

            if ($itemAntigo && $itemAntigo['imagem'] && file_exists("../" . $itemAntigo['imagem'])) {
                unlink("../" . $itemAntigo['imagem']);
            }

            // Sobe a nova imagem
            $targetDir = "../uploads/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $fileName = time() . "_" . basename($_FILES["imagem"]["name"]);
            move_uploaded_file($_FILES["imagem"]["tmp_name"], $targetDir . $fileName);
            $novoCaminho = "uploads/" . $fileName;

            $itemModel->editar($id, $nome, $descricao, $valor, $novoCaminho, $usuarioLogadoId);
        } else { // Update sem mexer na imagem
            $itemModel->editar($id, $nome, $descricao, $valor, null, $usuarioLogadoId);
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?msg=editado");
        exit;

    // 3. EXCLUIR SERVIÇO (DELETA REGISTRO E ARQUIVO FÍSICO)
    } elseif (isset($_POST['acao']) && $_POST['acao'] === 'excluir') {
        $id = $_POST['id'];
        
        $stmtImg = $pdo->prepare("SELECT imagem FROM itens WHERE id = ? AND terapeuta_id = ?");
        $stmtImg->execute([$id, $usuarioLogadoId]);
        $item = $stmtImg->fetch();

        if ($item && $item['imagem'] && file_exists("../" . $item['imagem'])) {
            unlink("../" . $item['imagem']);
        }

        $itemModel->excluir($id, $usuarioLogadoId);
        header("Location: " . $_SERVER['PHP_SELF'] . "?msg=excluido");
        exit;
    }
}

// Mensagens de Feedback
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'cadastrado') $mensagem_sucesso = "Serviço cadastrado com sucesso!";
    if ($_GET['msg'] == 'editado') $mensagem_sucesso = "Serviço atualizado com sucesso!";
    if ($_GET['msg'] == 'excluido') $mensagem_sucesso = "Serviço e imagem removidos!";
}

// --- LÓGICA DE PAGINAÇÃO ---
$itensPorPagina = 5;
$paginaAtual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($paginaAtual - 1) * $itensPorPagina;

// Busca o total de itens para o terapeuta logado
$totalItens = $itemModel->contarPorTerapeuta($usuarioLogadoId);
$totalPaginas = ceil($totalItens / $itensPorPagina);

// Busca os itens paginados
$itens = $itemModel->listarPorTerapeuta($usuarioLogadoId, $itensPorPagina, $offset);


?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Gerenciar Serviços — Aqui tem Terapia</title>
    <link rel="stylesheet" href="../view/style.css">
</head>
<body class="gerenciar-itens">
  <div class="site">
    <?php include '../view/header.php'; ?>

    <main class="services-wrapper">
        <?php if ($mensagem_sucesso): ?>
            <div class="alert alert-success">✓ <?php echo $mensagem_sucesso; ?></div>
        <?php endif; ?>

        <div class="header-section">
            <h2>🌿 Gerenciamento de Serviços</h2>
            <p class="subtitle">Organize suas ofertas e mantenha seus dados atualizados.</p>
        </div>

        <!-- CADASTRO -->
        <section class="container" style="background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <h3 style="margin-top:0">Cadastrar Novo Serviço</h3>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="acao" value="cadastrar">
                <div class="form-group">
                    <label>Nome do Serviço</label>
                    <input type="text" name="nome" placeholder="Ex: Psicoterapia Individual" required>
                </div>
                <div class="form-group">
                    <label>Descrição</label>
                    <textarea name="descricao" rows="2" placeholder="Detalhes do serviço..."></textarea>
                </div>
                <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                    <div class="form-group" style="flex: 1; min-width: 200px;">
                        <label>Valor (R$)</label>
                        <input type="number" step="0.01" name="valor" required>
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 200px;">
                        <label>Foto do Serviço</label>
                        <input type="file" name="imagem" accept="image/*">
                    </div>
                </div>
                <button type="submit" class="btn-save" style="width: 100%; padding: 12px;">➕ Salvar Serviço</button>
            </form>
        </section>

        <!-- LISTAGEM -->
        <div class="container">
            <table>
                <thead>
                    <tr>
                        <th width="60">ID</th>
                        <th width="100">Foto</th>
                        <th>Serviço</th>
                        <th>Valor</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itens as $item): ?>
                    <tr>
                        <td><span class="badge badge-user"><?php echo str_pad($item['id'], 3, '0', STR_PAD_LEFT); ?></span></td>
                        <td>
                            <img src="<?php echo !empty($item['imagem']) ? '../' . $item['imagem'] : 'https://placehold.co/80x60?text=Sem+Foto'; ?>" 
                                 style="width: 70px; height: 50px; border-radius: 4px; object-fit: cover;">
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($item['nome']); ?></strong><br>
                            <small style="color: #666;"><?php echo mb_strimwidth(htmlspecialchars($item['descricao']), 0, 50, "..."); ?></small>
                        </td>
                        <td>R$ <?php echo number_format($item['valor'], 2, ',', '.'); ?></td>
                        <td class="text-center" style="display: flex; gap: 5px; justify-content: center;">
                            <button class="btn-action btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($item)); ?>)">✏️ Editar</button>
                            
                            <form method="post" onsubmit="return confirm('Excluir este serviço permanentemente?')">
                                <input type="hidden" name="acao" value="excluir">
                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn-action btn-delete" style="background:#e74c3c; color:white;">🗑️ Excluir</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- MODAL DE EDIÇÃO -->
    <div class="modal" id="editServiceModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Editar Serviço</h2>
                <button class="close-modal" onclick="closeEditModal()">✕</button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label>Nome do Serviço</label>
                    <input type="text" name="nome" id="edit_nome" required>
                </div>
                <div class="form-group">
                    <label>Descrição</label>
                    <textarea name="descricao" id="edit_desc" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Valor (R$)</label>
                    <input type="number" step="0.01" name="valor" id="edit_valor" required>
                </div>
                <div class="form-group">
                    <label>Trocar Imagem (Opcional)</label>
                    <input type="file" name="imagem" accept="image/*">
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancelar</button>
                    <button type="submit" class="btn-save">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <footer>© 2025 Aqui tem Terapia!</footer>
  </div>

  <script>
    function openEditModal(item) {
        document.getElementById('edit_id').value = item.id;
        document.getElementById('edit_nome').value = item.nome;
        document.getElementById('edit_desc').value = item.descricao;
        document.getElementById('edit_valor').value = item.valor;
        document.getElementById('editServiceModal').classList.add('active');
    }

    function closeEditModal() {
        document.getElementById('editServiceModal').classList.remove('active');
    }

    function toggleMenu() {
        const sidebar = document.querySelector('.sidebar');
        if(sidebar) sidebar.classList.toggle('active');
    }

    // Fechar modal no ESC ou clique fora
    window.onclick = (e) => { if (e.target.classList.contains('modal')) closeEditModal(); }
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeEditModal(); });
  </script>
</body>
</html>