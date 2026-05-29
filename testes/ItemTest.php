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

    public function testCadastrarItem() {
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
        
        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with([5, 'Novo Serviço', 'Descrição', 100.0, 'path/to/img.jpg'])
            ->willReturn(true);

        $resultado = $this->item->cadastrar(5, 'Novo Serviço', 'Descrição', 100.0, 'path/to/img.jpg');
        $this->assertTrue($resultado);
    }

    public function testEditarItemSemTrocarImagem() {
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
        
        // Verifica se o SQL de UPDATE sem imagem é chamado
        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with(['Nome Editado', 'Desc Editada', 120.0, 10])
            ->willReturn(true);

        $resultado = $this->item->editar(10, 'Nome Editado', 'Desc Editada', 120.0, null);
        $this->assertTrue($resultado);
    }

    public function testExcluirItem() {
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with([10])
            ->willReturn(true);

        $resultado = $this->item->excluir(10);
        $this->assertTrue($resultado);
    }

    public function testContarPorTerapeuta() {
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
        $this->stmtMock->method('fetchColumn')->willReturn(5);

        $resultado = $this->item->contarPorTerapeuta(1);
        $this->assertEquals(5, $resultado);
    }
}