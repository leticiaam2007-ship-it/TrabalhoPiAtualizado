<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\JsonResponse;
use App\Core\SessionAuth;
use App\Models\ProductModel;
use Throwable;

final class ProductApiController
{
    private ProductModel $products;

    public function __construct()
    {
        $this->products = new ProductModel();
    }

    public function handle(): void
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (!in_array($method, ['GET', 'POST', 'DELETE'], true)) {
            header('Allow: GET, POST, DELETE');
            JsonResponse::send(405, ['ok' => false, 'message' => 'Metodo nao permitido.']);
        }

        try {
            if ($method === 'GET') {
                $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
                if ($id > 0) {
                    $product = $this->products->findActiveById($id);
                    if (!$product) {
                        JsonResponse::send(404, ['ok' => false, 'message' => 'Produto nao encontrado.']);
                    }
                    JsonResponse::send(200, ['ok' => true, 'data' => $product]);
                }

                JsonResponse::send(200, ['ok' => true, 'data' => $this->products->listActive()]);
            }

            if ($method === 'POST') {
                $this->assertAdmin();
                $data = $this->readJsonBody();

                $nome = trim((string) ($data['name'] ?? ''));
                $categoria = trim((string) ($data['category'] ?? ''));
                if ($nome === '' || $categoria === '') {
                    JsonResponse::send(422, ['ok' => false, 'message' => 'Nome e categoria sao obrigatorios.']);
                }

                $preco = isset($data['price']) ? (float) $data['price'] : 0.0;
                if ($preco <= 0) {
                    JsonResponse::send(422, ['ok' => false, 'message' => 'Informe um preco valido maior que zero.']);
                }

                $isUpdate = isset($data['id']) && (int) $data['id'] > 0;
                $id = $this->products->save($data);

                if ($isUpdate) {
                    JsonResponse::send(200, ['ok' => true, 'message' => 'Produto atualizado com sucesso.', 'id' => $id]);
                }

                JsonResponse::send(201, ['ok' => true, 'message' => 'Produto cadastrado com sucesso.', 'id' => $id]);
            }

            $this->assertAdmin();
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            if ($id <= 0) {
                JsonResponse::send(422, ['ok' => false, 'message' => 'ID invalido para exclusao.']);
            }

            $this->products->deactivate($id);
            JsonResponse::send(200, ['ok' => true, 'message' => 'Produto removido com sucesso.']);
        } catch (Throwable $e) {
            JsonResponse::send(500, ['ok' => false, 'message' => 'Erro interno no servidor.']);
        }
    }

    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            JsonResponse::send(400, ['ok' => false, 'message' => 'JSON invalido.']);
        }

        return $data;
    }

    private function assertAdmin(): void
    {
        SessionAuth::start();
        if (!SessionAuth::isLoggedIn()) {
            JsonResponse::send(401, ['ok' => false, 'message' => 'Login obrigatorio.']);
        }

        if (SessionAuth::userType() !== 'admin') {
            JsonResponse::send(403, ['ok' => false, 'message' => 'Apenas administradores podem executar esta acao.']);
        }
    }
}
