<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TelegramUser;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    public function handleWebhook(Request $request)
    {
        return response()->json("haloo").
        // Menangkap data JSON yang dikirim oleh Telegram
        $update = $request->all();

        // Log data yang diterima (optional)
        Log::info('Update Telegram:', $update);

        // Ambil chat_id dan nama pengguna dari pesan yang diterima
        $chatId = $update['message']['chat']['id'] ?? null;
        $firstName = $update['message']['chat']['first_name'] ?? '';
        $lastName = $update['message']['chat']['last_name'] ?? '';
        $fullName = $firstName . ' ' . $lastName;

        if ($chatId && $fullName) {
            // Simpan chat_id dan nama pengguna ke dalam database jika belum ada
            $this->saveTelegramUser($chatId, $fullName);

            // Kirimkan pesan balasan ke Telegram
            $this->sendMessageToTelegram($chatId, "Pesan diterima, terima kasih!");
        }

        return response()->json(['status' => 'success']);
    }

    private function sendMessageToTelegram($chatId, $message)
    {
        // Token bot Anda
        $botToken = env('TELEGRAM_BOT_TOKEN');  // Ambil token dari .env

        // URL API Telegram untuk mengirim pesan
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

        // Mengirim permintaan POST ke Telegram API
        $response = \Http::post($url, [
            'chat_id' => $chatId,
            'text' => $message
        ]);

        // Log response jika diperlukan
        Log::info('Telegram response: ', $response->json());
    }

    private function saveTelegramUser($chatId, $fullName)
    {
        // Periksa apakah chat_id sudah ada di database
        $telegramUser = TelegramUser::firstOrCreate(
            ['chat_id' => $chatId],  // Jika chat_id sudah ada, tidak akan membuat entri baru
            ['name' => $fullName]    // Jika chat_id tidak ada, akan menyimpan nama pengguna
        );

        Log::info('User saved: ', ['chat_id' => $chatId, 'name' => $fullName]);
    }
}
