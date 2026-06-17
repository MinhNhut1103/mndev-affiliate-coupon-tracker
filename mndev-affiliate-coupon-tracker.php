<?php
/**
 * Plugin Name: Mndev Affiliate Coupon Tracker
 * Plugin URI: https://github.com/MinhNhut1103/mndev-affiliate-coupon-tracker
 * Description: Tự động ghi nhận hoa hồng cho Cộng tác viên khi khách hàng nhập Username hoặc ID của họ vào ô Mã ưu đãi (Coupon) trong WooCommerce. Yêu cầu cài đặt plugin Mndev AffiliateWP Dynamic Commission Rates.
 * Version: 1.0.0
 * Author: MinhNhut1103
 * Author URI: https://github.com/MinhNhut1103
 * Text Domain: mndev-affiliate-coupon-tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Kiểm tra xem các plugin bắt buộc đã được cài đặt và kích hoạt chưa
 */
function mndev_affiliate_coupon_tracker_check_dependencies() {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$missing_dependencies = array();

	if ( ! class_exists( 'Affiliate_WP' ) ) {
		$missing_dependencies[] = 'AffiliateWP';
	}

	if ( ! is_plugin_active( 'mndev-affwp-dynamic-rates/mndev-affwp-dynamic-rates.php' ) ) {
		$missing_dependencies[] = 'Mndev AffiliateWP Dynamic Commission Rates';
	}

	if ( ! empty( $missing_dependencies ) ) {
		add_action( 'admin_notices', function() use ( $missing_dependencies ) {
			$deps = implode( ', ', $missing_dependencies );
			echo '<div class="error"><p><strong>Mndev Affiliate Coupon Tracker:</strong> Plugin không thể hoạt động vì thiếu các plugin bắt buộc: ' . esc_html( $deps ) . '. Vui lòng cài đặt và kích hoạt chúng.</p></div>';
		} );
		return false;
	}

	return true;
}

/**
 * Khởi tạo plugin
 */
function mndev_affiliate_coupon_tracker_init() {
	if ( ! mndev_affiliate_coupon_tracker_check_dependencies() ) {
		return;
	}

	// Hook vào WooCommerce để chặn coupon và tạo mã ảo
	add_filter( 'woocommerce_get_shop_coupon_data', 'mndev_affiliate_coupon_tracker_virtual_coupon', 10, 2 );

	// Hook vào AffiliateWP để ghi nhận ID của CTV khi dùng mã
	add_filter( 'affwp_get_referring_affiliate_id', 'mndev_affiliate_coupon_tracker_get_affiliate_id', 10, 3 );
}
add_action( 'plugins_loaded', 'mndev_affiliate_coupon_tracker_init' );

/**
 * Tạo mã giảm giá ảo (Virtual Coupon) nếu nhập đúng ID/Username CTV
 */
function mndev_affiliate_coupon_tracker_virtual_coupon( $data, $code ) {
	// Nếu mã coupon đã tồn tại trong database của WooCommerce thì giữ nguyên
	if ( ! empty( $data ) ) {
		return $data;
	}

	// Tìm xem mã code nhập vào có khớp với username hoặc ID của CTV nào không
	$affiliate = affwp_get_affiliate( $code );

	if ( $affiliate && affiliate_wp()->tracking->is_valid_affiliate( $affiliate->affiliate_id ) ) {
		// Trả về dữ liệu của một coupon ảo để WooCommerce áp dụng thành công
		return array(
			'id'                     => 0,
			'amount'                 => 0, // Không giảm giá cho khách hàng
			'discount_type'          => 'percent',
			'usage_limit'            => '',
			'usage_limit_per_user'   => '',
			'limit_usage_to_x_items' => '',
			'usage_count'            => '',
			'expiry_date'            => '',
			'free_shipping'          => false,
			'individual_use'         => false,
		);
	}

	return $data;
}

/**
 * Ghi nhận Affiliate ID khi khách hàng thanh toán dùng Coupon ảo
 */
function mndev_affiliate_coupon_tracker_get_affiliate_id( $affiliate_id, $reference, $context ) {
	// Chỉ xử lý trong ngữ cảnh WooCommerce
	if ( 'woocommerce' !== $context ) {
		return $affiliate_id;
	}

	$coupons = array();

	// Lấy danh sách coupons từ giỏ hàng hoặc đơn hàng
	if ( empty( $reference ) ) {
		if ( function_exists( 'WC' ) && isset( WC()->cart ) ) {
			$coupons = WC()->cart->get_applied_coupons();
		}
	} else {
		$order = wc_get_order( $reference );
		if ( $order ) {
			$coupons = $order->get_coupon_codes();
		}
	}

	// Kiểm tra xem trong các mã coupon được dùng, có mã nào là của CTV không
	if ( ! empty( $coupons ) ) {
		foreach ( $coupons as $code ) {
			$aff = affwp_get_affiliate( $code );
			if ( $aff && affiliate_wp()->tracking->is_valid_affiliate( $aff->affiliate_id ) ) {
				return $aff->affiliate_id; // Bắt buộc ghi nhận cho CTV này
			}
		}
	}

	return $affiliate_id;
}
