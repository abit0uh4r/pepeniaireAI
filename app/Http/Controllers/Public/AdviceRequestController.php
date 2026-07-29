<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\Exposure;
use App\Enums\Level;
use App\Enums\PlantEnvironment;
use App\Enums\SpaceSize;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreAdviceRequest;
use App\Models\AdviceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdviceRequestController extends Controller
{
    public function create(): View
    {
        return view('advice.create', [
            'environments' => [PlantEnvironment::INDOOR, PlantEnvironment::OUTDOOR],
            'exposures' => Exposure::cases(),
            'spaceSizes' => SpaceSize::cases(),
            'levels' => Level::cases(),
        ]);
    }

    public function store(StoreAdviceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['consent']);

        $adviceRequest = AdviceRequest::query()->create($data);

        return to_route('advice.create')
            ->with('status', 'Votre demande est enregistrée. Le suivi sera disponible dès le lancement du traitement.')
            ->with('advice_token', $adviceRequest->public_token);
    }
}
