<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Discount;
use App\Models\OrderItem;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ApiController;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;

class PaymentController extends ApiController
{
    public function checkCoupon(Request $request): JsonResponse
    {
        $validate = Validator::make($request->all(), [
            'code' => 'required|string',
        ]);
        if ($validate->fails()) {
            return $this->errorResponse(
                message: $validate->errors(),
                code: 422,
            );
        };

        $coupon = Discount::where('code', $request->code)
            ->where('expires_at', '>', Carbon::now())
            ->first();
        if (!$coupon) {
            return $this->errorResponse(
                message: 'Discount code does not exist.',
                code: 404,
            );
        };

        $couponAlreadyUsed = Order::where('user_id', auth()->id())
            ->where('coupon_id', $coupon->id)
            ->where('payment_status', 1)
            ->exists();
        if ($couponAlreadyUsed) {
            return $this->errorResponse(
                message: 'You have already used this discount code.',
                code: 409,
            );
        };

        return $this->successResponse(
            data: [
                'percent' => $coupon->percent,
            ],
        );
    }

    public function send(Request $request): JsonResponse
    {
        $validate = Validator::make($request->all(), [
            'cart' => 'required',
            'cart.*.id' => 'required|integer|exists:products,id',
            'cart.*.qty' => 'required|integer',
            'coupon_code' => 'nullable|exists:discounts,code',
            'address_id' => 'required|exists:user_addresses,id'
        ]);
        if ($validate->fails()) {
            return $this->errorResponse(
                message: $validate->errors(),
                code: 422,
            );
        };

        $totalAmount = 0;
        foreach ($request->cart as $orderItem) {
            $product = Product::findOrFail($orderItem['id']);
            if ($product->quantity < $orderItem['qty']) {
                return $this->errorResponse(
                    message: 'Number of products is wrong.',
                    code: 409,
                );
            };
            $totalAmount += $product->is_sale ?
                $product->sale_price * $orderItem['qty'] :
                $product->price * $orderItem['qty'];
        };
        $couponAmount = 0;
        $coupon = null;
        if ($request->coupon_code) {
            $coupon = Discount::where('code', $request->coupon_code)
                ->where('expires_at', '>', Carbon::now())
                ->first();
            if (!$coupon) {
                return $this->errorResponse(
                    message: 'Discount code does not exist.',
                    code: 404,
                );
            };

            $couponAlreadyUsed = Order::where('user_id', auth()->id())
                ->where('coupon_id', $coupon->id)
                ->where('payment_status', 1)
                ->exists();
            if ($couponAlreadyUsed) {
                return $this->errorResponse(
                    message: 'You have already used this discount code.',
                    code: 409,
                );
            };

            $couponAmount = $totalAmount * $coupon->percent / 100;
        };

        $payingAmount = $totalAmount - $couponAmount;
        $amounts = [
            'totalAmount' => $totalAmount,
            'couponAmount' => $couponAmount,
            'payingAmount' => $payingAmount,
        ];
        $parameters = [
            'merchant' => 'zibal',
            'callbackUrl' => 'http://localhost:5000/payment/verify',
            'amount' => $payingAmount * 10,
        ];
        $response = $this->postToZibal('request', $parameters);
        if ($response->result == 100) {
            $result = $this->createOrder(
                address_id: $request->address_id,
                coupon: $coupon,
                amounts: $amounts,
                cart: $request->cart,
                token: $response->trackId,
            );
            if ($result) {
                return $this->errorResponse(
                    message: $result->getMessage(),
                    code: 500,
                );
            };
            $startGateWayUrl = 'https://gateway.zibal.ir/start/'. $response->trackId;
            return $this->successResponse(
                data: [
                    'URL' => $startGateWayUrl,
                ],
            );
        } else {
            return $this->errorResponse(
                message: [
                    'result' => $this->resultCodes($response->result),
                    'error' => $response->message,
                ],
                code: 500,
            );
        };
    }

    public function verify(Request $request): JsonResponse
    {
        $validate = Validator::make($request->all(), [
            'success' => 'required|boolean',
            'trackId' => 'required|integer',
        ]);
        if ($validate->fails()) {
            return $this->errorResponse(
                message: $validate->errors(),
                code: 422,
            );
        };

        if ($request->success == 1) {
            $parameters = [
                "merchant" => 'zibal',
                "trackId" => $request->trackId,
            ];
            $response = $this->postToZibal('verify', $parameters);
            if ($response->result == 100) {
                $result = $this->verifyOrder($request->trackId, $response->refNumber);
                if ($result) {
                    return $this->errorResponse(
                        message: $result->getMessage(),
                        code: 500,
                    );
                };
                return $this->successResponse(
                    data: [
                        'trackId' => $request->trackId,
                        'refNumber' => $response->refNumber,
                    ],
                );
            } else {
                return $this->errorResponse(
                    message: [
                        'result' => $this->resultCodes($response->result),
                        'error' => $response->message,
                    ],
                    code: 500,
                );
            };
        } else {
            return $this->errorResponse(
                message: 'Transaction failed',
            );
        };
    }

    private function postToZibal(
        string $path,
        array $parameters
    ): object
    {
        $url = 'https://gateway.zibal.ir/v1/'. $path;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($parameters));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response  = curl_exec($ch);
        curl_close($ch);
        return json_decode($response);
    }

    private function resultCodes(int $code): string
    {
        switch ($code) {
            case 100:
                return "با موفقیت تایید شد";
            case 102:
                return "merchant یافت نشد";
            case 103:
                return "merchant غیرفعال";
            case 104:
                return "merchant نامعتبر";
            case 201:
                return "قبلا تایید شده";
            case 105:
                return "amount بایستی بزرگتر از 1,000 ریال باشد";
            case 106:
                return "callbackUrl نامعتبر می‌باشد. (شروع با http و یا https)";
            case 113:
                return "amount مبلغ تراکنش از سقف میزان تراکنش بیشتر است.";
            case 201:
                return "قبلا تایید شده";
            case 202:
                return "سفارش پرداخت نشده یا ناموفق بوده است";
            case 203:
                return "trackId نامعتبر می‌باشد";
            default:
                return "وضعیت مشخص شده معتبر نیست";
        };
    }

    private function createOrder(
        int $address_id,
        ?Discount $coupon,
        array $amounts,
        array $cart,
        int $token
    ): QueryException|null
    {
        try {
            DB::beginTransaction();
            $order = Order::create([
                'user_id' => auth()->id(),
                'address_id' => $address_id,
                'coupon_id' => $coupon ? $coupon->id : null,
                'status' => 0,
                'total_amount' => $amounts['totalAmount'],
                'coupon_amount' => $amounts['couponAmount'],
                'paying_amount' => $amounts['payingAmount'],
                'payment_status' => 0,
            ]);
            foreach ($cart as $orderItem) {
                $product = Product::find($orderItem['id']);
                $price = $product->is_sale ? $product->sale_price : $product->price;
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'price' => $price,
                    'quantity' => $orderItem['qty'],
                    'subtotal' => $orderItem['qty'] * $price,
                ]);
            };
            Transaction::create([
                'user_id' => auth()->id(),
                'order_id' => $order->id,
                'amount' => $amounts['payingAmount'],
                'token' => $token,
                'ref_number' => null,
                'status' => 0,
            ]);
            DB::commit();
            return null;
        } catch (\Exception $e) {
            DB::rollback();
            return $e;
        };
    }

    private function verifyOrder(
        int $token,
        int|null $refNumber
    ): QueryException|null
    {
        try {
            DB::beginTransaction();
            $transaction = Transaction::where('token', $token)->firstOrFail();
            $transaction->update([
                'status' => 1,
                'ref_number' => $refNumber,
            ]);
            $order = Order::findOrFail($transaction->order_id);
            $order->update([
                'status' => 1,
                'payment_status' => 1,
            ]);
            foreach (OrderItem::where('order_id', $order->id)->get() as $orderItem) {
                $product = Product::find($orderItem->product_id);
                $product->update([
                    'quantity' => $product->quantity - $orderItem->quantity,
                ]);
            };
            DB::commit();
            return null;
        } catch (\Exception $e) {
            DB::rollback();
            return $e;
        };
    }
}
