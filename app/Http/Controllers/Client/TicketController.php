<?php

namespace App\Http\Controllers\Client;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Services\Tickets\TicketService;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tickets d'assistance dans l'espace client : liste, ouverture, fil d'échanges (sans les notes internes), réponse,
 * pièces jointes. Un client ne voit que ses tickets.
 */
class TicketController extends Controller
{
    public function __construct(private readonly TicketService $service) {}

    public function index(Request $request): View
    {
        return view('client.tickets.index', [
            'tickets' => Ticket::where('client_id', $request->user('client')->id)->latest('last_activity_at')->paginate(15),
            'seo' => Seo::make('Mes tickets')->noIndex(),
        ]);
    }

    public function create(): View
    {
        return view('client.tickets.create', ['seo' => Seo::make('Nouvelle demande d\'assistance')->noIndex()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'category' => ['required', Rule::enum(TicketCategory::class)],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'device' => ['nullable', 'string', 'max:200'],
            'message' => ['required', 'string', 'min:10', 'max:10000'],
            'attachment' => ['nullable', 'file', 'mimes:'.TicketService::ATTACHMENT_MIMES, 'max:10240'],
        ], ['attachment.mimes' => 'Pièce jointe acceptée : PDF, Word, Excel, image, texte ou ZIP.', 'message.min' => 'Décrivez le problème en quelques phrases.'],
            ['subject' => 'objet', 'category' => 'type de demande', 'priority' => 'urgence', 'device' => 'appareil ou logiciel concerné', 'message' => 'description', 'attachment' => 'pièce jointe']);

        $ticket = $this->service->open($request->user('client'), $data, $request->file('attachment'));

        return redirect()->route('client.tickets.show', $ticket)->with('status', "Votre demande {$ticket->number} est enregistrée : nous vous répondons au plus vite.");
    }

    public function show(Request $request, Ticket $ticket): View
    {
        $this->own($request, $ticket);

        return view('client.tickets.show', [
            'ticket' => $ticket->load(['publicMessages.author', 'publicMessages.client']),
            'seo' => Seo::make($ticket->number)->noIndex(),
        ]);
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->own($request, $ticket);
        $data = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
            'attachment' => ['nullable', 'file', 'mimes:'.TicketService::ATTACHMENT_MIMES, 'max:10240'],
        ], [], ['body' => 'message', 'attachment' => 'pièce jointe']);

        $this->service->clientReply($ticket, $request->user('client'), $data['body'], $request->file('attachment'));

        return redirect()->route('client.tickets.show', $ticket)->with('status', 'Message envoyé.');
    }

    public function attachment(Request $request, Ticket $ticket, TicketMessage $message): StreamedResponse
    {
        $this->own($request, $ticket);
        abort_unless($message->ticket_id === $ticket->id && ! $message->is_internal && $message->attachment_path && Storage::disk('local')->exists($message->attachment_path), 404);

        return Storage::disk('local')->download($message->attachment_path, $message->attachment_name);
    }

    private function own(Request $request, Ticket $ticket): void
    {
        abort_unless($ticket->client_id === $request->user('client')->id, 404);
    }
}
