<?php

namespace WoocommerceTelegramBot\classes;

class OptionPanel extends \WC_Settings_Page
{

    /**
     * @var TelegramAdaptor
     */
    protected $telegram;
    protected $current_section;

    public function __construct($telegram)
    {
        parent::__construct();

        $this->id = 'wootb';
        global $current_section;
        $this->current_section = $current_section;
        add_filter('woocommerce_settings_tabs_array', [$this, 'add_settings_tab'], 50);
        add_action('woocommerce_settings_' . $this->id, [$this, 'output']);
        add_action('woocommerce_settings_save_' . $this->id, [$this, 'save']);
        add_action('woocommerce_sections_' . $this->id, [$this, 'wcslider_add_section']);
        $this->telegram = $telegram;
    }

    function wcslider_add_section()
    {
        $sections = ['' => 'hhh', 'hello2' => 'kkkk'];
        echo '<ul class="subsubsub">';

        $array_keys = array_keys($sections);

        foreach ($sections as $id => $label) {
            echo '<li><a href="' . admin_url('admin.php?page=wc-settings&tab=' . $this->id . '&section=' . sanitize_title($id)) . '" class="' . ($this->current_section == $id ? 'current' : '') . '">' . $label . '</a> ' . (end($array_keys) == $id ? '' : '|') . ' </li>';
        }

        echo '</ul><br class="clear" />';
        return apply_filters('woocommerce_get_sections_' . $this->id, $sections);

    }

    public function add_settings_tab($settings_tabs)
    {
        $settings_tabs[$this->id] = __('Telegram bot', 'woo-telegram-bot');

        return $settings_tabs;
    }

    public function output()
    {
        $settings = $this->get_settings($this->current_section);
        \WC_Admin_Settings::output_fields($settings);
        $this->renderAllowTagsDescription();
    }

    public function get_settings($section = null)
    {
        $settings = '';
        switch ($section) {
            case '':
                $settings = [
                    'section_title_1' => [
                        'name' => __('راهنما', 'woo-telegram-bot'),
                        'type' => 'title',
                        'desc' => $this->renderHelpDescription(),
                        'id' => 'wc_settings_tab_wootb_title_1'
                    ],
                    'token' => [
                        'name' => __('توکن ربات', 'woo-telegram-bot'),
                        'type' => 'text',
                        'id' => 'wootb_setting_token',
                        'desc_tip' => true,
                        'desc' => __('توکن ربات را وارد کنید', 'woo-telegram-bot')
                    ],
                    'chatid' => [
                        'name' => __('آیدی چت یا گروه', 'woo-telegram-bot'),
                        'type' => 'text',
                        'id' => 'wootb_setting_chatid',
                        'desc_tip' => true,
                        'desc' => __('آیدی چت یا گروه را وارد کنید، برای اطلاعات بیشتر به ربات تلگرامی @UserAccInfoBot مراجعه نمایید', 'woo-telegram-bot')
                    ],
                    'use_proxy' => [
                        'name' => __('use proxy', 'woo-telegram-bot'),
                        'type' => 'checkbox',
                        'id' => 'wootb_use_proxy',
                        'desc_tip' => true,
                        'desc' => __("To access Telegram servers using a proxy service, you can enable this feature. It is particularly useful if your server is located in a country that has banned Telegram.")
                    ],
                    'message_template' => [
                        'name' => __('نمونه پیام ارسالی', 'woo-telegram-bot'),
                        'type' => 'textarea',
                        'id' => 'wootb_setting_template',
                        'class' => 'code',
                        'css' => 'max-width:550px;width:100%;',
                        'default' => file_get_contents(WOOTB_PLUGIN_DIR . '/views/default-msg.php'),
                        'custom_attributes' => ['rows' => 10],
                    ],
                    'section_end' => [
                        'type' => 'sectionend',
                        'id' => 'wc_settings_tab_wootb_end_section_2'
                    ],
                ];
                break;
        }

        return apply_filters('wc_settings_tab_' . $this->id, $settings, $section);

    }

    public function renderHelpDescription()
    {

        $chatid_help = wp_kses(__("برای دریافت آیدی تان نیز از ربات <a href='https://t.me/UserAccInfoBot' target='_blank'>@UserAccInfoBot</a> استفاده کنید. راهنمایی های بیشتر و نحوه بدست آوردن آیدی چت یا گروه در این ربات وجود دارد.", "wootb"), ['a' => ['href' => 'https://t.me/UserAccInfoBot', 'target' => '_blank'], 'code' => []]);

        return $chatid_help . chr(10);
    }

    public function renderAllowTagsDescription()
    {
        include WOOTB_PLUGIN_DIR . '/views/markingDescription.php';
    }

    public function save()
    {
        $settings = $this->get_settings();
        $this->telegram->setWebhook(site_url("/wp-json/wootb/telegram/hook"));
        \WC_Admin_Settings::save_fields($settings);
    }
}