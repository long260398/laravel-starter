<?php

namespace App\Http\Controllers;

use App\Models\StaticPage;
use Illuminate\Contracts\View\View;

class StaticPageController extends Controller
{
    public function show(string $slug): View
    {
        $page = StaticPage::query()->published()->where('slug', $slug)->firstOrFail();

        return view('static-pages.show', [
            'page' => $page,
        ]);
    }
}
