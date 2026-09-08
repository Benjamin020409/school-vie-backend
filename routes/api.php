<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\EleveController;
use App\Http\Controllers\InscriptionController;
use App\Http\Controllers\ClasseController;
use App\Http\Controllers\AnneeScolaireController;
use App\Http\Controllers\NiveauController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\MatiereController;
use App\Http\Controllers\EnseignantController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\EmploiDuTempsController;
use App\Http\Controllers\PeriodeController;
use App\Http\Controllers\BulletinController;
use App\Http\Controllers\FraisScolariteController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\PresenceController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', function (Request $request) {
        return $request->user()->load('roles');
    });


    //Secretaire

    Route::get('/eleves', [EleveController::class, 'index']);
    Route::post('/eleves', [EleveController::class, 'store']);
    Route::get('/eleves/{eleve}', [EleveController::class, 'show']);
    Route::put('/eleves/{eleve}', [EleveController::class, 'update']);
    Route::get('/eleves/{eleve}/fiche-inscription', [EleveController::class, 'ficheInscription']);

    Route::get('/eleves/{eleve}/documents', [DocumentController::class, 'index']);
    Route::post('/eleves/{eleve}/documents', [DocumentController::class, 'store']);
    Route::get('/documents/{document}/telecharger', [DocumentController::class, 'telecharger']);
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy']);

    Route::put('/niveaux/{niveau}/matieres', [NiveauController::class, 'syncMatieres']);
    Route::get('/matieres', [MatiereController::class, 'index']);
    Route::post('/matieres', [MatiereController::class, 'store']);

    Route::get('/inscriptions', [InscriptionController::class, 'index']);
    Route::post('/inscriptions', [InscriptionController::class, 'store']);
    Route::get('/inscriptions/stats', [InscriptionController::class, 'stats']);

    Route::get('/classes', [ClasseController::class, 'index']);
    Route::post('/classes', [ClasseController::class, 'store']);

    Route::get('/niveaux', [NiveauController::class, 'index']);
    Route::post('/niveaux', [NiveauController::class, 'store']);

    Route::get('/annees-scolaires', [AnneeScolaireController::class, 'index']);
    Route::post('/annees-scolaires', [AnneeScolaireController::class, 'store']);
    Route::put('/annees-scolaires/{anneeScolaire}', [AnneeScolaireController::class, 'update']);

    Route::post('/niveaux/{niveau}/generer-emploi-du-temps', [EmploiDuTempsController::class, 'genererPourNiveau']);
    Route::get('/classes/{classe}/emploi-du-temps-pdf', [EmploiDuTempsController::class, 'classePdf']);
    Route::get('/mon-espace/mon-emploi-du-temps', [EmploiDuTempsController::class, 'monEmploiDuTemps']);
    Route::get('/mon-espace/mon-emploi-du-temps-pdf', [EmploiDuTempsController::class, 'monEmploiDuTempsPdf']);

    Route::post('/classes/{classe}/bulletins/preparer', [BulletinController::class, 'preparer']);
    Route::get('/classes/{classe}/bulletins', [BulletinController::class, 'liste']);
    Route::post('/classes/{classe}/bulletins/valider', [BulletinController::class, 'valider']);
    Route::get('/bulletins/{bulletin}/telecharger', [BulletinController::class, 'telecharger']);



    //Directeur

    Route::get('/enseignants', [EnseignantController::class, 'index']);
    Route::post('/enseignants', [EnseignantController::class, 'store']);
    Route::put('/enseignants/{enseignant}/affectations', [EnseignantController::class, 'syncAffectations']);
    Route::get('/frais-scolarite', [FraisScolariteController::class, 'index']);
    Route::post('/niveaux/{niveau}/frais-scolarite', [FraisScolariteController::class, 'definir']);


    //Enseignant
    Route::get('/mon-espace/mes-classes', [EnseignantController::class, 'mesClasses']);
    Route::put('/enseignants/{enseignant}/specialites', [EnseignantController::class, 'syncSpecialites']);
    Route::get('/mon-espace/mes-affectations', [EnseignantController::class, 'mesAffectations']);
    Route::get('/mon-espace/classes/{classe}/liste-pdf', [EnseignantController::class, 'listeClassePdf']);

    Route::get('/evaluations', [EvaluationController::class, 'index']);
    Route::post('/evaluations', [EvaluationController::class, 'store']);
    Route::get('/notes/recap', [EvaluationController::class, 'recap']);
    Route::get('/evaluations/{evaluation}/notes', [EvaluationController::class, 'notes']);
    Route::post('/evaluations/{evaluation}/notes', [EvaluationController::class, 'storeNotes']);
    Route::get('/periodes', [PeriodeController::class, 'index']);
    Route::post('/periodes', [PeriodeController::class, 'store']);




    // Comptable
    Route::get('/comptable/vue-ensemble', [PaiementController::class, 'vueEnsemble']);
    Route::get('/classes/{classe}/eleves-paiement', [PaiementController::class, 'eleves']);
    Route::post('/paiements', [PaiementController::class, 'store']);
    Route::get('/classes/{classe}/tableau-paiements', [PaiementController::class, 'tableau']);
    Route::get('/paiements/{paiement}/recu', [PaiementController::class, 'recuPdf']);




    //Surveillant
    Route::get('/classes/{classe}/fiche-presence', [ClasseController::class, 'fichePresence']);

    Route::get('/classes/{classe}/eleves-presence', [PresenceController::class, 'elevesDuJour']);
    Route::get('/presences/aujourdhui', [PresenceController::class, 'aujourdhui']);
    Route::post('/presences', [PresenceController::class, 'store']);
    Route::put('/presences/{presence}/justifier', [PresenceController::class, 'justifier']);
    Route::delete('/presences/{presence}', [PresenceController::class, 'remettrePresent']);
});