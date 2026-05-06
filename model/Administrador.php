<?php
class Administrador extends Usuario {
    private $id;
    private $con;

    public function __construct($con) {
        $this->con = $con;
    }

    public function promoverTerapeuta($usuario_id) {
        $stmt = $this->con->prepare("UPDATE usuario SET tipo = 'terapeuta' WHERE id = ?");
        $stmt->bind_param("i", $usuario_id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function promoverAdmin($usuario_id) {
        $stmt = $this->con->prepare("UPDATE usuario SET tipo = 'administrador' WHERE id = ?");
        $stmt->bind_param("i", $usuario_id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function revogarPermissao($usuario_id) {
        $stmt = $this->con->prepare("UPDATE usuario SET tipo = 'usuario' WHERE id = ?");
        $stmt->bind_param("i", $usuario_id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function listarTodos() {
        $res = $this->con->query("SELECT * FROM usuario ORDER BY tipo, nome");
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function deletarUsuario($usuario_id) {
        $stmt = $this->con->prepare("DELETE FROM usuario WHERE id = ?");
        $stmt->bind_param("i", $usuario_id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // Relatórios globais
    public function relatorioConsultas() {
        $sql = "SELECT u.nome AS cliente, t.nome AS terapeuta, i.nome AS item, a.data, a.horario, a.status
                FROM agendamento a
                JOIN usuario u ON u.id = a.usuario_id
                JOIN usuario t ON t.id = a.terapeuta_id
                JOIN item i ON i.id = a.item_id
                ORDER BY a.data, a.horario";
        $res = $this->con->query($sql);
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function relatorioFinanceiro() {
        $sql = "SELECT p.id AS pedido, u.nome AS cliente, p.valor_total, pg.status, pg.data_pagamento
                FROM pedido p
                JOIN usuario u ON u.id = p.usuario_id
                LEFT JOIN pagamento pg ON pg.pedido_id = p.id
                ORDER BY pg.data_pagamento DESC";
        $res = $this->con->query($sql);
        return $res->fetch_all(MYSQLI_ASSOC);
    }
}
?>