<?php

namespace App\Application\Purchasing;

use App\Models\Organization;
use App\Models\Supplier;
use LogicException;

final class SupplierService
{
    public function create(Organization $organization, array $data): Supplier
    {
        $supplier = new Supplier([
            'organization_id' => $organization->id,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
        ]);

        $supplier->save();

        return $supplier;
    }

    public function update(Organization $organization, Supplier $supplier, array $data): Supplier
    {
        if ($supplier->organization_id !== $organization->id) {
            throw new LogicException('The supplier does not belong to this organization.');
        }

        if (array_key_exists('name', $data)) {
            $supplier->name = $data['name'];
        }

        if (array_key_exists('email', $data)) {
            $supplier->email = $data['email'];
        }

        if (array_key_exists('phone', $data)) {
            $supplier->phone = $data['phone'];
        }

        if (array_key_exists('address', $data)) {
            $supplier->address = $data['address'];
        }

        $supplier->save();

        return $supplier;
    }

    public function archive(Organization $organization, Supplier $supplier): Supplier
    {
        if ($supplier->organization_id !== $organization->id) {
            throw new LogicException('The supplier does not belong to this organization.');
        }

        if (! $supplier->isArchived()) {
            $supplier->forceFill(['archived_at' => now()])->save();
        }

        return $supplier;
    }
}
