<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TelegramUser;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{

    public function handleWebhook(Request $request)
    {
        $update = $request->all();

        Log::info('Update Telegram:', $update);

        $chatId = $update['message']['chat']['id'] ?? null;
        $firstName = $update['message']['chat']['first_name'] ?? '';
        $lastName = $update['message']['chat']['last_name'] ?? '';
        $fullName = $firstName . ' ' . $lastName;

        if ($chatId && $fullName) {
            $this->saveTelegramUser($chatId, $fullName);

            $this->sendMessageToTelegram($chatId, "Pesan diterima, terima kasih!");
        }

        return response()->json(['status' => 'success']);
    }

    private function sendMessageToTelegram($chatId, $message)
    {
        $botToken = env('TELEGRAM_BOT_TOKEN');  


        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

        $response = \Http::post($url, [
            'chat_id' => $chatId,
            'text' => $message
        ]);

        Log::info('Telegram response: ', $response->json());
    }

    private function saveTelegramUser($chatId, $fullName)
    {
        $telegramUser = TelegramUser::firstOrCreate(
            ['chat_id' => $chatId],
            ['name' => $fullName] 
        );

        Log::info('User saved: ', ['chat_id' => $chatId, 'name' => $fullName]);
    }

    public function getChatIds()
    {
        $chatIds = TelegramUser::all()->pluck('chat_id')->toArray();  

        $chatIdsFormatted = array_map(function ($id) {
            return '"' . $id . '"';
        }, $chatIds);

        $chatIdsString = '{' . implode(', ', $chatIdsFormatted) . '}';

        return response()->json([
            'status' => 'success',
            'chat_ids' => $chatIdsString
        ]);
    }
}
