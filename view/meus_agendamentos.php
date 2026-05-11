<?php
session_start();
require_once __DIR__ . '/../controller/conectarBD.php';
require_once __DIR__ . '/../model/Agendamento.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$agendamentoModel = new Agendamento($pdo);
$meusAgendamentos = $agendamentoModel->listarPorUsuario($_SESSION['usuario_id']);

$mensagem_sucesso = $_SESSION['success'] ?? null;
$mensagem_erro = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Meus Agendamentos — Aqui tem Terapia</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="site">
    <?php include 'header.php'; ?>

    <main>
      <section class="orders-container" style="max-width: 900px; margin: 0 auto; width: 100%;">
        <h1 class="page-title">Meus Agendamentos</h1>
        <p class="lead">Acompanhe seus atendimentos marcados e histórico de consultas.</p>

        <?php if ($mensagem_sucesso): ?>
            <div class="alert alert-success">✓ <?php echo htmlspecialchars($mensagem_sucesso); ?></div>
        <?php endif; ?>
        <?php if ($mensagem_erro): ?>
            <div class="alert alert-error">✗ <?php echo htmlspecialchars($mensagem_erro); ?></div>
        <?php endif; ?>

        <?php if (empty($meusAgendamentos)): ?>
            <div class="card" style="text-align: center; padding: 40px;">
                <p>Você ainda não possui agendamentos.</p>
                <a href="agendamento.php" class="btn-primary" style="text-decoration: none; display: inline-block; margin-top: 15px;">Agendar agora</a>
            </div>
        <?php else: ?>
            <?php foreach ($meusAgendamentos as $ag): 
                $dataPassada = strtotime($ag['data']) < strtotime(date('Y-m-d'));
            ?>
                <article class="order-card" style="border: 1px solid #ddd; margin-bottom: 20px; background: #fff; padding: 20px; border-radius: 12px;">
                  <div class="order-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px;">
                    <h3 style="margin:0;"><?php echo htmlspecialchars($ag['item_nome']); ?></h3>
                    <span class="status-badge status-<?php echo $ag['status']; ?>" style="padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: bold;">
                        <?php echo ucfirst($ag['status']); ?>
                    </span>
                  </div>
                  
                  <div class="order-info" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <p><strong>👨‍⚕️ Profissional:</strong> <?php echo htmlspecialchars($ag['terapeuta_nome']); ?></p>
                    <p><strong>📅 Data:</strong> <?php echo date('d/m/Y', strtotime($ag['data'])); ?></p>
                    <p><strong>🕒 Horário:</strong> <?php echo substr($ag['horario'], 0, 5); ?></p>
                    <p><strong>💰 Valor:</strong> R$ <?php echo number_format($ag['preco'], 2, ',', '.'); ?></p>
                  </div>

                  <div class="order-actions" style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end; border-top: 1px solid #eee; pt: 15px;">
                    <?php if ($ag['status'] === 'pendente'): ?>
                        <a href="pedidos.php?pedido_id=<?php echo $ag['pedido_id']; ?>" class="btn-primary" style="text-decoration: none; font-size: 0.9rem;">Pagar Agora</a>
                        <form action="../controller/gerenciar_agendamentos.php" method="POST" onsubmit="return confirm('Deseja realmente cancelar este agendamento?')">
                            <input type="hidden" name="acao" value="cancelar">
                            <input type="hidden" name="id" value="<?php echo $ag['id']; ?>">
                            <button type="submit" class="btn-secondary" style="color: #dc3545; border-color: #dc3545;">Cancelar</button>
                        </form>
                    <?php elseif ($ag['status'] === 'confirmado'): ?>
                        <a href="agendamento.php?item_id=<?php echo $ag['itens_id']; ?>&terapeuta_id=<?php echo $ag['terapeuta_id']; ?>" class="btn-secondary" style="text-decoration: none; font-size: 0.9rem;">Reagendar</a>
                        <?php if (!$dataPassada): ?>
                            <form action="../controller/gerenciar_agendamentos.php" method="POST" onsubmit="return confirm('Atenção: O cancelamento de sessões pagas pode estar sujeito a taxas. Confirmar cancelamento?')">
                                <input type="hidden" name="acao" value="cancelar">
                                <input type="hidden" name="id" value="<?php echo $ag['id']; ?>">
                                <button type="submit" class="btn-secondary" style="color: #dc3545; border-color: #dc3545;">Cancelar</button>
                            </form>
                        <?php else: ?>
                            <a href="avaliacao.php?agendamento_id=<?php echo $ag['id']; ?>" class="btn-primary" style="text-decoration: none; font-size: 0.9rem; background: #28a745;">⭐ Avaliar Atendimento</a>
                        <?php endif; ?>
                    <?php elseif ($ag['status'] === 'cancelado'): ?>
                        <a href="agendamento.php?item_id=<?php echo $ag['itens_id']; ?>&terapeuta_id=<?php echo $ag['terapeuta_id']; ?>" class="btn-primary" style="text-decoration: none; font-size: 0.9rem;">Agendar Novamente</a>
                    <?php endif; ?>
                  </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 30px;">
            <a href="pedidos.php" style="color: var(--cta-bg); text-decoration: none; font-weight: bold;">→ Ver Histórico de Pagamentos</a>
        </div>
      </section>
    </main>

    <footer>© 2025 Aqui tem Terapia! | Contato via WhatsApp</footer>
  </div>
</body>
</html>