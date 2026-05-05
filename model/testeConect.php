<?php
include 'config.php'; // ou o nome que você deu ao arquivo de conexão
if ($conn) {
    echo "✅ Conexão com o MySQL realizada com sucesso!";
    // Mostra a versão do servidor para confirmar
    echo "<br>Versão do Servidor: " . $conn->server_info;
}
?>