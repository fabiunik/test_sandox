<?php
session_start();
require_once __DIR__ . '/../controller/conectarBD.php';
require_once __DIR__ . '/../model/Pedido.php';
require_once __DIR__ . '/../model/Agendamento.php';
require_once __DIR__ . '/../model/Item.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$pedidoModel = new Pedido($pdo);
$agendamentoModel = new Agendamento($pdo);
$itemModel = new Item($pdo);

$agendamento_id = intval($_GET['agendamento_id'] ?? 0);
$pedido_id = intval($_GET['pedido_id'] ?? 0);
$pedidosParaExibir = [];
$mensagem_erro = null;

try {
    if ($agendamento_id > 0) {
        $agendamento = $agendamentoModel->buscarPorId($agendamento_id);
        if (!$agendamento || $agendamento['usuario_id'] != $_SESSION['usuario_id']) {
            $mensagem_erro = "Agendamento não encontrado ou acesso negado.";
        } else {
            if (empty($agendamento['pedido_id'])) {
                $item = $itemModel->buscarPorId($agendamento['itens_id']);
                $valor = $item['valor'] ?? 0;
                $pedido_id = $pedidoModel->criar($_SESSION['usuario_id'], $valor);
                $agendamentoModel->vincularPedido($agendamento_id, $pedido_id);
            } else {
                $pedido_id = $agendamento['pedido_id'];
            }
        }
    }

    if ($pedido_id > 0) {
        $p = $pedidoModel->obterPorId($pedido_id);
        if ($p && $p['usuario_id'] == $_SESSION['usuario_id']) {
            $pedidosParaExibir[] = $p;
        }
    } else {
        // Fallback: Se não veio ID, listamos todos os pedidos do usuário logado para conferência
        $pedidosParaExibir = $pedidoModel->listarPorUsuario($_SESSION['usuario_id']);
    }

} catch (Exception $e) {
    $mensagem_erro = "Erro ao processar pedidos: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Conferir Pedido — Aqui tem Terapia</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="site">
        <?php include 'header.php'; ?>

        <main>
            <section class="content" style="max-width: 800px; margin: 0 auto; width: 100%;">
                <h1 class="page-title">Meus Pedidos</h1>
                <p class="lead">Acompanhe seus pedidos e histórico de pagamentos.</p>

                <?php if ($mensagem_erro): ?>
                    <div class="alert alert-error">✗ <?php echo htmlspecialchars($mensagem_erro); ?></div>
                <?php endif; ?>

                <?php if (empty($pedidosParaExibir)): ?>
                    <div class="card" style="text-align: center; padding: 40px; background: #fff; border-radius: 12px; border: 1px solid #ddd;">
                        <p>Você ainda não possui pedidos registrados.</p>
                        <a href="agendamento.php" class="btn-primary" style="text-decoration: none; display: inline-block; margin-top: 15px;">Fazer um agendamento</a>
                    </div>
                <?php else: ?>
                    <div class="orders-container">
                        <?php foreach ($pedidosParaExibir as $infoPedido): 
                            $detalhes = $pedidoModel->obterDetalhesPedido($infoPedido['id']);
                        ?>
                            <div class="pedido-bloco" style="background: #fff; padding: 20px; border-radius: 12px; margin-bottom: 40px; border: 1px solid #ddd;">
                                <h2 style="font-size: 1.2rem; margin-bottom: 15px;">Pedido #<?php echo $infoPedido['id']; ?></h2>
                                
                                <?php foreach ($detalhes as $item): ?>
                                    <div class="order-card" style="border: 1px solid #eee; margin-bottom: 15px;">
                                        <div class="order-header">
                                            <h3><?php echo htmlspecialchars($item['servico_nome']); ?></h3>
                                            <?php if ($infoPedido['status'] === 'pago'): ?>
                                                <span class="order-status confirmed">Pago</span>
                                            <?php else: ?>
                                                <span class="order-status pending">Aguardando Pagamento</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="order-info">
                                            <p><strong>Profissional:</strong> <?php echo htmlspecialchars($item['terapeuta_nome']); ?></p>
                                            <p><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($item['data'])); ?></p>
                                            <p><strong>Horário:</strong> <?php echo substr($item['horario'], 0, 5); ?></p>
                                            <p><strong>Valor:</strong> R$ <?php echo number_format($item['servico_valor'], 2, ',', '.'); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <div class="resumo-total" style="background: #f8fafc; padding: 20px; border-radius: 8px; text-align: right;">
                                    <h2 style="font-size: 1.5rem; color: var(--text-gray);">
                                        Total deste Pedido: 
                                        <span style="color: var(--cta-bg);">
                                            R$ <?php echo number_format($infoPedido['valor_total'], 2, ',', '.'); ?>
                                        </span>
                                    </h2>
                                </div>

                                <?php if ($infoPedido['status'] === 'pendente'): ?>
                                    <div class="order-actions" style="margin-top: 30px; display: flex; flex-direction: column; gap: 10px;">
                                        <form action="pagamento.php" method="GET">
                                            <input type="hidden" name="pedido_id" value="<?php echo $infoPedido['id']; ?>">
                                            <button type="submit" class="btn-primary" style="padding: 12px 40px; font-size: 1.1rem; width: 100%;">
                                                Confirmar e Ir para Pagamento
                                            </button>
                                        </form>
                                        
                                        <form action="../controller/gerenciar_pedidos.php" method="POST" onsubmit="return confirm('Tem certeza que deseja cancelar este pedido?')">
                                            <input type="hidden" name="acao" value="cancelar_pedido">
                                            <input type="hidden" name="pedido_id" value="<?php echo $infoPedido['id']; ?>">
                                            <button type="submit" class="btn-secondary" style="width: 100%; color: #dc3545; border-color: #dc3545;">
                                                Cancelar Pedido
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div style="text-align: center; margin-top: 20px;">
                    <a href="agendamento.php" class="btn-secondary" style="text-decoration: none; padding: 12px 24px;">Adicionar novo serviço</a>
                </div>
            </section>
        </main>
    </div>
</body>
</html>