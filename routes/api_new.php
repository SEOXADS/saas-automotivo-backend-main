<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SuperAdminAuthController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\VehicleBrandController;
use App\Http\Controllers\Api\VehicleModelController;
use App\Http\Controllers\Api\VehicleImageController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\TenantUserController;
use App\Http\Controllers\Api\SystemSettingsController;
use App\Http\Controllers\Api\SuperAdminTenantUserController;
use App\Http\Controllers\Api\VehicleImportController;
use App\Http\Controllers\Api\SiteConfigurationController;
use App\Http\Controllers\Api\AuthConfigurationController;
use App\Http\Controllers\Api\PluginManagerController;
use App\Http\Controllers\Api\OtherConfigurationController;
use App\Http\Controllers\Api\PrefixConfigurationController;
use App\Http\Controllers\Api\LanguageConfigurationController;
use App\Http\Controllers\Api\SystemMessageController;
use App\Http\Controllers\Api\PublicMessageController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rotas públicas (sem autenticação)
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
});

// Rotas para Super Admin (sem middleware tenant)
Route::prefix('super-admin')->group(function () {
    // Login do super admin (sem autenticação)
    Route::post('login', [SuperAdminAuthController::class, 'login']);

    // Rotas protegidas do super admin
    Route::middleware(['auth:super_admin'])->group(function () {
        Route::post('logout', [SuperAdminAuthController::class, 'logout']);
        Route::get('me', [SuperAdminAuthController::class, 'me']);
        Route::post('refresh', [SuperAdminAuthController::class, 'refresh']);

        // Gerenciamento global de tenants
        Route::prefix('tenants')->group(function () {
            Route::get('/', [TenantController::class, 'indexForSuperAdmin']);
            Route::post('/', [TenantController::class, 'store']);
            Route::get('{id}', [TenantController::class, 'showForSuperAdmin']);
            Route::put('{id}', [TenantController::class, 'update']);
            Route::delete('{id}', [TenantController::class, 'destroy']);
            Route::post('{id}/activate', [TenantController::class, 'activate']);
            Route::post('{id}/deactivate', [TenantController::class, 'deactivate']);
            Route::get('{id}/users', [TenantController::class, 'getUsersForSuperAdmin']);
            Route::get('{id}/stats', [TenantController::class, 'getStatsForSuperAdmin']);

            // Usuários do tenant (CRUD completo pelo SaaS)
            Route::get('{tenantId}/users', [SuperAdminTenantUserController::class, 'index']);
            Route::post('{tenantId}/users', [SuperAdminTenantUserController::class, 'store']);
            Route::get('{tenantId}/users/{userId}', [SuperAdminTenantUserController::class, 'show']);
            Route::put('{tenantId}/users/{userId}', [SuperAdminTenantUserController::class, 'update']);
            Route::delete('{tenantId}/users/{userId}', [SuperAdminTenantUserController::class, 'destroy']);
            Route::post('{tenantId}/users/{userId}/activate', [SuperAdminTenantUserController::class, 'activate']);
            Route::post('{tenantId}/users/{userId}/deactivate', [SuperAdminTenantUserController::class, 'deactivate']);
        });

        // Dashboard do super admin
        Route::get('dashboard', function () {
            $user = \Tymon\JWTAuth\Facades\JWTAuth::user();

            if (!$user->isSuperAdmin()) {
                return response()->json(['error' => 'Acesso negado'], 403);
            }

            $totalTenants = \App\Models\Tenant::count();
            $activeTenants = \App\Models\Tenant::where('status', 'active')->count();
            $totalUsers = \App\Models\TenantUser::count();
            $activeUsers = \App\Models\TenantUser::where('is_active', true)->count();
            $totalVehicles = \App\Models\Vehicle::count();
            $totalLeads = \App\Models\Lead::count();

            return response()->json([
                'stats' => [
                    'total_tenants' => $totalTenants,
                    'active_tenants' => $activeTenants,
                    'inactive_tenants' => $totalTenants - $activeTenants,
                    'total_users' => $totalUsers,
                    'active_users' => $activeUsers,
                    'total_vehicles' => $totalVehicles,
                    'total_leads' => $totalLeads,
                ],
                'recent_tenants' => \App\Models\Tenant::with('users')
                    ->latest()
                    ->take(5)
                    ->get(),
                'system_health' => [
                    'database' => 'healthy',
                    'cache' => 'healthy',
                    'storage' => 'healthy',
                    'environment' => app()->environment(),
                    'version' => config('app.version', '1.0.0'),
                ],
            ]);
        });

        // Configurações do Sistema (SaaS)
        Route::prefix('settings')->group(function () {
            // Geral
            Route::get('general', [SystemSettingsController::class, 'getGeneral']);
            Route::post('general', [SystemSettingsController::class, 'saveGeneral']);
            // Segurança
            Route::get('security', [SystemSettingsController::class, 'getSecurity']);
            Route::post('security', [SystemSettingsController::class, 'saveSecurity']);
            // Banco de Dados
            Route::get('database', [SystemSettingsController::class, 'getDatabase']);
            Route::post('database', [SystemSettingsController::class, 'saveDatabase']);
            // Notificações
            Route::get('notifications', [SystemSettingsController::class, 'getNotifications']);
            Route::post('notifications', [SystemSettingsController::class, 'saveNotifications']);
        });

        // Configurações do Site
        Route::prefix('site-config')->group(function () {
            // Configurações da empresa
            Route::get('company', [SiteConfigurationController::class, 'getCompanySettings']);
            Route::post('company', [SiteConfigurationController::class, 'updateCompanySettings']);

            // Configurações de localização
            Route::get('location', [SiteConfigurationController::class, 'getLocationSettings']);
            Route::post('location', [SiteConfigurationController::class, 'updateLocationSettings']);

            // Configurações de SEO
            Route::get('seo', [SiteConfigurationController::class, 'getSeoSettings']);
            Route::post('seo', [SiteConfigurationController::class, 'updateSeoSettings']);

            // Configurações de IA
            Route::get('ai', [SiteConfigurationController::class, 'getAiSettings']);
            Route::post('ai', [SiteConfigurationController::class, 'updateAiSettings']);

            // Modo de manutenção
            Route::get('maintenance', [SiteConfigurationController::class, 'getMaintenanceStatus']);
            Route::post('maintenance', [SiteConfigurationController::class, 'updateMaintenanceStatus']);

            // Todas as configurações
            Route::get('all', [SiteConfigurationController::class, 'getAllSettings']);

            // Configurações de autenticação
            Route::get('auth', [AuthConfigurationController::class, 'getAuthSettings']);
            Route::post('auth', [AuthConfigurationController::class, 'updateAuthSettings']);
            Route::get('auth/oauth', [AuthConfigurationController::class, 'getOAuthSettings']);
            Route::post('auth/oauth', [AuthConfigurationController::class, 'updateOAuthSettings']);

            // Gerenciamento de plugins
            Route::get('plugins', [PluginManagerController::class, 'getPlugins']);
            Route::post('plugins/{pluginId}/toggle', [PluginManagerController::class, 'togglePlugin']);
            Route::get('plugins/{pluginId}/settings', [PluginManagerController::class, 'getPluginSettings']);
            Route::post('plugins/{pluginId}/settings', [PluginManagerController::class, 'updatePluginSettings']);

            // Configurações de prefixos
            Route::get('prefixes', [PrefixConfigurationController::class, 'getPrefixSettings']);
            Route::post('prefixes', [PrefixConfigurationController::class, 'updatePrefixSettings']);
            Route::get('prefixes/validate', [PrefixConfigurationController::class, 'validatePrefixes']);

            // Configurações de linguagem
            Route::get('languages', [LanguageConfigurationController::class, 'getLanguageSettings']);
            Route::post('languages', [LanguageConfigurationController::class, 'updateLanguageSettings']);
            Route::get('languages/available', [LanguageConfigurationController::class, 'getAvailableLanguages']);
            Route::get('languages/translations', [LanguageConfigurationController::class, 'getTranslationFiles']);
            Route::post('languages/translations/export', [LanguageConfigurationController::class, 'exportTranslations']);
            Route::post('languages/translations/import', [LanguageConfigurationController::class, 'importTranslations']);
        });

        // Outras Configurações do Sistema
        Route::prefix('other-config')->group(function () {
            // Mapa do site
            Route::get('sitemap', [OtherConfigurationController::class, 'generateSitemap']);

            // Limpar cache
            Route::post('clear-cache', [OtherConfigurationController::class, 'clearCache']);

            // Informações de armazenamento
            Route::get('storage-info', [OtherConfigurationController::class, 'getStorageInfo']);
            Route::post('storage-cleanup', [OtherConfigurationController::class, 'storageCleanup']);

            // Cronjobs
            Route::get('cronjobs', [OtherConfigurationController::class, 'getCronjobs']);
            Route::post('cronjobs/{command}/run', [OtherConfigurationController::class, 'runCronjob']);

            // Backups
            Route::post('backup/system', [OtherConfigurationController::class, 'createSystemBackup']);
            Route::post('backup/database', [OtherConfigurationController::class, 'createDatabaseBackup']);

            // Atualizações do sistema
            Route::get('system-update/check', [OtherConfigurationController::class, 'checkSystemUpdates']);
            Route::post('system-update/install', [OtherConfigurationController::class, 'installSystemUpdate']);
        });
    });
});

// Rotas protegidas por autenticação JWT (sem tenant temporariamente)
Route::middleware(['jwt.verify'])->group(function () {

    // Rotas de autenticação
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });

    // Rotas de veículos
    Route::prefix('vehicles')->group(function () {
        Route::get('/', [VehicleController::class, 'index']);
        Route::post('/', [VehicleController::class, 'store']);
        Route::get('features', [VehicleController::class, 'getFeatures']); // Nova rota para características
        Route::get('{id}', [VehicleController::class, 'show']);
        Route::put('{id}', [VehicleController::class, 'update']);
        Route::delete('{id}', [VehicleController::class, 'destroy']);
        Route::post('{id}/images', [VehicleController::class, 'uploadImages']);
        Route::delete('{id}/images/{imageId}', [VehicleController::class, 'deleteImage']);
        Route::post('{id}/images/{imageId}/primary', [VehicleController::class, 'setPrimaryImage']);
        Route::post('{id}/images/reorder', [VehicleController::class, 'reorderImages']);

        // Rotas de importação de veículos (DENTRO do grupo vehicles)
        Route::prefix('import')->group(function () {
            Route::get('webmotors', [VehicleImportController::class, 'importFromWebmotors']);
            Route::get('olx', [VehicleImportController::class, 'importFromOlx']);
            Route::get('icarros', [VehicleImportController::class, 'importFromICarros']);
            Route::get('omegaveiculos', [VehicleImportController::class, 'importFromOmegaVeiculos']);
        });
    });

    // Rotas de marcas
    Route::prefix('brands')->group(function () {
        Route::get('/', [VehicleBrandController::class, 'index']);
        Route::post('/', [VehicleBrandController::class, 'store']);
        Route::get('{id}', [VehicleBrandController::class, 'show']);
        Route::put('{id}', [VehicleBrandController::class, 'update']);
        Route::delete('{id}', [VehicleBrandController::class, 'destroy']);
        Route::get('{id}/models', [VehicleModelController::class, 'byBrand']);
    });

    // Rotas de modelos
    Route::prefix('models')->group(function () {
        Route::get('/', [VehicleModelController::class, 'index']);
        Route::post('/', [VehicleModelController::class, 'store']);
        Route::get('{id}', [VehicleModelController::class, 'show']);
        Route::put('{id}', [VehicleModelController::class, 'update']);
        Route::delete('{id}', [VehicleModelController::class, 'destroy']);
        Route::get('by-brand/{brand_id}', [VehicleModelController::class, 'byBrand']);
    });

    // Rotas de leads
    Route::prefix('leads')->group(function () {
        Route::get('/', [LeadController::class, 'index']);
        Route::post('/', [LeadController::class, 'store']);
        Route::get('dashboard', [LeadController::class, 'dashboard'])->middleware('debug');
        Route::get('{id}', [LeadController::class, 'show']);
        Route::put('{id}', [LeadController::class, 'update']);
        Route::delete('{id}', [LeadController::class, 'destroy']);
        Route::post('{id}/status', [LeadController::class, 'updateStatus']);
        Route::post('{id}/assign', [LeadController::class, 'assign']);
    });

    // Rotas de usuários do tenant
    Route::prefix('users')->group(function () {
        Route::get('/', [TenantUserController::class, 'index']);
        Route::post('/', [TenantUserController::class, 'store']);
        Route::get('{id}', [TenantUserController::class, 'show']);
        Route::put('{id}', [TenantUserController::class, 'update']);
        Route::delete('{id}', [TenantUserController::class, 'destroy']);
        Route::post('{id}/activate', [TenantUserController::class, 'activate']);
        Route::post('{id}/deactivate', [TenantUserController::class, 'deactivate']);
    });

    // Rotas de mensagens do sistema (apenas para super admins)
    Route::prefix('system-messages')->middleware('can:manage-system-messages')->group(function () {
        Route::get('/', [SystemMessageController::class, 'index']);
        Route::post('/', [SystemMessageController::class, 'store']);
        Route::get('{id}', [SystemMessageController::class, 'show']);
        Route::put('{id}', [SystemMessageController::class, 'update']);
        Route::delete('{id}', [SystemMessageController::class, 'destroy']);
    });

    // Rotas de tenants (apenas para super admins)
    Route::prefix('tenants')->middleware('can:manage-tenants')->group(function () {
        Route::get('/', [TenantController::class, 'index']);
        Route::post('/', [TenantController::class, 'store']);
        Route::get('{id}', [TenantController::class, 'show']);
        Route::put('{id}', [TenantController::class, 'update']);
        Route::delete('{id}', [TenantController::class, 'destroy']);
    });

    // Rotas de dashboard
    Route::get('dashboard', function () {
        $user = \Tymon\JWTAuth\Facades\JWTAuth::user();

        return response()->json([
            'stats' => [
                'total_vehicles' => $user->tenant->vehicles()->count(),
                'active_vehicles' => $user->tenant->vehicles()->active()->count(),
                'total_leads' => $user->tenant->leads()->count(),
                'new_leads' => $user->tenant->leads()->new()->count(),
                'total_users' => $user->tenant->users()->count(),
                'active_users' => $user->tenant->users()->active()->count(),
            ],
            'recent_leads' => $user->tenant->leads()
                ->with(['vehicle', 'assignedTo'])
                ->latest()
                ->take(5)
                ->get(),
            'recent_vehicles' => $user->tenant->vehicles()
                ->with(['brand', 'model', 'images'])
                ->latest()
                ->take(5)
                ->get(),
        ]);
    });
});

// Rotas públicas para mensagens do sistema (sem necessidade de tenant)
Route::prefix('public/messages')->group(function () {
    Route::get('{module}', [PublicMessageController::class, 'getMessages']);
    Route::get('{module}/version', [PublicMessageController::class, 'getVersion']);
});

// Rotas públicas para o site (com middleware tenant para identificação)
Route::prefix('public')->middleware(['tenant.exists'])->group(function () {

    // Catálogo público de veículos
    Route::get('vehicles', function (Request $request) {
        // Buscar tenant pelo subdomínio
        $subdomain = $request->header('X-Tenant-Subdomain');

        if (!$subdomain) {
            return response()->json(['error' => 'Tenant não especificado'], 400);
        }

        $tenant = \App\Models\Tenant::bySubdomain($subdomain)->active()->first();

        if (!$tenant) {
            return response()->json(['error' => 'Tenant não encontrado'], 404);
        }

        $query = \App\Models\Vehicle::byTenant($tenant->id)
            ->with(['brand', 'model', 'images'])
            ->active()
            ->available();

        // Filtros públicos
        if ($request->filled('brand_id')) {
            $query->byBrand($request->brand_id);
        }

        if ($request->filled('model_id')) {
            $query->byModel($request->model_id);
        }

        if ($request->filled('min_price') && $request->filled('max_price')) {
            $query->byPriceRange($request->min_price, $request->max_price);
        }

        if ($request->filled('min_year') && $request->filled('max_year')) {
            $query->byYearRange($request->min_year, $request->max_year);
        }

        if ($request->filled('fuel_type')) {
            $query->byFuelType($request->fuel_type);
        }

        if ($request->filled('transmission')) {
            $query->byTransmission($request->transmission);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $vehicles = $query->paginate(12);

        return response()->json([
            'data' => $vehicles->items(),
            'current_page' => $vehicles->currentPage(),
            'per_page' => $vehicles->perPage(),
            'total' => $vehicles->total(),
            'last_page' => $vehicles->lastPage(),
        ]);
    });

    // Detalhes de um veículo público
    Route::get('vehicles/{id}', function ($id, Request $request) {
        $subdomain = $request->header('X-Tenant-Subdomain');

        if (!$subdomain) {
            return response()->json(['error' => 'Tenant não especificado'], 400);
        }

        $tenant = \App\Models\Tenant::bySubdomain($subdomain)->active()->first();

        if (!$tenant) {
            return response()->json(['error' => 'Tenant não encontrado'], 404);
        }

        $vehicle = \App\Models\Vehicle::byTenant($tenant->id)
            ->with(['brand', 'model', 'images', 'features'])
            ->active()
            ->available()
            ->find($id);

        if (!$vehicle) {
            return response()->json(['error' => 'Veículo não encontrado'], 404);
        }

        // Incrementar visualizações
        $vehicle->incrementViews();

        return response()->json([
            'data' => $vehicle
        ]);
    });

    // Criar lead público
    Route::post('leads', function (Request $request) {
        $subdomain = $request->header('X-Tenant-Subdomain');

        if (!$subdomain) {
            return response()->json(['error' => 'Tenant não especificado'], 400);
        }

        $tenant = \App\Models\Tenant::bySubdomain($subdomain)->active()->first();

        if (!$tenant) {
            return response()->json(['error' => 'Tenant não encontrado'], 404);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'message' => 'nullable|string',
            'vehicle_id' => 'nullable|integer|exists:vehicles,id',
            'source' => 'required|string|in:site,whatsapp,facebook,instagram,google,outro',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        $leadData = $request->all();
        $leadData['tenant_id'] = $tenant->id;
        $leadData['ip_address'] = $request->ip();
        $leadData['user_agent'] = $request->userAgent();

        $lead = \App\Models\Lead::create($leadData);

        return response()->json([
            'message' => 'Lead criado com sucesso',
            'data' => $lead
        ], 201);
    });

    // Filtros para o site público
    Route::get('filters', function (Request $request) {
        $subdomain = $request->header('X-Tenant-Subdomain');

        if (!$subdomain) {
            return response()->json(['error' => 'Tenant não especificado'], 400);
        }

        $tenant = \App\Models\Tenant::bySubdomain($subdomain)->active()->first();

        if (!$tenant) {
            return response()->json(['error' => 'Tenant não encontrado'], 404);
        }

        $brands = \App\Models\VehicleBrand::active()->ordered()->get(['id', 'name']);
        $models = \App\Models\VehicleModel::active()->ordered()->get(['id', 'name', 'brand_id']);

        $vehicleStats = \App\Models\Vehicle::byTenant($tenant->id)
            ->active()
            ->available()
            ->selectRaw('
                MIN(year) as min_year,
                MAX(year) as max_year,
                MIN(price) as min_price,
                MAX(price) as max_price
            ')
            ->first();

        return response()->json([
            'brands' => $brands,
            'models' => $models,
            'fuel_types' => ['flex', 'gasolina', 'diesel', 'eletrico', 'hibrido', 'gnv'],
            'transmissions' => ['manual', 'automatica', 'cvt', 'automatizada'],
            'price_range' => [
                'min' => $vehicleStats->min_price ?? 0,
                'max' => $vehicleStats->max_price ?? 0
            ],
            'year_range' => [
                'min' => $vehicleStats->min_year ?? date('Y'),
                'max' => $vehicleStats->max_year ?? date('Y')
            ]
        ]);
    });
});

// Rotas públicas para imagens de veículos (sem autenticação)
Route::prefix('public/images')->group(function () {
    Route::get('{tenantId}/{vehicleId}/{filename}', [VehicleImageController::class, 'serveImage'])->name('vehicle.image.serve');
    Route::get('{tenantId}/{vehicleId}/{filename}/url', [VehicleImageController::class, 'getImageUrl'])->name('vehicle.image.url');
});
