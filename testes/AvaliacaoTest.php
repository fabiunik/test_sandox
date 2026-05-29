<?php

use PHPUnit\Framework\TestCase;

class AvaliacaoTest extends TestCase {
    private $pdoMock;
    private $stmtMock;
    private $avaliacao;

    protected function setUp(): void {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(PDOStatement::class);
        $this->avaliacao = new Avaliacao($this->pdoMock);
    }

    public function testSalvarAvaliacaoComSucesso() {
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
        
        // Verificamos se o execute recebe os parâmetros tratados (item_id como null se for 0)
        $this->stmtMock->method('execute')->with([
            1,    // usuario_id
            null, // item_id (devido ao operador ?: no model)
            2,    // terapeuta_id
            5,    // nota
            'Excelente atendimento' // comentario
        ])->willReturn(true);

        // Simulamos o envio de 0 para um item, o que deve resultar em NULL no banco
        $resultado = $this->avaliacao->salvar(1, 0, 2, 5, 'Excelente atendimento');
        $this->assertTrue($resultado);
    }

    public function testBuscarPorTerapeutaRetornaDadosFormatados() {
        $dadosMock = [
            ['id' => 1, 'nota' => 5, 'comentario' => 'Muito bom', 'nome' => 'Cliente Teste'],
            ['id' => 2, 'nota' => 4, 'comentario' => 'Bom', 'nome' => 'Outro Cliente']
        ];

        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
        $this->stmtMock->method('execute')->willReturn(true);
        $this->stmtMock->method('fetchAll')->willReturn($dadosMock);

        $resultado = $this->avaliacao->buscarPorTerapeuta(2);

        $this->assertIsArray($resultado);
        $this->assertCount(2, $resultado);
        $this->assertEquals('Muito bom', $resultado[0]['comentario']);
        $this->assertEquals('Cliente Teste', $resultado[0]['nome']);
    }

    public function testVerificarAvaliacaoExistente() {
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
        $this->stmtMock->method('execute')->willReturn(true);
        $this->stmtMock->method('fetch')->willReturn(['id' => 99]);

        $resultado = $this->avaliacao->verificarAvaliacao(5);
        $this->assertTrue($resultado);
    }
}