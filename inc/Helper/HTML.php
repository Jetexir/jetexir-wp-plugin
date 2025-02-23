<?php

namespace WooAssistant\Helper;

defined( 'ABSPATH' ) || exit;

use WP_Query;

class HTML {
	private const prefix = 'wa-';
	private const prefixName = WOOASSISTANT_INPUT_PREFIX;

	public const saveFields = [
		'toggle',
		'checkbox',
		'radio',
		'radioinline',
		'textarea',
		'text',
		'password',
		'number',
		'url',
		'email',
		'tel',
		'search',
		'color',
		'range',
		'hidden',
		'select',
		'posttype',
		'taxonomy',

		'addon',
		'wpcolorpicker'
	];
	// FAQ

	private const checkIcon = '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" class="' . self::prefix . 'check-icon">
    <path fill-rule="evenodd" clip-rule="evenodd"
          d="M20 10C20 15.5228 15.5228 20 10 20C4.47715 20 0 15.5228 0 10C0 4.47715 4.47715 0 10 0C15.5228 0 20 4.47715 20 10ZM14.0303 6.96967C14.3232 7.26256 14.3232 7.73744 14.0303 8.0303L9.0303 13.0303C8.7374 13.3232 8.2626 13.3232 7.96967 13.0303L5.96967 11.0303C5.67678 10.7374 5.67678 10.2626 5.96967 9.9697C6.26256 9.6768 6.73744 9.6768 7.03033 9.9697L8.5 11.4393L10.7348 9.2045L12.9697 6.96967C13.2626 6.67678 13.7374 6.67678 14.0303 6.96967Z"
          fill="#873EFF"/></svg>';

	private const crossIcon = '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" class="' . self::prefix . 'cross-icon">
<path fill-rule="evenodd" clip-rule="evenodd" d="M13.5356 12.65C13.78 12.8937 13.78 13.2938 13.5356 13.5375C13.2919 13.7813 12.8956 13.7813 12.6513 13.5375L10.0037 10.8875L7.33749 13.5562C7.09124 13.8 6.69252 13.8 6.44627 13.5562C6.20064 13.3062 6.20064 12.9063 6.44627 12.6625L9.11251 9.99374L6.465 7.35001C6.22062 7.10626 6.22062 6.70623 6.465 6.46248C6.70812 6.21873 7.10437 6.21873 7.34875 6.46248L9.99626 9.11247L12.6825 6.42502C12.9287 6.18127 13.3269 6.18127 13.5731 6.42502C13.8187 6.67502 13.8187 7.06873 13.5731 7.31873L10.8875 10.0063L13.5356 12.65ZM10 0C4.47688 0 0 4.475 0 10C0 15.525 4.47688 20 10 20C15.5231 20 20 15.525 20 10C20 4.475 15.5231 0 10 0Z" fill="#8A8A8A"/>
</svg>';

	private const chevronRightIcon = '<svg width="16px" height="16px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">

<path d="M9 6L15 12L9 18" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>';

	private static function wrap( $field, $data ): string {
		$controlDisabled = isset( $data['attributes']['disabled'] ) && $data['attributes']['disabled'] === 'disabled';

		return '<div class="' . self::getClass( $data, self::prefix . 'field-wrap ' . self::prefix . 'field-' . $data['type'] . ( $controlDisabled ? ' ' . self::prefix . 'control-disabled' : '' ) ) . '"><div class="' . self::prefix . 'field-head">' . $field . '</div>' . ( ! empty( $data['desc'] ) ? '<div class="' . self::prefix . 'description">' . $data['desc'] . '</div>' : '' ) . '</div>';
	}

	public static function inputText( $data ): string {
		if ( ! $data = self::checkData( $data ) ) {
			return '';
		}

		$field = '<label for="' . self::prefix . $data['type'] . '-' . $data['id'] . '" class="' . self::prefix . 'input-label">' . $data['title'] . '</label>' .
		         '<input type="' . $data['type'] . '" name="' . self::prefixName . $data['id'] . '" id="' . self::prefix . $data['type'] . '-' . $data['id'] . '" class="' . self::prefix . 'input-' . $data['type'] . '" value="' . $data['setting_value'] . '" ' . self::getAttributes( $data ) . '>';

		return self::wrap( $field, $data );
	}

	public static function color( $data ): string {
		return self::inputText( $data );
	}

	public static function wpcolorpicker( $data ): string {
		return self::inputText( $data );
	}

	public static function text( $data ): string {
		return self::inputText( $data );
	}

	public static function email( $data ): string {
		return self::inputText( $data );
	}

	public static function tel( $data ): string {
		return self::inputText( $data );
	}

	public static function url( $data ): string {
		return self::inputText( $data );
	}

	public static function number( $data ): string {
		return self::inputText( $data );
	}

	public static function password( $data ): string {
		return self::inputText( $data );
	}

	public static function search( $data ): string {
		return self::inputText( $data );
	}

	public static function select( $data ): string {
		if ( ! $data = self::checkData( $data ) ) {
			return '';
		}

		$name = self::prefixName . $data['id'] . ( isset( $data['attributes']['multiple'] ) && $data['attributes']['multiple'] ? '[]' : '' );

		$field = '<label for="' . self::prefix . $data['type'] . '-' . $data['id'] . '" class="' . self::prefix . 'select-label">' . $data['title'] . '</label>' .
		         '<select name="' . $name . '" id="' . self::prefix . $data['type'] . '-' . $data['id'] . '" class="' . self::prefix . 'input-' . $data['type'] . '" ' . self::getAttributes( $data ) . '>';

		if ( ! empty( $data['option_none'] ) ) {
			$field .= '<option value="' . $data['option_none_value'] . '">' . $data['option_none'] . '</option>';
		}

		if ( ! empty( $data['options'] ) && is_array( $data['options'] ) ) {
			$isList = array_is_list( $data['options'] );

			foreach ( $data['options'] as $key => $value ) {
				$selected = isset( $data['multiple'] ) && $data['multiple'] && is_array( $data['setting_value'] ) ? in_array( ( $isList ? $value : $key ), $data['setting_value'], true ) : $data['setting_value'] == ( $isList ? $value : $key );

				$field .= '<option value="' . ( $isList ? $value : $key ) . '" ' . selected( $selected, true, false ) . '>' . $value . '</option>';
			}
		}

		$field .= '</select>';

		return self::wrap( $field, $data );
	}

	public static function imagesize( $data ): string {
		if ( ! $data = self::checkData( $data ) ) {
			return '';
		}
		$data['options'] = apply_filters( 'woo_assistant_image_sizes_select_items', Assets::getImageSizes() );

		return self::select( $data );
	}

	public static function taxonomy( $data ): string {
		if ( ! $data = self::checkData( $data ) ) {
			return '';
		}

		$defaultArgs     = array(
			'taxonomy'   => 'category',
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		);
		$args            = wp_parse_args( $data['args'], $defaultArgs );
		$args['fields']  = 'all';
		$terms           = get_terms( $args );
		$termIds         = wp_list_pluck( $terms, 'term_id' );
		$termNames       = wp_list_pluck( $terms, 'name' );
		$data['options'] = array_combine( $termIds, $termNames );

		return self::select( $data );
	}

	public static function posttype( $data ): string {
		global $post;
		if ( ! $data = self::checkData( $data ) ) {
			return '';
		}

		$postTemp        = $post;
		$defaultArgs     = array(
			'post_type'              => 'post',
			'post_status'            => 'publish',
			'posts_per_page'         => 50,
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'order'                  => 'DESC',
			'orderby'                => 'date',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);
		$args            = wp_parse_args( $data['args'], $defaultArgs );
		$args['fields']  = 'all';
		$postsQuery      = new WP_Query( $args );
		$postIds         = wp_list_pluck( $postsQuery->posts, 'ID' );
		$postTitles      = wp_list_pluck( $postsQuery->posts, 'post_title' );
		$data['options'] = array_combine( $postIds, $postTitles );

		wp_reset_query();
		$post = $postTemp;

		return self::select( $data );
	}

	public static function range( $data ): string {
		if ( ! $data = self::checkData( $data ) ) {
			return '';
		}
		$field = '<label for="' . self::prefix . $data['type'] . '-' . $data['id'] . '" class="' . self::prefix . 'input-text">' . $data['title'] . '</label>' .
		         '<div class="' . self::prefix . 'range-field-wrap' . '"><input type="' . $data['type'] . '" name="' . self::prefixName . $data['id'] . '" id="' . self::prefix . $data['type'] . '-' . $data['id'] . '" class="' . self::prefix . 'input-' . $data['type'] . '" value="' . $data['setting_value'] . '" ' . self::getAttributes( $data ) . '>';
		if ( isset( $data['display_value'] ) && $data['display_value'] ) {
			$field .= '<output>' . $data['setting_value'] . '</output></div>';
		}

		return self::wrap( $field, $data );
	}

	public static function hidden( $data ): string {
		return '<input type="hidden" name="' . self::prefixName . $data['id'] . '" id="' . self::prefix . $data['type'] . '-' . $data['id'] . '" value="' . $data['setting_value'] . '" >';
	}

	public static function radio( $data ): string {
		if ( ! $data = self::checkData( $data ) ) {
			return '';
		}
		$field = '<label class="' . self::prefix . 'radio-wrap">' .
		         '<input type="radio" name="' . self::prefixName . $data['id'] . '" id="' . self::prefix . $data['type'] . '-' . $data['id'] . '" value="' . $data['value'] . '"  ' . checked( $data['setting_value'] == $data['value'], true, false ) . self::getAttributes( $data ) . '>' .
		         '<span class="' . self::prefix . 'checkmark"></span><span class="' . self::prefix . 'title">' . $data['title'] . '</span></label>';

		return self::wrap( $field, $data );
	}

	public static function radioinline( $data ): string {
		if ( ! $data = self::checkData( $data ) ) {
			return '';
		}

		if ( array_is_list( $data['options'] ) ) {
			$data['options'] = array_combine( $data['options'], $data['options'] );
		}

		$field = self::startradiogroup( $data );

		foreach ( $data['options'] as $key => $value ) {
			$field .= '<label class="' . self::prefix . 'radio-inline">' .
			          '<input type="radio" name="' . self::prefixName . $data['id'] . '" id="' . self::prefix . $data['type'] . '-' . $data['id'] . '" value="' . $key . '"  ' . checked( $data['setting_value'] == $key, true, false ) . self::getAttributes( $data ) . '>' .
			          '<span class="' . self::prefix . 'checkmark"></span><span class="' . self::prefix . 'title">' . $value . '</span></label>';
		}

		$field .= self::endradiogroup( $data );

		return $field;
	}

	public static function checkbox( $data ): string {
		if ( ! $data = self::checkData( $data ) ) {
			return '';
		}
		$field = '<label class="' . self::prefix . 'checkbox-wrap">' .
		         '<input type="checkbox" name="' . self::prefixName . $data['id'] . '" id="' . self::prefix . $data['type'] . '-' . $data['id'] . '" value="' . $data['value'] . '"  ' . checked( $data['setting_value'] == $data['value'], true, false ) . self::getAttributes( $data ) . '>' .
		         '<span class="' . self::prefix . 'checkmark"></span><span class="' . self::prefix . 'title">' . $data['title'] . '</span></label>';

		return self::wrap( $field, $data );
	}

	public static function toggle( $data ): string {
		if ( ! $data = self::checkData( $data ) ) {
			return '';
		}

		$field = '<label class="' . self::prefix . 'toggle">' .
		         '<input type="checkbox" name="' . self::prefixName . $data['id'] . '" id="' . self::prefix . 'toggle-' . $data['id'] . '" value="' . $data['value'] . '" ' . checked( $data['setting_value'] == $data['value'], true, false ) . self::getAttributes( $data ) . '>' .
		         '<span class="' . self::prefix . 'toggle-slider" type="button"><span class="' . self::prefix . 'toggle-handle">' . self::checkIcon . self::crossIcon . '</span></span></label>' .
		         '<label for="' . self::prefix . 'toggle-' . $data['id'] . '" class="' . self::prefix . 'input-title">' . $data['title'] . '</label>';

		return self::wrap( $field, $data );
	}

	public static function button( $data ): string {
		if ( ! $data = self::checkData( $data ) ) {
			return '';
		}

		return '<button id="' . self::prefix . $data['id'] . '-button" class="' . self::getClass( $data, self::prefix . 'button ' . self::prefix . 'button-' . $data['button_type'] ) . '" type="' . $data['button_type'] . '">' . $data['title'] . '</button>';
	}

	public static function hr(): string {
		return '<hr />';
	}

	public static function startradiogroup( $data ): string {
		return '<fieldset id="' . self::prefix . $data['id'] . '-radio-group" class="' . self::prefix . 'radio-group ' . ( ! empty( $data['class'] ) ? ' ' . $data['class'] : '' ) . '"><legend class="' . self::prefix . 'title">' . $data['title'] . '</legend><div class="' . self::prefix . 'radio-group-options">';
	}

	public static function endradiogroup( $data ): string {
		return '</div></fieldset>';
	}

	public static function startgrid( $data ): string {
		if ( ! isset( $data['cols'] ) ) {
			$data['cols'] = 2;
		}

		return '<div class="' . self::prefix . 'grid ' . self::prefix . 'grid-cols-' . $data['cols'] . ( ! empty( $data['class'] ) ? ' ' . $data['class'] : '' ) . '"><div class="' . self::prefix . 'title">' . $data['title'] . '</div><div class="' . self::prefix . 'fields-wrap">';
	}

	public static function endgrid( $data ): string {
		return '</div></div>';
	}

	public static function h2( $data ): string {
		return '<h2 class="' . self::prefix . 'heading-2">' . $data['title'] . '</h2>';
	}

	public static function addon( $data ): string {
		if ( ! $data = self::checkData( $data ) ) {
			return '';
		}

		$canActivate = isset( $data['can_activate'] ) && $data['can_activate'];
		$class       = ' ' . ( $data['class'] ?? '' );
		if ( ! $canActivate ) {
			$class .= ' ' . self::prefix . 'addon-inactive';
		}

		$addon = '<div class="' . self::prefix . 'addon-wrap' . $class . '">' .
		         '<div class="' . self::prefix . 'image-wrap">';

		if ( is_array( $data['tags'] ) && ! empty( $data['tags'] ) && is_string( $data['tags'][0] ) ) {
			$addon .= '<span class="' . self::prefix . 'tag">' . $data['tags'][0] . '</span>';
		}

		$image = '';
		if ( ! empty( $data['icon'] ) ) {
			$image = $data['icon'];
		} elseif ( ! empty( $data['image'] ) ) {
			$image = self::image( [ 'src' => $data['image'] ] );
		}

		if ( ! empty( $image ) ) {
			if ( ! empty( $data['image_link'] ) ) {
				$addon .= '<a href="' . $data['image_link'] . '" target="_blank" class="' . self::prefix . 'image-link">' . $image . '</a>';
			} else {
				$addon .= $image;
			}
		}


		$addon .= '</div><div class="' . self::prefix . 'title-desc"><h3 class="' . self::prefix . 'title">' . $data['title'] . '</h3>' .
		          ( ! empty( $data['desc'] ) ? '<p class="' . self::prefix . 'desc">' . $data['desc'] . '</p>' : '' ) .
		          ( ! empty( $data['more_info_link'] ) ? '<a href="' . $data['more_info_link'] . '" target="_blank" class="' . self::prefix . 'more-info-link">' . self::chevronRightIcon . __( 'More info' ) . '</a>' : '' ) .
		          '</div><div class="' . self::prefix . 'action-wrap">';

		if ( $canActivate ) {
			$addon .= self::toggle( array(
				'id'            => $data['id'],
				'type'          => 'toggle',
				'title'         => $data['action_title'],
				'value'         => $data['force_enable'] ?: $data['value'],
				'setting_value' => $data['force_enable'] ?: $data['setting_value'],
				'attributes'    => $data['force_enable'] ? [ 'disabled' => 'disabled' ] : [],
			) );

		} else if ( ! empty( $data['action_link'] ) ) {
			$addon .= '<a href="' . $data['action_link'] . '" ' . ( $data['action_link_external'] ? 'target="_blank"' : '' ) . ' class="' . self::prefix . 'action-link">' . $data['action_title'] . '</a>';

		} else {

		}

		$addon .= '</div></div>';

		return $addon;
	}

	public static function startaddons( $data ): string {
		return '<div class="' . self::prefix . 'addons-wrap' . ( ! empty( $data['class'] ) ? ' ' . $data['class'] : '' ) . '">' . self::h2( $data ) . '<div class="' . self::prefix . 'addons-grid">';
	}

	public static function endaddons( $data ): string {
		return '</div></div>';
	}

	public static function notice( $data ): string {
		if ( ! $data = self::checkData( $data ) ) {
			return '';
		}

		$key = 'notice_element_' . $data['id'];
		Notice::clear( $key );
		foreach ( $data['notices'] as $notice ) {
			Notice::add( $key, $notice['message'], $notice['type'] );
		}

		return Notice::display( $key, null, false );
	}

	public static function paragraph( $data ): string {
		if ( ! $data = self::checkData( $data ) ) {
			return '';
		}

		$class = self::getClass( $data, self::prefix . 'paragraph-wrap' );

		return '<p class="' . $class . '">' . $data['text'] . '</p>';
	}

	public static function image( $data ): string {
		if ( ! $data = self::checkData( $data ) ) {
			return '';
		}

		return '<img src="' . $data['src'] . '" class="' . self::prefix . 'image' . ( ! empty( $data['class'] ) ? ' ' . $data['class'] : '' ) . '" ' . self::getAttributes( $data ) . '>';
	}

	/**
	 * Check input data
	 *
	 * @param array $data
	 *
	 * @return bool|array
	 */
	private static function checkData( array $data ) {
		$attributes = empty( $data['attributes'] ) || ! is_array( $data['attributes'] ) ? [] : $data['attributes'];

		if ( ( empty( $data['id'] ) || empty( $data['title'] ) ) && in_array( $data['type'], self::saveFields, true ) ) {
			return false;
		}
		if ( $data['type'] === 'image' && filter_var( $data['src'], FILTER_VALIDATE_URL ) ) {
			return false;
		}

		if ( $data['type'] === 'notice' && ( empty( $data['id'] ) || empty( $data['notices'] ) || ! is_array( $data['notices'] ) ) ) {
			return false;
		}

		if ( isset( $data['setting_value'] ) && ( is_string( $data['setting_value'] ) || is_numeric( $data['setting_value'] ) ) ) {
			$settingValue = html_entity_decode( $data['setting_value'] );
			if ( mb_strlen( $settingValue ) !== mb_strlen( $data['setting_value'] ) ) {
				$data['setting_value'] = $settingValue;
			}
		}
		if ( isset( $data['multiple'] ) && $data['multiple'] ) {
			$attributes['multiple'] = 'multiple';
		}
		if ( isset( $data['disabled'] ) && $data['disabled'] ) {
			$attributes['disabled'] = 'disabled';
		}
		if ( isset( $data['readonly'] ) && $data['readonly'] ) {
			$attributes['readonly'] = 'readonly';
		}
		if ( isset( $data['required'] ) && $data['required'] ) {
			$attributes['required'] = 'required';
		}
		if ( ! empty( $data['placeholder'] ) ) {
			$attributes['placeholder'] = $data['placeholder'];
		}
		if ( $data['type'] === 'range' && isset( $data['display_value'] ) && $data['display_value'] ) {
			$attributes['oninput'] = 'this.nextElementSibling.value = this.value';
		}
		if ( ! empty( $data['option_none'] ) && ! isset( $data['option_none_value'] ) ) {
			$data['option_none_value'] = '';
		}
		if ( $data['type'] === 'radioinline' && ( ! isset( $data['options'] ) || ! is_array( $data['options'] ) ) ) {
			$data['options'] = array();
		}
		if ( $data['type'] === 'select' && ( ! isset( $data['options'] ) || ! is_array( $data['options'] ) ) ) {
			$data['options'] = array();
		}
		if ( $data['type'] === 'posttype' && ( ! isset( $data['args'] ) || ! is_array( $data['args'] ) ) ) {
			$data['args'] = array();
		}
		if ( $data['type'] === 'taxonomy' && ( ! isset( $data['args'] ) || ! is_array( $data['args'] ) ) ) {
			$data['args'] = array();
		}
		if ( $data['type'] === 'wpcolorpicker' ) {
			$data['class'] = self::getClass( $data, self::prefix . 'wp-color-picker' );
			$data['type']  = 'text';
		}
		$data['attributes'] = $attributes;

		return $data;
	}

	private static function getAttributes( $data ): string {
		$attributes = '';

		if ( ! empty( $data['attributes'] ) ) {
			if ( is_array( $data['attributes'] ) ) {
				foreach ( $data['attributes'] as $key => $value ) {
					$attributes .= ' ' . $key . '="' . $value . '"';
				}
			} else {
				//$attributes .= ' ' . $data['attributes'];
			}
		}

		return $attributes;
	}

	private static function getClass( $data, $default = '' ): string {
		$class = $default;

		if ( ! empty( $data['class'] ) ) {
			$class .= ' ' . ( is_array( $data['class'] ) ? implode( ' ', $data['class'] ) : $data['class'] );
		}

		return $class;
	}
}