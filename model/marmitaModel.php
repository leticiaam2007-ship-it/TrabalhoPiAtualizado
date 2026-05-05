<?php
require_once 'config.php';

class MarmitaModel {
    public function salvarPedido($dados) {
        $conn = obterConexao();

        // Ajustado para as colunas exatas da sua imagem
        // usuario_id (coloquei 1 como padrão), status (pendente), valor_total
        $sql = "INSERT INTO pedidos (usuario_id, status, valor_total) VALUES (?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        $usuario_id = 1; // Teste com ID 1
        $status = 'pendente';
        $valor = $dados['valor'];

        $stmt->bind_param("isd", $usuario_id, $status, $valor);

        if ($stmt->execute()) {
            return true;
        } else {
            // Se der erro, isso vai aparecer no Console do navegador (F12)
            error_log("Erro no MySQL: " . $conn->error);
            return false;
        }
    }
}