<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PasswordResetController extends Controller
{
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        
        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json(['message' => 'Email not found'], 404);
            }

            $token = Str::random(64);

            DB::table('password_resets')->updateOrInsert(
                ['email' => $request->email],
                ['token' => $token, 'created_at' => Carbon::now()]
            );

            $resetLink = env('FRONTEND_URL') . "/reset-password/$token?email=" . urlencode($request->email);

            Mail::raw("Click here to reset your password: $resetLink", function ($message) use ($request) {
                $message->to($request->email)->subject('Password Reset Link');
            });

            return response()->json(['message' => 'Reset link sent! Check your email.']);
            
        } catch (\Exception $e) {
            \Log::error('Password reset error: ' . $e->getMessage());
            return response()->json(['message' => 'Unable to send reset link. Please try again.'], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|confirmed|min:8',
        ]);

        try {
            $record = DB::table('password_resets')
                ->where('email', $request->email)
                ->where('token', $request->token)
                ->first();

            if (!$record) {
                return response()->json(['error' => 'Invalid token'], 400);
            }

            if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
                DB::table('password_resets')->where('email', $request->email)->delete();
                return response()->json(['error' => 'Token expired'], 400);
            }

            $user = User::where('email', $request->email)->first();
            
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }
            
            $user->update(['password' => Hash::make($request->password)]);

            DB::table('password_resets')->where('email', $request->email)->delete();

            return response()->json(['message' => 'Password reset successful']);
            
        } catch (\Exception $e) {
            \Log::error('Password reset error: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to reset password. Please try again.'], 500);
        }
    }
}
