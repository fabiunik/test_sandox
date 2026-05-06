<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="5;url=login.php">
    <title>Cadastro Realizado — Aqui tem Terapia</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="site">
        <?php include 'header.php'; ?>
        <main>
            <section class="content" style="max-width: 600px; margin: 40px auto; text-align: center;">
                <div style="font-size: 4rem;">✅</div>
                <h1>Cadastro Realizado!</h1>
                <p class="lead">Seu cadastro foi concluído com sucesso. Seja bem-vindo(a) ao portal!</p>
                <p>Você será redirecionado para a tela de login em 5 segundos...</p>
                <div style="margin-top: 25px;">
                    <a href="login.php" class="btn-primary" style="text-decoration: none; padding: 12px 24px;">Ir para Login agora</a>
                </div>
            </section>
        </main>
    </div>
</body>
</html>