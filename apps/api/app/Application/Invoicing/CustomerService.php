<?php

namespace App\Application\Invoicing;

use App\Models\Customer;
use App\Models\Organization;
use LogicException;

final class CustomerService
{
    public function create(Organization $organization, array $data): Customer
    {
        $customer = new Customer([
            'organization_id' => $organization->id,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
        ]);

        $customer->save();

        return $customer;
    }

    public function update(Organization $organization, Customer $customer, array $data): Customer
    {
        if ($customer->organization_id !== $organization->id) {
            throw new LogicException('The customer does not belong to this organization.');
        }

        if (array_key_exists('name', $data)) {
            $customer->name = $data['name'];
        }

        if (array_key_exists('email', $data)) {
            $customer->email = $data['email'];
        }

        if (array_key_exists('phone', $data)) {
            $customer->phone = $data['phone'];
        }

        if (array_key_exists('address', $data)) {
            $customer->address = $data['address'];
        }

        $customer->save();

        return $customer;
    }

    public function archive(Organization $organization, Customer $customer): Customer
    {
        if ($customer->organization_id !== $organization->id) {
            throw new LogicException('The customer does not belong to this organization.');
        }

        if (! $customer->isArchived()) {
            $customer->forceFill(['archived_at' => now()])->save();
        }

        return $customer;
    }
}
