<?php

declare(strict_types=1);

namespace Premisely\Modules\Automations\Controllers;

use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Modules\Automations\Services\AutomationRunner;
use Premisely\Shared\PropertyContext;

final class AutomationController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $runner = new AutomationRunner();
        $runner->ensureDefaults(PropertyContext::propertyId());
        $rules = Connection::fetchAll(
            'SELECT * FROM automation_rules WHERE property_id = :p ORDER BY type',
            ['p' => PropertyContext::propertyId()]
        );
        $this->view('automations/index', [
            'title' => 'Automatizaciones',
            'property' => PropertyContext::property(),
            'rules' => $rules,
            'labels' => AutomationRunner::TYPES,
            'canManage' => PropertyContext::canManage(),
        ]);
    }

    public function toggle(Request $request, array $params): never
    {
        if (!PropertyContext::canManage()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/automations');
        }
        $rule = Connection::fetch(
            'SELECT * FROM automation_rules WHERE public_id = :pid AND property_id = :p LIMIT 1',
            ['pid' => $params['rule'] ?? '', 'p' => PropertyContext::propertyId()]
        );
        if (!$rule) {
            flash('error', 'Regla no encontrada.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/automations');
        }
        $enabled = (int) $rule['enabled'] === 1 ? 0 : 1;
        Connection::query(
            'UPDATE automation_rules SET enabled = :e, updated_at = NOW() WHERE id = :id',
            ['e' => $enabled, 'id' => $rule['id']]
        );
        flash('success', $enabled ? 'Regla activada.' : 'Regla desactivada.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/automations');
    }

    public function run(Request $request, array $params): never
    {
        if (!PropertyContext::canManage()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/automations');
        }
        $n = (new AutomationRunner())->runForProperty(
            PropertyContext::propertyId(),
            (string) PropertyContext::property()['public_id']
        );
        flash('success', "Automatizaciones ejecutadas ({$n} acciones).");
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/automations');
    }
}
