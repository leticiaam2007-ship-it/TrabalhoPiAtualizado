<?php
declare(strict_types=1);

namespace App\Core;

use mysqli;
use RuntimeException;
use Throwable;

final class Database
{
    private static ?mysqli $connection = null;

    public static function connection(): mysqli
    {
        if (self::$connection instanceof mysqli) {
            return self::$connection;
        }

        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = (int) (getenv('DB_PORT') ?: 3306);
        $db = getenv('DB_NAME') ?: 'sabores_tecnicos';
        $user = getenv('DB_USER') ?: 'root';
        $envPass = getenv('DB_PASS');

        $passwords = [];
        if ($envPass !== false) {
            $passwords[] = $envPass;
        } else {
            $passwords[] = '';
            $passwords[] = 'admin';
        }

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $lastError = null;
        foreach ($passwords as $pass) {
            try {
                self::$connection = new mysqli($host, $user, $pass, $db, $port);
                self::$connection->set_charset('utf8mb4');
                return self::$connection;
            } catch (Throwable $e) {
                $lastError = $e;
                self::$connection = null;
            }
        }

        $msg = $lastError ? $lastError->getMessage() : 'erro desconhecido';
        throw new RuntimeException('Erro na conexao com o MySQL: ' . $msg);
    }
}
