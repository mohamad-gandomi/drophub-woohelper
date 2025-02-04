<?php
/**
 * Review order table
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/review-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 5.2.0
 */

defined( 'ABSPATH' ) || exit;
?>
<table class="shop_table woocommerce-checkout-review-order-table">
	<thead>
		<tr>
			<th class="product-name"><?php esc_html_e( 'Product', 'woocommerce' ); ?></th>
			<th class="product-total"><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
		do_action( 'woocommerce_review_order_before_cart_contents' );

		$grouper = new DropHub_WooHelper\Shipping_Grouper();
		$grouped_items = $grouper->group_cart_items();

		foreach ($grouped_items as $group_key => $group) {
			if (empty($group['items'])) {
				continue;
			}

			// Add shipping group header
			?>
			<tr class="shipping-group-header">
				<td colspan="2">
					<div class="shipping-details">
						<!-- <strong><?php //echo esc_html($group['shipping_class']); ?></strong> -->
						<?php if (isset($group['shipping_method']) && $group['shipping_method'] == __('Standard Shipping', 'drophub-woohelper') && get_option('drophub_ignore_shipping', 'no') === 'no'): ?>
						<?php
						$packages = WC()->shipping->get_packages();

						foreach ($packages as $i => $package) {
							$available_methods = $package['rates'];
							$chosen_method = WC()->session->get('chosen_shipping_methods')[$i] ?? '';

							if (!empty($available_methods)) {
								echo '<div class="shipping-method-selection">';
								echo '<label for="shipping_method_' . esc_attr($i) . '">' . esc_html__('Select Shipping Method:', 'woocommerce') . '</label>';
								echo '<select name="shipping_method[' . esc_attr($i) . ']" id="shipping_method_' . esc_attr($i) . '" class="shipping-method-dropdown">';
								foreach ($available_methods as $method_id => $method) {
									echo '<option value="' . esc_attr($method_id) . '" ' . selected($chosen_method, $method_id, false) . '>';
									echo esc_html($method->get_label()) . ' - ' . wc_price($method->get_cost());
									echo '</option>';
								}
								echo '</select>';
								echo '</div>';
							} else {
								echo '<p>' . esc_html__('No shipping methods available for this package.', 'woocommerce') . '</p>';
							}
						}
						?>
						<?php else: ?>
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
							<?php if ( get_option('drophub_ignore_shipping', 'no') === 'no' && isset($group['prepaid'])): ?>
								<span class="prepaid-status <?php echo $group['prepaid'] ? 'prepaid' : 'not-prepaid'; ?>">
									<?php echo $group['prepaid'] ? 
										'' : 
										esc_html__('Cash on Delivery', 'drophub-woohelper'); ?>
								</span>
							<?php endif; ?>
							<?php if ( get_option('drophub_ignore_shipping', 'no') === 'no' && isset($group['rate'])): ?>
								<span class="shipping-rates">
									<?php 
									$total_items = 0;
									foreach ($group['items'] as $item) {
										$total_items += $item['quantity'];
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
						<?php endif; ?>
					</div>
				</td>
			</tr>
			<?php

			foreach ($group['items'] as $cart_item_key => $cart_item) {
				$_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);

				if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key)) {
					?>
					<tr class="<?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); ?>">
						<td class="product-name">
							<?php echo wp_kses_post(apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key)) . '&nbsp;'; ?>
							<?php echo apply_filters('woocommerce_checkout_cart_item_quantity', ' <strong class="product-quantity">' . sprintf('&times;&nbsp;%s', $cart_item['quantity']) . '</strong>', $cart_item, $cart_item_key); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php echo wc_get_formatted_cart_item_data($cart_item); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</td>
						<td class="product-total">
							<?php echo apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</td>
					</tr>
					<?php
				}
			}
		}

		do_action( 'woocommerce_review_order_after_cart_contents' );
		?>
	</tbody>
	<tfoot>

		<tr class="cart-subtotal">
			<th><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></th>
			<td><?php wc_cart_totals_subtotal_html(); ?></td>
		</tr>

		<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
			<tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
				<th><?php wc_cart_totals_coupon_label( $coupon ); ?></th>
				<td><?php wc_cart_totals_coupon_html( $coupon ); ?></td>
			</tr>
		<?php endforeach; ?>

		<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>

			<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>

			<?php wc_cart_totals_shipping_html(); ?>

			<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>

		<?php endif; ?>

		<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
			<tr class="fee">
				<th><?php echo esc_html( $fee->name ); ?></th>
				<td><?php wc_cart_totals_fee_html( $fee ); ?></td>
			</tr>
		<?php endforeach; ?>

		<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
			<?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>
				<?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited ?>
					<tr class="tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
						<th><?php echo esc_html( $tax->label ); ?></th>
						<td><?php echo wp_kses_post( $tax->formatted_amount ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr class="tax-total">
					<th><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></th>
					<td><?php wc_cart_totals_taxes_total_html(); ?></td>
				</tr>
			<?php endif; ?>
		<?php endif; ?>

		<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>

		<tr class="order-total">
			<th><?php esc_html_e( 'Total', 'woocommerce' ); ?></th>
			<td><?php wc_cart_totals_order_total_html(); ?></td>
		</tr>

		<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>

	</tfoot>
</table>
