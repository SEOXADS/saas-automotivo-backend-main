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
use App\Http\Controllers\Api\PortalController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\SuperAdminTenantVehicleController;
use App\Http\Controllers\Api\SuperAdminTenantConfigController;
use App\Http\Controllers\Api\SuperAdminProfileController;
use App\Http\Controllers\Api\SuperAdminUrlController;
use App\Http\Controllers\Api\TenantUserProfileController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TenantAuthController;
use App\Http\Controllers\Api\FipeController;
use App\Http\Controllers\Api\PublicFipeController;
use App\Http\Controllers\Api\TenantConfigurationController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\StateController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\NeighborhoodController;
use App\Http\Controllers\Api\TenantLocationController;
use App\Http\Controllers\Api\TenantUrlController;
use App\Http\Controllers\Api\TenantSitemapController;
use App\Http\Controllers\Api\TenantRobotsController;
use App\Http\Controllers\Api\TenantSeoController;
use App\Http\Controllers\Api\UserAuthController;

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


// ========================================
// ROTAS EXCLUSIVAS DO SUPER ADMIN
// ========================================
Route::prefix('super-admin')->group(function () {
    // Login do super admin (sem autenticação)
    Route::post('login', [SuperAdminAuthController::class, 'login']);

    // Recuperação de senha (sem autenticação)
    Route::post('forgot-password', [SuperAdminAuthController::class, 'forgotPassword']);
    Route::post('reset-password', [SuperAdminAuthController::class, 'resetPassword']);

    // ========================================
    // ROTAS PROTEGIDAS - ACESSO TOTAL DO SUPER ADMIN
    // ========================================
    Route::middleware(['token.auth.super_admin'])->group(function () {
        Route::post('logout', [SuperAdminAuthController::class, 'logout']);
        Route::get('me', [SuperAdminAuthController::class, 'me']);

        // ========================================
        // GESTÃO COMPLETA DE TENANTS
        // ========================================
        Route::prefix('tenants')->group(function () {
            // CRUD básico de tenants
            Route::get('/', [TenantController::class, 'indexForSuperAdmin']);
            Route::post('/', [TenantController::class, 'store']);
            Route::get('/{id}', [TenantController::class, 'showForSuperAdmin']);
            Route::put('/{id}', [TenantController::class, 'update']);
            Route::delete('/{id}', [TenantController::class, 'destroy']);

            // Controle de status
            Route::post('/{id}/activate', [TenantController::class, 'activate']);
            Route::post('/{id}/deactivate', [TenantController::class, 'deactivate']);
            Route::post('/{id}/suspend', [TenantController::class, 'suspend']);
            Route::post('/{id}/unsuspend', [TenantController::class, 'unsuspend']);

            // Gestão de usuários do tenant
            Route::get('/{id}/users', [TenantController::class, 'getTenantUsers']);
            Route::post('/{id}/users', [TenantController::class, 'addTenantUser']);
            Route::put('/{id}/users/{userId}', [TenantController::class, 'updateTenantUser']);
            Route::delete('/{id}/users/{userId}', [TenantController::class, 'removeTenantUser']);
            Route::post('/{id}/users/{userId}/activate', [TenantController::class, 'activateTenantUser']);
            Route::post('/{id}/users/{userId}/deactivate', [TenantController::class, 'deactivateTenantUser']);

            // Dados e analytics do tenant
            Route::get('/{id}/vehicles', [TenantController::class, 'getTenantVehicles']);
            Route::get('/{id}/leads', [TenantController::class, 'getTenantLeads']);
            Route::get('/{id}/analytics', [TenantController::class, 'getTenantAnalytics']);
            Route::get('/{id}/stats', [TenantController::class, 'getTenantStats']);
            Route::get('/{id}/config', [TenantController::class, 'getTenantConfig']);
            Route::put('/{id}/config', [TenantController::class, 'updateTenantConfig']);

            // Backup e restauração
            Route::post('/{id}/backup', [TenantController::class, 'createBackup']);
            Route::post('/{id}/restore', [TenantController::class, 'restoreBackup']);
            Route::get('/{id}/backups', [TenantController::class, 'getBackups']);
        });

        // ========================================
        // GESTÃO GLOBAL DE VEÍCULOS
        // ========================================
        Route::prefix('vehicles')->group(function () {
            // CRUD global de veículos
            Route::get('/', [VehicleController::class, 'indexForSuperAdmin']);
            Route::post('/', [VehicleController::class, 'store']);
            Route::get('/{id}', [VehicleController::class, 'showForSuperAdmin']);
            Route::put('/{id}', [VehicleController::class, 'update']);
            Route::delete('/{id}', [VehicleController::class, 'destroy']);

            // Gestão de marcas e modelos
            Route::get('/brands', [VehicleBrandController::class, 'index']);
            Route::post('/brands', [VehicleBrandController::class, 'store']);
            Route::get('/brands/{id}', [VehicleBrandController::class, 'show']);
            Route::put('/brands/{id}', [VehicleBrandController::class, 'update']);
            Route::delete('/brands/{id}', [VehicleBrandController::class, 'destroy']);

            Route::get('/models', [VehicleModelController::class, 'index']);
            Route::post('/models', [VehicleModelController::class, 'store']);
            Route::get('/models/{id}', [VehicleModelController::class, 'show']);
            Route::put('/models/{id}', [VehicleModelController::class, 'update']);
            Route::delete('/models/{id}', [VehicleModelController::class, 'destroy']);

            // Gestão de características (usando VehicleController existente)
            Route::get('/features', [VehicleController::class, 'getFeatures']);
            Route::post('/features', [VehicleController::class, 'storeFeature']);
            Route::get('/features/{id}', [VehicleController::class, 'showFeature']);
            Route::put('/features/{id}', [VehicleController::class, 'updateFeature']);
            Route::delete('/features/{id}', [VehicleController::class, 'destroyFeature']);

            // Analytics e relatórios
            Route::get('/analytics', [VehicleController::class, 'getAnalytics']);
            Route::get('/reports', [VehicleController::class, 'getReports']);
            Route::get('/export', [VehicleController::class, 'exportVehicles']);
        });

        // ========================================
        // GESTÃO GLOBAL DE LEADS
        // ========================================
        Route::prefix('leads')->group(function () {
            // CRUD global de leads
            Route::get('/', [LeadController::class, 'indexForSuperAdmin']);
            Route::post('/', [LeadController::class, 'store']);
            Route::get('/{id}', [LeadController::class, 'showForSuperAdmin']);
            Route::put('/{id}', [LeadController::class, 'update']);
            Route::delete('/{id}', [LeadController::class, 'destroy']);

            // Gestão de status e atribuição
            Route::post('/{id}/status', [LeadController::class, 'updateStatus']);
            Route::post('/{id}/assign', [LeadController::class, 'assignLead']);
            Route::post('/{id}/unassign', [LeadController::class, 'unassignLead']);

            // Analytics e relatórios
            Route::get('/analytics', [LeadController::class, 'getAnalytics']);
            Route::get('/dashboard', [LeadController::class, 'getDashboard']);
            Route::get('/reports', [LeadController::class, 'getReports']);
            Route::get('/export', [LeadController::class, 'exportLeads']);
        });

        // ========================================
        // GESTÃO GLOBAL DE USUÁRIOS (usando TenantUserController existente)
        // ========================================
        Route::prefix('users')->group(function () {
            // CRUD global de usuários
            Route::get('/', [TenantUserController::class, 'indexForSuperAdmin']);
            Route::post('/', [TenantUserController::class, 'storeForSuperAdmin']);
            Route::get('/{id}', [TenantUserController::class, 'showForSuperAdmin']);
            Route::put('/{id}', [TenantUserController::class, 'updateForSuperAdmin']);
            Route::delete('/{id}', [TenantUserController::class, 'destroyForSuperAdmin']);

            // Controle de status
            Route::post('/{id}/activate', [TenantUserController::class, 'activate']);
            Route::post('/{id}/deactivate', [TenantUserController::class, 'deactivate']);

            // Gestão de permissões
            Route::put('/{id}/role', [TenantUserController::class, 'updateRole']);
            Route::put('/{id}/permissions', [TenantUserController::class, 'updatePermissions']);

            // Analytics e relatórios
            Route::get('/analytics', [TenantUserController::class, 'getAnalytics']);
            Route::get('/reports', [TenantUserController::class, 'getReports']);
            Route::get('/export', [TenantUserController::class, 'exportUsers']);
        });

        // ========================================
        // GESTÃO DE LOCALIZAÇÕES
        // ========================================
        Route::prefix('locations')->group(function () {
            // Países
            Route::get('/countries', [CountryController::class, 'index']);
            Route::post('/countries', [CountryController::class, 'store']);
            Route::get('/countries/{id}', [CountryController::class, 'show']);
            Route::put('/countries/{id}', [CountryController::class, 'update']);
            Route::delete('/countries/{id}', [CountryController::class, 'destroy']);

            // Estados
            Route::get('/states', [StateController::class, 'index']);
            Route::post('/states', [StateController::class, 'store']);
            Route::get('/states/{id}', [StateController::class, 'show']);
            Route::put('/states/{id}', [StateController::class, 'update']);
            Route::delete('/states/{id}', [StateController::class, 'destroy']);

            // Cidades
            Route::get('/cities', [CityController::class, 'index']);
            Route::post('/cities', [CityController::class, 'store']);
            Route::get('/cities/{id}', [CityController::class, 'show']);
            Route::put('/cities/{id}', [CityController::class, 'update']);
            Route::delete('/cities/{id}', [CityController::class, 'destroy']);

            // Bairros
            Route::get('/neighborhoods', [NeighborhoodController::class, 'index']);
            Route::post('/neighborhoods', [NeighborhoodController::class, 'store']);
            Route::get('/neighborhoods/{id}', [NeighborhoodController::class, 'show']);
            Route::put('/neighborhoods/{id}', [NeighborhoodController::class, 'update']);
            Route::delete('/neighborhoods/{id}', [NeighborhoodController::class, 'destroy']);
        });

        // ========================================
        // CONFIGURAÇÕES DO SISTEMA
        // ========================================
        Route::prefix('settings')->group(function () {
            // Configurações gerais
            Route::get('/general', [SystemSettingsController::class, 'getGeneral']);
            Route::put('/general', [SystemSettingsController::class, 'updateGeneral']);

            // Configurações de segurança
            Route::get('/security', [SystemSettingsController::class, 'getSecurity']);
            Route::put('/security', [SystemSettingsController::class, 'updateSecurity']);

            // Configurações de banco
            Route::get('/database', [SystemSettingsController::class, 'getDatabase']);
            Route::put('/database', [SystemSettingsController::class, 'updateDatabase']);

            // Configurações de notificações
            Route::get('/notifications', [SystemSettingsController::class, 'getNotifications']);
            Route::put('/notifications', [SystemSettingsController::class, 'updateNotifications']);

            // Configurações de email
            Route::get('/email', [SystemSettingsController::class, 'getEmail']);
            Route::put('/email', [SystemSettingsController::class, 'updateEmail']);

            // Configurações de backup
            Route::get('/backup', [SystemSettingsController::class, 'getBackup']);
            Route::put('/backup', [SystemSettingsController::class, 'updateBackup']);
            Route::post('/backup/create', [SystemSettingsController::class, 'createBackup']);
            Route::post('/backup/restore', [SystemSettingsController::class, 'restoreBackup']);
        });

        // ========================================
        // DASHBOARD E ANALYTICS (usando DashboardController existente)
        // ========================================
        Route::prefix('dashboard')->group(function () {
            Route::get('/', [DashboardController::class, 'index']);
            Route::get('/stats', [DashboardController::class, 'getStats']);
            Route::get('/analytics', [DashboardController::class, 'getAnalytics']);
            Route::get('/reports', [DashboardController::class, 'getReports']);
            Route::get('/exports', [DashboardController::class, 'getExports']);
        });

        // ========================================
        // PERFIL DO SUPER ADMIN
        // ========================================
        Route::prefix('profile')->group(function () {
            Route::get('/', [SuperAdminProfileController::class, 'show']);
            Route::put('/', [SuperAdminProfileController::class, 'update']);
            Route::put('/password', [SuperAdminProfileController::class, 'updatePassword']);
            Route::put('/avatar', [SuperAdminProfileController::class, 'updateAvatar']);
            Route::delete('/avatar', [SuperAdminProfileController::class, 'deleteAvatar']);
            Route::get('/activity', [SuperAdminProfileController::class, 'getActivity']);
            Route::get('/sessions', [SuperAdminProfileController::class, 'getSessions']);
            Route::post('/sessions/{sessionId}/revoke', [SuperAdminProfileController::class, 'revokeSession']);
            Route::post('/sessions/revoke-all', [SuperAdminProfileController::class, 'revokeAllSessions']);
            Route::get('/preferences', [SuperAdminProfileController::class, 'getPreferences']);
            Route::put('/preferences', [SuperAdminProfileController::class, 'updatePreferences']);
            Route::put('/notifications', [SuperAdminProfileController::class, 'updateNotificationSettings']);
            Route::put('/security', [SuperAdminProfileController::class, 'updateSecuritySettings']);
            Route::put('/privacy', [SuperAdminProfileController::class, 'updatePrivacySettings']);
            Route::put('/theme', [SuperAdminProfileController::class, 'updateThemeSettings']);
            Route::put('/language', [SuperAdminProfileController::class, 'updateLanguageSettings']);
            Route::put('/timezone', [SuperAdminProfileController::class, 'updateTimezoneSettings']);
            Route::put('/currency', [SuperAdminProfileController::class, 'updateCurrencySettings']);
        });

        // ========================================
        // SISTEMA DE SEO E SITEMAPS (Super Admin)
        // ========================================
        Route::prefix('seo')->group(function () {
            Route::get('sitemap', [TenantSeoController::class, 'generateSitemapSuperAdmin']);
        });

        // ========================================
        // SISTEMA DE SITEMAPS (Super Admin)
        // ========================================
        Route::prefix('sitemap')->group(function () {
            Route::get('configs', [TenantSitemapController::class, 'getConfigs']);
            Route::post('configs', [TenantSitemapController::class, 'createConfig']);
            Route::get('configs/{id}', [TenantSitemapController::class, 'getConfig']);
            Route::put('configs/{id}', [TenantSitemapController::class, 'updateConfig']);
            Route::delete('configs/{id}', [TenantSitemapController::class, 'deleteConfig']);
            Route::post('generate', [TenantSitemapController::class, 'generateSitemap']);
        });

        // ========================================
        // SISTEMA DE ROBOTS.TXT (Super Admin)
        // ========================================
        Route::prefix('robots')->group(function () {
            Route::get('configs', [TenantRobotsController::class, 'listConfigs']);
            Route::post('configs', [TenantRobotsController::class, 'createConfig']);
            Route::get('configs/{id}', [TenantRobotsController::class, 'getConfig']);
            Route::put('configs/{id}', [TenantRobotsController::class, 'updateConfig']);
            Route::delete('configs/{id}', [TenantRobotsController::class, 'deleteConfig']);
            Route::post('generate', [TenantRobotsController::class, 'generateRobots']);
            Route::get('preview', [TenantRobotsController::class, 'previewRobots']);
        });

        // ========================================
        // SISTEMA DE URLs HIERÁRQUICAS (Super Admin)
        // ========================================
        Route::prefix('urls')->group(function () {
            Route::post('generate', [SuperAdminUrlController::class, 'generateUrls']);
            Route::get('stats/{tenant_id}', [SuperAdminUrlController::class, 'getStats']);
            Route::delete('clear/{tenant_id}', [SuperAdminUrlController::class, 'clearUrls']);
            Route::post('regenerate-all', [SuperAdminUrlController::class, 'regenerateAllUrls']);
        });

        // ========================================
        // SISTEMA DE LOGS E AUDITORIA (comentado até implementar controllers)
        // ========================================
        /*
        Route::prefix('logs')->group(function () {
            // Route::get('/', [SuperAdminLogController::class, 'index']);
            // Route::get('/{id}', [SuperAdminLogController::class, 'show']);
            // Route::get('/export', [SuperAdminLogController::class, 'export']);
            Route::delete('/clean', [SuperAdminLogController::class, 'clean']);
        });

        Route::prefix('audit')->group(function () {
            Route::get('/', [SuperAdminAuditController::class, 'index']);
            Route::get('/{id}', [SuperAdminAuditController::class, 'show']);
            Route::get('/export', [SuperAdminAuditController::class, 'export']);
        });
        */
    });
});

// ========================================
// ROTAS PÚBLICAS (SEM AUTENTICAÇÃO)
// ========================================

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

// ========================================
// ROTAS PÚBLICAS PARA ROBOTS.TXT (SEM AUTENTICAÇÃO)
// ========================================
Route::prefix('robots')->group(function () {
    Route::get('serve', [TenantRobotsController::class, 'serveRobotsFile']);
});

// ========================================
// ROTAS PÚBLICAS DE SEO (SEM AUTENTICAÇÃO)
// ========================================
Route::prefix('seo')->group(function () {
    // Resolver path SEO
    Route::get('resolve-path', [TenantSeoController::class, 'resolvePath']);

    // Gerar sitemap index
    Route::get('sitemap-index', [TenantSeoController::class, 'getSitemapIndex']);

    // Servir arquivo de sitemap
    Route::get('sitemap-file', [TenantSeoController::class, 'serveSitemapFile']);

    // Obter URL canônica
    Route::get('canonical-redirect', [TenantSeoController::class, 'getCanonicalRedirect']);

    // Preview de URL SEO
    Route::get('preview', [TenantSeoController::class, 'preview']);

    // Listar templates de spintax
    Route::get('templates', [TenantSeoController::class, 'getTemplates']);

    // Dados da organização do tenant
    Route::get('tenants/{tenant}/organization', [TenantSeoController::class, 'getTenantOrganization']);
});

// Rotas protegidas para SEO (admin do tenant)
Route::prefix('seo')->middleware(['token.auth.unified'])->group(function () {
    // Criar/atualizar URL SEO
    Route::post('urls', [TenantSeoController::class, 'createOrUpdateUrl']);
});

// Rotas do Portal de Anúncios (com identificação automática de tenant)
Route::prefix('portal')->middleware(['tenant.identification'])->group(function () {
    Route::get('vehicles', [PortalController::class, 'getVehicles']);
    Route::get('vehicles/{id}', [PortalController::class, 'getVehicle']);
    Route::get('filters', [PortalController::class, 'getFilters']);
    Route::post('leads', [PortalController::class, 'createLead']);
    Route::get('tenant-info', [PortalController::class, 'getTenantInfo']);
    Route::get('stats', [PortalController::class, 'getPortalStats']); // Nova rota para estatísticas
        Route::get('theme', [PortalController::class, 'getTenantTheme']);
        Route::get('social-media', [PortalController::class, 'getTenantSocialMedia']);
        Route::get('business-hours', [PortalController::class, 'getTenantBusinessHours']);
        Route::get('contact', [PortalController::class, 'getTenantContact']);
        Route::get('portal-config', [PortalController::class, 'getTenantPortalConfig']);
    });

// Login para usuários da tabela users (que não são super admin) - ROTA PÚBLICA
Route::post('user/login', [UserAuthController::class, 'login']);

// Rotas protegidas por autenticação unificada (usuários da tabela users + tenant_users)
Route::middleware(['token.auth.unified'])->group(function () {

    // Rotas protegidas para usuários da tabela users
    Route::post('user/logout', [UserAuthController::class, 'logout']);
    Route::get('user/me', [UserAuthController::class, 'me']);

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
        Route::get('{id}/images', [VehicleController::class, 'getImages']);
        Route::post('{id}/images', [VehicleController::class, 'uploadImages']);
        Route::delete('{id}/images/{imageId}', [VehicleController::class, 'deleteImage']);
        Route::match(['POST', 'PUT'], '{id}/images/{imageId}/primary', [VehicleController::class, 'setPrimaryImage']);
        Route::post('{id}/images/reorder', [VehicleController::class, 'reorderImages']);
    });

    // Rotas de importação de veículos
    Route::prefix('vehicles/import')->group(function () {
        Route::match(['GET', 'POST'], 'webmotors', [VehicleImportController::class, 'importFromWebmotors']);
        Route::match(['GET', 'POST'], 'olx', [VehicleImportController::class, 'importFromOlx']);
        Route::match(['GET', 'POST'], 'icarros', [VehicleImportController::class, 'importFromICarros']);
        Route::match(['GET', 'POST'], 'omegaveiculos', [VehicleImportController::class, 'importFromOmegaVeiculos']);
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

    // Rotas da API FIPE para Super Admin
    Route::prefix('fipe')->group(function () {
        Route::get('references', [FipeController::class, 'getReferences']);
        Route::get('brands/{vehicleType}', [FipeController::class, 'getBrands']);
        Route::get('brands/{vehicleType}/{brandId}/models', [FipeController::class, 'getModels']);
        Route::get('brands/{vehicleType}/{brandId}/models/{modelId}/years', [FipeController::class, 'getYears']);
        Route::get('vehicle/{vehicleType}/{brandId}/{modelId}/{yearId}', [FipeController::class, 'getVehicleInfo']);
        Route::get('search', [FipeController::class, 'searchVehicles']);
        Route::get('search/code/{codeFipe}', [FipeController::class, 'searchVehicleByCode']);
        Route::get('status', [FipeController::class, 'getStatus']);
        Route::get('usage-stats', [FipeController::class, 'getUsageStats']);
        Route::post('cache/clear', [FipeController::class, 'clearCache']);
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
        $user = \App\Helpers\TokenHelper::getAuthenticatedUser(request());

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

    // Dashboard do tenant (com autenticação - funciona com JWT e Sanctum)
    Route::get('dashboard', [DashboardController::class, 'index']);
});

// Rotas de Analytics (com autenticação unificada)
Route::prefix('analytics')->middleware(['token.auth.tenant', 'tenant.identification'])->group(function () {
    Route::get('dashboard', [AnalyticsController::class, 'getDashboard']);
    Route::get('page-views', [AnalyticsController::class, 'getPageViews']);
    Route::get('leads', [AnalyticsController::class, 'getLeadStats']);
    Route::get('search', [AnalyticsController::class, 'getSearchStats']);
});

// Rotas de Site Configuration (Admin do Cliente - com autenticação unificada)
Route::prefix('site-config')->middleware(['token.auth.tenant', 'tenant.identification'])->group(function () {
    Route::get('/', [SiteConfigurationController::class, 'getConfig']);
    Route::put('/', [SiteConfigurationController::class, 'updateBasicConfig']);
    Route::put('theme', [SiteConfigurationController::class, 'updateTheme']);
    Route::post('logo', [SiteConfigurationController::class, 'uploadLogo']);
    Route::put('social-media', [SiteConfigurationController::class, 'updateSocialMedia']);
    Route::put('business-hours', [SiteConfigurationController::class, 'updateBusinessHours']);
    Route::put('portal-settings', [SiteConfigurationController::class, 'updatePortalSettings']);
});

// Rotas de Perfil do Usuário (Admin do Cliente - com autenticação unificada)
Route::prefix('profile')->middleware(['token.auth.tenant', 'tenant.identification'])->group(function () {
    Route::get('/', [TenantUserProfileController::class, 'show']);
    Route::put('/', [TenantUserProfileController::class, 'update']);
    Route::put('password', [TenantUserProfileController::class, 'updatePassword']);
    Route::put('avatar', [TenantUserProfileController::class, 'updateAvatar']);
    Route::delete('avatar', [TenantUserProfileController::class, 'deleteAvatar']);
    Route::get('activity', [TenantUserProfileController::class, 'getActivity']);
    Route::get('sessions', [TenantUserProfileController::class, 'getSessions']);
    Route::post('sessions/{sessionId}/revoke', [TenantUserProfileController::class, 'revokeSession']);
    Route::post('sessions/revoke-all', [TenantUserProfileController::class, 'revokeAllSessions']);
    Route::get('preferences', [TenantUserProfileController::class, 'getPreferences']);
    Route::put('preferences', [TenantUserProfileController::class, 'updatePreferences']);
    Route::get('notifications', [TenantUserProfileController::class, 'getNotifications']);
    Route::put('notifications', [TenantUserProfileController::class, 'updateNotifications']);
    Route::get('security', [TenantUserProfileController::class, 'getSecuritySettings']);
    Route::put('security', [TenantUserProfileController::class, 'updateSecuritySettings']);
});

// Autenticação para Tenants (Admin Client)
Route::prefix('tenant')->group(function () {
    Route::post('login', [TenantAuthController::class, 'login']);
    Route::post('register', [TenantAuthController::class, 'register']);
    Route::post('forgot-password', [TenantAuthController::class, 'forgotPassword']);
    Route::post('reset-password', [TenantAuthController::class, 'resetPassword']);

    // Rotas protegidas do tenant (funciona com usuários da tabela users + tenant_users)
    Route::middleware(['token.auth.unified'])->group(function () {
        Route::post('logout', [TenantAuthController::class, 'logout']);
        Route::get('me', [TenantAuthController::class, 'me']);
        Route::get('profile', [TenantUserProfileController::class, 'show']);
        Route::put('profile', [TenantUserProfileController::class, 'update']);
        Route::put('profile/password', [TenantUserProfileController::class, 'updatePassword']);
        Route::put('profile/avatar', [TenantUserProfileController::class, 'updateAvatar']);
        Route::delete('profile/avatar', [TenantUserProfileController::class, 'deleteAvatar']);
        Route::get('profile/activity', [TenantUserProfileController::class, 'getActivity']);
        Route::get('profile/sessions', [TenantUserProfileController::class, 'getSessions']);
        Route::post('profile/sessions/{sessionId}/revoke', [TenantUserProfileController::class, 'revokeSession']);
        Route::post('profile/sessions/revoke-all', [TenantUserProfileController::class, 'revokeAllSessions']);
        Route::get('profile/preferences', [TenantUserProfileController::class, 'getPreferences']);
        Route::put('profile/preferences', [TenantUserProfileController::class, 'updatePreferences']);
        Route::put('profile/notifications', [TenantUserProfileController::class, 'updateNotificationSettings']);
        Route::put('profile/security', [TenantUserProfileController::class, 'updateSecuritySettings']);
        Route::put('profile/privacy', [TenantUserProfileController::class, 'updatePrivacySettings']);
        Route::put('profile/theme', [TenantUserProfileController::class, 'updateThemeSettings']);
        Route::put('profile/language', [TenantUserProfileController::class, 'updateLanguageSettings']);
        Route::put('profile/timezone', [TenantUserProfileController::class, 'updateTimezoneSettings']);
        Route::put('profile/currency', [TenantUserProfileController::class, 'updateCurrencySettings']);

        // Rotas de configuração do tenant (para admin cliente)
        Route::prefix('configuration')->group(function () {
            Route::get('/', [TenantConfigurationController::class, 'index']);
            Route::put('profile', [TenantConfigurationController::class, 'updateProfile']);
            Route::put('theme', [TenantConfigurationController::class, 'updateTheme']);
            Route::put('seo', [TenantConfigurationController::class, 'updateSeo']);
            Route::put('portal', [TenantConfigurationController::class, 'updatePortalSettings']);
            Route::get('preview', [TenantConfigurationController::class, 'preview']);
        });
    });
});

// Rotas públicas da API FIPE (sem autenticação, com rate limiting)
Route::prefix('public/fipe')->middleware(['fipe.rate.limit'])->group(function () {
    Route::get('references', [PublicFipeController::class, 'getReferences']);
    Route::get('brands/{vehicleType}', [PublicFipeController::class, 'getBrands']);
    Route::get('brands/{vehicleType}/{brandId}/models', [PublicFipeController::class, 'getModels']);
    Route::get('brands/{vehicleType}/{brandId}/models/{modelId}/years', [PublicFipeController::class, 'getYears']);
    Route::get('search', [PublicFipeController::class, 'searchVehicles']);
    Route::get('search/code/{codeFipe}', [PublicFipeController::class, 'searchVehicleByCode']);
    Route::get('status', [PublicFipeController::class, 'getStatus']);
    Route::post('calculate-price', [PublicFipeController::class, 'calculatePrice']);
});

// Rotas de Localizações por Tenant (identificação automática do tenant)
Route::middleware(['tenant.auto'])->prefix('tenant/locations')->group(function () {
    // Países
    Route::get('countries', [TenantLocationController::class, 'getCountries']);
    Route::post('countries', [TenantLocationController::class, 'addCountry']);
    Route::put('countries/{id}', [TenantLocationController::class, 'updateCountry']);
    Route::delete('countries/{id}', [TenantLocationController::class, 'removeCountry']);
    Route::get('available-countries', [TenantLocationController::class, 'getAvailableCountries']);

    // Estados
    Route::get('states', [TenantLocationController::class, 'getStates']);
    Route::post('states', [TenantLocationController::class, 'addState']);
    Route::put('states/{id}', [TenantLocationController::class, 'updateState']);
    Route::delete('states/{id}', [TenantLocationController::class, 'removeState']);
    Route::get('available-states', [TenantLocationController::class, 'getAvailableStates']);

    // Cidades
    Route::get('cities', [TenantLocationController::class, 'getCities']);
    Route::post('cities', [TenantLocationController::class, 'addCity']);
    Route::put('cities/{id}', [TenantLocationController::class, 'updateCity']);
    Route::delete('cities/{id}', [TenantLocationController::class, 'removeCity']);
    Route::get('available-cities', [TenantLocationController::class, 'getAvailableCities']);

    // Bairros
    Route::get('neighborhoods', [TenantLocationController::class, 'getNeighborhoods']);
    Route::post('neighborhoods', [TenantLocationController::class, 'addNeighborhood']);
    Route::put('neighborhoods/{id}', [TenantLocationController::class, 'updateNeighborhood']);
    Route::delete('neighborhoods/{id}', [TenantLocationController::class, 'removeNeighborhood']);
    Route::get('available-neighborhoods', [TenantLocationController::class, 'getAvailableNeighborhoods']);
});

// Rotas de Localizações (públicas para consulta, protegidas para CRUD)
Route::prefix('locations')->group(function () {
    // Rotas públicas para consulta
    Route::get('countries', [CountryController::class, 'index']);
    Route::get('countries/{id}', [CountryController::class, 'show']);
    Route::get('states', [StateController::class, 'index']);
    Route::get('states/{id}', [StateController::class, 'show']);
    Route::get('cities', [CityController::class, 'index']);
    Route::get('cities/{id}', [CityController::class, 'show']);
    Route::get('neighborhoods', [NeighborhoodController::class, 'index']);
    Route::get('neighborhoods/{id}', [NeighborhoodController::class, 'show']);

    // Rotas protegidas para CRUD (requer autenticação de super admin)
    Route::middleware(['auth:sanctum', 'super.admin'])->group(function () {
        Route::post('countries', [CountryController::class, 'store']);
        Route::put('countries/{id}', [CountryController::class, 'update']);
        Route::delete('countries/{id}', [CountryController::class, 'destroy']);

        Route::post('states', [StateController::class, 'store']);
        Route::put('states/{id}', [StateController::class, 'update']);
        Route::delete('states/{id}', [StateController::class, 'destroy']);

        Route::post('cities', [CityController::class, 'store']);
        Route::put('cities/{id}', [CityController::class, 'update']);
        Route::delete('cities/{id}', [CityController::class, 'destroy']);

        Route::post('neighborhoods', [NeighborhoodController::class, 'store']);
        Route::put('neighborhoods/{id}', [NeighborhoodController::class, 'update']);
        Route::delete('neighborhoods/{id}', [NeighborhoodController::class, 'destroy']);
    });
});

// Rotas de URLs Personalizadas por Tenant (identificação automática do tenant)
Route::middleware(['tenant.auto'])->prefix('tenant/urls')->group(function () {
    // Patterns de URL
    Route::get('patterns', [TenantUrlController::class, 'getPatterns']);
    Route::post('patterns', [TenantUrlController::class, 'createPattern']);
    Route::put('patterns/{id}', [TenantUrlController::class, 'updatePattern']);
    Route::delete('patterns/{id}', [TenantUrlController::class, 'deletePattern']);

    // Redirects
    Route::get('redirects', [TenantUrlController::class, 'getRedirects']);
    Route::post('redirects', [TenantUrlController::class, 'createRedirect']);
    Route::put('redirects/{id}', [TenantUrlController::class, 'updateRedirect']);
    Route::delete('redirects/{id}', [TenantUrlController::class, 'deleteRedirect']);
});

// Rotas de Sitemaps por Tenant (identificação automática do tenant) - APENAS LEITURA
Route::middleware(['tenant.auto'])->prefix('tenant/sitemap')->group(function () {
    // Apenas leitura de configurações
    Route::get('configs', [TenantSitemapController::class, 'getConfigs']);
    Route::get('configs/{id}', [TenantSitemapController::class, 'getConfig']);
});

// Rotas de Robots.txt por Tenant (identificação automática do tenant) - APENAS LEITURA
Route::middleware(['tenant.auto'])->prefix('tenant/robots-txt')->group(function () {
    // Apenas leitura de configuração
    Route::get('/', [TenantRobotsController::class, 'getConfig']);
});
