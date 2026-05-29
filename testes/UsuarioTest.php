<?php

use PHPUnit\Framework\TestCase;

class UsuarioTest extends TestCase {
    private $pdoMock;
    private $stmtMock;
    private $usuario;
    private $appKey = '12345678901234567890123456789012'; // 32 chars para AES-256

    protected function setUp(): void {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(PDOStatement::class);
        
        // Define a chave de ambiente para os testes de criptografia
        putenv("APP_KEY={$this->appKey}");
        
        $this->usuario = new Usuario($this->pdoMock);
    }

    public function testCriarUsuarioComSucessoECriptografia() {
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
        
        // Simula que não existe e-mail duplicado
        $this->stmtMock->method('execute')->willReturn(true);
        $this->stmtMock->method('fetch')->willReturn(false);
        
        $this->pdoMock->method('lastInsertId')->willReturn("1");

        $token = $this->usuario->criar(
            "João Teste", 
            "12345678901", 
            "1990-01-01", 
            "11999999999", 
            "joao@teste.com", 
            "Senha123"
        );

        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token)); // bin2hex(32)
    }

    public function testImpedirCadastroMenorDeIdade() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Você deve ter pelo menos 18 anos para se cadastrar.");

        $dataNasc = (new DateTime())->modify('-17 years')->format('Y-m-d');
        
        $this->usuario->criar("Jovem", "12345678901", $dataNasc, "11999999999", "jovem@teste.com", "Senha123");
    }

    public function testLoginComSucesso() {
        $senhaOriginal = "Senha123";
        $hash = password_hash($senhaOriginal, PASSWORD_ARGON2ID);

        $dadosUsuario = [
            'id' => 1,
            'nome' => 'João',
            'senha' => $hash,
            'tipo' => 'usuario',
            'email_confirmado' => 1,
            'status' => 'ativo'
        ];

        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
        $this->stmtMock->method('fetch')->willReturn($dadosUsuario);

        $resultado = $this->usuario->login("joao@teste.com", $senhaOriginal);
        
        $this->assertTrue($resultado);
        $this->assertEquals(1, $_SESSION['usuario_id']);
    }

    public function testSanitizacaoDeNomeXSS() {
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
        $this->stmtMock->method('fetch')->willReturn(false); // e-mail livre

        // O PHPUnit pode interceptar os argumentos passados para o execute
        $this->stmtMock->expects($this->atLeastOnce())
            ->method('execute')
            ->with($this->callback(function($params) {
                // Verifica se o primeiro parâmetro (nome) foi limpo de tags script
                if (is_array($params) && strpos($params[0], '<script>') === false) {
                    return true;
                }
                return false;
            }))
            ->willReturn(true);

        $nomeSujo = "<script>alert('hack')</script>João Limpo";
        $this->usuario->criar($nomeSujo, "12345678901", "1990-01-01", "11999999999", "xss@teste.com", "Senha123");
    }
}