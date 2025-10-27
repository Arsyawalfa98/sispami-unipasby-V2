<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Prodi;
use App\Models\Siakad;
use Illuminate\Support\Facades\DB;

class SyncUserProdiFromMitra extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:sync-prodi
                           {--user= : Sync specific user by username}
                           {--all : Sync all users}
                           {--force : Force re-sync even if prodi already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync multi-prodi data dari sistem mitra (gate.sc_userrole) ke pivot table user_prodi';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting user prodi synchronization...');
        $this->newLine();

        // Get target users
        $users = $this->getTargetUsers();

        if ($users->isEmpty()) {
            $this->error('❌ No users found to sync.');
            return 1;
        }

        $this->info("📊 Found {$users->count()} user(s) to sync.");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($users->count());
        $progressBar->start();

        $stats = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'total_prodis' => 0,
        ];

        foreach ($users as $user) {
            $result = $this->syncUserProdi($user);

            $stats['success'] += $result['success'] ? 1 : 0;
            $stats['failed'] += $result['failed'] ? 1 : 0;
            $stats['skipped'] += $result['skipped'] ? 1 : 0;
            $stats['total_prodis'] += $result['prodis_count'];

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display summary
        $this->info('✅ Synchronization completed!');
        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Users Processed', $users->count()],
                ['Successful', $stats['success']],
                ['Failed', $stats['failed']],
                ['Skipped', $stats['skipped']],
                ['Total Prodis Synced', $stats['total_prodis']],
            ]
        );

        return 0;
    }

    /**
     * Get target users based on options
     */
    protected function getTargetUsers()
    {
        if ($this->option('user')) {
            // Sync specific user
            $username = $this->option('user');
            $user = User::where('username', $username)->first();

            if (!$user) {
                $this->error("❌ User with username '{$username}' not found.");
                return collect([]);
            }

            return collect([$user]);
        }

        if ($this->option('all')) {
            // Sync all active users
            return User::where('is_active', 1)->get();
        }

        // Interactive mode
        if (!$this->confirm('Do you want to sync all users?', false)) {
            $username = $this->ask('Enter username to sync');
            if ($username) {
                $user = User::where('username', $username)->first();
                return $user ? collect([$user]) : collect([]);
            }
            return collect([]);
        }

        return User::where('is_active', 1)->get();
    }

    /**
     * Sync prodi for a specific user
     */
    protected function syncUserProdi(User $user)
    {
        try {
            // Get user ID dari sistem mitra
            $mitraUserId = Siakad::getMitraUserId($user->username);

            if (!$mitraUserId) {
                $this->newLine();
                $this->warn("⚠️  Cannot find mitra user ID for: {$user->username}");
                return ['success' => false, 'failed' => false, 'skipped' => true, 'prodis_count' => 0];
            }

            // Get semua prodi dari sc_userrole + home base
            $mitraProdis = Siakad::getUserProdisFromMitra($mitraUserId, $user->username);

            if (empty($mitraProdis)) {
                $this->newLine();
                $this->warn("⚠️  No prodis found in mitra for: {$user->username}");
                return ['success' => false, 'failed' => false, 'skipped' => true, 'prodis_count' => 0];
            }

            // Sync ke pivot table
            $prodisCount = 0;
            DB::transaction(function () use ($user, $mitraProdis, &$prodisCount) {
                $existingProdis = $user->prodis->pluck('kode_prodi')->toArray();
                $isFirst = $user->prodis->isEmpty();
                $force = $this->option('force');

                foreach ($mitraProdis as $index => $mitraProdi) {
                    // Skip jika sudah ada (kecuali force)
                    if (!$force && in_array($mitraProdi->kode_prodi, $existingProdis)) {
                        continue;
                    }

                    // Update or create
                    Prodi::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'kode_prodi' => $mitraProdi->kode_prodi,
                        ],
                        [
                            'nama_prodi' => $mitraProdi->nama_prodi,
                            'kode_fakultas' => $mitraProdi->kode_fakultas,
                            'nama_fakultas' => $mitraProdi->nama_fakultas,
                            'is_default' => $isFirst && $index === 0,
                        ]
                    );

                    $prodisCount++;
                }
            });

            return ['success' => true, 'failed' => false, 'skipped' => false, 'prodis_count' => $prodisCount];
        } catch (\Exception $e) {
            $this->newLine();
            $this->error("❌ Error syncing {$user->username}: " . $e->getMessage());
            return ['success' => false, 'failed' => true, 'skipped' => false, 'prodis_count' => 0];
        }
    }
}
