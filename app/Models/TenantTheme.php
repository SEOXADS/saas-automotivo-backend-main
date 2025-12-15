<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantTheme extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'theme_name',
        'theme_version',
        'primary_color',
        'secondary_color',
        'accent_color',
        'success_color',
        'warning_color',
        'danger_color',
        'info_color',
        'text_color',
        'text_muted_color',
        'background_color',
        'background_secondary_color',
        'border_color',
        'font_family',
        'font_size_base',
        'font_size_small',
        'font_size_large',
        'font_weight_normal',
        'font_weight_bold',
        'border_radius',
        'border_radius_large',
        'border_radius_small',
        'spacing_unit',
        'container_max_width',
        'button_style',
        'card_style',
        'form_style',
        'custom_css',
        'custom_js',
        'enable_dark_mode',
        'enable_animations'
    ];

    protected $casts = [
        'custom_css' => 'array',
        'custom_js' => 'array',
        'enable_dark_mode' => 'boolean',
        'enable_animations' => 'boolean',
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
     * Obter variáveis CSS do tema
     */
    public function getCssVariables(): array
    {
        return [
            '--primary-color' => $this->primary_color,
            '--secondary-color' => $this->secondary_color,
            '--accent-color' => $this->accent_color,
            '--success-color' => $this->success_color,
            '--warning-color' => $this->warning_color,
            '--danger-color' => $this->danger_color,
            '--info-color' => $this->info_color,
            '--text-color' => $this->text_color,
            '--text-muted-color' => $this->text_muted_color,
            '--background-color' => $this->background_color,
            '--background-secondary-color' => $this->background_secondary_color,
            '--border-color' => $this->border_color,
            '--font-family' => $this->font_family,
            '--font-size-base' => $this->font_size_base,
            '--font-size-small' => $this->font_size_small,
            '--font-size-large' => $this->font_size_large,
            '--font-weight-normal' => $this->font_weight_normal,
            '--font-weight-bold' => $this->font_weight_bold,
            '--border-radius' => $this->border_radius,
            '--border-radius-large' => $this->border_radius_large,
            '--border-radius-small' => $this->border_radius_small,
            '--spacing-unit' => $this->spacing_unit,
            '--container-max-width' => $this->container_max_width
        ];
    }

    /**
     * Gerar CSS completo do tema
     */
    public function generateCss(): string
    {
        $variables = $this->getCssVariables();
        $css = ":root {\n";

        foreach ($variables as $property => $value) {
            $css .= "    {$property}: {$value};\n";
        }

        $css .= "}\n\n";

        // CSS específico baseado nas configurações
        $css .= $this->generateComponentCss();
        $css .= $this->generateCustomCss();

        return $css;
    }

    /**
     * Gerar CSS para componentes específicos
     */
    private function generateComponentCss(): string
    {
        $css = '';

        // Botões
        if ($this->button_style === 'pill') {
            $css .= ".btn { border-radius: 50px !important; }\n";
        } elseif ($this->button_style === 'square') {
            $css .= ".btn { border-radius: 0 !important; }\n";
        }

        // Cards
        if ($this->card_style === 'shadow') {
            $css .= ".card { box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important; }\n";
        } elseif ($this->card_style === 'border') {
            $css .= ".card { border: 1px solid var(--border-color) !important; box-shadow: none !important; }\n";
        }

        // Formulários
        if ($this->form_style === 'modern') {
            $css .= ".form-control { border-radius: var(--border-radius) !important; }\n";
        } elseif ($this->form_style === 'minimal') {
            $css .= ".form-control { border: none !important; border-bottom: 1px solid var(--border-color) !important; border-radius: 0 !important; }\n";
        }

        // Animações
        if ($this->enable_animations) {
            $css .= ".btn, .card, .form-control { transition: all 0.2s ease-in-out !important; }\n";
        }

        return $css;
    }

    /**
     * Gerar CSS customizado
     */
    private function generateCustomCss(): string
    {
        if (empty($this->custom_css) || !is_array($this->custom_css)) {
            return '';
        }

        $css = "\n/* CSS Customizado */\n";
        foreach ($this->custom_css as $selector => $rules) {
            if (is_array($rules)) {
                $css .= "{$selector} {\n";
                foreach ($rules as $property => $value) {
                    $css .= "    {$property}: {$value};\n";
                }
                $css .= "}\n";
            }
        }

        return $css;
    }

    /**
     * Obter configurações do tema para o frontend
     */
    public function getFrontendConfig(): array
    {
        return [
            'theme' => [
                'name' => $this->theme_name,
                'version' => $this->theme_version,
                'colors' => [
                    'primary' => $this->primary_color,
                    'secondary' => $this->secondary_color,
                    'accent' => $this->accent_color,
                    'success' => $this->success_color,
                    'warning' => $this->warning_color,
                    'danger' => $this->danger_color,
                    'info' => $this->info_color,
                    'text' => $this->text_color,
                    'textMuted' => $this->text_muted_color,
                    'background' => $this->background_color,
                    'backgroundSecondary' => $this->background_secondary_color,
                    'border' => $this->border_color
                ],
                'typography' => [
                    'fontFamily' => $this->font_family,
                    'fontSizeBase' => $this->font_size_base,
                    'fontSizeSmall' => $this->font_size_small,
                    'fontSizeLarge' => $this->font_size_large,
                    'fontWeightNormal' => $this->font_weight_normal,
                    'fontWeightBold' => $this->font_weight_bold
                ],
                'layout' => [
                    'borderRadius' => $this->border_radius,
                    'borderRadiusLarge' => $this->border_radius_large,
                    'borderRadiusSmall' => $this->border_radius_small,
                    'spacingUnit' => $this->spacing_unit,
                    'containerMaxWidth' => $this->container_max_width
                ],
                'components' => [
                    'buttonStyle' => $this->button_style,
                    'cardStyle' => $this->card_style,
                    'formStyle' => $this->form_style
                ],
                'features' => [
                    'darkMode' => $this->enable_dark_mode,
                    'animations' => $this->enable_animations
                ]
            ],
            'css' => $this->generateCss(),
            'customJs' => is_array($this->custom_js) ? $this->custom_js : []
        ];
    }
}
