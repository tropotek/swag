<?php

use App\Models\Page;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

it('lets only the owner view, update and delete a page', function () {
    $page = Page::factory()->create();
    $other = User::factory()->create();

    foreach (['view', 'update', 'delete'] as $ability) {
        expect($page->user->can($ability, $page))->toBeTrue()
            ->and($other->can($ability, $page))->toBeFalse();
    }
});

it('denies non-owners as not found', function () {
    $response = Gate::forUser(User::factory()->create())->inspect('view', Page::factory()->create());

    expect($response->status())->toBe(404);
});

it('gives admins no access to other users\' pages', function () {
    $page = Page::factory()->create();

    expect(User::factory()->admin()->create()->can('view', $page))->toBeFalse();
});
