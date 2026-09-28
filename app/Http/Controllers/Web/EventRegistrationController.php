<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class EventRegistrationController extends Controller
{
    public function create(Event $event): View
    {
        abort_unless($event->is_published, 404);

        return view('web.events.register', compact('event'));
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        abort_unless($event->is_published, 404);
        if ($request->filled('website')) {
            return back()->withInput();
        }

        $rules = [];
        foreach ($event->registration_fields as $field) {
            $rule = $field['required'] ? ['required'] : ['nullable'];
            $rule[] = match ($field['type']) {
                'email' => 'email', 'date' => 'date', default => 'string',
            };
            $rule[] = 'max:'.($field['type'] === 'textarea' ? '3000' : '255');
            if ($field['type'] === 'select') {
                $rule[] = Rule::in($field['options']);
            }
            $rules['answers.'.$field['key']] = $rule;
        }
        $answers = $request->validate($rules)['answers'] ?? [];
        $nameField = collect($event->registration_fields)->first(fn ($field) => $field['key'] === 'name') ?? $event->registration_fields[0];
        $attendeeName = trim((string) ($answers[$nameField['key']] ?? 'Peserta Event'));
        $registration = $event->registrations()->create([
            'ticket_code' => (string) Str::ulid(),
            'attendee_name' => $attendeeName,
            'answers' => $answers,
        ]);

        return redirect()->route('events.ticket', $registration->ticket_code);
    }

    public function ticket(EventRegistration $registration, QrCodeService $qr): View
    {
        $registration->load('event');

        return view('web.events.ticket', ['registration' => $registration, 'qrCode' => $qr->dataUri(route('admin.event-registrations.verify', $registration->ticket_code))]);
    }

    public function download(EventRegistration $registration, QrCodeService $qr): Response
    {
        $registration->load('event');
        $escape = fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $event = $escape($registration->event->title);
        $name = $escape($registration->attendee_name);
        $date = $escape($registration->event->starts_at->translatedFormat('d F Y, H:i').' WIB');
        $code = $escape($registration->ticket_code);
        $qrData = $qr->dataUri(route('admin.event-registrations.verify', $registration->ticket_code), 420);
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630">
<rect width="1200" height="630" rx="42" fill="#f5f5f7"/><rect x="28" y="28" width="1144" height="574" rx="30" fill="#fff" stroke="#d1d1d6"/>
<rect x="28" y="28" width="330" height="574" rx="30" fill="#7d1729"/><text x="76" y="105" font-family="-apple-system,BlinkMacSystemFont,Arial" font-size="24" font-weight="700" fill="#ffcf79">JKI HANANEEL CINTA</text>
<text x="76" y="180" font-family="-apple-system,BlinkMacSystemFont,Arial" font-size="44" font-weight="700" fill="#fff">TIKET EVENT</text><image href="{$qrData}" x="68" y="245" width="250" height="250"/>
<text x="76" y="550" font-family="monospace" font-size="15" fill="#fff">{$code}</text>
<text x="415" y="130" font-family="-apple-system,BlinkMacSystemFont,Arial" font-size="22" font-weight="600" fill="#636366">{$event}</text>
<text x="415" y="245" font-family="-apple-system,BlinkMacSystemFont,Arial" font-size="54" font-weight="700" fill="#1d1d1f">{$name}</text>
<text x="415" y="315" font-family="-apple-system,BlinkMacSystemFont,Arial" font-size="25" fill="#636366">{$date}</text>
<line x1="415" y1="380" x2="1110" y2="380" stroke="#d1d1d6"/><text x="415" y="440" font-family="-apple-system,BlinkMacSystemFont,Arial" font-size="20" fill="#636366">Tunjukkan QR ini kepada petugas saat daftar ulang.</text>
</svg>
SVG;

        return response($svg, 200, ['Content-Type' => 'image/svg+xml', 'Content-Disposition' => 'attachment; filename="tiket-'.$registration->ticket_code.'.svg"']);
    }
}
