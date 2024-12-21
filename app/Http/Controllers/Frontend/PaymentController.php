<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    public function verifyPayment(Request $request)
    {
        $url = "https://khalti.com/api/v2/payment/verify/";

        $response = Http::withHeaders([
            'Authorization' => 'Key YOUR_KHALTI_SECRET_KEY', // Replace with your Secret Key
        ])->post($url, [
            'token' => $request->input('token'),
            'amount' => $request->input('amount'),
        ]);

        if ($response->successful()) {
            return response()->json([
                'success' => true,
                'message' => 'Payment Verified Successfully',
                'data' => $response->json(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Payment Verification Failed',
            'data' => $response->json(),
        ], 400);
    }
}
