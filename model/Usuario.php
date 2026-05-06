<?php


class Usuario {
    private $id;
    private $nome;
    private $cpf;
    private $dtnas;
    private $telefone;
    private $email;
    private $senha;
    private $tipo; // 'usuario', 'terapeuta', 'administrador'
    private $con;

    public function __construct($con) {
        $this->con = $con;
    }

    // Criar novo usuário
    public function criar($nome, $cpf, $dtnas, $telefone, $email, $senha, $tipo = 'usuario') {
        // --- 1. VALIDAÇÕES INICIAIS ---
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("E-mail inválido.");
        }

        if (!preg_match('/^\d{3}\.\d{3}\.\d{3}\-\d{2}$/', $cpf)) {
            throw new Exception("CPF inválido.");
        }

        $telefoneLimpo = preg_replace('/\D/', '', $telefone);
        if (strlen($telefoneLimpo) < 10 || strlen($telefoneLimpo) > 11) {
            throw new Exception("Telefone inválido.");
        }

        // --- 2. TRATAMENTO E CRIPTOGRAFIA ---
        $nomeSanitizado = htmlspecialchars(strip_tags($nome));
        $senhaHash = password_hash($senha, PASSWORD_ARGON2ID);

        // Criptografando dados sensíveis
        $cpfCripto = $this->criptografar($cpf);
        $telCripto = $this->criptografar($telefoneLimpo);

        // --- 3. VERIFICAÇÃO DE DUPLICIDADE ---
        $stmt = $this->con->prepare("SELECT id FROM usuario WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("E-mail já cadastrado.");
        }

        // --- 4. PERSISTÊNCIA ---
        $stmt = $this->con->prepare("INSERT INTO usuario (nome, cpf, dtnas, telefone, email, senha, tipo)
                                    VALUES (?, ?, ?, ?, ?, ?, ?)");

        if ($stmt->execute([$nomeSanitizado, $cpfCripto, $dtnas, $telCripto, $email, $senhaHash, $tipo])) {
            $this->id = $this->con->lastInsertId();
            $this->nome = $nomeSanitizado;
            $this->email = $email;
            return $this->id;
        } else {
            throw new Exception("Erro ao criar usuário.");
        }
    } 
      
    // função de criptografia
    private function criptografar($dados) {
        $chave = getenv('APP_KEY'); 
        $iv = openssl_random_pseudo_bytes(12); // GCM usa 12 bytes de IV por padrão

        // Criptografa. A $tag é gerada automaticamente pelo PHP
        $valorCripto = openssl_encrypt($dados, 'aes-256-gcm', $chave, 0, $iv, $tag);

        // Retorna tudo grudado: IV (12 bytes) + TAG (16 bytes) + Dados
        // O base64 garante que isso vire uma string comum para o banco
        return base64_encode($iv . $tag . $valorCripto);
    }
    
        private function descriptografar($valorBanco) {
        $chave = getenv('APP_KEY');
        $decodificado = base64_decode($valorBanco);

        // Extraímos os pedaços pelos tamanhos fixos
        $iv = substr($decodificado, 0, 12);
        $tag = substr($decodificado, 12, 16);
        $dadosCripto = substr($decodificado, 28);

        return openssl_decrypt($dadosCripto, 'aes-256-gcm', $chave, 0, $iv, $tag);
    }

    // Login
    public function login($email, $senha) {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);

        $stmt = $this->con->prepare("SELECT id, senha, tipo, nome FROM usuario WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if($user && password_verify($senha, $user['senha'])) {
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['tipo'] = $user['tipo'];
            $_SESSION['email'] = $email;
            $_SESSION['nome'] = $user['nome'];
            return true;
        }
        return false;
    }

    // Logout
    public function logout() {
        session_destroy();
    }

    // Verificar se é terapeuta
    public function ehTerapeuta() {
        return isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'terapeuta';
    }

    // Verificar se é administrador
    public function ehAdministrador() {
        return isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'administrador';
    }

    // Verificar se está autenticado
    public function estaAutenticado() {
        return isset($_SESSION['usuario_id']);
    }

    // Obter tipo do usuário
    public function getTipo() {
        return $_SESSION['tipo'] ?? null;
    }

    /**
     * Obter usuário por ID (com dados descriptografados para uso na aplicação/perfil)
     */
    public function obterPorId($id) {
        $stmt = $this->con->prepare("SELECT * FROM usuario WHERE id = ?");
        $stmt->execute([$id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // Descriptografa dados sensíveis para exibição no Perfil
            if (!empty($usuario['cpf'])) {
                $usuario['cpf'] = $this->descriptografar($usuario['cpf']);
            }
            if (!empty($usuario['telefone'])) {
                $usuario['telefone'] = $this->descriptografar($usuario['telefone']);
            }
        }
        return $usuario;
    }

    /**
     * Alias para obterPorId, utilizado no controlador gerenciar_usuarios.php
     */
    public function buscarPorId($id) {
        return $this->obterPorId($id);
    }

    // Editar usuário
    public function editar($id, $nome, $telefone, $email) {
        // Criptografa o telefone antes de salvar no banco para manter a consistência
        $telefoneLimpo = preg_replace('/\D/', '', $telefone);
        $telCripto = $this->criptografar($telefoneLimpo);

        $stmt = $this->con->prepare("UPDATE usuario SET nome = ?, telefone = ?, email = ? WHERE id = ?");
        $resultado = $stmt->execute([$nome, $telCripto, $email, $id]);
        return $resultado;
    }

    //recupera senha
    public function buscarPorEmail($email) {
        $stmt = $this->con->prepare("SELECT * FROM usuario WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        return $usuario;
    }

    public function gerarTokenRecuperacao($usuario_id) {
        $token = bin2hex(random_bytes(16)); // gera token aleatório
        $stmt = $this->con->prepare("UPDATE usuario SET token_recuperacao = ? WHERE id = ?");
        $stmt->execute([$token, $usuario_id]);
        return $token;
    }

    //alterar senha
    public function buscarPorToken($token) {
        $stmt = $this->con->prepare("SELECT * FROM usuario WHERE token_recuperacao = ?");
        $stmt->execute([$token]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        return $usuario;
    }

    public function atualizarSenhaPerfil($usuario_id, $novaSenhaHash) {
        $stmt = $this->con->prepare("UPDATE usuario SET senha = ? WHERE id = ?");
        $stmt->execute([$novaSenhaHash, $usuario_id]);
        return $stmt->rowCount() > 0;
    }
    
    public function atualizarSenhaToken($usuario_id, $novaSenhaHash) {
        $stmt = $this->con->prepare("UPDATE usuario SET senha = ?, token_recuperacao = NULL WHERE id = ?");
        $stmt->execute([$novaSenhaHash, $usuario_id]);
        return $stmt->rowCount() > 0;
    }

    // Listar todos os usuários
    public function listar() {
        $stmt = $this->con->query("SELECT * FROM usuario ORDER BY nome");
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $usuarios;
    }

    // Listar terapeutas
    public function listarTerapeutas() {
        $stmt = $this->con->query("SELECT u.*, p.foto as imagem FROM usuario u LEFT JOIN perfil_terapeuta p ON u.id = p.usuario_id WHERE u.tipo = 'terapeuta' ORDER BY u.nome");
        $terapeutas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $terapeutas;
    }

    // Atualizar tipo de usuário (apenas admin)
    public function atualizarTipo($usuario_id, $novo_tipo) {
        if(!$this->ehAdministrador()) {
            throw new Exception("Permissão negada!");
        }

        $stmt = $this->con->prepare("UPDATE usuario SET tipo = ? WHERE id = ?");

        if($stmt->execute([$novo_tipo, $usuario_id])) {
            return true;
        } else {
            throw new Exception("Erro ao atualizar tipo.");
        }
    }

    // Deletar usuário
    public function deletar($usuario_id) {
    // Verifica se o usuário está logado
        if (!isset($_SESSION['usuario_id'])) {
            throw new Exception("Você precisa estar logado para excluir um perfil.");
        }

        // Se for administrador, pode excluir qualquer usuário
        if ($this->ehAdministrador()) {
            // ok, segue para exclusão
        } else {
            // Se não for administrador, só pode excluir o próprio perfil
            if ($_SESSION['usuario_id'] != $usuario_id) {
                throw new Exception("Você só pode excluir o seu próprio perfil.");
            }
        }

        // Executa a exclusão no banco
        $stmt = $this->con->prepare("DELETE FROM usuario WHERE id = ?");

        if ($stmt->execute([$usuario_id])) {
            return true;
        } else {
            throw new Exception("Erro ao deletar usuário.");
        }
    }

    // Getters
    public function getId() { return $this->id; }
    public function getNome() { return $this->nome; }
    public function getEmail() { return $this->email; }
    public function getTipoUsuario() { return $this->tipo; }
}
?>