<?php

namespace App\Modules\Faq\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Faq\Actions\BuildFaqSchema;
use App\Modules\Faq\Models\Faq;
use Illuminate\Contracts\View\View;

class FaqController extends Controller
{
    public function index(BuildFaqSchema $buildFaqSchema): View
    {
        $faqs = Faq::query()->published()->orderBy('sort_order')->get();

        return view('faq::index', [
            'faqs' => $faqs,
            'faqSchema' => $buildFaqSchema->handle($faqs),
        ]);
    }
}
