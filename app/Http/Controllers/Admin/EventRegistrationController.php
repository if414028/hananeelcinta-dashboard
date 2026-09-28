<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class EventRegistrationController extends Controller
{
    public function index(Request $request, Event $event): View
    {
        $registrations = $event->registrations()->with('checkedInBy')
            ->when($request->filled('search'), fn ($query) => $query->where('attendee_name', 'like', '%'.$request->string('search').'%')->orWhere('ticket_code', $request->string('search')))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.events.registrations', compact('event', 'registrations'));
    }

    public function scanner(Event $event): View
    {
        return view('admin.events.scanner', compact('event'));
    }

    public function verify(EventRegistration $registration): View
    {
        $registration->load(['event', 'checkedInBy']);

        return view('admin.events.verify', compact('registration'));
    }

    public function checkIn(Request $request, EventRegistration $registration): RedirectResponse
    {
        if ($registration->checked_in_at) {
            return back()->with('success', 'Peserta ini sudah check-in sebelumnya.');
        }
        $registration->update(['checked_in_at' => now(), 'checked_in_by' => $request->user()->id]);

        return back()->with('success', 'Check-in berhasil dicatat.');
    }
}
