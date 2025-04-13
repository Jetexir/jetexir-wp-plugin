<?php

namespace WooAssistant\App\Product;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Addons\Addon;
use WooAssistant\Helper\Param;
use WooAssistant\Helper\PostMeta;
use WooAssistant\Interfaces\AddonInterface;
use WooAssistant\Settings\Settings;

class ProductFAQ extends Addon implements AddonInterface {
	public string $addonID = 'product-faq';
	public string $currentTab = 'product';
	private const sectionID = 'faq';
	private const maxProductFAQs = 10;

	public function initAction(): void {
		// Admin
		add_filter( 'woocommerce_product_data_tabs', [ $this, 'adminProductTab' ] );
		add_filter( 'woocommerce_product_data_panels', [ $this, 'adminProductSettings' ] );
		add_action( 'woocommerce_process_product_meta', [ $this, 'adminProductSaveMeta' ] );

		// Front
		add_filter( 'woocommerce_product_tabs', [ $this, 'productTab' ], 9999 );
	}

	public function productTabContent(): void {
		$productID          = get_the_ID();
		$globalFAQsPosition = Settings::get( 'product_faq_global_position', 'before' );
		$globalFAQs         = Settings::get( 'product_faq', [] );
		$buttonIcon         = Settings::get( 'product_faq_button_icon', 'chevron' );
		$productFAQs        = PostMeta::get( $productID, WOOASSISTANT_INPUT_PREFIX . 'product_faq' );
		$productFAQs        = is_array( $productFAQs ) ? $productFAQs : [];

		if ( $globalFAQsPosition === 'before' ) {
			$FAQs = array_merge( $globalFAQs, $productFAQs );
		} else {
			$FAQs = array_merge( $productFAQs, $globalFAQs );
		}

		$title = apply_filters( 'woo_assistant_product_faq_tab_title', __( 'FAQs', 'woo-assistant' ) );

		echo '<h2>' . $title . '</h2>';

		if ( ! empty( $FAQs ) ) {
			echo '<div class="wa-faqs-wrap">';
			foreach ( $FAQs as $faq ) {
				if ( empty( $faq['question'] ) || empty( $faq['answer'] ) ) {
					continue;
				}

				echo '<div class="wa-faq-item">';
				echo '<button class="wa-faq-question" type="button">' . $faq['question'] . $this->getIcon( $buttonIcon ) . '</button>';
				echo '<div class="wa-faq-answer">' . $faq['answer'] . '</div>';
				echo '</div>';
			}
			echo '</div>';
		}
	}

	private function getIcon( $icon ): string {
		if ( $icon === 'chevron' ) {
			return '<i class="wa-icon-chevron-down"></i>';

		} elseif ( $icon === 'chevrons' ) {
			return '<i class="wa-icon-chevrons-down"></i>';

		} elseif ( $icon === 'arrow' ) {
			return '<i class="wa-icon-arrow-down"></i>';

		} elseif ( $icon === 'arrow-circle' ) {
			return '<i class="wa-icon-circle-down"></i>';

		} elseif ( $icon === 'plus' ) {
			return '<i class="wa-icon-plus"></i>';
		}

		return '';
	}

	public function productTab( $tabs ) {
		$enable = PostMeta::get( wc_get_product()->get_id(), WOOASSISTANT_INPUT_PREFIX . 'product_faq_enable' );
		$enable = $enable === '' ? 1 : (int) $enable;
		if ( $enable === 0 ) {
			return $tabs;
		}

		$tabs['docs'] = array(
			'title'    => apply_filters( 'woo_assistant_product_faq_tab_title', __( 'FAQs', 'woo-assistant' ) ),
			'priority' => 50,
			'callback' => [ $this, 'productTabContent' ],
		);

		return $tabs;
	}

	public function adminProductSaveMeta( $productID ): void {
		$enable = (int) wc_string_to_bool( Param::post( WOOASSISTANT_INPUT_PREFIX . 'product_faq_enable' ) );
		PostMeta::update( $productID, WOOASSISTANT_INPUT_PREFIX . 'product_faq_enable', $enable );

		$FAQs = Param::post( WOOASSISTANT_INPUT_PREFIX . 'product_faq' );
		if ( is_array( $FAQs ) ) {
			foreach ( $FAQs as $index => $faq ) {
				$faq = array_map( 'trim', $faq );
				if ( implode( $faq ) === '' ) {
					unset( $FAQs[ $index ] );
				}
			}
			$FAQs = array_values( $FAQs );

			PostMeta::update( $productID, WOOASSISTANT_INPUT_PREFIX . 'product_faq', $FAQs );
		}
	}

	public function adminProductTab( $tabs ) {
		$tabs[ WOOASSISTANT_PLUGIN_KEY . '_faq_control' ] = array(
			'label'  => __( 'FAQs', 'woo-assistant' ),
			'target' => WOOASSISTANT_PLUGIN_KEY . '_faq_control'
		);

		return $tabs;
	}

	public function adminProductSettings(): void {
		$productID = get_the_ID();
		$enable    = PostMeta::get( $productID, WOOASSISTANT_INPUT_PREFIX . 'product_faq_enable' );
		$enable    = $enable === '' ? 1 : (int) $enable;
		$FAQs      = PostMeta::get( $productID, WOOASSISTANT_INPUT_PREFIX . 'product_faq' );
		$FAQs      = is_array( $FAQs ) ? $FAQs : [];
		?>
        <div id="<?php echo WOOASSISTANT_PLUGIN_KEY . '_faq_control' ?>" class="panel woocommerce_options_panel"
             style="display: none">
            <div class="options_group">
				<?php
				woocommerce_wp_checkbox( array(
					'id'      => WOOASSISTANT_INPUT_PREFIX . 'product_faq_enable',
					'name'    => WOOASSISTANT_INPUT_PREFIX . 'product_faq_enable',
					'label'   => __( 'Enable Product FAQ', 'woo-assistant' ),
					'value'   => $enable === 1 ? 1 : 0,
					'cbvalue' => 1
				) );

				echo '<p><strong>' . __( 'Product FAQs', 'woo-assistant' ) . '</strong></p>';

				for ( $i = 1; $i <= self::maxProductFAQs; $i ++ ) {
					$index = $i - 1;
					woocommerce_wp_text_input( array(
						'id'          => WOOASSISTANT_INPUT_PREFIX . 'product_faq_question_' . $index,
						'name'        => WOOASSISTANT_INPUT_PREFIX . 'product_faq[' . $index . '][question]',
						'label'       => __( 'Question', 'woo-assistant' ) . ' ' . $i,
						'type'        => 'text',
						'placeholder' => __( 'Question', 'woo-assistant' ),
						'value'       => $FAQs[ $index ]['question'] ?? '',
					) );

					woocommerce_wp_textarea_input( array(
						'id'          => WOOASSISTANT_INPUT_PREFIX . 'product_faq_answer_' . $index,
						'name'        => WOOASSISTANT_INPUT_PREFIX . 'product_faq[' . $index . '][answer]',
						'label'       => __( 'Answer', 'woo-assistant' ) . ' ' . $i,
						'rows'        => 3,
						'placeholder' => __( 'Answer', 'woo-assistant' ),
						'value'       => $FAQs[ $index ]['answer'] ?? '',
					) );

					if ( $i !== self::maxProductFAQs ) {
						echo '<hr>';
					}
				}
				?>
            </div>
        </div>
		<?php
	}

	public function addSectionSettings( $sections ) {
		$sections[ self::sectionID ] = array(
			'title'    => __( 'FAQ', 'woo-assistant' ),
			'desc'     => __( 'Product frequently asked questions', 'woo-assistant' ),
			'settings' => array(
				'product_faq_start_grid_1'    => array(
					'id'    => 'product_faq_start_grid_1',
					'title' => __( 'Frequently asked questions', 'woo-assistant' ),
					'type'  => 'startgrid',
				),
				'product_faq_global_position' => array(
					'id'       => 'product_faq_global_position',
					'title'    => __( 'Global FAQ position', 'woo-assistant' ),
					'type'     => 'select',
					'options'  => array(
						'before' => __( 'Before Product FAQs', 'woo-assistant' ),
						'after'  => __( 'After Product FAQs', 'woo-assistant' ),
					),
					'default'  => 'before',
					'sanitize' => 'text'
				),
				'product_faq_button_icon'     => array(
					'id'       => 'product_faq_button_icon',
					'title'    => __( 'Button icon', 'woo-assistant' ),
					'type'     => 'radioInline',
					'default'  => 'chevron',
					'options'  => array(
						'chevron'      => '<i class="wa-icon-chevron-down"></i>',
						'chevrons'     => '<i class="wa-icon-chevrons-down"></i>',
						'arrow'        => '<i class="wa-icon-arrow-down"></i>',
						'arrow-circle' => '<i class="wa-icon-circle-down"></i>',
						'plus'         => '<i class="wa-icon-plus"></i>',
					),
					'sanitize' => 'text'
				),
				'product_faq_end_grid_1'      => array(
					'type' => 'endgrid',
				),

				'product_faq_start_grid_3'              => array(
					'id'    => 'product_faq_start_grid_3',
					'title' => __( 'Global FAQs', 'woo-assistant' ),
					'type'  => 'startgrid',
				),
				'product_faq_start_repeatable'          => array(
					'id'         => 'product_faq_start_repeatable',
					'title'      => __( 'Global FAQs', 'woo-assistant' ),
					'max_repeat' => 10,
					'type'       => 'startRepeatable',
				),
				'product_faq_start_repeatable_elements' => array(
					'id'    => 'product_faq',
					'title' => __( 'FAQ', 'woo-assistant' ),
					'type'  => 'startRepeatableElements',
				),
				'product_faq_question'                  => array(
					'id'          => 'product_faq_question',
					'title'       => __( 'Question', 'woo-assistant' ),
					'placeholder' => __( 'Question', 'woo-assistant' ),
					'type'        => 'text'
				),
				'product_faq_answer'                    => array(
					'id'         => 'product_faq_answer',
					'title'      => __( 'Answer', 'woo-assistant' ),
					'type'       => 'textarea',
					'attributes' => array(
						'rows'        => 2,
						'placeholder' => __( 'Answer', 'woo-assistant' ),
						'resize'      => 'none'
					)
				),
				'product_faq_end_repeatable_elements'   => array(
					'type' => 'endRepeatableElements',
				),
				'product_faq_end_repeatable'            => array(
					'add_text' => __( 'Add', 'woo-assistant' ),
					'type'     => 'endRepeatable',
				),
				'product_faq_end_grid_3'                => array(
					'type' => 'endgrid',
				),
			)
		);

		return $sections;
	}

	public function info(): array {
		return array(
			'id'             => $this->addonID,
			'title'          => __( 'Products FAQ', 'woo-assistant' ),
			'desc'           => __( 'Add FAQ to product', 'woo-assistant' ),
			'tags'           => [ __( 'Product', 'woo-assistant' ) ],
			'cat'            => 'product',
			'more_info_link' => 'https://parsa.ws'
		);
	}
}