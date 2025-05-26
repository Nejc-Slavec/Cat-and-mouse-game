<?php

namespace ExtendBuilder;

require_once __DIR__ . '/filters.php';

function colibri_woocommerce_is_active() {
	return class_exists( 'woocommerce' );
}


function colibri_is_woocomerce_archive_page() {
	if(!colibri_woocommerce_is_active()) {
		return false;
	}
	$is_product_type_archive =  get_post_type() === 'product';
	return  (is_archive() && $is_product_type_archive ) || is_shop();
}

function woocommerce_is_enabled() {
    return function_exists('\is_shop');
}

function woocommerce_is_shop_archive_page() {
    return \is_shop() || \is_product_category() || \is_product_tag();
}
function is_woocommerce_shop_page($page_id = null) {
	if(!woocommerce_is_enabled()) {
		return false;
	}
	if($page_id) {
		$shop_id = \wc_get_page_id( 'shop' );
		return $page_id === $shop_id;
	} else {
		return \is_shop();
	}
}
function is_woocommerce_page($page_id = null) {
	if(!woocommerce_is_enabled()) {
		return false;
	}
	if($page_id) {
		$cart_id = \wc_get_page_id( 'cart' );
		$checkout_id = \wc_get_page_id('checkout');
		$account_id = \wc_get_page_id('myaccount');
		$woo_pages = [$cart_id, $checkout_id, $account_id];
		return in_array($page_id , $woo_pages);
	} else {
		return (is_cart() || is_checkout() || is_account_page());
	}
}

