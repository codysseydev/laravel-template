<?php

declare(strict_types=1);

use {{PRODUCT_NAMESPACE}}\Health\GetStatus\GetStatusController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->prefix('api')->group(function (): void {
    Route::get('/health/status', GetStatusController::class)->name('health.status');
});
