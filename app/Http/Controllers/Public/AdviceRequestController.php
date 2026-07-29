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
use Illuminate\Http\JsonResponse;
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

        return to_route('advice.track', ['token' => $adviceRequest->public_token]);
    }

    public function track(string $token): View
    {
        return view('advice.track', [
            'adviceRequest' => $this->findByToken($token),
        ]);
    }

    public function status(string $token): JsonResponse
    {
        $adviceRequest = $this->findByToken($token);

        return response()->json([
            'status' => $adviceRequest->status->value,
            'label' => $adviceRequest->status->label(),
            'terminal' => $adviceRequest->status->isTerminal(),
            'updated_at' => $adviceRequest->updated_at?->toIso8601String(),
        ]);
    }

    private function findByToken(string $token): AdviceRequest
    {
        return AdviceRequest::query()
            ->where('public_token', $token)
            ->firstOrFail();
    }
}
