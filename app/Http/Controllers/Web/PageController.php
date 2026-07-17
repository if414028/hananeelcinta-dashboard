<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\WebsiteSettings;
use Illuminate\View\View;

final class PageController extends Controller
{
    public function about(WebsiteSettings $settings): View
    {
        return view('web.about', ['settings' => $settings->public()]);
    }

    public function contact(WebsiteSettings $settings): View
    {
        return view('web.contact', ['settings' => $settings->public()]);
    }

    public function privacy(WebsiteSettings $settings): View
    {
        return view('web.privacy', ['settings' => $settings->public()]);
    }

    public function terms(WebsiteSettings $settings): View
    {
        return view('web.terms', ['settings' => $settings->public()]);
    }
}
