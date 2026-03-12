<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up() {
    Schema::create('donneurs', function (Blueprint $table) {
        $table->bigIncrements('donneur_id');
        $table->foreignId('utilisateur_id')->constrained('users', 'utilisateur_id')->onDelete('cascade');
        $table->string('groupe_sanguin'); // Ex: A+, O-
        $table->string('genre'); // Masculin/Féminin
        $table->date('date_naissance'); //
        $table->string('telephone'); //
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donneurs');
    }
};
