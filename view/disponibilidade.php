<?php require_once __DIR__ . '/../controller/gerenciar_disponibilidade.php'; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Disponibilidade</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="site">
  <header>
    <div class="logo">Aqui tem Terapia!</div>
    <nav>
      <a class="cta" href="tela_inicial.html">Home</a>
      <a class="cta" href="perfil_profissional.php">Perfil</a>
      <a class="cta" href="gerenciar_itens.php">Serviços</a>
    </nav>
  </header>

  <div class="cards">
    <!-- CONFIGURAÇÕES GERAIS -->
    <div>
      <h2>Configurações de Disponibilidade</h2>

      <?php if ($mensagem): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($mensagem); ?></div>
      <?php endif; ?>

      <form method="post" class="gerenciar-perfil">
        <input type="hidden" name="acao" value="salvar_config">

        <div class="grade-config">
          <div class="form-group">
            <label>Duração da Sessão (minutos)</label>
            <input type="number" name="duracao_sessao" value="<?php echo $config['duracao_sessao']; ?>" min="15" step="15" required>
          </div>

          <div class="form-group">
            <label>Período de Visualização (dias)</label>
            <input type="number" name="dias_visibilidade" value="<?php echo $config['dias_visibilidade']; ?>" min="1" max="45" required>
            <small>Clientes verão sua agenda até quantos dias à frente?</small>
          </div>

          <div class="form-group">
            <label>Intervalo entre Sessões (minutos)</label>
            <input type="number" name="intervalo_sessoes" value="<?php echo $config['intervalo_sessoes']; ?>" min="0" step="5">
            <small>Tempo de descanso/limpeza entre sessões</small>
          </div>

          <div class="form-group">
            <label>Antecedência Mínima (dias)</label>
            <input type="number" name="antecedencia_dias" value="<?php echo $config['antecedencia_dias']; ?>" min="0">
            <small>Cliente não pode agendar com antecedência menor</small>
          </div>
        </div>

        <button type="submit" class="btn-primary">Salvar Configurações</button>
      </form>
    </div>

    <!-- ADICIONAR SLOTS PONTUAIS -->
    <div class="cards">
      <h2>Adicionar Disponibilidade Pontual</h2>
      
      <form method="post">
        <input type="hidden" name="acao" value="adicionar_slot">

        <div class="form-group">
          <label>Data</label>
          <input type="date" name="data" required>
        </div>

        <div class="grade-config">
          <div class="form-group">
            <label>Horário de Início</label>
            <input type="time" name="horario_inicio" required>
          </div>

          <div class="form-group">
            <label>Horário de Fim</label>
            <input type="time" name="horario_fim" required>
          </div>

          <div class="form-group">
            <label>Tipo</label>
            <select name="tipo">
              <option value="presencial">Presencial</option>
              <option value="online">Online</option>
            </select>
          </div>
        </div>

        <small style="display: block; margin-bottom: 15px; color: #666;">
          ℹ️ Os slots serão gerados automaticamente com base na duração configurada acima
        </small>

        <button type="submit" class="btn-primary">Adicionar Slots</button>
      </form>
    </div>

    <!-- ADICIONAR RECORRÊNCIA (DIAS DA SEMANA) -->
    <div class="cards">
      <h2>Adicionar Disponibilidade Recorrente</h2>
      
      <form method="post">
        <input type="hidden" name="acao" value="adicionar_recorrencia">

        <div class="form-group">
          <label>Selecione os Dias da Semana</label>
          <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin: 10px 0;">
            <?php
            $dias = [1 => 'Segunda', 2 => 'Terça', 3 => 'Quarta', 4 => 'Quinta', 5 => 'Sexta', 6 => 'Sábado', 7 => 'Domingo'];
            foreach ($dias as $num => $nome):
            ?>
              <label style="display: flex; align-items: center; gap: 8px; margin: 0;">
                <input type="checkbox" name="dias_semana[]" value="<?php echo $num; ?>">
                <span><?php echo $nome; ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="grade-config">
          <div class="form-group">
            <label>Horário de Início</label>
            <input type="time" name="recorrencia_inicio" required>
          </div>

          <div class="form-group">
            <label>Horário de Fim</label>
            <input type="time" name="recorrencia_fim" required>
          </div>
        </div>

        <div class="grade-config">
          <div class="form-group">
            <label>Data de Início da Recorrência</label>
            <input type="date" name="recorrencia_data_inicio" required>
          </div>

          <div class="form-group">
            <label>Data de Fim da Recorrência</label>
            <input type="date" name="recorrencia_data_fim" required>
          </div>

          <div class="form-group">
            <label>Tipo</label>
            <select name="recorrencia_tipo">
              <option value="presencial">Presencial</option>
              <option value="online">Online</option>
            </select>
          </div>
        </div>

        <small style="display: block; margin-bottom: 15px; color: #666;">
          ℹ️ Será criado um slot para cada intervalo de duração nos dias selecionados
        </small>

        <button type="submit" class="btn-primary">Adicionar Recorrência</button>
      </form>
    </div>

    <!-- LISTA DE DISPONIBILIDADES -->
    <div class="cards">
      <h2>Disponibilidades Registradas</h2>
      
      <?php if (count($disponibilidades) > 0): ?>
        <table>
          <thead>
            <tr>
              <th>Data</th>
              <th>Horário</th>
              <th>Tipo</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($disponibilidades as $disp): ?>
              <tr>
                <td><?php echo date('d/m/Y', strtotime($disp['data'])); ?></td>
                <td><?php echo substr($disp['horario'], 0, 5); ?></td>
                <td>
                  <span style="background: <?php echo $disp['tipo'] === 'online' ? '#e3f2fd' : '#f3e5f5'; ?>; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;">
                    <?php echo ucfirst($disp['tipo']); ?>
                  </span>
                </td>
                <td>
                  <form method="post" style="display: inline;">
                    <input type="hidden" name="acao" value="remover_slot">
                    <input type="hidden" name="id" value="<?php echo $disp['id']; ?>">
                    <button type="submit" class="btn-delete" onclick="return confirm('Remover?')">Remover</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p style="text-align: center; color: #666; padding: 20px;">Nenhuma disponibilidade registrada no momento.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function toggleMenu() {
  // menu logic
}
</script>
</body>
</html>
