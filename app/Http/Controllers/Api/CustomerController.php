<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CarModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;
use App\Models\User;
use App\Models\Service;
use Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

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

        $servicePrice = Service::find($request->service_id)->price;
        if(Auth::guard('api')->user()->balance < $servicePrice)
            return response()->json(['status' => false, 'error' => 'Insufficient balance'], 400);

        DB::transaction(function() use($request, $servicePrice) {
            try {
                $order = new Order();
                $order->user_id = Auth::guard('api')->user()->id;
                $order->service_id = $request->service_id;
                $order->car_model_id = $request->car_model_id;
                $order->datetime = $request->datetime;
                $order->save();

                $user = Auth::guard('api')->user();
                $user->balance -= $servicePrice;
                $user->save();
            } catch (\Exception $e) {
                return response()->json(['status' => false, 'error' => $e->getMessage()], 400);
            }
        });

        return response()->json(['status' => true, 'message' => 'Place order successful'], 200);
    }

    public function listOrders(Request $request) {
        $filterOptions = $request->filterOptions;
        $currentOrders = collect();

        /*
            This part will filter customer's orders if request has filterOptions parameter and tables include filter parameter column
            Example request:
            {
                "filterOptions": {
                    "service": {

                    },
                    "carModel": {
                        "brand": "ACURA",
                        "model": "2018"
                    }
                }
            }
        */

        if($filterOptions && count($filterOptions) > 0) {
            $orders = Order::where('user_id', Auth::guard('api')->user()->id);
            $tableRelationArr = [
                'carModel' => ['table' => 'car_models'],
                'service' => ['table' => 'services']
            ];

            foreach ($filterOptions as $tableKey => $filterOption) {
                if(isset($tableRelationArr[$tableKey])) {
                    foreach ($filterOption as $columnKey => $value) {
                        $orders = $orders->whereHas($tableKey, function ($q) use ($columnKey, $value, $tableRelationArr, $tableKey) {
                            if(Schema::hasColumn($tableRelationArr[$tableKey]['table'], $columnKey))
                                $q->where($columnKey, 'LIKE', '%' . $value . '%');
                        });
                    }
                }
            }

            $orders = $orders->with(['service', 'carModel'])->get();
        } else {
            $orders = Order::where('user_id', Auth::guard('api')->user()->id)->with(['service', 'carModel'])->get();
        }

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

    public function getCarModels() {
        if(Cache::has('carModels')) {
            return response()->json(['status' => true, 'data' => Cache::get('carModels')]);
        } else {
            $carModels = CarModel::all()->toArray();
            Cache::put('carModels', $carModels, 600);
            return response()->json(['status' => true, 'data' => $carModels]);
        }
    }
}
