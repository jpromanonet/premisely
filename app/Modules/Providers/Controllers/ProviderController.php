<?php

declare(strict_types=1);

namespace Premisely\Modules\Providers\Controllers;

use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Modules\Activity\Services\ActivityLogger;
use Premisely\Shared\PropertyContext;

final class ProviderController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $providers = Connection::fetchAll(
            'SELECT * FROM providers WHERE property_id = :p AND archived_at IS NULL ORDER BY name',
            ['p' => PropertyContext::propertyId()]
        );
        $this->view('providers/index', [
            'title' => 'Proveedores',
            'property' => PropertyContext::property(),
            'providers' => $providers,
            'canEdit' => PropertyContext::canEdit(),
        ]);
    }

    public function create(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permiso.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/providers');
        }
        $this->view('providers/create', [
            'title' => 'Nuevo proveedor',
            'property' => PropertyContext::property(),
            'canEdit' => true,
        ]);
    }

    public function store(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permiso.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/providers');
        }
        $name = trim((string) $request->input('name', ''));
        if ($name === '') {
            flash('error', 'El nombre es obligatorio.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/providers/create');
        }
        Connection::query(
            'INSERT INTO providers (public_id, property_id, name, specialty, phone, email, notes, rating, created_at)
             VALUES (:pid, :prop, :name, :spec, :phone, :email, :notes, :rating, NOW())',
            [
                'pid' => ulid(),
                'prop' => PropertyContext::propertyId(),
                'name' => $name,
                'spec' => $request->input('specialty'),
                'phone' => $request->input('phone'),
                'email' => $request->input('email'),
                'notes' => $request->input('notes'),
                'rating' => $request->input('rating') !== '' ? (int) $request->input('rating') : null,
            ]
        );
        ActivityLogger::log(PropertyContext::propertyId(), 'provider', (int) Connection::lastInsertId(), 'created');
        flash('success', 'Proveedor agregado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/providers');
    }

    public function edit(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permiso.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/providers');
        }
        $provider = Connection::fetch(
            'SELECT * FROM providers WHERE public_id = :pid AND property_id = :prop AND archived_at IS NULL LIMIT 1',
            ['pid' => $params['provider'] ?? '', 'prop' => PropertyContext::propertyId()]
        );
        if (!$provider) {
            flash('error', 'Proveedor no encontrado.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/providers');
        }
        $this->view('providers/edit', [
            'title' => 'Editar proveedor',
            'property' => PropertyContext::property(),
            'provider' => $provider,
            'canEdit' => true,
        ]);
    }

    public function update(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permiso.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/providers');
        }
        $name = trim((string) $request->input('name', ''));
        if ($name === '') {
            flash('error', 'El nombre es obligatorio.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/providers/' . ($params['provider'] ?? '') . '/edit');
        }
        Connection::query(
            'UPDATE providers SET name = :name, specialty = :spec, phone = :phone, email = :email, notes = :notes, rating = :rating
             WHERE public_id = :pid AND property_id = :prop AND archived_at IS NULL',
            [
                'name' => $name,
                'spec' => $request->input('specialty'),
                'phone' => $request->input('phone'),
                'email' => $request->input('email'),
                'notes' => $request->input('notes'),
                'rating' => $request->input('rating') !== '' ? (int) $request->input('rating') : null,
                'pid' => $params['provider'] ?? '',
                'prop' => PropertyContext::propertyId(),
            ]
        );
        flash('success', 'Proveedor actualizado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/providers');
    }

    public function archive(Request $request, array $params): never
    {
        Connection::query(
            'UPDATE providers SET archived_at = NOW() WHERE public_id = :pid AND property_id = :prop',
            ['pid' => $params['provider'] ?? '', 'prop' => PropertyContext::propertyId()]
        );
        flash('success', 'Proveedor archivado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/providers');
    }
}
