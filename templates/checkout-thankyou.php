<?php
/**
 * Thank you page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/thankyou.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.7.0
 */

defined( 'ABSPATH' ) || exit;

global $wp;
$order_id = isset($wp->query_vars['order-received']) ? $wp->query_vars['order-received'] : 0;
$order = wc_get_order( $order_id );
?>
<div class="woocommerce-order">
	<?php if ( $order ) : ?>
		<?php if ( $order->has_status( 'failed' ) ) : ?>
			<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed"><?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' ); ?></p>
			<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed-actions">
				<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php esc_html_e( 'Pay', 'woocommerce' ); ?></a>
				<?php if ( is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button pay"><?php esc_html_e( 'My account', 'woocommerce' ); ?></a>
				<?php endif; ?>
			</p>
		<?php else : ?>
			<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received"><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Thank you. Your order has been received.', 'woocommerce' ), $order ); ?></p>
			<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">
				<li class="woocommerce-order-overview__order order">
					<?php esc_html_e( 'Order number:', 'woocommerce' ); ?>
					<strong><?php echo esc_html( $order->get_order_number() ); ?></strong>
				</li>
				<li class="woocommerce-order-overview__date date">
					<?php esc_html_e( 'Date:', 'woocommerce' ); ?>
					<strong><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></strong>
				</li>
				<li class="woocommerce-order-overview__total total">
					<?php esc_html_e( 'Total:', 'woocommerce' ); ?>
					<strong><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></strong>
				</li>
				<?php if ( $order->get_payment_method_title() ) : ?>
					<li class="woocommerce-order-overview__payment-method method">
						<?php esc_html_e( 'Payment method:', 'woocommerce' ); ?>
						<strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
					</li>
				<?php endif; ?>
			</ul>
		<?php endif; ?>

		<?php
		$grouper = new DropHub_WooHelper\Shipping_Grouper();
		$grouped_items = $grouper->group_order_items( $order->get_items() );

		foreach ($grouped_items as $group_key => $group) {
			if (empty($group['items'])) {
				continue;
			}

			// Add shipping group header
			?>
			<h2 class="shipping-group-header">
				<?php echo esc_html($group['shipping_class']); ?>
				<span class="shipping-method"><?php echo esc_html($group['shipping_method']); ?></span>
				<?php if (isset($group['delivery_time'])): ?>
					<span class="delivery-time">
						<?php printf(
							esc_html__('Delivery: %d-%d days', 'drophub-woohelper'),
							$group['delivery_time']['min'],
							$group['delivery_time']['max']
						); ?>
					</span>
				<?php endif; ?>
				<?php if (isset($group['prepaid'])): ?>
					<span class="prepaid-status <?php echo $group['prepaid'] ? 'prepaid' : 'not-prepaid'; ?>">
						<?php echo $group['prepaid'] ? 
							esc_html__('Prepaid Shipping', 'drophub-woohelper') : 
							esc_html__('Cash on Delivery', 'drophub-woohelper'); ?>
					</span>
				<?php endif; ?>
				<?php if (isset($group['rate'])): ?>
					<span class="shipping-rates">
						<?php 
						$total_items = 0;
						foreach ($group['items'] as $item) {
							$total_items += $item->get_quantity();
						}
						$shipping_cost = $group['rate'];
						if ($total_items > 1 && isset($group['extra_rate'])) {
							$shipping_cost += ($total_items - 1) * $group['extra_rate'];
						}
						printf(
							esc_html__('Shipping Cost: %s', 'drophub-woohelper'),
							wc_price($shipping_cost)
						);
						?>
					</span>
				<?php endif; ?>
			</h2>
			<table class="shop_table order_details">
				<thead>
					<tr>
						<th class="product-name"><?php esc_html_e( 'Product', 'woocommerce' ); ?></th>
						<th class="product-total"><?php esc_html_e( 'Total', 'woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ($group['items'] as $item_id => $item) {
						$_product = $item->get_product();

						if ($_product && $_product->exists() && $item->get_quantity() > 0) {
							?>
							<tr class="<?php echo esc_attr(apply_filters('woocommerce_order_item_class', 'order_item', $item, $order)); ?>">
								<td class="product-name">
									<?php echo wp_kses_post(apply_filters('woocommerce_order_item_name', $_product->get_name(), $item, false)) . '&nbsp;'; ?>
									<?php echo apply_filters('woocommerce_order_item_quantity_html', ' <strong class="product-quantity">' . sprintf('&times;&nbsp;%s', $item->get_quantity()) . '</strong>', $item); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<?php echo wc_display_item_meta($item); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</td>
								<td class="product-total">
									<?php echo $order->get_formatted_line_subtotal($item); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</td>
							</tr>
							<?php
						}
					}
					?>
				</tbody>
			</table>
			<?php
		}
		?>

		<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
		<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>
	<?php else : ?>
		<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received"><?php esc_html_e( 'Thank you. Your order has been received.', 'woocommerce' ); ?></p>
	<?php endif; ?>
</div>
