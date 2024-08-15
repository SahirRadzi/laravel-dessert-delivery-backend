<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Tarsoft\Toyyibpay\ToyyibpayFacade;
use Tarsoft\Toyyibpay\Toyyibpay;
use Illuminate\Routing\Route;

class ToyyibPayController extends Controller
{
    public function getBankFPX()
    {
        $data =  ToyyibpayFacade::getBanks();

        dd($data);
    }

    public function purchase(Request $request)
    {
        // amount X 100
        // value amount in SEN

        $priceX100 = 20.00 * 100;
        $grandTotal = 10.00;
        $gradTotalX100 = $grandTotal * 100;
        $orderId = 1;
        $code = config('toyyibpay.code');

        // dd($category_code);

        // // SplitPayment - username
        $idToyyibForVendor = "sahir.radzi";

        $bill_object = [
            'billName' => 'Dessde',
            'billDescription' => 'Cuba Purchase Dessde',
            'billPriceSetting' => 1,
            'billPayorInfo'=>1,
            'billAmount'=> $priceX100,
            'billReturnUrl'=> route(name:'toyyibpay-paymentStatus'),
            'billCallbackUrl'=> route(name:'toyyibpay-callBack'),
            'billExternalReferenceNo' => $orderId,
            'billTo'=>'Sahir Radzi',
            'billEmail'=>'sahir.radzi@gmail.com',
            'billPhone'=>'0123456789',
            'billSplitPayment'=>1,
            'billSplitPaymentArgs'=>'[{"id":"'.$idToyyibForVendor.'","amount":"'.$gradTotalX100.'"}]',
            'billPaymentChannel'=>'0',
            'billContentEmail'=>'Thank you for the testing!',
        ];

        // dd($object);

        $data = ToyyibpayFacade::createBill($code, (object)$bill_object);

        $bill_code = $data[0]->BillCode;

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

    public function callBack ()
    {

        $data = request()->all([
            'order_id',
            'refno',
            'status',
            'billcode',
            'transaction_time',

        ]);

        return $data;
    }
}
