<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
Use Illuminate\Support\Facades\Auth;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Product;
use App\Mail\OrderMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;

class CheckoutController extends Controller
{
    public function index(){
        $cartitems=Cart::where('user_id',Auth::id())->get();
        
        return view('frontend.checkout',compact('cartitems'));
        
        
    }

    
    public function placeorder(Request $request){
        
        $order=new Order();
        $order->user_id=Auth::id();
        $order->fname=$request->input('fname');
        $order->lname=$request->input('lname');
        $order->email=$request->input('email');
        $order->phone=$request->input('phone');
        $order->address=$request->input('address');
        $order->city=$request->input('city');
        $order->color=$request->input('color');
        $order->payment_mode=$request->input('payment_mode');
        $order->payment_id=$request->input('payment_id');
       
        
        $total=0;
        $cartitems_total=Cart::where('user_id',Auth::id())->get();
        foreach($cartitems_total as $product){
            $tot=$product->products->selling_price*$product->product_quantity;
            $total=$total+$tot;
            
        }

        $order->total_price=$total;
        
        
        $order->save();

        

        $cartitems=Cart::where('user_id',Auth::id())->get();
        foreach($cartitems as $items){
            OrderItem::create([
                'order_id'=>$order->id,
                'product_id'=>$items->product_id,
                'quantity'=>$items->product_quantity,
                'price'=>$items->products->selling_price,
                
            ]);

            $product=Product::where('id',$items->product_id)->first();
            $product->quantity=$product->quantity-$items->product_quantity;
            $product->update();
        }

        if(Auth::user()->address==Null){
            $user=User::where('id',Auth::id())->first();
            $user->name=$request->input('fname');
            $user->lname=$request->input('lname');
            $user->phone=$request->input('phone');
            $user->address=$request->input('address');
            $user->city=$request->input('city');
           
            $user->update();
        }

        $cartitems=Cart::where('user_id',Auth::id())->get();
        Cart::destroy($cartitems);
        if($request->input('payment_mode')=="Online Payment"){
            request()->validate(['email'=>'required|email']);
            Mail::to(request('email'))->send(new OrderMail($order));
            return response()->json(['status'=>'Payment Successfully and Confirmation Mail Sent.']);
            
        }
        
        
        request()->validate(['email'=>'required|email']);
        Mail::to(request('email'))->send(new OrderMail($order));
        return redirect('/checkout')->with('status','Order Placed Successfully and Confirmation Mail Sent.');
    
        
        
    }



// public function initiate()
// {
//     try {
//         $client = new Client([
//             'verify' => false, // Disables SSL verification
//         ]);
//     $url = 'https://a.khalti.com/api/v2/epayment/initiate/';
//     $headers = [
//         'Authorization' => 'key 04dc9a4f23fb436387f2e2aee1b72b94',
//         'Content-Type' => 'application/json',
//     ];
//     $data = [
//         "return_url" => "http://example.com/",
//         "website_url" => "https://example.com/",
//         "amount" => 1000,
//         "purchase_order_id" => "Order01",
//         "purchase_order_name" => "test",
//         "customer_info" => [
//             "name" => "Test Bahadur",
//             "email" => "test@khalti.com",
//             "phone" => "9800000001",
//         ],
//     ];

//     // Make the HTTP POST request
//     $response = Http::withHeaders($headers)
//     ->withoutVerifying() 
//     ->post($url, $data);

//     // Check if the response is successful
//     if ($response->successful()) {
//         // Handle success
//         return response()->json($response->json());
//     } else {
//         // Handle error
//         return response()->json(['error' => 'Payment initiation failed'], 500);
//     }
// }

    // public function pay(Request $request){
        
    //     $cartitem = Cart::where('user_id',Auth::id())->get();
    //     $total_price = 0;
    //     foreach($cartitem as $item){
    //         $total_price += $item->products->selling_price*$item->product_quantity;
           
    //     }

    //     $firstname = $request->input('firstname');
    //     $lastname = $request->input('lastname');
    //     $email = $request->input('email');
    //     $phone = $request->input('phone');
    //     $address = $request->input('address');
    //     $city = $request->input('city');

    //     return response()->json([
    //         'firstname'=>$firstname,
    //         'lastname'=>$lastname,
    //         'email'=>$email,
    //         'phone'=>$phone,
    //         'address'=>$address,
    //         'city'=>$city,
    //         'total_price'=>$total_price
    //     ]);
        

        
    // }

    public function initiate()
    {
        try {
            $url = 'https://a.khalti.com/api/v2/epayment/initiate/';
            $headers = [
                'Authorization' => 'Key 05bf95cc57244045b8df5fad06748dab',
                'Content-Type' => 'application/json',
            ];
            $data = [
                "return_url" => "http://localhost:8000/",
                "website_url" => "http://localhost:8000",
                "amount" => 1000,
                "purchase_order_id" => "Order01",
                "purchase_order_name" => "test",
                "customer_info" => [
                    "name" => "Raunak Tuladhar",
                    "email" => "raunak@khalti.com",
                    "phone" => "9800000001",
                ],
                "merchant_username" => "Raunak"
                  
            ];
    
            // Use Laravel's HTTP client for the request
            $response = Http::withHeaders($headers)
                ->withoutVerifying() // Disables SSL verification
                ->post($url, $data);
    
            // Check if the response is successful
            if ($response->successful()) {
                return response()->redirectTo($response->json()["payment_url"]);
      
            } else {
                return response()->json(['error' => 'Payment initiation failed', 'details' => $response->body()], $response->status());
            }
        } catch (\Exception $e) {
            // Handle exceptions and log errors
            \Log::error('Payment initiation error: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred during payment initiation'], 500);
        }
    }
}       

