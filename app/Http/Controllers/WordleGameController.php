<?php

namespace App\Http\Controllers;

use App\Models\PlayerTracker;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
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
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'confirmPassword' => 'required|same:password',
            'dept'  => 'required',
        ]);

        $validated = array_merge($validated, [
            'status' => $request->status,
            'password' => Hash::make($request->password)
        ]);

        $user = User::updateOrCreate(['tid' => $validated['email']], $validated);

        Auth::login($user);

        return redirect()->route('home');
    }

    public function loginPlayer(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => 'This account does not exist. Please register first.',
            ]);
        }

        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'The provided password is incorrect.',
            ]);
        }

        $user->update([
            'status' => 'active',
        ]);

        Auth::login($user);

        return redirect()->route('home');
    }

    public function existGame($id)
    {
        $user = User::findOrFail($id);

        $user->update([
            'status' => 'inactive',
        ]);

        Auth::logout($user);

        return redirect()->route('home');
    }

    public function storeActivity(Request $request)
    {
        PlayerTracker::create([
            'user_id' => Auth::id(),
            'answer' => $request->answer,
            'attempt_number' => $request->attempt_number,
            'result' => $request->result,
            'activity_date' => Carbon::now()->timezone('Asia/Yangon')
        ]);
    }

    public function getActivity($id = null)
    {
        if (Auth::user()->role === 'admin') {
            $activityLogs = PlayerTracker::with('user')->get();
        } else {
            $activityLogs = PlayerTracker::with('user')->where('user_id', $id)->get();
        }

        return Inertia::render('PlayerActivities', [
            'activityLogs' => $activityLogs
        ]);
    }
}
