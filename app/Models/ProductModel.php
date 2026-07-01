<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use mysqli;
use RuntimeException;

final class ProductModel
{
    private mysqli $conn;

    public function __construct()
    {
        $this->conn = Database::connection();
    }

    public function listActive(): array
    {
        $sql = 'SELECT id, nome, categoria, preco, descricao, imagem, is_marmita, marmita_config FROM produtos WHERE ativo = 1 ORDER BY categoria, nome';
        $result = $this->conn->query($sql);

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = self::normalizeRow($row);
        }

        return $products;
    }

    public function findActiveById(int $id): ?array
    {
        $stmt = $this->conn->prepare('SELECT id, nome, categoria, preco, descricao, imagem, is_marmita, marmita_config FROM produtos WHERE id = ? AND ativo = 1 LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;

        return $row ? self::normalizeRow($row) : null;
    }

    public function save(array $payload): int
    {
        $id = isset($payload['id']) ? (int) $payload['id'] : 0;
        $nome = trim((string) ($payload['name'] ?? ''));
        $categoria = trim((string) ($payload['category'] ?? ''));
        $preco = (float) ($payload['price'] ?? 0);
        $descricao = trim((string) ($payload['desc'] ?? ''));
        $imagem = trim((string) ($payload['image'] ?? ''));
        $isMarmita = !empty($payload['isMarmita']) ? 1 : 0;
        $marmitaConfig = null;

        if ($nome === '' || $categoria === '') {
            throw new RuntimeException('Nome e categoria sao obrigatorios.');
        }

        if ($preco <= 0) {
            throw new RuntimeException('Informe um preco valido maior que zero.');
        }

        if (isset($payload['marmitaConfig']) && is_array($payload['marmitaConfig'])) {
            $marmitaConfig = json_encode($payload['marmitaConfig'], JSON_UNESCAPED_UNICODE);
        }

        if ($id > 0) {
            $stmt = $this->conn->prepare('UPDATE produtos SET nome = ?, categoria = ?, preco = ?, descricao = ?, imagem = ?, is_marmita = ?, marmita_config = ?, ativo = 1 WHERE id = ?');
            $stmt->bind_param('ssdssisi', $nome, $categoria, $preco, $descricao, $imagem, $isMarmita, $marmitaConfig, $id);
            $stmt->execute();
            return $id;
        }

        $stmt = $this->conn->prepare('INSERT INTO produtos (nome, categoria, preco, descricao, imagem, is_marmita, marmita_config, ativo) VALUES (?, ?, ?, ?, ?, ?, ?, 1)');
        $stmt->bind_param('ssdssis', $nome, $categoria, $preco, $descricao, $imagem, $isMarmita, $marmitaConfig);
        $stmt->execute();

        return (int) $stmt->insert_id;
    }

    public function deactivate(int $id): void
    {
        $stmt = $this->conn->prepare('UPDATE produtos SET ativo = 0 WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
    }

    private static function normalizeRow(array $row): array
    {
        $config = null;
        if (!empty($row['marmita_config'])) {
            $json = json_decode((string) $row['marmita_config'], true);
            if (is_array($json)) {
                $config = $json;
            }
        }

        return [
            'id' => (string) $row['id'],
            'name' => (string) $row['nome'],
            'desc' => (string) ($row['descricao'] ?? ''),
            'price' => (float) $row['preco'],
            'category' => (string) $row['categoria'],
            'image' => (string) ($row['imagem'] ?? ''),
            'isMarmita' => ((int) $row['is_marmita']) === 1,
            'marmitaConfig' => $config,
        ];
    }
}
