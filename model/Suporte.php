<?php

class Suporte {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function criarTicket($usuario_id, $assunto, $descricao) {
        $sql = "INSERT INTO suporte (usuario_id, assunto, descricao, status) VALUES (?, ?, ?, 'pendente')";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$usuario_id, $assunto, htmlspecialchars($descricao)]);
    }

    public function listarTodos() {
        $sql = "SELECT s.*, u.nome as usuario_nome, u.email as usuario_email 
                FROM suporte s 
                JOIN usuario u ON s.usuario_id = u.id 
                ORDER BY s.data_criacao DESC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function atualizarStatus($id, $status) {
        $stmt = $this->pdo->prepare("UPDATE suporte SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }
}