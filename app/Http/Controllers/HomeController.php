<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Post;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('home', [
            'sliderBanners' => Banner::query()->visibleAt('home_slider')->get(),
            'middleBanners' => Banner::query()->visibleAt('home_middle')->get(),
            'latestPosts' => Post::query()->published()->latest('published_at')->take(6)->get(),
        ]);
    }
}
