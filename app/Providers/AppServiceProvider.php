<?php

namespace App\Providers;

use App\Models\Cellule;
use App\Models\FamilleImpact;
use App\Models\Invitation;
use App\Models\Membre;
use App\Models\ParametreValeur;
use App\Models\Rapport;
use App\Models\RapportMensuel;
use App\Models\Transfert;
use App\Observers\AuditObserver;
use App\Observers\InvitationObserver;
use App\Observers\MembreObserver;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fix MySQL key length for utf8mb4
        Builder::defaultStringLength(191);

        Vite::prefetch(concurrency: 3);

        Invitation::observe(InvitationObserver::class);
        Membre::observe(MembreObserver::class);

        // Audit trail (journal des modifications)
        Membre::observe(AuditObserver::class);
        Cellule::observe(AuditObserver::class);
        Transfert::observe(AuditObserver::class);
        Rapport::observe(AuditObserver::class);
        RapportMensuel::observe(AuditObserver::class);
        FamilleImpact::observe(AuditObserver::class);
        ParametreValeur::observe(AuditObserver::class);
    }
}
