<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class TenantLocationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // 👇 Add this logging BEFORE the switch
        Log::info('TenantLocationRequest - Validation starting:', [
            'action_method' => $this->route()->getActionMethod(),
            'all_input' => $this->all(),
            'user_tenant_id' => $this->user()?->tenant_id,
        ]);

        $rules = [];

        switch ($this->route()->getActionMethod()) {
            case 'addCountry':
                $rules = [
                    'country_id' => [
                        'required',
                        'integer',
                        'exists:countries,id',
                        Rule::unique('tenant_countries')->where(function ($query) {
                            return $query->where('tenant_id', $this->user()->tenant_id);
                        })
                    ],
                    'is_active' => 'boolean'
                ];
                break;

            case 'addState':
                $rules = [
                    'state_id' => [
                        'required',
                        'integer',
                        'exists:states,id',
                        Rule::unique('tenant_states')->where(function ($query) {
                            return $query->where('tenant_id', $this->user()->tenant_id);
                        })
                    ],
                    'is_active' => 'boolean'
                ];
                break;

            case 'addCity':
                $rules = [
                    'city_id' => [
                        'required',
                        'integer',
                        'exists:cities,id',
                        Rule::unique('tenant_cities')->where(function ($query) {
                            return $query->where('tenant_id', $this->user()->tenant_id);
                        })
                    ],
                    'is_active' => 'boolean'
                ];
                // 👇 Add this to check if city exists
                Log::info('addCity validation - Checking city:', [
                    'city_id_from_request' => $this->city_id,
                    'tenant_id' => $this->user()?->tenant_id,
                ]);
                break;
                


            case 'addNeighborhood':
                $rules = [
                    'neighborhood_id' => [
                        'required',
                        'integer',
                        'exists:neighborhoods,id',
                        Rule::unique('tenant_neighborhoods')->where(function ($query) {
                            return $query->where('tenant_id', $this->user()->tenant_id);
                        })
                    ],
                    'is_active' => 'boolean'
                ];
                break;

            case 'updateCountry':
            case 'updateState':
            case 'updateCity':
            case 'updateNeighborhood':
                $rules = [
                    'is_active' => 'boolean'
                ];
                break;
        }

        return $rules;        
    }

        // 👇 ADD THIS METHOD to see exactly which validation fails
    protected function failedValidation(Validator $validator)
    {
        Log::error('TenantLocationRequest - VALIDATION FAILED:', [
            'errors' => $validator->errors()->toArray(),
            'input_data' => $this->all(),
            'action_method' => $this->route()->getActionMethod(),
            'user_tenant_id' => $this->user()?->tenant_id,
        ]);

        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'country_id.required' => 'O ID do país é obrigatório.',
            'country_id.exists' => 'O país selecionado não existe.',
            'country_id.unique' => 'Este país já está associado ao tenant.',

            'state_id.required' => 'O ID do estado é obrigatório.',
            'state_id.exists' => 'O estado selecionado não existe.',
            'state_id.unique' => 'Este estado já está associado ao tenant.',

            'city_id.required' => 'O ID da cidade é obrigatório.',
            'city_id.exists' => 'A cidade selecionada não existe.',
            'city_id.unique' => 'Esta cidade já está associada ao tenant.',

            'neighborhood_id.required' => 'O ID do bairro é obrigatório.',
            'neighborhood_id.exists' => 'O bairro selecionado não existe.',
            'neighborhood_id.unique' => 'Este bairro já está associado ao tenant.',

            'is_active.boolean' => 'O status ativo deve ser verdadeiro ou falso.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'country_id' => 'país',
            'state_id' => 'estado',
            'city_id' => 'cidade',
            'neighborhood_id' => 'bairro',
            'is_active' => 'status ativo'
        ];
    }
}
