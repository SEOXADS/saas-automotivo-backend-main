<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantConfiguration extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'company_name',
        'company_description',
        'company_address',
        'company_phone',
        'company_email',
        'company_website',
        'company_cnpj',
        'business_hours',
        'social_media',
        'theme_settings',
        'portal_settings',
        'seo_settings',
        'ai_settings',
        'is_active'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'business_hours' => 'array',
        'social_media' => 'array',
        'theme_settings' => 'array',
        'portal_settings' => 'array',
        'seo_settings' => 'array',
        'ai_settings' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Get the tenant that owns the configuration.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope a query to only include active configurations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the business hours for a specific day.
     */
    public function getBusinessHoursForDay($day)
    {
        $businessHours = $this->business_hours ?? [];
        return $businessHours[$day] ?? null;
    }

    /**
     * Check if the business is open on a specific day.
     */
    public function isOpenOnDay($day)
    {
        $dayConfig = $this->getBusinessHoursForDay($day);

        if (!$dayConfig) {
            return false;
        }

        return !($dayConfig['closed'] ?? false);
    }

    /**
     * Get the primary color from theme settings.
     */
    public function getPrimaryColor()
    {
        $themeSettings = $this->theme_settings ?? [];
        return $themeSettings['primary_color'] ?? '#007bff';
    }

    /**
     * Get the secondary color from theme settings.
     */
    public function getSecondaryColor()
    {
        $themeSettings = $this->theme_settings ?? [];
        return $themeSettings['secondary_color'] ?? '#6c757d';
    }

    /**
     * Get the accent color from theme settings.
     */
    public function getAccentColor()
    {
        $themeSettings = $this->theme_settings ?? [];
        return $themeSettings['accent_color'] ?? '#28a745';
    }

    /**
     * Get the text color from theme settings.
     */
    public function getTextColor()
    {
        $themeSettings = $this->theme_settings ?? [];
        return $themeSettings['text_color'] ?? '#212529';
    }

    /**
     * Get the background color from theme settings.
     */
    public function getBackgroundColor()
    {
        $themeSettings = $this->theme_settings ?? [];
        return $themeSettings['background_color'] ?? '#ffffff';
    }

    /**
     * Get the font family from theme settings.
     */
    public function getFontFamily()
    {
        $themeSettings = $this->theme_settings ?? [];
        return $themeSettings['font_family'] ?? 'Inter, sans-serif';
    }

    /**
     * Get the font size from theme settings.
     */
    public function getFontSize()
    {
        $themeSettings = $this->theme_settings ?? [];
        return $themeSettings['font_size'] ?? 'medium';
    }

    /**
     * Get the border radius from theme settings.
     */
    public function getBorderRadius()
    {
        $themeSettings = $this->theme_settings ?? [];
        return $themeSettings['border_radius'] ?? 'medium';
    }

    /**
     * Get the button style from theme settings.
     */
    public function getButtonStyle()
    {
        $themeSettings = $this->theme_settings ?? [];
        return $themeSettings['button_style'] ?? 'rounded';
    }

    /**
     * Get the layout style from theme settings.
     */
    public function getLayoutStyle()
    {
        $themeSettings = $this->theme_settings ?? [];
        return $themeSettings['layout_style'] ?? 'modern';
    }

    /**
     * Get social media links.
     */
    public function getSocialMediaLinks()
    {
        $socialMedia = $this->social_media ?? [];
        return array_filter($socialMedia, function($value) {
            return !empty($value);
        });
    }

    /**
     * Get a specific social media link.
     */
    public function getSocialMediaLink($platform)
    {
        $socialMedia = $this->social_media ?? [];
        return $socialMedia[$platform] ?? null;
    }

    /**
     * Get portal settings.
     */
    public function getPortalSettings()
    {
        $portalSettings = $this->portal_settings ?? [];
        return array_merge([
            'show_featured_vehicles' => true,
            'max_vehicles_per_page' => 20,
            'enable_search_filters' => true,
            'enable_vehicle_comparison' => true,
            'enable_wishlist' => true,
            'enable_reviews' => true,
            'enable_newsletter' => true,
            'enable_chat_widget' => false
        ], $portalSettings);
    }

    /**
     * Get SEO settings.
     */
    public function getSeoSettings()
    {
        $seoSettings = $this->seo_settings ?? [];
        return array_merge([
            'meta_title' => $this->company_name ?? 'Portal de Veículos',
            'meta_description' => $this->company_description ?? 'Encontre o veículo ideal para você',
            'meta_keywords' => 'veículos, carros, motos, compra, venda',
            'og_title' => $this->company_name ?? 'Portal de Veículos',
            'og_description' => $this->company_description ?? 'Encontre o veículo ideal para você',
            'twitter_card' => 'summary_large_image'
        ], $seoSettings);
    }

    /**
     * Get AI settings.
     */
    public function getAiSettings()
    {
        $aiSettings = $this->ai_settings ?? [];
        return array_merge([
            'enable_ai_chat' => false,
            'ai_chat_model' => 'gpt-3.5-turbo',
            'ai_chat_temperature' => 0.7,
            'ai_chat_max_tokens' => 1000,
            'enable_ai_vehicle_recommendations' => false,
            'enable_ai_price_analysis' => false,
            'enable_ai_content_generation' => false
        ], $aiSettings);
    }

    /**
     * Check if a specific AI feature is enabled.
     */
    public function isAiFeatureEnabled($feature)
    {
        $aiSettings = $this->getAiSettings();
        return $aiSettings["enable_{$feature}"] ?? false;
    }

    /**
     * Get the complete theme configuration as CSS variables.
     */
    public function getThemeCssVariables()
    {
        return [
            '--primary-color' => $this->getPrimaryColor(),
            '--secondary-color' => $this->getSecondaryColor(),
            '--accent-color' => $this->getAccentColor(),
            '--text-color' => $this->getTextColor(),
            '--background-color' => $this->getBackgroundColor(),
            '--font-family' => $this->getFontFamily(),
            '--font-size' => $this->getFontSize(),
            '--border-radius' => $this->getBorderRadius(),
            '--button-style' => $this->getButtonStyle(),
            '--layout-style' => $this->getLayoutStyle()
        ];
    }

    /**
     * Generate CSS for the theme.
     */
    public function generateThemeCss()
    {
        $variables = $this->getThemeCssVariables();
        $css = ":root {\n";

        foreach ($variables as $property => $value) {
            $css .= "    {$property}: {$value};\n";
        }

        $css .= "}\n\n";

        // Add specific CSS rules based on theme settings
        if ($this->getBorderRadius() === 'none') {
            $css .= ".btn, .form-control, .card { border-radius: 0 !important; }\n";
        } elseif ($this->getBorderRadius() === 'large') {
            $css .= ".btn, .form-control, .card { border-radius: 1rem !important; }\n";
        }

        if ($this->getButtonStyle() === 'pill') {
            $css .= ".btn { border-radius: 50px !important; }\n";
        }

        return $css;
    }
}
