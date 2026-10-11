<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\RoleName;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\Tickets\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tickets d'assistance (cahier §15.3) : liste filtrable, fil d'échanges, réponse publique ou note interne, statut,
 * priorité, attribution, pièces jointes (disque privé).
 */
class TicketController extends Controller
{
    public function __construct(private readonly TicketService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Ticket::class);

        return view('tickets.index', ['tickets' => Ticket::with(['client', 'assignee'])->latest('last_activity_at')->get()]);
    }

    public function show(Ticket $ticket): View
    {
        Gate::authorize('view', $ticket);

        return view('tickets.show', [
            'ticket' => $ticket->load(['client', 'assignee', 'messages.author', 'messages.client']),
            'history' => ActivityLog::where('subject_type', $ticket->getMorphClass())->where('subject_id', $ticket->id)->latest('id')->get(),
            'assignees' => User::query()->active()->whereHas('roles', fn ($q) => $q->whereIn('name', [RoleName::Commercial->value, RoleName::Administrateur->value, RoleName::Gestionnaire->value]))->orderBy('name')->get(),
        ]);
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('update', $ticket);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
            'internal' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::enum(TicketStatus::class)],
            'attachment' => ['nullable', 'file', 'mimes:'.TicketService::ATTACHMENT_MIMES, 'max:10240'],
        ], [], ['body' => 'message', 'status' => 'statut', 'attachment' => 'pièce jointe']);

        $internal = (bool) ($data['internal'] ?? false);
        $this->service->teamReply($ticket, $request->user(), $data['body'], $internal, isset($data['status']) ? TicketStatus::from($data['status']) : null, $request->file('attachment'));

        return redirect()->route('tickets.show', $ticket)->with('status', $internal ? 'Note interne ajoutée.' : 'Réponse envoyée au client.');
    }

    public function status(Request $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('update', $ticket);

        $data = $request->validate(['status' => ['required', Rule::enum(TicketStatus::class)]], [], ['status' => 'statut']);
        $status = TicketStatus::from($data['status']);
        $this->service->changeStatus($ticket, $status, $request->user());

        return redirect()->route('tickets.show', $ticket)->with('status', "Statut : {$status->teamLabel()}.");
    }

    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('update', $ticket);

        $data = $request->validate([
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
        ], [], ['priority' => 'priorité', 'assigned_to' => 'suivi par']);

        $this->service->update($ticket, TicketPriority::from($data['priority']), isset($data['assigned_to']) ? User::find($data['assigned_to']) : null);

        return redirect()->route('tickets.show', $ticket)->with('status', 'Ticket mis à jour.');
    }

    public function attachment(Ticket $ticket, TicketMessage $message): StreamedResponse
    {
        Gate::authorize('view', $ticket);
        abort_unless($message->ticket_id === $ticket->id && $message->attachment_path && Storage::disk('local')->exists($message->attachment_path), 404);

        return Storage::disk('local')->download($message->attachment_path, $message->attachment_name);
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        Gate::authorize('delete', $ticket);

        $ticket->delete();

        return redirect()->route('tickets.index')->with('status', "Ticket {$ticket->number} supprimé.");
    }
}
