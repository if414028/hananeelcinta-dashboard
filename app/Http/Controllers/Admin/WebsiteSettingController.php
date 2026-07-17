<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateWebsiteSettingRequest;
use App\Models\WebsiteSetting;
use App\Services\HtmlSanitizer;
use App\Services\WebsiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class WebsiteSettingController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.index', ['groups' => WebsiteSetting::query()->orderBy('group')->orderBy('id')->get()->groupBy('group')]);
    }

    public function update(UpdateWebsiteSettingRequest $request, HtmlSanitizer $sanitizer, WebsiteSettings $settings): RedirectResponse
    {
        DB::transaction(function () use ($request, $sanitizer) {
            $richTextKeys = WebsiteSetting::query()->where('type', 'richtext')->pluck('key')->all();
            foreach ($request->validated('settings') as $key => $value) {
                if (in_array($key, $richTextKeys, true) && is_string($value)) {
                    $value = $sanitizer->sanitize($value);
                }
                WebsiteSetting::query()->where('key', $key)->update(['value' => $value]);
            }
        });
        $settings->forget();
        activity('settings')->causedBy($request->user())->event('updated')->log('Website settings diperbarui');

        return back()->with('success', 'Pengaturan website berhasil diperbarui.');
    }
}
