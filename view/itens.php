<?php
session_start();
require_once '../controller/conectarBD.php';
require_once '../model/Item.php';

$busca = $_GET['busca'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Serviços — Aqui tem Terapia</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="site">
    <?php include 'header.php'; ?>

    <main>

      <section class="services-wrapper" aria-label="Lista de serviços">
        <h1 class="page-title">Conheça nossas especialidades</h1>
        <p class="lead">Atendimentos presenciais e online — escolha a terapia que mais combina com você.</p>

        <div class="services">
          <?php if ($busca): ?>
            <p>Mostrando resultados para: <strong>"<?php echo htmlspecialchars($busca); ?>"</strong> | <a href="itens.php">Limpar busca</a></p>
          <?php endif; ?>
          <div class="card-grid">
            <?php
            $itemModel = new Item($pdo);
            // Query customizada para trazer o nome do terapeuta junto
            $sql = "SELECT i.*, u.nome AS terapeuta 
                    FROM itens i 
                    JOIN usuario u ON i.terapeuta_id = u.id";
            if ($busca) {
                $sql .= " WHERE i.nome LIKE :b OR i.descricao LIKE :b";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['b' => "%$busca%"]);
            } else {
                $stmt = $pdo->query($sql);
            }
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($result):
                foreach($result as $row): ?>
                  <article class="service-card">
                      <img class="avatar" 
                          src="<?php echo !empty($row['imagem']) ? '../' . $row['imagem'] : 'https://placehold.co/239x239'; ?>" 
                          alt="<?php echo htmlspecialchars($row['nome']); ?>">
                      <h3><?php echo htmlspecialchars($row['nome']); ?></h3>
                      <p><?php echo htmlspecialchars($row['descricao']); ?></p>
                      <p><strong>Valor:</strong> R$ <?php echo number_format($row['valor'], 2, ',', '.'); ?></p>
                      <p><em>Terapeuta:</em> <?php echo htmlspecialchars($row['terapeuta']); ?></p>
                      <a class="cta" href="agendamento.php?item_id=<?php echo (isset($row['id']) ? $row['id'] : ''); ?>&terapeuta_id=<?php echo (isset($row['terapeuta_id']) ? $row['terapeuta_id'] : ''); ?>">
                      Agendar</a>
                  </article>                 
            <?php endforeach;
            else: ?>
                <p>Nenhum serviço cadastrado no momento.</p>
            <?php endif; ?>
          </div>
        </div>
      </section>
    </main>
    <?php include $view_path . 'footer.php'; ?>
  </div>
</body>
</html>
