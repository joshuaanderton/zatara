<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Zatara\Support\Facades\Zatara;

uses(TestCase::class, RefreshDatabase::class)->in(__DIR__);

it('Actions without auth middleware run without error and return view or redirect', function () {
    $actions = Zatara::actions()->filter(fn ($action) => ! in_array('auth', $action['action']['middleware']));
    $actions->each(function ($action) {
        $method = $action['methods'][0];
        $parameters = [];

        $response = $this->$method(
            route($action['action']['as'], $parameters)
        );

        $response->assertStatus(200);
    });
});

it('Actions with auth middleware run without error and return view or redirect', function () {
    $actions = Zatara::actions()->filter(fn ($action) => in_array('auth', $action['action']['middleware']));
    $this->actingAs($user = User::factory()->withPersonalTeam()->create());

    $actions->each(function ($action) {
        $method = $action['methods'][0];
        $parameters = [];

        $response = $this->$method(
            route($action['action']['as'], $parameters)
        );

        $response->assertStatus(200);
    });
});
