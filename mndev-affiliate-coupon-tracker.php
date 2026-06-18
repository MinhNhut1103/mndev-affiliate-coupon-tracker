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

	// Thêm trường nhập mã giới thiệu vào trang thanh toán
	add_action( 'woocommerce_after_order_notes', 'mndev_affiliate_add_referral_field_checkout' );
	
	// Xác thực mã giới thiệu khi khách hàng bấm thanh toán
	add_action( 'woocommerce_checkout_process', 'mndev_affiliate_validate_referral_field' );
	
	// Lưu ID của CTV vào metadata của đơn hàng
	add_action( 'woocommerce_checkout_create_order', 'mndev_affiliate_save_referral_field_to_order', 10, 2 );

	// Hook vào AffiliateWP để ghi nhận ID của CTV
	add_filter( 'affwp_get_referring_affiliate_id', 'mndev_affiliate_coupon_tracker_get_affiliate_id', 10, 3 );
	
	// Cực kỳ quan trọng: Báo cho AffiliateWP biết là có CTV giới thiệu (dù không có cookie)
	add_filter( 'affwp_was_referred', 'mndev_affiliate_coupon_tracker_was_referred', 10, 2 );
	
	// Enqueue JS
	add_action( 'wp_enqueue_scripts', 'mndev_affiliate_enqueue_scripts' );
	
	// AJAX handlers
	add_action( 'wp_ajax_mndev_validate_affiliate', 'mndev_affiliate_ajax_validate' );
	add_action( 'wp_ajax_nopriv_mndev_validate_affiliate', 'mndev_affiliate_ajax_validate' );
}
add_action( 'plugins_loaded', 'mndev_affiliate_coupon_tracker_init' );

/**
 * Đánh lừa AffiliateWP rằng khách hàng này đã được giới thiệu (was_referred = true) 
 * nếu họ có nhập mã giới thiệu hợp lệ ở Checkout.
 */
function mndev_affiliate_coupon_tracker_was_referred( $was_referred, $tracking ) {
	if ( $was_referred ) {
		return $was_referred;
	}

	// Nếu đang gửi form checkout và có nhập mã
	if ( isset( $_POST['mndev_affiliate_code'] ) && ! empty( $_POST['mndev_affiliate_code'] ) ) {
		$code = sanitize_text_field( $_POST['mndev_affiliate_code'] );
		$aff = mndev_affiliate_get_by_code( $code );
		
		if ( $aff && affiliate_wp()->tracking->is_valid_affiliate( $aff->affiliate_id ) ) {
			return true;
		}
	}

	return $was_referred;
}

/**
 * Enqueue JS script
 */
function mndev_affiliate_enqueue_scripts() {
	if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_wc_endpoint_url( 'order-pay' ) && ! is_wc_endpoint_url( 'order-received' ) ) {
		wp_enqueue_script( 'mndev-affiliate-checkout', plugin_dir_url( __FILE__ ) . 'assets/checkout.js', array('jquery'), '1.0.0', true );
		wp_localize_script( 'mndev-affiliate-checkout', 'mndev_affiliate_ajax', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'mndev_validate_affiliate_nonce' )
		));
	}
}

/**
 * AJAX Validate Affiliate
 */
function mndev_affiliate_ajax_validate() {
	check_ajax_referer( 'mndev_validate_affiliate_nonce', 'nonce' );
	
	$code = isset( $_POST['code'] ) ? sanitize_text_field( $_POST['code'] ) : '';
	$aff = mndev_affiliate_get_by_code( $code );
	
	if ( $aff && affiliate_wp()->tracking->is_valid_affiliate( $aff->affiliate_id ) ) {
		// Thiết lập cookie ngay lập tức qua AJAX
		affiliate_wp()->tracking->set_affiliate_id( $aff->affiliate_id );
		wp_send_json_success();
	} else {
		wp_send_json_error();
	}
}

/**
 * Hiển thị khung nhập mã giới thiệu trên trang thanh toán (Checkout)
 */
function mndev_affiliate_add_referral_field_checkout( $checkout ) {
	echo '<div id="mndev_affiliate_referral_field"><h3>Mã giới thiệu Cộng tác viên</h3>';
	
	woocommerce_form_field( 'mndev_affiliate_code', array(
		'type'          => 'text',
		'class'         => array('my-field-class form-row-wide'),
		'label'         => __('Nhập mã người giới thiệu'),
		'placeholder'   => __('Ví dụ: nhut1103'),
	), $checkout->get_value( 'mndev_affiliate_code' ) );
	
	echo '</div>';
}

/**
 * Tìm Affiliate dựa trên Username duy nhất
 */
function mndev_affiliate_get_by_code( $code ) {
	$code = trim( $code );
	if ( empty( $code ) ) {
		return false;
	}

	// Chỉ tìm theo Username (user_login)
	$user = get_user_by( 'login', $code );
	if ( ! $user ) {
		return false;
	}

	$aff_id = affiliate_wp()->affiliates->get_column_by( 'affiliate_id', 'user_id', $user->ID );
	if ( ! $aff_id ) {
		return false;
	}

	return affwp_get_affiliate( $aff_id );
}

/**
 * Kiểm tra mã giới thiệu có hợp lệ không và set Cookie
 */
function mndev_affiliate_validate_referral_field() {
	if ( ! empty( $_POST['mndev_affiliate_code'] ) ) {
		$code = sanitize_text_field( $_POST['mndev_affiliate_code'] );
		$aff = mndev_affiliate_get_by_code( $code );
		
		if ( ! $aff || ! affiliate_wp()->tracking->is_valid_affiliate( $aff->affiliate_id ) ) {
			wc_add_notice( __( 'Mã giới thiệu không hợp lệ. Vui lòng kiểm tra lại hoặc để trống nếu không có mã.' ), 'error' );
		} else {
			// Thiết lập cookie nếu nhấn Đặt hàng luôn
			affiliate_wp()->tracking->set_affiliate_id( $aff->affiliate_id );
		}
	}
}

/**
 * Lưu Affiliate ID vào meta của đơn hàng nếu mã hợp lệ
 */
function mndev_affiliate_save_referral_field_to_order( $order, $data ) {
	if ( ! empty( $_POST['mndev_affiliate_code'] ) ) {
		$code = sanitize_text_field( $_POST['mndev_affiliate_code'] );
		$aff = mndev_affiliate_get_by_code( $code );
		
		if ( $aff && affiliate_wp()->tracking->is_valid_affiliate( $aff->affiliate_id ) ) {
			$order->update_meta_data( 'mndev_referring_affiliate_id', $aff->affiliate_id );
		}
	}
}

/**
 * Cung cấp Affiliate ID cho AffiliateWP khi tạo Referral
 */
function mndev_affiliate_coupon_tracker_get_affiliate_id( $affiliate_id, $reference, $context ) {
	// Chỉ xử lý trong ngữ cảnh WooCommerce
	if ( 'woocommerce' !== $context || empty( $reference ) ) {
		return $affiliate_id;
	}

	$order = wc_get_order( $reference );
	if ( $order ) {
		$meta_aff_id = $order->get_meta( 'mndev_referring_affiliate_id' );
		if ( ! empty( $meta_aff_id ) ) {
			return $meta_aff_id; // Ghi nhận cho CTV này
		}
	}

	return $affiliate_id;
}
