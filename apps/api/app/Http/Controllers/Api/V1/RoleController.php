<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Authorization\AuthorizationService;
use App\Enums\PermissionKey;
use App\Http\Requests\Roles\AssignMembershipRolesRequest;
use App\Http\Requests\Roles\ListRolesRequest;
use App\Http\Requests\Roles\StoreRoleRequest;
use App\Http\Requests\Roles\UpdateRoleRequest;
use App\Http\Resources\EffectivePermissionsResource;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoleController
{
    private function allow(Request $request, Organization $organization, string $permission): void
    {
        abort_unless(app(AuthorizationService::class)->allows($request->user(), $organization, $permission), 403);
    }

    private function own(Role $role, Organization $organization): void
    {
        abort_unless($role->organization_id === $organization->id, 404);
    }

    public function permissions(Request $request, Organization $organization)
    {
        $this->allow($request, $organization, PermissionKey::RoleView->value);

        return PermissionResource::collection(Permission::orderBy('key')->get(['id', 'key', 'description']));
    }

    public function index(ListRolesRequest $request, Organization $organization)
    {
        $this->allow($request, $organization, PermissionKey::RoleView->value);
        $filters = $request->validated();

        $query = $organization->roles()->with('permissions');

        if (! empty($filters['q'])) {
            $query->search($filters['q'], ['name', 'slug']);
        }

        return RoleResource::collection($query->orderBy('name')->paginate($request->perPage()));
    }

    public function store(StoreRoleRequest $request, Organization $organization)
    {
        $this->allow($request, $organization, PermissionKey::RoleCreate->value);
        $data = $request->validated();
        $slug = Str::slug(trim($data['name']));
        abort_if(Role::isReservedSlug($slug), 422, 'Cannot use reserved role name.');

        $role = DB::transaction(function () use ($organization, $data, $slug) {
            $role = Role::create([
                'organization_id' => $organization->id,
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'is_system' => false,
            ]);

            if (! empty($data['permission_ids'])) {
                $role->permissions()->sync(Permission::whereIn('id', $data['permission_ids'])->pluck('id'));
            }

            return $role;
        });

        return (new RoleResource($role->load('permissions')))->response()->setStatusCode(201);
    }

    public function show(Request $request, Organization $organization, Role $role)
    {
        $this->allow($request, $organization, PermissionKey::RoleView->value);
        $this->own($role, $organization);

        return new RoleResource($role->load('permissions'));
    }

    public function update(UpdateRoleRequest $request, Organization $organization, Role $role)
    {
        $this->allow($request, $organization, PermissionKey::RoleUpdate->value);
        $this->own($role, $organization);
        abort_if($role->is_system || Role::isReservedSlug($role->slug), 422, 'System roles cannot be modified.');
        $data = $request->validated();

        DB::transaction(function () use ($role, $data) {
            if (isset($data['name'])) {
                $slug = Str::slug(trim($data['name']));
                abort_if(Role::isReservedSlug($slug), 422, 'Cannot rename to a reserved role name.');
                $role->name = $data['name'];
                $role->slug = $slug;
            }
            if (array_key_exists('description', $data)) {
                $role->description = $data['description'];
            }
            $role->save();

            if (array_key_exists('permission_ids', $data)) {
                $role->permissions()->sync(Permission::whereIn('id', $data['permission_ids'])->pluck('id'));
            }
        });

        return new RoleResource($role->load('permissions'));
    }

    public function destroy(Request $request, Organization $organization, Role $role)
    {
        $this->allow($request, $organization, PermissionKey::RoleDelete->value);
        $this->own($role, $organization);
        abort_if($role->is_system || Role::isReservedSlug($role->slug), 422, 'System roles cannot be deleted.');
        abort_if($role->memberships()->exists(), 422, 'Cannot delete role assigned to memberships.');

        DB::transaction(function () use ($role) {
            $role->permissions()->detach();
            $role->delete();
        });

        return response()->noContent();
    }

    public function assign(AssignMembershipRolesRequest $request, Organization $organization, OrganizationMembership $membership, AuthorizationService $service)
    {
        $this->allow($request, $organization, PermissionKey::RoleAssign->value);
        abort_unless($membership->organization_id === $organization->id, 404);
        $data = $request->validated();

        try {
            $service->assign($organization, $membership, $data['role_ids'], $request->user());

            return RoleResource::collection($membership->fresh(['roles.permissions'])->roles);
        } catch (\LogicException $e) {
            abort(422, $e->getMessage());
        }
    }

    public function effective(Request $request, Organization $organization, AuthorizationService $service)
    {
        $membership = $service->membership($request->user(), $organization);
        abort_unless($membership, 403);
        $roles = $membership->roles()->with('permissions')->get();
        $keys = $roles->contains('slug', AuthorizationService::OWNER)
            ? Permission::orderBy('key')->pluck('key')
            : $roles->flatMap->permissions->pluck('key')->unique()->sort()->values();

        return new EffectivePermissionsResource(['roles' => $roles, 'permissions' => $keys]);
    }
}
