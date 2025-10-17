<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
// No API routes should be defined here; all API endpoints belong in routes/api.php
