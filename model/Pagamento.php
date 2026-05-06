<?php

class Pagamento {
    private $con;

    public function __construct($con) {
        $this->con = $con;
    }

    public function registrarTransacao($pedido_id, $transacao_id, $metodo, $valor, $status) {
        $stmt = $this->con->prepare(
            "INSERT INTO pagamento (pedido_id, transacao_id, metodo_pagamento, valor_pago, status, data_pagamento) 
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        return $stmt->execute([$pedido_id, $transacao_id, $metodo, $valor, $status]);
    }

    public function buscarPorTransacaoId($transacao_id) {
        $stmt = $this->con->prepare("SELECT * FROM pagamento WHERE transacao_id = ?");
        $stmt->execute([$transacao_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function buscarPorPedido($pedido_id) {
        $stmt = $this->con->prepare("SELECT * FROM pagamento WHERE pedido_id = ? ORDER BY data_pagamento DESC");
        $stmt->execute([$pedido_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function atualizarStatus($id, $status) {
        $stmt = $this->con->prepare("UPDATE pagamento SET status = ?, data_pagamento = NOW() WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }
}
