<?php

use App\Enums\Exposure;
use App\Enums\Level;
use App\Enums\PlantEnvironment;
use App\Models\Plant;
use App\Models\User;

function plantPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Ficus elastica',
        'species' => 'Ficus elastica',
        'description' => 'Une plante robuste pour une pièce lumineuse.',
        'environment' => PlantEnvironment::INDOOR->value,
        'exposure' => Exposure::PARTIAL_SHADE->value,
        'watering_level' => Level::MEDIUM->value,
        'maintenance_level' => Level::LOW->value,
        'adult_height_cm' => 120,
        'adult_width_cm' => 60,
        'price' => '24.90',
        'stock_quantity' => 7,
        'is_active' => '1',
    ], $overrides);
}

beforeEach(function () {
    $this->manager = User::factory()->create(['email_verified_at' => now()]);
});

test('guests cannot view or modify the plant catalogue', function () {
    $this->get(route('admin.plants.index'))->assertRedirect(route('login'));
    $this->post(route('admin.plants.store'), plantPayload())->assertRedirect(route('login'));

    expect(Plant::query()->count())->toBe(0);
});

test('unverified users cannot access the plant catalogue', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('admin.plants.index'))
        ->assertRedirect(route('verification.notice'));
});

test('a manager can search and filter the catalogue', function () {
    Plant::factory()->create(['name' => 'Ficus elastica', 'is_active' => true, 'stock_quantity' => 4]);
    Plant::factory()->create(['name' => 'Palmier dormant', 'is_active' => false, 'stock_quantity' => 0]);

    $this->actingAs($this->manager)
        ->get(route('admin.plants.index', ['search' => 'Ficus', 'status' => 'active', 'stock' => 'available']))
        ->assertOk()
        ->assertSee('Ficus elastica')
        ->assertDontSee('Palmier dormant');
});

test('a manager can create a valid plant', function () {
    $response = $this->actingAs($this->manager)
        ->post(route('admin.plants.store'), plantPayload());

    $response->assertRedirect(route('admin.plants.index'));
    $this->assertDatabaseHas('plants', [
        'name' => 'Ficus elastica',
        'species' => 'Ficus elastica',
        'environment' => PlantEnvironment::INDOOR->value,
        'stock_quantity' => 7,
        'is_active' => 1,
    ]);

    expect(Plant::query()->first()->exposure)->toBe(Exposure::PARTIAL_SHADE);
});

test('plant creation validates required characteristics and non-negative prices', function () {
    $response = $this->actingAs($this->manager)
        ->post(route('admin.plants.store'), plantPayload([
            'exposure' => 'INVALID',
            'price' => '-1.00',
        ]));

    $response->assertSessionHasErrors(['exposure', 'price']);
    expect(Plant::query()->count())->toBe(0);
});

test('a manager can update stock and catalogue characteristics', function () {
    $plant = Plant::factory()->create(['stock_quantity' => 2]);

    $response = $this->actingAs($this->manager)
        ->put(route('admin.plants.update', $plant), plantPayload([
            'name' => 'Ficus mis à jour',
            'stock_quantity' => 11,
            'is_active' => '0',
        ]));

    $response->assertRedirect(route('admin.plants.index'));
    $plant->refresh();

    expect($plant->name)->toBe('Ficus mis à jour')
        ->and($plant->stock_quantity)->toBe(11)
        ->and($plant->is_active)->toBeFalse();
});

test('a manager can deactivate a plant without deleting its record', function () {
    $plant = Plant::factory()->create();

    $this->actingAs($this->manager)
        ->patch(route('admin.plants.deactivate', $plant))
        ->assertRedirect(route('admin.plants.index'));

    expect($plant->refresh()->is_active)->toBeFalse()
        ->and($plant->deleted_at)->toBeNull();
});

test('archiving a plant uses soft delete and preserves its row', function () {
    $plant = Plant::factory()->create();

    $this->actingAs($this->manager)
        ->delete(route('admin.plants.destroy', $plant))
        ->assertRedirect(route('admin.plants.index'));

    $this->assertSoftDeleted('plants', ['id' => $plant->id]);
});

test('a manager can browse archived plants and restore one without activating it', function () {
    $plant = Plant::factory()->create([
        'name' => 'Figuier archive',
        'is_active' => true,
    ]);

    $this->actingAs($this->manager)
        ->delete(route('admin.plants.destroy', $plant));

    $this->actingAs($this->manager)
        ->get(route('admin.plants.archived'))
        ->assertOk()
        ->assertSee('Figuier archive');

    $this->actingAs($this->manager)
        ->patch(route('admin.plants.restore', $plant))
        ->assertRedirect(route('admin.plants.archived'))
        ->assertSessionHas('status');

    expect($plant->refresh()->deleted_at)->toBeNull()
        ->and($plant->is_active)->toBeFalse();
});

test('guests cannot browse or restore archived plants', function () {
    $plant = Plant::factory()->create();
    $plant->delete();

    $this->get(route('admin.plants.archived'))->assertRedirect(route('login'));
    $this->patch(route('admin.plants.restore', $plant))->assertRedirect(route('login'));
});
