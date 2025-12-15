<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SystemMessage;

class SystemMessageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $messages = [
            // Mensagens para o módulo de veículos
            [
                'module' => 'vehicles',
                'title' => 'Veículo Criado',
                'type' => 'success',
                'message' => 'Veículo criado com sucesso! O veículo foi adicionado ao catálogo e está disponível para visualização.',
                'icon' => 'check-circle',
                'icon_library' => 'fontawesome',
                'options' => [
                    'showConfirmButton' => true,
                    'confirmButtonText' => 'OK',
                    'timer' => 5000
                ],
                'is_active' => true,
                'sort_order' => 1
            ],
            [
                'module' => 'vehicles',
                'title' => 'Veículo Atualizado',
                'type' => 'success',
                'message' => 'Veículo atualizado com sucesso! As alterações foram salvas e estão visíveis no catálogo.',
                'icon' => 'check-circle',
                'icon_library' => 'fontawesome',
                'options' => [
                    'showConfirmButton' => true,
                    'confirmButtonText' => 'OK',
                    'timer' => 5000
                ],
                'is_active' => true,
                'sort_order' => 2
            ],
            [
                'module' => 'vehicles',
                'title' => 'Veículo Excluído',
                'type' => 'success',
                'message' => 'Veículo excluído com sucesso! O veículo foi removido do catálogo permanentemente.',
                'icon' => 'check-circle',
                'icon_library' => 'fontawesome',
                'options' => [
                    'showConfirmButton' => true,
                    'confirmButtonText' => 'OK',
                    'timer' => 5000
                ],
                'is_active' => true,
                'sort_order' => 3
            ],
            [
                'module' => 'vehicles',
                'title' => 'Erro ao Criar Veículo',
                'type' => 'error',
                'message' => 'Erro ao criar veículo. Verifique os dados informados e tente novamente.',
                'icon' => 'exclamation-circle',
                'icon_library' => 'fontawesome',
                'options' => [
                    'showConfirmButton' => true,
                    'confirmButtonText' => 'Tentar Novamente',
                    'showCancelButton' => true,
                    'cancelButtonText' => 'Cancelar'
                ],
                'is_active' => true,
                'sort_order' => 4
            ],
            [
                'module' => 'vehicles',
                'title' => 'Erro ao Atualizar Veículo',
                'type' => 'error',
                'message' => 'Erro ao atualizar veículo. Verifique os dados informados e tente novamente.',
                'icon' => 'exclamation-circle',
                'icon_library' => 'fontawesome',
                'options' => [
                    'showConfirmButton' => true,
                    'confirmButtonText' => 'Tentar Novamente',
                    'showCancelButton' => true,
                    'cancelButtonText' => 'Cancelar'
                ],
                'is_active' => true,
                'sort_order' => 5
            ],
            [
                'module' => 'vehicles',
                'title' => 'Confirmar Exclusão',
                'type' => 'question',
                'message' => 'Tem certeza que deseja excluir este veículo? Esta ação não pode ser desfeita.',
                'icon' => 'question-circle',
                'icon_library' => 'fontawesome',
                'options' => [
                    'showConfirmButton' => true,
                    'confirmButtonText' => 'Sim, Excluir',
                    'showCancelButton' => true,
                    'cancelButtonText' => 'Cancelar',
                    'confirmButtonColor' => '#d33',
                    'cancelButtonColor' => '#3085d6'
                ],
                'is_active' => true,
                'sort_order' => 6
            ],
            [
                'module' => 'vehicles',
                'title' => 'Carregando Veículos',
                'type' => 'loading',
                'message' => 'Carregando veículos... Por favor, aguarde.',
                'icon' => 'spinner',
                'icon_library' => 'fontawesome',
                'options' => [
                    'allowOutsideClick' => false,
                    'allowEscapeKey' => false,
                    'showConfirmButton' => false
                ],
                'is_active' => true,
                'sort_order' => 7
            ],
            [
                'module' => 'vehicles',
                'title' => 'Imagem Carregada',
                'type' => 'success',
                'message' => 'Imagem carregada com sucesso! A imagem foi adicionada ao veículo.',
                'icon' => 'image',
                'icon_library' => 'fontawesome',
                'options' => [
                    'showConfirmButton' => true,
                    'confirmButtonText' => 'OK',
                    'timer' => 3000
                ],
                'is_active' => true,
                'sort_order' => 8
            ],
            [
                'module' => 'vehicles',
                'title' => 'Imagem Definida como Principal',
                'type' => 'success',
                'message' => 'Imagem definida como principal com sucesso! Esta será a imagem exibida em destaque.',
                'icon' => 'star',
                'icon_library' => 'fontawesome',
                'options' => [
                    'showConfirmButton' => true,
                    'confirmButtonText' => 'OK',
                    'timer' => 3000
                ],
                'is_active' => true,
                'sort_order' => 9
            ],
            [
                'module' => 'vehicles',
                'title' => 'Aviso de Dados',
                'type' => 'warning',
                'message' => 'Alguns campos obrigatórios não foram preenchidos. Verifique e complete as informações necessárias.',
                'icon' => 'exclamation-triangle',
                'icon_library' => 'fontawesome',
                'options' => [
                    'showConfirmButton' => true,
                    'confirmButtonText' => 'Verificar',
                    'showCancelButton' => true,
                    'cancelButtonText' => 'Continuar',
                    'confirmButtonColor' => '#f39c12'
                ],
                'is_active' => true,
                'sort_order' => 10
            ],
            [
                'module' => 'vehicles',
                'title' => 'Informação sobre Preços',
                'type' => 'info',
                'message' => 'Os preços são atualizados automaticamente. Para alterações manuais, entre em contato com o administrador.',
                'icon' => 'info-circle',
                'icon_library' => 'fontawesome',
                'options' => [
                    'showConfirmButton' => true,
                    'confirmButtonText' => 'Entendi',
                    'timer' => 8000
                ],
                'is_active' => true,
                'sort_order' => 11
            ]
        ];

        foreach ($messages as $messageData) {
            SystemMessage::firstOrCreate(
                [
                    'module' => $messageData['module'],
                    'title' => $messageData['title']
                ],
                $messageData
            );
        }

        $this->command->info('✅ Mensagens do sistema criadas com sucesso.');
    }
}
