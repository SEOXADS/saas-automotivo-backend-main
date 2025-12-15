<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class TestGuards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:guards';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test authentication guards configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing authentication guards...');

        $guards = config('auth.guards');

        $this->info('Configured guards:');
        foreach ($guards as $name => $guard) {
            $this->line("- {$name} => {$guard['driver']} ({$guard['provider']})");
        }

        $this->info('Testing tenant guard specifically...');
        try {
            $tenantGuard = Auth::guard('tenant');
            $this->info('✅ Tenant guard is accessible: ' . get_class($tenantGuard));
        } catch (\Exception $e) {
            $this->error('❌ Tenant guard error: ' . $e->getMessage());
        }

        return 0;
    }
}
