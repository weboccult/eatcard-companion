<?php

namespace Weboccult\EatcardCompanion\Helpers;

safeDefine('CACHING_TIME', 600); // In seconds

/* COMMON CACHING TAGS */

safeDefine('FLUSH_ALL', 'flush-all');
safeDefine('FLUSH_STORE_BY_ID', 'flush-store-by-id-');
safeDefine('FLUSH_STORE_BY_SLUG', 'flush-store-by-slug-');
safeDefine('FLUSH_TRANSLATION', 'flush-translation');
safeDefine('STORE_CHANGE_BY_ID', 'store-by-id-');
safeDefine('STORE_CHANGE_BY_SLUG', 'store-by-slug-');
safeDefine('TAKEAWAY_SETTING', 'takeaway-setting-');
safeDefine('MULTI_SAFE_PAY', 'multi-safe-pay');
safeDefine('STORE_SETTING', 'store-setting');
safeDefine('ORDERS', 'orders');

safeDefine('ORDER_WITH_ITEMS', 'order-with-items-');
safeDefine('SUB_ORDER_WITH_ITEMS', 'sub-order-with-items-');
safeDefine('ALL_STORE_RESERVATIONS', 'all-store-reservations');
safeDefine('STORE_RESERVATION_WITH_TABLE', 'store-reservation-with-table-');
safeDefine('GIFT_ORDER_WITH_QR', 'gift-order-with-qr-');
safeDefine('STORE_POS_SETTING', 'store-pos-setting');
safeDefine('DEVICE_PRINTERS', 'device-printers-');
safeDefine('ALL_STORES', 'all-stores');
safeDefine('STORE_OWNERS', 'store-owners');
safeDefine('STORE_MANAGERS', 'store-managers');
safeDefine('STORE_POS_EMPLOYEE', 'store-pos-employee');
safeDefine('STORES_PRINTERS', 'stores-printer-');
safeDefine('STORES_POS_IMAGES', 'stores-pos-images-');
safeDefine('ALL_CATEGORIES', 'all-categories');
safeDefine('ALL_PRODUCTS', 'all-products');

safeDefine('CONTENT_PAGES', 'content-pages');
safeDefine('CATEGORIES', 'categories-');
safeDefine('PRODUCTS', 'products-');
safeDefine('SUB_CATEGORIES', 'sub-categories-');
safeDefine('SUPPLEMENTS', 'supplements-');
safeDefine('PRE_ORDERS', 'pre-orders-');
safeDefine('PRODUCT_PRE_ORDERS', 'product-pre-orders-');
safeDefine('MOST_POPULAR_PRODUCTS', 'most-popular-products-');
safeDefine('UPSALE_CATEGORY', 'upsale-categories-');
safeDefine('CAT_PRODUCT_UPSALE', 'cat-product-upsale-');
safeDefine('PRODUCT_UPSALE', 'product-upsale-');
safeDefine('TABLES', 'tables');
safeDefine('TABLE_BY_QR', 'table-by-qr-');
safeDefine('TABLE_BY_ID', 'table-by-id-');
safeDefine('STORE_BY_QR', 'store-by-qr-QR_CODE');
safeDefine('STORE_BUTLER', 'store-butler-');
safeDefine('KIOSK_DEVICES', 'kiosk-devices');
safeDefine('KIOSK_BANNER_IMAGES', 'kiosk-banner-images-');
safeDefine('KIOSK_SLIDER_IMAGES', 'kiosk-slider-images-');
safeDefine('STORE_LANGUAGE', 'store-language-');

/* TAKEAWAY CACHING TAGS */

safeDefine('FLUSH_TAKEAWAY', 'flush-takeaway');
safeDefine('EP_TAKEAWAY_FORM', 'ep-takeaway-form');
safeDefine('EP_TIME_SLOTS', 'ep-time-slots');
safeDefine('EP_POST_ORDER', 'ep-post-order');
safeDefine('EP_GENERAL_INFO', 'ep-general-info');
safeDefine('EP_DELIVERY_RATE', 'ep-delivery-rate');

/* DINE IN CACHING TAGS */
safeDefine('FLUSH_DINE_IN', 'flush-dinein');
safeDefine('EP_POST_DINE_QR', 'ep-post-dine-qr');
safeDefine('EP_DINE_PRODUCTS', 'ep-dine-products');
safeDefine('EP_DINE_GENERAL_INFO', 'ep-dine-general-info');
safeDefine('EP_DINE_POST_ORDER', 'ep-dine-post-order');

/* KIOSK CACHING TAGS */
safeDefine('FLUSH_KIOSK', 'flush-kiosk');
safeDefine('EP_DEVICE_PRODUCTS', 'ep-device-products');
safeDefine('EP_CAT_PRODUCTS', 'ep-cat-wise-products');
safeDefine('EP_UPSALE_PRODUCTS', 'ep-upsale-products');
safeDefine('EP_KIOSK_POST_ORDER', 'ep-kiosk-post-order');
safeDefine('EP_KIOSK_GENERAL_INFO', 'ep-kiosk-general-info');
safeDefine('CATEGORY_TRANSLATION', 'category-translation-');
safeDefine('PRODUCT_TRANSLATION', 'product-translation-');
safeDefine('SUB_CATEGORY_TRANSLATION', 'sub-category-translation-');
safeDefine('SUPPLEMENT_TRANSLATION', 'supplement-translation-');

/* REVIEW CACHING TAGS */
safeDefine('FLUSH_REVIEW', 'flush-review');

/* POS CACHING TAGS */
safeDefine('FLUSH_POS', 'flush-pos');
