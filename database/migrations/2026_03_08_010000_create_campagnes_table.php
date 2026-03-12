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
    Schema::create('campagnes', function (Blueprint $table) {
        $table->id();
        $table->string('titre');

        $table->string('lieu');
        $table->date('date_debut'); // Jour de début de la campagne
        $table->date('date_fin');   // Jour de fin
        $table->integer('capacite_max')->default(50);
        $table->text('description')->nullable();
        $table->json('groupes_cibles')->nullable();

        // C'est ici qu'on stocke les jours ouvrables et leurs heures
        // Format : [{"name":"Lundi","start":"08:00","end":"16:00"}, ...]
        $table->json('planning');

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campagnes');
    }
};
