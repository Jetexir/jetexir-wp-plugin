<?php

namespace Jetexir\App\General;

defined( 'ABSPATH' ) || exit;

use Jetexir\Admin\AdminPages;
use Jetexir\Enums\Colors;
use Jetexir\Helper\Assets;
use Jetexir\Settings\Settings;

class Styles {
  private const sectionID = 'styles';

  public function __construct() {
    add_filter( 'jetexir_general_settings_sections', [ $this, 'addSectionSettings' ] );
    add_action( 'wp_enqueue_scripts', [ $this, 'addInlineStyles' ], 0 );
    add_filter( 'jetexir_dashboard_custom_links', [ $this, 'addDashboardLink' ] );
  }

  public function addDashboardLink( $links ) {
    $links[] = [
      'title' => esc_html__( 'Plugin Styles', 'jetexir' ),
      'desc'  => esc_html__( 'General plugin styles', 'jetexir' ),
      'link'  => AdminPages::link( [
        'tab'     => 'general',
        'section' => self::sectionID,
      ] ),
      'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><g stroke="#873eff" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><path d="M10.97 2h-2c-5 0-7 2-7 7v6c0 5 2 7 7 7h6c5 0 7-2 7-7v-2"/><path d="M21.88 3.56c-1.23 3.07-4.32 7.25-6.9 9.32l-1.58 1.26c-.2.15-.4.27-.63.36 0-.15-.01-.3-.03-.46-.09-.67-.39-1.3-.93-1.83-.55-.55-1.21-.86-1.89-.95-.16-.01-.32-.02-.48-.01.09-.25.22-.48.39-.67L11.09 9c2.07-2.58 6.26-5.69 9.32-6.92.47-.18.93-.04 1.22.25.3.3.44.76.25 1.23"/><path d="M12.78 14.49c0 .88-.34 1.72-.97 2.36-.49.49-1.15.83-1.94.93l-1.97.21a1.7 1.7 0 0 1-1.87-1.88l.21-1.97c.19-1.75 1.65-2.87 3.21-2.9.16-.01.32 0 .48.01.68.09 1.34.4 1.89.95.54.54.84 1.16.93 1.83.02.16.03.32.03.46M15.82 11.98c0-2.09-1.69-3.79-3.79-3.79"/></g></svg>',
      'type'  => 'style'
    ];

    return $links;
  }

  public function addInlineStyles(): void {
    if ( ! Settings::get( 'enable_styles', false ) ) {
      return;
    }
    $settings   = $this->addSectionSettings( [] );
    $settings   = $settings[ self::sectionID ]['settings'] ?? [];
    $variables  = [];
    $properties = [];

    if ( ! empty( $settings ) && is_array( $settings ) ) {
      foreach ( $settings as $setting ) {
        if ( isset( $setting['meta']['css_variable'] ) ) {
          // DebugTrait::dd($setting['id'] . '_enable');
          $add = ! isset( $settings[ $setting['id'] . '_enable' ] ) || Settings::get( $setting['id'] . '_enable', true );

          if ( $add && $value = Settings::get( $setting['id'], $setting['default'] ?? false ) ) {
            $name         = JETEXIR_CLASS_PREFIX . str_replace( '_', '-', $setting['meta']['css_variable'] );
            $syntax       = $setting['meta']['css_syntax'] ?? '*';
            $inherits     = $setting['meta']['css_inherits'] ?? true;
            $initialValue = $setting['meta']['css_initial_value'] ?? '';

            $properties[] = Assets::generateCssProperty( $name, $syntax, $inherits, $initialValue );
            $variables[]  = '--' . $name . ': ' . $value . ';';
          }
        }
      }
    }

    if ( empty( $variables ) ) {
      return;
    }

    $sep    = JETEXIR_DEBUG_MODE ? "\n\t\t\t" : '';
    $styles = implode( $sep, $properties ) . $sep . ":root{" . $sep . "\t" . implode( $sep . "\t", $variables ) . "$sep}\n";

    wp_register_style( JETEXIR_PLUGIN_SLUG . '-general-inline-style', false, [], Assets::getVersion() );
    wp_enqueue_style( JETEXIR_PLUGIN_SLUG . '-general-inline-style' );
    wp_add_inline_style( JETEXIR_PLUGIN_SLUG . '-general-inline-style', esc_html( $styles ) );
  }

  public function addSectionSettings( $sections ) {
    $settings = [
      'start_grid_enable_styles' => array(
        'title' => esc_html__( 'Styles', 'jetexir' ),
        'type'  => 'startgrid',
      ),
      'enable_styles'            => array(
        'id'       => 'enable_styles',
        'title'    => esc_html__( 'Enable Styles', 'jetexir' ),
        'type'     => 'toggle',
        'value'    => 1,
        'default'  => true,
        'desc'     => esc_html__( 'If you want to change elements based on the theme style, disable this option.', 'jetexir' ),
        'sanitize' => 'bool'
      ),
      'end_grid_enable_styles'   => array(
        'type' => 'endgrid',
      )
    ];

    $settings = array_merge( $settings, [
      'start_grid_general_styles' => array(
        'title' => esc_html__( 'General', 'jetexir' ),
        'type'  => 'startgrid',
      ),

      'start_inline_elements_primary_color' => array(
        'title' => esc_html__( 'Primary color', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'primary_color_enable'                => array(
        'id'       => 'primary_color_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'primary_color'                       => array(
        'id'       => 'primary_color',
        'type'     => 'wpColorPicker',
        'default'  => Colors::primary,
        'sanitize' => 'color',
        'meta'     => [
          'css_variable' => 'primary-color',
          'css_syntax'   => 'color',
        ]
      ),
      'end_inline_elements_primary_color'   => array(
        'type' => 'endInlineElements',
      ),

      'start_inline_elements_text_color' => array(
        'title' => esc_html__( 'Text color', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'text_color_enable'                => array(
        'id'       => 'text_color_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'text_color'                       => array(
        'id'       => 'text_color',
        'type'     => 'wpColorPicker',
        'default'  => Colors::text,
        'sanitize' => 'color',
        'meta'     => [
          'css_variable' => 'text-color',
          'css_syntax'   => 'color',
        ]
      ),
      'end_inline_elements_text_color'   => array(
        'type' => 'endInlineElements',
      ),

      'start_inline_elements_bg_color' => array(
        'title' => esc_html__( 'Background color', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'bg_color_enable'                => array(
        'id'       => 'bg_color_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'bg_color'                       => array(
        'id'       => 'bg_color',
        'type'     => 'wpColorPicker',
        'default'  => Colors::bg,
        'sanitize' => 'color',
        'meta'     => [
          'css_variable' => 'bg-color',
          'css_syntax'   => 'color',
        ]
      ),
      'end_inline_elements_bg_color'   => array(
        'type' => 'endInlineElements',
      ),

      'end_grid_general_styles' => array(
        'type' => 'endgrid',
      ),
    ] );

    $settings = array_merge( $settings, [
      'start_grid_elements_styles' => array(
        'title' => esc_html__( 'Elements', 'jetexir' ),
        'type'  => 'startgrid',
      ),

      'start_inline_elements_element_color' => array(
        'title' => esc_html__( 'Text color', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'element_color_enable'                => array(
        'id'       => 'element_color_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'element_color'                       => array(
        'id'       => 'element_color',
        'type'     => 'wpColorPicker',
        'default'  => Colors::text,
        'sanitize' => 'color',
        'meta'     => [
          'css_variable' => 'element-color',
          'css_syntax'   => 'color',
        ]
      ),
      'end_inline_elements_element_color'   => array(
        'type' => 'endInlineElements',
      ),

      'start_inline_elements_element_hover_color' => array(
        'title' => esc_html__( 'Hover text color', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'element_hover_color_enable'                => array(
        'id'       => 'element_hover_color_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'element_hover_color'                       => array(
        'id'       => 'element_hover_color',
        'type'     => 'wpColorPicker',
        'default'  => Colors::primary,
        'sanitize' => 'color',
        'meta'     => [
          'css_variable' => 'element-hover-color',
          'css_syntax'   => 'color',
        ]
      ),
      'end_inline_elements_element_hover_color'   => array(
        'type' => 'endInlineElements',
      ),

      'start_inline_elements_element_bg_color' => array(
        'title' => esc_html__( 'Background color', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'element_bg_color_enable'                => array(
        'id'       => 'bg_color_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'element_bg_color'                       => array(
        'id'       => 'element_bg_color',
        'type'     => 'wpColorPicker',
        'default'  => Colors::primaryLight,
        'sanitize' => 'color',
        'meta'     => [
          'css_variable' => 'element-bg-color',
          'css_syntax'   => 'color',
        ]
      ),
      'end_inline_elements_element_bg_color'   => array(
        'type' => 'endInlineElements',
      ),

      'start_inline_elements_element_hover_bg_color' => array(
        'title' => esc_html__( 'Hover background color', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'element_hover_bg_color_enable'                => array(
        'id'       => 'bg_color_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'element_hover_bg_color'                       => array(
        'id'       => 'element_hover_bg_color',
        'type'     => 'wpColorPicker',
        'default'  => Colors::primaryLight2,
        'sanitize' => 'color',
        'meta'     => [
          'css_variable' => 'element-hover-bg-color',
          'css_syntax'   => 'color',
        ]
      ),
      'end_inline_elements_element_hover_bg_color'   => array(
        'type' => 'endInlineElements',
      ),

      'start_inline_elements_element_border_color' => array(
        'title' => esc_html__( 'Border color', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'element_border_color_enable'                => array(
        'id'       => 'element_border_color_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'element_border_color'                       => array(
        'id'       => 'element_border_color',
        'type'     => 'wpColorPicker',
        'default'  => Colors::border,
        'sanitize' => 'color',
        'meta'     => [
          'css_variable' => 'element-border-color',
          'css_syntax'   => 'color',
        ]
      ),
      'end_inline_elements_element_border_color'   => array(
        'type' => 'endInlineElements',
      ),

      'start_inline_elements_element_hover_border_color' => array(
        'title' => esc_html__( 'Hover border color', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'element_hover_border_color_enable'                => array(
        'id'       => 'element_hover_border_color_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'element_hover_border_color'                       => array(
        'id'       => 'element_hover_border_color',
        'type'     => 'wpColorPicker',
        'default'  => Colors::primary,
        'sanitize' => 'color',
        'meta'     => [
          'css_variable' => 'element-hover-border-color',
          'css_syntax'   => 'color',
        ]
      ),
      'end_inline_elements_element_hover_border_color'   => array(
        'type' => 'endInlineElements',
      ),

      'start_inline_elements_element_border_radius' => array(
        'title' => esc_html__( 'Border radius', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'element_border_radius_enable'                => array(
        'id'       => 'element_border_radius_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'element_border_radius'                       => array(
        'id'         => 'element_border_radius',
        'type'       => 'text',
        'default'    => '5px',
        'attributes' => array(
          'placeholder' => 'eg: 4px'
        ),
        'meta'       => [
          'css_variable' => 'element-border-radius',
          'css_syntax'   => [ 'length', 'percentage' ],
        ]
      ),
      'end_inline_elements_element_border_radius'   => array(
        'type' => 'endInlineElements',
      ),

      'start_inline_elements_element_border_width' => array(
        'title' => esc_html__( 'Border width', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'element_border_width_enable'                => array(
        'id'       => 'element_border_width_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'element_border_width'                       => array(
        'id'         => 'element_border_width',
        'type'       => 'text',
        'default'    => '1px',
        'attributes' => array(
          'placeholder' => 'eg: 1px'
        ),
        'meta'       => [
          'css_variable' => 'element-border-width',
          'css_syntax'   => [ 'length', 'percentage' ],
        ]
      ),
      'end_inline_elements_element_border_width'   => array(
        'type' => 'endInlineElements',
      ),

      'end_grid_elements_styles' => array(
        'type' => 'endgrid',
      ),
    ] );

    // Input styles
    $settings = array_merge( $settings, [
      'start_grid_input_styles' => array(
        'title' => esc_html__( 'Input box', 'jetexir' ),
        'type'  => 'startgrid',
      ),

      'start_inline_elements_input_color' => array(
        'title' => esc_html__( 'Text color', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'input_color_enable'                => array(
        'id'       => 'input_color_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'input_color'                       => array(
        'id'       => 'input_color',
        'type'     => 'wpColorPicker',
        'default'  => Colors::inputText,
        'sanitize' => 'color',
        'meta'     => [
          'css_variable' => 'input-color',
          'css_syntax'   => 'color',
        ]
      ),
      'end_inline_elements_input_color'   => array(
        'type' => 'endInlineElements',
      ),

      'start_inline_elements_input_bg_color' => array(
        'title' => esc_html__( 'Background color', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'input_bg_color_enable'                => array(
        'id'       => 'input_bg_color_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'input_bg_color'                       => array(
        'id'       => 'input_bg_color',
        'type'     => 'wpColorPicker',
        'default'  => Colors::inputBg,
        'sanitize' => 'color',
        'meta'     => [
          'css_variable' => 'input-bg-color',
          'css_syntax'   => 'color',
        ]
      ),
      'end_inline_elements_input_bg_color'   => array(
        'type' => 'endInlineElements',
      ),

      'start_inline_elements_input_border_color' => array(
        'title' => esc_html__( 'Border color', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'input_border_color_enable'                => array(
        'id'       => 'input_border_color_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'input_border_color'                       => array(
        'id'       => 'input_border_color',
        'type'     => 'wpColorPicker',
        'default'  => Colors::inputBorder,
        'sanitize' => 'color',
        'meta'     => [
          'css_variable' => 'input-border-color',
          'css_syntax'   => 'color',
        ]
      ),
      'end_inline_elements_input_border_color'   => array(
        'type' => 'endInlineElements',
      ),

      'start_inline_elements_input_border_radius' => array(
        'title' => esc_html__( 'Border radius', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'input_border_radius_enable'                => array(
        'id'       => 'input_border_radius_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'input_border_radius'                       => array(
        'id'         => 'input_border_radius',
        'type'       => 'text',
        'default'    => '5px',
        'attributes' => array(
          'placeholder' => 'eg: 4px'
        ),
        'meta'       => [
          'css_variable' => 'input-border-radius',
          'css_syntax'   => [ 'length', 'percentage' ],
        ]
      ),
      'end_inline_elements_input_border_radius'   => array(
        'type' => 'endInlineElements',
      ),

      'start_inline_elements_input_border_width' => array(
        'title' => esc_html__( 'Border width', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'input_border_width_enable'                => array(
        'id'       => 'input_border_width_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'input_border_width'                       => array(
        'id'         => 'input_border_width',
        'type'       => 'text',
        'default'    => '1px',
        'attributes' => array(
          'placeholder' => 'eg: 1px'
        ),
        'meta'       => [
          'css_variable' => 'input-border-width',
          'css_syntax'   => [ 'length', 'percentage' ],
        ]
      ),
      'end_inline_elements_input_border_width'   => array(
        'type' => 'endInlineElements',
      ),

      'end_grid_input_styles' => array(
        'type' => 'endgrid',
      )
    ] );


    // Primary Button styles
    $settings = array_merge( $settings, [
      'start_grid_button_styles' => array(
        'title' => esc_html__( 'Primary Button', 'jetexir' ),
        'type'  => 'startgrid',
      ),

      'start_inline_elements_button_color' => array(
        'title' => esc_html__( 'Text color', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'button_color_enable'                => array(
        'id'       => 'button_color_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'button_color'                       => array(
        'id'       => 'button_color',
        'type'     => 'wpColorPicker',
        'default'  => Colors::buttonText,
        'sanitize' => 'color',
        'meta'     => [
          'css_variable' => 'button-color',
          'css_syntax'   => 'color',
        ]
      ),
      'end_inline_elements_button_color'   => array(
        'type' => 'endInlineElements',
      ),

      'start_inline_elements_button_hover_color' => array(
        'title' => esc_html__( 'Hover text color', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'button_hover_color_enable'                => array(
        'id'       => 'button_hover_color_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'button_hover_color'                       => array(
        'id'       => 'button_hover_color',
        'type'     => 'wpColorPicker',
        'default'  => Colors::buttonHoverText,
        'sanitize' => 'color',
        'meta'     => [
          'css_variable' => 'button-hover-color',
          'css_syntax'   => 'color',
        ]
      ),
      'end_inline_elements_button_hover_color'   => array(
        'type' => 'endInlineElements',
      ),

      'start_inline_elements_button_bg_color' => array(
        'title' => esc_html__( 'Background color', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'button_bg_color_enable'                => array(
        'id'       => 'button_bg_color_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'button_bg_color'                       => array(
        'id'       => 'button_bg_color',
        'type'     => 'wpColorPicker',
        'default'  => Colors::buttonBg,
        'sanitize' => 'color',
        'meta'     => [
          'css_variable' => 'button-bg-color',
          'css_syntax'   => 'color',
        ]
      ),
      'end_inline_elements_button_bg_color'   => array(
        'type' => 'endInlineElements',
      ),

      'start_inline_elements_button_hover_bg_color' => array(
        'title' => esc_html__( 'Hover background color', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'button_hover_bg_color_enable'                => array(
        'id'       => 'button_hover_bg_color_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'button_hover_bg_color'                       => array(
        'id'       => 'button_hover_bg_color',
        'type'     => 'wpColorPicker',
        'default'  => Colors::buttonHoverBg,
        'sanitize' => 'color',
        'meta'     => [
          'css_variable' => 'button-hover-bg-color',
          'css_syntax'   => 'color',
        ]
      ),
      'end_inline_elements_button_hover_bg_color'   => array(
        'type' => 'endInlineElements',
      ),

      'start_inline_elements_button_border_color' => array(
        'title' => esc_html__( 'Border color', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'button_border_color_enable'                => array(
        'id'       => 'button_border_color_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'button_border_color'                       => array(
        'id'       => 'button_border_color',
        'type'     => 'wpColorPicker',
        'default'  => Colors::buttonBorder,
        'sanitize' => 'color',
        'meta'     => [
          'css_variable' => 'button-border-color',
          'css_syntax'   => 'color',
        ]
      ),
      'end_inline_elements_button_border_color'   => array(
        'type' => 'endInlineElements',
      ),

      'start_inline_elements_button_hover_border_color' => array(
        'title' => esc_html__( 'Hover border color', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'button_hover_border_color_enable'                => array(
        'id'       => 'button_hover_border_color_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'button_hover_border_color'                       => array(
        'id'       => 'button_hover_border_color',
        'type'     => 'wpColorPicker',
        'default'  => Colors::buttonHoverBorder,
        'sanitize' => 'color',
        'meta'     => [
          'css_variable' => 'button-hover-border-color',
          'css_syntax'   => 'color',
        ]
      ),
      'end_inline_elements_button_hover_border_color'   => array(
        'type' => 'endInlineElements',
      ),

      'start_inline_elements_button_border_radius' => array(
        'title' => esc_html__( 'Border radius', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'button_border_radius_enable'                => array(
        'id'       => 'button_border_radius_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'button_border_radius'                       => array(
        'id'         => 'button_border_radius',
        'type'       => 'text',
        'default'    => '5px',
        'attributes' => array(
          'placeholder' => 'eg: 4px'
        ),
        'meta'       => [
          'css_variable' => 'button-border-radius',
          'css_syntax'   => [ 'length', 'percentage' ],
        ]
      ),
      'end_inline_elements_button_border_radius'   => array(
        'type' => 'endInlineElements',
      ),

      'start_inline_elements_button_border_width' => array(
        'title' => esc_html__( 'Border width', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'button_border_width_enable'                => array(
        'id'       => 'button_border_width_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'button_border_width'                       => array(
        'id'         => 'button_border_width',
        'type'       => 'text',
        'default'    => '1px',
        'attributes' => array(
          'placeholder' => 'eg: 1px'
        ),
        'meta'       => [
          'css_variable' => 'button-border-width',
          'css_syntax'   => [ 'length', 'percentage' ],
        ]
      ),
      'end_inline_elements_button_border_width'   => array(
        'type' => 'endInlineElements',
      ),

      'end_grid_button_styles' => array(
        'type' => 'endgrid',
      )
    ] );

    // Secondary button styles
    $settings = array_merge( $settings, [
      'start_grid_secondary_button_styles' => array(
        'title' => esc_html__( 'Secondary Button', 'jetexir' ),
        'type'  => 'startGrid',
      ),

      'start_inline_elements_secondary_button_color' => array(
        'title' => esc_html__( 'Text color', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'secondary_button_color_enable'                => array(
        'id'       => 'secondary_button_color_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'secondary_button_color'                       => array(
        'id'       => 'secondary_button_color',
        'type'     => 'wpColorPicker',
        'default'  => Colors::secondaryButtonText,
        'sanitize' => 'color',
        'meta'     => [
          'css_variable' => 'secondary-button-color',
          'css_syntax'   => 'color',
        ]
      ),
      'end_inline_elements_secondary_button_color'   => array(
        'type' => 'endInlineElements',
      ),

      'start_inline_elements_secondary_button_hover_color' => array(
        'title' => esc_html__( 'Hover text color', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'secondary_button_hover_color_enable'                => array(
        'id'       => 'secondary_button_hover_color_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'secondary_button_hover_color'                       => array(
        'id'       => 'secondary_button_hover_color',
        'type'     => 'wpColorPicker',
        'default'  => Colors::secondaryButtonHoverText,
        'sanitize' => 'color',
        'meta'     => [
          'css_variable' => 'secondary-button-hover-color',
          'css_syntax'   => 'color',
        ]
      ),
      'end_inline_elements_secondary_button_hover_color'   => array(
        'type' => 'endInlineElements',
      ),

      'start_inline_elements_secondary_button_bg_color' => array(
        'title' => esc_html__( 'Background color', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'secondary_button_bg_color_enable'                => array(
        'id'       => 'secondary_button_bg_color_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'secondary_button_bg_color'                       => array(
        'id'       => 'secondary_button_bg_color',
        'type'     => 'wpColorPicker',
        'default'  => Colors::secondaryButtonBg,
        'sanitize' => 'color',
        'meta'     => [
          'css_variable' => 'secondary-button-bg-color',
          'css_syntax'   => 'color',
        ]
      ),
      'end_inline_elements_secondary_button_bg_color'   => array(
        'type' => 'endInlineElements',
      ),

      'start_inline_elements_secondary_button_hover_bg_color' => array(
        'title' => esc_html__( 'Hover background color', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'secondary_button_hover_bg_color_enable'                => array(
        'id'       => 'secondary_button_hover_bg_color_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'secondary_button_hover_bg_color'                       => array(
        'id'       => 'secondary_button_hover_bg_color',
        'type'     => 'wpColorPicker',
        'default'  => Colors::secondaryButtonHoverBg,
        'sanitize' => 'color',
        'meta'     => [
          'css_variable' => 'secondary-button-hover-bg-color',
          'css_syntax'   => 'color',
        ]
      ),
      'end_inline_elements_secondary_button_hover_bg_color'   => array(
        'type' => 'endInlineElements',
      ),

      'start_inline_elements_secondary_button_border_color' => array(
        'title' => esc_html__( 'Border color', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'secondary_button_border_color_enable'                => array(
        'id'       => 'secondary_button_border_color_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'secondary_button_border_color'                       => array(
        'id'       => 'secondary_button_border_color',
        'type'     => 'wpColorPicker',
        'default'  => Colors::secondaryButtonBorder,
        'sanitize' => 'color',
        'meta'     => [
          'css_variable' => 'secondary-button-border-color',
          'css_syntax'   => 'color',
        ]
      ),
      'end_inline_elements_secondary_button_border_color'   => array(
        'type' => 'endInlineElements',
      ),

      'start_inline_elements_secondary_button_hover_border_color' => array(
        'title' => esc_html__( 'Hover border color', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'secondary_button_hover_border_color_enable'                => array(
        'id'       => 'secondary_button_hover_border_color_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'secondary_button_hover_border_color'                       => array(
        'id'       => 'secondary_button_hover_border_color',
        'type'     => 'wpColorPicker',
        'default'  => Colors::secondaryButtonHoverBorder,
        'sanitize' => 'color',
        'meta'     => [
          'css_variable' => 'secondary-button-hover-border-color',
          'css_syntax'   => 'color',
        ]
      ),
      'end_inline_elements_secondary_button_hover_border_color'   => array(
        'type' => 'endInlineElements',
      ),

      'start_inline_elements_secondary_button_border_radius' => array(
        'title' => esc_html__( 'Border radius', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'secondary_button_border_radius_enable'                => array(
        'id'       => 'secondary_button_border_radius_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'secondary_button_border_radius'                       => array(
        'id'         => 'secondary_button_border_radius',
        'type'       => 'text',
        'default'    => '5px',
        'attributes' => array(
          'placeholder' => 'eg: 4px'
        ),
        'meta'       => [
          'css_variable' => 'secondary-button-border-radius',
          'css_syntax'   => [ 'length', 'percentage' ],
        ]
      ),
      'end_inline_elements_secondary_button_border_radius'   => array(
        'type' => 'endInlineElements',
      ),

      'start_inline_elements_secondary_button_border_width' => array(
        'title' => esc_html__( 'Border width', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      'secondary_button_border_width_enable'                => array(
        'id'       => 'secondary_button_border_width_enable',
        'type'     => 'checkbox',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ),
      'secondary_button_border_width'                       => array(
        'id'         => 'secondary_button_border_width',
        'type'       => 'text',
        'default'    => '1px',
        'attributes' => array(
          'placeholder' => 'eg: 1px'
        ),
        'meta'       => [
          'css_variable' => 'secondary-button-border-width',
          'css_syntax'   => [ 'length', 'percentage' ],
        ]
      ),
      'end_inline_elements_secondary_button_border_width'   => array(
        'type' => 'endInlineElements',
      ),

      'end_grid_secondary_button_styles' => array(
        'type' => 'endGrid',
      )
    ] );

    $sections[ self::sectionID ] = array(
      'title'    => esc_html__( 'Styles', 'jetexir' ),
      'desc'     => esc_html__( 'General Styles', 'jetexir' ),
      'settings' => $settings
    );

    return $sections;
  }
}
