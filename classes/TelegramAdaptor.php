<?php

namespace WoocommerceTelegramBot\classes;

class TelegramAdaptor
{

    private $tel_url = 'https://tlbot.devnow.ir';
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
        $data = ['chat_id' => $chatId, 'text' => $text, 'caption' => $text, 'allow_sending_without_reply' => true, 'parse_mode' => 'HTML'];
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

        return json_decode(file_get_contents("$this->reqUrl/$method?$data"));
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