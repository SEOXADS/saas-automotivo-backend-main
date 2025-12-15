<?php

namespace App\Helpers;

use App\Models\Vehicle;
use Illuminate\Support\Str;

class UrlHelper
{
    /**
     * Gera uma URL única baseada no título do veículo
     *
     * @param string $title Título do veículo
     * @param int $tenantId ID do tenant
     * @param int|null $excludeId ID do veículo a ser excluído da verificação (para updates)
     * @return string URL única
     */
    public static function generateUniqueUrl(string $title, int $tenantId, ?int $excludeId = null): string
    {
        $baseUrl = self::generateBasicUrl($title);
        $url = $baseUrl;
        $counter = 1;

        // Verificar se a URL já existe
        while (self::urlExists($url, $tenantId, $excludeId)) {
            $url = $baseUrl . '-' . $counter;
            $counter++;
        }

        return $url;
    }

    /**
     * Verifica se uma URL já existe no sistema
     *
     * @param string $url URL a ser verificada
     * @param int $tenantId ID do tenant
     * @param int|null $excludeId ID do veículo a ser excluído da verificação
     * @return bool True se a URL já existe
     */
    public static function urlExists(string $url, int $tenantId, ?int $excludeId = null): bool
    {
        $query = Vehicle::where('url', $url)
            ->where('tenant_id', $tenantId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Gera uma URL baseada no título sem verificar duplicatas
     *
     * @param string $title Título do veículo
     * @return string URL básica
     */
    public static function generateBasicUrl(string $title): string
    {
        $processedTitle = self::processTitleForSlug($title);
        return Str::slug($processedTitle);
    }

    /**
     * Processa o título do veículo para gerar um slug melhor
     *
     * @param string $title Título original
     * @return string Título processado
     */
    public static function processTitleForSlug(string $title): string
    {
        $processed = $title;

        // Primeiro: remover pontos entre números (ex: 1.0, 1.4)
        $processed = preg_replace('/(\d)\.(\d)/', '$1$2', $processed);

        // Segundo: substituir barras por hífens
        $processed = str_replace('/', '-', $processed);

        // Terceiro: substituir pontos entre palavras por hífens
        $processed = preg_replace('/\./', '-', $processed);

        // Substituições de palavras específicas
        $replacements = [
            // Marcas específicas
            'GM - Chevrolet' => 'chevrolet',
            'GM -' => '',
            'GM-' => '',

            // Transmissão
            'Aut.' => 'automatico',
            'Man.' => 'manual',
            'Automatic' => 'automatico',
            'Manual' => 'manual',
            'CVT' => 'cvt',
            'AT' => 'automatico',
            'MT' => 'manual',

            // Cores em inglês para português
            'White' => 'branco',
            'Black' => 'preto',
            'Red' => 'vermelho',
            'Blue' => 'azul',
            'Green' => 'verde',
            'Yellow' => 'amarelo',
            'Orange' => 'laranja',
            'Purple' => 'roxo',
            'Pink' => 'rosa',
            'Brown' => 'marrom',
            'Gray' => 'cinza',
            'Grey' => 'cinza',
            'Silver' => 'prata',
            'Gold' => 'dourado',
            'Beige' => 'bege',

            // Combustível
            'Gasoline' => 'gasolina',
            'Diesel' => 'diesel',
            'Ethanol' => 'etanol',
            'Flex' => 'flex',
            'Hybrid' => 'hibrido',
            'Electric' => 'eletrico',

            // Outros termos comuns
            'SUV' => 'suv',
            'Pickup' => 'pickup',
            'Hatchback' => 'hatchback',
            'Sedan' => 'sedan',
            'Coupe' => 'coupe',
            'Convertible' => 'conversivel',
            'Wagon' => 'perua',
            'Van' => 'van',
            'Truck' => 'caminhao',
            'Motorcycle' => 'moto',
            'Bike' => 'moto',

            // Condições
            'New' => 'novo',
            'Used' => 'usado',
            'Certified' => 'certificado',
            'Pre-owned' => 'seminovo',

            // Características
            '4WD' => '4x4',
            'AWD' => '4x4',
            'FWD' => 'dianteira',
            'RWD' => 'traseira',
            'ABS' => 'abs',
            'Airbag' => 'airbag',
            'Air Conditioning' => 'ar-condicionado',
            'AC' => 'ar-condicionado',
            'Power Steering' => 'direcao-hidraulica',
            'Power Windows' => 'vidros-eletricos',
            'Central Lock' => 'travas-eletricas',
            'Alarm' => 'alarme',
            'Immobilizer' => 'imobilizador',
        ];

        // Aplicar substituições (case insensitive)
        foreach ($replacements as $search => $replace) {
            $processed = preg_replace('/\b' . preg_quote($search, '/') . '\b/i', $replace, $processed);
        }

        // Limpar espaços extras e caracteres especiais
        $processed = preg_replace('/\s+/', ' ', $processed);
        $processed = trim($processed);

        return $processed;
    }

    /**
     * Valida se uma URL é válida
     *
     * @param string $url URL a ser validada
     * @return bool True se a URL é válida
     */
    public static function isValidUrl(string $url): bool
    {
        // URL deve conter apenas letras minúsculas, números e hífens
        // Não pode começar ou terminar com hífen
        // Não pode ter hífens consecutivos
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $url);
    }

    /**
     * Limpa e normaliza uma URL
     *
     * @param string $url URL a ser normalizada
     * @return string URL normalizada
     */
    public static function normalizeUrl(string $url): string
    {
        // Converter para minúsculas
        $url = strtolower($url);

        // Remover caracteres especiais e espaços
        $url = Str::slug($url);

        // Remover hífens no início e fim
        $url = trim($url, '-');

        // Substituir hífens consecutivos por um único hífen
        $url = preg_replace('/-+/', '-', $url);

        return $url;
    }

    /**
     * Gera sugestões de URLs alternativas
     *
     * @param string $title Título do veículo
     * @param int $tenantId ID do tenant
     * @param int $maxSuggestions Número máximo de sugestões
     * @return array Array de URLs sugeridas
     */
    public static function generateUrlSuggestions(string $title, int $tenantId, int $maxSuggestions = 5): array
    {
        $baseUrl = self::generateBasicUrl($title);
        $suggestions = [];
        $counter = 1;

        while (count($suggestions) < $maxSuggestions) {
            $suggestedUrl = $baseUrl . '-' . $counter;

            if (!self::urlExists($suggestedUrl, $tenantId)) {
                $suggestions[] = $suggestedUrl;
            }

            $counter++;
        }

        return $suggestions;
    }
}
