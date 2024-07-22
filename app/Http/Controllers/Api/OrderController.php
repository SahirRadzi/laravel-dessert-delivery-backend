<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    //User: create new order
    public function createOrder(Request $request)
    {
        $request->validate([
            'order_items' => 'required|array',
            'order_items.*.product_id' => 'required|integer|exists:products,id',
            'order_items.*.quantity' => 'required|integer|min:1',
            'restaurant_id' => 'required|integer|exists:users,id',
            'shipping_cost' => 'required|numeric',

        ]);

        $totalPrice = 0;
        foreach ($request->order_items as $item) {
            $product = Product::find($item['product_id']);
            $totalPrice += $product->price * $item['quantity'];
        }
        $maintenanceCost = 0;
        $totalBill = $totalPrice + $request->shipping_cost;
        if ($totalBill < 25) {
            $maintenanceCost = $totalBill * (9 / 100);
          } else if ($totalBill > 25 && $totalBill < 50) {
            $maintenanceCost = $totalBill * (8 / 100);
          } else if ($totalBill > 50) {
            $maintenanceCost = $totalBill * (7 / 100);
          }

        $grandTotal = $maintenanceCost + $totalBill;


        $user = $request->user();
        $data = $request->all();
        $data['user_id'] = $user->id;
        $shippingAddress = $user->address;
        $data['shipping_address'] = $shippingAddress;
        $shippingLatLong = $user->latlong;
        $data['shipping_latlong'] = $shippingLatLong;
        $data['status'] = 'pending';
        $data['total_price'] = $totalPrice;
        $data['maintenance_cost'] = $maintenanceCost;
        $data['total_bill'] = $grandTotal;

        $order = Order::create($data);

        foreach ($request->order_items as $item) {
            $product = Product::find($item['product_id']);
            $orderItem = new OrderItem([
                'product_id' => $product->id,
                'order_id' => $order->id,
                'quantity' => $item['quantity'],
                'price' => $product->price,
            ]);
            $order->orderItems()->save($orderItem);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Order created successfully',
            'data' => $order
        ], 201);
    }

     //update purchase status
     public function updatePurchaseStatus(Request $request, $id)
     {
         $request->validate([
             'status' => 'required|string|in:pending,processing,completed,cancelled',
         ]);

         $order = Order::find($id);
         $order->status = $request->status;
         $order->save();

         return response()->json([
             'status' => 'success',
             'message' => 'Order status updated successfully',
             'data' => $order
         ]);
     }

      //order history
    public function orderHistory(Request $request)
    {
        $user = $request->user();
        $orders = Order::where('user_id', $user->id)->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Get all order history',
            'data' => $orders
        ]);
    }

     //cancel order
     public function cancelOrder(Request $request, $id)
     {
         $order = Order::find($id);
         $order->status = 'cancelled';
         $order->save();

         return response()->json([
             'status' => 'success',
             'message' => 'Order cancelled successfully',
             'data' => $order
         ]);
     }

      //get orders by status for restaurant
    public function getOrdersByStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|string|in:pending,processing,completed,cancelled',
        ]);

        $user = $request->user();
        $orders = Order::where('restaurant_id', $user->id)
            ->where('status', $request->status)
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Get all orders by status',
            'data' => $orders
        ]);
    }

     //update order status for restaurant
     public function updateOrderStatus(Request $request, $id)
     {
         $request->validate([
             'status' => 'required|string|in:pending,processing,completed,cancelled,ready_for_delivery,prepared',
         ]);

         $order = Order::find($id);
         $order->status = $request->status;
         $order->save();

         return response()->json([
             'status' => 'success',
             'message' => 'Order status updated successfully',
             'data' => $order
         ]);
     }

      //get orders by status for driver
    public function getOrdersByStatusDriver(Request $request)
    {
        $request->validate([
            'status' => 'required|string|in:pending,processing,completed,cancelled,ready_for_delivery,prepared',
        ]);

        $user = $request->user();
        $orders = Order::where('driver_id', $user->id)
            ->where('status', $request->status)
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Get all orders by status',
            'data' => $orders
        ]);
    }

     //get order status ready for delivery
     public function getOrderStatusReadyForDelivery(Request $request)
     {
         // $user = $request->user();
         $orders = Order::with('restaurant')
             ->where('status', 'ready_for_delivery')
             ->get();

         return response()->json([
             'status' => 'success',
             'message' => 'Get all orders by status ready for delivery',
             'data' => $orders
         ]);
     }

      //update order status for driver
    public function updateOrderStatusDriver(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:pending,processing,completed,cancelled,on_the_way,delivered',
        ]);

        $order = Order::find($id);
        $order->status = $request->status;
        $order->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Order status updated successfully',
            'data' => $order
        ]);
    }

    //get payment method
    public function getPaymentMethods()
    {
        $paymentMethods = [
            'fpx' =>[
                'TOYYIBPAY' => 'Toyyibpay',
                'CHIP' => 'Chip',
            ]

        ];

        return response()->json([
            'message' => 'Payment methods retrieved successfully',
            'payment_methods' => $paymentMethods
        ], 200);
    }

    public function purchaseOrder(Request $request, $orderId)
    {
        $request->validate([
            'payment_method' => 'required|in:fpx,qr_online',
        ]);

        $order = Order::where('id', $orderId)->where('user_id', auth()->id())->first();

        if (!$order){
            return response()->json(['message' => 'Order not found'], 404);
        }else{
            // Payment::create([
            //     'order_id' => $order->id,
            //     'payment_method' => $request->payment_method,
            //     'amount' =>$order->total_bill,
            //     'status' => 'pending',

            // ]);

        }


    }

    public function createBill()

    {
        //amount X 100
        //value amount in SEN
        $priceX100 = 16.79 * 100;
        $grandTotal = 10.90;
        $gradTotalX100 = $grandTotal * 100;

        // SplitPayment - username
        $idToyyibForVendor = "sahir.radzi";

        $data = array(
            'userSecretKey'=> config('toyyibpayconfig.key'),
            'categoryCode'=> config('toyyibpayconfig.category'),
            'billName'=>'Dessde',
            'billDescription'=>'Cuba CreateBill Dessde',
            'billPriceSetting'=>1,
            'billPayorInfo'=>1,
            'billAmount'=> $priceX100,
            'billReturnUrl'=> route(name:'toyyibpay-status'),
            'billCallbackUrl'=> route(name:'toyyibpay-callback'),
            'billExternalReferenceNo' => 'TEST 01',
            'billTo'=>'Sahir Radzi',
            'billEmail'=>'sahir.radzi@gmail.com',
            'billPhone'=>'0123456789',
            'billSplitPayment'=>1,
            'billSplitPaymentArgs'=>'[{"id":"'.$idToyyibForVendor.'","amount":"'.$gradTotalX100.'"}]',
            'billPaymentChannel'=>'0',
            'billContentEmail'=>'Thank you for the testing!',

            // 'billChargeToCustomer'=>0,
            // set 0 to Customer
            // Leave blank to Owner

        );


        $url = 'https://dev.toyyibpay.com/index.php/api/createBill';
        $response = Http::asForm()->post($url, $data);
        $billCode = $response[0]['BillCode'];

        return redirect('http://dev.toyyibpay.com/'.$billCode);

    }

    public function paymentStatus()
    {
        $response = request()->all(['status_id', 'billcode', 'order_id']);
        return $response;

    }

    // public function callBack()
    // {
    //     $response = request()->all(['refno', 'status', 'reason', 'billcode', 'order_id', 'amount']);
    //     Log::info($response);
    // }


    // Example study method GET (getBank)
    // public function getBank()
    // {
    //     $curl = curl_init();

    //     curl_setopt($curl, CURLOPT_POST, 0);
    //     curl_setopt($curl, CURLOPT_URL, 'https://toyyibpay.com/index.php/api/getBank');
    //     curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    //     $result = curl_exec($curl);
    //     $info = curl_getinfo($curl);
    //     curl_close($curl);

    //     echo $result;
    // }

}

