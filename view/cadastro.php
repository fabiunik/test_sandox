<?php
session_start();
$mensagem_erro = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

$old = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Cadastro — Aqui tem Terapia</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="site">
    <?php include 'header.php'; ?>

    <main class="login-centered">
      <section class="content" style="max-width: 500px; margin: 0 auto;" aria-labelledby="cadastro-title">
        <h1 id="cadastro-title">Crie sua conta</h1>
        <p class="lead">Junte-se a nós e comece sua jornada de bem-estar.</p>

        <?php if ($mensagem_erro): ?>
          <div class="alert alert-error">✗ <?php echo htmlspecialchars($mensagem_erro); ?></div>
        <?php endif; ?>

        <form class="form" action="../controller/gerenciar_usuarios.php" method="post">
          <input type="hidden" name="acao" value="criar">
          
          <div class="input-group">
            <label for="nome">Nome Completo</label>
            <input type="text" id="nome" name="nome" placeholder="Seu nome completo" value="<?php echo htmlspecialchars($old['nome'] ?? ''); ?>" required>
          </div>
          <div class="input-group">
            <label for="cpf">CPF</label>
            <input type="text" id="cpf" name="cpf" placeholder="000.000.000-00" value="<?php echo htmlspecialchars($old['cpf'] ?? ''); ?>" required>
          </div>
          <div class="input-group">
            <label for="dtnas">Data de Nascimento</label>
            <input type="date" id="dtnas" name="dtnas" value="<?php echo htmlspecialchars($old['dtnas'] ?? ''); ?>" required>
          </div>
          <div class="input-group">
            <label for="telefone">Telefone</label>
            <input type="tel" id="telefone" name="telefone" placeholder="(00) 00000-0000" value="<?php echo htmlspecialchars($old['telefone'] ?? ''); ?>" required>
          </div>
          <div class="input-group">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" placeholder="seu@email.com" value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>" required>
          </div>
          <div class="input-group">
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" placeholder="••••••••" required>
          </div>
          <div class="input-group">
            <label for="confirma_senha">Confirmar Senha</label>
            <input type="password" id="confirma_senha" name="confirma_senha" placeholder="••••••••" required>
          </div>

          <button type="submit" class="btn-primary">Finalizar Cadastro</button>
          
          <p style="text-align:center;margin-top:16px;color:var(--muted)">
            Já tem uma conta? <a href="login.php" style="color:var(--cta-bg);font-weight:700;text-decoration:none">Faça login</a>
          </p>
        </form>
      </section>
    </main>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
  <script>
    $(document).ready(function(){
        // Máscara de CPF
        $('#cpf').mask('000.000.000-00');

        // Máscara de Telefone Dinâmica (ajusta para 9º dígito)
        var behavior = function (val) {
            return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
        },
        options = {
            onKeyPress: function(val, e, field, options) {
                field.mask(behavior.apply({}, arguments), options);
            }
        };
        $('#telefone').mask(behavior, options);
    });

    document.querySelector('.form').onsubmit = function(e) {
        const senha = document.getElementById('senha').value;
        const confirma = document.getElementById('confirma_senha').value;
        if (senha !== confirma) {
            alert('As senhas não coincidem. Por favor, verifique os campos.');
            e.preventDefault();
            return false;
        }
    };
  </script>
</body>
</html>