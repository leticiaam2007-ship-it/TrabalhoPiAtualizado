<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\JsonResponse;
use App\Core\SessionAuth;

function apiResponder(int $statusCode, array $payload): void
{
    JsonResponse::send($statusCode, $payload);
}

function apiLerJsonBody(): array
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

function apiExigirMetodo(array $metodos): void
{
    $metodo = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if (!in_array($metodo, $metodos, true)) {
        header('Allow: ' . implode(', ', $metodos));
        JsonResponse::send(405, ['ok' => false, 'message' => 'Metodo nao permitido.']);
    }
}

function apiExigirLogin(): void
{
    SessionAuth::start();
    if (!SessionAuth::isLoggedIn()) {
        JsonResponse::send(401, ['ok' => false, 'message' => 'Login obrigatorio.']);
    }
}

function apiExigirAdmin(): void
{
    apiExigirLogin();
    if (SessionAuth::userType() !== 'admin') {
        JsonResponse::send(403, ['ok' => false, 'message' => 'Apenas administradores podem executar esta acao.']);
    }
}
