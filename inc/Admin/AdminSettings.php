<?php

namespace WooAssistant\Admin;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Helper\Cache;
use WooAssistant\Helper\DebugTrait;
use WooAssistant\Helper\HTML;
use WooAssistant\Helper\Notice;
use WooAssistant\Helper\Param;
use WooAssistant\Helper\Sanitizing;
use WooAssistant\Settings\Settings;

class AdminSettings {
	public function __construct() {
		add_action( 'woo_assistant_submit_settings_form', [ $this, 'saveForm' ] );
	}

	public function saveForm( $tab ): void {
		$settings = self::getSettings( $tab );

		if ( $settings ) {
			$optionsName = $settings['options_id'] ?? null;
			$options     = [];
			$saveFields  = HTML::saveFields;

			if ( self::isSectionMode( $settings ) ) {
				$currentSection = self::getActiveSection( $settings );
				$tabSettings    = $settings['sections'][ $currentSection ]['settings'];
			} else {
				$tabSettings = $settings['settings'];
			}

			if ( is_array( $tabSettings ) ) {
				foreach ( $tabSettings as $setting ) {
					if ( isset( $setting['save'] ) && $setting['save'] === false ) {
						continue;
					}
					$setting['type'] = strtolower( $setting['type'] );
					if ( in_array( $setting['type'], $saveFields, true ) ) {
						$default = ! empty( $setting['default'] ) ? $setting['default'] : null;

						if ( empty( $setting['default'] ) && in_array( $setting['type'], [
								'toggle',
								'checkbox',
								'plugin'
							], true ) ) {
							$default = 0;
						}

						if ( isset( $data['multiple'] ) && $data['multiple'] && empty( $setting['default'] ) && in_array( $setting['type'], [
								'select',
								'taxonomy',
								'posttype'
							] ) ) {
							$default = [];
						}

						$value = Param::post( WOOASSISTANT_INPUT_PREFIX . $setting['id'], $default );

						if ( empty( $setting['sanitize'] ) ) {
							if ( isset( $setting['multiple'] ) && $setting['multiple'] && in_array( $setting['type'], [
									'taxonomy',
									'posttype',
									'select'
								] ) ) {
								$setting['sanitize'] = 'array';

							} else if ( in_array( $setting['type'], [
								'text',
								'search',
								'password',
								'tel',
								'hidden',
								'radio',
								'radioinline',
								'select'
							], true ) ) {
								$setting['sanitize'] = 'text';

							} elseif ( $setting['type'] === 'number' ) {
								$setting['sanitize'] = 'float';

							} elseif ( $setting['type'] === 'email' ) {
								$setting['sanitize'] = 'email';

							} elseif ( $setting['type'] === 'url' ) {
								$setting['sanitize'] = 'url';

							} elseif ( $setting['type'] === 'textarea' ) {
								$setting['sanitize'] = 'textarea';

							} elseif ( $setting['type'] === 'color' ) {
								$setting['sanitize'] = 'color';

							} elseif ( $setting['type'] === 'range' ) {
								$setting['sanitize'] = 'int';

							} elseif ( $setting['type'] === 'posttype' || $setting['type'] === 'taxonomy' ) {
								$setting['sanitize'] = 'absint';

							} elseif ( $setting['type'] === 'plugin' ) {
								$setting['sanitize'] = 'int';
							}
						}

						if ( ! empty( $setting['sanitize'] ) && method_exists( Sanitizing::class, $setting['sanitize'] ) ) {
							$value = Sanitizing::{$setting['sanitize']}( $value );
						}

						if ( is_array( $value ) && isset( $setting['sanitize_options'] ) && method_exists( Sanitizing::class, $setting['sanitize_options'] ) ) {
							$value = array_map( 'WooAssistant\Helper\Sanitizing::' . $setting['sanitize_options'], $value );
						}

						$options[ $setting['id'] ] = $value;
					}
				}
			}

			$options = apply_filters( 'woo_assistant_settings_before_save', $options, $tab );

			$saved = Settings::saves( $options, $optionsName );

			if ( $saved ) {
				Cache::set( 'settings_saved', true, 0 );
				Notice::add( $tab, apply_filters( 'woo_assistant_save_settings_success_message', __( 'Settings saved.', 'woo-assistant' ), $tab ), 'success' );
			} else {
				Notice::add( $tab, apply_filters( 'woo_assistant_save_settings_error_message', __( 'Error saving settings!', 'woo-assistant' ), $tab ), 'error' );
			}
		}
	}

	public static function getSettings( $tab ) {
		return apply_filters( 'woo_assistant_' . $tab . '_settings', [] );
	}

	public static function allSettings( $tab = null ) {
		$settings = apply_filters( 'woo_assistant_settings', [] );

		if ( ! is_null( $tab ) ) {
			return ! empty( $settings[ $tab ] ) ? $settings[ $tab ] : false;
		}

		return $settings;
	}

	public static function printPage( $currentTab, $settings ): void {
		$optionsName = $settings['options_id'] ?? null;
		self::headerSettings( $currentTab, $settings );

		echo '<form method="post" id="wa-settings-form">';
		wp_nonce_field( 'settings_submit_' . $currentTab, '_form_nonce' );

		if ( self::isSectionMode( $settings ) ) {
			$currentSection  = self::getActiveSection( $settings );
			$sectionSettings = $settings['sections'][ $currentSection ]['settings'];

			self::printSettings( $sectionSettings, $optionsName );
		} else {
			self::printSettings( $settings['settings'], $optionsName );
		}

		self::footerSettings( $currentTab );
		echo '</form>';
	}

	private static function printSettings( $settings, $optionsName ): void {
		foreach ( $settings as $key => $field ) {
			if ( ! empty( $field['type'] ) && method_exists( HTML::class, strtolower( $field['type'] ) ) ) {
				if ( isset( $field['force_value'] ) ) {
					$field['setting_value'] = $field['force_value'];
				} else {
					$field['setting_value'] = wp_unslash( Settings::get( $field['id'], $field['default'], $optionsName ) );
				}

				$field['type'] = strtolower( $field['type'] );
				echo HTML::{$field['type']}( $field );
			}
		}
	}

	private static function headerSettings( $currentTab, $settings ): void {
		echo '<header id="wa-settings-header" class="wa-header">';
		echo '<h1>' . $settings['title'] . '</h1>';
		if ( ! empty( $settings['desc'] ) ) {
			echo '<p class="wa-description">' . $settings['desc'] . '</p>';
		}
		echo '<hr />';
		self::printSections( $currentTab, $settings );
		echo '</header>';

		if ( apply_filters( 'woo_assistant_' . $currentTab . '_tab_content_display_notice', false ) ) {
			Notice::display( '*' );
			Notice::display( $currentTab );
		}
	}

	private static function footerSettings( $currentTab ): void {
		if ( ! apply_filters( 'woo_assistant_' . $currentTab . '_settings_display_footer', true ) ) {
			return;
		}

		echo '<footer id="wa-settings-footer" class="wa-footer wa-settings-footer">';

		echo HTML::button( [
			'id'          => 'settings-submit',
			'title'       => apply_filters( 'woo_assistant_settings_submit_button_title', __( 'Save changes', 'woo-assistant' ), $currentTab ),
			'button_type' => 'submit',
			'class'       => 'wa-button-primary',
		] );

		if ( apply_filters( 'woo_assistant_' . $currentTab . '_settings_display_reset_button', true ) ) {
			echo HTML::button( [
				'id'          => 'settings-reset',
				'title'       => apply_filters( 'woo_assistant_settings_reset_button_title', __( 'Discard changes', 'woo-assistant' ), $currentTab ),
				'button_type' => 'reset',
			] );
		}
		echo '</footer>';
	}

	private static function printSections( $currentTab, $settings ): void {
		if ( self::isSectionMode( $settings ) ) {
			$currentSection = self::getActiveSection( $settings );
			$sections       = self::getSections( $settings['sections'] );

			if ( empty( $sections ) ) {
				return;
			}

			echo '<div class="wa-section-links"><ul>';
			foreach ( $sections as $key => $section ) {
				echo '<li>';
				echo '<a href="' . AdminPages::link( [
						'tab'     => $currentTab,
						'section' => $key
					] ) . '" title="' . $section['desc'] . '" class="wa-section-link' . ( $key === $currentSection ? ' wa-section-link-current' : '' ) . '">' . $section['title'] . '</a>';
				echo '</li>';
			}
			echo '</ul>';

			if ( ! empty( $sections[ $currentSection ]['desc'] ) && apply_filters( 'woo_assistant_display_section_description', false, $currentTab, $currentSection ) ) {
				echo '<p class="wa-description">' . $sections[ $currentSection ]['desc'] . '</p>';
			}

			echo '</div><hr />';
		}
	}

	private static function getSections( $sections ): array {
		$sections = array_map( static function ( $section ) {
			if ( empty( $section['title'] ) ) {
				return '';
			}

			return [ 'title' => $section['title'], 'desc' => empty( $section['desc'] ) ? '' : $section['desc'] ];
		}, $sections );

		return array_filter( $sections );
	}

	private static function getActiveSection( $settings ) {
		$sections = array_keys( $settings['sections'] );
		$sections = array_map( 'strtolower', $sections );
		$default  = current( $sections );
		$current  = strtolower( Param::get( 'section', $default ) );

		return in_array( $current, $sections, true ) ? $current : $default;
	}

	private static function isSectionMode( $settings ): bool {
		return is_array( $settings['sections'] ) && ! empty( $settings['sections'] );
	}
}