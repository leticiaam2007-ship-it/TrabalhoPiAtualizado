<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use DateTimeImmutable;
use RuntimeException;
use mysqli;
use Throwable;

final class OrderModel
{
    private mysqli $conn;
    private string $orderCreatedAtColumn;
    /** @var string[] */
    private array $orderStatusOptions;
    /** @var string[] */
    private array $paymentTypeOptions;
    private bool $paymentsTableReady;

    public function __construct()
    {
        $this->conn = Database::connection();
        $this->orderCreatedAtColumn = $this->detectCreatedAtColumn();
        $this->orderStatusOptions = $this->detectStatusOptions();
        $this->paymentTypeOptions = $this->detectPaymentTypeOptions();
        $this->paymentsTableReady = $this->detectPaymentsTableReady();
    }

    public function listRecentWithUser(int $limit = 100): array
    {
        $limit = max(1, $limit);
        $column = $this->orderCreatedAtColumn;

        $sql = "SELECT p.id, p.usuario_id, p.valor_total, p.status, p.$column AS criado_em, u.nome AS usuario_nome, u.email AS usuario_email
                FROM pedidos p
                LEFT JOIN usuarios u ON u.id = p.usuario_id
                ORDER BY p.id DESC
                LIMIT ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();

        $result = $stmt->get_result();
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = [
                'id' => (int) $row['id'],
                'usuario_id' => $row['usuario_id'] !== null ? (int) $row['usuario_id'] : null,
                'usuario_nome' => (string) ($row['usuario_nome'] ?? ''),
                'usuario_email' => (string) ($row['usuario_email'] ?? ''),
                'valor_total' => (float) $row['valor_total'],
                'status' => (string) $row['status'],
                'criado_em' => (string) $row['criado_em'],
            ];
        }

        return $orders;
    }

    public function listOpenWithItems(int $limit = 50): array
    {
        $limit = max(1, $limit);
        $openStatuses = $this->resolveOpenStatuses();
        if ($openStatuses === []) {
            return [];
        }

        $column = $this->orderCreatedAtColumn;
        $statusList = $this->quoteStringList($openStatuses);

        $sql = "SELECT p.id, p.usuario_id, p.valor_total, p.status, p.$column AS criado_em, u.nome AS usuario_nome, u.email AS usuario_email
                FROM pedidos p
                LEFT JOIN usuarios u ON u.id = p.usuario_id
                WHERE p.status IN ($statusList)
                ORDER BY p.id ASC
                LIMIT ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();

        $result = $stmt->get_result();
        $orders = [];
        $orderIds = [];

        while ($row = $result->fetch_assoc()) {
            $orderId = (int) $row['id'];
            $orderIds[] = $orderId;
            $orders[] = [
                'id' => $orderId,
                'usuario_id' => $row['usuario_id'] !== null ? (int) $row['usuario_id'] : null,
                'usuario_nome' => (string) ($row['usuario_nome'] ?? ''),
                'usuario_email' => (string) ($row['usuario_email'] ?? ''),
                'valor_total' => (float) $row['valor_total'],
                'status' => (string) $row['status'],
                'criado_em' => (string) $row['criado_em'],
                'itens' => [],
                'pagamento' => null,
            ];
        }

        if ($orderIds === []) {
            return [];
        }

        $itemsByOrder = $this->loadItemsByOrderIds($orderIds);
        $paymentsByOrder = $this->loadPaymentsByOrderIds($orderIds);
        foreach ($orders as &$order) {
            $order['itens'] = $itemsByOrder[$order['id']] ?? [];
            $order['pagamento'] = $paymentsByOrder[$order['id']] ?? null;
        }
        unset($order);

        return $orders;
    }

    public function listMineGrouped(int $userId, ?string $filter = null, int $limit = 200): array
    {
        if ($userId <= 0) {
            return [
                'pedidos_atuais' => [],
                'historico' => [],
            ];
        }

        $limit = max(1, $limit);
        $column = $this->orderCreatedAtColumn;

        $sql = "SELECT p.id, p.usuario_id, p.valor_total, p.status, p.$column AS criado_em, u.nome AS usuario_nome, u.email AS usuario_email
                FROM pedidos p
                LEFT JOIN usuarios u ON u.id = p.usuario_id
                WHERE p.usuario_id = ?
                ORDER BY p.$column DESC, p.id DESC
                LIMIT ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ii', $userId, $limit);
        $stmt->execute();

        $result = $stmt->get_result();
        $orders = [];
        $orderIds = [];

        while ($row = $result->fetch_assoc()) {
            $orderId = (int) $row['id'];
            $orderIds[] = $orderId;
            $orders[] = [
                'id' => $orderId,
                'usuario_id' => $row['usuario_id'] !== null ? (int) $row['usuario_id'] : null,
                'usuario_nome' => (string) ($row['usuario_nome'] ?? ''),
                'usuario_email' => (string) ($row['usuario_email'] ?? ''),
                'valor_total' => (float) $row['valor_total'],
                'status' => (string) $row['status'],
                'criado_em' => (string) $row['criado_em'],
                'itens' => [],
                'pagamento' => null,
            ];
        }

        if ($orderIds === []) {
            return [
                'pedidos_atuais' => [],
                'historico' => [],
            ];
        }

        $itemsByOrder = $this->loadItemsByOrderIds($orderIds);
        $paymentsByOrder = $this->loadPaymentsByOrderIds($orderIds);

        foreach ($orders as &$order) {
            $order['itens'] = $itemsByOrder[$order['id']] ?? [];
            $order['pagamento'] = $paymentsByOrder[$order['id']] ?? null;
        }
        unset($order);

        $normalizedFilter = $this->normalizeCustomerFilter($filter);
        if ($normalizedFilter !== 'todos') {
            $orders = array_values(array_filter($orders, fn (array $order): bool => $this->matchesCustomerFilter($order, $normalizedFilter)));
        }

        $historicalStatuses = $this->resolveHistoricalStatuses();
        $current = [];
        $history = [];

        foreach ($orders as $order) {
            $status = strtolower(trim((string) ($order['status'] ?? '')));
            if (in_array($status, $historicalStatuses, true)) {
                $history[] = $order;
            } else {
                $current[] = $order;
            }
        }

        return [
            'pedidos_atuais' => $current,
            'historico' => $history,
        ];
    }

    public function markAsDelivered(int $orderId): bool
    {
        if ($orderId <= 0) {
            return false;
        }

        $deliveredStatus = $this->resolveDeliveredStatus();
        $stmt = $this->conn->prepare('UPDATE pedidos SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $deliveredStatus, $orderId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            return true;
        }

        $check = $this->conn->prepare('SELECT id FROM pedidos WHERE id = ? AND status = ? LIMIT 1');
        $check->bind_param('is', $orderId, $deliveredStatus);
        $check->execute();
        $result = $check->get_result();

        return (bool) ($result && $result->fetch_assoc());
    }

    public function cancelMine(int $userId, int $orderId): bool
    {
        if ($userId <= 0 || $orderId <= 0) {
            return false;
        }

        if (!in_array('cancelado', $this->orderStatusOptions, true)) {
            return false;
        }

        $openStatuses = $this->resolveOpenStatuses();
        if ($openStatuses === []) {
            return false;
        }

        $statusList = $this->quoteStringList($openStatuses);
        $cancelledStatus = 'cancelado';

        $this->conn->begin_transaction();

        try {
            $stmt = $this->conn->prepare("UPDATE pedidos SET status = ? WHERE id = ? AND usuario_id = ? AND status IN ($statusList)");
            $stmt->bind_param('sii', $cancelledStatus, $orderId, $userId);
            $stmt->execute();

            if ($stmt->affected_rows <= 0) {
                $this->conn->rollback();
                return false;
            }

            if ($this->paymentsTableReady) {
                $paymentStatus = 'cancelado';
                $stmtPayment = $this->conn->prepare('UPDATE pagamentos SET status = ? WHERE pedido_id = ?');
                $stmtPayment->bind_param('si', $paymentStatus, $orderId);
                $stmtPayment->execute();
            }

            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            $this->conn->rollback();
            return false;
        }
    }

    public function monthlySalesReport(?string $monthRef = null, int $limit = 10): array
    {
        $limit = max(1, $limit);

        [$month, $startDate, $endDate] = $this->buildMonthRange($monthRef);
        $deliveredStatuses = $this->resolveDeliveredStatusesForReport();
        if ($deliveredStatuses === []) {
            return [
                'month' => $month,
                'items' => [],
            ];
        }

        $column = $this->orderCreatedAtColumn;
        $statusList = $this->quoteStringList($deliveredStatuses);

        $sql = "SELECT pi.nome_produto AS produto,
                       SUM(pi.quantidade) AS quantidade_vendida,
                       SUM(pi.quantidade * pi.preco_unitario) AS faturamento
                FROM pedido_itens pi
                INNER JOIN pedidos p ON p.id = pi.pedido_id
                WHERE p.status IN ($statusList)
                  AND p.$column >= ?
                  AND p.$column < ?
                GROUP BY pi.nome_produto
                ORDER BY quantidade_vendida DESC, faturamento DESC
                LIMIT ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ssi', $startDate, $endDate, $limit);
        $stmt->execute();

        $result = $stmt->get_result();
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = [
                'produto' => (string) ($row['produto'] ?? 'Sem nome'),
                'quantidade_vendida' => (int) ($row['quantidade_vendida'] ?? 0),
                'faturamento' => (float) ($row['faturamento'] ?? 0),
            ];
        }

        return [
            'month' => $month,
            'items' => $items,
        ];
    }

    public function monthlyRevenueReport(int $limit = 12): array
    {
        $limit = max(1, $limit);
        $deliveredStatuses = $this->resolveDeliveredStatusesForReport();
        if ($deliveredStatuses === []) {
            return [];
        }

        $column = $this->orderCreatedAtColumn;
        $statusList = $this->quoteStringList($deliveredStatuses);

        $sql = "SELECT DATE_FORMAT(p.$column, '%Y-%m') AS mes,
                       COUNT(*) AS total_pedidos,
                       SUM(p.valor_total) AS faturamento
                FROM pedidos p
                WHERE p.status IN ($statusList)
                GROUP BY DATE_FORMAT(p.$column, '%Y-%m')
                ORDER BY mes DESC
                LIMIT ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();

        $result = $stmt->get_result();
        $months = [];
        while ($row = $result->fetch_assoc()) {
            $months[] = [
                'mes' => (string) ($row['mes'] ?? ''),
                'total_pedidos' => (int) ($row['total_pedidos'] ?? 0),
                'faturamento' => (float) ($row['faturamento'] ?? 0),
            ];
        }

        return array_reverse($months);
    }

    public function createFromItems(int $usuarioId, array $items, ?string $paymentType = null): array
    {
        if (!$this->paymentsTableReady) {
            throw new RuntimeException('Banco sem estrutura de pagamento. Rode o arquivo sql/migrate_existing.sql.');
        }

        $resolvedPaymentType = $this->resolvePaymentType($paymentType);
        $this->conn->begin_transaction();

        try {
            $valorTotal = 0.0;
            $status = 'pendente';

            $stmtOrder = $this->conn->prepare('INSERT INTO pedidos (usuario_id, valor_total, status) VALUES (?, ?, ?)');
            $stmtOrder->bind_param('ids', $usuarioId, $valorTotal, $status);
            $stmtOrder->execute();
            $orderId = (int) $stmtOrder->insert_id;

            $stmtProduct = $this->conn->prepare('SELECT id, nome, preco FROM produtos WHERE id = ? AND ativo = 1 LIMIT 1');
            $stmtItem = $this->conn->prepare('INSERT INTO pedido_itens (pedido_id, produto_id, nome_produto, quantidade, preco_unitario, configuracao) VALUES (?, ?, ?, ?, ?, ?)');

            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $productId = (int) ($item['id_produto'] ?? 0);
                $qty = max(1, (int) ($item['quantidade'] ?? 1));
                if ($productId <= 0) {
                    continue;
                }

                $stmtProduct->bind_param('i', $productId);
                $stmtProduct->execute();
                $productResult = $stmtProduct->get_result();
                $product = $productResult ? $productResult->fetch_assoc() : null;
                if (!$product) {
                    continue;
                }

                $unitPrice = (float) ($item['preco_unitario'] ?? 0);
                if ($unitPrice <= 0) {
                    $unitPrice = (float) $product['preco'];
                }

                $name = trim((string) ($item['nome_produto'] ?? ''));
                if ($name === '') {
                    $name = (string) $product['nome'];
                }

                $configuration = null;
                if (isset($item['configuracao']) && is_array($item['configuracao'])) {
                    $configuration = json_encode($item['configuracao'], JSON_UNESCAPED_UNICODE);
                }

                $stmtItem->bind_param('iisids', $orderId, $productId, $name, $qty, $unitPrice, $configuration);
                $stmtItem->execute();

                $valorTotal += $qty * $unitPrice;
            }

            if ($valorTotal <= 0) {
                throw new RuntimeException('Nao foi possivel montar os itens do pedido.');
            }

            $stmtUpdate = $this->conn->prepare('UPDATE pedidos SET valor_total = ? WHERE id = ?');
            $stmtUpdate->bind_param('di', $valorTotal, $orderId);
            $stmtUpdate->execute();

            $paymentStatus = 'pendente';
            $stmtPayment = $this->conn->prepare('INSERT INTO pagamentos (pedido_id, tipo, status) VALUES (?, ?, ?)');
            $stmtPayment->bind_param('iss', $orderId, $resolvedPaymentType, $paymentStatus);
            $stmtPayment->execute();
            $paymentId = (int) $stmtPayment->insert_id;

            $this->conn->commit();

            return [
                'pedido_id' => $orderId,
                'valor_total' => $valorTotal,
                'pagamento_id' => $paymentId,
                'pagamento_tipo' => $resolvedPaymentType,
            ];
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    private function loadItemsByOrderIds(array $orderIds): array
    {
        if ($orderIds === []) {
            return [];
        }

        $cleanIds = array_values(array_unique(array_map(static fn ($id): int => (int) $id, $orderIds)));
        if ($cleanIds === []) {
            return [];
        }

        $idList = implode(',', $cleanIds);
        $sql = "SELECT pi.pedido_id,
                       pi.nome_produto,
                       pi.quantidade,
                       pi.preco_unitario,
                       pi.configuracao,
                       COALESCE(NULLIF(TRIM(pr.descricao), ''), '') AS descricao_produto
                FROM pedido_itens pi
                LEFT JOIN produtos pr ON pr.id = pi.produto_id
                WHERE pi.pedido_id IN ($idList)
                ORDER BY pi.id ASC";

        $result = $this->conn->query($sql);
        $itemsByOrder = [];

        while ($row = $result->fetch_assoc()) {
            $orderId = (int) $row['pedido_id'];
            if (!isset($itemsByOrder[$orderId])) {
                $itemsByOrder[$orderId] = [];
            }

            $configuration = null;
            $rawConfig = (string) ($row['configuracao'] ?? '');
            if ($rawConfig !== '') {
                $decoded = json_decode($rawConfig, true);
                if (is_array($decoded)) {
                    $configuration = $decoded;
                }
            }

            $qty = (int) ($row['quantidade'] ?? 0);
            $unit = (float) ($row['preco_unitario'] ?? 0);

            $itemsByOrder[$orderId][] = [
                'nome' => (string) ($row['nome_produto'] ?? 'Item'),
                'descricao' => (string) ($row['descricao_produto'] ?? ''),
                'quantidade' => $qty,
                'preco_unitario' => $unit,
                'subtotal' => $qty * $unit,
                'configuracao' => $configuration,
            ];
        }

        return $itemsByOrder;
    }

    private function loadPaymentsByOrderIds(array $orderIds): array
    {
        if ($orderIds === [] || !$this->paymentsTableReady) {
            return [];
        }

        $cleanIds = array_values(array_unique(array_map(static fn ($id): int => (int) $id, $orderIds)));
        if ($cleanIds === []) {
            return [];
        }

        $idList = implode(',', $cleanIds);
        $sql = "SELECT pg.pedido_id, pg.tipo, pg.status
                FROM pagamentos pg
                INNER JOIN (
                  SELECT pedido_id, MAX(id) AS max_id
                  FROM pagamentos
                  WHERE pedido_id IN ($idList)
                  GROUP BY pedido_id
                ) latest ON latest.max_id = pg.id";

        $result = $this->conn->query($sql);
        if (!$result) {
            return [];
        }

        $paymentsByOrder = [];
        while ($row = $result->fetch_assoc()) {
            $orderId = (int) ($row['pedido_id'] ?? 0);
            if ($orderId <= 0) {
                continue;
            }

            $paymentsByOrder[$orderId] = [
                'tipo' => (string) ($row['tipo'] ?? ''),
                'status' => (string) ($row['status'] ?? ''),
            ];
        }

        return $paymentsByOrder;
    }

    private function buildMonthRange(?string $monthRef): array
    {
        $monthRef = trim((string) $monthRef);

        $date = null;
        if ($monthRef !== '' && preg_match('/^\d{4}-\d{2}$/', $monthRef) === 1) {
            $date = DateTimeImmutable::createFromFormat('!Y-m', $monthRef) ?: null;
        }

        if (!$date) {
            $date = new DateTimeImmutable('first day of this month 00:00:00');
        }

        $start = $date->format('Y-m-01 00:00:00');
        $end = $date->modify('+1 month')->format('Y-m-01 00:00:00');

        return [$date->format('Y-m'), $start, $end];
    }

    /**
     * @return string[]
     */
    private function resolveOpenStatuses(): array
    {
        $delivered = $this->resolveDeliveredStatus();
        $blacklist = [$delivered, 'cancelado'];

        $open = [];
        foreach ($this->orderStatusOptions as $status) {
            if (!in_array($status, $blacklist, true)) {
                $open[] = $status;
            }
        }

        return $open;
    }

    /**
     * @return string[]
     */
    private function resolveDeliveredStatusesForReport(): array
    {
        $candidates = ['finalizado', 'pronto', 'entregue'];
        $values = [];

        foreach ($candidates as $candidate) {
            if (in_array($candidate, $this->orderStatusOptions, true)) {
                $values[] = $candidate;
            }
        }

        if ($values === []) {
            $values[] = $this->resolveDeliveredStatus();
        }

        return array_values(array_unique($values));
    }

    private function resolveDeliveredStatus(): string
    {
        foreach (['finalizado', 'pronto', 'entregue'] as $candidate) {
            if (in_array($candidate, $this->orderStatusOptions, true)) {
                return $candidate;
            }
        }

        if ($this->orderStatusOptions !== []) {
            return $this->orderStatusOptions[count($this->orderStatusOptions) - 1];
        }

        return 'finalizado';
    }

    /**
     * @return string[]
     */
    private function resolveHistoricalStatuses(): array
    {
        $values = ['cancelado', 'entregue', 'finalizado', $this->resolveDeliveredStatus()];
        $clean = [];

        foreach ($values as $value) {
            $status = strtolower(trim((string) $value));
            if ($status !== '') {
                $clean[] = $status;
            }
        }

        return array_values(array_unique($clean));
    }

    private function normalizeCustomerFilter(?string $filter): string
    {
        $value = strtolower(trim((string) $filter));
        if ($value === '') {
            return 'todos';
        }

        $allowed = ['todos', 'pendentes', 'entregues', 'cancelados', 'pagos', 'nao_pagos'];
        if (!in_array($value, $allowed, true)) {
            return 'todos';
        }

        return $value;
    }

    private function matchesCustomerFilter(array $order, string $filter): bool
    {
        $status = strtolower(trim((string) ($order['status'] ?? '')));
        $paymentStatus = strtolower(trim((string) ($order['pagamento']['status'] ?? '')));
        $historicalStatuses = $this->resolveHistoricalStatuses();
        $deliveredStatuses = array_values(array_unique(['finalizado', 'entregue', $this->resolveDeliveredStatus()]));

        if ($filter === 'pendentes') {
            return !in_array($status, $historicalStatuses, true);
        }

        if ($filter === 'entregues') {
            return in_array($status, $deliveredStatuses, true);
        }

        if ($filter === 'cancelados') {
            return $status === 'cancelado';
        }

        if ($filter === 'pagos') {
            return $paymentStatus === 'pago';
        }

        if ($filter === 'nao_pagos') {
            return $paymentStatus !== 'pago';
        }

        return true;
    }

    private function quoteStringList(array $values): string
    {
        if ($values === []) {
            return "''";
        }

        $parts = [];
        foreach ($values as $value) {
            $parts[] = "'" . $this->conn->real_escape_string((string) $value) . "'";
        }

        return implode(', ', $parts);
    }

    /**
     * @return string[]
     */
    private function detectStatusOptions(): array
    {
        $result = $this->conn->query("SHOW COLUMNS FROM pedidos LIKE 'status'");
        if (!$result) {
            return ['pendente', 'preparando', 'finalizado', 'cancelado'];
        }

        $row = $result->fetch_assoc();
        if (!$row) {
            return ['pendente', 'preparando', 'finalizado', 'cancelado'];
        }

        $type = (string) ($row['Type'] ?? $row['type'] ?? '');
        if ($type === '') {
            return ['pendente', 'preparando', 'finalizado', 'cancelado'];
        }

        if (preg_match('/^enum\((.*)\)$/i', $type, $matches) !== 1) {
            return ['pendente', 'preparando', 'finalizado', 'cancelado'];
        }

        $inner = $matches[1];
        $values = str_getcsv($inner, ',', "'", '\\');
        $clean = [];
        foreach ($values as $value) {
            $v = trim((string) $value);
            if ($v !== '') {
                $clean[] = $v;
            }
        }

        if ($clean === []) {
            return ['pendente', 'preparando', 'finalizado', 'cancelado'];
        }

        return $clean;
    }

    private function detectCreatedAtColumn(): string
    {
        $result = $this->conn->query("SHOW COLUMNS FROM pedidos LIKE 'criado_em'");
        if ($result && $result->fetch_assoc()) {
            return 'criado_em';
        }

        return 'data_pedido';
    }

    private function resolvePaymentType(?string $paymentType): string
    {
        $paymentType = strtolower(trim((string) $paymentType));
        if ($paymentType === '') {
            return 'dinheiro';
        }

        if (!in_array($paymentType, $this->paymentTypeOptions, true)) {
            throw new RuntimeException('Forma de pagamento invalida.');
        }

        return $paymentType;
    }

    private function detectPaymentsTableReady(): bool
    {
        $tableResult = $this->conn->query("SHOW TABLES LIKE 'pagamentos'");
        if (!$tableResult || !$tableResult->fetch_row()) {
            return false;
        }

        $required = ['pedido_id', 'tipo', 'status'];
        foreach ($required as $column) {
            $safeColumn = $this->conn->real_escape_string($column);
            $columnResult = $this->conn->query("SHOW COLUMNS FROM pagamentos LIKE '{$safeColumn}'");
            if (!$columnResult || !$columnResult->fetch_assoc()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string[]
     */
    private function detectPaymentTypeOptions(): array
    {
        try {
            $tableResult = $this->conn->query("SHOW TABLES LIKE 'pagamentos'");
            if (!$tableResult || !$tableResult->fetch_row()) {
                return ['dinheiro', 'cartao', 'pix'];
            }
        } catch (Throwable $e) {
            return ['dinheiro', 'cartao', 'pix'];
        }

        try {
            $result = $this->conn->query("SHOW COLUMNS FROM pagamentos LIKE 'tipo'");
            if (!$result) {
                return ['dinheiro', 'cartao', 'pix'];
            }
        } catch (Throwable $e) {
            return ['dinheiro', 'cartao', 'pix'];
        }

        $row = $result->fetch_assoc();
        if (!$row) {
            return ['dinheiro', 'cartao', 'pix'];
        }

        $type = (string) ($row['Type'] ?? $row['type'] ?? '');
        if ($type === '') {
            return ['dinheiro', 'cartao', 'pix'];
        }

        if (preg_match('/^enum\((.*)\)$/i', $type, $matches) !== 1) {
            return ['dinheiro', 'cartao', 'pix'];
        }

        $inner = $matches[1];
        $values = str_getcsv($inner, ',', "'", '\\');
        $clean = [];
        foreach ($values as $value) {
            $v = trim((string) $value);
            if ($v !== '') {
                $clean[] = strtolower($v);
            }
        }

        if ($clean === []) {
            return ['dinheiro', 'cartao', 'pix'];
        }

        return array_values(array_unique($clean));
    }
}
