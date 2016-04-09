<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OrdersController extends Controller
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
       $filteredOrders = $this->dispatch(new OrderFilterJob($filterParams));
       return response()->json(['results' => $filteredOrders->toJson()]); 
    }

    /**
     * Import and validate the content of uploaded file
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function import(Request $request) {
        $this->validate($request, [
            'orders' => 'bail|required|mimes:csv'
        ]);
 
        $csvFile = $request->file('orders');
        if ($csvFile->isValid()) {
            $newFilename = uniqid($csvFile.getClientOriginalName().'_');
            $csvFile = $csvFile->move(config('ordercsv.path'),
                $newFilename);
            $csvFilePathname = $csvFile->getRealPath();
            $this->dispatch(new ImportOrdersFromCsvJob($csvFilePathname));
	    return response()->json(['status' => 'uploaded successfuly']);
        }
        else {
            return response()->json(['status' => 'uploading fails']);
        }
    }

    /**
     * 
}
