<?php

namespace App\Http\Controllers;

use App\Models\PlayerTracker;
use App\Models\Wordle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $upperWord = collect($word)->map(fn($w) => strtoupper($w));

        Wordle::updateOrCreate(
            ['date' => $date],
            ['word' => $upperWord]
        );

        return response()->json([
            'message' => 'Data insert successfully.'
        ], 200);
    }

    public function playerTracker()
    {
        $data = PlayerTracker::get();

        return response()->json([
            'message' => 'success',
            'data' => $data
        ], 200);
    }

    public function playerWinner()
    {
        $firstDayOfWeek = now()->startOfWeek()->toDateString();
        $lastDayOfWeek = now()->endOfWeek()->toDateString();

        $minAttempt = PlayerTracker::whereBetween('activity_date', [$firstDayOfWeek, $lastDayOfWeek])
            ->where('result', 'correct')
            ->min('attempt_number');

        $winner = PlayerTracker::select('user_id', 'attempt_number', 'activity_date', 'result', 'answer')->with([
            'user' => function ($query) {
                $query->select('id', 'name', 'email', 'tid', 'dept');
            }
        ])->whereBetween('activity_date', [$firstDayOfWeek, $lastDayOfWeek])
            ->where('result', 'correct')
            ->where('attempt_number', $minAttempt)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'message' => 'success',
            'data' => $winner
        ]);
    }
}
