<?php

namespace Weboccult\EatcardCompanion\Helpers;

define('CACHING_TIME', 600); // In seconds

/* COMMON CACHING TAGS */

define('FLUSH_ALL', 'flush-all');
define('FLUSH_STORE_BY_ID', 'flush-store-by-id-');
define('FLUSH_STORE_BY_SLUG', 'flush-store-by-slug-');
define('FLUSH_TRANSLATION', 'flush-translation');
define('STORE_CHANGE_BY_ID', 'store-by-id-');
define('STORE_CHANGE_BY_SLUG', 'store-by-slug-');
define('TAKEAWAY_SETTING', 'takeaway-setting-');
define('MULTI_SAFE_PAY', 'multi-safe-pay');
define('STORE_SETTING', 'store-setting');
define('ORDERS', 'orders');

define('ORDER_WITH_ITEMS', 'order-with-items-');
define('SUB_ORDER_WITH_ITEMS', 'sub-order-with-items-');
define('ALL_STORE_RESERVATIONS', 'all-store-reservations');
define('STORE_RESERVATION_WITH_TABLE', 'store-reservation-with-table-');
define('GIFT_ORDER_WITH_QR', 'gift-order-with-qr-');
define('STORE_POS_SETTING', 'store-pos-setting');
define('DEVICE_PRINTERS', 'device-printers-');
define('ALL_STORES', 'all-stores');
define('STORE_OWNERS', 'store-owners');
define('STORE_MANAGERS', 'store-managers');
define('STORE_POS_EMPLOYEE', 'store-pos-employee');
define('STORES_PRINTERS', 'stores-printer-');
define('STORES_POS_IMAGES', 'stores-pos-images-');
define('ALL_CATEGORIES', 'all-categories');
define('ALL_PRODUCTS', 'all-products');

define('CONTENT_PAGES', 'content-pages');
define('CATEGORIES', 'categories-');
define('PRODUCTS', 'products-');
define('SUB_CATEGORIES', 'sub-categories-');
define('SUPPLEMENTS', 'supplements-');
define('PRE_ORDERS', 'pre-orders-');
define('PRODUCT_PRE_ORDERS', 'product-pre-orders-');
define('MOST_POPULAR_PRODUCTS', 'most-popular-products-');
define('UPSALE_CATEGORY', 'upsale-categories-');
define('CAT_PRODUCT_UPSALE', 'cat-product-upsale-');
define('PRODUCT_UPSALE', 'product-upsale-');
define('TABLES', 'tables');
define('TABLE_BY_QR', 'table-by-qr-');
define('TABLE_BY_ID', 'table-by-id-');
define('STORE_BY_QR', 'store-by-qr-QR_CODE');
define('STORE_BUTLER', 'store-butler-');
define('KIOSK_DEVICES', 'kiosk-devices');
define('KIOSK_BANNER_IMAGES', 'kiosk-banner-images-');
define('KIOSK_SLIDER_IMAGES', 'kiosk-slider-images-');
define('STORE_LANGUAGE', 'store-language-');

/* TAKEAWAY CACHING TAGS */

define('FLUSH_TAKEAWAY', 'flush-takeaway');
define('EP_TAKEAWAY_FORM', 'ep-takeaway-form');
define('EP_TIME_SLOTS', 'ep-time-slots');
define('EP_POST_ORDER', 'ep-post-order');
define('EP_GENERAL_INFO', 'ep-general-info');
define('EP_DELIVERY_RATE', 'ep-delivery-rate');

/* DINE IN CACHING TAGS */
define('FLUSH_DINE_IN', 'flush-dinein');
define('EP_POST_DINE_QR', 'ep-post-dine-qr');
define('EP_DINE_PRODUCTS', 'ep-dine-products');
define('EP_DINE_GENERAL_INFO', 'ep-dine-general-info');
define('EP_DINE_POST_ORDER', 'ep-dine-post-order');

/* KIOSK CACHING TAGS */
define('FLUSH_KIOSK', 'flush-kiosk');
define('EP_DEVICE_PRODUCTS', 'ep-device-products');
define('EP_CAT_PRODUCTS', 'ep-cat-wise-products');
define('EP_UPSALE_PRODUCTS', 'ep-upsale-products');
define('EP_KIOSK_POST_ORDER', 'ep-kiosk-post-order');
define('EP_KIOSK_GENERAL_INFO', 'ep-kiosk-general-info');
define('CATEGORY_TRANSLATION', 'category-translation-');
define('PRODUCT_TRANSLATION', 'product-translation-');
define('SUB_CATEGORY_TRANSLATION', 'sub-category-translation-');
define('SUPPLEMENT_TRANSLATION', 'supplement-translation-');

/* REVIEW CACHING TAGS */
define('FLUSH_REVIEW', 'flush-review');

/* POS CACHING TAGS */
define('FLUSH_POS', 'flush-pos');
