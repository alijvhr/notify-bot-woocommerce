<?php

namespace WoocommerceTelegramBot\classes;

class TelegramAdaptor
{

    private $tel_url = 'https://api.telegram.org';
    /**
     * @var string
     */
    private $token = '';
    /**
     * @var string
     */
    private $accessTags = '<b><strong><i><u><em><ins><s><strike><del><a><code><pre>';

    private $reqUrl = '';

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function sendMessage($chatId, $text, $keyboard = null)
    {
        $data = $this->prepare($chatId, $text, $keyboard);
        return $this->request('sendMessage', $data);
    }

    public function prepare($chatId, $text, $keyboard = null): array
    {
        $text = strip_tags($text, $this->accessTags);
        $data = ['chat_id' => $chatId, 'text' => $text, 'allow_sending_without_reply' => true, 'parse_mode' => 'HTML'];
        if ($keyboard ?? 0) {
            $data['reply_markup'] = json_encode($keyboard);
        }
        return $data;
    }

    private function request($method, $data = [])
    {
        if (!$this->reqUrl) {
            $this->reqUrl = "$this->tel_url/bot$this->token";
        }

        $data = http_build_query($data);

        $ch = curl_init();
        $timeout = 3;

        curl_setopt($ch, CURLOPT_URL, "$this->reqUrl/$method?$data");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        $response = curl_exec($ch);
        if (curl_errno($ch) != 0) throw new \Exception('Cannot connect to telegram!' . curl_error($ch));
        $response = json_decode($response);
//        if (!$response->ok) throw new \Exception("$response->error_code: $response->description");
        curl_close($ch);
        return $response;
    }

    public function use_proxy($use = true)
    {
        $this->tel_url = $use ? 'https://tlbot.devnow.ir' : 'https://api.telegram.org';
    }

    public function updateMessage($chatId, $message_id, $text, $keyboard = null)
    {
        $data = $this->prepare($chatId, $text, $keyboard);
        $data['message_id'] = $message_id;
        return $this->request('editMessageText', $data);
    }

    public function callback($callbackQueryId, $text)
    {
        $data = ['callback_query_id' => $callbackQueryId, 'text' => $text];
        return $this->request('answerCallbackQuery', $data);
    }

    public function setWebhook($url)
    {
        return $this->request('setWebhook', ['url' => $url]);
    }

    public function getWebhookInfo()
    {
        return $this->request('getWebhookInfo');
    }
}