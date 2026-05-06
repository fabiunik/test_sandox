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
} else {
    die("Erro: Arquivo .env não encontrado na raiz!");
}

// 2. Agora você usa as variáveis para a conexão
$host = $_ENV['DB_HOST'];
$db   = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];
$key  = $_ENV['APP_KEY']; // Sua chave de criptografia aqui

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}