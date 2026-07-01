<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\SessionAuth;
use App\Models\UserModel;
use mysqli_sql_exception;
use Throwable;

final class AuthController extends Controller
{
    private UserModel $users;

    public function __construct()
    {
        $this->users = new UserModel();
    }

    public function login(): void
    {
        SessionAuth::start();
        $nextTarget = $this->resolveNextTarget();

        if (SessionAuth::isLoggedIn()) {
            $this->redirect($nextTarget ?? 'index.php');
        }

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            $email = trim((string) ($_POST['email'] ?? ''));
            $senha = (string) ($_POST['senha'] ?? '');

            if ($email === '' || $senha === '') {
                $this->redirect($this->buildLoginRedirect('erro=campos', $nextTarget));
            }

            try {
                $usuario = $this->users->findByEmail($email);
                if (!$usuario || !password_verify($senha, (string) $usuario['senha'])) {
                    $this->redirect($this->buildLoginRedirect('erro=credenciais', $nextTarget));
                }

                SessionAuth::login($usuario);
                $this->redirect($nextTarget ?? 'index.php');
            } catch (Throwable $e) {
                $this->redirect($this->buildLoginRedirect('erro=sistema', $nextTarget));
            }
        }

        $this->render('auth/login', [
            'mensagem' => $this->resolveMessage(),
            'showRegister' => ((string) ($_GET['ctx'] ?? '')) === 'register',
            'nextTarget' => $nextTarget,
        ]);
    }

    public function register(): void
    {
        $nextTarget = $this->resolveNextTarget();
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            $this->redirect($this->buildLoginRedirect('', $nextTarget));
        }

        $nome = trim((string) ($_POST['nome'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $senha = (string) ($_POST['senha'] ?? '');

        if ($nome === '' || $email === '' || $senha === '') {
            $this->redirect($this->buildLoginRedirect('erro=campos&ctx=register', $nextTarget));
        }

        if (!$this->isEmailFormatoValido($email)) {
            $this->redirect($this->buildLoginRedirect('erro=email_invalido&ctx=register', $nextTarget));
        }

        if (strlen($senha) < 8) {
            $this->redirect($this->buildLoginRedirect('erro=senha_curta&ctx=register', $nextTarget));
        }

        try {
            if ($this->users->emailExists($email)) {
                $this->redirect($this->buildLoginRedirect('erro=email_existe&ctx=register', $nextTarget));
            }

            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $this->users->create($nome, $email, $hash);
            $this->redirect($this->buildLoginRedirect('cad=ok', $nextTarget));
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) {
                $this->redirect($this->buildLoginRedirect('erro=email_existe&ctx=register', $nextTarget));
            }

            if ($e->getCode() === 1406) {
                $this->redirect($this->buildLoginRedirect('erro=email_grande&ctx=register', $nextTarget));
            }

            if (in_array($e->getCode(), [1265, 1366], true)) {
                $this->redirect($this->buildLoginRedirect('erro=schema_incompativel&ctx=register', $nextTarget));
            }

            $this->redirect($this->buildLoginRedirect('erro=sistema', $nextTarget));
        } catch (Throwable $e) {
            $this->redirect($this->buildLoginRedirect('erro=sistema', $nextTarget));
        }
    }

    public function logout(): void
    {
        SessionAuth::logout();
        $this->redirect('login.php?logout=ok');
    }

    private function resolveMessage(): ?string
    {
        if (isset($_GET['erro'])) {
            $erro = (string) $_GET['erro'];
            if ($erro === 'credenciais') {
                return 'Email ou senha invalidos.';
            }
            if ($erro === 'email_existe') {
                return 'Ja existe uma conta com esse email.';
            }
            if ($erro === 'email_invalido') {
                return 'Informe um email valido.';
            }
            if ($erro === 'senha_curta') {
                return 'A senha precisa ter pelo menos 6 caracteres.';
            }
            if ($erro === 'email_grande') {
                return 'O email informado e muito longo.';
            }
            if ($erro === 'schema_incompativel') {
                return 'O banco esta com estrutura antiga. Rode o arquivo sql/migrate_existing.sql.';
            }
            if ($erro === 'campos') {
                return 'Preencha os campos obrigatorios.';
            }
            if ($erro === 'acesso') {
                return 'Acesso permitido apenas para administradores.';
            }
            if ($erro === 'login') {
                return 'Faca login para continuar.';
            }

            return 'Erro inesperado ao autenticar.';
        }

        if (isset($_GET['cad']) && (string) $_GET['cad'] === 'ok') {
            return 'Cadastro realizado com sucesso. Agora faca login.';
        }

        if (isset($_GET['logout']) && (string) $_GET['logout'] === 'ok') {
            return 'Sessao encerrada com sucesso.';
        }

        return null;
    }

    private function isEmailFormatoValido(string $email): bool
    {
        $email = trim($email);
        if ($email === '') {
            return false;
        }

        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return false;
        }

        $local = trim($parts[0]);
        $domain = trim($parts[1]);

        return $local !== '' && $domain !== '';
    }

    private function resolveNextTarget(): ?string
    {
        $sources = [
            (string) ($_POST['next'] ?? ''),
            (string) ($_GET['next'] ?? ''),
        ];

        foreach ($sources as $candidate) {
            $candidate = trim($candidate);
            if ($candidate === '') {
                continue;
            }

            if ($this->isSafeNextTarget($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function isSafeNextTarget(string $target): bool
    {
        if ($target === '' || strlen($target) > 200) {
            return false;
        }

        if (preg_match('#^https?://#i', $target) === 1) {
            return false;
        }

        if (strpos($target, '//') === 0 || strpos($target, '\\') !== false) {
            return false;
        }

        if (strpos($target, "\n") !== false || strpos($target, "\r") !== false) {
            return false;
        }

        if (strpos($target, '.php') === false) {
            return false;
        }

        return true;
    }

    private function buildLoginRedirect(string $query, ?string $nextTarget): string
    {
        $url = 'login.php';
        $params = trim($query);

        if ($params !== '') {
            $url .= '?' . $params;
        }

        if ($nextTarget !== null && $nextTarget !== '') {
            $url .= ($params === '' ? '?' : '&') . 'next=' . rawurlencode($nextTarget);
        }

        return $url;
    }
}
