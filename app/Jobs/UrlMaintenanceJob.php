<?php

namespace App\Jobs;

use App\Models\Vehicle;
use App\Models\TenantUrlRedirect;
use App\Helpers\UrlHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UrlMaintenanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $vehicleId;
    protected $action; // 'create', 'update', 'delete'
    protected $oldUrl;

    /**
     * Create a new job instance.
     */
    public function __construct(int $vehicleId, string $action, ?string $oldUrl = null)
    {
        $this->vehicleId = $vehicleId;
        $this->action = $action;
        $this->oldUrl = $oldUrl;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $vehicle = Vehicle::with(['brand', 'model'])->find($this->vehicleId);

            if (!$vehicle) {
                Log::warning('UrlMaintenanceJob: Veículo não encontrado', ['vehicle_id' => $this->vehicleId]);
                return;
            }

            switch ($this->action) {
                case 'create':
                    $this->handleCreate($vehicle);
                    break;
                case 'update':
                    $this->handleUpdate($vehicle);
                    break;
                case 'delete':
                    $this->handleDelete($vehicle);
                    break;
            }

            Log::info('UrlMaintenanceJob executado com sucesso', [
                'vehicle_id' => $this->vehicleId,
                'action' => $this->action,
                'tenant_id' => $vehicle->tenant_id
            ]);

        } catch (\Exception $e) {
            Log::error('Erro no UrlMaintenanceJob', [
                'vehicle_id' => $this->vehicleId,
                'action' => $this->action,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle vehicle creation
     */
    private function handleCreate(Vehicle $vehicle): void
    {
        // Gerar URL única baseada no título
        $newUrl = UrlHelper::generateUniqueUrl($vehicle->title, $vehicle->tenant_id);

        // Atualizar o veículo com a nova URL
        $vehicle->update(['url' => $newUrl]);

        Log::info('URL gerada para novo veículo', [
            'vehicle_id' => $vehicle->id,
            'title' => $vehicle->title,
            'url' => $newUrl,
            'tenant_id' => $vehicle->tenant_id
        ]);
    }

    /**
     * Handle vehicle update
     */
    private function handleUpdate(Vehicle $vehicle): void
    {
        // Gerar nova URL baseada no título atual
        $newUrl = UrlHelper::generateUniqueUrl($vehicle->title, $vehicle->tenant_id, $vehicle->id);

        // Se a URL mudou, criar redirecionamento 301
        if ($this->oldUrl && $this->oldUrl !== $newUrl) {
            $this->createRedirect($vehicle, $this->oldUrl, $newUrl);
        }

        // Atualizar o veículo com a nova URL
        $vehicle->update(['url' => $newUrl]);

        Log::info('URL atualizada para veículo', [
            'vehicle_id' => $vehicle->id,
            'old_url' => $this->oldUrl,
            'new_url' => $newUrl,
            'tenant_id' => $vehicle->tenant_id
        ]);
    }

    /**
     * Handle vehicle deletion
     */
    private function handleDelete(Vehicle $vehicle): void
    {
        // Marcar redirecionamentos como inativos se existirem
        TenantUrlRedirect::where('tenant_id', $vehicle->tenant_id)
            ->where('from_url', $vehicle->url)
            ->update(['is_active' => false]);

        Log::info('Redirecionamentos desativados para veículo deletado', [
            'vehicle_id' => $vehicle->id,
            'url' => $vehicle->url,
            'tenant_id' => $vehicle->tenant_id
        ]);
    }

    /**
     * Create 301 redirect
     */
    private function createRedirect(Vehicle $vehicle, string $oldUrl, string $newUrl): void
    {
        // Verificar se já existe um redirecionamento
        $existingRedirect = TenantUrlRedirect::where('tenant_id', $vehicle->tenant_id)
            ->where('from_url', $oldUrl)
            ->first();

        if ($existingRedirect) {
            // Atualizar redirecionamento existente
            $existingRedirect->update([
                'to_url' => $newUrl,
                'redirect_type' => '301',
                'redirect_reason' => 'vehicle_url_changed',
                'is_active' => true,
                'updated_at' => now()
            ]);
        } else {
            // Criar novo redirecionamento
            TenantUrlRedirect::create([
                'tenant_id' => $vehicle->tenant_id,
                'from_url' => $oldUrl,
                'to_url' => $newUrl,
                'redirect_type' => '301',
                'redirect_reason' => 'vehicle_url_changed',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        Log::info('Redirecionamento 301 criado', [
            'vehicle_id' => $vehicle->id,
            'from_url' => $oldUrl,
            'to_url' => $newUrl,
            'tenant_id' => $vehicle->tenant_id
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['url-maintenance', 'vehicle-' . $this->vehicleId];
    }
}
