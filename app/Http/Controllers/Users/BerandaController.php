<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BerandaController extends Controller
{
    public function index()
    {
        return view('roles.users.beranda.index', [
            'title' => 'Beranda'
        ]);
    }
}
