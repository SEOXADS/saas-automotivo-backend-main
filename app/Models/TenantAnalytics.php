<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class TenantAnalytics extends Model
{
    use HasFactory;

    protected $table = 'tenant_analytics';

    protected $fillable = [
        'tenant_id',
        'metric_type',
        'metric_name',
        'metric_data',
        'ip_address',
        'user_agent',
        'referrer',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'session_data',
        'recorded_at',
    ];

    protected $casts = [
        'metric_data' => 'array',
        'session_data' => 'array',
        'recorded_at' => 'datetime',
    ];

    /**
     * Relacionamento com o tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope para filtrar por tenant
     */
    public function scopeByTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope para filtrar por tipo de métrica
     */
    public function scopeByMetricType(Builder $query, string $metricType): Builder
    {
        return $query->where('metric_type', $metricType);
    }

    /**
     * Scope para filtrar por período
     */
    public function scopeByPeriod(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('recorded_at', [$startDate, $endDate]);
    }

    /**
     * Scope para métricas recentes
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('recorded_at', '>=', now()->subDays($days));
    }

    /**
     * Scope para métricas de hoje
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('recorded_at', today());
    }

    /**
     * Scope para métricas desta semana
     */
    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('recorded_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Scope para métricas deste mês
     */
    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('recorded_at', now()->month)
                    ->whereYear('recorded_at', now()->year);
    }

    /**
     * Método para registrar uma nova métrica
     */
    public static function recordMetric(
        int $tenantId,
        string $metricType,
        string $metricName,
        array $metricData = [],
        array $sessionData = [],
        array $utmData = []
    ): self {
        return self::create([
            'tenant_id' => $tenantId,
            'metric_type' => $metricType,
            'metric_name' => $metricName,
            'metric_data' => $metricData,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referrer' => request()->header('referer'),
            'utm_source' => $utmData['utm_source'] ?? null,
            'utm_medium' => $utmData['utm_medium'] ?? null,
            'utm_campaign' => $utmData['utm_campaign'] ?? null,
            'utm_term' => $utmData['utm_term'] ?? null,
            'utm_content' => $utmData['utm_content'] ?? null,
            'session_data' => $sessionData,
            'recorded_at' => now(),
        ]);
    }

    /**
     * Método para registrar visualização de página
     */
    public static function recordPageView(
        int $tenantId,
        string $pageName,
        array $additionalData = []
    ): self {
        return self::recordMetric(
            $tenantId,
            'page_view',
            $pageName,
            $additionalData
        );
    }

    /**
     * Método para registrar lead criado
     */
    public static function recordLeadCreated(
        int $tenantId,
        int $leadId,
        array $additionalData = []
    ): self {
        return self::recordMetric(
            $tenantId,
            'lead_created',
            'lead_form',
            array_merge(['lead_id' => $leadId], $additionalData)
        );
    }

    /**
     * Método para registrar veículo visualizado
     */
    public static function recordVehicleViewed(
        int $tenantId,
        int $vehicleId,
        array $additionalData = []
    ): self {
        return self::recordMetric(
            $tenantId,
            'vehicle_viewed',
            'vehicle_detail',
            array_merge(['vehicle_id' => $vehicleId], $additionalData)
        );
    }

    /**
     * Método para registrar busca realizada
     */
    public static function recordSearchPerformed(
        int $tenantId,
        string $searchTerm,
        array $filters = [],
        array $additionalData = []
    ): self {
        return self::recordMetric(
            $tenantId,
            'search_performed',
            'search',
            array_merge([
                'search_term' => $searchTerm,
                'filters' => $filters
            ], $additionalData)
        );
    }

    /**
     * Método para obter estatísticas resumidas
     */
    public static function getStats(int $tenantId, int $days = 30): array
    {
        $stats = self::byTenant($tenantId)
            ->recent($days)
            ->selectRaw('
                metric_type,
                COUNT(*) as count,
                DATE(recorded_at) as date
            ')
            ->groupBy('metric_type', 'date')
            ->get()
            ->groupBy('metric_type');

        $result = [];
        foreach ($stats as $metricType => $data) {
            $result[$metricType] = [
                'total' => $data->sum('count'),
                'daily' => $data->pluck('count', 'date')->toArray()
            ];
        }

        return $result;
    }

    /**
     * Método para limpar métricas antigas
     */
    public static function cleanOldMetrics(int $daysToKeep = 90): int
    {
        return self::where('recorded_at', '<', now()->subDays($daysToKeep))->delete();
    }
}
