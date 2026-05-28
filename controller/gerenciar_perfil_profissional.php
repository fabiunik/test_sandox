<?php
session_start();
require_once __DIR__ . '/conectarBD.php';

$usuarioLogadoId = $_SESSION['usuario_id'] ?? 0;
$usuarioLogadoTipo = $_SESSION['tipo'] ?? '';

$perfil = null;
$mensagemPerfil = null;
$erroPerfil = null;
$modoEdicao = false;
$id = 0;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($usuarioLogadoTipo === 'administrador') {
        $modoEdicao = true;
    } elseif ($usuarioLogadoTipo === 'terapeuta' && $usuarioLogadoId === $id) {
        $modoEdicao = true;
    }
} elseif ($usuarioLogadoTipo === 'terapeuta' && $usuarioLogadoId > 0) {
    $modoEdicao = true;
    $id = $usuarioLogadoId;
}

if ($id > 0) {
    $sql = "SELECT u.id, u.nome, p.descricao, p.especialidades, p.experiencia, p.foto
            FROM usuario u
            LEFT JOIN perfil_terapeuta p ON p.usuario_id = u.id
            WHERE u.id = ? AND u.tipo = 'terapeuta'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $perfil = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar_perfil') {
    if (!$modoEdicao) {
        $erroPerfil = "Você não pode editar este perfil.";
    } elseif (!$perfil) {
        $erroPerfil = "Perfil não encontrado.";
    } else {
        $descricao = $_POST['descricao'] ?? '';
        $especialidades = $_POST['especialidades'] ?? '';
        $experiencia = $_POST['experiencia'] ?? '';
        $fotoPath = $perfil['foto'] ?? null;

        if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = dirname(__DIR__) . '/uploads';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $fileType = mime_content_type($_FILES['foto']['tmp_name']);

            if (!in_array($fileType, $allowedTypes, true)) {
                $erroPerfil = "Formato de imagem inválido. Use JPG, PNG, WEBP ou GIF.";
            } else {
                $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                $fileName = time() . "_" . $id . "." . $ext;
                $targetFile = $uploadDir . '/' . $fileName;

                if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetFile)) {
                    $fotoPath = 'uploads/' . $fileName;
                    chmod($targetFile, 0644);
                } else {
                    $erroPerfil = "Falha ao enviar a imagem. Tente novamente.";
                }
            }
        }

        if (!$erroPerfil) {
            $stmtExiste = $pdo->prepare("SELECT 1 FROM perfil_terapeuta WHERE usuario_id = ?");
            $stmtExiste->execute([$id]);
            $existePerfil = (bool) $stmtExiste->fetchColumn();

            if ($existePerfil) {
                $sqlUp = "UPDATE perfil_terapeuta 
                          SET descricao = ?, especialidades = ?, experiencia = ?, foto = ?
                          WHERE usuario_id = ?";
                $stmtUp = $pdo->prepare($sqlUp);
                if ($stmtUp->execute([$descricao, $especialidades, $experiencia, $fotoPath, $id])) {
                    $mensagemPerfil = "Perfil atualizado com sucesso!";
                } else {
                    $erroPerfil = "Erro ao atualizar perfil.";
                }
            } else {
                $sqlIns = "INSERT INTO perfil_terapeuta (usuario_id, descricao, especialidades, experiencia, foto)
                           VALUES (?, ?, ?, ?, ?)";
                $stmtIns = $pdo->prepare($sqlIns);
                if ($stmtIns->execute([$id, $descricao, $especialidades, $experiencia, $fotoPath])) {
                    $mensagemPerfil = "Perfil criado com sucesso!";
                } else {
                    $erroPerfil = "Erro ao criar perfil.";
                }
            }

            if (!$erroPerfil) {
                $stmt->execute([$id]);
                $perfil = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }
    }
}

$fotoUrl = 'https://placehold.co/240x240';
if ($perfil && !empty($perfil['foto'])) {
    $storedFoto = $perfil['foto'];
    if (strpos($storedFoto, 'uploads/') === 0) {
        $fotoUrl = '../' . $storedFoto;
    } elseif (strpos($storedFoto, '../uploads/') === 0) {
        $fotoUrl = $storedFoto;
    } else {
        $fotoUrl = '../uploads/' . $storedFoto;
    }
} else {
    // Fallback: procura diretamente na pasta uploads
    $uploadDir = dirname(__DIR__) . '/uploads';
    $arquivos = @scandir($uploadDir);
    if ($arquivos) {
        foreach ($arquivos as $arquivo) {
            if (preg_match('/_' . $id . '\.(jpg|jpeg|png|gif|webp)$/', $arquivo)) {
                $fotoUrl = '../uploads/' . $arquivo;
                break;
            }
        }
    }
}
