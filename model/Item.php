<?php
class Item {
    private $con;
    public function __construct($con) { $this->con = $con; }

    public function cadastrar($terapeuta_id, $nome, $descricao, $valor, $imagemPath) {
        $stmt = $this->con->prepare(
            "INSERT INTO itens (terapeuta_id, nome, descricao, valor, imagem) VALUES (?, ?, ?, ?, ?)"
        );
        return $stmt->execute([$terapeuta_id, $nome, $descricao, $valor, $imagemPath]);
    }

    public function editar($id, $nome, $descricao, $valor, $imagemPath = null) {
        if ($imagemPath) {
            $stmt = $this->con->prepare(
                "UPDATE itens SET nome = ?, descricao = ?, valor = ?, imagem = ? WHERE id = ?"
            );
            return $stmt->execute([$nome, $descricao, $valor, $imagemPath, $id]);
        } else {
            $stmt = $this->con->prepare(
                "UPDATE itens SET nome = ?, descricao = ?, valor = ? WHERE id = ?"
            );
            return $stmt->execute([$nome, $descricao, $valor, $id]);
        }
    }

    public function excluir($id) {
        $stmt = $this->con->prepare("DELETE FROM itens WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function buscarPorId($id) {
        $stmt = $this->con->prepare("SELECT * FROM itens WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function listarPorTerapeuta($terapeuta_id) {
        $stmt = $this->con->prepare("SELECT * FROM itens WHERE terapeuta_id = ?");
        $stmt->execute([$terapeuta_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listar($busca = null) {
        if ($busca) {
            $stmt = $this->con->prepare("SELECT * FROM itens WHERE nome LIKE ? OR descricao LIKE ?");
            $stmt->execute(["%$busca%", "%$busca%"]);
        } else {
            $stmt = $this->con->query("SELECT * FROM itens");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>
