<?php

namespace WooAssistant\App\Checkout;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Addons\Addon;
use WooAssistant\Interfaces\AddonInterface;

class CheckoutFields extends Addon implements AddonInterface {
	public string $addonID = 'checkout-fields';

	public string $currentTab = 'checkout';

	public function initAction(): void {
		add_filter( 'woocommerce_checkout_fields', [ $this, 'addCustomField' ] );
	}

	public function addCustomField( $fields ): array {
		// Add the custom radio buttons
		$fields['billing']['billing_address_type'] = array(
			'label'    => __( 'Address Type' ),
			'type'     => 'radio',
			'class'    => array( 'form-row-wide', 'address-type' ),
			'required' => true,
			'priority' => 85,
			'options'  => array(
				'home' => __( 'Home (9AM-9PM)', 'woocommerce' ),
				'work' => __( 'Work (9AM-6PM)', 'woocommerce' ),
			),
		);

		return $fields;
	}

	public function addSectionSettings( $sections ): array {
		$sections[ $this->addonID ] = array(
			'title'      => __( 'Fields', 'woo-assistant' ),
			'desc'       => __( 'Checkout fields manager', 'woo-assistant' ),
			'options_id' => $this->addonID,
			'settings'   => array()
		);

		return $sections;
	}

	public function info(): array {
		return array(
			'id'             => $this->addonID,
			'title'          => __( 'Checkout Fields', 'woo-assistant' ),
			'desc'           => __( 'Customize WooCommerce checkout fields', 'woo-assistant' ),
			'tags'           => [ __( 'Checkout', 'woo-assistant' ) ],
			'cat'            => 'checkout',
			'more_info_link' => 'https://parsa.ws'
		);
	}
}