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

        $validated = $request->validate(
            [
                'products' => 'required',
                'products.*' => 'required|exists:products,id'
            ]
        );
        try {



            $order = Order::create(
                [
                    'customer_id' => $request->user()->id,
                    'paid' => 0
                ]
            );

            $orderDetails = [];

            foreach ($validated['products'] as $product) {
                $orderDetails[] = [
                    'order_id' => $order->id,
                    'product_id' => $product
                ];
            }
            if ($order) {
                $productOrders = OrderDetail::insert($orderDetails);
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

        if ($order->paid == 1) {
            return $this->customResponse(false, 'Cannot add items to a paid order');
        }

        $validated = $request->validate(
            [
                'products' => 'filled',
                'products.add.*' => 'filled|exists:products,id',
                'products.remove.*' => 'filled|exists:orders_details,product_id'
            ]
        );

        try {


            $orderDetails = [];

            foreach ($validated['products']['add'] as $product) {
                $orderDetails[] = [
                    'order_id' => $order->id,
                    'product_id' => $product
                ];
            }

            OrderDetail::insertOrIgnore($orderDetails);

            OrderDetail::whereIn('product_id', $validated['products']['remove'])->delete();

            $order = $order->load('orderDetails.product', 'user');

            return new OrderResource($order);
        } catch (QueryException $e) {
            return $this->customResponse(true, $e->getMessage());
        } catch (Exception $e) {
            return $this->customResponse(true, $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        //
        try {
            $orderDetails = OrderDetail::where('order_id', $order->id)->delete();
            $order->delete();

            return $this->customResponse(true, 'The order has been deleted');
        } catch (QueryException $e) {
            return $this->customResponse(true, $e->getMessage());
        } catch (Exception $e) {
            return $this->customResponse(true, $e->getMessage());
        }
    }

    /**
     * Create a service endpoint to attach a product to an existing order. This operation may only be executed as long as the order is not payed.
     */
    public function add(Request $request, Order $order)
    {
        if ($order->paid == 1) {
            return $this->customResponse(false, 'Cannot add items to a paid order');
        }

        $validated = $request->validate(
            [
                'product_id' => 'required|exists:products,id'
            ]
        );
        try {

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

    public function pay(Request $request, Order $order)
    {

        if ($order->paid == 1) {
            return $this->customResponse(false, 'Already paid for the order!');
        }
        try {

            $orderDetails = $order->load('orderDetails.product');

            $products = $orderDetails->orderDetails->pluck('product');

            if ($products->isEmpty()) {
                return $this->customResponse(false, 'No products to checkout!');
            }

            $price = $products->pluck('price')->sum();


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
                $order->fill(
                    [
                        'paid' => 1
                    ]
                )->save();
                return $this->customResponse(true, $res['message']);
            } else {
                return $this->customResponse(false, $res['message']);
            }
        } catch (\GuzzleHttp\Exception\ConnectException) {
            return $this->customResponse(false, $e->getMessage());
        } catch (Exception $e) {
            return $this->customResponse(false, $e->getMessage());
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
