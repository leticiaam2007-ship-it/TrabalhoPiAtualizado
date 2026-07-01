<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

try {
    $con = obterConexao();
    echo 'Conexao com MySQL OK.';
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Falha na conexao: ' . $e->getMessage();
}
