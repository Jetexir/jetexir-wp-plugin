<?php

namespace Jetexir\App\General;

use Jetexir\Helper\Notice;
use Jetexir\Settings\Settings;

defined( 'ABSPATH' ) || exit;

class Debug {
  private const sectionID = 'debug';

  public function __construct() {
    add_filter( 'jetexir_general_settings_sections', [ $this, 'addSectionSettings' ] );
    add_action( 'jetexir_admin_init', [ $this, 'addNotice' ] );
  }

  public function addNotice( $tab ): void {
    if ( Settings::get( 'debug_mode', false ) ) {
      Notice::add( 'dashboard', esc_html__( 'Debug mode is enabled!', 'jetexir' ), 'warning' );
    }
  }

  public function addSectionSettings( array $sections ): array {
    $settings = [
      'start_grid_debug_mode' => array(
        'title' => esc_html__( 'Debugging', 'jetexir' ),
        'type'  => 'startGrid',
      ),
      'debug_mode'            => array(
        'id'       => 'debug_mode',
        'title'    => esc_html__( 'Enable debug mode', 'jetexir' ),
        'type'     => 'toggle',
        'value'    => 1,
        'default'  => false,
        'desc'     => esc_html__( 'By enabling this option, the uncompressed version of the JS and CSS files will be loaded.', 'jetexir' ),
        'sanitize' => 'bool'
      ),
      'end_grid_debug_mode'   => array(
        'type' => 'endGrid',
      )
    ];

    $sections[ self::sectionID ] = array(
      'title'    => esc_html__( 'Debug', 'jetexir' ),
      'desc'     => esc_html__( 'Debug Settings', 'jetexir' ),
      'settings' => $settings
    );

    return $sections;
  }
}
