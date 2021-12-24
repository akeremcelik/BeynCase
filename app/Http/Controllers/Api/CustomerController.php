<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;
use App\Models\User;
use App\Models\Service;
use Auth;

class CustomerController extends Controller
{
    public function addBalance(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric|exists:users,id',
            'balance' => 'required|numeric|min:1'
        ]);
        if($validator->fails()) {
            return response()->json(['status' => false, 'error' => $validator->messages()], 400);
        }

        try {
            if(Auth::guard('api')->user()->id != $request->user_id)
                return response()->json(['status' => false, 'error' => 'User id mismatch'], 400);

            DB::transaction(function() use($request) {
                $user = User::find($request->user_id);
                $user->balance += $request->balance;
                $user->save();
            });
        } catch (\Throwable $e) {
            return response()->json(['status' => false, 'error' => $e->getMessage()], 400);
        }

        return response()->json(['status' => true, 'message' => 'Add balance successful'], 200);
    }

    public function getServices() {
        $services = Service::all();
        return response()->json(['status' => true, 'data' => $services], 200);
    }
}
