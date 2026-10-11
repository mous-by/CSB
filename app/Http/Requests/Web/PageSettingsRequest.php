<?php

namespace App\Http\Requests\Web;

use App\Services\Medias\MediaService;
use App\Support\PhoneNumber;
use App\Support\SiteSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Un onglet de « Pages et paramètres » : seuls les champs de cet onglet sont acceptés, selon leur type
 * (SiteSettings::GROUPS). L'onglet inconnu répond 404, l'onglet non autorisé 403.
 */
class PageSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $group = SiteSettings::GROUPS[$this->route('group')] ?? abort(404);

        return $this->user()?->can($group['permission']) ?? false;
    }

    /** @return array<string, array{0: string, 1: string, 2: int, 3?: string}> */
    private function fields(): array
    {
        return SiteSettings::GROUPS[$this->route('group')]['fields'] ?? [];
    }

    /** Les clés contiennent des points : elles transitent sous forme de clé « aplatie » (contact.email → contact__email). */
    public static function inputName(string $key): string
    {
        return str_replace('.', '__', $key);
    }

    protected function prepareForValidation(): void
    {
        abort_unless(isset(SiteSettings::GROUPS[$this->route('group')]), 404);

        $data = [];
        foreach (array_keys($this->fields()) as $key) {
            $value = $this->input(self::inputName($key));
            if (is_array($value)) {
                $value = null;
            }
            $data[self::inputName($key)] = is_string($value) ? trim(str_replace("\r\n", "\n", $value)) : null;
        }
        $this->merge($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [];
        foreach ($this->fields() as $key => [$label, $type, $max]) {
            if ($type === 'image') {
                $rules[self::inputName($key)] = ['nullable', 'integer', Rule::exists('medias', 'id')->whereNull('deleted_at')];
                $rules[self::inputName($key).'_upload'] = ['nullable', ...MediaService::rules()];

                continue;
            }
            $rule = ['nullable', 'string', "max:{$max}"];
            $rule[] = match ($type) {
                'email' => 'email',
                'url' => 'url:http,https',
                default => null,
            };
            $rules[self::inputName($key)] = array_values(array_filter($rule));
        }

        // La raison sociale, le sigle et le titre de l'accueil ne peuvent pas être vides.
        foreach (['company.name', 'company.short_name', 'home.hero_title'] as $required) {
            if (isset($rules[self::inputName($required)])) {
                $rules[self::inputName($required)][0] = 'required';
            }
        }

        return $rules;
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                foreach ($this->fields() as $key => [$label, $type]) {
                    $value = $this->input(self::inputName($key));
                    if ($type === 'tel' && filled($value) && ! PhoneNumber::isValid($value)) {
                        $validator->errors()->add(self::inputName($key), "Le champ « {$label} » n'est pas un numéro valide (exemple : 74 74 56 69).");
                    }
                    // Plusieurs numéros séparés par « / », « , » ou « ; » (ex. « 74 74 56 69 / 67 20 57 36 ») : chacun est vérifié.
                    if ($type === 'tels' && filled($value)) {
                        foreach (preg_split('/\s*[\/,;]\s*/', trim($value)) as $number) {
                            if ($number !== '' && ! PhoneNumber::isValid($number)) {
                                $validator->errors()->add(self::inputName($key), "« {$number} » n'est pas un numéro valide (exemple : 74 74 56 69 / 67 20 57 36).");
                            }
                        }
                    }
                }
            },
        ];
    }

    public function attributes(): array
    {
        return collect($this->fields())->mapWithKeys(fn ($field, $key) => [self::inputName($key) => mb_strtolower($field[0])])->all();
    }

    public function messages(): array
    {
        $messages = ['*.url' => 'Le champ :attribute doit être une adresse web complète (https://…).'];
        foreach ($this->fields() as $key => $field) {
            if ($field[1] === 'image') {
                $messages += MediaService::messages(self::inputName($key).'_upload');
            }
        }

        return $messages;
    }

    /**
     * Images envoyées directement depuis l'onglet : clé du paramètre => fichier.
     *
     * @return array<string, UploadedFile>
     */
    public function imageUploads(): array
    {
        $uploads = [];
        foreach ($this->fields() as $key => $field) {
            if ($field[1] === 'image' && $this->hasFile(self::inputName($key).'_upload')) {
                $uploads[$key] = $this->file(self::inputName($key).'_upload');
            }
        }

        return $uploads;
    }

    /**
     * Valeurs validées, avec leurs vraies clés (contact.email).
     *
     * @return array<string, ?string>
     */
    public function settings(): array
    {
        $validated = $this->validated();
        $values = [];
        foreach (array_keys($this->fields()) as $settingKey) {
            $value = $validated[self::inputName($settingKey)] ?? null;
            $values[$settingKey] = $value === null ? null : (string) $value;
        }

        return $values;
    }
}
