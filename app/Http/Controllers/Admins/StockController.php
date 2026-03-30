<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index()
    {
        return view('roles.admins.stock.index', [
            'title' => 'Stock'
        ]);
    }
}
