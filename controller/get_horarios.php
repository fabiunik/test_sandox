<?php
require_once __DIR__ . '/conectarBD.php';
require_once __DIR__ . '/../model/Agendamento.php';
require_once __DIR__ . '/../model/Item.php';
require_once __DIR__ . '/../model/Disponibilidade.php';

header('Content-Type: application/json; charset=utf-8');

$agendamentoModel = new Agendamento($pdo);
$itemModel = new Item($pdo);
$disponibilidadeModel = new Disponibilidade($pdo);

$terapeuta_id = intval($_GET['terapeuta_id'] ?? 0);
$item_id = intval($_GET['item_id'] ?? 0);
$duracao = intval($_GET['duracao'] ?? 0);

if ($terapeuta_id && $item_id) {
    $item = $itemModel->buscarPorId($item_id);
    $duracao = $duracao > 0 ? $duracao : intval($item['duracao'] ?? 0);
    if ($duracao <= 0) {
        $duracao = 60;
    }

    // Busca a configuração de visibilidade definida pelo terapeuta (ou padrão 30 dias)
    $config = $disponibilidadeModel->obterConfiguracao($terapeuta_id);

    // Sugestão: Aumentar o fallback para 30 ou 60 para garantir que datas futuras apareçam 
    // se o terapeuta não definiu um limite restrito.
    $diasVisibilidade = (isset($config['dias_visibilidade']) && (int)$config['dias_visibilidade'] > 0) 
                        ? intval($config['dias_visibilidade']) : 30;

    $horarios = $agendamentoModel->listarHorariosDisponiveisPorPeriodo($terapeuta_id, $duracao, $diasVisibilidade);
    echo json_encode($horarios);
} else {
    echo json_encode([]);
}
?>