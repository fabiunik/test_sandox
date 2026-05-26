<?php
session_start();
require_once __DIR__ . '/../controller/conectarBD.php';
require_once __DIR__ . '/../model/Suporte.php';
require_once __DIR__ . '/../model/Avaliacao.php';
require_once __DIR__ . '/../model/Agendamento.php';
require_once __DIR__ . '/../model/Pedido.php';

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['tipo'], ['administrador', 'terapeuta'])) {
    header("Location: login.php");
    exit;
}

$tipo = $_SESSION['tipo'];
$usuario_id = $_SESSION['usuario_id'];

// Filtros de Data
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01'); // Início do mês atual
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');

$pedidoModel = new Pedido($pdo);
$agendamentoModel = new Agendamento($pdo);

$financeiro = $pedidoModel->relatorioFinanceiro($data_inicio, $data_fim, ($tipo === 'terapeuta' ? $usuario_id : null));
$agendamentos = $agendamentoModel->listarPorPeriodo($data_inicio, $data_fim, ($tipo === 'terapeuta' ? $usuario_id : null));

// Lógica de Paginação Manual para Arrays
$perPage = 5;
$pageF = isset($_GET['pageF']) ? (int)$_GET['pageF'] : 1;
$pageA = isset($_GET['pageA']) ? (int)$_GET['pageA'] : 1;

$totalF = count($financeiro);
$totalA = count($agendamentos);

$financeiroPaginado = array_slice($financeiro, ($pageF - 1) * $perPage, $perPage);
$agendamentosPaginados = array_slice($agendamentos, ($pageA - 1) * $perPage, $perPage);
$totalPaginasF = ceil($totalF / $perPage);
$totalPaginasA = ceil($totalA / $perPage);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatórios — Aqui tem Terapia</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .report-grid { display: grid; gap: 20px; margin-top: 20px; }
        .report-card { background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #ddd; height: fit-content; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold; }
        .status-pendente, .status-pago { background: #fef3c7; color: #92400e; }
        .status-resolvido, .status-confirmado { background: #d1fae5; color: #065f46; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
        .filter-bar { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ddd; display: flex; gap: 15px; align-items: flex-end; }
        .rating-stars { color: #f59e0b; }
        .pagination-simple { margin-top: 15px; display: flex; gap: 10px; justify-content: center; }
        .pagination-simple a { text-decoration: none; padding: 5px 10px; border: 1px solid #ddd; border-radius: 4px; color: #333; font-size: 0.9rem; }
        .pagination-simple span { font-size: 0.9rem; align-self: center; }
    </style>
</head>
<body>
    <div class="site">
        <?php include 'header.php'; ?>

        <main>
            <section class="content">
                <h1>Painel de Relatórios</h1>
                <p class="lead">Bem-vindo, <?php echo $_SESSION['nome']; ?>. Abaixo estão as métricas do sistema.</p>

                <form class="filter-bar" method="GET">
                    <div class="input-group">
                        <label>Data Início</label>
                        <input type="date" name="data_inicio" value="<?php echo $data_inicio; ?>">
                    </div>
                    <div class="input-group">
                        <label>Data Fim</label>
                        <input type="date" name="data_fim" value="<?php echo $data_fim; ?>">
                    </div>
                    <button type="submit" class="btn-primary" style="height: 44px;">Filtrar Período</button>
                </form>

                <div class="report-grid">
                    <!-- Relatório Financeiro -->
                    <div class="report-card">
                        <h2>Resumo Financeiro</h2>
                        <p>Total no período: <strong>R$ <?php echo number_format(array_sum(array_column($financeiro, 'valor_total')), 2, ',', '.'); ?></strong></p>
                        <table>
                            <thead>
                                <tr>
                                    <th>Pedido</th>
                                    <th>Cliente</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($financeiroPaginado as $p): ?>
                                <tr>
                                    <td>#<?php echo $p['id']; ?></td>
                                    <td><?php echo htmlspecialchars($p['cliente_nome']); ?></td>
                                    <td>R$ <?php echo number_format($p['valor_total'], 2, ',', '.'); ?></td>
                                    <td><span class="status-badge status-<?php echo $p['status']; ?>"><?php echo ucfirst($p['status']); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if ($totalPaginasF > 1): ?>
                            <div class="pagination-simple">
                                <?php if ($pageF > 1): ?>
                                    <a href="?data_inicio=<?php echo $data_inicio; ?>&data_fim=<?php echo $data_fim; ?>&pageF=<?php echo $pageF - 1; ?>&pageA=<?php echo $pageA; ?>">« Ant</a>
                                <?php endif; ?>
                                <span>Pág <?php echo $pageF; ?> de <?php echo $totalPaginasF; ?></span>
                                <?php if ($pageF < $totalPaginasF): ?>
                                    <a href="?data_inicio=<?php echo $data_inicio; ?>&data_fim=<?php echo $data_fim; ?>&pageF=<?php echo $pageF + 1; ?>&pageA=<?php echo $pageA; ?>">Próx »</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Relatório de Agendamentos -->
                    <div class="report-card">
                        <h2>Agenda do Período</h2>
                        <table>
                            <thead>
                                <tr>
                                    <th>Data / Hora</th>
                                    <th>Cliente / Pedido</th>
                                    <th>Terapia</th>
                                    <?php if ($tipo === 'administrador'): ?><th>Terapeuta</th><?php endif; ?>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($agendamentosPaginados as $ag): ?>
                                <tr>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($ag['data'])); ?>
                                        <br><small style="color: #666;"><?php echo substr($ag['horario'], 0, 5); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($ag['usuario_nome']); ?></strong>
                                        <br><small style="color: #666;">Pedido: #<?php echo $ag['pedido_id'] ?: 'N/A'; ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($ag['item_nome']); ?></td>
                                    <?php if ($tipo === 'administrador'): ?>
                                        <td><?php echo htmlspecialchars($ag['terapeuta_nome']); ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <?php if ($ag['status'] === 'confirmado'): ?>
                                            <span class="status-badge status-confirmado">✓ Confirmado</span>
                                        <?php else: ?>
                                            <span style="color: #999; font-size: 0.8rem;">Aguardando</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if ($totalPaginasA > 1): ?>
                            <div class="pagination-simple">
                                <?php if ($pageA > 1): ?>
                                    <a href="?data_inicio=<?php echo $data_inicio; ?>&data_fim=<?php echo $data_fim; ?>&pageA=<?php echo $pageA - 1; ?>&pageF=<?php echo $pageF; ?>">« Ant</a>
                                <?php endif; ?>
                                <span>Pág <?php echo $pageA; ?> de <?php echo $totalPaginasA; ?></span>
                                <?php if ($pageA < $totalPaginasA): ?>
                                    <a href="?data_inicio=<?php echo $data_inicio; ?>&data_fim=<?php echo $data_fim; ?>&pageA=<?php echo $pageA + 1; ?>&pageF=<?php echo $pageF; ?>">Próx »</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($tipo === 'administrador'): 
                        $suporte = new Suporte($pdo);
                        $tickets = $suporte->listarTodos();
                    ?>
                        <!-- Visão do Administrador: Tickets de Suporte -->
                        <div class="report-card">
                            <h2>Chamados de Suporte</h2>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Usuário</th>
                                        <th>Assunto</th>
                                        <th>Status</th>
                                        <th>Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tickets as $t): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($t['usuario_nome']); ?></td>
                                        <td><?php echo htmlspecialchars($t['assunto']); ?></td>
                                        <td><span class="status-badge status-<?php echo $t['status']; ?>"><?php echo ucfirst($t['status']); ?></span></td>
                                        <td><?php echo date('d/m/Y', strtotime($t['data_criacao'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <?php if ($tipo === 'terapeuta' || $tipo === 'administrador'): 
                        $avalModel = new Avaliacao($pdo);
                        // Se for admin, poderíamos listar todas, aqui focamos no terapeuta logado
                        $avaliacoes = ($tipo === 'terapeuta') ? $avalModel->buscarPorTerapeuta($usuario_id) : [];
                    ?>
                        <?php if ($tipo === 'terapeuta'): ?>
                            <!-- Visão do Terapeuta: Avaliações -->
                            <div class="report-card">
                                <h2>Minhas Avaliações</h2>
                                <?php if (empty($avaliacoes)): ?>
                                    <p>Nenhuma avaliação recebida ainda.</p>
                                <?php else: ?>
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Cliente</th>
                                                <th>Nota</th>
                                                <th>Comentário</th>
                                                <th>Data</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($avaliacoes as $a): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($a['nome']); ?></td>
                                                <td class="rating-stars"><?php echo str_repeat('★', $a['nota']); ?></td>
                                                <td><?php echo htmlspecialchars($a['comentario']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($a['data_criacao'])); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
</body>
</html>