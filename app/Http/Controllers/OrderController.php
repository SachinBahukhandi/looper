<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderDetail;
use Exception;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $orders = Order::with(['orderDetails.product', 'user'])->paginate();

        return OrderResource::collection($orders);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        try {
            $validated = $request->validate(
                [
                    'product_id' => 'required|exists:products,id'
                ]
            );

            $order = Order::firstOrCreate(
                [
                    'customer_id' => $request->user()->id,
                ]
            );

            if ($order->paid == 1) {
                return $this->customResponse(false, 'Cannot add items to a paid order');
            }

            if ($order) {
                $productOrders = OrderDetail::firstOrCreate(
                    [
                        'product_id' => $validated['product_id'],
                        'order_id' => $order->id
                    ]
                );
            }

            $order = $order->load('orderDetails.product', 'user');

            return new OrderResource($order);
        } catch (QueryException $e) {
            return $this->customResponse(true, $e->getMessage());
        } catch (Exception $e) {
            return $this->customResponse(true, $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        //
        $order = $order->load('orderDetails.product', 'user');

        return new OrderResource($order);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        //
        try {
            $orderDetails = OrderDetail::where('order_id', $order->id)->delete();
            if ($orderDetails) {
                $order->delete();
            }

            return $this->customResponse(true, 'The order has been deleted');
        } catch (QueryException $e) {
            return $this->customResponse(true, $e->getMessage());
        } catch (Exception $e) {
            return $this->customResponse(true, $e->getMessage());
        }
    }

    public function pay(Request $request, Order $order)
    {

        $orderDetails = $order->load('orderDetails.product');

        $price = $orderDetails->orderDetails->pluck('product')->pluck('price')->sum();


        $response = Http::post(
            'https://superpay.view.agentur-loop.com/pay',
            [
                "order_id" => $order->id,
                "customer_email" => $request->user()->email,
                "value" => $price
            ]
        );
        $res = $response->json();
        if ($response->successful()) {
            return $this->customResponse(true, $res['message']);
        } else {
            return $this->customResponse(false, $res['message']);
        }
    }

    private function customResponse($success, $message)
    {
        return response()->json(
            [
                'success' => $success,
                'message' => $message
            ]
        );
    }
}
