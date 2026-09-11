<?php

declare(strict_types=1);

namespace Premisely\Modules\Properties\Controllers;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Core\Validation\Validator;
use Premisely\Modules\Activity\Services\ActivityLogger;
use Premisely\Modules\Properties\Services\PropertyService;
use Premisely\Shared\PropertyContext;

final class PropertyController extends Controller
{
    public function index(Request $request, array $params): never
    {
        Auth::requireLogin();
        $properties = Connection::fetchAll(
            'SELECT p.*, pm.role FROM properties p
             INNER JOIN property_members pm ON pm.property_id = p.id
             WHERE pm.user_id = :uid AND pm.status = \'active\' AND p.archived_at IS NULL
             ORDER BY p.name',
            ['uid' => Auth::id()]
        );
        $this->view('properties/index', [
            'title' => 'Propiedades',
            'heading' => 'Propiedades',
            'properties' => $properties,
        ]);
    }

    public function create(Request $request, array $params): never
    {
        Auth::requireLogin();
        $this->view('properties/form', [
            'title' => 'Nueva propiedad',
            'heading' => 'Nueva propiedad',
            'property' => null,
        ]);
    }

    public function store(Request $request, array $params): never
    {
        Auth::requireLogin();
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:160',
            'type' => 'required|in:casa,departamento,oficina,local,quinta,casa_vacaciones,otra',
            'tenure' => 'required|in:propia,alquiler',
            'currency' => 'required|max:3',
        ]);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            $this->redirect('/properties/create');
        }
        $property = (new PropertyService())->create(array_merge($validator->validated(), $request->all()));
        flash('success', 'Propiedad creada.');
        $this->redirect('/properties/' . $property['public_id'] . '/dashboard');
    }

    public function show(Request $request, array $params): never
    {
        $property = PropertyContext::property();
        $this->redirect('/properties/' . $property['public_id'] . '/dashboard');
    }

    public function edit(Request $request, array $params): never
    {
        $property = PropertyContext::property();
        $this->view('properties/form', [
            'title' => 'Editar propiedad',
            'heading' => 'Editar propiedad',
            'property' => $property,
        ]);
    }

    public function update(Request $request, array $params): never
    {
        $property = PropertyContext::property();
        if (!PropertyContext::canManage()) {
            flash('error', 'No tenés permiso.');
            $this->redirect('/properties/' . $property['public_id']);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:160',
            'type' => 'required|in:casa,departamento,oficina,local,quinta,casa_vacaciones,otra',
            'tenure' => 'required|in:propia,alquiler',
            'currency' => 'required|max:3',
        ]);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            $this->redirect('/properties/' . $property['public_id'] . '/edit');
        }
        $data = $validator->validated();
        Connection::query(
            'UPDATE properties SET name = :name, type = :type, tenure = :tenure, address = :address, description = :description,
             status = :status, currency = :currency, area_m2 = :area_m2, rooms = :rooms,
             managed_since = :managed_since, notes = :notes, updated_at = NOW()
             WHERE id = :id',
            [
                'name' => $data['name'],
                'type' => $data['type'],
                'tenure' => $data['tenure'],
                'address' => $request->input('address'),
                'description' => $request->input('description'),
                'status' => $request->input('status', 'activa'),
                'currency' => strtoupper((string) $data['currency']),
                'area_m2' => $request->input('area_m2') ?: null,
                'rooms' => $request->input('rooms') !== '' ? (int) $request->input('rooms') : null,
                'managed_since' => $request->input('managed_since') ?: null,
                'notes' => $request->input('notes'),
                'id' => $property['id'],
            ]
        );
        ActivityLogger::log((int) $property['id'], 'property', (int) $property['id'], 'updated');
        flash('success', 'Propiedad actualizada.');
        $this->redirect('/properties/' . $property['public_id'] . '/dashboard');
    }

    public function archive(Request $request, array $params): never
    {
        $property = PropertyContext::property();
        if (!PropertyContext::canManage()) {
            flash('error', 'No tenés permiso.');
            $this->redirect('/properties/' . $property['public_id']);
        }
        Connection::query('UPDATE properties SET archived_at = NOW(), status = \'archivada\' WHERE id = :id', ['id' => $property['id']]);
        ActivityLogger::log((int) $property['id'], 'property', (int) $property['id'], 'archived');
        flash('success', 'Propiedad archivada.');
        $this->redirect('/properties');
    }

    public function destroy(Request $request, array $params): never
    {
        $property = PropertyContext::property();
        if (!PropertyContext::canManage()) {
            flash('error', 'No tenés permiso para eliminar esta propiedad.');
            $this->redirect('/properties/' . $property['public_id']);
        }

        $confirm = trim((string) $request->input('confirm_name', ''));
        if ($confirm === '' || strcasecmp($confirm, (string) $property['name']) !== 0) {
            flash('error', 'Para eliminar, escribí exactamente el nombre de la propiedad.');
            $this->redirect('/properties/' . $property['public_id'] . '/edit');
        }

        (new PropertyService())->delete($property);
        flash('success', 'Propiedad eliminada permanentemente.');
        $this->redirect('/properties');
    }
}
