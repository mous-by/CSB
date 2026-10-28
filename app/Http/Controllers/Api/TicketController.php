<?php

namespace App\Http\Controllers\Api;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Services\Tickets\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * API des applications connectées (KalanNet, DojoManager…) : un utilisateur de l'application ouvre un ticket et suit
 * les réponses de CSB sans compte client. `reporter_ref` est l'identifiant de l'utilisateur dans l'application : il ne
 * voit que ses tickets, et une application ne voit que les tickets ouverts sous son nom (nom du jeton).
 *   POST /api/v1/tickets                            → ouvrir (multipart si pièce jointe)
 *   GET  /api/v1/tickets?reporter_ref=…              → ses tickets
 *   GET  /api/v1/tickets/{numéro}?reporter_ref=…     → fil (sans les notes internes)
 *   POST /api/v1/tickets/{numéro}/messages           → répondre
 *   GET  /api/v1/tickets/{numéro}/messages/{id}/piece-jointe?reporter_ref=…
 */
class TicketController extends Controller
{
    public function __construct(private readonly TicketService $service) {}

    public function options(): JsonResponse
    {
        return response()->json(['categories' => TicketCategory::options(), 'priorities' => TicketPriority::options()]);
    }

    public function index(Request $request): JsonResponse
    {
        $ref = $this->reporterRef($request);

        return response()->json(['data' => Ticket::where('source_app', $this->app($request)->name)->where('reporter_ref', $ref)
            ->latest('last_activity_at')->limit(100)->get()->map(fn (Ticket $t) => $this->summary($t))]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'category' => ['required', Rule::enum(TicketCategory::class)],
            'priority' => ['nullable', Rule::enum(TicketPriority::class)],
            'device' => ['nullable', 'string', 'max:200'],
            'message' => ['required', 'string', 'min:10', 'max:10000'],
            'reporter' => ['required', 'array'],
            'reporter.ref' => ['required', 'string', 'max:100'],
            'reporter.name' => ['required', 'string', 'max:150'],
            'reporter.role' => ['nullable', 'string', 'max:60'],
            'reporter.organization' => ['nullable', 'string', 'max:150'],
            'reporter.email' => ['nullable', 'email', 'max:190'],
            'reporter.phone' => ['nullable', 'string', 'max:20'],
            'attachment' => ['nullable', 'file', 'mimes:'.TicketService::ATTACHMENT_MIMES, 'max:10240'],
        ], ['message.min' => 'Décrivez le problème en quelques phrases.', 'attachment.mimes' => 'Pièce jointe acceptée : PDF, Word, Excel, image, texte ou ZIP.'],
            ['subject' => 'objet', 'category' => 'type de demande', 'priority' => 'urgence', 'device' => 'appareil ou logiciel concerné', 'message' => 'description', 'reporter.name' => 'nom', 'reporter.email' => 'e-mail', 'reporter.phone' => 'téléphone', 'attachment' => 'pièce jointe']);
        $data['priority'] ??= TicketPriority::Normale->value;

        $ticket = $this->service->openExternal($this->app($request), $data, $request->file('attachment'));

        return response()->json(['data' => $this->detail($ticket->fresh())], 201);
    }

    public function show(Request $request, string $number): JsonResponse
    {
        return response()->json(['data' => $this->detail($this->find($request, $number))]);
    }

    public function reply(Request $request, string $number): JsonResponse
    {
        $ticket = $this->find($request, $number);
        $data = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
            'attachment' => ['nullable', 'file', 'mimes:'.TicketService::ATTACHMENT_MIMES, 'max:10240'],
        ], [], ['body' => 'message', 'attachment' => 'pièce jointe']);

        $this->service->reporterReply($ticket, $data['body'], $request->file('attachment'));

        return response()->json(['data' => $this->detail($ticket->fresh())], 201);
    }

    public function attachment(Request $request, string $number, TicketMessage $message): StreamedResponse
    {
        $ticket = $this->find($request, $number);
        abort_unless($message->ticket_id === $ticket->id && ! $message->is_internal && $message->attachment_path && Storage::disk('local')->exists($message->attachment_path), 404);

        return Storage::disk('local')->download($message->attachment_path, $message->attachment_name);
    }

    private function app(Request $request): ApiToken
    {
        return $request->attributes->get('api_token');
    }

    private function reporterRef(Request $request): string
    {
        return $request->validate(['reporter_ref' => ['required', 'string', 'max:100']])['reporter_ref'];
    }

    private function find(Request $request, string $number): Ticket
    {
        return Ticket::where('source_app', $this->app($request)->name)->where('reporter_ref', $this->reporterRef($request))
            ->where('number', $number)->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function summary(Ticket $ticket): array
    {
        return [
            'number' => $ticket->number, 'subject' => $ticket->subject,
            'category' => $ticket->category->value, 'category_label' => $ticket->category->label(),
            'priority' => $ticket->priority->value, 'priority_label' => $ticket->priority->label(),
            'status' => $ticket->status->value, 'status_label' => $ticket->status->label(), 'is_open' => $ticket->status->isOpen(),
            'awaiting_reporter' => ! $ticket->awaiting_team && $ticket->status->isOpen(),
            'device' => $ticket->device, 'created_at' => $ticket->created_at?->toIso8601String(),
            'last_activity_at' => $ticket->last_activity_at?->toIso8601String(), 'closed_at' => $ticket->closed_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function detail(Ticket $ticket): array
    {
        return $this->summary($ticket) + ['messages' => $ticket->publicMessages()->with('author')->get()->map(fn (TicketMessage $m) => [
            'id' => $m->id, 'from' => $m->fromClient() ? 'reporter' : 'csb',
            'author' => $m->fromClient() ? $ticket->requesterName() : ($m->author?->displayName() ?? 'Équipe CSB'),
            'body' => $m->body, 'created_at' => $m->created_at->toIso8601String(),
            'attachment' => $m->attachment_path ? ['name' => $m->attachment_name, 'size' => $m->attachment_size] : null,
        ])->values()];
    }
}
