<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class SystemMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'module',
        'title',
        'type',
        'message',
        'icon',
        'icon_library',
        'options',
        'is_active',
        'sort_order',
        'version_hash',
    ];

    protected $casts = [
        'options' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Boot do modelo
     */
    protected static function boot()
    {
        parent::boot();

        // Gerar hash de versão automaticamente
        static::saving(function ($message) {
            $message->generateVersionHash();
        });
    }

    /**
     * Scopes
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }

    /**
     * Métodos auxiliares
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getTypeClass(): string
    {
        return match($this->type) {
            'error' => 'error',
            'success' => 'success',
            'info' => 'info',
            'warning' => 'warning',
            'question' => 'question',
            'loading' => 'info',
            default => 'info'
        };
    }

    public function getIconClass(): string
    {
        if (!$this->icon) {
            return $this->getDefaultIcon();
        }

        return $this->icon_library === 'fontawesome'
            ? "fas fa-{$this->icon}"
            : $this->icon;
    }

    public function getDefaultIcon(): string
    {
        return match($this->type) {
            'error' => 'fas fa-exclamation-circle',
            'success' => 'fas fa-check-circle',
            'info' => 'fas fa-info-circle',
            'warning' => 'fas fa-exclamation-triangle',
            'question' => 'fas fa-question-circle',
            'loading' => 'fas fa-spinner fa-spin',
            default => 'fas fa-info-circle'
        };
    }

    public function getOptions(): array
    {
        return $this->options ?? [];
    }

    public function hasOptions(): bool
    {
        return !empty($this->options);
    }

    /**
     * Gerar hash de versão baseado no conteúdo
     */
    public function generateVersionHash(): void
    {
        $content = $this->module . $this->title . $this->type . $this->message .
                   $this->icon . $this->icon_library . json_encode($this->options) .
                   $this->is_active . $this->sort_order;

        $this->version_hash = Hash::make($content);
    }

    /**
     * Verificar se houve alteração comparando hashes
     */
    public function hasChanged(string $previousHash): bool
    {
        return $this->version_hash !== $previousHash;
    }

    /**
     * Obter hash de versão atual
     */
    public function getCurrentVersionHash(): string
    {
        return $this->version_hash;
    }
}
