<?php
require_once __DIR__ . '/conectarBD.php';

$termo = trim($_GET['termo'] ?? '');

if (empty($termo)) {
    header("Location: ../view/tela_inicial.php");
    exit;
}

// 1. Tenta buscar em Serviços (Itens)
$stmtItem = $pdo->prepare("SELECT id FROM itens WHERE nome LIKE ? OR descricao LIKE ? LIMIT 1");
$stmtItem->execute(["%$termo%", "%$termo%"]);
if ($stmtItem->fetch()) {
    header("Location: ../view/itens.php?busca=" . urlencode($termo));
    exit;
}

// 2. Tenta buscar em Profissionais (Terapeutas)
$stmtProf = $pdo->prepare("SELECT id FROM usuario WHERE tipo = 'terapeuta' AND nome LIKE ? LIMIT 1");
$stmtProf->execute(["%$termo%"]);
if ($stmtProf->fetch()) {
    header("Location: ../view/profissionais.php?busca=" . urlencode($termo));
    exit;
}

// 3. Se não encontrar nada específico, manda para serviços com aviso
header("Location: ../view/itens.php?busca=" . urlencode($termo) . "&msg=nao_encontrado");
exit;