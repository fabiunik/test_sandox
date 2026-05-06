<?php

use PHPUnit\Framework\TestCase;

class ItemTest extends TestCase {
    private $pdoMock;
    private $stmtMock;
    private $item;

    protected function setUp(): void {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(PDOStatement::class);
        $this->item = new Item($this->pdoMock);
    }

    public function testBuscarPorIdRetornaItemCorreto() {
        $dadosItem = [
            'id' => 10,
            'nome' => 'Terapia Cognitivo Comportamental',
            'valor' => 150.00
        ];

        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
        $this->stmtMock->method('execute')->with([10])->willReturn(true);
        $this->stmtMock->method('fetch')->willReturn($dadosItem);

        $resultado = $this->item->buscarPorId(10);

        $this->assertIsArray($resultado);
        $this->assertEquals('Terapia Cognitivo Comportamental', $resultado['nome']);
        $this->assertEquals(150.00, $resultado['valor']);
    }
}