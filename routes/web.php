<?php

use App\Livewire\MediaSummarizer;
use App\Livewire\SummaryDisplay;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/', MediaSummarizer::class);
Route::get('/summary/{summaryId}', SummaryDisplay::class)->name('summary.display');


Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
