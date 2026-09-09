<?php

declare(strict_types=1);

namespace Premisely\Modules\Accounts\Controllers;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Core\Logging\Logger;
use Premisely\Core\Validation\Validator;
use Premisely\Modules\Activity\Services\ActivityLogger;

final class AuthController extends Controller
{
    public function showLogin(Request $request, array $params): never
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        $this->view('auth/login', ['title' => 'Iniciar sesión']);
    }

    public function login(Request $request, array $params): never
    {
        if (Auth::loginThrottled()) {
            flash('error', 'Demasiados intentos. Esperá unos minutos.');
            $this->redirect('/login');
        }

        $email = (string) $request->input('email', '');
        $password = (string) $request->input('password', '');

        if (!Auth::attempt($email, $password)) {
            Logger::security('login.failed', ['email' => $email, 'ip' => $request->ip()]);
            flash('error', 'Credenciales inválidas.');
            $_SESSION['_old'] = ['email' => $email];
            $this->redirect('/login');
        }

        unset($_SESSION['_old']);
        flash('success', 'Bienvenido/a.');
        $this->redirect('/dashboard');
    }

    public function showRegister(Request $request, array $params): never
    {
        if (!is_file(storage_path('installed'))) {
            $this->redirect('/install');
        }
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        $this->view('auth/register', ['title' => 'Crear cuenta']);
    }

    public function register(Request $request, array $params): never
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:120',
            'email' => 'required|email|max:190',
            'password' => 'required|min:8|confirmed',
        ]);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            $_SESSION['_old'] = [
                'name' => $request->input('name'),
                'email' => $request->input('email'),
            ];
            $this->redirect('/register');
        }
        $data = $validator->validated();
        $email = strtolower(trim((string) $data['email']));
        if (Connection::fetch('SELECT id FROM users WHERE email = :e', ['e' => $email])) {
            flash('error', 'Ese email ya está registrado.');
            $this->redirect('/register');
        }

        Connection::query(
            'INSERT INTO users (public_id, name, email, password_hash, locale, timezone, preferred_currency, created_at)
             VALUES (:pid, :name, :email, :hash, \'es\', \'America/Argentina/Buenos_Aires\', \'ARS\', NOW())',
            [
                'pid' => ulid(),
                'name' => $data['name'],
                'email' => $email,
                'hash' => password_hash((string) $data['password'], PASSWORD_DEFAULT),
            ]
        );
        $user = Connection::fetch('SELECT * FROM users WHERE email = :e', ['e' => $email]);
        if ($user) {
            Auth::loginUser($user);
            ActivityLogger::log(null, 'user', (int) $user['id'], 'registered');
        }
        unset($_SESSION['_old']);
        flash('success', 'Cuenta creada.');
        $this->redirect('/dashboard');
    }

    public function logout(Request $request, array $params): never
    {
        Auth::logout();
        flash('success', 'Sesión cerrada.');
        $this->redirect('/login');
    }

    public function showForgot(Request $request, array $params): never
    {
        $this->view('auth/forgot', ['title' => 'Recuperar contraseña']);
    }

    public function forgot(Request $request, array $params): never
    {
        flash('success', 'Si el email existe, recibirás instrucciones (función pendiente en V1).');
        $this->redirect('/forgot-password');
    }

    public function profile(Request $request, array $params): never
    {
        Auth::requireLogin();
        $user = Connection::fetch('SELECT * FROM users WHERE id = :id', ['id' => Auth::id()]);
        $this->view('settings/profile', [
            'title' => 'Mi perfil',
            'user' => $user,
            'form_action' => '/profile',
        ]);
    }

    public function updateProfile(Request $request, array $params): never
    {
        Auth::requireLogin();
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:120',
            'locale' => 'required|max:10',
            'timezone' => 'required|max:64',
            'preferred_currency' => 'required|max:3',
        ]);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            $this->redirect('/profile');
        }

        Connection::query(
            'UPDATE users SET name = :name, locale = :locale, timezone = :timezone,
             preferred_currency = :currency, notify_email = :notify, updated_at = NOW()
             WHERE id = :id',
            [
                'name' => trim((string) $request->input('name')),
                'locale' => (string) $request->input('locale'),
                'timezone' => (string) $request->input('timezone'),
                'currency' => strtoupper((string) $request->input('preferred_currency')),
                'notify' => $request->input('notify_email') ? 1 : 0,
                'id' => Auth::id(),
            ]
        );

        if ($_SESSION['user'] ?? null) {
            $_SESSION['user']['name'] = trim((string) $request->input('name'));
            $_SESSION['user']['locale'] = (string) $request->input('locale');
            $_SESSION['user']['timezone'] = (string) $request->input('timezone');
            $_SESSION['user']['preferred_currency'] = strtoupper((string) $request->input('preferred_currency'));
        }

        flash('success', 'Perfil actualizado.');
        $this->redirect('/profile');
    }
}
