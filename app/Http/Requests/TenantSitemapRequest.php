<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\TenantSitemapConfig;

class TenantSitemapRequest extends FormRequest
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
        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|in:index,images,videos,articles,vehicles,pages',
            'url' => 'required|url|max:500',
            'is_active' => 'boolean',
            'priority' => 'numeric|min:0|max:1',
            'change_frequency' => 'required|in:always,hourly,daily,weekly,monthly,yearly,never',
            'config_data' => 'nullable|array'
        ];

        // Para updates, tornar campos opcionais
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = array_map(function ($rule) {
                if (is_string($rule) && strpos($rule, 'required') === 0) {
                    return 'sometimes|' . substr($rule, 9);
                }
                return $rule;
            }, $rules);
        }

        return $rules;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Verificar se URL já existe para o tenant
            if ($this->has('url')) {
                $query = TenantSitemapConfig::forTenant($this->user()->tenant_id)
                    ->where('url', $this->url);

                // Para updates, excluir o próprio registro
                if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
                    $query->where('id', '!=', $this->route('id'));
                }

                if ($query->exists()) {
                    $validator->errors()->add('url', 'Esta URL já está sendo usada');
                }
            }

            // Validar config_data baseado no tipo
            if ($this->has('type') && $this->has('config_data')) {
                $this->validateConfigData($validator);
            }
        });
    }

    /**
     * Validar config_data baseado no tipo
     */
    private function validateConfigData($validator): void
    {
        $type = $this->type;
        $configData = $this->config_data ?? [];

        switch ($type) {
            case 'vehicles':
                if (isset($configData['max_vehicles']) && (!is_numeric($configData['max_vehicles']) || $configData['max_vehicles'] < 1)) {
                    $validator->errors()->add('config_data.max_vehicles', 'Max vehicles deve ser um número maior que 0');
                }
                break;

            case 'images':
                if (isset($configData['max_images']) && (!is_numeric($configData['max_images']) || $configData['max_images'] < 1)) {
                    $validator->errors()->add('config_data.max_images', 'Max images deve ser um número maior que 0');
                }
                if (isset($configData['image_types']) && !is_array($configData['image_types'])) {
                    $validator->errors()->add('config_data.image_types', 'Image types deve ser um array');
                }
                break;

            case 'videos':
                if (isset($configData['max_videos']) && (!is_numeric($configData['max_videos']) || $configData['max_videos'] < 1)) {
                    $validator->errors()->add('config_data.max_videos', 'Max videos deve ser um número maior que 0');
                }
                if (isset($configData['video_types']) && !is_array($configData['video_types'])) {
                    $validator->errors()->add('config_data.video_types', 'Video types deve ser um array');
                }
                break;

            case 'articles':
                if (isset($configData['max_articles']) && (!is_numeric($configData['max_articles']) || $configData['max_articles'] < 1)) {
                    $validator->errors()->add('config_data.max_articles', 'Max articles deve ser um número maior que 0');
                }
                break;
        }
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório',
            'name.max' => 'O nome não pode ter mais de 255 caracteres',
            'type.required' => 'O tipo é obrigatório',
            'type.in' => 'O tipo deve ser um dos valores permitidos',
            'url.required' => 'A URL é obrigatória',
            'url.url' => 'A URL deve ter um formato válido',
            'url.max' => 'A URL não pode ter mais de 500 caracteres',
            'priority.numeric' => 'A prioridade deve ser um número',
            'priority.min' => 'A prioridade deve ser pelo menos 0.0',
            'priority.max' => 'A prioridade deve ser no máximo 1.0',
            'change_frequency.required' => 'A frequência de mudança é obrigatória',
            'change_frequency.in' => 'A frequência deve ser um dos valores permitidos',
            'config_data.array' => 'Os dados de configuração devem ser um objeto'
        ];
    }
}
