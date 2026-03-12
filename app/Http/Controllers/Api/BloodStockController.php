<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BloodStockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    
        public function index()
{
    // Pour l'instant, on renvoie des données de test
    // Demain, on fera : return Don::all();
    return response()->json([
        ['group' => 'A+', 'pockets' => 12, 'status' => 'Stable'],
        ['group' => 'O-', 'pockets' => 3, 'status' => 'Critique'],
        ['group' => 'B+', 'pockets' => 25, 'status' => 'Optimal'],
    ]);
}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
