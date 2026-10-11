<?php

namespace App\Http\Requests\Site;

use App\Enums\DemandeType;
use App\Enums\NeedType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Formulaires publics (cahier §6.2 à §6.4) : nom, au moins un moyen de réponse (e-mail ou téléphone), information sur
 * l'utilisation des données acceptée, puis les champs propres à chaque demande. La pièce jointe d'un devis est
 * limitée en type réel, extension et taille (cahier §6.3, §10).
 */
class DemandeRequest extends FormRequest
{
    public const ATTACHMENT_MIMES = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'odt', 'ods', 'jpg', 'jpeg', 'png'];

    public const ATTACHMENT_MAX_KB = 5120;

    public function authorize(): bool
    {
        return true;
    }

    public function demandeType(): DemandeType
    {
        return DemandeType::from((string) $this->route()->defaults['demande_type']);
    }

    protected function prepareForValidation(): void
    {
        $clean = [];
        foreach (['name', 'organization', 'email', 'phone', 'subject', 'message', 'budget', 'deadline', 'availability', 'source_url'] as $field) {
            $value = $this->input($field);
            $clean[$field] = is_string($value) && trim($value) !== '' ? trim(str_replace("\r\n", "\n", $value)) : null;
        }
        $this->merge($clean);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'organization' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'required_without:phone', 'email', 'max:190'],
            'phone' => ['nullable', 'required_without:email', 'string', 'max:30', 'regex:/^\+?[0-9][0-9 .\-()]{6,28}$/'],
            'consent' => ['accepted'],
            'source_url' => ['nullable', 'string', 'max:500'],
        ];

        return $rules + match ($this->demandeType()) {
            DemandeType::Contact => [
                'subject' => ['required', 'string', 'max:200'],
                'message' => ['required', 'string', 'min:10', 'max:5000'],
            ],
            DemandeType::Devis => [
                'need_type' => ['required', Rule::enum(NeedType::class)],
                'message' => ['required', 'string', 'min:10', 'max:5000'],
                'budget' => ['nullable', 'string', 'max:100'],
                'deadline' => ['nullable', 'string', 'max:100'],
                'attachment' => ['nullable', 'file', 'mimes:'.implode(',', self::ATTACHMENT_MIMES), 'extensions:'.implode(',', self::ATTACHMENT_MIMES), 'max:'.self::ATTACHMENT_MAX_KB],
            ],
            DemandeType::Demonstration => [
                'message' => ['required', 'string', 'min:5', 'max:3000'],
                'availability' => ['nullable', 'string', 'max:300'],
            ],
            DemandeType::Disponibilite => [
                'message' => ['nullable', 'string', 'max:2000'],
            ],
        };
    }

    public function messages(): array
    {
        return [
            'email.required_without' => 'Indiquez au moins un moyen de vous répondre : e-mail ou téléphone.',
            'phone.required_without' => 'Indiquez au moins un moyen de vous répondre : téléphone ou e-mail.',
            'phone.regex' => 'Ce numéro de téléphone ne semble pas valide (ex. 74 74 56 69).',
            'consent.accepted' => 'Merci de confirmer avoir pris connaissance de l\'utilisation de vos données.',
            'attachment.mimes' => 'Pièce jointe acceptée : PDF, Word, Excel, OpenDocument, JPEG ou PNG.',
            'attachment.extensions' => 'Pièce jointe acceptée : PDF, Word, Excel, OpenDocument, JPEG ou PNG.',
            'attachment.max' => 'La pièce jointe ne doit pas dépasser '.(self::ATTACHMENT_MAX_KB / 1024).' Mo.',
            'message.min' => 'Votre message est un peu court : précisez votre besoin.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nom complet', 'organization' => 'organisation', 'email' => 'e-mail', 'phone' => 'téléphone', 'subject' => 'objet',
            'message' => $this->demandeType() === DemandeType::Contact ? 'message' : 'description du besoin', 'need_type' => 'type de besoin',
            'budget' => 'budget', 'deadline' => 'délai', 'availability' => 'disponibilités', 'attachment' => 'pièce jointe',
        ];
    }
}
