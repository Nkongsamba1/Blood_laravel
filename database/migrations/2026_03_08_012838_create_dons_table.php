<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dons', function (Blueprint $table) {
            $table->id();

            // --- RELATIONS ---
            // On lie au donneur (clé primaire 'donneur_id' dans la table 'donneurs')
            $table->foreignId('donneur_id')->constrained('donneurs', 'donneur_id')->onDelete('cascade');

            // On lie au personnel (clé primaire 'utilisateur_id' dans la table 'users')
            $table->foreignId('personnel_id')->nullable()->constrained('users', 'utilisateur_id')->onDelete('set null');

            // On lie à la campagne (clé primaire 'id' par défaut dans 'campagnes')
            $table->foreignId('campagne_id')->nullable()->constrained('campagnes')->onDelete('set null');

            // --- CONSTANTES MÉDICALES ---
            $table->string('tension_arterielle')->nullable();
            $table->float('poids_donneur')->nullable();
            $table->boolean('est_apte')->default(false);

            // --- INFORMATIONS TECHNIQUES ---
            $table->string('num_poche')->unique()->nullable();
            $table->integer('quantite')->nullable(); // En ml
            $table->string('type_don')->default('Sang Total');
            $table->time('heure_debut')->nullable();
            $table->time('heure_fin')->nullable();


            // --- LOGISTIQUE ---
            $table->date('date_don');
            $table->time('heure_rdv')->nullable();
            $table->string('lieu')->default('Hôpital Saint-Raphaël');
            $table->string('statut')->default('En attente');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dons');
    }
};
