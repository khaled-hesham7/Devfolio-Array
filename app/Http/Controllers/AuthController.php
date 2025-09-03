<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\RegisterRequest;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

       

        return response()->json([
            'message' => 'تم التسجيل بنجاح',
            'user'    => $user,
            
        ], 201);
    }

    
      public function login(LoginRequest $request)
    {
        // هنجيب اليوزر بالايميل
        $user = User::where('email', $request->email)->first();

        // لو مفيش يوزر او الباسورد غلط
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'الايميل او الباسورد غير صحيح'
            ], 401);
        }

        // مسح التوكينات القديمة لو حابب
        $user->tokens()->delete();

        // نولّد توكن جديد
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح',
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'تم تسجيل الخروج بنجاح'
        ]);
    }
}
