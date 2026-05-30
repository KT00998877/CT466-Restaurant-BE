<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MenuItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; // Đừng quên use Log ở đây nhé

class ChatbotController extends Controller
{
    // 1. Xử lý yêu cầu từ chatbot (cả button và text)
    public function handleChat(Request $request)
    {
        $request->validate([
            'type' => 'required|in:button,text',
            'content' => 'required|string'
        ]);

        $type = $request->input('type');
        $content = $request->input('content');

        // LUỒNG 1: KHÁCH BẤM NÚT (Truy xuất Database cực nhanh)
        if ($type === 'button') {
            switch ($content) {
                case 'menu':
                    $items = MenuItem::inRandomOrder()->take(5)->get();
                    return response()->json([
                        'reply_type' => 'cards',
                        'message' => 'Đây là một số món ăn hấp dẫn của nhà hàng, bạn tham khảo nhé:',
                        'data' => $items
                    ]);
                case 'combo':
                    $items = MenuItem::where('is_combo', true)->get();
                    return response()->json([
                        'reply_type' => 'cards',
                        'message' => 'Nhà hàng đang có các Combo siêu tiết kiệm sau:',
                        'data' => $items
                    ]);
                default:
                    return response()->json([
                        'reply_type' => 'text',
                        'message' => 'Xin lỗi, tính năng này đang được cập nhật.'
                    ]);
            }
        }

        // LUỒNG 2: KHÁCH GÕ TEXT TỰ DO (Gọi Gemini API)
        if ($type === 'text') {
            try {
                // 1. Lấy dữ liệu thực đơn để "dạy" cho AI
                $menuData = MenuItem::select('name', 'price', 'description')->get()->toJson();

                // 2. Tạo Prompt
                $systemPrompt = "Bạn là nhân viên tư vấn nhiệt tình của nhà hàng Nhật/Hàn. 
                Dưới đây là thực đơn của chúng tôi (tên món, giá, mô tả): $menuData. 
                Hãy trả lời câu hỏi của khách hàng một cách ngắn gọn, thân thiện, và CHỈ tư vấn những món có trong thực đơn. 
                Câu hỏi của khách: $content";

                // 3. Gọi Google Gemini API
                $apiKey = env('GEMINI_API_KEY');

                /** @var \Illuminate\Http\Client\Response $response */
                $response = Http::withoutVerifying()->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}", [
                    'contents' => [
                        ['parts' => [['text' => $systemPrompt]]]
                    ]
                ]);
                // 4. Lọc lấy câu trả lời text từ API trả về
                $responseBody = $response->json();

                if (isset($responseBody['error'])) {
                    // GHI LOG ẨN: Lưu lỗi vào backend để Dev biết

                    Log::error('Gemini API Error: ' . $responseBody['error']['message']);
                    // HIỂN THỊ CHO KHÁCH: Câu xin lỗi lịch sự

                    $aiText = "Xin lỗi bạn, trợ lý AI hiện đang hơi quá tải. Bạn vui lòng tham khảo Menu qua các nút bấm bên dưới nhé!";
                } else {
                    // Lấy câu trả lời thành công, nếu kẹt định dạng thì trả về câu mặc định
                    $aiText = data_get($responseBody, 'candidates.0.content.parts.0.text', "Xin lỗi, tôi chưa hiểu rõ ý bạn. Bạn có thể nói rõ hơn hoặc chọn Menu bên dưới nhé.");
                }
                return response()->json([
                    'reply_type' => 'text',
                    'message' => $aiText,
                    'data' => null
                ]);
            } catch (\Exception $e) {
                // GHI LOG ẨN: Lưu lỗi đứt mạng/sập server vào backend
                
                Log::error('Chatbot Exception: ' . $e->getMessage());

                // HIỂN THỊ CHO KHÁCH: Câu xin lỗi chung chung
                return response()->json([
                    'reply_type' => 'text',
                    'message' => 'Xin lỗi, kết nối đang bị gián đoạn. Bạn hãy dùng các nút bấm ở dưới để xem Menu nhanh nhé!'
                ]);
            }
        }
    }
}
