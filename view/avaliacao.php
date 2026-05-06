<?php
session_start();
require_once __DIR__ . '/../controller/conectarBD.php';
require_once __DIR__ . '/../model/Agendamento.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$agendamento_id = intval($_GET['agendamento_id'] ?? 0);
$agendamentoModel = new Agendamento($pdo);
$agendamento = $agendamentoModel->buscarPorId($agendamento_id);

if (!$agendamento || $agendamento['usuario_id'] != $_SESSION['usuario_id']) {
    die("Agendamento não encontrado.");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Avaliar Atendimento — Aqui tem Terapia</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="site">
        <?php include 'header.php'; ?>
        <main>
            <section class="content" style="max-width: 600px; margin: 0 auto;">
                <h1>Avaliar Atendimento</h1>
                <p class="lead">Sua opinião é fundamental para melhorarmos nossos serviços.</p>

                <form action="../controller/processar_avaliacao.php" method="POST" class="form">
                    <input type="hidden" name="agendamento_id" value="<?php echo $agendamento_id; ?>">
                    <input type="hidden" name="terapeuta_id" value="<?php echo $agendamento['terapeuta_id']; ?>">

                    <div class="input-group">
                        <label>Sua nota:</label>
                        <div class="estrelas">
                            <input type="radio" id="st5" name="nota" value="5" required/><label for="st5">★</label>
                            <input type="radio" id="st4" name="nota" value="4"/><label for="st4">★</label>
                            <input type="radio" id="st3" name="nota" value="3"/><label for="st3">★</label>
                            <input type="radio" id="st2" name="nota" value="2"/><label for="st2">★</label>
                            <input type="radio" id="st1" name="nota" value="1"/><label for="st1">★</label>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="comentario">O que você achou do atendimento?</label>
                        <textarea id="comentario" name="comentario" rows="5" placeholder="Escreva aqui..."></textarea>
                    </div>

                    <button type="submit" class="btn-primary">Salvar Avaliação</button>
                </form>
            </section>
        </main>
        <footer>© 2025 Aqui tem Terapia!</footer>
    </div>
</body>
</html>