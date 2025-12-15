<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Vehicle;
use App\Helpers\UrlHelper;

class UpdateVehicleUrlsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔄 Atualizando URLs dos veículos existentes...');

        $vehicles = Vehicle::whereNull('url')->orWhere('url', '')->get();

        if ($vehicles->isEmpty()) {
            $this->command->info('✅ Todos os veículos já possuem URLs válidas.');
            return;
        }

        $this->command->info("📝 Encontrados {$vehicles->count()} veículos para atualizar.");

        $updated = 0;
        foreach ($vehicles as $vehicle) {
            try {
                $oldUrl = $vehicle->url;
                $newUrl = UrlHelper::generateUniqueUrl($vehicle->title, $vehicle->tenant_id, $vehicle->id);

                $vehicle->update(['url' => $newUrl]);

                $this->command->info("✅ Veículo ID {$vehicle->id}: '{$vehicle->title}' -> URL: {$newUrl}");
                $updated++;

            } catch (\Exception $e) {
                $this->command->error("❌ Erro ao atualizar veículo ID {$vehicle->id}: " . $e->getMessage());
            }
        }

        $this->command->info("🎉 Processo concluído! {$updated} veículos atualizados com sucesso.");
    }
}
