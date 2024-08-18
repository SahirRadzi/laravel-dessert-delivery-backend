<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Tarsoft\Toyyibpay\ToyyibpayFacade;
use Tarsoft\Toyyibpay\Toyyibpay;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Http;

class ToyyibPayController extends Controller
{
    public function getBanks()
    {
        $data1 =  ToyyibpayFacade::getBanks();

        dd($data1);
    }

    public function getBankFPX()
    {
        $data2 = ToyyibpayFacade::getBanksFPX();

        dd($data2);
    }

    public function purchase(Request $request)
    {

        // $order_id = 5;
        // $user = auth()->user->id;
        // $user2= auth()->user();

        $user = auth('sanctum')->user()->id;

        $status = 'pending';

        $order = Order::where('user_id',$user)->where('status', $status)->first();

        // dd($order);

        // $order = Order::with('user')->where('user_id', $userId)->first();

        $price = $order->total_bill;
        $priceX100 = $price * 100;
        $grandTotal = $order->total_price;
        $grandTotalX100 = $grandTotal * 100;
        $order_id = $order->id;
        $code = config('toyyibpay.code');
        $idToyyibForVendor = "sahir.radzi";

        // $price = 29.16;
        // $priceX100 = $price * 100;
        // $grandTotal = 24.00;
        // $grandTotalX100 = $grandTotal * 100;
        // $order_id = 5;
        // $code = config('toyyibpay.code');
        // $idToyyibForVendor = "sahir.radzi";

        // dd($category_code);

        // // SplitPayment - username

        $bill_object = [
            'billName' => 'Dessde',
            'billDescription' => 'Cuba Purchase Dessde',
            'billPriceSetting' => 1,
            'billPayorInfo'=>1,
            'billAmount'=> $priceX100,
            'billReturnUrl'=> route(name:'toyyibpay-paymentStatus'),
            'billCallbackUrl'=> route(name:'toyyibpay-callBack'),
            'billExternalReferenceNo' => $order_id,
            'billTo'=>'Sahir Radzi',
            'billEmail'=>'sahir.radzi@gmail.com',
            'billPhone'=>'0123456789',
            'billSplitPayment'=>1,
            'billSplitPaymentArgs'=>'[{"id":"'.$idToyyibForVendor.'","amount":"'.$grandTotalX100.'"}]',
            'billPaymentChannel'=>'0',
            'billContentEmail'=>'Thank you for the testing!',
        ];

        // dd($bill_object);

        $data = ToyyibpayFacade::createBill($code, (object)$bill_object);

        $bill_code = $data[0]->BillCode;

        $payment = Payment::create([
            'order_id' => $order_id,
            'billcode' => $bill_code,
            'amount' => $price
        ]);


        return redirect()->route('toyyibpay-payment', $bill_code);

    }

    public function billPaymentLink($bill_code)
    {
        $data = ToyyibpayFacade::billPaymentLink($bill_code);

        return redirect($data);

    }

    public function returnUrl()
    {
        $data = request()->all([
            'status_id',
            'billcode',
            'order_id',
            'transaction_id'
        ]);

        return $data;

    }

    public function callBack (Request $request)
    {

        $order = Order::where('id',$request->order_id)->first();
        $payment = Payment::where('billcode', $request->billcode)->first();

        if ($request->status == 1) {
            //update complete
            $order->status = 'success';
            $order->save();
            $payment->status = 'success';
            $payment->refno = $request->transaction_id;
            $payment->transaction_time = $request->transaction_time;
            $payment->reason = $request->reason;
            $payment->save();
        } elseif ($request->status == 2) {
            //update failed
            $order->status = 'failed';
            $order->save();
            $payment->status = 'failed';
            $payment->refno = $request->transaction_id;
            $payment->transaction_time = $request->transaction_time;
            $payment->reason = $request->reason;
            $payment->save();

        } else {
            //pending
            $order->status = 'pending';
            $order->save();
            $payment->status = 'pending';
            $payment->refno = $request->transaction_id;
            $payment->transaction_time = $request->transaction_time;
            $payment->reason = $request->reason;
            $payment->save();
        }
    }
}
