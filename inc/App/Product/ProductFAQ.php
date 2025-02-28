<?php

namespace WooAssistant\App\Product;

use WooAssistant\Helper\Param;
use WooAssistant\Helper\PostMeta;
use WooAssistant\Settings\Settings;

class ProductFAQ {
	private const sectionID = 'faq';
	private const maxProductFAQs = 5;

	public function __construct() {
		add_filter( 'woo_assistant_product_settings_sections', [ $this, 'addSectionSettings' ] );
		add_action( 'woo_assistant_init', [ $this, 'init' ] );
	}

	public function init(): void {
		if ( Settings::get( 'product_faq_enable', false ) ) {
			// Admin
			add_filter( 'woocommerce_product_data_tabs', [ $this, 'adminProductTab' ] );
			add_filter( 'woocommerce_product_data_panels', [ $this, 'adminProductSettings' ] );
			add_action( 'woocommerce_process_product_meta', [ $this, 'adminProductSaveMeta' ] );

			// Front
			add_filter( 'woocommerce_product_tabs', [ $this, 'productTab' ], 9999 );
		}
	}

	public function productTabContent(): void {
		$productID          = get_the_ID();
		$globalFAQsPosition = Settings::get( 'product_faq_global_position', 'before' );
		$globalFAQs         = Settings::get( 'product_faq', [] );
		$primaryColor       = Settings::get( 'product_faq_primary_color', '#720eec' );
		$bgColor            = Settings::get( 'product_faq_bg_color', '#ffffff' );
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
			echo '<div class="wa-faqs-wrap" style="--wa-faq-primary-color: ' . $primaryColor . '; --wa-faq-bg-color: ' . $bgColor . ';">';
			foreach ( $FAQs as $faq ) {
				if ( empty( $faq['question'] ) || empty( $faq['answer'] ) ) {
					continue;
				}

				echo '<div class="wa-faq-item">';
				echo '<button class="wa-faq-question" type="button">' . $faq['question'] . '<i class="wa-icon-chevron-down"></i></button>';
				echo '<div class="wa-faq-answer">' . $faq['answer'] . '</div>';
				echo '</div>';
			}
			echo '</div>';
		}
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
				'product_faq_start_grid_1'              => array(
					'id'    => 'product_faq_start_grid_1',
					'title' => __( 'Frequently asked questions', 'woo-assistant' ),
					'type'  => 'startgrid',
				),
				'product_faq_enable'                    => array(
					'id'       => 'product_faq_enable',
					'title'    => __( 'Enable FAQ feature', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => false,
					'sanitize' => 'bool'
				),
				'product_faq_global_position'           => array(
					'id'       => 'product_faq_global_position',
					'title'    => __( 'Global FAQ position', 'woo-assistant' ),
					'type'     => 'select',
					'options'  => array(
						'before' => __( 'Before product FAQs', 'woo-assistant' ),
						'after'  => __( 'After product FAQs', 'woo-assistant' ),
					),
					'default'  => 'before',
					'sanitize' => 'text'
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
				'product_faq_end_grid_1'                => array(
					'type' => 'endgrid',
				),
				'product_faq_start_grid_2'              => array(
					'id'    => 'product_faq_start_grid_2',
					'title' => __( 'Style', 'woo-assistant' ),
					'type'  => 'startgrid',
				),
				'product_faq_primary_color'             => array(
					'id'       => 'product_faq_primary_color',
					'title'    => __( 'Primary color', 'woo-assistant' ),
					'type'     => 'wpColorPicker',
					'default'  => '#720eec',
					'sanitize' => 'color'
				),
				'product_faq_bg_color'                  => array(
					'id'       => 'product_faq_bg_color',
					'title'    => __( 'Background color', 'woo-assistant' ),
					'type'     => 'wpColorPicker',
					'default'  => '#ffffff',
					'sanitize' => 'color'
				),
				'product_faq_end_grid_2'                => array(
					'type' => 'endgrid',
				),
			)
		);

		return $sections;
	}
}