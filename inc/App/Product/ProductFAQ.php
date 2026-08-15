<?php

namespace Jetexir\App\Product;

defined( 'ABSPATH' ) || exit;

use Jetexir\Addons\Addon;
use Jetexir\Helper\{Param, PostMeta, Sanitizing, Templates};
use Jetexir\Interfaces\AddonInterface;

class ProductFAQ extends Addon implements AddonInterface {
  public string $addonID = 'product-faq';
  public string $currentTab = 'product';
  public string $currentSection = 'faq';
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
    $globalFAQsPosition = $this->getSetting( 'global_position', 'before' );
    $globalFAQs         = $this->getSetting( 'product_faq', [] );
    $buttonIcon         = $this->getSetting( 'button_icon', 'chevron' );
    $productFAQs        = PostMeta::get( $productID, JETEXIR_INPUT_PREFIX . 'product_faq' );
    $productFAQs        = is_array( $productFAQs ) ? $productFAQs : [];

    if ( $globalFAQsPosition === 'before' ) {
      $FAQs = array_merge( $globalFAQs, $productFAQs );
    } else {
      $FAQs = array_merge( $productFAQs, $globalFAQs );
    }

    /**
     * Product FAQ items
     *
     * @param array $FAQs FAQs items
     * @param string $productID Current product ID
     *
     * @return array FAQ items
     *
     * @since 1.0
     *
     */
    $FAQs = (array) apply_filters( 'jetexir_product_faq_items', $FAQs, $productID );

    /**
     * Product FAQ title
     *
     * @param array $FAQs FAQ tab title
     * @param string $productID Current product ID
     *
     * @return string FAQ title
     *
     * @since 1.0
     *
     */
    $title = (string) apply_filters( 'jetexir_product_faq_tab_title', $this->getSetting( 'tab_title', esc_html__( 'FAQs', 'jetexir' ) ), $productID );

    Templates::load( Templates::getPath( 'product-faq/product_faq.php' ), array(
      'title' => $title,
      'items' => $FAQs,
      'icon'  => $this->getIcon( $buttonIcon ),
    ) );
  }

  public function productTab( $tabs ) {
    $productID = wc_get_product()->get_id();
    $enable    = PostMeta::get( $productID, JETEXIR_INPUT_PREFIX . 'product_faq_enable' );
    $enable    = $enable === '' ? 1 : (int) $enable;
    if ( $enable === 0 || ! $this->productHasFAQs( $productID ) ) {
      return $tabs;
    }

    /**
     * Product FAQ title
     *
     * @param array $FAQs FAQ tab title
     * @param string $productID Current product ID
     *
     * @return string FAQ title
     *
     * @since 1.0
     *
     */
    $title = (string) apply_filters( 'jetexir_product_faq_tab_title', $this->getSetting( 'tab_title', esc_html__( 'FAQs', 'jetexir' ) ), $productID );

    /**
     * Product FAQ title
     *
     * @param array $FAQs FAQ tab priority
     * @param string $productID Current product ID
     *
     * @return int FAQ tab priority
     *
     * @since 1.0
     *
     */
    $priority = (int) apply_filters( 'jetexir_product_faq_tab_priority', 50, $productID );

    $tabs['jetexir_product_faq'] = array(
      'title'    => $title,
      'priority' => $priority,
      'callback' => [ $this, 'productTabContent' ],
    );

    return $tabs;
  }

  private function productHasFAQs( $productID ): bool {
    $globalFAQs = $this->getSetting( 'product_faq', [] );
    if ( is_array( $globalFAQs ) && count( $globalFAQs ) ) {
      return true;
    }

    $productFAQs = PostMeta::get( $productID, JETEXIR_INPUT_PREFIX . 'product_faq' );

    return is_array( $productFAQs ) && count( $productFAQs );
  }

  private function getIcon( $icon ): string {
    if ( $icon === 'chevron' ) {
      return '<i class="jetexir-icon-chevron-down"></i>';

    } elseif ( $icon === 'chevrons' ) {
      return '<i class="jetexir-icon-chevrons-down"></i>';

    } elseif ( $icon === 'arrow' ) {
      return '<i class="jetexir-icon-arrow-down"></i>';

    } elseif ( $icon === 'arrow-circle' ) {
      return '<i class="jetexir-icon-circle-down"></i>';

    } elseif ( $icon === 'plus' ) {
      return '<i class="jetexir-icon-plus"></i>';
    }

    return '';
  }

  public function adminProductSaveMeta( $productID ): void {
    $enable = (int) Sanitizing::bool( Param::post( JETEXIR_INPUT_PREFIX . 'product_faq_enable' ) );
    PostMeta::update( $productID, JETEXIR_INPUT_PREFIX . 'product_faq_enable', $enable );

    $FAQs = Param::post( JETEXIR_INPUT_PREFIX . 'product_faq' );
    if ( is_array( $FAQs ) ) {
      foreach ( $FAQs as $index => $faq ) {
        $faq = array_map( 'trim', $faq );
        if ( implode( $faq ) === '' ) {
          unset( $FAQs[ $index ] );
        }
      }
      $FAQs = array_values( $FAQs );

      PostMeta::update( $productID, JETEXIR_INPUT_PREFIX . 'product_faq', $FAQs );
    }
  }

  public function adminProductTab( $tabs ) {
    $tabs[ JETEXIR_PLUGIN_KEY . '_faq_control' ] = array(
      'label'  => esc_html__( 'FAQs', 'jetexir' ),
      'target' => JETEXIR_PLUGIN_KEY . '_faq_control'
    );

    return $tabs;
  }

  public function adminProductSettings(): void {
    $productID = get_the_ID();
    $enable    = PostMeta::get( $productID, JETEXIR_INPUT_PREFIX . 'product_faq_enable' );
    $enable    = $enable === '' ? 1 : (int) $enable;
    $FAQs      = PostMeta::get( $productID, JETEXIR_INPUT_PREFIX . 'product_faq' );
    $FAQs      = is_array( $FAQs ) ? $FAQs : [];
    ?>
    <div id="<?php echo esc_html( JETEXIR_PLUGIN_KEY ) . '_faq_control' ?>"
         class="panel woocommerce_options_panel"
         style="display: none">
      <div class="options_group">
        <?php
        woocommerce_wp_checkbox( array(
          'id'      => JETEXIR_INPUT_PREFIX . 'product_faq_enable',
          'name'    => JETEXIR_INPUT_PREFIX . 'product_faq_enable',
          'label'   => esc_html__( 'Enable Product FAQ', 'jetexir' ),
          'value'   => $enable === 1 ? 1 : 0,
          'cbvalue' => 1
        ) );

        echo '<p><strong>' . esc_html__( 'Product FAQs', 'jetexir' ) . '</strong></p>';

        for ( $i = 1; $i <= self::maxProductFAQs; $i ++ ) {
          $index = $i - 1;
          woocommerce_wp_text_input( array(
            'id'          => JETEXIR_INPUT_PREFIX . 'product_faq_question_' . $index,
            'name'        => JETEXIR_INPUT_PREFIX . 'product_faq[' . $index . '][question]',
            'label'       => esc_html__( 'Question', 'jetexir' ) . ' ' . $i,
            'type'        => 'text',
            'placeholder' => esc_html__( 'Question', 'jetexir' ),
            'value'       => $FAQs[ $index ]['question'] ?? '',
          ) );

          woocommerce_wp_textarea_input( array(
            'id'          => JETEXIR_INPUT_PREFIX . 'product_faq_answer_' . $index,
            'name'        => JETEXIR_INPUT_PREFIX . 'product_faq[' . $index . '][answer]',
            'label'       => esc_html__( 'Answer', 'jetexir' ) . ' ' . $i,
            'rows'        => 3,
            'placeholder' => esc_html__( 'Answer', 'jetexir' ),
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
    $sections[ $this->currentSection ] = array(
      'title'        => esc_html__( 'FAQ', 'jetexir' ),
      'desc'         => esc_html__( 'Product frequently asked questions', 'jetexir' ),
      'settings_key' => $this->addonID,
      'settings'     => array(
        'product_faq_start_grid_1' => array(
          'id'    => 'product_faq_start_grid_1',
          'title' => esc_html__( 'Frequently asked questions', 'jetexir' ),
          'type'  => 'startgrid',
        ),
        'tab_title'                => array(
          'id'          => 'tab_title',
          'title'       => esc_html__( 'Title', 'jetexir' ),
          'type'        => 'text',
          'default'     => esc_html__( 'FAQs', 'jetexir' ),
          'placeholder' => esc_html__( 'FAQs', 'jetexir' ),
          'desc'        => esc_html__( 'Product FAQ tab title', 'jetexir' )
        ),
        'global_position'          => array(
          'id'       => 'global_position',
          'title'    => esc_html__( 'Global FAQ position', 'jetexir' ),
          'type'     => 'select',
          'options'  => array(
            'before' => esc_html__( 'Before Product FAQs', 'jetexir' ),
            'after'  => esc_html__( 'After Product FAQs', 'jetexir' ),
          ),
          'default'  => 'before',
          'sanitize' => 'text'
        ),
        'button_icon'              => array(
          'id'       => 'button_icon',
          'title'    => esc_html__( 'Button icon', 'jetexir' ),
          'type'     => 'radioInline',
          'default'  => 'chevron',
          'options'  => array(
            'chevron'      => '<i class="jetexir-icon-chevron-down"></i>',
            'chevrons'     => '<i class="jetexir-icon-chevrons-down"></i>',
            'arrow'        => '<i class="jetexir-icon-arrow-down"></i>',
            'arrow-circle' => '<i class="jetexir-icon-circle-down"></i>',
            'plus'         => '<i class="jetexir-icon-plus"></i>',
          ),
          'sanitize' => 'text'
        ),
        'product_faq_end_grid_1'   => array(
          'type' => 'endgrid',
        ),

        'product_faq_start_grid_3'              => array(
          'id'    => 'product_faq_start_grid_3',
          'title' => esc_html__( 'Global FAQs', 'jetexir' ),
          'type'  => 'startgrid',
        ),
        'product_faq_start_repeatable'          => array(
          'id'         => 'product_faq_start_repeatable',
          'title'      => esc_html__( 'Global FAQs', 'jetexir' ),
          'max_repeat' => 10,
          'type'       => 'startRepeatable',
        ),
        'product_faq_start_repeatable_elements' => array(
          'id'    => 'product_faq',
          'title' => esc_html__( 'FAQ', 'jetexir' ),
          'type'  => 'startRepeatableElements',
        ),
        'product_faq_question'                  => array(
          'id'          => 'product_faq_question',
          'title'       => esc_html__( 'Question', 'jetexir' ),
          'placeholder' => esc_html__( 'Question', 'jetexir' ),
          'type'        => 'text'
        ),
        'product_faq_answer'                    => array(
          'id'         => 'product_faq_answer',
          'title'      => esc_html__( 'Answer', 'jetexir' ),
          'type'       => 'textarea',
          'attributes' => array(
            'rows'        => 2,
            'placeholder' => esc_html__( 'Answer', 'jetexir' ),
            'resize'      => 'none'
          )
        ),
        'product_faq_end_repeatable_elements'   => array(
          'type' => 'endRepeatableElements',
        ),
        'product_faq_end_repeatable'            => array(
          'add_text' => esc_html__( 'Add', 'jetexir' ),
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
    $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="#873eff" stroke-width="1.5"/><path stroke="#873eff" stroke-linecap="round" stroke-width="1.5" d="M10.125 8.875a1.875 1.875 0 1 1 2.828 1.615c-.475.281-.953.708-.953 1.26V13"/><circle cx="12" cy="16" r="1" fill="#873eff"/></svg>';

    return array(
      'id'             => $this->addonID,
      'title'          => esc_html__( 'Products FAQ', 'jetexir' ),
      'desc'           => esc_html__( 'Add a frequently asked questions (FAQ) section to the product page.', 'jetexir' ),
      'tags'           => [ esc_html__( 'Product', 'jetexir' ) ],
      'cat'            => 'product',
      'icon'           => $icon,
      'more_info_link' => '{jetexir_website}/addons/faq-section',
      'settings_key'   => $this->addonID,
    );
  }
}
