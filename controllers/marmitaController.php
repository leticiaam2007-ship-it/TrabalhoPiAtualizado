<?php
require_once '../model/MarmitaModel.php';

// Se receber um POST, é para salvar o pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $dados = json_decode($json, true);
    
    $model = new MarmitaModel();
    $sucesso = $model->salvarPedido($dados); // Você deve criar essa função no Model
    
    echo json_encode(['success' => $sucesso]);
    exit;
}

// Se for um GET, busca a marmita para exibir na View
$id = $_GET['id'] ?? null;
$model = new MarmitaModel();
$marmita = $model->buscarPorId($id);

// Se não achar, redireciona ou mostra erro
if (!$marmita) {
    die("Marmita não encontrada!");
}

include '../View/marmita.php';