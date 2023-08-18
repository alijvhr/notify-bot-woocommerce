<?php

namespace WoocommerceTelegramBot\classes;

class Initializer extends Singleton
{
    /**@var TelegramAdaptor */
    public $telegram;

    function init()
    {
        $path = dirname(plugin_basename(__FILE__), 2);
        load_plugin_textdomain('woo-telegram-bot', false, "$path/languages/");
        $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));

        if (in_array('woocommerce/woocommerce.php', $active_plugins)) {
            $this->run();
        } else {
            add_action('admin_notices', [$this, 'woocommerceNotice']);
        }
    }

    private function run()
    {
        add_action('plugins_loaded', [$this, 'loadHooks'], 26);
        add_filter('plugin_action_links_WoocommerceTelegramBot/WoocommerceTelegramBot.php', [$this, 'add_action_links']);
    }

    function woocommerceNotice()
    {
        $message = __('Please activate woocommerce on your wp installation in order to use Woocommerce Telegram Bot plugin', 'woo-telegram-bot');
        echo "<div class=\"notice notice-error\"><p>$message</p></div>";
    }

    function add_action_links($actions)
    {
        $configure = __('Configure', 'woo-telegram-bot');
        $url = admin_url('admin.php?page=wc-settings&tab=wootb');
        $actions[] = "<a href=\"$url\">$configure</a>";
        return $actions;
    }


    function loadHooks()
    {
        $this->initTelegramApi();
        add_action('wp_ajax_wootb_send_test_message', [$this, 'sendTestMessage']);
        add_filter('woocommerce_get_settings_pages', array($this, 'addWooSettingSection'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_script'));
        add_action('rest_api_init', array($this, 'rest_routes'));
        $order_status_changed_enabled = get_option('wootb_send_after_order_status_changed', false);
        if ($order_status_changed_enabled == 'yes') {
            add_action('woocommerce_order_status_changed', array($this, 'woocommerce_order_status_changed'), 20, 4);
        } else {
            add_action('woocommerce_checkout_order_processed', array($this, 'woocommerce_new_order'));
        }
    }

    private function initTelegramApi()
    {
        $this->telegram = new TelegramAdaptor(get_option('wootb_setting_token'));

    }

    public function rest_routes()
    {
        register_rest_route('wootb/telegram', "/hook", [
            [
                'methods' => \WP_REST_Server::ALLMETHODS,
                'callback' => [
                    $this,
                    'telegramHook'
                ],
                'permission_callback' => '__return_true',
                'args' => []
            ]
        ]);
    }

    function admin_enqueue_script()
    {
        wp_enqueue_script('wootb', plugin_dir_url(__FILE__) . '../assets/js/admin.js', array('jquery'), '1.5', true);
    }

    function sendTestMessage()
    {
        try {
            $this->telegram->sendMessage(self::getTemplate());
            echo json_encode(['error' => 0, 'message' => __('پیام ارسال شد!', 'woo-telegram-bot')]);
            wp_die();
        } catch (\Exception $ex) {
            echo json_encode(['error' => 1, 'message' => $ex->getMessage()]);
            wp_die();
        }
    }

    private static function getTemplate()
    {
        return str_replace([
            "billing_first_name",
            "billing_last_name",
            "billing_address_1",
            "billing_address_2",
            "billing_city",
            "billing_state",
            "billing_postcode",
            "billing_email",
            "billing_phone"
        ], [
            "billing-first_name",
            "billing-last_name",
            "billing-address_1",
            "billing-address_2",
            "billing-city",
            "billing-state",
            "billing-postcode",
            "billing-email",
            "billing-phone"
        ], get_option('wootb_setting_template'));
    }

    public function woocommerce_new_order($order_id)
    {
        $this->sendUpdateToBot($order_id);
    }

    public function sendUpdateToBot($orderID)
    {
        $wc = new WooCommerceAdaptor($orderID);
        $text = $wc->interpolate(self::getTemplate());
        $chatIds = explode(',', get_option('wootb_setting_chatid'));
        $messageIds = json_decode(get_post_meta($orderID, 'telegramMessageIds', true) ?: "[]", true);
        $keyboard = [
            "inline_keyboard" => [
                [
                    ["text" => __('Completed', 'woo-telegram-bot'), "callback_data" => '{"id":"' . $orderID . '","status":"completed"}'],
                    ["text" => __('Refunded', 'woo-telegram-bot'), "callback_data" => '{"id":"' . $orderID . '","status":"refunded"}']
                ]
            ]
        ];
        foreach ($chatIds as $chatId) {
            do {
                if (isset($messageIds[$chatId])) {
                    $message = $this->telegram->updateMessage($chatId, $messageIds[$chatId], $text, $keyboard);
                } else {
                    $message = $this->telegram->sendMessage($chatId, $text, $keyboard);
                }
            } while (!isset($message->result) && sleep(2) !== false);
            $messageIds[$chatId] = $message->result->message_id;
        }
        update_post_meta($orderID, 'telegramMessageIds', json_encode($messageIds));
        return $messageIds;
    }

    public function woocommerce_order_status_changed($order_id, $status_transition_from, $status_transition_to, $that)
    {
        $order = wc_get_order($order_id);
        $statuses = get_option('wootb_order_statuses');
        if (in_array('wc-' . $order->get_status(), $statuses)) {
            $this->sendUpdateToBot($order->get_id());
        }
    }

    public function addWooSettingSection($settings)
    {
        $settings[] = new OptionPanel($this->telegram);

        return $settings;
    }

    public function telegramHook()
    {
        $this->telegram = new TelegramAdaptor(get_option('wootb_setting_token'));

        $chatIds = explode(',', get_option('wootb_setting_chatid'));
        $object = json_decode(\WP_REST_Server::get_raw_data());
        if (isset($object->callback_query))
            $this->telegram->callback($object->callback_query->id, 'how are you!');

        return new \WP_REST_Response($object, 200);
    }
}