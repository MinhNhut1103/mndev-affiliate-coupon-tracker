# Mndev Affiliate Coupon Tracker

Một Add-on mở rộng cho hệ thống AffiliateWP & WooCommerce, cho phép khách hàng tự điền **Mã giới thiệu (Referral Code)** của Cộng tác viên ngay tại trang Thanh toán (Checkout) thay vì phải nhúng vào ô Mã giảm giá (Coupon). 

Đặc biệt tối ưu khi kết hợp cùng plugin [Mndev AffiliateWP Dynamic Commission Rates](https://github.com/MinhNhut1103/mndev-affwp-dynamic-rates).

## 🚀 Tính năng nổi bật

- **Tách biệt Mã giới thiệu và Mã giảm giá:** Tạo riêng một ô nhập liệu `Mã giới thiệu Cộng tác viên` trên trang thanh toán WooCommerce, không can thiệp vào mã giảm giá của hệ thống để dữ liệu kế toán luôn sạch sẽ.
- **Hỗ trợ đa dạng mã định danh:** Khách hàng có thể nhập **Username**, **Affiliate ID**, hoặc thậm chí là **User ID** của Cộng tác viên. Hệ thống tự động nhận diện và gán đúng.
- **Tự động bắt lỗi:** Hiện thông báo nếu người dùng nhập sai mã giới thiệu, giúp hạn chế đơn hàng rác không rõ nguồn.
- **Bắt tay mượt mà với AffiliateWP:** Gán trực tiếp ID của CTV vào siêu dữ liệu đơn hàng (Order Meta) và tự động ghi nhận hoa hồng khi đơn hàng hoàn tất.

## 🛠 Yêu cầu hệ thống (Dependencies)

Plugin này được thiết kế như một mắt xích phụ thuộc nên bắt buộc phải có:
1. **[AffiliateWP](https://affiliatewp.com/):** Plugin lõi quản lý hệ thống Affiliate.

*(Plugin sẽ tự động tắt và hiển thị thông báo lỗi nếu bạn chưa bật plugin này).*

> **💡 Khuyên dùng:** Để đạt hiệu quả tối đa trong việc chia % hoa hồng linh hoạt dựa trên tổng giá trị đơn hàng, bạn nên kết hợp sử dụng cùng plugin [Mndev AffiliateWP Dynamic Commission Rates](https://github.com/MinhNhut1103/mndev-affwp-dynamic-rates).

## 📥 Hướng dẫn cài đặt

1. Tải toàn bộ mã nguồn về và đưa vào thư mục `wp-content/plugins/mndev-affiliate-coupon-tracker`.
2. Truy cập vào trang Quản trị WordPress `wp-admin` -> **Cài đặt (Plugins)**.
3. Tìm **Mndev Affiliate Coupon Tracker** và nhấn **Kích hoạt**.
4. Trải nghiệm ngay trên trang Thanh toán của WooCommerce! Không cần cài đặt gì thêm vì mọi thứ diễn ra hoàn toàn tự động.

## 👥 Tác giả

- **Tác giả:** [MinhNhut1103](https://github.com/MinhNhut1103)
- **Giấy phép:** GPL-2.0+
