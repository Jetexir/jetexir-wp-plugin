<?php

namespace WooAssistant\App\Product;

class ProductTest {
	private static string $sectionID = 'test';

	public function __construct() {
		add_filter( 'woo_assistant_product_settings_sections', [ $this, 'addSectionSettings' ] );
		add_action( 'woo_assistant_section_content', [ $this, 'sectionContent' ], 10, 3 );
	}

	public function sectionContent( $currentTab, $currentSection, $currentSettings ): void {
		if ( $currentSection === self::$sectionID ) {
			echo 11155;
		}
	}

	public function addSectionSettings( $sections ) {
		$sections[ self::$sectionID ] = array(
			'title' => __( 'Test', 'woo-assistant' ),
			'desc'  => __( 'Test customization', 'woo-assistant' )
		);

		return $sections;
	}
}