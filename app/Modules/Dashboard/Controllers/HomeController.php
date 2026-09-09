<?php

declare(strict_types=1);

namespace Premisely\Modules\Dashboard\Controllers;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;

final class HomeController extends Controller
{
    public function index(Request $request, array $params): never
    {
        if (!is_file(storage_path('installed'))) {
            $this->redirect('/install');
        }
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        $this->redirect('/login');
    }
}
