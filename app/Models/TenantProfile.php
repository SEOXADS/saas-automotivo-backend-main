<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'company_name',
        'company_description',
        'company_cnpj',
        'company_phone',
        'company_email',
        'company_website',
        'address_street',
        'address_number',
        'address_complement',
        'address_district',
        'address_city',
        'address_state',
        'address_zipcode',
        'address_country',
        'business_hours',
        'social_media',
        'logo_url',
        'favicon_url',
        'banner_url'
    ];

    protected $casts = [
        'business_hours' => 'array',
        'social_media' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Relacionamento com o tenant
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Obter endereço completo formatado
     */
    public function getFullAddressAttribute(): string
    {
        $parts = [];

        if ($this->address_street) {
            $parts[] = $this->address_street;
            if ($this->address_number) {
                $parts[] = $this->address_number;
            }
            if ($this->address_complement) {
                $parts[] = $this->address_complement;
            }
        }

        if ($this->address_district) {
            $parts[] = $this->address_district;
        }

        if ($this->address_city) {
            $parts[] = $this->address_city;
        }

        if ($this->address_state) {
            $parts[] = $this->address_state;
        }

        if ($this->address_zipcode) {
            $parts[] = $this->address_zipcode;
        }

        return implode(', ', $parts);
    }

    /**
     * Obter horário de funcionamento para um dia específico
     */
    public function getBusinessHoursForDay(string $day): ?array
    {
        $businessHours = $this->business_hours ?? [];
        return $businessHours[$day] ?? null;
    }

    /**
     * Verificar se está aberto em um dia específico
     */
    public function isOpenOnDay(string $day): bool
    {
        $dayConfig = $this->getBusinessHoursForDay($day);

        if (!$dayConfig) {
            return false;
        }

        return !($dayConfig['closed'] ?? false);
    }

    /**
     * Obter links de redes sociais
     */
    public function getSocialMediaLinks(): array
    {
        $socialMedia = $this->social_media ?? [];
        return array_filter($socialMedia, function($value) {
            return !empty($value);
        });
    }

    /**
     * Obter link de uma rede social específica
     */
    public function getSocialMediaLink(string $platform): ?string
    {
        $socialMedia = $this->social_media ?? [];
        return $socialMedia[$platform] ?? null;
    }

    /**
     * Obter informações de contato
     */
    public function getContactInfo(): array
    {
        return [
            'email' => $this->company_email,
            'phone' => $this->company_phone,
            'website' => $this->company_website,
            'address' => $this->getFullAddressAttribute()
        ];
    }

    /**
     * Obter dados para Schema.org
     */
    public function getSchemaOrgData(): array
    {
        return [
            '@type' => 'Organization',
            'name' => $this->company_name,
            'description' => $this->company_description,
            'url' => $this->company_website,
            'email' => $this->company_email,
            'telephone' => $this->company_phone,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $this->address_street,
                'addressLocality' => $this->address_city,
                'addressRegion' => $this->address_state,
                'postalCode' => $this->address_zipcode,
                'addressCountry' => $this->address_country
            ],
            'sameAs' => $this->getSocialMediaLinks()
        ];
    }
}
