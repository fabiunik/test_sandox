<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Sucesso — Aqui tem Terapia</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="site">
        <?php include 'header.php'; ?>
        <main>
            <section class="content" style="max-width: 600px; margin: 40px auto; text-align: center;">
                <div style="font-size: 4rem;">🎉</div>
                <h1>Pagamento Recebido!</h1>
                <p class="lead">Seu agendamento foi confirmado com sucesso.</p>
                
                <div class="card" style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 20px;">
                    <p>O profissional já foi notificado e aguarda você na data e hora escolhidas.</p>
                </div>

                <div style="margin-top: 30px; display: flex; gap: 15px; justify-content: center;">
                    <a href="pedidos.php" class="btn-primary" style="text-decoration: none; padding: 12px 24px;">Ver Meus Pedidos</a>
                    <a href="agendamento.php" class="btn-secondary" style="text-decoration: none; padding: 12px 24px;">Novo Agendamento</a>
                </div>
            </section>
        </main>
    </div>
</body>
</html>