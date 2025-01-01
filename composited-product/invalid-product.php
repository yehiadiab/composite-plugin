<?php
/**
 * Composited Invalid Product template
 *
 * Override this template by copying it to 'yourtheme/woocommerce/composited-product/invalid-product.php'.
 *
 * On occasion, this template file may need to be updated and you (the theme developer) will need to copy the new files to your theme to maintain compatibility.
 * We try to do this as little as possible, but it does happen.
 * When this occurs the version of the template file will be bumped and the readme will list any important changes.
 *
 * @version 8.10.4
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?><div class="component_data">
<?php

	ob_start();

if ( $is_static ) {
	$html = __( 'This item cannot be purchased at the moment.', 'woocommerce-composite-products' );
} else {
	$link = '<a class="clear_component_options button" href="#" role="button">' . __( 'Clear selection', 'woocommerce-composite-products' ) . '</a>';
	$html = $link . __( 'The selected item cannot be purchased at the moment.', 'woocommerce-composite-products' );
}

	echo wp_kses_post( $html );

if ( ! empty( $note ) ) {
	echo '<span class="invalid_product_note">' . wp_kses_post( $note ) . '</span>';
}

if ( ! is_admin() && ! $is_block_editor_request ) {
	$notice = ob_get_clean();

	wc_print_notice( $notice, 'error' );
}

?>
</div>

