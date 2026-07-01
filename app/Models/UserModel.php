<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use mysqli;
use RuntimeException;

final class UserModel
{
    private mysqli $conn;
    /** @var array<string, bool> */
    private array $userColumns = [];
    private bool $scheduledOrdersReady = false;

    public function __construct()
    {
        $this->conn = Database::connection();
        $this->userColumns = $this->detectUserColumns();
        $this->scheduledOrdersReady = $this->detectScheduledOrdersReady();
    }

    public function findByEmail(string $email): ?array
    {
        $fields = ['id', 'nome', 'email', 'senha', 'tipo'];
        if ($this->hasUserColumn('foto')) {
            $fields[] = 'foto';
        }

        $sql = 'SELECT ' . implode(', ', array_map(static fn (string $field): string => "`{$field}`", $fields)) . ' FROM usuarios WHERE email = ? LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result ? $result->fetch_assoc() : null;

        return $user ?: null;
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->conn->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();

        $result = $stmt->get_result();
        return (bool) ($result && $result->fetch_assoc());
    }

    public function create(string $nome, string $email, string $senhaHash): int
    {
        $nome = trim($nome);
        $email = trim($email);

        if ($nome === '' || $email === '') {
            throw new RuntimeException('Nome e email sao obrigatorios.');
        }

        if ($this->hasUserColumn('login')) {
            $loginPadrao = $this->buildDefaultLoginFromEmail($email);
            $stmt = $this->conn->prepare('INSERT INTO usuarios (nome, email, senha, login) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssss', $nome, $email, $senhaHash, $loginPadrao);
        } else {
            $stmt = $this->conn->prepare('INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $nome, $email, $senhaHash);
        }
        $stmt->execute();

        return (int) $stmt->insert_id;
    }

    public function findProfileById(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        $fields = ['id', 'nome', 'email', 'tipo'];
        foreach (['nick', 'login', 'data_nascimento', 'sexo', 'endereco', 'foto', 'descricao'] as $column) {
            if ($this->hasUserColumn($column)) {
                $fields[] = $column;
            }
        }

        $sql = 'SELECT ' . implode(', ', array_map(static fn (string $field): string => "`{$field}`", $fields)) . ' FROM usuarios WHERE id = ? LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        if (!$row) {
            return null;
        }

        return [
            'id' => (int) ($row['id'] ?? 0),
            'nome' => (string) ($row['nome'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'tipo' => (string) ($row['tipo'] ?? 'cliente'),
            'nick' => (string) ($row['nick'] ?? $row['login'] ?? ''),
            'login' => (string) ($row['login'] ?? ''),
            'data_nascimento' => (string) ($row['data_nascimento'] ?? ''),
            'sexo' => (string) ($row['sexo'] ?? ''),
            'endereco' => (string) ($row['endereco'] ?? ''),
            'foto' => (string) ($row['foto'] ?? ''),
            'descricao' => (string) ($row['descricao'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function updateProfileById(int $userId, array $payload): void
    {
        if ($userId <= 0) {
            throw new RuntimeException('Usuario invalido.');
        }

        $nome = trim((string) ($payload['nome'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $nick = trim((string) ($payload['nick'] ?? ''));
        $login = trim((string) ($payload['login'] ?? ''));
        $dataNascimento = trim((string) ($payload['data_nascimento'] ?? ''));
        $sexo = trim((string) ($payload['sexo'] ?? ''));
        $endereco = trim((string) ($payload['endereco'] ?? ''));
        $descricao = trim((string) ($payload['descricao'] ?? ''));

        if ($nome === '' || mb_strlen($nome) > 120) {
            throw new RuntimeException('Informe um nome valido (maximo 120 caracteres).');
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Informe um email valido.');
        }

        if ($this->emailExistsForOther($email, $userId)) {
            throw new RuntimeException('Este email ja esta em uso por outra conta.');
        }

        if ($nick !== '' && mb_strlen($nick) > 60) {
            throw new RuntimeException('Nick muito longo (maximo 60 caracteres).');
        }

        if ($login !== '' && mb_strlen($login) > 40) {
            throw new RuntimeException('Login muito longo (maximo 40 caracteres).');
        }

        if ($dataNascimento !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataNascimento) !== 1) {
            throw new RuntimeException('Data de nascimento invalida.');
        }

        if ($sexo !== '' && !in_array(mb_strtolower($sexo), ['masculino', 'feminino', 'outro', 'nao_informado'], true)) {
            throw new RuntimeException('Sexo invalido. Use Masculino, Feminino, Outro ou Nao informado.');
        }

        if ($endereco !== '' && mb_strlen($endereco) > 255) {
            throw new RuntimeException('Endereco muito longo (maximo 255 caracteres).');
        }

        if ($descricao !== '' && mb_strlen($descricao) > 500) {
            throw new RuntimeException('Descricao muito longa (maximo 500 caracteres).');
        }

        $columns = [];
        $values = [];
        $types = '';

        $add = static function (string $column, mixed $value, string $type, array &$columns, array &$values, string &$types): void {
            $columns[] = "`{$column}` = ?";
            $values[] = $value;
            $types .= $type;
        };

        $add('nome', $nome, 's', $columns, $values, $types);
        $add('email', $email, 's', $columns, $values, $types);

        if ($this->hasUserColumn('nick')) {
            $add('nick', $nick, 's', $columns, $values, $types);
        }
        if ($this->hasUserColumn('login')) {
            $add('login', $login, 's', $columns, $values, $types);
        }
        if ($this->hasUserColumn('data_nascimento')) {
            $add('data_nascimento', $dataNascimento !== '' ? $dataNascimento : null, 's', $columns, $values, $types);
        }
        if ($this->hasUserColumn('sexo')) {
            $add('sexo', $sexo, 's', $columns, $values, $types);
        }
        if ($this->hasUserColumn('endereco')) {
            $add('endereco', $endereco, 's', $columns, $values, $types);
        }
        if ($this->hasUserColumn('descricao')) {
            $add('descricao', $descricao, 's', $columns, $values, $types);
        }

        if ($columns === []) {
            return;
        }

        $sql = 'UPDATE usuarios SET ' . implode(', ', $columns) . ' WHERE id = ? LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $values[] = $userId;
        $types .= 'i';
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
    }

    public function updatePasswordById(int $userId, string $currentPassword, string $newPassword): void
    {
        if ($userId <= 0) {
            throw new RuntimeException('Usuario invalido.');
        }

        if (mb_strlen($newPassword) < 8) {
            throw new RuntimeException('A nova senha deve ter pelo menos 8 caracteres.');
        }

        $stmt = $this->conn->prepare('SELECT senha FROM usuarios WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $userId);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        if (!$row) {
            throw new RuntimeException('Usuario nao encontrado.');
        }

        $hashAtual = (string) ($row['senha'] ?? '');
        if (!password_verify($currentPassword, $hashAtual)) {
            throw new RuntimeException('Senha atual incorreta.');
        }

        $novoHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $update = $this->conn->prepare('UPDATE usuarios SET senha = ? WHERE id = ? LIMIT 1');
        $update->bind_param('si', $novoHash, $userId);
        $update->execute();
    }

    public function updatePhotoById(int $userId, string $photoPath): void
    {
        if (!$this->hasUserColumn('foto')) {
            throw new RuntimeException('Campo de foto nao disponivel no banco.');
        }

        $stmt = $this->conn->prepare('UPDATE usuarios SET foto = ? WHERE id = ? LIMIT 1');
        $stmt->bind_param('si', $photoPath, $userId);
        $stmt->execute();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listScheduledOrdersByUser(int $userId, int $limit = 5): array
    {
        if ($userId <= 0 || !$this->scheduledOrdersReady) {
            return [];
        }

        $limit = max(1, $limit);
        $sql = 'SELECT id, produto_nome, descricao, data_agendada, valor_total, status
                FROM pedidos_agendados
                WHERE usuario_id = ?
                ORDER BY data_agendada ASC
                LIMIT ?';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ii', $userId, $limit);
        $stmt->execute();

        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = [
                'id' => (int) ($row['id'] ?? 0),
                'produto_nome' => (string) ($row['produto_nome'] ?? 'Pedido agendado'),
                'descricao' => (string) ($row['descricao'] ?? ''),
                'data_agendada' => (string) ($row['data_agendada'] ?? ''),
                'valor_total' => (float) ($row['valor_total'] ?? 0),
                'status' => (string) ($row['status'] ?? 'agendado'),
            ];
        }

        return $rows;
    }

    public function hasScheduledOrdersSupport(): bool
    {
        return $this->scheduledOrdersReady;
    }

    /**
     * @return array<string, bool>
     */
    private function detectUserColumns(): array
    {
        $result = $this->conn->query('SHOW COLUMNS FROM usuarios');
        $columns = [];
        if (!$result) {
            return $columns;
        }

        while ($row = $result->fetch_assoc()) {
            $name = strtolower((string) ($row['Field'] ?? ''));
            if ($name !== '') {
                $columns[$name] = true;
            }
        }

        return $columns;
    }

    private function hasUserColumn(string $column): bool
    {
        return isset($this->userColumns[strtolower($column)]);
    }

    private function emailExistsForOther(string $email, int $userId): bool
    {
        $stmt = $this->conn->prepare('SELECT id FROM usuarios WHERE email = ? AND id <> ? LIMIT 1');
        $stmt->bind_param('si', $email, $userId);
        $stmt->execute();

        $result = $stmt->get_result();
        return (bool) ($result && $result->fetch_assoc());
    }

    private function buildDefaultLoginFromEmail(string $email): string
    {
        $base = explode('@', mb_strtolower(trim($email)))[0] ?? '';
        $base = preg_replace('/[^a-z0-9._-]+/i', '', $base ?? '');
        $base = trim((string) $base);
        if ($base === '') {
            return 'usuario';
        }

        return mb_substr($base, 0, 40);
    }

    private function detectScheduledOrdersReady(): bool
    {
        $tableResult = $this->conn->query("SHOW TABLES LIKE 'pedidos_agendados'");
        if (!$tableResult || !$tableResult->fetch_row()) {
            return false;
        }

        foreach (['usuario_id', 'produto_nome', 'data_agendada', 'valor_total'] as $column) {
            $safe = $this->conn->real_escape_string($column);
            $columnResult = $this->conn->query("SHOW COLUMNS FROM pedidos_agendados LIKE '{$safe}'");
            if (!$columnResult || !$columnResult->fetch_assoc()) {
                return false;
            }
        }

        return true;
    }
}
