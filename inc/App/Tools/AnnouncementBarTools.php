<?php

namespace WooAssistant\App\Tools;

use WooAssistant\Addons\Addon;
use WooAssistant\Interfaces\AddonInterface;

class AnnouncementBarTools extends Addon implements AddonInterface {
	public string $addonID = 'announcement-bar-tools';
	private const sectionID = 'announcement-bar';

	public function initAction(): void {
		add_filter( 'woo_assistant_tools_settings_sections', [ $this, 'addSectionSettings' ] );
	}

	public function addSectionSettings( $sections ) {
		$sections[ self::sectionID ] = array(
			'title'    => __( 'Announcement Bar', 'woo-assistant' ),
			//'desc'     => __( 'Announcement Bar', 'woo-assistant' ),
			'settings' => array()
		);

		return $sections;
	}

	public function info(): array {
		return array(
			'id'             => $this->addonID,
			'title'          => __( 'Announcement Bar', 'woo-assistant' ),
			'desc'           => __( 'Promote sales with multiple announcement bar banner types', 'woo-assistant' ),
			'tags'           => [ __( 'Notification', 'woo-assistant' ) ],
			'cat'            => 'customizations',
			'more_info_link' => 'https://parsa.ws'
		);
	}
}