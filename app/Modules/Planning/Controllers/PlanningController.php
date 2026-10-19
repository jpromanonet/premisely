<?php

declare(strict_types=1);

namespace Premisely\Modules\Planning\Controllers;

use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Shared\PropertyContext;

final class PlanningController extends Controller
{
    public function weekly(Request $request, array $params): never
    {
        $day = (string) $request->query('week', '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) {
            $day = date('Y-m-d');
        }
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/calendar?day=' . $day);
    }
}
