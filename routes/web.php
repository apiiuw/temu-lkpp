<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('roles.users.beranda.index');
});
