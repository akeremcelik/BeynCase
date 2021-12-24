<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;
use App\Models\User;

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
}
