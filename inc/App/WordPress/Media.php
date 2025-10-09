<?php

namespace AssistantForWooCommerce\App\WordPress;

defined( 'ABSPATH' ) || exit;

use AssistantForWooCommerce\Settings\Settings;

class Media {
	private const sectionID = 'media';

	public function __construct() {
		add_filter( 'assistant_for_woocommerce_wordpress_settings_sections', [ $this, 'addSectionSettings' ] );

		if ( Settings::get( 'svg_enable', true ) ) {
			add_filter( 'upload_mimes', [ $this, 'addSvgToMedia' ] );
			add_filter( 'image_downsize', [ $this, 'fixSvgSize' ], 10, 2 );
		}
	}

	public function addSvgToMedia( $mimes ) {
		$mimes['svg'] = 'image/svg+xml';

		return $mimes;
	}

	/**
	 * Removes the width and height attributes of <img> tags for SVG
	 *
	 * Without this filter, the width and height are set to "1" since
	 * WordPress core can't seem to figure out an SVG file's dimensions.
	 *
	 * For SVG:s, returns an array with file url, width and height set
	 * to null, and false for 'is_intermediate'.
	 *
	 * @wp-hook image_downsize
	 *
	 * @param mixed $out Value to be filtered
	 * @param int $id Attachment ID for image.
	 *
	 * @return bool|array False if not in admin or not SVG. Array otherwise.
	 */
	public function fixSvgSize( $out, $id ) {
		$url = wp_get_attachment_url( $id );
		$ext = pathinfo( $url, PATHINFO_EXTENSION );

		if ( 'svg' !== $ext || is_admin() ) {
			return false;
		}

		return array( $url, null, null, false );
	}

	public function addSectionSettings( array $sections ): array {
		$settings = [
			'start_grid_svg' => array(
				'title' => 'SVG',
				'type'  => 'startGrid',
			),
			'svg_enable'     => array(
				'id'       => 'svg_enable',
				'title'    => esc_html__( 'Enable SVG support', 'assistant-for-woocommerce' ),
				'type'     => 'toggle',
				'value'    => 1,
				'default'  => true,
				'desc'     => esc_html__( 'Allows upload SVG Files into your Media library', 'assistant-for-woocommerce' ),
				'sanitize' => 'bool'
			),
			'end_grid_svg'   => array(
				'type' => 'endGrid',
			)
		];

		$sections[ self::sectionID ] = array(
			'title'    => esc_html__( 'Media', 'assistant-for-woocommerce' ),
			'desc'     => esc_html__( 'Media Settings', 'assistant-for-woocommerce' ),
			'settings' => $settings
		);

		return $sections;
	}
}