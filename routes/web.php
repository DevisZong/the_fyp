<?php

use Illuminate\Support\Facades\Route;
// require __DIR__.'/child.php';


Route::get('/', function () {
    return view('welcome');
});
