<?php
// app/Providers/PemenuhanDokumenServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PemenuhanDokumen\{
    PemenuhanDokumenService,
    JadwalService,
    StatusService
};
use App\Repositories\PemenuhanDokumen\{
    KriteriaDokumenRepository,
    JadwalAmiRepository
};

class PemenuhanDokumenServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(KriteriaDokumenRepository::class, function ($app) {
            return new KriteriaDokumenRepository();
        });

        $this->app->bind(JadwalAmiRepository::class, function ($app) {
            return new JadwalAmiRepository();
        });

        $this->app->bind(StatusService::class, function ($app) {
            return new StatusService();
        });

        $this->app->bind(JadwalService::class, function ($app) {
            return new JadwalService($app->make(StatusService::class));
        });

        $this->app->bind(PemenuhanDokumenService::class, function ($app) {
            return new PemenuhanDokumenService(
                $app->make(StatusService::class),        // Parameter pertama harus StatusService
                $app->make(JadwalService::class),        // Parameter kedua JadwalService
                $app->make(KriteriaDokumenRepository::class),
                $app->make(JadwalAmiRepository::class)
            );
        });
    }
}