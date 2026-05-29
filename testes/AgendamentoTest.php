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

    public function testVerificarDisponibilidadeComConflito() {
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
        
        // Simula que o slot existe na tabela disponibilidade
        $this->stmtMock->method('fetchColumn')
            ->will($this->onConsecutiveCalls(1, 1)); // 1 para disponibilidade, 1 para agendamento ocupado

        // Como o segundo fetchColumn retorna 1 (já existe agendamento), deve retornar false
        $resultado = $this->agendamento->verificarDisponibilidade(1, '2025-01-01', '10:00:00', 60);
        
        $this->assertFalse($resultado);
    }

    public function testVerificarDisponibilidadeLivre() {
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
        
        // Simula que o slot existe mas não há agendamentos
        $this->stmtMock->method('fetchColumn')
            ->will($this->onConsecutiveCalls(1, 0)); 

        $resultado = $this->agendamento->verificarDisponibilidade(1, '2025-01-01', '10:00:00', 60);
        
        $this->assertTrue($resultado);
    }
}