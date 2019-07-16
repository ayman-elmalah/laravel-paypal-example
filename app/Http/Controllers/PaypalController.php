<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Srmklive\PayPal\Services\ExpressCheckout;

class PaypalController extends Controller
{
    /**
     * @var ExpressCheckout
     */
    protected $provider;
    public function __construct() {
        $this->provider = new ExpressCheckout();
    }

    public function index(Request $request) {
        $cart = $this->getCheckoutData();

        $response = $this->provider->setExpressCheckout($cart, false);
        if ($response['paypal_link'] == null) {
            dd('Invalid credentials');
        }
        return redirect($response['paypal_link']);
    }

    /**
     * Process payment on PayPal.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function success(Request $request)
    {
        $token = $request->get('token');
        $PayerID = $request->get('PayerID');
        $cart = $this->getCheckoutData();
        // Verify Express Checkout Token
        $response = $this->provider->getExpressCheckoutDetails($token);
        if (in_array(strtoupper($response['ACK']), ['SUCCESS', 'SUCCESSWITHWARNING'])) {
            // Perform transaction on PayPal
            $payment_status = $this->provider->doExpressCheckoutPayment($cart, $token, $PayerID);
            $status = $payment_status['PAYMENTINFO_0_PAYMENTSTATUS'];
            return redirect('/');
        }
    }
    /**
     * Set cart data for processing payment on PayPal.
     *
     * @param bool $recurring
     *
     * @return array
     */
    protected function getCheckoutData()
    {
        $data = [];
        $order_id = 12132334;
        $data['items'] = [
            [
                'name'  => 'Product 1',
                'price' => 9.99,
                'qty'   => 1,
            ],
            [
                'name'  => 'Product 2',
                'price' => 4.99,
                'qty'   => 2,
            ],
        ];
        $data['return_url'] = route('paypal.success');
        $data['invoice_id'] = 'invoice_'.$order_id;
        $data['invoice_description'] = "Order' . $order_id . ' Invoice";
        $data['cancel_url'] = url('/');
        $total = 0;
        foreach ($data['items'] as $item) {
            $total += $item['price'] * $item['qty'];
        }
        $data['total'] = $total;
        return $data;
    }
}
