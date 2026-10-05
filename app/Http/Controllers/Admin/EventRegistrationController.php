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
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($search) => $search->where('attendee_name', 'like', '%'.$request->string('search').'%')->orWhere('ticket_code', $request->string('search'))))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.events.registrations', compact('event', 'registrations'));
    }

    public function manual(Request $request, Event $event): View
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:all,present,pending'],
        ]);
        $status = $request->input('status') ?: 'all';
        $registrations = $event->registrations()->with('checkedInBy')
            ->when($status === 'present', fn ($query) => $query->whereNotNull('checked_in_at'))
            ->when($status === 'pending', fn ($query) => $query->whereNull('checked_in_at'))
            ->when($request->filled('search'), fn ($query) => $query->where('attendee_name', 'like', '%'.$request->string('search')->trim().'%'))
            ->orderBy('attendee_name')->orderBy('id')->paginate(25)->withQueryString();
        $totalRegistrations = $event->registrations()->count();
        $presentRegistrations = $event->registrations()->whereNotNull('checked_in_at')->count();
        $statusCounts = ['all' => $totalRegistrations, 'present' => $presentRegistrations, 'pending' => $totalRegistrations - $presentRegistrations];

        return view('admin.events.manual', compact('event', 'registrations', 'totalRegistrations', 'status', 'statusCounts'));
    }

    public function manualCheckIn(Request $request, Event $event, EventRegistration $registration): RedirectResponse
    {
        abort_unless($registration->event_id === $event->id, 404);

        return $this->checkIn($request, $registration);
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
        $updated = EventRegistration::query()->whereKey($registration->id)->whereNull('checked_in_at')
            ->update(['checked_in_at' => now(), 'checked_in_by' => $request->user()->id]);

        if (! $updated) {
            return back()->with('success', 'Peserta ini sudah check-in sebelumnya.');
        }

        return back()->with('success', 'Check-in berhasil dicatat.');
    }
}
