<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\TenantRobotsConfig;

class TenantRobotsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // A autorização é feita pelo middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_agent' => 'required|string|max:255',
            'allow' => 'nullable|array',
            'allow.*' => 'string|max:255',
            'disallow' => 'nullable|array',
            'disallow.*' => 'string|max:255',
            'crawl_delay' => 'nullable|integer|min:0',
            'sitemap' => 'required|array|min:1',
            'sitemap.*' => 'url|max:500',
            'is_active' => 'boolean'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Verificar se pelo menos um sitemap é válido
            if ($this->has('sitemap') && is_array($this->sitemap)) {
                $validSitemaps = 0;
                foreach ($this->sitemap as $sitemapUrl) {
                    if (filter_var($sitemapUrl, FILTER_VALIDATE_URL)) {
                        $validSitemaps++;
                    }
                }

                if ($validSitemaps === 0) {
                    $validator->errors()->add('sitemap', 'Pelo menos uma URL de sitemap válida deve ser fornecida');
                }
            }

            // Verificar se user_agent já existe para o tenant
            if ($this->has('user_agent')) {
                $query = TenantRobotsConfig::forTenant($this->user()->tenant_id)
                    ->where('user_agent', $this->user_agent);

                // Para updates, excluir o próprio registro
                if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
                    $query->where('id', '!=', $this->route('id'));
                }

                if ($query->exists()) {
                    $validator->errors()->add('user_agent', 'Este user-agent já está sendo usado');
                }
            }

            // Validar paths de allow/disallow
            $this->validatePaths($validator, 'allow');
            $this->validatePaths($validator, 'disallow');
        });
    }

    /**
     * Validar paths de allow/disallow
     */
    private function validatePaths($validator, string $field): void
    {
        if ($this->has($field) && is_array($this->$field)) {
            foreach ($this->$field as $index => $path) {
                // Paths devem começar com /
                if (!empty($path) && !str_starts_with($path, '/')) {
                    $validator->errors()->add("{$field}.{$index}", "O path deve começar com '/'");
                }

                // Paths não podem ter caracteres inválidos
                if (!empty($path) && preg_match('/[<>"\']/', $path)) {
                    $validator->errors()->add("{$field}.{$index}", "O path contém caracteres inválidos");
                }
            }
        }
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_agent.required' => 'O user-agent é obrigatório',
            'user_agent.max' => 'O user-agent não pode ter mais de 255 caracteres',
            'allow.array' => 'Allow deve ser um array',
            'allow.*.string' => 'Cada item de allow deve ser uma string',
            'allow.*.max' => 'Cada item de allow não pode ter mais de 255 caracteres',
            'disallow.array' => 'Disallow deve ser um array',
            'disallow.*.string' => 'Cada item de disallow deve ser uma string',
            'disallow.*.max' => 'Cada item de disallow não pode ter mais de 255 caracteres',
            'crawl_delay.integer' => 'Crawl delay deve ser um número inteiro',
            'crawl_delay.min' => 'Crawl delay deve ser pelo menos 0',
            'sitemap.required' => 'Pelo menos um sitemap é obrigatório',
            'sitemap.array' => 'Sitemap deve ser um array',
            'sitemap.min' => 'Pelo menos um sitemap deve ser fornecido',
            'sitemap.*.url' => 'Cada URL de sitemap deve ser válida',
            'sitemap.*.max' => 'Cada URL de sitemap não pode ter mais de 500 caracteres'
        ];
    }
}
