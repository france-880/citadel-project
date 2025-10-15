<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Password reset routes (required by Laravel's password reset functionality)
// Route::get('/password/reset/{token}', function ($token) {
//     return redirect('/reset-password?token=' . $token);
// })->name('password.reset');
