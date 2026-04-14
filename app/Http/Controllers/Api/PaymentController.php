<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class PaymentController extends Controller
{
    // 1. Tạo link thanh toán VNPAY
    public function createPayment(Request $request)
    {
        $vnp_Url = env('VNP_URL');
        $vnp_Returnurl = env('VNP_RETURN_URL');
        $vnp_TmnCode = env('VNP_TMN_CODE');
        $vnp_HashSecret = env('VNP_HASH_SECRET');

        $vnp_TxnRef = $request->order_id;
        if (!$vnp_TxnRef) {
            return response()->json(['message' => 'Thiếu ID đơn hàng'], 400);
        }

        $vnp_OrderInfo = "Thanh toán đơn hàng #" . $vnp_TxnRef;
        $vnp_OrderType = 'billpayment';
        $vnp_Amount = $request->amount * 100; // VNPAY nhân 100
        $vnp_Locale = 'vn';
        $vnp_IpAddr = $request->ip();

        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef
        );

        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnp_Url . "?" . $query;
        if (isset($vnp_HashSecret)) {
            $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }

        return response()->json(['url' => $vnp_Url]);
    }
    //2. Xử lý IPN từ VNPAY
    public function vnpayIPN(Request $request)
    {
        $inputData = $request->all();
        $vnp_SecureHash = $inputData['vnp_SecureHash'];
        unset($inputData['vnp_SecureHash']);

        // Tạo lại chuỗi hash để kiểm tra tính toàn vẹn của dữ liệu
        ksort($inputData);
        $i = 0;
        $hashData = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        $secureHash = hash_hmac('sha512', $hashData, env('VNP_HASH_SECRET'));

        // Kiểm tra chữ ký
        if ($secureHash === $vnp_SecureHash) {
            // LẤY MÃ PHẢN HỒI TỪ MẢNG DỮ LIỆU
            $vnp_ResponseCode = $inputData['vnp_ResponseCode'];
            $vnp_TxnRef = $inputData['vnp_TxnRef']; // ID đơn hàng

            if ($vnp_ResponseCode == "00") {
                // Sử dụng Model Order của bạn (nhớ import ở đầu file)
                $order = \App\Models\Order::find($vnp_TxnRef);

                if ($order) {
                    if ($order->payment_status !== 'paid') {
                        $order->update([
                            'payment_status' => 'paid',
                            'status' => 'processing'
                        ]);
                        return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
                    } else {
                        return response()->json(['RspCode' => '02', 'Message' => 'Order already confirmed']);
                    }
                } else {
                    return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
                }
            } else {
                return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success (Payment Failed)']);
            }
        } else {
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);
        }
    }
        // 3. Xử lý trả về từ VNPAY sau khi khách thanh toán
    public function vnpayReturn(Request $request)
    {
        $vnp_SecureHash = $request->vnp_SecureHash;
        $inputData = $request->all();
        unset($inputData['vnp_SecureHash']);

        ksort($inputData);
        $i = 0;
        $hashData = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        $secureHash = hash_hmac('sha512', $hashData, env('VNP_HASH_SECRET'));

        // BƯỚC QUAN TRỌNG: Kiểm tra chữ ký và cập nhật Database
        if ($secureHash === $vnp_SecureHash) {
            if ($request->vnp_ResponseCode == '00') {

                // Tìm đơn hàng bằng vnp_TxnRef (chính là order_id bạn truyền lúc tạo link)
                $order = \App\Models\Order::find($request->vnp_TxnRef);

                if ($order) {
                    // Cập nhật trạng thái thanh toán và trạng thái đơn hàng
                    $order->update([
                        'payment_status' => 'paid',
                        'status' => 'processing' // Chuyển sang đang chuẩn bị
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Thanh toán thành công và đã cập nhật đơn hàng'
                    ]);
                }

                return response()->json(['success' => false, 'message' => 'Không tìm thấy đơn hàng'], 404);
            }
        }

        return response()->json(['success' => false, 'message' => 'Giao dịch thất bại hoặc lỗi chữ ký'], 400);
    }
}
