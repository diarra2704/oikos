<?php

use App\Http\Controllers\AnniversaireController;
use App\Http\Controllers\BadgeController;
use App\Http\Controllers\CelluleController;
use App\Http\Controllers\CulteRapportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FdController;
use App\Http\Controllers\HistoriqueController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\InteractionController;
use App\Http\Controllers\KpiController;
use App\Http\Controllers\MembreController;
use App\Http\Controllers\DepartementController;
use App\Http\Controllers\FamilleImpactController;
use App\Http\Controllers\ParametrageController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RappelController;
use App\Http\Controllers\RapportController;
use App\Http\Controllers\RapportMensuelController;
use App\Http\Controllers\TemoignageController;
use App\Http\Controllers\TransfertController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ── Page d'accueil : redirige vers login ──
Route::get('/', fn () => redirect()->route('login'));

// ── Routes authentifiées ──
Route::middleware('auth')->group(function () {

    // Dashboard contextuel
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Anniversaires
    Route::get('/anniversaires', [AnniversaireController::class, 'index'])->name('anniversaires.index');

    // Historique / Dernières modifications (admin + superviseur)
    Route::get('/historique', [HistoriqueController::class, 'index'])->name('historique.index')->middleware('role:superviseur');

    // Envoi SMS (admin + superviseur)
    Route::get('/sms', [SmsController::class, 'index'])->name('sms.index')->middleware('role:superviseur');
    Route::get('/sms/create', [SmsController::class, 'create'])->name('sms.create')->middleware('role:superviseur');
    Route::get('/sms/membres-by-fd', [SmsController::class, 'membresByFd'])->name('sms.membres-by-fd')->middleware('role:superviseur');
    Route::post('/sms', [SmsController::class, 'store'])->name('sms.store')->middleware('role:superviseur');
    Route::delete('/sms/{envoi}', [SmsController::class, 'destroy'])->name('sms.destroy')->middleware('role:superviseur');

    // Recherche globale
    Route::get('/recherche', [SearchController::class, 'index'])->name('recherche');

    // Familles de Disciples
    Route::get('/fd', [FdController::class, 'index'])->name('fd.index')->middleware('role:superviseur');
    Route::post('/fd', [FdController::class, 'store'])->name('fd.store')->middleware('role:admin');
    Route::get('/fd/{fd}', [FdController::class, 'show'])->name('fd.show');
    Route::put('/fd/{fd}', [FdController::class, 'update'])->name('fd.update')->middleware('role:superviseur');
    Route::delete('/fd/{fd}', [FdController::class, 'destroy'])->name('fd.destroy')->middleware('role:admin');

    // Cellules
    Route::get('/cellules/{cellule}', [CelluleController::class, 'show'])->name('cellules.show');
    Route::post('/cellules', [CelluleController::class, 'store'])->name('cellules.store')->middleware('role:superviseur');
    Route::put('/cellules/{cellule}', [CelluleController::class, 'update'])->name('cellules.update')->middleware('role:superviseur');
    Route::delete('/cellules/{cellule}', [CelluleController::class, 'destroy'])->name('cellules.destroy')->middleware('role:superviseur');
    Route::post('/cellules/reaffecter-faiseur', [CelluleController::class, 'reaffecterFaiseur'])->name('cellules.reaffecterFaiseur')->middleware('role:superviseur');

    // Membres (âmes)
    Route::get('/membres', [MembreController::class, 'index'])->name('membres.index');
    Route::post('/membres/vues', [MembreController::class, 'storeVue'])->name('membres.vues.store');
    Route::delete('/membres/vues/{membreVue}', [MembreController::class, 'destroyVue'])->name('membres.vues.destroy');
    Route::get('/membres/export/csv', [ExportController::class, 'membresCsv'])->name('membres.export.csv');
    Route::get('/membres/export/excel', [ExportController::class, 'membresExcel'])->name('membres.export.excel');
    Route::get('/membres/create', [MembreController::class, 'create'])->name('membres.create');
    Route::post('/membres/check-doublon', [MembreController::class, 'checkDoublon'])->name('membres.checkDoublon');
    Route::post('/membres/suggestion-faiseur', [MembreController::class, 'suggererFaiseur'])->name('membres.suggererFaiseur');
    Route::post('/membres', [MembreController::class, 'store'])->name('membres.store');
    Route::get('/membres/{membre}', [MembreController::class, 'show'])->name('membres.show');
    Route::get('/membres/{membre}/edit', [MembreController::class, 'edit'])->name('membres.edit');
    Route::put('/membres/{membre}', [MembreController::class, 'update'])->name('membres.update');
    Route::put('/membres/{membre}/formations', [MembreController::class, 'updateFormations'])->name('membres.formations.update');
    Route::delete('/membres/{membre}', [MembreController::class, 'destroy'])->name('membres.destroy')->middleware('role:superviseur');

    // Interactions faiseur-âme
    Route::post('/membres/{membre}/interactions', [InteractionController::class, 'store'])->name('interactions.store');
    Route::delete('/interactions/{interaction}', [InteractionController::class, 'destroy'])->name('interactions.destroy');

    // Rappels (faiseurs)
    Route::get('/rappels', [RappelController::class, 'index'])->name('rappels.index')->middleware('role:faiseur');
    Route::post('/rappels', [RappelController::class, 'store'])->name('rappels.store');
    Route::put('/rappels/{rappel}/fait', [RappelController::class, 'marquerFait'])->name('rappels.fait');
    Route::delete('/rappels/{rappel}', [RappelController::class, 'destroy'])->name('rappels.destroy');

    // Invitations (invités au culte — 1 point par invité venu)
    Route::get('/invitations', [InvitationController::class, 'index'])->name('invitations.index');
    Route::get('/invitations/create', [InvitationController::class, 'create'])->name('invitations.create');
    Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
    Route::get('/invitations/{invitation}/edit', [InvitationController::class, 'edit'])->name('invitations.edit');
    Route::put('/invitations/{invitation}', [InvitationController::class, 'update'])->name('invitations.update');
    Route::delete('/invitations/{invitation}', [InvitationController::class, 'destroy'])->name('invitations.destroy');

    // Présences
    Route::get('/presences', [PresenceController::class, 'index'])->name('presences.index');
    Route::get('/presences/pointer', [PresenceController::class, 'create'])->name('presences.create');
    Route::post('/presences', [PresenceController::class, 'store'])->name('presences.store');

    // Rapport de fin de culte
    Route::get('/rapport-culte', [CulteRapportController::class, 'index'])->name('rapport-culte')->middleware('role:superviseur');

    // Rapports
    Route::get('/rapports', [RapportController::class, 'index'])->name('rapports.index');
    Route::get('/rapports/create', [RapportController::class, 'create'])->name('rapports.create');
    Route::post('/rapports', [RapportController::class, 'store'])->name('rapports.store');
    Route::get('/rapports/{rapport}', [RapportController::class, 'show'])->name('rapports.show');

    // Rapport mensuel faiseur
    Route::get('/rapport-mensuel', [RapportMensuelController::class, 'index'])->name('rapport-mensuel.index');
    Route::get('/rapport-mensuel/create', [RapportMensuelController::class, 'create'])->name('rapport-mensuel.create');
    Route::post('/rapport-mensuel', [RapportMensuelController::class, 'store'])->name('rapport-mensuel.store');
    Route::get('/rapport-mensuel/{rapportMensuel}', [RapportMensuelController::class, 'show'])->name('rapport-mensuel.show');

    // KPI (admin et superviseur)
    Route::get('/kpi', [KpiController::class, 'index'])->name('kpi.index')->middleware('role:superviseur');

    // Témoignages
    Route::get('/temoignages', [TemoignageController::class, 'index'])->name('temoignages.index');
    Route::get('/temoignages/create', [TemoignageController::class, 'create'])->name('temoignages.create');
    Route::post('/temoignages', [TemoignageController::class, 'store'])->name('temoignages.store');
    Route::get('/temoignages/{temoignage}', [TemoignageController::class, 'show'])->name('temoignages.show');
    Route::put('/temoignages/{temoignage}/valider', [TemoignageController::class, 'valider'])->name('temoignages.valider')->middleware('role:superviseur');

    // Transferts de membres
    Route::get('/transferts', [TransfertController::class, 'index'])->name('transferts.index')->middleware('role:superviseur');
    Route::get('/transferts/create', [TransfertController::class, 'create'])->name('transferts.create')->middleware('role:superviseur');
    Route::post('/transferts', [TransfertController::class, 'store'])->name('transferts.store')->middleware('role:superviseur');
    Route::get('/transferts/{transfert}', [TransfertController::class, 'show'])->name('transferts.show')->middleware('role:superviseur');
    Route::put('/transferts/{transfert}/traiter', [TransfertController::class, 'traiter'])->name('transferts.traiter')->middleware('role:admin');

    // Utilisateurs (admin)
    Route::get('/users', [UserController::class, 'index'])->name('users.index')->middleware('role:admin');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create')->middleware('role:admin');
    Route::post('/users', [UserController::class, 'store'])->name('users.store')->middleware('role:admin');
    Route::get('/users/{user}/reset-password', [UserController::class, 'showResetPassword'])->name('users.reset-password')->middleware('role:admin');
    Route::put('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password.update')->middleware('role:admin');

    // Paramètrage (admin)
    Route::get('/parametrage', [ParametrageController::class, 'index'])->name('parametrage.index')->middleware('role:admin');
    Route::post('/parametrage', [ParametrageController::class, 'store'])->name('parametrage.store')->middleware('role:admin');
    Route::put('/parametrage/{parametreValeur}', [ParametrageController::class, 'update'])->name('parametrage.update')->middleware('role:admin');
    Route::delete('/parametrage/{parametreValeur}', [ParametrageController::class, 'destroy'])->name('parametrage.destroy')->middleware('role:admin');
    // Familles d'impact (CRUD)
    Route::get('/parametrage/familles-impact', [FamilleImpactController::class, 'index'])->name('parametrage.familles-impact.index')->middleware('role:admin');
    Route::post('/parametrage/familles-impact', [FamilleImpactController::class, 'store'])->name('parametrage.familles-impact.store')->middleware('role:admin');
    Route::put('/parametrage/familles-impact/{familleImpact}', [FamilleImpactController::class, 'update'])->name('parametrage.familles-impact.update')->middleware('role:admin');
    Route::delete('/parametrage/familles-impact/{familleImpact}', [FamilleImpactController::class, 'destroy'])->name('parametrage.familles-impact.destroy')->middleware('role:admin');
    // Départements / services (CRUD)
    Route::get('/parametrage/departements', [DepartementController::class, 'index'])->name('parametrage.departements.index')->middleware('role:admin');
    Route::post('/parametrage/departements', [DepartementController::class, 'store'])->name('parametrage.departements.store')->middleware('role:admin');
    Route::put('/parametrage/departements/{departement}', [DepartementController::class, 'update'])->name('parametrage.departements.update')->middleware('role:admin');
    Route::delete('/parametrage/departements/{departement}', [DepartementController::class, 'destroy'])->name('parametrage.departements.destroy')->middleware('role:admin');

    // Badges / Gamification
    Route::get('/honneur', [BadgeController::class, 'honneur'])->name('honneur');
    Route::get('/mon-profil-badges', [BadgeController::class, 'profil'])->name('badges.profil');

    // Profil utilisateur
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
