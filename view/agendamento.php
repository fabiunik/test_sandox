<?php
session_start();
require_once __DIR__ . '/../controller/conectarBD.php';
require_once __DIR__ . '/../model/Usuario.php';
require_once __DIR__ . '/../model/Item.php';
require_once __DIR__ . '/../model/Agendamento.php';

$usuarioModel = new Usuario($pdo);
$itemModel = new Item($pdo);
$agendamentoModel = new Agendamento($pdo);

$terapeutas = $usuarioModel->listarTerapeutas();
$itens = $itemModel->listar();

$mensagem = null;
$erro = null;

// Recuperar dados pendentes da sessão se o usuário acabou de logar
$pending = $_SESSION['pending_agendamento'] ?? null;

// Obter valores pré-selecionados da URL, se existirem
$pre_selected_item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : ($pending ? intval($pending['item_id']) : 0);
$pre_selected_terapeuta_id = isset($_GET['terapeuta_id']) ? intval($_GET['terapeuta_id']) : ($pending ? intval($pending['terapeuta_id']) : 0);
$pre_selected_data = $pending['data'] ?? '';
$pre_selected_horario = $pending['horario'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    if ($usuario_id == 0) {
        $_SESSION['pending_agendamento'] = $_POST;
        header("Location: login.php");
        exit;
    } else {
        $terapeuta_id = $_POST['terapeuta_id'] ?? 0;
        $item_id = $_POST['item_id'] ?? 0;
        $data = $_POST['data'] ?? '';
        $horario = $_POST['horario'] ?? '';
        $duracao = 0;
        $preco = 0;

        if ($item_id > 0) {
            $item = $itemModel->buscarPorId($item_id);
            $duracao = intval($item['duracao'] ?? 0);
            $preco = floatval($item['valor'] ?? 0);
        }

        if ($duracao <= 0) {
            $duracao = 60;
        }

        try {
            // No futuro, se quiser 'carrinho', você verificaria se já existe um pedido aberto na sessão
            // Por enquanto, criamos o agendamento e o pedidos.php criará o Pedido 'guarda-chuva'
            $id = $agendamentoModel->criar($usuario_id, $terapeuta_id, $item_id, $data, $horario, $duracao, $preco);
            unset($_SESSION['pending_agendamento']); // Limpa o agendamento pendente após sucesso
            header("Location: pedidos.php?agendamento_id=" . $id);
            exit;
        } catch (Exception $e) {
            $erro = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Agendamento — Aqui tem Terapia</title>
  <link rel="stylesheet" href="style.css">
  <script>
    function formatDateLabel(dateString) {
      // Parse a string "YYYY-MM-DD" sem interpretar como UTC
      const [year, month, day] = dateString.split('-').map(Number);
      const dt = new Date(year, month - 1, day); // Cria data local
      return dt.toLocaleDateString('pt-BR', { weekday: 'long', day: '2-digit', month: '2-digit' });
    }

    function atualizarPainelHorarios(horarios) {
      const panel = document.getElementById('horarios-panel');
      const status = document.getElementById('horariosStatus');
      const list = document.getElementById('horariosList');
      const selectedDateInput = document.getElementById('data');
      const selectedTimeInput = document.getElementById('horario');
      const selectedDateText = document.getElementById('selectedDate');
      const selectedTimeText = document.getElementById('selectedTime');
      
      const targetData = '<?php echo $pre_selected_data; ?>';
      const targetHorario = '<?php echo $pre_selected_horario; ?>';

      list.innerHTML = '';
      selectedDateInput.value = '';
      selectedTimeInput.value = '';
      selectedDateText.textContent = 'Nenhuma data selecionada';
      selectedTimeText.textContent = 'Nenhum horário selecionado';

      if (!horarios || horarios.length === 0) {
        status.textContent = 'Nenhum horário disponível para este profissional nos próximos dias.';
        panel.classList.remove('hidden');
        return;
      }

      const grouped = horarios.reduce((acc, slot) => {
        acc[slot.data] = acc[slot.data] || [];
        acc[slot.data].push(slot.horario);
        return acc;
      }, {});

      status.textContent = 'Clique em um horário para definir data e hora do agendamento.';

      Object.keys(grouped).forEach(data => {
        const groupBlock = document.createElement('div');
        groupBlock.className = 'slot-day';

        const dateTitle = document.createElement('h3');
        dateTitle.textContent = formatDateLabel(data);
        groupBlock.appendChild(dateTitle);

        const groupGrid = document.createElement('div');
        groupGrid.className = 'slot-grid';

        grouped[data].forEach(horario => {
          const button = document.createElement('button');
          button.type = 'button';
          button.className = 'slot-btn disponivel';
          button.textContent = horario;
          button.addEventListener('click', () => {
            selectedDateInput.value = data;
            selectedTimeInput.value = horario;
            selectedDateText.textContent = formatDateLabel(data);
            selectedTimeText.textContent = horario;
            document.querySelectorAll('.slot-btn').forEach(el => el.classList.remove('selected'));
            button.classList.add('selected');
          });

          // Auto-selecionar se vier da sessão
          if (data === targetData && horario === targetHorario) {
              button.classList.add('selected');
              selectedDateInput.value = data;
              selectedTimeInput.value = horario;
              selectedDateText.textContent = formatDateLabel(data);
              selectedTimeText.textContent = horario;
          }
          groupGrid.appendChild(button);
        });

        groupBlock.appendChild(groupGrid);
        list.appendChild(groupBlock);
      });

      panel.classList.remove('hidden');
    }

    function carregarHorarios() {
      const terapeutaId = document.getElementById('terapeuta_id').value;
      const itemId = document.getElementById('item_id').value;
      const panel = document.getElementById('horarios-panel');

      if (!terapeutaId || !itemId) {
        panel.classList.add('hidden');
        return;
      }

      fetch(`../controller/get_horarios.php?terapeuta_id=${terapeutaId}&item_id=${itemId}`)
        .then(response => response.json())
        .then(horarios => atualizarPainelHorarios(horarios))
        .catch(() => {
          const status = document.getElementById('horariosStatus');
          status.textContent = 'Erro ao carregar horários. Tente novamente.';
          panel.classList.remove('hidden');
        });
    }

    // Chama carregarHorarios() ao carregar a página se os seletores já estiverem preenchidos
    document.addEventListener('DOMContentLoaded', function() {
      const pre_selected_item_id = <?php echo $pre_selected_item_id; ?>;
      const pre_selected_terapeuta_id = <?php echo $pre_selected_terapeuta_id; ?>;
      if (pre_selected_item_id > 0 && pre_selected_terapeuta_id > 0) {
        carregarHorarios();
      }
    });
  </script>
</head>
<body>
  <div class="site">
    <?php include 'header.php'; ?>

    <main>
      <section class="content agenda-layout" aria-labelledby="agendamento-title">
        <div class="agendamento-form card">
          <form class="form" action="" method="post" aria-label="Formulário de agendamento">
            <div class="input-group">
          <?php if ($mensagem): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($mensagem); ?></div>
          <?php endif; ?>
          <?php if ($erro): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($erro); ?></div>
          <?php endif; ?>
          <h1 id="agendamento-title">Agendamento</h1>
          <p class="lead">Escolha o serviço e o profissional para ativar os horários disponíveis.</p>

            <label for="item_id">Qual é o serviço desejado?</label>
              <select id="item_id" name="item_id" required onchange="carregarHorarios()">
                <option value="">-- escolha --</option>
                <?php foreach ($itens as $item): ?>
                  <option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['nome']); ?> - R$ <?php echo number_format($item['valor'], 2, ',', '.'); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="input-group">
              <label for="terapeuta_id">Quem é o profissional?</label>
              <select id="terapeuta_id" name="terapeuta_id" required onchange="carregarHorarios()">
                <option value="">-- escolha --</option>
                <?php foreach ($terapeutas as $terapeuta): ?>
                  <option value="<?php echo $terapeuta['id']; ?>"><?php echo htmlspecialchars($terapeuta['nome']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <input type="hidden" id="data" name="data" value="">
            <input type="hidden" id="horario" name="horario" value="">

            <div class="input-group">
              <label>Data selecionada</label>
              <div id="selectedDate" class="selected-time">Nenhuma data selecionada</div>
            </div>
            <div class="input-group">
              <label>Horário selecionado</label>
              <div id="selectedTime" class="selected-time">Nenhum horário selecionado</div>
            </div>

            <button type="submit" class="btn-primary">Agendar</button>
          </form>
        </div>

        <aside class="tabela-horarios card" id="horarios-panel" aria-live="polite">
          <div class="resumo-agendamento">
            <h2>Horários disponíveis</h2>
            <p id="horariosStatus">Selecione serviço e profissional para ver os horários automaticamente.</p>
            <!-- Removida a classe slot-grid para que os dias fiquem em lista vertical -->
            <div id="horariosList"></div>
          </div>
        </aside>
      </section>
    </main>
  </div>
</body>
</html>