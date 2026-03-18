<?php

namespace App\Http\Controllers;

use App\Models\PlayerTracker;
use App\Models\Wordle;
use Illuminate\Http\Request;

class WorldeApiController extends Controller
{
    public function setWordle(Request $request)
    {
        $word = $request->word;
        $date = $request->date;

        $request->validate([
            'word' => 'required',
            'date' => 'required|date',
        ]);

        $upperWord = collect($word)->map(fn ($w) => strtoupper($w));

        Wordle::updateOrCreate(
            ['date' => $date],
            ['word' => $upperWord]
        );

        return response()->json([
            'message' => 'Data insert successfully.'
        ], 200);
    }

    public function playerTracker () {
        $data = PlayerTracker::get();

        return response()->json([
            'message' => 'success',
            'data' => $data
        ], 200);
    }
}
