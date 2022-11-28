<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use Cartalyst\Stripe\Stripe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $link = Link::where('code', $request->code)->first();
        // dd($link);

        DB::beginTransaction();

        $order = new Order();
        $order->name = $request->name;
        $order->email = $request->email;
        $order->code = $link->code;
        $order->user_id = $link->user_id;
        $order->influencer_email = $link->user->email;
        $order->address = $request->address;
        $order->address2 = $request->address2;
        $order->city = $request->city;
        $order->country = $request->country;
        $order->zip = $request->zip;

        if($order->save()) {
            $line_items = [];
            foreach($request->items as $item) {
                $product = Product::find($item['product_id']);

                $order_item = new OrderItem();
                $order_item->order_id = $order->id;
                $order_item->product_title = $product->title;
                $order_item->price = $product->price;
                $order_item->quantity = $item['quantity'];
                $order_item->influencer_revenue = 0.1 * $product->price * $item['quantity'];
                $order_item->admin_revenue = 0.9 * $product->price * $item['quantity'];
                $order_item->save();
                $line_items[] = [
                    'name' => $product->title,
                    'description' => $product->description,
                    'amount' => 100 * $product->price,
                    'currency' => 'usd',
                    'quantity' => $order_item->quantity
                ];
            }

            DB::commit();

            $stripe = Stripe::make(env('STRIPE_SECRET'));

            $source = $stripe->checkout()->sessions()->create([
                'payment_method_types' => ['card'],
                'line_items' => $line_items,
                'success_url' => env('CHECKOUT_URL') . '/success?source={CHECKOUT_SESSION_ID}',
                'cancel_url' => env('CHECKOUT_URL') . '/error'
            ]);

            $order->transaction_id = $source['id'];
            $order->save();

            return response()->json(['message' => 'Successfully Ordered.', 'source' => $source]);

        } else {
            return response()->json([
                'message' => 'Failed order.'
            ], 500);
        }

    }

    public function confirmOrder(Request $request)
    {
        if(!$order = Order::where('transaction_id', $request->source)->first()) {
            return response()->json([
                "message" => 'Order not found'
            ]);
        }

        $order->complete = 1;
        $order->save();

        return response()->json([
            'message' => 'success'
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function show(Order $order)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function destroy(Order $order)
    {
        //
    }
}
