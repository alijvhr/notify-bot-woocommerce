<?php

namespace WoocommerceTelegramBot\classes;

class OptionPanel extends \WC_Settings_Page
{

    public function __construct()
    {
        parent::__construct();
        $this->id = 'wootb';
        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 50);
        add_action('woocommerce_settings_' . $this->id, array($this, 'output'));
        add_action('woocommerce_settings_save_' . $this->id, array($this, 'save'));

    }

    public function add_settings_tab($settings_tabs)
    {
        $settings_tabs[$this->id] = __('Telegram bot', 'woo-telegram-bot');

        return $settings_tabs;
    }

    public function output()
    {
        $settings = $this->get_settings();
        \WC_Admin_Settings::output_fields($settings);
        $this->renderAllowTagsDescription();
    }

    public function get_settings($section = null)
    {
        $settings = array(
            'section_title_1' => array(
                'name' => __('راهنما', 'woo-telegram-bot'),
                'type' => 'title',
                'desc' => $this->renderHelpDescription(),
                'id' => 'wc_settings_tab_wootb_title_1'
            ),
            'token' => array(
                'name' => __('توکن ربات', 'woo-telegram-bot'),
                'type' => 'text',
                'id' => 'wootb_setting_token',
                'desc_tip' => true,
                'desc' => __('توکن ربات را وارد کنید', 'woo-telegram-bot')
            ),
            'chatid' => array(
                'name' => __('آیدی چت یا گروه', 'woo-telegram-bot'),
                'type' => 'text',
                'id' => 'wootb_setting_chatid',
                'desc_tip' => true,
                'desc' => __('آیدی چت یا گروه را وارد کنید، برای اطلاعات بیشتر به ربات تلگرامی @UserAccInfoBot مراجعه نمایید', 'woo-telegram-bot')
            ),
            'sending_after_order_status_changed' => array(
                'name' => __('ارسال نوتیفیکشن با تغییر وضعیت', 'woo-telegram-bot'),
                'type' => 'checkbox',
                'id' => 'wootb_send_after_order_status_changed',
                'desc_tip' => true,
                'desc' => __("براساس وضعیت های انتخابی هنگام ثبت سفارش نوتیفیکیشن ارسال می شود. در غیر اینصورت برای همه وضعیت ها نوتیفیکیشن ارسال می شود.")
            ),
            'order_statuses' => array(
                'name' => __('انتخاب وضعیت های سفارش', 'woo-telegram-bot'),
                'type' => 'multiselect',
                'id' => 'wootb_order_statuses',
                'options' => wc_get_order_statuses(),
                'class' => 'wc-enhanced-select',
                'desc_tip' => true,
                'css' => 'width:45%;',
                'desc' => __('وضعیت هایی که برایشان نوتیفیکیشن ارسال می شود', 'woo-telegram-bot')
            ),
            'message_template' => array(
                'name' => __('نمونه پیام ارسالی', 'woo-telegram-bot'),
                'type' => 'textarea',
                'id' => 'wootb_setting_template',
                'class' => 'code',
                'css' => 'max-width:550px;width:100%;',
                'default' => include(dirname(__DIR__) . "/default-msg.php"),
                'custom_attributes' => ['rows' => 10],
            ),
            'section_end' => array(
                'type' => 'sectionend',
                'id' => 'wc_settings_tab_wootb_end_section_2'
            ),
        );

        return apply_filters('wc_settings_tab_wootb_settings', $settings, $section);

    }

    public function renderHelpDescription()
    {

        $chatid_help = wp_kses(__("برای دریافت آیدی تان نیز از ربات <a href='https://t.me/UserAccInfoBot' target='_blank'>@UserAccInfoBot</a> استفاده کنید. راهنمایی های بیشتر و نحوه بدست آوردن آیدی چت یا گروه در این ربات وجود دارد.", "wootb"), ['a' => ['href' => 'https://t.me/UserAccInfoBot', 'target' => '_blank'], 'code' => []]);

        return $chatid_help . chr(10);
    }

    public function renderAllowTagsDescription()
    {
        ?>
        <style>
            .row {
                display: flex;
                flex-wrap: wrap;
                justify-content: space-between;
            }

            textarea {
                background-color: #141414;
                color: #F8F8F8;
                width: 100%;
                font: 12px/normal 'Monaco', 'Menlo', 'Ubuntu Mono', 'Consolas', 'source-code-pro', monospace;
            }
        </style>
        <div class="row text-right">
            <table class="form-table">
                <tbody>
                <tr>
                    <th><?php echo __('ارسال پیام تستی', 'woo-telegram-bot') ?></th>
                    <td>
                        <button id="wootb_send_test_message" type="button"
                                class="button-primary"><?= __('ارسال پیام', 'woo-telegram-bot') ?></button>
                    </td>
                </tr>
                <tr>
                    <th><?php echo __('تگ های مجاز', 'woo-telegram-bot') ?></th>
                    <td>
                        <div class="text-right" style="text-align: right;">
                            <pre>&ltb&gtbold&lt/b&gt</pre>
                            &#10;<pre>&ltstrong&gtbold&lt/strong&gt</pre>
                            &#10;<pre>&lti&gtitalic&lt/i&gt</pre>
                            &#10;<pre>&ltem&gtitalic&lt/em&gt</pre>
                            &#10;<pre>&ltu&gtunderline&lt/u&gt</pre>
                            &#10;<pre>&ltins&gtunderline&lt/ins&gt</pre>
                            &#10;<pre>&lts&gtstrikethrough&lt/s&gt</pre>
                            &#10;<pre>&ltstrike&gtstrikethrough&lt/strike&gt</pre>
                            &#10;<pre>&ltdel&gtstrikethrough&lt/del&gt</pre>
                            &#10;<pre>&lta href="http://www.domain.com/">inline URL&lt/a&gt</pre>
                            &#10;<pre>&ltcode&gtcode&lt/code&gt</pre>
                            &#10;<pre>&ltpre&gtcode block&lt/pre&gt</pre>

                        </div>
                    </td>
                </tr>
                <tr>
                    <th><?php echo __('شورت کد های قابل استفاده', 'woo-telegram-bot') ?></th>
                    <td>
                        <div>
                            <p>نام وبسایت: <code>{site_name}</code></p>
                            <p>نام وبسایت: <code>{site_url}</code></p>
                            <p>نام وبسایت: <code>{site_tag}</code></p>
                            <p>شماره سفارش: <code>{order_id}</code></p>
                            <p>زمان ثبت سفارش: <code>{order_date_created}</code></p>
                            <p>زمان ثبت سفارش (شمسی): <code>{order_date_created_per}</code></p>
                            <p>وضعیت سفارش: <code>{order_status}</code></p>
                            <p>آیتم های سفارش: <code>{products}</code></p>
                            <p>مجموع مبلغ سفارش: <code>{total}</code></p>
                            <p>نام: <code>{billing_first_name}</code></p>
                            <p>نام خانوادگی: <code>{billing_last_name}</code></p>
                            <p>بخش اول آدرس: <code>{billing_address_1}</code></p>
                            <p>بخش دوم آدرس: <code>{billing_address_2}</code></p>
                            <p>شهر: <code>{billing_city}</code></p>
                            <p>استان: <code>{billing_state}</code></p>
                            <p>کدپستی: <code>{billing_postcode}</code></p>
                            <p>ایمیل: <code>{billing_email}</code></p>
                            <p>شماره تلفن: <code>{billing_phone}</code></p>
                            <p>روش پرداخت: <code>{payment_method}</code></p>
                            <p>روش ارسال: <code>{shipping_method_title}</code></p>
                            <p>نام متد پرداخت: <code>{payment_method_title}</code></p>
                            <p>آیپی پرداخت کننده: <code>{customer_ip_address}</code></p>
                            <p>آیدی کاربری مشتری: <code>{customer_id}</code></p>
                            <p>تعداد سفارش های مشتری: <code>{customer_order_count}</code></p>
                            <p>مرورگر پرداخت کننده: <code>{customer_user_agent}</code></p>
                            <p>تمامی متا دیتاهای محصول: <code>{product_meta}</code></p>
                            <p>یک متا دیتای خاص براساس کلید آن: <code>{product_meta_[meta_key]}</code></p>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function save()
    {
        $settings = $this->get_settings();
        \WC_Admin_Settings::save_fields($settings);
    }
}