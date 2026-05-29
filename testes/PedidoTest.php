<?php

use PHPUnit\Framework\TestCase;

class PedidoTest extends TestCase {
    private $pdoMock;
    private $stmtMock;
    private $pedido;

    protected function setUp(): void {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(PDOStatement::class);
        $this->pedido = new Pedido($this->pdoMock);
    }

    public function testCriarPedidoRetornaUltimoId() {
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
        $this->stmtMock->method('execute')->willReturn(true);
        
        // Simula o retorno do ID auto-incremento do MySQL
        $this->pdoMock->method('lastInsertId')->willReturn("50");

        $novoId = $this->pedido->criar(1, 250.00);
        
        $this->assertEquals(50, $novoId);
    }

    public function testObterDetalhesPedido() {
        $detalhesFake = [
            [
                'id' => 1,
                'servico_nome' => 'Consulta Online',
                'terapeuta_nome' => 'Dr. Freud',
                'data' => '2025-05-10',
                'horario' => '14:00:00',
                'servico_valor' => 200.00
            ]
        ];

        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
        $this->stmtMock->method('execute')->willReturn(true);
        $this->stmtMock->method('fetchAll')->willReturn($detalhesFake);

        $resultado = $this->pedido->obterDetalhesPedido(1);

        $this->assertCount(1, $resultado);
        $this->assertEquals('Dr. Freud', $resultado[0]['terapeuta_nome']);
    }

    public function testListarPorUsuario() {
        $pedidosUsuario = [
            ['id' => 1, 'valor_total' => 100.00, 'status' => 'pendente'],
            ['id' => 2, 'valor_total' => 150.00, 'status' => 'pago']
        ];

        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
        $this->stmtMock->method('execute')->willReturn(true);
        $this->stmtMock->method('fetchAll')->willReturn($pedidosUsuario);

        $resultado = $this->pedido->listarPorUsuario(1);

        $this->assertCount(2, $resultado);
        $this->assertEquals('pago', $resultado[1]['status']);
    }
}