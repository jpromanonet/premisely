<?php

declare(strict_types=1);

namespace Premisely\Modules\Install\Controllers;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;
use Premisely\Core\Database\Migrator;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Core\Validation\Validator;
use Throwable;

final class InstallController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $this->guardNotInstalled();
        $this->redirect('/install/requirements');
    }

    public function requirements(Request $request, array $params): never
    {
        $this->guardNotInstalled();

        $checks = [
            'PHP >= 8.1' => version_compare(PHP_VERSION, '8.1.0', '>='),
            'ext-pdo' => extension_loaded('pdo'),
            'ext-pdo_mysql' => extension_loaded('pdo_mysql'),
            'ext-mbstring' => extension_loaded('mbstring'),
            'ext-json' => extension_loaded('json'),
            'ext-fileinfo' => extension_loaded('fileinfo'),
            'storage escribible' => is_writable(storage_path()) || is_writable(dirname(storage_path())) || @mkdir(storage_path('tmp'), 0775, true),
            'uploads escribible' => is_writable(storage_path('uploads')) || @mkdir(storage_path('uploads'), 0775, true) || is_writable(storage_path()),
        ];

        $this->view('install/requirements', [
            'title' => 'Instalación · Requisitos',
            'checks' => $checks,
            'ok' => !in_array(false, $checks, true),
            'layout' => 'layouts/guest',
        ]);
    }

    public function databaseForm(Request $request, array $params): never
    {
        $this->guardNotInstalled();
        $this->view('install/database', [
            'title' => 'Instalación · Base de datos',
            'config' => config('database'),
            'layout' => 'layouts/guest',
        ]);
    }

    public function database(Request $request, array $params): never
    {
        $this->guardNotInstalled();

        $host = (string) ($request->input('host') ?: '127.0.0.1');
        $port = (int) ($request->input('port') ?: 3306);
        $database = (string) ($request->input('database') ?: 'premisely');
        $username = (string) ($request->input('username') ?: 'root');
        $password = (string) ($request->input('password') ?? '');

        try {
            $server = Connection::connectServer([
                'host' => $host,
                'port' => $port,
                'username' => $username,
                'password' => $password,
                'charset' => 'utf8mb4',
            ]);

            $safeDb = str_replace('`', '``', $database);
            $server->exec("CREATE DATABASE IF NOT EXISTS `{$safeDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            $this->writeEnvDatabase($host, $port, $database, $username, $password);

            // Reconnect app connection to the new DB
            $ref = new \ReflectionClass(Connection::class);
            $prop = $ref->getProperty('pdo');
            $prop->setAccessible(true);
            $prop->setValue(null, null);

            Connection::connect([
                'host' => $host,
                'port' => $port,
                'database' => $database,
                'username' => $username,
                'password' => $password,
                'charset' => 'utf8mb4',
            ]);

            Migrator::run(base_path('database/migrations'));
            Migrator::seed(base_path('database/seeds'));

            $_SESSION['_install'] = ['db_ok' => true];
            flash('success', 'Base de datos creada y migrada.');
            $this->redirect('/install/admin');
        } catch (Throwable $e) {
            flash('error', 'Error de base de datos: ' . $e->getMessage());
            $this->redirect('/install/database');
        }
    }

    public function adminForm(Request $request, array $params): never
    {
        $this->guardNotInstalled();
        if (empty($_SESSION['_install']['db_ok'])) {
            flash('error', 'Completá primero la configuración de base de datos.');
            $this->redirect('/install/database');
        }

        $this->ensureDbConnected();
        $this->view('install/admin', [
            'title' => 'Instalación · Administrador',
            'layout' => 'layouts/guest',
        ]);
    }

    public function admin(Request $request, array $params): never
    {
        $this->guardNotInstalled();
        $this->ensureDbConnected();

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:120',
            'email' => 'required|email|max:190',
            'password' => 'required|min:8|confirmed',
        ]);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            $this->redirect('/install/admin');
        }

        $email = strtolower(trim((string) $request->input('email')));
        $exists = Connection::fetch('SELECT id FROM users WHERE email = :email', ['email' => $email]);
        if ($exists) {
            flash('error', 'Ese email ya existe.');
            $this->redirect('/install/admin');
        }

        Connection::query(
            'INSERT INTO users (public_id, name, email, password_hash, locale, timezone, preferred_currency, created_at)
             VALUES (:public_id, :name, :email, :password_hash, \'es\', \'America/Argentina/Buenos_Aires\', \'ARS\', NOW())',
            [
                'public_id' => ulid(),
                'name' => trim((string) $request->input('name')),
                'email' => $email,
                'password_hash' => password_hash((string) $request->input('password'), PASSWORD_DEFAULT),
            ]
        );

        $user = Connection::fetch('SELECT * FROM users WHERE email = :email', ['email' => $email]);
        if ($user) {
            Auth::loginUser($user);
        }

        $_SESSION['_install']['admin_ok'] = true;
        flash('success', 'Usuario administrador creado.');
        $this->redirect('/install/finish');
    }

    public function finishForm(Request $request, array $params): never
    {
        $this->guardNotInstalled();
        $this->view('install/finish', [
            'title' => 'Instalación · Finalizar',
            'layout' => 'layouts/guest',
        ]);
    }

    public function finish(Request $request, array $params): never
    {
        $this->guardNotInstalled();
        file_put_contents(storage_path('installed'), date('c') . PHP_EOL);
        unset($_SESSION['_install']);
        flash('success', 'Premisely instalado correctamente.');
        $this->redirect('/dashboard');
    }

    private function guardNotInstalled(): void
    {
        if (is_file(storage_path('installed'))) {
            flash('error', 'La aplicación ya está instalada.');
            redirect('/login');
        }
    }

    private function ensureDbConnected(): void
    {
        if (Connection::connected()) {
            return;
        }
        try {
            Connection::connect([
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => (int) env('DB_PORT', '3306'),
                'database' => env('DB_DATABASE', 'premisely'),
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => env('DB_CHARSET', 'utf8mb4'),
            ]);
        } catch (Throwable $e) {
            flash('error', 'No hay conexión a la base de datos.');
            redirect('/install/database');
        }
    }

    private function writeEnvDatabase(string $host, int $port, string $database, string $username, string $password): void
    {
        $envPath = base_path('.env');
        $content = is_file($envPath) ? (string) file_get_contents($envPath) : (string) file_get_contents(base_path('.env.example'));

        $pairs = [
            'DB_HOST' => $host,
            'DB_PORT' => (string) $port,
            'DB_DATABASE' => $database,
            'DB_USERNAME' => $username,
            'DB_PASSWORD' => $password,
        ];

        foreach ($pairs as $key => $value) {
            $line = $key . '=' . $this->envEscape($value);
            if (preg_match('/^' . preg_quote($key, '/') . '=.*$/m', $content)) {
                $content = preg_replace('/^' . preg_quote($key, '/') . '=.*$/m', $line, $content) ?? $content;
            } else {
                $content .= PHP_EOL . $line;
            }
        }

        file_put_contents($envPath, $content);

        // Refresh runtime config
        $_ENV['DB_HOST'] = $host;
        $_ENV['DB_PORT'] = (string) $port;
        $_ENV['DB_DATABASE'] = $database;
        $_ENV['DB_USERNAME'] = $username;
        $_ENV['DB_PASSWORD'] = $password;
        putenv('DB_HOST=' . $host);
        putenv('DB_PORT=' . $port);
        putenv('DB_DATABASE=' . $database);
        putenv('DB_USERNAME=' . $username);
        putenv('DB_PASSWORD=' . $password);
    }

    private function envEscape(string $value): string
    {
        if ($value === '' || preg_match('/[\s#"\']/', $value)) {
            return '"' . str_replace('"', '\"', $value) . '"';
        }
        return $value;
    }
}
