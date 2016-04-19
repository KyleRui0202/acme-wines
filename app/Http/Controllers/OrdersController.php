<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\OrderFilterJob;
use App\Jobs\ImportOrdersFromCsvJob;
use App\Order;
use Illuminate\Database\Eloquent\ModelNotFoundException as OrderNotFoundException;

class OrdersController extends Controller
{
    /**
     * Present all imported orders.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $filterParams = $request->query();

        $orderFilterJob = new OrderFilterJob($filterParams);

        $filteredOrders = $this->dispatch($orderFilterJob);

        $effectFilters = $orderFilterJob->getParsedFilterParams();

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
    public function import(Request $request)
    {
        //dd($request->file('orders')->getClientMimeType());
        $this->validate($request, [
            'orders' => 'bail|required|mimes:csv,txt'
        ]);

        $csvFile = $request->file('orders');

        if ($csvFile->isValid()) {
            if ($csvFile->getClientSize() <= config('ordercsv.max_file_size')) {
                $newFilename = uniqid().'_'.$csvFile->getClientOriginalName();

                $csvFile = $csvFile->move(config('ordercsv.path'),
                    $newFilename);

                $csvFilePathname = $csvFile->getRealPath();

                $this->dispatch(new ImportOrdersFromCsvJob($csvFilePathname));

	        return response()->json([
                    'import_status' => 'File uploaded successfuly']);
            }
            else {
                return response()->json([
                    'import_status' => 'Too large file (>'.
                        config('ordercsv.max_file_size').') to be uploaded']);
            }
        }
        else {
            return response()->json([
                'import_status' => 'File uploading fails']);
        }
    }

    /**
     * Display the specified order.
     * 
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $order = Order::findOrFail($id);
        } catch (OrderNotFoundException $e) {
            return response()->json([
                'no_result' => 'The order of id='.$id.' is not found']);
        }

        return $order;
    }
}
