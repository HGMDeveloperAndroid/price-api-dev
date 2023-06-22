<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\PasswordResets;
use App\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;


class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'password' => 'required|confirmed|min:6',
        ]);

        $passwordReset = PasswordResets::where('token', $request->token)->first();

        if (!$passwordReset) {
            return response()->json([
                'error' => true,
                'message' => 'This Password Reset token is invalid.'
            ], 404);
        }

        $user = User::where('email', $passwordReset->email)->first();
//        dd($user);
        if (!$user) {
            return response()->json([
                'error' => true,
                'message' => 'We cannot find a user with that Email Address'
            ], 404);
        }

//        $this->validate($request, $this->rules(), $this->validationErrorMessages());
//
        $user->password = bcrypt($request->password);
        $user->save();
        $passwordReset->delete();

        return response()->json([
            'error' => false,
            'message' => 'Your Password changed successfully.'
        ]);
    }
}
