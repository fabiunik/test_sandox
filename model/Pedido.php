<?php

class Pedido {
    private $con;

    public function __construct($con) {
        $this->con = $con;
    }

    public function criar($usuario_id, $valor_total) {
        $stmt = $this->con->prepare("INSERT INTO pedido (usuario_id, valor_total, status) VALUES (?, ?, 'pendente')");
        $stmt->execute([$usuario_id, $valor_total]);
        return $this->con->lastInsertId();
    }

    public function obterPorId($id) {
        $stmt = $this->con->prepare("SELECT * FROM pedido WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function atualizarCheckoutId($id, $checkout_id) {
        $stmt = $this->con->prepare("UPDATE pedido SET sessao_checkout_id = ? WHERE id = ?");
        return $stmt->execute([$checkout_id, $id]);
    }

    public function obterUltimoPendentePorUsuario($usuario_id) {
        $stmt = $this->con->prepare("SELECT id FROM pedido WHERE usuario_id = ? AND status = 'pendente' ORDER BY id DESC LIMIT 1");
        $stmt->execute([$usuario_id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ? (int)$res['id'] : 0;
    }

    /**
     * Busca os detalhes completos do pedido para a view de conferência
     */
    public function obterDetalhesPedido($pedido_id) {
        $sql = "SELECT 
                    p.id as pedido_id, p.valor_total, p.status as pedido_status,
                    a.id as agendamento_id, a.data, a.horario,
                    u.nome as terapeuta_nome,
                    i.nome as servico_nome, i.valor as servico_valor
                FROM pedido p
                JOIN agendamento a ON a.pedido_id = p.id
                JOIN usuario u ON a.terapeuta_id = u.id
                JOIN itens i ON a.itens_id = i.id
                WHERE p.id = ?";
        
        $stmt = $this->con->prepare($sql);
        $stmt->execute([$pedido_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPorUsuario($usuario_id) {
        $stmt = $this->con->prepare("SELECT * FROM pedido WHERE usuario_id = ? ORDER BY id DESC");
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function atualizarStatus($id, $status) {
        $stmt = $this->con->prepare("UPDATE pedido SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function relatorioFinanceiro($inicio, $fim, $terapeuta_id = null) {
        $sql = "SELECT p.*, u.nome as cliente_nome 
                FROM pedido p 
                JOIN usuario u ON p.usuario_id = u.id";
        
        $params = [$inicio . " 00:00:00", $fim . " 23:59:59"];
        
        if ($terapeuta_id) {
            // Filtra pedidos que possuem agendamentos deste terapeuta
            $sql .= " JOIN agendamento a ON a.pedido_id = p.id WHERE a.terapeuta_id = ? AND p.data_criacao BETWEEN ? AND ?";
            array_unshift($params, $terapeuta_id);
        } else {
            $sql .= " WHERE p.data_criacao BETWEEN ? AND ?";
        }

        $stmt = $this->con->prepare($sql . " GROUP BY p.id ORDER BY p.data_criacao DESC");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
