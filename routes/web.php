<?php

use App\Http\Controllers\SeasonController;
use App\Livewire\SeasonDetector;
use Illuminate\Support\Facades\Route;

Route::livewire('/', SeasonDetector::class)->name('home');

Route::post('/season/check', [SeasonController::class, 'check'])->name('season.check');
