<?php

namespace App\Application\Organizations;

use App\Enums\MembershipStatus;
use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

final class OrganizationLifecycleService
{
    public function create(User $user, array $data): Organization
    {
        return DB::transaction(function () use ($user, $data) {
            $name = $data['name'];
            $org = Organization::create(array_merge($data, ['slug' => $this->uniqueSlug($name), 'country_code' => $data['country_code'] ?? 'NG', 'currency_code' => $data['currency_code'] ?? 'NGN', 'currency' => $data['currency_code'] ?? 'NGN', 'timezone' => $data['timezone'] ?? 'Africa/Lagos']));
            $membership = new OrganizationMembership;
            $membership->forceFill(['organization_id' => $org->id, 'user_id' => $user->id, 'status' => MembershipStatus::Active, 'joined_at' => now()])->save();

            return $org;
        });
    }

    public function transition(Organization $organization, OrganizationStatus $target): Organization
    {
        $current = $organization->status;
        if ($current === OrganizationStatus::Closed || ! in_array($target, $current === OrganizationStatus::Active ? [OrganizationStatus::Suspended, OrganizationStatus::Closed] : [OrganizationStatus::Active, OrganizationStatus::Closed], true)) {
            throw new LogicException('Invalid organization lifecycle transition.');
        } $organization->forceFill(['status' => $target])->save();

        return $organization;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'organization';
        $slug = $base;
        $n = 2;
        while (Organization::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }

return $slug;
    }
}
