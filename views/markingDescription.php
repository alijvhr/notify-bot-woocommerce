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