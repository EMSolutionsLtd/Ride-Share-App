<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\LoginNeedsVerification;

class LoginController extends Controller
{
    public function submit(Request $request)
    {
        // Validate the phone number
        $request->validate([
            'phone' => 'required|numeric|min:10'
            // 'mail' => 'required'
        ]);

        // Find or create a user model
        $user = User::firstOrCreate([
            'phone' => $request->phone
            // 'mail' => $request->mail
        ]);

        if(!$user) {
            return response()->json(['message' => 'Could not process a user with that phone number.'], 401);
        }

        // Send the user a one-time use code
        $user->notify(new LoginNeedsVerification());

        // Return back a response
        return response()->json(['message' => 'Text message notification sent.']);
    }

    public function verify(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'phone' => 'required|numeric|min:10',
            'login_code' => 'required|numeric|between:111111,999999'
        ]);

        // Find the user
        $user = User::where('phone', $request->phone)
            ->where('login_code', $request->login_code)
            ->first();

        //is the code provided the same one saved?
        // if so, return back an auth token
        if($user) {
            $user->update([
                'login_code' => null
            ]);

            return $user->createToken($request->login_code)->plainTextToken;
        }

        // if not, return back a message
        return response()->json(['message' => 'Invalid verification code.'], 401);
    }
}
