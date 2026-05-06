<?php
// 1. Localiza o caminho para a raiz (subindo um nível da pasta controller)
$caminhoEnv = dirname(__DIR__) . '/.env';

if (file_exists($caminhoEnv)) {
    $linhas = file($caminhoEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($linhas as $linha) {
        if (strpos(trim($linha), '#') === 0) continue;

        if (strpos($linha, '=') !== false) {
            list($nome, $valor) = explode('=', $linha, 2);
            $nome = trim($nome);
            $valor = trim($valor);

            // Define nas globais para que fiquem disponíveis em todo o projeto
            putenv("$nome=$valor");
            $_ENV[$nome] = $valor;
        }
    }
}

// 2. Agora você usa as variáveis para a conexão
$host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '');
$db   = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? '');
$user = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? '');
$pass = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? '');
$key  = getenv('APP_KEY') ?: ($_ENV['APP_KEY'] ?? '');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}