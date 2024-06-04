{order.icon} Status: <b><u>{order.status}</u></b>
#order_{order.d}
Order ID: <b><a href="{order.edit_url">{order.id}</a></b>
🗓 Order creation time: {order.date_created_per}
------------------
📃 Products: {products}
💲 Total: {total}
🚚 Shipping fee: {shipping.total}
------------------
Recipient: {billing.first_name} {billing.last_name}
Address: {billing.state} - {billing.city} - {billing.address_1} - {billing.address_2}
Phone: {billing.phone}
Zip/Postal Code: {billing.postcode}
Notes: {order.notes}
Shipping Method: {shipping.method_title}
Payment Method: {payment.method_title}