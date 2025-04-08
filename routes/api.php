<?php

use App\Http\Controllers\Api\CategoryController;
use Illuminate\Support\Facades\Route;

    //  Categories
    Route::get('categories', [CategoryController::class, 'index']);
    Route::post('categories', [CategoryController::class, 'store']);
    Route::get('categories/{id}', [CategoryController::class, 'show']);
    Route::put('categories/{id}', [CategoryController::class, 'update']);
    Route::delete('categories/{id}', [CategoryController::class, 'destroy']);
    Route::get('/categories-tree', [CategoryController::class, 'tree']);
