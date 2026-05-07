<?php
session_start();
require_once __DIR__ . '/../controller/conectarBD.php';
require_once __DIR__ . '/../model/Pedido.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$pedido_id = intval($_GET['pedido_id'] ?? 0);
$pedidoModel = new Pedido($pdo);
$infoPedido = $pedidoModel->obterPorId($pedido_id);
$detalhes = $pedidoModel->obterDetalhesPedido($pedido_id);

if (!$infoPedido || $infoPedido['usuario_id'] != $_SESSION['usuario_id']) {
    die("Pedido não encontrado.");
}

// --- INTEGRAÇÃO MERCADO PAGO ---
$access_token = getenv('MP_ACCESS_TOKEN');
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
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$baseUrl = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);

$data = [
    "items" => $items,
    "back_urls" => [
        "success" => $baseUrl . "/sucesso.php",
        "failure" => $baseUrl . "/pedidos.php",
        "pending" => $baseUrl . "/pedidos.php"
    ],
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
$response = curl_exec($ch);
$result = json_decode($response, true);

// Log de erro completo da resposta da API
error_log('Resposta Mercado Pago: ' . $response);

// Log de erro para depuração se a API falhar
if (curl_errno($ch)) {
    error_log('Erro cURL Mercado Pago: ' . curl_error($ch));
}

curl_close($ch);

$preference_id = $result['id'] ?? null;

if ($preference_id) {
    $pedidoModel->atualizarCheckoutId($pedido_id, $preference_id);
}
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
                        <p><?php echo htmlspecialchars($result['message'] ?? 'Erro de comunicação com o Mercado Pago.'); ?></p>
                        <p>Verifique se as chaves MP_ACCESS_TOKEN e MP_PUBLIC_KEY estão configuradas corretamente no Railway.</p>
                        <a href="pedidos.php" class="btn-secondary" style="margin-top: 15px; display: inline-block;">Voltar</a>
                    </div>
                <?php endif; ?>

                <!-- Container do Botão do Mercado Pago -->
                <div id="wallet_container"></div>
            </section>
        </main>
    </div>

    <script>
        const mp = new MercadoPago('<?php echo getenv('MP_PUBLIC_KEY'); ?>', {
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