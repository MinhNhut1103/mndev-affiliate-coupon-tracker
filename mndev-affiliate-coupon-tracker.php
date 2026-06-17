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

	// Đăng ký menu cài đặt
	add_action( 'admin_menu', 'mndev_affiliate_coupon_tracker_menu' );

	// Hook vào WooCommerce để chặn coupon và tạo mã ảo
	add_filter( 'woocommerce_get_shop_coupon_data', 'mndev_affiliate_coupon_tracker_virtual_coupon', 10, 2 );

	// Hook vào AffiliateWP để ghi nhận ID của CTV khi dùng mã
	add_filter( 'affwp_get_referring_affiliate_id', 'mndev_affiliate_coupon_tracker_get_affiliate_id', 10, 3 );
}
add_action( 'plugins_loaded', 'mndev_affiliate_coupon_tracker_init' );

/**
 * Thêm menu cài đặt (Submenu của Hoa hồng động hoặc menu độc lập)
 */
function mndev_affiliate_coupon_tracker_menu() {
	add_submenu_page(
		'mndev-affwp-dynamic-rates',
		'Cài đặt Mã Ưu đãi CTV',
		'Mã ưu đãi CTV',
		'manage_options',
		'mndev-affiliate-coupon-tracker',
		'mndev_affiliate_coupon_tracker_page'
	);
}

/**
 * Giao diện cài đặt
 */
function mndev_affiliate_coupon_tracker_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( isset( $_POST['mndev_affiliate_coupon_tracker_nonce'] ) && wp_verify_nonce( $_POST['mndev_affiliate_coupon_tracker_nonce'], 'mndev_save_coupon_settings' ) ) {
		update_option( 'mndev_affwp_coupon_discount_type', sanitize_text_field( $_POST['discount_type'] ) );
		update_option( 'mndev_affwp_coupon_discount_amount', (float) sanitize_text_field( $_POST['discount_amount'] ) );
		echo '<div class="notice notice-success is-dismissible"><p>Đã lưu cấu hình mã ưu đãi.</p></div>';
	}

	$discount_type = get_option( 'mndev_affwp_coupon_discount_type', 'percent' );
	$discount_amount = get_option( 'mndev_affwp_coupon_discount_amount', 0 );

	?>
	<div class="wrap">
		<h1>Cài đặt Mã ưu đãi Cộng tác viên</h1>
		<p>Khách hàng có thể nhập <strong>Username</strong> hoặc <strong>ID</strong> của Cộng tác viên vào ô Mã ưu đãi (Coupon) trong WooCommerce. Khi đó, hệ thống sẽ tự động tạo một mã giảm giá ảo và ghi nhận hoa hồng cho CTV đó.</p>
		
		<form method="post" action="">
			<?php wp_nonce_field( 'mndev_save_coupon_settings', 'mndev_affiliate_coupon_tracker_nonce' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="discount_type">Loại chiết khấu cho khách hàng</label></th>
					<td>
						<select name="discount_type" id="discount_type">
							<option value="percent" <?php selected( $discount_type, 'percent' ); ?>>Giảm giá theo phần trăm (%)</option>
							<option value="fixed_cart" <?php selected( $discount_type, 'fixed_cart' ); ?>>Giảm giá cố định giỏ hàng (VNĐ)</option>
							<option value="fixed_product" <?php selected( $discount_type, 'fixed_product' ); ?>>Giảm giá cố định sản phẩm (VNĐ)</option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="discount_amount">Mức chiết khấu</label></th>
					<td>
						<input type="number" step="0.01" name="discount_amount" id="discount_amount" value="<?php echo esc_attr( $discount_amount ); ?>" class="regular-text" />
						<p class="description">Để <strong>0</strong> nếu bạn chỉ muốn ghi nhận hoa hồng cho CTV mà KHÔNG giảm tiền cho khách hàng.</p>
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" name="submit" id="submit" class="button button-primary" value="Lưu thay đổi" />
			</p>
		</form>
	</div>
	<?php
}

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
		$discount_type = get_option( 'mndev_affwp_coupon_discount_type', 'percent' );
		$discount_amount = get_option( 'mndev_affwp_coupon_discount_amount', 0 );

		// Trả về dữ liệu của một coupon ảo để WooCommerce áp dụng thành công
		return array(
			'id'                     => 0,
			'amount'                 => $discount_amount,
			'discount_type'          => $discount_type,
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
