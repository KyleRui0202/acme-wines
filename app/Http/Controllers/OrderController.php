<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Present all imported orders.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request) {
       $filterParams = $request->query();
       $filteredOrders = $this->dispatch(new OrderFilter($filterParams));
       return response()->json(['results' => $filteredOrders->toJson()]); 
    }

    /**
     * Import and validate the content of uploaded csv file
     *
     * @param Request $request
     * @return void
     */
    public function import(Request $request) {
        $this->validate($request, [
            'orders' => 'bail|required|mimes:csv'
        ]);

        

    }

    /**
     * 
}
