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

    public function listarPorTerapeuta($terapeuta_id, $limite = null, $offset = null) {
        $sql = "SELECT * FROM itens WHERE terapeuta_id = ?";
        if ($limite !== null && $offset !== null) {
            $sql .= " ORDER BY id DESC LIMIT :limite OFFSET :offset";
            $stmt = $this->con->prepare($sql);
            $stmt->bindParam(':terapeuta_id', $terapeuta_id, PDO::PARAM_INT);
            $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        } else {
            $sql .= " ORDER BY id DESC";
            $stmt = $this->con->prepare($sql);
            $stmt->bindParam(':terapeuta_id', $terapeuta_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarPorTerapeuta($terapeuta_id) {
        $stmt = $this->con->prepare("SELECT COUNT(*) FROM itens WHERE terapeuta_id = ?");
        $stmt->execute([$terapeuta_id]);
        return $stmt->fetchColumn();
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
