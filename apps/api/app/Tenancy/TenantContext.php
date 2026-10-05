<?php

namespace App\Tenancy;

use App\Enums\MembershipStatus;
use App\Enums\OrganizationStatus;
use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\User;
use LogicException;

final class TenantContext
{
    private ?Organization $organization = null;

    private ?User $user = null;

    public function establish(User $user, Organization $organization): void
    {
        if ($user->status !== UserStatus::Active
            || $organization->status !== OrganizationStatus::Active
            || ! $user->memberships()
                ->where('organization_id', $organization->getKey())
                ->where('status', MembershipStatus::Active)
                ->exists()) {
            throw new LogicException('An active membership in an active organization is required to establish tenant context.');
        }

        $this->user = $user;
        $this->organization = $organization;
    }

    public function organization(): ?Organization
    {
        return $this->organization;
    }

    public function user(): ?User
    {
        return $this->user;
    }

    public function hasOrganization(): bool
    {
        return $this->organization !== null;
    }

    public function clear(): void
    {
        $this->organization = null;
        $this->user = null;
    }
}
