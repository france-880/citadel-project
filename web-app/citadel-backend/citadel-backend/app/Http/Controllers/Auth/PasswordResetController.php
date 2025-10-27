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

            \Log::info('Sending password reset email', [
                'email' => $request->email,
                'link' => $resetLink
            ]);

            Mail::send([], [], function ($message) use ($request, $resetLink) {
                $message->to($request->email)
                    ->subject('Password Reset Request - Citadel')
                    ->html("
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                            <h2 style='color: #064F32;'>Password Reset Request</h2>
                            <p>Hello,</p>
                            <p>You have requested to reset your password for your Citadel account.</p>
                            <p>Click the button below to reset your password:</p>
                            <div style='text-align: center; margin: 30px 0;'>
                                <a href='$resetLink' 
                                   style='background-color: #064F32; 
                                          color: white; 
                                          padding: 12px 30px; 
                                          text-decoration: none; 
                                          border-radius: 5px; 
                                          display: inline-block;'>
                                    Reset Password
                                </a>
                            </div>
                            <p>Or copy and paste this link into your browser:</p>
                            <p style='word-break: break-all; color: #0066cc;'>$resetLink</p>
                            <p style='color: #666; font-size: 12px; margin-top: 30px;'>
                                This link will expire in 60 minutes.<br>
                                If you did not request a password reset, please ignore this email.
                            </p>
                            <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
                            <p style='color: #999; font-size: 11px;'>
                                © 2025 Citadel - Academic Information Management System
                            </p>
                        </div>
                    ");
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
            \Log::info('Password reset attempt', [
                'email' => $request->email,
                'token_length' => strlen($request->token),
                'token_preview' => substr($request->token, 0, 10) . '...'
            ]);

            $record = DB::table('password_resets')
                ->where('email', $request->email)
                ->where('token', $request->token)
                ->first();

            if (!$record) {
                \Log::warning('Token not found in database', [
                    'email' => $request->email,
                    'existing_records' => DB::table('password_resets')->where('email', $request->email)->count()
                ]);
                return response()->json(['error' => 'Invalid token or email. Please request a new reset link.'], 400);
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
