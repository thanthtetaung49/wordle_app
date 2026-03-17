<?php

namespace App\Http\Controllers;

use App\Models\PlayerInformation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WordleGameController extends Controller
{
    public function index()
    {
        return Inertia::render('Welcome');
    }

    public function registerPlayer(Request $request)
    {
        $validated = $request->validate([
            'tid'   => 'required',
            'name'  => 'required',
            'email' => 'required|email',
            'dept'  => 'required',
        ]);

        PlayerInformation::updateOrCreate(['tid' => $validated['tid']], $validated);

        return redirect()->route('home');
    }
}
