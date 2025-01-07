<?php

namespace App\Http\Controllers\Web;

use Illuminate\Routing\Controller as BaseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends BaseController
{
    public function index()
    {
        $user = auth()->user();

        return view('dashboard', [
            'user' => $user,
            // ...
        ]);
    }
}
