<?php

namespace Tests\Unit\Models;

use App\Models\Branch;
use App\Models\Organization;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class BranchTest extends TestCase
{
    public function test_branch_code_is_unique_within_an_organization_but_reusable_across_organizations(): void
    {
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        Branch::factory()->for($organizationA)->create(['code' => 'LAGOS']);
        Branch::factory()->for($organizationB)->create(['code' => 'LAGOS']);

        $this->expectException(QueryException::class);
        Branch::factory()->for($organizationA)->create(['code' => 'LAGOS']);
    }
}
