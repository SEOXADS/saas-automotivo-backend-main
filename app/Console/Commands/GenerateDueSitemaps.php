public function handle()
{
    $configs = TenantSitemapConfig::where('is_active', true)->get();

    foreach ($configs as $config) {
        if ($this->isDue($config)) {
            // Call your generation logic
            app(TenantSitemapController::class)->generateSitemap($config->tenant_id);
            // Update last_run_at timestamp, etc.
        }
    }
}

protected function isDue($config)
{
    // Implement logic to check if it's time to run based on change_frequency and last_run_at
}