<?php

declare(strict_types=1);

namespace Premisely\Modules\Properties\Services;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;
use Premisely\Modules\Activity\Services\ActivityLogger;
use RuntimeException;

final class PropertyService
{
    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function create(array $data): array
    {
        $user = Auth::user();
        if (!$user) {
            throw new RuntimeException('Usuario no autenticado.');
        }

        Connection::begin();
        try {
            $publicId = ulid();
            Connection::query(
                'INSERT INTO properties (
                    public_id, name, type, address, description, status, currency,
                    area_m2, rooms, managed_since, notes, created_by, created_at
                 ) VALUES (
                    :public_id, :name, :type, :address, :description, :status, :currency,
                    :area_m2, :rooms, :managed_since, :notes, :created_by, NOW()
                 )',
                [
                    'public_id' => $publicId,
                    'name' => (string) $data['name'],
                    'type' => (string) ($data['type'] ?? 'casa'),
                    'address' => $data['address'] ?? null,
                    'description' => $data['description'] ?? null,
                    'status' => 'activa',
                    'currency' => (string) ($data['currency'] ?? ($user['preferred_currency'] ?? 'ARS')),
                    'area_m2' => $data['area_m2'] !== '' && $data['area_m2'] !== null ? $data['area_m2'] : null,
                    'rooms' => $data['rooms'] !== '' && $data['rooms'] !== null ? (int) $data['rooms'] : null,
                    'managed_since' => $data['managed_since'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => (int) $user['id'],
                ]
            );

            $propertyId = (int) Connection::lastInsertId();

            Connection::query(
                'INSERT INTO property_members (
                    property_id, user_id, display_name, email, role, member_type, status, joined_at, created_at
                 ) VALUES (
                    :property_id, :user_id, :display_name, :email, :role, :member_type, :status, NOW(), NOW()
                 )',
                [
                    'property_id' => $propertyId,
                    'user_id' => (int) $user['id'],
                    'display_name' => (string) $user['name'],
                    'email' => (string) $user['email'],
                    'role' => 'owner',
                    'member_type' => 'residente',
                    'status' => 'active',
                ]
            );

            Connection::query(
                'INSERT INTO shopping_lists (public_id, property_id, name, status, created_at)
                 VALUES (:public_id, :property_id, :name, :status, NOW())',
                [
                    'public_id' => ulid(),
                    'property_id' => $propertyId,
                    'name' => 'Lista activa',
                    'status' => 'active',
                ]
            );

            ActivityLogger::log($propertyId, 'property', $propertyId, 'created', [
                'name' => $data['name'],
            ]);

            Connection::commit();

            $property = Connection::fetch('SELECT * FROM properties WHERE id = :id', ['id' => $propertyId]);
            if (!$property) {
                throw new RuntimeException('No se pudo crear la propiedad.');
            }

            return $property;
        } catch (\Throwable $e) {
            Connection::rollBack();
            throw $e;
        }
    }
}
