<?php

namespace WoocommerceTelegramBot\classes;


use WC_Customer;

class WooCommerceAdaptor
{

    public $pattern;
    public $order;
    public $order_id;
    public $status_access;

    function __construct($order_id)
    {
        $this->pattern = array();
        $this->status_access = array();
        $this->order = wc_get_order($order_id);
        $this->order_id = $order_id;
        add_filter('wootb_filter_code_template', array($this, 'filterTemplate'), 10, 2);

    }

    public function interpolate($str)
    {
        $this->decodeShortcode($str);
        $pr = $this->getProducts();
        $str = str_replace(array_keys($pr), array_values($pr), $str);

        return str_replace(array_keys($this->pattern), array_values($this->pattern), $str);
    }

    private function decodeShortcode($str)
    {
        $pattern = '/\{.+?}/m';
        preg_match_all($pattern, $str, $matches, PREG_SET_ORDER, 0);
        array_walk_recursive($matches, function ($item, $key) {
            $pattern = explode('-', preg_replace('/\{|\}/', '', $item));
            if (count($pattern) > 1) {
                $this->pattern[$item] = (string)$this->order->data[$pattern[0]][$pattern[1]];
            } else {
                $res = preg_replace('/\{|\}/', '', $item);
                $_result = $this->order->data[$res];
                if ($_result) {
                    $this->pattern[$item] = $_result;
                } else {
                    $this->pattern[$item] = $this->order->get_meta($res) ?: '';
                }
            }
        });
        $this->pattern = apply_filters('wootb_filter_code_template', $this->pattern, $this->order_id);
    }

    private function getProducts(): array
    {
        $items = $this->order->get_items();
        $product_meta = "";
        $product = chr(10);
        if (!empty($items)) {
            foreach ($items as $item) {
                $product_item = $item->get_product();
                if ($product_item) {
                    $price = wc_get_price_to_display($product_item);
                    $product .= $item['name'] . ': ' . $item['quantity'] . '  عدد ' . ' با قیمت ' . wc_price($price) . chr(10);
                    $item_meta = $item->get_meta_data();
                    if ($item_meta) {
                        if (is_array($item_meta)) {
                            foreach ($item_meta as $object) {
                                $product_meta .= $object->key . " : " . $object->value . "\n";
                                $return["{product_meta_$object->key}"] = $object->value;
                            }
                            $return['{product_meta}'] = $product_meta;
                        }
                    }
                }
            }
        }
        $return['{products}'] = $product;
        $shop = $this->order->get_items('shipping');
        if ($shop) {
            $shipping = end($shop)->get_data();
            $return['{shipping_method_title}'] = $shipping['method_title'];
        }
        $return['{site_url}'] = get_option('siteurl', $_SERVER['HTTP_HOST']);
        $return['{site_name}'] = get_option('blogname', "blog");
        $return['{site_tag}'] = preg_replace('/\W/', '', get_option('blogname', "blog"));

        return $return;
    }

    function filterTemplate($replace)
    {
        $replace['{order_id}'] = $this->order_id;
        $replace['{customer_id}'] = $this->order->get_user_id();
        $replace['{customer_order_count}'] = $this->wcGetCustomerOrderCount();
        $replace['{order_status}'] = wc_get_order_status_name($this->order->get_status());
        $replace['{order_notes}'] = $this->order->get_customer_note();
        $replace['{order_icon}'] = ['processing' => '⏰', 'completed' => '✅', 'cancelled' => '❌'][$this->order->get_status()];
        $replace['{total}'] = wc_price($this->order->get_total());
        $date = $this->order->get_date_created()->date(get_option('links_updated_date_format'));
        $replace['{order_date_created}'] = $date;
        $replace['{order_date_created_per}'] = PersianDate::jdate('d F Y, g:i a', strtotime($date));
        $replace['{site_name}'] = get_bloginfo();

        return $replace;
    }

    function wcGetCustomerOrderCount()
    {
        $count = "";
        try {
            $customer = new WC_Customer($this->order->get_user_id());
            $count = $customer->get_order_count();
        } catch (\Exception $e) {
        }

        return $count ?? "";
    }
}