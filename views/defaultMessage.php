{order_icon} Status: <b><u>{order_status}</u></b>
#order_{order_id}
Order ID: <b><a href="{site_url}/wp-admin/post.php?post={order_id}&amp;action=edit">{order_id}</a></b>
🗓 Order creation time: {order_date_created_per}
------------------
📃 Products: {products}
💲 Total: {total}
------------------
Recipient: {billing_first_name} {billing_last_name}
Address: {billing_state} - {billing_city} - {billing_address_1} - {billing_address_2}
Phone: {billing_phone}
Zip/Postal Code: {billing_postcode}
Notes: {order_notes}
Shipping Method: {shipping_method_title}