<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
public function index()
{
    // On définit l'objectif idéal par groupe (ex: 30 poches pour être en sécurité)
    $objectifSecurite = 30;

    $stocks = DB::table('donneurs')
        ->join('dons', 'donneurs.donneur_id', '=', 'dons.donneur_id')
        ->select(
            'donneurs.groupe_sanguin', 
            DB::raw('count(dons.id) as total_poches'),
            // Calcul du taux : (total / objectif) * 100
            DB::raw("ROUND((count(dons.id) / $objectifSecurite) * 100) as taux_remplissage")
        )
        ->where('dons.statut', '=', 'Terminé') // On ne compte que les poches réelles
        ->groupBy('donneurs.groupe_sanguin')
        ->get();

    return response()->json($stocks);
}}