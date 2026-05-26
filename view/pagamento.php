<?php
session_start();
require_once __DIR__ . '/../controller/conectarBD.php';
require_once __DIR__ . '/../model/Pedido.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$pedido_id = intval($_GET['pedido_id'] ?? 0);

// Verifica se o pedido existe e pertence ao usuário logado
$pedidoModel = new Pedido($pdo);
$infoPedido = $pedido_id > 0 ? $pedidoModel->obterPorId($pedido_id) : null;
$pedidoValido = ($infoPedido && $infoPedido['usuario_id'] == $_SESSION['usuario_id']);
$detalhes = $pedidoValido ? $pedidoModel->obterDetalhesPedido($pedido_id) : [];

$preference_id = null;
$result = [];

// --- INTEGRAÇÃO MERCADO PAGO ---
if ($pedidoValido) {
    $access_token = trim(getenv('MP_ACCESS_TOKEN') ?: '');
    $public_key = trim(getenv('MP_PUBLIC_KEY') ?: '');

if (!$access_token || !$public_key) {
    die("Erro: Chave de acesso (Access Token) do Mercado Pago não configurada nas variáveis de ambiente.");
}

// Validação de consistência (Ambas devem ser TEST- ou ambas APP_USR-)
$isTestToken = strpos($access_token, 'TEST-') === 0;
$isTestKey = strpos($public_key, 'TEST-') === 0;

if ($isTestToken !== $isTestKey) {
    die("Erro de Configuração: Você está misturando credenciais de Teste e Produção. Verifique seu arquivo .env");
}

$url = "https://api.mercadopago.com/checkout/preferences";

// Preparar os itens para o Mercado Pago
$items = [];
foreach ($detalhes as $item) {
    $items[] = [
        "id" => (string)$item['agendamento_id'],
        "title" => $item['servico_nome'] . " - Prof. " . $item['terapeuta_nome'],
        "quantity" => 1,
        "unit_price" => (float)$item['servico_valor'],
        "currency_id" => "BRL"
    ];
}

// Dados da preferência
// Detecta a URL base dinamicamente para funcionar tanto em localhost quanto no servidor de teste
$protocol = 'http://';
// Verifica se é HTTPS nativo ou se está atrás de um proxy SSL (como no Railway)
if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
    $protocol = 'https://';
}

// Normaliza o caminho: converte backslashes do Windows em / e remove a barra final para evitar URLs malformadas
$currentPath = str_replace('\\', '/', dirname($_SERVER['REQUEST_URI']));
$baseUrl = $protocol . $_SERVER['HTTP_HOST'] . rtrim($currentPath, '/');

// Define a URL de notificação de forma absoluta baseada no HOST
$notificationUrl = $protocol . $_SERVER['HTTP_HOST'] . str_replace('/view', '/controller', $currentPath) . "/notificacao_mp.php";

// No Sandbox, o uso de um e-mail de teste evita conflitos com contas reais.
// Se o token for de teste, usamos um e-mail fictício padrão do Mercado Pago.
$email_pagador = $isTestToken ? "test_user_1303254949@testuser.com" : $_SESSION['email'];

$data = [
    "items" => $items,
    "payer" => [
        "email" => $email_pagador
    ],
    "back_urls" => [
        "success" => $baseUrl . "/sucesso.php",
        "failure" => $baseUrl . "/pedidos.php",
        "pending" => $baseUrl . "/pedidos.php"
    ],
    "notification_url" => $notificationUrl,
    "auto_return" => "approved",
    "external_reference" => (string)$pedido_id
];

// Requisição Nativa via cURL (Manual)
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $access_token",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
// Proteção contra erros de SSL em ambientes de container (Railway/Docker)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$curl_error = curl_error($ch);
$result = json_decode($response, true);

// Log de erro completo da resposta da API
error_log('Resposta Mercado Pago: ' . $response);

// Log de erro para depuração se a API falhar
if (curl_errno($ch)) {
    error_log('Erro cURL Mercado Pago: ' . $curl_error);
}

curl_close($ch);

$preference_id = $result['id'] ?? null;
}

if ($pedidoValido && $preference_id) {
    $pedidoModel->atualizarCheckoutId($pedido_id, $preference_id);
}
$public_key = getenv('MP_PUBLIC_KEY');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Pagamento — Aqui tem Terapia</title>
    <link rel="stylesheet" href="style.css">
    <!-- SDK Mercado Pago -->
    <script src="https://sdk.mercadopago.com/js/v2"></script>
</head>
<body>
    <div class="site">
        <header>
            <div class="logo">Aqui tem Terapia!</div>
            <nav><a class="cta" href="pedidos.php">Voltar aos Pedidos</a></nav>
        </header>

        <main>
            <section class="content" style="max-width: 600px; margin: 0 auto; text-align: center;">
                <?php if (!$pedidoValido): ?>
                    <h1>Meus Pedidos</h1>
                    <p class="empty-message">Você ainda não possui pedidos registrados ou este pedido não foi encontrado.</p>
                    <a href="tela_inicial.php" class="btn-primary" style="text-decoration: none; display: inline-block; padding: 10px 20px; margin-top: 20px;">Explorar Serviços</a>
                <?php else: ?>
                    <h1>Finalizar Pagamento</h1>
                    <p class="lead">Pedido #<?php echo $pedido_id; ?></p>
                    
                    <?php if ($preference_id): ?>
                    <div class="card" style="margin-bottom: 20px; padding: 30px; background: white;">
                        <h2 style="font-size: 2rem; color: var(--cta-bg);">
                            R$ <?php echo number_format($infoPedido['valor_total'], 2, ',', '.'); ?>
                        </h2>
                        <p>Você será redirecionado para o ambiente seguro do Mercado Pago.</p>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-error">
                        <p><strong>Erro ao gerar pagamento:</strong></p>
                        <p><?php 
                            if (isset($result['message'])) {
                                echo "Motivo: " . htmlspecialchars($result['message']);
                            } elseif (isset($result['cause'][0]['description'])) {
                                echo "Causa: " . htmlspecialchars($result['cause'][0]['description']);
                            } elseif (!empty($curl_error)) {
                                echo "Erro de Rede (cURL): " . htmlspecialchars($curl_error);
                            } else {
                                echo "Erro de comunicação ou dados inválidos (Verifique se o valor do serviço é maior que zero).";
                            }
                        ?></p>
                        <p>Verifique se as chaves MP_ACCESS_TOKEN e MP_PUBLIC_KEY estão configuradas corretamente no Railway.</p>
                        <a href="pedidos.php" class="btn-secondary" style="margin-top: 15px; display: inline-block;">Voltar</a>
                    </div>
                    <?php endif; ?>

                    <!-- Container do Botão do Mercado Pago -->
                    <div id="wallet_container"></div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <script>
        const publicKey = '<?php echo $public_key; ?>';
        if (!publicKey) console.error("Erro: MP_PUBLIC_KEY não encontrada.");

        const mp = new MercadoPago(publicKey, {
            locale: 'pt-BR'
        });

        <?php if ($preference_id): ?>
            mp.bricks().create("wallet", "wallet_container", {
                initialization: {
                    preferenceId: '<?php echo $preference_id; ?>',
                    redirectMode: 'modal'
                },
                customization: {
                    texts: {
                        valueProp: 'smart_option',
                    },
                },
            });
        <?php endif; ?>
    </script>
</body>
</html>