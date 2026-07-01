<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\JsonResponse;
use App\Core\SessionAuth;
use App\Models\OrderModel;
use RuntimeException;
use Throwable;

final class OrderApiController
{
    private OrderModel $orders;

    public function __construct()
    {
        $this->orders = new OrderModel();
    }

    public function handle(): void
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (!in_array($method, ['GET', 'POST'], true)) {
            header('Allow: GET, POST');
            JsonResponse::send(405, ['ok' => false, 'message' => 'Metodo nao permitido.']);
        }

        try {
            if ($method === 'GET') {
                $this->handleGet();
            }

            $action = strtolower(trim((string) ($_GET['action'] ?? '')));
            if (in_array($action, ['deliver', 'entregar', 'entregue'], true)) {
                $this->handleDeliver();
            }

            if (in_array($action, ['cancel', 'cancelar', 'cancelar_pedido'], true)) {
                $this->handleCancel();
            }

            $this->handleCreateOrder();
        } catch (Throwable $e) {
            JsonResponse::send(500, ['ok' => false, 'message' => 'Erro interno ao processar pedido.']);
        }
    }

    private function handleGet(): void
    {
        $action = strtolower(trim((string) ($_GET['action'] ?? '')));
        if ($action === 'meus_pedidos') {
            $this->assertLoggedIn();
            $userId = SessionAuth::userId();
            if ($userId === null) {
                JsonResponse::send(401, ['ok' => false, 'message' => 'Sessao invalida.']);
            }

            $filtro = trim((string) ($_GET['filtro'] ?? 'todos'));
            JsonResponse::send(200, [
                'ok' => true,
                'data' => $this->orders->listMineGrouped($userId, $filtro, 300),
            ]);
        }

        $this->assertAdmin();

        if ($action === 'dashboard') {
            $month = trim((string) ($_GET['month'] ?? ''));
            JsonResponse::send(200, [
                'ok' => true,
                'data' => [
                    'pedidos' => $this->orders->listOpenWithItems(100),
                    'relatorio' => $this->orders->monthlySalesReport($month !== '' ? $month : null, 12),
                    'faturamento_mensal' => $this->orders->monthlyRevenueReport(12),
                ],
            ]);
        }

        JsonResponse::send(200, ['ok' => true, 'data' => $this->orders->listRecentWithUser(100)]);
    }

    private function handleDeliver(): void
    {
        $this->assertAdmin();
        $data = $this->readJsonBody();
        $orderId = (int) ($data['pedido_id'] ?? $data['id_pedido'] ?? 0);

        if ($orderId <= 0) {
            JsonResponse::send(422, ['ok' => false, 'message' => 'Pedido invalido.']);
        }

        if (!$this->orders->markAsDelivered($orderId)) {
            JsonResponse::send(404, ['ok' => false, 'message' => 'Pedido nao encontrado.']);
        }

        JsonResponse::send(200, ['ok' => true, 'message' => 'Pedido marcado como entregue.']);
    }

    private function handleCancel(): void
    {
        $this->assertLoggedIn();
        $data = $this->readJsonBody();
        $orderId = (int) ($data['pedido_id'] ?? $data['id_pedido'] ?? 0);

        if ($orderId <= 0) {
            JsonResponse::send(422, ['ok' => false, 'message' => 'Pedido invalido.']);
        }

        $userId = SessionAuth::userId();
        if ($userId === null) {
            JsonResponse::send(401, ['ok' => false, 'message' => 'Sessao invalida.']);
        }

        if (!$this->orders->cancelMine($userId, $orderId)) {
            JsonResponse::send(409, ['ok' => false, 'message' => 'Pedido nao encontrado ou ja entregue.']);
        }

        JsonResponse::send(200, ['ok' => true, 'message' => 'Pedido cancelado com sucesso.']);
    }

    private function handleCreateOrder(): void
    {
        $this->assertLoggedIn();
        $data = $this->readJsonBody();
        $items = $data['itens'] ?? [];
        $payment = $data['pagamento'] ?? null;
        $paymentType = null;

        if (!is_array($items) || count($items) === 0) {
            JsonResponse::send(422, ['ok' => false, 'message' => 'Pedido sem itens.']);
        }

        if ($payment !== null) {
            if (!is_array($payment)) {
                JsonResponse::send(422, ['ok' => false, 'message' => 'Dados de pagamento invalidos.']);
            }
            $paymentType = isset($payment['tipo']) ? trim((string) $payment['tipo']) : null;
        }

        $userId = SessionAuth::userId();
        if ($userId === null) {
            JsonResponse::send(401, ['ok' => false, 'message' => 'Sessao invalida.']);
        }

        try {
            $result = $this->orders->createFromItems($userId, $items, $paymentType);
        } catch (RuntimeException $e) {
            JsonResponse::send(422, ['ok' => false, 'message' => $e->getMessage()]);
        }

        JsonResponse::send(201, [
            'ok' => true,
            'message' => 'Pedido registrado com sucesso.',
            'data' => $result,
        ]);
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

    private function assertLoggedIn(): void
    {
        SessionAuth::start();
        if (!SessionAuth::isLoggedIn()) {
            JsonResponse::send(401, ['ok' => false, 'message' => 'Login obrigatorio.']);
        }
    }

    private function assertAdmin(): void
    {
        $this->assertLoggedIn();
        if (SessionAuth::userType() !== 'admin') {
            JsonResponse::send(403, ['ok' => false, 'message' => 'Apenas administradores podem executar esta acao.']);
        }
    }
}
