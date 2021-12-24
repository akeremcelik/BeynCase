<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;
use App\Models\User;
use App\Models\Service;
use Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Order;
use Carbon\Carbon;

class CustomerController extends Controller
{
    public function addBalance(Request $request) {
        $validator = Validator::make($request->all(), [
            'balance' => 'required|numeric|min:1'
        ]);
        if($validator->fails()) {
            return response()->json(['status' => false, 'error' => $validator->messages()], 400);
        }

        try {
            DB::transaction(function() use($request) {
                $user = User::find(Auth::guard('api')->user()->id);
                $user->balance += $request->balance;
                $user->save();
            });
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'error' => $e->getMessage()], 400);
        }

        return response()->json(['status' => true, 'message' => 'Add balance successful'], 200);
    }

    public function getServices() {
        $services = Service::all();
        return response()->json(['status' => true, 'data' => $services], 200);
    }

    public function placeOrder(Request $request) {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|numeric|exists:services,id',
            'car_model_id' => 'required|numeric|exists:car_models,id',
            'datetime' => 'required|date_format:Y-m-d H:i'
        ]);
        if($validator->fails()) {
            return response()->json(['status' => false, 'error' => $validator->messages()], 400);
        }

        DB::transaction(function() use($request) {
            try {
                $order = new Order();
                $order->user_id = Auth::guard('api')->user()->id;
                $order->service_id = $request->service_id;
                $order->car_model_id = $request->car_model_id;
                $order->datetime = $request->datetime;
                $order->save();
            } catch (\Exception $e) {
                return response()->json(['status' => false, 'error' => $e->getMessage()], 400);
            }
        });

        return response()->json(['status' => true, 'message' => 'Place order successful'], 200);
    }

    public function listOrders(Request $request) {
        $currentOrders = collect();

        $orders = Order::where('user_id', Auth::guard('api')->user()->id)->get();
        foreach($orders as $order) {
            if(Carbon::now() < Carbon::parse($order->datetime))
                $currentOrders->push($order);
        }

        $pastOrders = $orders->diff($currentOrders);

        return response()->json([
            'status' => true,
            'data' => [
                'currentOrders' => $currentOrders,
                'pastOrders' => $pastOrders
            ]
        ]);
    }
}
