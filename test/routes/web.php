<?php

use App\Http\Controllers\PlebicomController;
use App\Services\PlebicomService;
use Illuminate\Support\Facades\Route;


//List Catalog Route
Route::get('/plebicom/catalog', [PlebicomController::class, 'listCatalog'])->name('plebicom.catalog');


//Test Authentication Route
// Route::get('/test-auth', function(PlebicomService $plebicomService){
//     try{
//         $response = $plebicomService->authenticate();

//         return response()->json([
//             'response' => $response
//         ]);
//     } catch(Exception $e) {
//         return response()->json([
//             'message' => $e->getMessage()
//         ], 500);
//     }
// });