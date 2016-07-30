DELETE FROM admin_pages WHERE page_key='configOnePageCheckout' LIMIT 1;
DELETE FROM configuration WHERE configuration_key LIKE 'CHECKOUT_ONE_%';
DELETE FROM configuration_group WHERE configuration_group_title = 'One-Page Checkout Settings' LIMIT 1;