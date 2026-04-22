<?php

use Illuminate\Support\Facades\Route;

$spa = static fn () => response()->file(public_path('index.html'));

Route::get('/{any}', $spa)->where('any', '.*');
