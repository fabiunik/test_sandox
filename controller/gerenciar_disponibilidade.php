<?php
session_start();
require_once '../controller/conectarBD.php';
require_once '../model/Disponibilidade.php';

// Verificação de Segurança
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.html");
    exit;
}

$usuarioLogadoId = $_SESSION['usuario_id'];
$usuarioLogadoTipo = $_SESSION['tipo'] ?? '';

if ($usuarioLogadoTipo !== 'terapeuta') {
    echo "<p>Acesso negado. Apenas terapeutas podem gerenciar disponibilidade.</p>";
    exit;
}

// Instanciar Model
$disponibilidadeModel = new Disponibilidade($pdo);

// Buscar configurações do terapeuta
$config = $disponibilidadeModel->obterConfiguracao($usuarioLogadoId);
$config = array_merge([
    'duracao_sessao' => 60,
    'dias_visibilidade' => 7,
    'intervalo_sessoes' => 0,
    'antecedencia_dias' => 0
], $config);

// Processamento de Formulários (POST)
$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // Salvar configurações
    if ($acao === 'salvar_config') {
        $duracao = intval($_POST['duracao_sessao'] ?? 60);
        $dias_vis = intval($_POST['dias_visibilidade'] ?? 7);
        $intervalo = intval($_POST['intervalo_sessoes'] ?? 0);
        $antecedencia = intval($_POST['antecedencia_dias'] ?? 0);

        if ($disponibilidadeModel->salvarConfiguracao($usuarioLogadoId, $duracao, $dias_vis, $intervalo, $antecedencia)) {
            $mensagem = 'Configurações atualizadas com sucesso!';
            // Recarregar configurações
            $config = $disponibilidadeModel->obterConfiguracao($usuarioLogadoId);
        }
    }

    // Adicionar slots de disponibilidade
    if ($acao === 'adicionar_slot') {
        $data = $_POST['data'] ?? '';
        $horario_inicio = $_POST['horario_inicio'] ?? '';
        $horario_fim = $_POST['horario_fim'] ?? '';
        $tipo = $_POST['tipo'] ?? 'presencial';

        if ($data && $horario_inicio && $horario_fim) {
            $duracao = $config['duracao_sessao'];
            $intervalo = $config['intervalo_sessoes'];

            $inicio = strtotime($horario_inicio);
            $fim = strtotime($horario_fim);
            $horario_atual = $inicio;
            $slots = 0;

            while ($horario_atual < $fim) {
                $horario_slot = date('H:i:s', $horario_atual);
                if ($disponibilidadeModel->adicionarSlot($usuarioLogadoId, $data, $horario_slot, $tipo)) {
                    $slots++;
                }
                $horario_atual += ($duracao + $intervalo) * 60;
            }

            $mensagem = "Foram adicionados $slots slots de disponibilidade!";
        }
    }

    // Remover slot
    if ($acao === 'remover_slot') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            if ($disponibilidadeModel->removerSlot($id, $usuarioLogadoId)) {
                $mensagem = 'Slot removido com sucesso!';
            }
        }
    }

    // Adicionar recorrência
    if ($acao === 'adicionar_recorrencia') {
        $dias_semana = array_map('intval', $_POST['dias_semana'] ?? []);
        $horario_inicio = $_POST['recorrencia_inicio'] ?? '';
        $horario_fim = $_POST['recorrencia_fim'] ?? '';
        $tipo = $_POST['recorrencia_tipo'] ?? 'presencial';
        $data_inicio = $_POST['recorrencia_data_inicio'] ?? '';
        $data_fim = $_POST['recorrencia_data_fim'] ?? '';

        if (!empty($dias_semana) && $horario_inicio && $horario_fim && $data_inicio && $data_fim) {
            $slots = $disponibilidadeModel->adicionarSlotsRecorrencia(
                $usuarioLogadoId,
                $dias_semana,
                $data_inicio,
                $data_fim,
                $horario_inicio,
                $horario_fim,
                $tipo
            );
            $mensagem = "Foram adicionados $slots slots de recorrência!";
        }
    }

    if ($mensagem !== '') {
        $_SESSION['mensagem'] = $mensagem;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Buscar disponibilidades
$pagina = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$itens_por_pagina = 8;
$offset = ($pagina - 1) * $itens_por_pagina;

$total_disponibilidades = $disponibilidadeModel->contarSlotsDisponíveis($usuarioLogadoId);
$total_paginas = ceil($total_disponibilidades / $itens_por_pagina);
$disponibilidades = $disponibilidadeModel->listarPorTerapeuta($usuarioLogadoId, $itens_por_pagina, $offset);

// Mensagens de sessão
$mensagem = $_SESSION['mensagem'] ?? '';
unset($_SESSION['mensagem']);
