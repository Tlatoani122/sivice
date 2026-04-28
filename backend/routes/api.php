<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AspiranteController;

Route::get('/aspirantes', [AspiranteController::class, 'index']);
Route::get('/aspirantes/{id}', [AspiranteController::class, 'show']);
Route::get('/aspirantes/{id}/examen', [AspiranteController::class, 'examen']);