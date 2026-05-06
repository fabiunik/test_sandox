<?php

use PHPUnit\Framework\TestCase;

class AgendamentoTest extends TestCase {
    private $pdoMock;
    private $stmtMock;
    private $agendamento;

    protected function setUp(): void {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(PDOStatement::class);
        $this->agendamento = new Agendamento($this->pdoMock);
    }

    public function testBuscarAgendamentoPorId() {
        $dadosAgendamento = [
            'id' => 5,
            'usuario_id' => 1,
            'itens_id' => 10,
            'pedido_id' => null
        ];

        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
        $this->stmtMock->method('execute')->willReturn(true);
        $this->stmtMock->method('fetch')->willReturn($dadosAgendamento);

        $resultado = $this->agendamento->buscarPorId(5);

        $this->assertEquals(1, $resultado['usuario_id']);
        $this->assertNull($resultado['pedido_id']);
    }

    public function testVincularPedidoAoAgendamento() {
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
        
        // Verifica se o UPDATE está passando os IDs na ordem correta
        $this->stmtMock->method('execute')
            ->with([20, 5]) // [pedido_id, agendamento_id]
            ->willReturn(true);

        $resultado = $this->agendamento->vincularPedido(5, 20);
        
        $this->assertTrue($resultado);
    }
}