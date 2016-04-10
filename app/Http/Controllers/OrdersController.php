<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\OrderFilterJob;
use App\Jobs\ImportOrdersFromCsvJob;
use App\Order;
use Illuminate\Database\Eloquent\ModelNotFoundException as OrderNotFound;

class OrdersController extends Controller
{
    /**
     * Present all imported orders.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request) {
       $filterParams = $request->query();
       //dd($filterParams);
       $orderFilterJob = new OrderFilterJob($filterParams);
       $filteredOrders = $this->dispatch($orderFilterJob);
       return response()->json([
           'effect_filters' => $orderFilterJob->getParsedFilterParams(),
           'num_of_orders' => $filteredOrders->count(),
           'results' => $filteredOrders->toArray()]); 
    }

    /**
     * Import and validate the content of uploaded file
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function import(Request $request) {
        $this->validate($request, [
            'orders' => 'bail|required|mimes:csv,txt'
        ]);
 
        $csvFile = $request->file('orders');
        if ($csvFile->isValid()) {
            //dd($csvFile->getClientSize());
            if ($csvFile->getClientSize() <= config('ordercsv.max_file_size')) {
                $newFilename = uniqid().'_'.$csvFile->getClientOriginalName();
                $csvFile = $csvFile->move(config('ordercsv.path'),
                    $newFilename);
                $csvFilePathname = $csvFile->getRealPath();
                $this->dispatch(new ImportOrdersFromCsvJob($csvFilePathname));
	        return response()->json([
                    'import_status' => 'Uploaded successfuly']);
            }
            else {
                return response()->json([
                    'import_status' => 'Too large file to upload']);
            }
        }
        else {
            return response()->json([
                'import_status' => 'Uploading fails']);
        }
    }

    /**
     * Display the specified order.
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id) {
        try {
            $order = Order::findOrFail($id);
        } catch (OrderNotFound $e) {
            return response()->json([
                'not_found' => 'The order of id='.$id.' is not found']);
        }
        return $order;
    }
}
