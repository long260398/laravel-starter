<?php

namespace App\Http\Controllers;

use App\Models\StaticPage;
use Illuminate\Contracts\View\View;

class StaticPageController extends Controller
{
    private const string SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /**
     * Tham số phải tên là `$fallbackPlaceholder` — đây là route fallback
     * (xem routes/web.php), Laravel luôn đặt tên param theo quy ước đó.
     */
    public function show(string $fallbackPlaceholder): View
    {
        if (preg_match(self::SLUG_PATTERN, $fallbackPlaceholder) !== 1) {
            abort(404);
        }

        $page = StaticPage::query()->published()->where('slug', $fallbackPlaceholder)->firstOrFail();

        return view('static-pages.show', [
            'page' => $page,
        ]);
    }
}
