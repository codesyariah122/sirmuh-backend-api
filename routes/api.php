<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{LoginController, PublicFeatureController};
use App\Core\RoutingMiddleware;


Route::middleware(['auth:api', 'cors', 'json.response', 'session.expired'])->prefix('v1')->group(function () {

    RoutingMiddleware::insideAuth();

});

Route::middleware('cors')->prefix('v1')->group(function () {
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/detail', [PublicFeatureController::class, 'detail_data']);
});
