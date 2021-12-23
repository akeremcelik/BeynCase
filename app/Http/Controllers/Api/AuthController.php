<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function registerUser(Request $request) {
        $validator = Validator::make($request->all(), [
            'name'=>'required',
            'email'=>'required|email|unique:users',
            'password'=>'required|min:8',
        ]);
        if($validator->fails()) {
            return response($validator->messages(), 200);
        }

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->save();

        $user->createToken('Token')->accessToken;
        return response()->json(['status' => true, 'message' => 'You have been registered successfully'], 200);
    }

    public function loginUser(Request $request) {
        $validator = Validator::make($request->all(), [
            'email'=>'required|email',
            'password'=>'required',
        ]);
        if($validator->fails()) {
            return response($validator->messages(), 200);
        }

        if(auth()->attempt(['email' => $request->email, 'password' => $request->password])) {
            $token = auth()->user()->createToken('Token')->accessToken;
            return response()->json(['status' => true, 'token' => $token], 200);
        } else {
            return response()->json(['status' => false, 'message' => 'Email or password is invalid'], 400);
        }
    }
}
