<?php

class Disponibilidade {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Adicionar um slot de disponibilidade
     */
    public function adicionarSlot($terapeuta_id, $data, $horario, $tipo = 'presencial') {
        try {
            $sql = "INSERT INTO disponibilidade (terapeuta_id, data, horario, tipo) VALUES (?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$terapeuta_id, $data, $horario, $tipo]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Adicionar múltiplos slots (para recorrências)
     */
    public function adicionarSlotsRecorrencia($terapeuta_id, $dias_semana, $data_inicio, $data_fim, $horario_inicio, $horario_fim, $tipo = 'presencial') {
        $config = $this->obterConfiguracao($terapeuta_id);
        $duracao = $config['duracao_sessao'] ?? 60;
        $intervalo = $config['intervalo_sessoes'] ?? 0;

        $inicio_periodo = new DateTime($data_inicio);
        $fim_periodo = new DateTime($data_fim);
        $slots_adicionados = 0;

        while ($inicio_periodo <= $fim_periodo) {
            $dia_semana = intval($inicio_periodo->format('w'));
            $dia_semana = ($dia_semana === 0) ? 7 : $dia_semana;

            if (in_array($dia_semana, $dias_semana)) {
                $data_slot = $inicio_periodo->format('Y-m-d');
                $tempo_atual = strtotime($horario_inicio);
                $tempo_fim = strtotime($horario_fim);

                while ($tempo_atual < $tempo_fim) {
                    $horario_slot = date('H:i:s', $tempo_atual);
                    if ($this->adicionarSlot($terapeuta_id, $data_slot, $horario_slot, $tipo)) {
                        $slots_adicionados++;
                    }
                    $tempo_atual += ($duracao + $intervalo) * 60;
                }
            }

            $inicio_periodo->modify('+1 day');
        }

        return $slots_adicionados;
    }

    /**
     * Remover um slot
     */
    public function removerSlot($id, $terapeuta_id) {
        $sql = "DELETE FROM disponibilidade WHERE id = ? AND terapeuta_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id, $terapeuta_id]);
    }

    /**
     * Remover todos os slots de uma data
     */
    public function removerSlotsPorData($terapeuta_id, $data) {
        $sql = "DELETE FROM disponibilidade WHERE terapeuta_id = ? AND data = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$terapeuta_id, $data]);
    }

    /**
     * Listar disponibilidades de um terapeuta
     */
    public function listarPorTerapeuta($terapeuta_id, $limite = 50, $offset = 0) {
        $sql = "SELECT * FROM disponibilidade WHERE terapeuta_id = ? ORDER BY data DESC, horario DESC LIMIT " . intval($limite) . " OFFSET " . intval($offset);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$terapeuta_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Listar disponibilidades futuras de um terapeuta (para agendamento)
     */
    public function listarFuturas($terapeuta_id) {
        $hoje = date('Y-m-d');
        $sql = "SELECT * FROM disponibilidade 
                WHERE terapeuta_id = ? AND data >= ? 
                ORDER BY data ASC, horario ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$terapeuta_id, $hoje]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obter configuração do terapeuta em perfil_terapeuta
     */
    public function obterConfiguracao($terapeuta_id) {
        $sql = "SELECT duracao_sessao, dias_visibilidade, intervalo_sessoes, antecedencia_dias FROM perfil_terapeuta WHERE usuario_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$terapeuta_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Salvar ou atualizar configuração em perfil_terapeuta
     */
    public function salvarConfiguracao($terapeuta_id, $duracao_sessao, $dias_visibilidade, $intervalo_sessoes, $antecedencia_dias) {
        $existe = $this->obterConfiguracao($terapeuta_id);

        if ($existe) {
            $sql = "UPDATE perfil_terapeuta SET duracao_sessao = ?, dias_visibilidade = ?, intervalo_sessoes = ?, antecedencia_dias = ? WHERE usuario_id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$duracao_sessao, $dias_visibilidade, $intervalo_sessoes, $antecedencia_dias, $terapeuta_id]);
        } else {
            $sql = "INSERT INTO perfil_terapeuta (usuario_id, duracao_sessao, dias_visibilidade, intervalo_sessoes, antecedencia_dias) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$terapeuta_id, $duracao_sessao, $dias_visibilidade, $intervalo_sessoes, $antecedencia_dias]);
        }
    }

    /**
     * Buscar slot por ID
     */
    public function buscarPorId($id, $terapeuta_id = null) {
        if ($terapeuta_id) {
            $sql = "SELECT * FROM disponibilidade WHERE id = ? AND terapeuta_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id, $terapeuta_id]);
        } else {
            $sql = "SELECT * FROM disponibilidade WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
        }
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Verificar disponibilidade para agendamento
     */
    public function verificarDisponibilidade($terapeuta_id, $data, $horario) {
        $sql = "SELECT id FROM disponibilidade WHERE terapeuta_id = ? AND data = ? AND horario = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$terapeuta_id, $data, $horario]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
    }

    /**
     * Contar slots disponíveis para um terapeuta em um período
     */
    public function contarSlotsDisponíveis($terapeuta_id, $data_inicio = null, $data_fim = null) {
        $sql = "SELECT COUNT(*) as total FROM disponibilidade WHERE terapeuta_id = ?";
        $params = [$terapeuta_id];

        if ($data_inicio && $data_fim) {
            $sql .= " AND data BETWEEN ? AND ?";
            $params[] = $data_inicio;
            $params[] = $data_fim;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'] ?? 0;
    }

    /**
     * Listar disponibilidades por tipo (presencial/online)
     */
    public function listarPorTipo($terapeuta_id, $tipo) {
        $sql = "SELECT * FROM disponibilidade WHERE terapeuta_id = ? AND tipo = ? ORDER BY data DESC, horario DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$terapeuta_id, $tipo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
