<?php

namespace App\Modules\Services\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Services\Models\Service;
use App\Modules\Services\Models\ServiceCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $categories = ServiceCategory::query()->published()->get();

        $services = Service::query()->published()
            ->when(
                $request->string('category')->isNotEmpty(),
                fn ($query) => $query->whereHas(
                    'category',
                    fn ($query) => $query->where('slug', $request->string('category')),
                ),
            )
            ->orderBy('sort_order')
            ->get();

        return view('services::index', [
            'categories' => $categories,
            'services' => $services,
            'activeCategory' => $request->string('category')->toString(),
        ]);
    }

    public function show(string $slug): View
    {
        $service = Service::query()->published()->where('slug', $slug)->firstOrFail();

        return view('services::show', [
            'service' => $service,
        ]);
    }
}
