<?php

if (! defined('CACHING_TIME')) {
    define('CACHING_TIME', 600);
}
if (! defined('FLUSH_ALL')) {
    define('FLUSH_ALL', 'flush-all');
}
if (! defined('FLUSH_STORE_BY_ID')) {
    define('FLUSH_STORE_BY_ID', 'flush-store-by-id-');
}
if (! defined('FLUSH_STORE_BY_SLUG')) {
    define('FLUSH_STORE_BY_SLUG', 'flush-store-by-slug-');
}
if (! defined('FLUSH_TRANSLATION')) {
    define('FLUSH_TRANSLATION', 'flush-translation');
}
if (! defined('STORE_CHANGE_BY_ID')) {
    define('STORE_CHANGE_BY_ID', 'store-by-id-');
}
if (! defined('STORE_CHANGE_BY_SLUG')) {
    define('STORE_CHANGE_BY_SLUG', 'store-by-slug-');
}
if (! defined('TAKEAWAY_SETTING')) {
    define('TAKEAWAY_SETTING', 'takeaway-setting-');
}
if (! defined('MULTI_SAFE_PAY')) {
    define('MULTI_SAFE_PAY', 'multi-safe-pay');
}
if (! defined('STORE_SETTING')) {
    define('STORE_SETTING', 'store-setting');
}
if (! defined('ORDERS')) {
    define('ORDERS', 'orders');
}
if (! defined('ORDER_WITH_ITEMS')) {
    define('ORDER_WITH_ITEMS', 'order-with-items-');
}
if (! defined('SUB_ORDER_WITH_ITEMS')) {
    define('SUB_ORDER_WITH_ITEMS', 'sub-order-with-items-');
}
if (! defined('ALL_STORE_RESERVATIONS')) {
    define('ALL_STORE_RESERVATIONS', 'all-store-reservations');
}
if (! defined('STORE_RESERVATION_WITH_TABLE')) {
    define('STORE_RESERVATION_WITH_TABLE', 'store-reservation-with-table-');
}
if (! defined('GIFT_ORDER_WITH_QR')) {
    define('GIFT_ORDER_WITH_QR', 'gift-order-with-qr-');
}
if (! defined('STORE_POS_SETTING')) {
    define('STORE_POS_SETTING', 'store-pos-setting');
}
if (! defined('DEVICE_PRINTERS')) {
    define('DEVICE_PRINTERS', 'device-printers-');
}
if (! defined('ALL_STORES')) {
    define('ALL_STORES', 'all-stores');
}
if (! defined('STORE_OWNERS')) {
    define('STORE_OWNERS', 'store-owners');
}
if (! defined('STORE_MANAGERS')) {
    define('STORE_MANAGERS', 'store-managers');
}
if (! defined('STORE_POS_EMPLOYEE')) {
    define('STORE_POS_EMPLOYEE', 'store-pos-employee');
}
if (! defined('STORES_PRINTERS')) {
    define('STORES_PRINTERS', 'stores-printer-');
}
if (! defined('STORES_POS_IMAGES')) {
    define('STORES_POS_IMAGES', 'stores-pos-images-');
}
if (! defined('ALL_CATEGORIES')) {
    define('ALL_CATEGORIES', 'all-categories');
}
if (! defined('ALL_PRODUCTS')) {
    define('ALL_PRODUCTS', 'all-products');
}
if (! defined('CONTENT_PAGES')) {
    define('CONTENT_PAGES', 'content-pages');
}
if (! defined('CATEGORIES')) {
    define('CATEGORIES', 'categories-');
}
if (! defined('PRODUCTS')) {
    define('PRODUCTS', 'products-');
}
if (! defined('SUB_CATEGORIES')) {
    define('SUB_CATEGORIES', 'sub-categories-');
}
if (! defined('SUPPLEMENTS')) {
    define('SUPPLEMENTS', 'supplements-');
}
if (! defined('PRE_ORDERS')) {
    define('PRE_ORDERS', 'pre-orders-');
}
if (! defined('PRODUCT_PRE_ORDERS')) {
    define('PRODUCT_PRE_ORDERS', 'product-pre-orders-');
}
if (! defined('MOST_POPULAR_PRODUCTS')) {
    define('MOST_POPULAR_PRODUCTS', 'most-popular-products-');
}
if (! defined('UPSALE_CATEGORY')) {
    define('UPSALE_CATEGORY', 'upsale-categories-');
}
if (! defined('CAT_PRODUCT_UPSALE')) {
    define('CAT_PRODUCT_UPSALE', 'cat-product-upsale-');
}
if (! defined('PRODUCT_UPSALE')) {
    define('PRODUCT_UPSALE', 'product-upsale-');
}
if (! defined('TABLES')) {
    define('TABLES', 'tables');
}
if (! defined('TABLE_BY_QR')) {
    define('TABLE_BY_QR', 'table-by-qr-');
}
if (! defined('TABLE_BY_ID')) {
    define('TABLE_BY_ID', 'table-by-id-');
}
if (! defined('STORE_BY_QR')) {
    define('STORE_BY_QR', 'store-by-qr-QR_CODE');
}
if (! defined('STORE_BUTLER')) {
    define('STORE_BUTLER', 'store-butler-');
}
if (! defined('KIOSK_DEVICES')) {
    define('KIOSK_DEVICES', 'kiosk-devices');
}
if (! defined('KIOSK_BANNER_IMAGES')) {
    define('KIOSK_BANNER_IMAGES', 'kiosk-banner-images-');
}
if (! defined('KIOSK_SLIDER_IMAGES')) {
    define('KIOSK_SLIDER_IMAGES', 'kiosk-slider-images-');
}
if (! defined('STORE_LANGUAGE')) {
    define('STORE_LANGUAGE', 'store-language-');
}
/* TAKEAWAY CACHING TAGS */
if (! defined('FLUSH_TAKEAWAY')) {
    define('FLUSH_TAKEAWAY', 'flush-takeaway');
}
if (! defined('EP_TAKEAWAY_FORM')) {
    define('EP_TAKEAWAY_FORM', 'ep-takeaway-form');
}
if (! defined('EP_TIME_SLOTS')) {
    define('EP_TIME_SLOTS', 'ep-time-slots');
}
if (! defined('EP_POST_ORDER')) {
    define('EP_POST_ORDER', 'ep-post-order');
}
if (! defined('EP_GENERAL_INFO')) {
    define('EP_GENERAL_INFO', 'ep-general-info');
}
if (! defined('EP_DELIVERY_RATE')) {
    define('EP_DELIVERY_RATE', 'ep-delivery-rate');
}
/* DINE IN CACHING TAGS */
if (! defined('FLUSH_DINE_IN')) {
    define('FLUSH_DINE_IN', 'flush-dinein');
}
if (! defined('EP_POST_DINE_QR')) {
    define('EP_POST_DINE_QR', 'ep-post-dine-qr');
}
if (! defined('EP_DINE_PRODUCTS')) {
    define('EP_DINE_PRODUCTS', 'ep-dine-products');
}
if (! defined('EP_DINE_GENERAL_INFO')) {
    define('EP_DINE_GENERAL_INFO', 'ep-dine-general-info');
}
if (! defined('EP_DINE_POST_ORDER')) {
    define('EP_DINE_POST_ORDER', 'ep-dine-post-order');
}
/* KIOSK CACHING TAGS */
if (! defined('FLUSH_KIOSK')) {
    define('FLUSH_KIOSK', 'flush-kiosk');
}
if (! defined('EP_DEVICE_PRODUCTS')) {
    define('EP_DEVICE_PRODUCTS', 'ep-device-products');
}
if (! defined('EP_CAT_PRODUCTS')) {
    define('EP_CAT_PRODUCTS', 'ep-cat-wise-products');
}
if (! defined('EP_UPSALE_PRODUCTS')) {
    define('EP_UPSALE_PRODUCTS', 'ep-upsale-products');
}
if (! defined('EP_KIOSK_POST_ORDER')) {
    define('EP_KIOSK_POST_ORDER', 'ep-kiosk-post-order');
}
if (! defined('EP_KIOSK_GENERAL_INFO')) {
    define('EP_KIOSK_GENERAL_INFO', 'ep-kiosk-general-info');
}
if (! defined('CATEGORY_TRANSLATION')) {
    define('CATEGORY_TRANSLATION', 'category-translation-');
}
if (! defined('PRODUCT_TRANSLATION')) {
    define('PRODUCT_TRANSLATION', 'product-translation-');
}
if (! defined('SUB_CATEGORY_TRANSLATION')) {
    define('SUB_CATEGORY_TRANSLATION', 'sub-category-translation-');
}
if (! defined('SUPPLEMENT_TRANSLATION')) {
    define('SUPPLEMENT_TRANSLATION', 'supplement-translation-');
}
/* REVIEW CACHING TAGS */
if (! defined('FLUSH_REVIEW')) {
    define('FLUSH_REVIEW', 'flush-review');
}
/* POS CACHING TAGS */
if (! defined('FLUSH_POS')) {
    define('FLUSH_POS', 'flush-pos');
}

/* sold products*/
if (! defined('EP_STATISTIC_SLOTS')) {
	define('EP_STATISTIC_SLOTS', 'ep-statistic-slots');
}
if (! defined('FLUSH_EATSTAT')) {
	define('FLUSH_EATSTAT', 'flush-eatstat');
}
if (! defined('STORE_RESERVATION')) {
	define('STORE_RESERVATION', 'store-reservation');
}
if (! defined('ORDER_ITEM')) {
	define('ORDER_ITEM', 'order-item-');
}