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
    $globalFAQsPosition = $this->getSetting( 'product_faq_global_position', 'before' );
    $globalFAQs         = $this->getSetting( 'product_faq', [] );
    $buttonIcon         = $this->getSetting( 'product_faq_button_icon', 'chevron' );
    $productFAQs        = PostMeta::get( $productID, JETEXIR_INPUT_PREFIX . 'product_faq' );
    $productFAQs        = is_array( $productFAQs ) ? $productFAQs : [];

    if ( $globalFAQsPosition === 'before' ) {
      $FAQs = array_merge( $globalFAQs, $productFAQs );
    } else {
      $FAQs = array_merge( $productFAQs, $globalFAQs );
    }

    $title = apply_filters( 'jetexir_product_faq_tab_title', esc_html__( 'FAQs', 'jetexir' ) );

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

    $tabs['jetexir_product_faq'] = array(
      'title'    => apply_filters( 'jetexir_product_faq_tab_title', esc_html__( 'FAQs', 'jetexir' ) ),
      'priority' => 50,
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
        'product_faq_start_grid_1'    => array(
          'id'    => 'product_faq_start_grid_1',
          'title' => esc_html__( 'Frequently asked questions', 'jetexir' ),
          'type'  => 'startgrid',
        ),
        'product_faq_global_position' => array(
          'id'       => 'product_faq_global_position',
          'title'    => esc_html__( 'Global FAQ position', 'jetexir' ),
          'type'     => 'select',
          'options'  => array(
            'before' => esc_html__( 'Before Product FAQs', 'jetexir' ),
            'after'  => esc_html__( 'After Product FAQs', 'jetexir' ),
          ),
          'default'  => 'before',
          'sanitize' => 'text'
        ),
        'product_faq_button_icon'     => array(
          'id'       => 'product_faq_button_icon',
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
        'product_faq_end_grid_1'      => array(
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
    $icon = '<svg fill="#873eff" viewBox="-2.4 -2.4 28.80 28.80" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"><path transform="translate(-2.4, -2.4), scale(0.8999999999999999)" d="M16,31.965446455294597C20.406759313481942,31.744183831601248,22.385060002277697,26.732112649062696,25.015434445256012,23.18956905930547C27.46066883355492,19.89636894825435,31.174361707140367,16.743764821892324,30.273475416344745,12.742172379351086C29.371206456275125,8.734438341611988,24.695895827434633,7.4078928448546435,21.04046107126705,5.533374736924053C17.191078881952365,3.559399792892778,13.213080969520368,0.25236148361584476,9.235796291003204,1.953986267494189C5.166684346217215,3.6948980435666883,4.0156900015017944,8.735971820057475,3.3336411605343645,13.108986250607973C2.722245691171473,17.029000551203772,3.560801396536637,20.862353400017675,5.7730427963683955,24.155726219135406C8.28936695005648,27.901788277496678,11.492932558850207,32.191745532746076,16,31.965446455294597" fill="#fff" strokewidth="0"></path></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M12,1A11,11,0,1,0,23,12,11.013,11.013,0,0,0,12,1Zm0,20a9,9,0,1,1,9-9A9.011,9.011,0,0,1,12,21Zm1-4.5v2H11v-2Zm3-7a3.984,3.984,0,0,1-1.5,3.122A3.862,3.862,0,0,0,13.063,15H11.031a5.813,5.813,0,0,1,2.219-3.936A2,2,0,0,0,13.1,7.832a2.057,2.057,0,0,0-2-.14A1.939,1.939,0,0,0,10,9.5,1,1,0,0,1,8,9.5V9.5a3.909,3.909,0,0,1,2.319-3.647,4.061,4.061,0,0,1,3.889.315A4,4,0,0,1,16,9.5Z"></path></g></svg>';

    return array(
      'id'             => $this->addonID,
      'title'          => esc_html__( 'Products FAQ', 'jetexir' ),
      'desc'           => esc_html__( 'Add a frequently asked questions (FAQ) section to the product page.', 'jetexir' ),
      'tags'           => [ esc_html__( 'Product', 'jetexir' ) ],
      'cat'            => 'product',
      'icon'           => $icon,
      'more_info_link' => 'https://parsa.ws',
      'settings_key'   => $this->addonID,
    );
  }
}
