<?php

namespace WOOTB\includes;

class OptionPanel extends \WC_Settings_Page
{

    /**
     * @var TelegramAdaptor
     */
    protected $telegram;
    protected $current_section;

    public function __construct($telegram)
    {
        global $current_section;

        $this->id = 'wootb';
        $this->label = __('Telegram bot', 'notify-bot-woocommerce');
        $this->current_section = $current_section;
        $this->telegram = $telegram;
        parent::__construct();
    }

    function get_own_sections()
    {
        return [
            '' => __('telegram', 'notify-bot-woocommerce'),
            'register' => __('register', 'notify-bot-woocommerce'),
            'users' => __('users', 'notify-bot-woocommerce'),
            'template' => __('message', 'notify-bot-woocommerce')
        ];


    }

    public function get_settings_for_default_section()
    {
        return [
            'section_title_1' => [
                'name' => __('Telegram Configuration', 'notify-bot-woocommerce'),
                'type' => 'title',
                'id' => 'wc_settings_tab_wootb_title'
            ],
            'token' => [
                'name' => __('bot token', 'notify-bot-woocommerce'),
                'type' => 'text',
                'id' => 'wootb_setting_token',
                'desc_tip' => true,
                'desc' => __('Enter your bot token', 'notify-bot-woocommerce')
            ],
            'use_proxy' => [
                'name' => __('use proxy', 'notify-bot-woocommerce'),
                'type' => 'checkbox',
                'id' => 'wootb_use_proxy',
                'desc_tip' => false,
                'desc' => __("It is particularly useful if your server is located in a country that has banned Telegram.", 'notify-bot-woocommerce')
            ],
            'section_end' => [
                'type' => 'sectionend',
                'id' => 'wc_settings_tab_wootb_end_section'
            ],
        ];
    }

    public function get_settings_for_register_section()
    {
        $otp = get_option('wootb_setting_otp');
        $bot_uname = get_option('wootb_bot_username');
        $link = "https://t.me/$bot_uname?start=$otp";
        $encoded_link = urlencode($link);

        return [
            'link_title' => [
                'name' => __('Registration link (bot start)', 'notify-bot-woocommerce'),
                'id' => 'wc_settings_tab_wootb_title_2',
                'type' => 'title'
            ],
            'register_link' => [
                'type' => 'info',
                'text' => "<a href=\"$link\">$link</a>"
            ],
            'register_qr' => [
                'type' => 'info',
                'text' => "<a href=\"$link\"><img src=\"https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=$encoded_link&choe=UTF-8\"></a>"
            ],
            'section_end' => [
                'type' => 'sectionend',
                'id' => 'wc_settings_tab_wootb_end_section_2'
            ],
        ];
    }

    public function get_settings_for_users_section()
    {
        return [
            'section_title_1' => [
                'name' => __('Users management', 'notify-bot-woocommerce'),
                'type' => 'title',
                'id' => 'wc_settings_tab_wootb_title_1',
                'desc' => $this->render_users_table()
            ],
            'section_end' => [
                'type' => 'sectionend',
                'id' => 'wc_settings_tab_wootb_end_section_2'
            ],
        ];
    }

    public function render_users_table()
    {
        $users = json_decode(get_option('wootb_setting_users'), true);
        $headers = [
            __('ID', 'notify-bot-woocommerce'),
            __('username', 'notify-bot-woocommerce'),
            __('first name', 'notify-bot-woocommerce'),
            __('last name', 'notify-bot-woocommerce'),
            __('remove', 'notify-bot-woocommerce')
        ];
        $table = "<table class=\"wootb-table\"><tr><th>$headers[0]</th><th>$headers[1]</th><th>$headers[2]</th><th>$headers[3]</th><th>$headers[4]</th></tr>";
        foreach ($users as $uid => $user) {
            $remove_url = admin_url("admin.php?action=remove_wootb_user&uid=$uid");
            $table .= "<tr><td>{$user['id']}</td><td>{$user['uname']}</td><td>{$user['fname']}</td><td>{$user['lname']}</td><td><a href=\"$remove_url\"><span class=\"dashicons dashicons-trash\"></span></a></td></tr>";
        }
        $table .= "</table>";

        return $table;
    }

    public function get_settings_for_template_section()
    {
        return [
            'section_title_1' => [
                'name' => __('Message settings', 'notify-bot-woocommerce'),
                'type' => 'title',
                'id' => 'wc_settings_tab_wootb_title_1'
            ],
            'message_template' => [
                'name' => __('template', 'notify-bot-woocommerce'),
                'type' => 'textarea',
                'id' => 'wootb_setting_template',
                'class' => 'code',
                'css' => 'max-width:550px;width:100%;',
                'default' => file_get_contents(WOOTB_PLUGIN_DIR . '/views/default-msg.php'),
                'custom_attributes' => ['rows' => 10],
            ],
            'remove_buttons_on_status' => [
                'name' => __('Remove btn on status', 'notify-bot-woocommerce'),
                'type' => 'multiselect',
                'id' => 'wootb_remove_btn_statuses',
                'options'  => wc_get_order_statuses(),
                'class'    => 'wc-enhanced-select',
                'desc_tip' => true,
                'desc' => __("Remove message keyboards on order complete.", 'notify-bot-woocommerce')
            ],
            'update_on_status' => [
                'name' => __('Send on specific status', 'notify-bot-woocommerce'),
                'type' => 'multiselect',
                'id' => 'wootb_update_statuses',
                'options'  => wc_get_order_statuses(),
                'class'    => 'wc-enhanced-select',
                'desc_tip' => true,
                'desc' => __("Remove message keyboards on order complete.", 'notify-bot-woocommerce')
            ],
            'section_end' => [
                'type' => 'sectionend',
                'id' => 'wc_settings_tab_wootb_end_section_2'
            ],
        ];
    }

    public function save()
    {
        parent::save();
        if (in_array($this->current_section, ['', 'register'])) {
            $response = $this->telegram->setWebhook(site_url("/wp-json/wootb/telegram/hook"));
            if ($response->ok) {
                $info = $this->telegram->getInfo();
                update_option('wootb_bot_username', $info->result->username);
                update_option('wootb_setting_otp', md5(time()));
            }
        }
    }
}