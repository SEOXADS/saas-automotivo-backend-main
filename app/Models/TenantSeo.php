<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantSeo extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nome da tabela no banco de dados
     */
    protected $table = 'tenant_seo';

    protected $fillable = [
        'tenant_id',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'meta_author',
        'meta_robots',
        'og_title',
        'og_description',
        'og_image',
        'og_type',
        'og_site_name',
        'og_locale',
        'twitter_card',
        'twitter_site',
        'twitter_creator',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'schema_organization',
        'schema_website',
        'schema_automotive',
        'canonical_url',
        'hreflang',
        'structured_data',
        'enable_amp',
        'enable_sitemap'
    ];

    protected $casts = [
        'schema_organization' => 'array',
        'schema_website' => 'array',
        'schema_automotive' => 'array',
        'structured_data' => 'array',
        'enable_amp' => 'boolean',
        'enable_sitemap' => 'boolean',
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
     * Obter meta tags básicas
     */
    public function getMetaTags(): array
    {
        return [
            'title' => $this->meta_title,
            'description' => $this->meta_description,
            'keywords' => $this->meta_keywords,
            'author' => $this->meta_author,
            'robots' => $this->meta_robots,
            'canonical' => $this->canonical_url,
            'hreflang' => $this->hreflang
        ];
    }

    /**
     * Obter tags Open Graph
     */
    public function getOpenGraphTags(): array
    {
        return [
            'og:title' => $this->og_title,
            'og:description' => $this->og_description,
            'og:image' => $this->og_image,
            'og:type' => $this->og_type,
            'og:site_name' => $this->og_site_name,
            'og:locale' => $this->og_locale
        ];
    }

    /**
     * Obter tags Twitter Card
     */
    public function getTwitterCardTags(): array
    {
        return [
            'twitter:card' => $this->twitter_card,
            'twitter:site' => $this->twitter_site,
            'twitter:creator' => $this->twitter_creator,
            'twitter:title' => $this->twitter_title,
            'twitter:description' => $this->twitter_description,
            'twitter:image' => $this->twitter_image
        ];
    }

    /**
     * Obter dados Schema.org
     */
    public function getSchemaOrgData(): array
    {
        $schemas = [];

        if ($this->schema_organization) {
            $schemas[] = $this->schema_organization;
        }

        if ($this->schema_website) {
            $schemas[] = $this->schema_website;
        }

        if ($this->schema_automotive) {
            $schemas[] = $this->schema_automotive;
        }

        return $schemas;
    }

    /**
     * Gerar HTML das meta tags
     */
    public function generateMetaTagsHtml(): string
    {
        $html = '';

        // Meta tags básicas
        $metaTags = $this->getMetaTags();
        foreach ($metaTags as $name => $content) {
            if ($content) {
                if ($name === 'title') {
                    $html .= "<title>{$content}</title>\n";
                } elseif ($name === 'canonical') {
                    $html .= "<link rel=\"canonical\" href=\"{$content}\">\n";
                } elseif ($name === 'hreflang') {
                    $html .= "<link rel=\"alternate\" hreflang=\"{$content}\" href=\"{$content}\">\n";
                } else {
                    $html .= "<meta name=\"{$name}\" content=\"{$content}\">\n";
                }
            }
        }

        // Open Graph tags
        $ogTags = $this->getOpenGraphTags();
        foreach ($ogTags as $property => $content) {
            if ($content) {
                $html .= "<meta property=\"{$property}\" content=\"{$content}\">\n";
            }
        }

        // Twitter Card tags
        $twitterTags = $this->getTwitterCardTags();
        foreach ($twitterTags as $name => $content) {
            if ($content) {
                $html .= "<meta name=\"{$name}\" content=\"{$content}\">\n";
            }
        }

        // Schema.org
        $schemas = $this->getSchemaOrgData();
        if (!empty($schemas)) {
            $html .= "<script type=\"application/ld+json\">\n";
            $html .= json_encode($schemas, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $html .= "\n</script>\n";
        }

        return $html;
    }

    /**
     * Obter configurações para o frontend
     */
    public function getFrontendConfig(): array
    {
        return [
            'meta' => $this->getMetaTags(),
            'openGraph' => $this->getOpenGraphTags(),
            'twitter' => $this->getTwitterCardTags(),
            'schema' => $this->getSchemaOrgData(),
            'features' => [
                'amp' => $this->enable_amp,
                'sitemap' => $this->enable_sitemap
            ],
            'html' => $this->generateMetaTagsHtml()
        ];
    }

    /**
     * Gerar dados Schema.org padrão para empresa automotiva
     */
    public function generateDefaultAutomotiveSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'AutoDealer',
            'name' => $this->tenant->profile->company_name ?? 'Concessionária',
            'description' => $this->tenant->profile->company_description ?? 'Venda de veículos novos e usados',
            'url' => $this->tenant->profile->company_website ?? '',
            'telephone' => $this->tenant->profile->company_phone ?? '',
            'email' => $this->tenant->profile->company_email ?? '',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $this->tenant->profile->address_street ?? '',
                'addressLocality' => $this->tenant->profile->address_city ?? '',
                'addressRegion' => $this->tenant->profile->address_state ?? '',
                'postalCode' => $this->tenant->profile->address_zipcode ?? '',
                'addressCountry' => $this->tenant->profile->address_country ?? 'Brasil'
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => null, // Pode ser configurado posteriormente
                'longitude' => null
            ],
            'openingHours' => $this->tenant->profile->business_hours ?? [],
            'sameAs' => $this->tenant->profile->getSocialMediaLinks(),
            'serviceType' => 'Venda de Veículos',
            'areaServed' => 'Brasil'
        ];
    }
}
