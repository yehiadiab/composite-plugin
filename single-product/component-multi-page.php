<?php
/**
 * Multi-Page Component template
 *
 * Override this template by copying it to 'yourtheme/woocommerce/single-product/component-multi-page.php'.
 *
 * On occasion, this template file may need to be updated and you (the theme developer) will need to copy the new files to your theme to maintain compatibility.
 * We try to do this as little as possible, but it does happen.
 * When this occurs the version of the template file will be bumped and the readme will list any important changes.
 *
 * @version 8.8.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?><div id="component_<?php echo esc_attr( $component_id ); ?>" class="<?php echo esc_attr( implode( ' ', $component_classes ) ); ?>" data-nav_title="<?php echo esc_attr( $component->get_title() ); ?>" data-item_id="<?php echo esc_attr( $component_id ); ?>" style="display:none;">

	<div class="component_title_wrapper">
	<?php

		wc_get_template(
			'single-product/component-title.php',
			array(
				'step'       => $step,
				'title'      => $component->get_title(),
				'is_toggled' => false,
				'is_open'    => false,
			),
			'',
			WC_CP()->plugin_path() . '/templates/'
		);

		?>
	</div>

	<div id="component_<?php echo esc_attr( $component_id ); ?>_inner" class="component_inner">
		<div class="component_description_wrapper">
		<?php

		if ( $component->get_description() !== '' ) {
			wc_get_template(
				'single-product/component-description.php',
				array(
					'description' => $component->get_description(),
				),
				'',
				WC_CP()->plugin_path() . '/templates/'
			);
		}

		?>
		</div>
		<div class="component_selections">
		<?php

			/**
			 * Action 'woocommerce_composite_component_selections_paged'.
			 *
			 * @param  string                $component_id
			 * @param  WC_Product_Composite  $product
			 *
			 * @hooked wc_cp_component_options_scroll_target_paged_top - -20
			 * @hooked wc_cp_component_options_message_paged_top       - -10
			 * @hooked wc_cp_component_selection_paged_top             -   0
			 * @hooked wc_cp_component_options_sorting                 -  10
			 * @hooked wc_cp_component_options_filtering               -  20
			 * @hooked wc_cp_component_options_title                   -  30
			 * @hooked wc_cp_component_options_pagination_top          -  39
			 * @hooked wc_cp_component_options                         -  40
			 * @hooked wc_cp_component_options_pagination_bottom       -  41
			 * @hooked wc_cp_component_selection_paged_bottom          -  50
			 * @hooked wc_cp_component_selection_message_paged_bottom  -  60
			 */
			do_action( 'woocommerce_composite_component_selections_paged', $component_id, $product );

		?>
		</div>
	</div>
</div>
