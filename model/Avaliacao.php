<?php

class Avaliacao {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function salvar($usuario_id, $item_id, $terapeuta_id, $nota, $comentario) {
        $sql = "INSERT INTO avaliacao (usuario_id, item_id, terapeuta_id, nota, comentario) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $usuario_id, 
            $item_id ?: null, 
            $terapeuta_id ?: null, 
            $nota, 
            htmlspecialchars($comentario)
        ]);
    }

    public function buscarPorTerapeuta($terapeuta_id) {
        $stmt = $this->pdo->prepare("SELECT a.*, u.nome FROM avaliacao a JOIN usuario u ON a.usuario_id = u.id WHERE a.terapeuta_id = ? ORDER BY a.data_criacao DESC");
        $stmt->execute([$terapeuta_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}