<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdviceRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(): View
    {
        Gate::authorize('viewAny', AdviceRequest::class);

        return view('dashboard');
    }
}
