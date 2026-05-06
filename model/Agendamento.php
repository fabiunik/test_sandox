<?php

class Agendamento {
    private $con;

    public function __construct($con) {
        $this->con = $con;
    }

    public function criar($usuario_id, $terapeuta_id, $item_id, $data, $horario, $duracao, $observacoes = null, $status = 'pendente', $pedido_id = null) {
        // Verificar se o horário está disponível
        if (!$this->verificarDisponibilidade($terapeuta_id, $data, $horario, $duracao)) {
            throw new Exception("Horário não disponível.");
        }

        $stmt = $this->con->prepare(
            "INSERT INTO agendamento (usuario_id, terapeuta_id, itens_id, data, horario, duracao, observacoes, status, pedido_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$usuario_id, $terapeuta_id, $item_id, $data, $horario, $duracao, $observacoes, $status, $pedido_id]);
        return $this->con->lastInsertId();
    }

    public function buscarPorId($id) {
        $stmt = $this->con->prepare("SELECT * FROM agendamento WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function listarPorUsuario($usuario_id) {
        $stmt = $this->con->prepare("SELECT a.*, u.nome as terapeuta_nome, i.nome as item_nome FROM agendamento a JOIN usuario u ON a.terapeuta_id = u.id JOIN itens i ON a.itens_id = i.id WHERE a.usuario_id = ? ORDER BY a.data DESC, a.horario DESC");
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPorTerapeuta($terapeuta_id) {
        $stmt = $this->con->prepare("SELECT a.*, u.nome as usuario_nome, i.nome as item_nome FROM agendamento a JOIN usuario u ON a.usuario_id = u.id JOIN itens i ON a.itens_id = i.id WHERE a.terapeuta_id = ? ORDER BY a.data DESC, a.horario DESC");
        $stmt->execute([$terapeuta_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function vincularPedido($agendamento_id, $pedido_id) {
        $stmt = $this->con->prepare("UPDATE agendamento SET pedido_id = ? WHERE id = ?");
        return $stmt->execute([$pedido_id, $agendamento_id]);
    }

    public function verificarDisponibilidade($terapeuta_id, $data, $horario, $duracao) {
        $slotsNeeded = max(1, (int) ceil($duracao / 60));
        $startTimestamp = strtotime("{$data} {$horario}");
        if ($startTimestamp === false) {
            return false;
        }

        for ($slot = 0; $slot < $slotsNeeded; $slot++) {
            $slotHorario = date('H:i:s', strtotime("+{$slot} hour", $startTimestamp));

            $stmtDisp = $this->con->prepare("SELECT COUNT(*) FROM disponibilidade WHERE terapeuta_id = ? AND data = ? AND horario = ?");
            $stmtDisp->execute([$terapeuta_id, $data, $slotHorario]);
            if ($stmtDisp->fetchColumn() == 0) {
                return false;
            }

            $stmtAg = $this->con->prepare("SELECT COUNT(*) FROM agendamento WHERE terapeuta_id = ? AND data = ? AND horario = ? AND status != 'cancelado'");
            $stmtAg->execute([$terapeuta_id, $data, $slotHorario]);
            if ($stmtAg->fetchColumn() > 0) {
                return false;
            }
        }

        return true;
    }

    public function listarHorariosDisponiveis($terapeuta_id, $data, $duracao = 60) {
        // Função legada, não usada pelo endpoint atual.
        return [];
    }

    public function listarHorariosDisponiveisPorPeriodo($terapeuta_id, $duracao = 60, $dias = 14) {
        $hoje = date('Y-m-d');
        $fim = date('Y-m-d', strtotime("+$dias days"));

        $stmt = $this->con->prepare(
            "SELECT data, horario FROM disponibilidade WHERE terapeuta_id = ? AND data BETWEEN ? AND ? ORDER BY data ASC, horario ASC"
        );
        $stmt->execute([$terapeuta_id, $hoje, $fim]);
        $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $horarios = [];
        foreach ($slots as $slot) {
            if ($this->verificarDisponibilidade($terapeuta_id, $slot['data'], $slot['horario'], $duracao)) {
                $horarios[] = ['data' => $slot['data'], 'horario' => substr($slot['horario'], 0, 5)];
            }
        }

        return $horarios;
    }

    public function atualizarStatus($id, $status) {
        $stmt = $this->con->prepare("UPDATE agendamento SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function cancelar($id) {
        return $this->atualizarStatus($id, 'cancelado');
    }

    public function listarPorPeriodo($inicio, $fim, $terapeuta_id = null) {
        $sql = "SELECT a.*, u.nome as usuario_nome, t.nome as terapeuta_nome, i.nome as item_nome 
                FROM agendamento a 
                JOIN usuario u ON a.usuario_id = u.id 
                JOIN usuario t ON a.terapeuta_id = t.id 
                JOIN itens i ON a.itens_id = i.id 
                WHERE a.data BETWEEN ? AND ?";
        $params = [$inicio, $fim];
        if ($terapeuta_id) {
            $sql .= " AND a.terapeuta_id = ?";
            $params[] = $terapeuta_id;
        }
        $stmt = $this->con->prepare($sql . " ORDER BY a.data DESC");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
