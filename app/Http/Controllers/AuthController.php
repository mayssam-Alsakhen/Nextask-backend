<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    // register 
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
    
        $token = JWTAuth::fromUser($user);
    
        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token,
        ], 201);
    }
    

//login
public function login(Request $request)
{
    $credentials = $request->only('email', 'password');

    if (!$token = JWTAuth::attempt($credentials)) {
        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    return response()->json([
        'message' => 'Login successful',
        'token' => $token,
    ]);
}


// protected function respondWithToken($token)
// {
//     return response()->json([
//         'access_token' => $token,
//         'token_type' => 'bearer',
//         'expires_in' => auth()->factory()->getTTL() * 60
//     ]);
// }


//logout
public function logout()
{
    Auth::logout();
    return response()->json(['message' => 'Successfully logged out']);
}


// the current logged user
public function me()
{
    return response()->json(Auth::user());
}

}
