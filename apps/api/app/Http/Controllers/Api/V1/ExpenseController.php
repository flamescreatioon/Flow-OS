<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Authorization\AuthorizationService;
use App\Application\Finance\ExpenseService;
use App\Enums\PermissionKey;
use App\Http\Requests\Finance\ListExpensesRequest;
use App\Http\Requests\Finance\StoreExpenseRequest;
use App\Http\Requests\Finance\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Models\Organization;
use Illuminate\Http\Request;
use InvalidArgumentException;
use LogicException;

class ExpenseController
{
    public function index(ListExpensesRequest $request, Organization $organization)
    {
        $this->permission($request, $organization, PermissionKey::ExpenseView->value);
        $filters = $request->validated();

        $query = $organization->expenses()->with(['category', 'branch']);

        if (! empty($filters['expense_category_id'])) {
            $query->where('expense_category_id', $filters['expense_category_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        return ExpenseResource::collection(
            $query->orderByDesc('created_at')->orderByDesc('id')->paginate($request->perPage())
        );
    }

    public function store(StoreExpenseRequest $request, Organization $organization, ExpenseService $service)
    {
        $this->permission($request, $organization, PermissionKey::ExpenseCreate->value);

        try {
            $expense = $service->create($organization, $request->validated(), $request->user());
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            abort(422, $exception->getMessage());
        }

        return (new ExpenseResource($expense->load(['category', 'branch'])))->response()->setStatusCode(201);
    }

    public function show(Request $request, Organization $organization, Expense $expense)
    {
        $this->permission($request, $organization, PermissionKey::ExpenseView->value);
        $this->assertBelongsToOrganization($expense, $organization);

        return new ExpenseResource($expense->load(['category', 'branch']));
    }

    public function update(UpdateExpenseRequest $request, Organization $organization, Expense $expense, ExpenseService $service)
    {
        $this->permission($request, $organization, PermissionKey::ExpenseUpdate->value);
        $this->assertBelongsToOrganization($expense, $organization);

        try {
            $expense = $service->update($organization, $expense, $request->validated(), $request->user());
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            abort(422, $exception->getMessage());
        }

        return new ExpenseResource($expense);
    }

    public function void(Request $request, Organization $organization, Expense $expense, ExpenseService $service)
    {
        $this->permission($request, $organization, PermissionKey::ExpenseVoid->value);
        $this->assertBelongsToOrganization($expense, $organization);

        try {
            $expense = $service->void($organization, $expense, $request->user());
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        }

        return new ExpenseResource($expense);
    }

    private function assertBelongsToOrganization(Expense $expense, Organization $organization): void
    {
        abort_unless($expense->organization_id === $organization->id, 404);
    }

    private function permission(Request $request, Organization $organization, string $permission): void
    {
        abort_unless(app(AuthorizationService::class)->allows($request->user(), $organization, $permission), 403);
    }
}
