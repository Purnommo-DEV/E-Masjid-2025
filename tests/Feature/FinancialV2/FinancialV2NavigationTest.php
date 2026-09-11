<?php

use App\Models\User;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Permission;
use Tests\Support\UatFinancialFixture;

function financialNavigationUser(array $permissions = []): User
{
    $user = User::factory()->create();
    if ($permissions !== []) {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $user->givePermissionTo($permissions);
    }

    return $user;
}

function assertFinancialNavigationActive(TestResponse $response, ?string $group, string $item): void
{
    $response->assertOk();
    if ($group !== null) {
        $response->assertSee('data-nav-group="'.$group.'" data-active="true"', false);
    }
    $response->assertSee('data-nav-item="'.$item.'" data-active="true"', false);
}

test('Financial V2 navigation uses the requested grouped order without duplicate root links', function () {
    $context = UatFinancialFixture::context();
    $url = route('financial-v2.dashboard', ['entity' => $context['entity']->id]);

    $this->get($url)->assertRedirect(route('login'));
    expect(Permission::query()->count())->toBe(0);

    $response = $this->actingAs(financialNavigationUser())->get($url);
    $response->assertOk()
        ->assertSeeInOrder(['data-nav-group="finance"', 'data-nav-group="ziswaf"', 'data-nav-group="reports"', 'data-nav-item="controls"'], false)
        ->assertSeeInOrder([
            'data-nav-item="receipt"',
            'data-nav-item="payment"',
            'data-nav-item="transfer"',
            'data-nav-item="funds"',
            'data-nav-item="allocations"',
            'data-nav-item="realization"',
            'data-nav-item="history"',
            'data-nav-item="distributions"',
            'data-nav-item="beneficiaries"',
            'data-nav-item="planning"',
            'data-nav-item="financial-report"',
            'data-nav-item="ziswaf-report"',
            'data-nav-item="controls"',
        ], false)
        ->assertSee('data-financial-nav', false)
        ->assertSee('flex flex-wrap items-center', false)
        ->assertSee('let openDropdown = null;', false)
        ->assertSee('applyOpenDropdown(openDropdown === dropdown ? null : dropdown);', false)
        ->assertSee('! navigation.contains(event.target)', false)
        ->assertSee("event.key !== 'Escape'", false)
        ->assertSee('trigger?.focus();', false)
        ->assertSee('data-nav-item="receipt"', false)
        ->assertSee('data-nav-item="payment"', false)
        ->assertSee('data-nav-item="transfer"', false)
        ->assertSee('data-nav-item="realization"', false)
        ->assertSee('data-nav-item="history"', false)
        ->assertSee('data-nav-item="funds"', false)
        ->assertSee('data-nav-item="allocations"', false)
        ->assertSee('data-nav-item="planning"', false)
        ->assertSee('data-nav-item="distributions"', false)
        ->assertSee('data-nav-item="beneficiaries"', false)
        ->assertSee('data-nav-item="ziswaf-report"', false)
        ->assertSee('data-nav-item="financial-report"', false)
        ->assertSee('data-nav-item="controls"', false)
        ->assertDontSee('data-nav-item="dashboard"', false)
        ->assertDontSee('data-nav-group="funding"', false)
        ->assertDontSee('Dana &amp; Perencanaan', false);

    expect(Permission::query()->count())->toBe(0);

    $html = $response->getContent();
    foreach (['finance', 'ziswaf', 'reports'] as $group) {
        expect(substr_count($html, 'data-nav-group="'.$group.'"'))->toBe(1);
    }
    expect(substr_count($html, 'data-nav-item="controls"'))->toBe(1)
        ->and(substr_count($html, 'aria-expanded="false"'))->toBe(3)
        ->and(substr_count($html, 'aria-controls="financial-v2-nav-'))->toBe(3)
        ->and(substr_count($html, ' data-nav-menu>'))->toBe(3);
});

test('Financial V2 navigation marks every parent and child route active without changing endpoint authorization', function () {
    $context = UatFinancialFixture::context();
    $user = financialNavigationUser([
        'financial-v2.planning.view',
        'view penyaluran ziswaf',
        'view penerima ziswaf',
    ]);
    $entity = $context['entity']->id;

    $pages = [
        ['financial-v2.transactions.create', ['operation' => 'receipt', 'entity' => $entity], 'finance', 'receipt'],
        ['financial-v2.transactions.create', ['operation' => 'payment', 'entity' => $entity], 'finance', 'payment'],
        ['financial-v2.transactions.create', ['operation' => 'transfer', 'entity' => $entity], 'finance', 'transfer'],
        ['financial-v2.realizations.drafts', ['entity' => $entity], 'finance', 'realization'],
        ['financial-v2.transactions.index', ['entity' => $entity], 'finance', 'history'],
        ['financial-v2.funds.index', ['entity' => $entity], 'finance', 'funds'],
        ['financial-v2.allocations.create', ['entity' => $entity], 'finance', 'allocations'],
        ['financial-v2.plannings.index', ['entity' => $entity], 'ziswaf', 'planning'],
        ['financial-v2.distributions.index', ['entity' => $entity], 'ziswaf', 'distributions'],
        ['financial-v2.beneficiaries.index', ['entity' => $entity], 'ziswaf', 'beneficiaries'],
        ['financial-v2.ziswaf-v2.index', ['entity' => $entity], 'reports', 'ziswaf-report'],
        ['financial-v2.reports.index', ['entity' => $entity], 'reports', 'financial-report'],
        ['financial-v2.controls.index', ['entity' => $entity], null, 'controls'],
    ];

    foreach ($pages as [$routeName, $parameters, $group, $item]) {
        $response = $this->actingAs($user)->get(route($routeName, $parameters));
        assertFinancialNavigationActive($response, $group, $item);
    }

    $unprivilegedUser = financialNavigationUser();
    $this->actingAs($unprivilegedUser)->get(route('financial-v2.plannings.index', ['entity' => $entity]))->assertForbidden();
    $this->actingAs($unprivilegedUser)->get(route('financial-v2.distributions.index', ['entity' => $entity]))->assertForbidden();
    $this->actingAs($unprivilegedUser)->get(route('financial-v2.beneficiaries.index', ['entity' => $entity]))->assertForbidden();
});
