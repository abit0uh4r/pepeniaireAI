<?php

use App\Enums\AdviceRequestStatus;
use App\Models\AdviceRequest;
use App\Models\Plant;
use App\Models\PlantRecommendation;
use App\Models\User;

test('guests and unverified users cannot access advice request administration', function () {
    $adviceRequest = AdviceRequest::factory()->create();

    $this->get(route('admin.advice-requests.index'))->assertRedirect(route('login'));
    $this->get(route('admin.advice-requests.show', $adviceRequest))->assertRedirect(route('login'));

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('admin.advice-requests.index'))
        ->assertRedirect(route('verification.notice'));
});

test('a manager can filter the advice request history', function () {
    $manager = User::factory()->create();
    AdviceRequest::factory()->create([
        'customer_name' => 'Camille Jardin',
        'customer_phone' => '06 12 34 56 78',
        'status' => AdviceRequestStatus::PENDING,
    ]);
    AdviceRequest::factory()->completed()->create([
        'customer_name' => 'Nora Balcon',
    ]);

    $this->actingAs($manager)
        ->get(route('admin.advice-requests.index', [
            'search' => 'Camille',
            'status' => AdviceRequestStatus::PENDING->value,
        ]))
        ->assertOk()
        ->assertSee('Camille Jardin')
        ->assertSee('06 12 34 56 78')
        ->assertDontSee('Nora Balcon')
        ->assertSee('En attente');
});

test('the advice request history uses the project pagination view', function () {
    $manager = User::factory()->create();
    AdviceRequest::factory()->count(16)->create();

    $this->actingAs($manager)
        ->get(route('admin.advice-requests.index'))
        ->assertOk()
        ->assertSee('Résultats')
        ->assertSee('Suivant')
        ->assertSee('Aller à la page 2');
});

test('a manager can audit the persisted quantity snapshot', function () {
    $manager = User::factory()->create();
    $plant = Plant::factory()->create([
        'name' => 'Ficus audité',
        'price' => '44.00',
        'stock_quantity' => 1,
    ]);
    $adviceRequest = AdviceRequest::factory()->completed()->create([
        'customer_name' => 'Samira',
        'customer_phone' => '0611223344',
        'space_summary' => '<script>alert(1)</script>',
        'general_advice' => 'Conseil contrôlé',
    ]);
    PlantRecommendation::factory()->create([
        'advice_request_id' => $adviceRequest,
        'plant_id' => $plant,
        'reason' => 'Bonne adaptation.',
        'stock_quantity_snapshot' => 6,
    ]);

    $this->actingAs($manager)
        ->get(route('admin.advice-requests.show', $adviceRequest))
        ->assertOk()
        ->assertSee('Ficus audité')
        ->assertSee('Stock observé')
        ->assertSee('6')
        ->assertSee('0611223344')
        ->assertDontSee('Stock courant')
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', escape: false)
        ->assertDontSee('<script>', escape: false);
});

test('the dashboard provides navigation without statistics', function () {
    $manager = User::factory()->create();

    $this->actingAs($manager)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Le jardin, en un regard.')
        ->assertSee('Parcours de démonstration')
        ->assertSee('Gérer les plantes')
        ->assertSee('Consulter les demandes')
        ->assertDontSee('Conseils rendus');
});
