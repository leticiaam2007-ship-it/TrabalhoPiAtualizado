<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\SessionAuth;
use App\Models\OrderModel;
use App\Models\UserModel;
use RuntimeException;
use Throwable;

final class PerfilController extends Controller
{
    private UserModel $users;
    private OrderModel $orders;

    public function __construct()
    {
        $this->users = new UserModel();
        $this->orders = new OrderModel();
    }

    public function index(): void
    {
        SessionAuth::requireLogin('login.php?erro=login&next=' . rawurlencode('perfil.php'));
        SessionAuth::start();

        $userId = SessionAuth::userId();
        if ($userId === null || $userId <= 0) {
            $this->redirect('login.php?erro=login&next=' . rawurlencode('perfil.php'));
        }

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            $this->handlePost($userId);
        }

        $profile = $this->users->findProfileById($userId);
        if ($profile === null) {
            $profile = [
                'id' => $userId,
                'nome' => (string) ($_SESSION['nome'] ?? 'Usuario'),
                'email' => (string) ($_SESSION['email'] ?? ''),
                'tipo' => (string) ($_SESSION['tipo'] ?? 'cliente'),
                'nick' => '',
                'login' => '',
                'data_nascimento' => '',
                'sexo' => '',
                'endereco' => '',
                'foto' => '',
                'descricao' => '',
            ];
        }

        $groupedOrders = $this->orders->listMineGrouped($userId, 'todos', 300);
        $currentOrders = is_array($groupedOrders['pedidos_atuais'] ?? null) ? $groupedOrders['pedidos_atuais'] : [];
        $historyOrders = is_array($groupedOrders['historico'] ?? null) ? $groupedOrders['historico'] : [];

        $allOrders = array_merge($currentOrders, $historyOrders);
        usort($allOrders, function (array $a, array $b): int {
            return strcmp((string) ($b['criado_em'] ?? ''), (string) ($a['criado_em'] ?? ''));
        });

        $this->render('perfil/index', [
            'flash' => $this->consumeFlash(),
            'perfil' => $profile,
            'tipoUsuarioLabel' => $this->roleLabel((string) ($profile['tipo'] ?? 'cliente')),
            'pedidosFeitos' => $this->mapOrdersToCards(array_slice($allOrders, 0, 8)),
            'pedidosPendentes' => $this->mapOrdersToCards(array_slice($currentOrders, 0, 8)),
        ]);
    }

    private function handlePost(int $userId): void
    {
        $action = strtolower(trim((string) ($_POST['acao'] ?? '')));

        try {
            if ($action === 'salvar_dados') {
                $this->users->updateProfileById($userId, [
                    'nome' => (string) ($_POST['nome'] ?? ''),
                    'email' => (string) ($_POST['email'] ?? ''),
                    'nick' => (string) ($_POST['nick'] ?? ''),
                    'login' => (string) ($_POST['login'] ?? ''),
                    'endereco' => (string) ($_POST['endereco'] ?? ''),
                ]);

                $_SESSION['nome'] = trim((string) ($_POST['nome'] ?? ''));
                $_SESSION['email'] = trim((string) ($_POST['email'] ?? ''));
                $this->setFlash('success', 'Dados pessoais atualizados com sucesso.');
                $this->redirect('perfil.php');
            }

            if ($action === 'alterar_senha') {
                $senhaAtual = (string) ($_POST['senha_atual'] ?? '');
                $senhaNova = (string) ($_POST['senha_nova'] ?? '');
                $senhaConfirmacao = (string) ($_POST['senha_confirmacao'] ?? '');

                if ($senhaNova !== $senhaConfirmacao) {
                    throw new RuntimeException('A confirmacao da nova senha nao confere.');
                }

                $this->users->updatePasswordById($userId, $senhaAtual, $senhaNova);
                $this->setFlash('success', 'Senha alterada com sucesso.');
                $this->redirect('perfil.php?aba=senha');
            }

            if ($action === 'alterar_foto') {
                $file = $_FILES['foto'] ?? null;
                if (!is_array($file)) {
                    throw new RuntimeException('Selecione uma imagem para atualizar a foto.');
                }

                $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
                if ($errorCode !== UPLOAD_ERR_OK) {
                    if ($errorCode === UPLOAD_ERR_NO_FILE) {
                        throw new RuntimeException('Selecione uma imagem para atualizar a foto.');
                    }

                    throw new RuntimeException('Falha no upload da foto. Tente novamente.');
                }

                $tmpPath = (string) ($file['tmp_name'] ?? '');
                $size = (int) ($file['size'] ?? 0);
                if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
                    throw new RuntimeException('Arquivo de foto invalido.');
                }

                if ($size <= 0 || $size > (3 * 1024 * 1024)) {
                    throw new RuntimeException('A foto deve ter no maximo 3MB.');
                }

                $mime = (string) mime_content_type($tmpPath);
                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                ];
                if (!isset($allowed[$mime])) {
                    throw new RuntimeException('Formato invalido. Use JPG, PNG ou WEBP.');
                }

                $dir = BASE_PATH . DIRECTORY_SEPARATOR . 'imagens' . DIRECTORY_SEPARATOR . 'perfis';
                if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
                    throw new RuntimeException('Nao foi possivel criar pasta para fotos.');
                }

                $fileName = 'perfil_' . $userId . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
                $absolutePath = $dir . DIRECTORY_SEPARATOR . $fileName;
                if (!move_uploaded_file($tmpPath, $absolutePath)) {
                    throw new RuntimeException('Nao foi possivel salvar a foto no servidor.');
                }

                $relativePath = 'imagens/perfis/' . $fileName;
                $oldProfile = $this->users->findProfileById($userId);
                $oldPhoto = is_array($oldProfile) ? trim((string) ($oldProfile['foto'] ?? '')) : '';

                $this->users->updatePhotoById($userId, $relativePath);
                $_SESSION['foto'] = $relativePath;
                if ($oldPhoto !== '' && str_starts_with($oldPhoto, 'imagens/perfis/') && $oldPhoto !== $relativePath) {
                    $oldAbsolute = BASE_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $oldPhoto);
                    if (is_file($oldAbsolute)) {
                        @unlink($oldAbsolute);
                    }
                }

                $this->setFlash('success', 'Foto atualizada com sucesso.');
                $this->redirect('perfil.php?aba=foto');
            }

            throw new RuntimeException('Acao invalida para a tela de perfil.');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
            $targetTab = $action === 'alterar_senha' ? 'senha' : ($action === 'alterar_foto' ? 'foto' : 'dados');
            $this->redirect('perfil.php?aba=' . $targetTab);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $orders
     * @return array<int, array<string, mixed>>
     */
    private function mapOrdersToCards(array $orders): array
    {
        return array_map(fn (array $order): array => [
            'id' => (int) ($order['id'] ?? 0),
            'produto' => $this->extractMainItemName($order),
            'descricao' => $this->extractMainItemDescription($order),
            'status' => $this->statusLabel((string) ($order['status'] ?? '')),
            'valor_total' => $this->formatMoney((float) ($order['valor_total'] ?? 0)),
            'data' => $this->formatDateTime((string) ($order['criado_em'] ?? '')),
            'forma_pagamento' => $this->paymentTypeLabel((string) ($order['pagamento']['tipo'] ?? '')),
        ], $orders);
    }

    private function extractMainItemName(array $order): string
    {
        $items = is_array($order['itens'] ?? null) ? $order['itens'] : [];
        if ($items === []) {
            return 'Pedido sem itens';
        }

        $first = $items[0];
        return (string) ($first['nome'] ?? 'Item');
    }

    private function extractMainItemDescription(array $order): string
    {
        $items = is_array($order['itens'] ?? null) ? $order['itens'] : [];
        if ($items === []) {
            return '';
        }

        $first = $items[0];
        $descricaoProduto = trim((string) ($first['descricao'] ?? ''));
        if ($descricaoProduto !== '') {
            return $descricaoProduto;
        }

        $qty = max(1, (int) ($first['quantidade'] ?? 1));
        $config = $first['configuracao'] ?? null;
        if (!is_array($config)) {
            return $qty . ' unidade(s)';
        }

        $parts = [];
        if (!empty($config['tamanho'])) {
            $parts[] = 'Tam: ' . (string) $config['tamanho'];
        }
        if (!empty($config['carbo'])) {
            $parts[] = 'Carbo: ' . (string) $config['carbo'];
        }

        return $qty . ' unidade(s)' . ($parts !== [] ? ' - ' . implode(' | ', $parts) : '');
    }

    private function statusLabel(string $status): string
    {
        $key = strtolower(trim($status));
        return match ($key) {
            'pendente' => 'Aguardando pagamento',
            'preparando' => 'Em analise',
            'pronto' => 'Em processamento',
            'finalizado', 'entregue' => 'Entregue',
            'cancelado' => 'Cancelado',
            'agendado' => 'Agendado',
            'concluido' => 'Concluido',
            default => ucfirst($key !== '' ? $key : 'Sem status'),
        };
    }

    private function paymentTypeLabel(string $type): string
    {
        $type = strtolower(trim($type));
        return match ($type) {
            'pix' => 'Pix',
            'cartao' => 'Cartao',
            'dinheiro' => 'Dinheiro',
            default => 'Nao informado',
        };
    }

    private function formatMoney(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }

    private function formatDateTime(string $value): string
    {
        $raw = trim($value);
        if ($raw === '') {
            return 'Data nao informada';
        }

        $timestamp = strtotime($raw);
        if ($timestamp === false) {
            return $raw;
        }

        return date('d/m/Y - H:i', $timestamp);
    }

    private function roleLabel(string $role): string
    {
        $role = strtolower(trim($role));
        return match ($role) {
            'admin' => 'Administrador',
            'aluno' => 'Aluno',
            'funcionario' => 'Funcionario',
            'cliente' => 'Usuario',
            default => ucfirst($role !== '' ? $role : 'Usuario'),
        };
    }

    private function setFlash(string $type, string $message): void
    {
        $_SESSION['perfil_flash'] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    /**
     * @return array<string, string>|null
     */
    private function consumeFlash(): ?array
    {
        $flash = $_SESSION['perfil_flash'] ?? null;
        if (!is_array($flash)) {
            return null;
        }

        unset($_SESSION['perfil_flash']);

        return [
            'type' => (string) ($flash['type'] ?? 'success'),
            'message' => (string) ($flash['message'] ?? ''),
        ];
    }
}
