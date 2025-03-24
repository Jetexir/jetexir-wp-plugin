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
		'checkboxinline',
		'textarea',
		'text',
		'password',
		'number',
		'url',
		'email',
		'tel',
		'search',
		'color',
		'colorpalette',
		'range',
		'hidden',
		'select',
		'posttypeselect',
		'postselect',
		'taxonomyselect',
		'termselect',
		'imagesizeselect',
		'userroleselect',
		'userselect',
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
		if ( isset( $data['wrap'] ) && ! $data['wrap'] ) {
			return $field;
		}

		$controlDisabled = isset( $data['attributes']['disabled'] ) && $data['attributes']['disabled'] === 'disabled';

		return '<div class="' . self::getClass( $data, self::prefix . 'field-wrap ' . self::prefix . 'field-' . $data['type'] . ( $controlDisabled ? ' ' . self::prefix . 'control-disabled' : '' ) ) . '"><div class="' . self::prefix . 'field-head">' . $field . '</div>' . ( ! empty( $data['desc'] ) ? '<div class="' . self::prefix . 'description">' . $data['desc'] . '</div>' : '' ) . '</div>';
	}

	public static function textarea( $data ): string {
		if ( ! $data = self::checkData( $data ) ) {
			return '';
		}

		$id   = self::prefix . $data['type'] . '-' . $data['id'];
		$name = self::prefixName . $data['id'];

		if ( isset( $data['is_repeatable'] ) && $data['is_repeatable'] ) {
			$name .= '[]';
			$id   = '';
		}
		$field = '';

		if ( ! empty( $data['title'] ) ) {
			$field .= '<label for="' . $id . '" class="' . self::prefix . 'input-label">' . $data['title'] . '</label>';
		}

		$field .= '<textarea name="' . $name . '" id="' . $id . '" class="' . self::getClass( $data, self::prefix . 'field-textarea' ) . '" ' . self::getAttributes( $data ) . '>' . $data['setting_value'] . '</textarea>';

		return self::wrap( $field, $data );
	}

	public static function inputText( $data ): string {
		if ( ! $data = self::checkData( $data ) ) {
			return '';
		}

		$id   = self::prefix . $data['type'] . '-' . $data['id'];
		$name = self::prefixName . $data['id'];

		if (
			( isset( $data['is_repeatable'] ) && $data['is_repeatable'] ) ||
			( isset( $data['is_multiple'] ) )
		) {
			$name .= isset( $data['is_multiple'] ) && is_numeric( $data['is_multiple'] ) ? '[' . $data['is_multiple'] . ']' : '[]';
			$id   = '';
		}

		$field = '';

		if ( ! empty( $data['title'] ) ) {
			$field .= '<label for="' . $id . '" class="' . self::prefix . 'input-label">' . $data['title'] . '</label>';
		}

		$field .= '<input type="' . $data['type'] . '" name="' . $name . '" id="' . $id . '" class="' . self::prefix . 'input-' . $data['type'] . '" value="' . $data['setting_value'] . '" ' . self::getAttributes( $data ) . '>';

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

		$name  = self::prefixName . $data['id'] . ( isset( $data['attributes']['multiple'] ) && $data['attributes']['multiple'] ? '[]' : '' );
		$field = '';

		if ( ! empty( $data['title'] ) ) {
			$field .= '<label for="' . self::prefix . $data['type'] . '-' . $data['id'] . '" class="' . self::prefix . 'select-label">' . $data['title'] . '</label>';
		}

		$field .= '<select name="' . $name . '" id="' . self::prefix . $data['type'] . '-' . $data['id'] . '" class="' . self::prefix . 'input-' . $data['type'] . '" ' . self::getAttributes( $data ) . '>';

		if ( ! empty( $data['option_none'] ) ) {
			$field .= '<option value="' . $data['option_none_value'] . '">-- ' . $data['option_none'] . ' --</option>';
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

	public static function imagesizeselect( $data ): string {
		if ( ! $data = self::checkData( $data ) ) {
			return '';
		}
		$data['options'] = apply_filters( 'woo_assistant_image_sizes_select_items', Assets::getImageSizes() );

		return self::select( $data );
	}

	public static function userselect( $data ): string {
		if ( ! $data = self::checkData( $data ) ) {
			return '';
		}

		$userList       = [];
		$defaultArgs    = array(
			'number'      => 50,
			'count_total' => false
		);
		$args           = wp_parse_args( $data['args'], $defaultArgs );
		$args['fields'] = [ 'ID', 'display_name', 'user_login', 'user_email' ];
		$users          = get_users( $args );
		if ( ! empty( $users ) && is_array( $users ) ) {
			foreach ( $users as $user ) {
				$userList[ $user->ID ] = $user->display_name . ' (' . $user->ID . ', ' . $user->user_login . ', ' . $user->user_email . ')';
			}
		}
		$data['options'] = $userList;

		return self::select( $data );
	}

	public static function userroleselect( $data ): string {
		if ( ! $data = self::checkData( $data ) ) {
			return '';
		}

		$roles           = get_editable_roles();
		$roleNames       = array_keys( $roles );
		$roleLabels      = wp_list_pluck( $roles, 'name' );
		$data['options'] = array_combine( $roleNames, $roleLabels );

		return self::select( $data );
	}

	public static function taxonomyselect( $data ): string {
		if ( ! $data = self::checkData( $data ) ) {
			return '';
		}

		$defaultArgs     = array( 'public' => true );
		$args            = wp_parse_args( $data['args'], $defaultArgs );
		$taxonomies      = get_taxonomies( $args, 'objects' );
		$taxonomyNames   = wp_list_pluck( $taxonomies, 'name' );
		$taxonomyLabels  = wp_list_pluck( $taxonomies, 'label' );
		$data['options'] = array_combine( $taxonomyNames, $taxonomyLabels );

		return self::select( $data );
	}

	public static function termselect( $data ): string {
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

	public static function posttypeselect( $data ): string {
		if ( ! $data = self::checkData( $data ) ) {
			return '';
		}

		$defaultArgs     = array( 'public' => true );
		$args            = wp_parse_args( $data['args'], $defaultArgs );
		$postTypes       = get_post_types( $args, 'objects' );
		$postTypeNames   = wp_list_pluck( $postTypes, 'name' );
		$postTypeLabels  = wp_list_pluck( $postTypes, 'label' );
		$data['options'] = array_combine( $postTypeNames, $postTypeLabels );

		return self::select( $data );
	}

	public static function postselect( $data ): string {
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

		$field = '';

		if ( ! empty( $data['title'] ) ) {
			$field .= '<label for="' . self::prefix . $data['type'] . '-' . $data['id'] . '" class="' . self::prefix . 'input-text">' . $data['title'] . '</label>';
		}

		$field .= '<div class="' . self::prefix . 'range-field-wrap' . '"><input type="' . $data['type'] . '" name="' . self::prefixName . $data['id'] . '" id="' . self::prefix . $data['type'] . '-' . $data['id'] . '" class="' . self::getClass( $data, self::prefix . 'input-' . $data['type'] ) . '" value="' . $data['setting_value'] . '" ' . self::getAttributes( $data ) . '>';

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

		$field = self::startinlineelements( $data );

		foreach ( $data['options'] as $key => $value ) {
			$field .= '<label class="' . self::prefix . 'radio-inline">' .
			          '<input type="radio" name="' . self::prefixName . $data['id'] . '" id="' . self::prefix . $data['type'] . '-' . $data['id'] . '" value="' . $key . '"  ' . checked( $data['setting_value'] == $key, true, false ) . self::getAttributes( $data ) . '>' .
			          '<span class="' . self::prefix . 'checkmark"></span><span class="' . self::prefix . 'title">' . $value . '</span></label>';
		}

		$field .= self::endinlineelements( $data );

		return $field;
	}

	public static function checkboxinline( $data ): string {
		if ( ! $data = self::checkData( $data ) ) {
			return '';
		}

		$isList = array_is_list( $data['options'] );
		if ( $isList ) {
			$data['options'] = array_combine( $data['options'], $data['options'] );
		}

		$field = self::startinlineelements( $data );

		$labelClass = self::prefix . 'checkbox-inline' . ( isset( $data['not_equal'] ) && $data['not_equal'] ? ' wa-not-equal' : '' );
		foreach ( $data['options'] as $key => $value ) {
			$checked = is_array( $data['setting_value'] ) ? in_array( ( $isList ? $value : $key ), $data['setting_value'], true ) : $data['setting_value'] == ( $isList ? $value : $key );

			$field .= '<label class="' . $labelClass . '">' .
			          '<input type="checkbox" name="' . self::prefixName . $data['id'] . '[]" id="' . self::prefix . $data['type'] . '-' . $data['id'] . '" value="' . $key . '"  ' . checked( $checked, true, false ) . self::getAttributes( $data ) . '>' .
			          '<span class="' . self::prefix . 'checkmark"></span><span class="' . self::prefix . 'title">' . $value . '</span></label>';
		}

		$field .= self::endinlineelements( $data );

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

	public static function startinlineelements( $data ): string {
		if ( $data['type'] === 'startinlineelements' ) {
			$type = 'inline-elements';
		} else if ( $data['type'] === 'radioinline' ) {
			$type = 'radio';
		} else {
			$type = 'checkbox';
		}

		return '<fieldset id="' . self::prefix . ( empty( $data['id'] ) ? '' : $data['id'] . '-' ) . $type . '-group" class="' . self::getClass( $data, self::prefix . $type . '-group' ) . '"><legend class="' . self::prefix . 'title">' . $data['title'] . '</legend><div class="' . self::prefix . $type . '-group-options">';
	}

	public static function endinlineelements( $data ): string {
		return '</div></fieldset>';
	}

	public static function startrepeatable( $data ): string {
		if ( ! $data = self::checkData( $data ) ) {
			return '';
		}

		$addRepeat = '<a href="#" class="' . self::prefix . 'add-repeatable" data-position="start"><svg width="24px" height="24px" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
    <g transform="translate(-464.000000, -1087.000000)">
        <path d="M480,1117 C472.268,1117 466,1110.73 466,1103 C466,1095.27 472.268,1089 480,1089 C487.732,1089 494,1095.27 494,1103 C494,1110.73 487.732,1117 480,1117 L480,1117 Z M480,1087 C471.163,1087 464,1094.16 464,1103 C464,1111.84 471.163,1119 480,1119 C488.837,1119 496,1111.84 496,1103 C496,1094.16 488.837,1087 480,1087 L480,1087 Z M486,1102 L481,1102 L481,1097 C481,1096.45 480.553,1096 480,1096 C479.447,1096 479,1096.45 479,1097 L479,1102 L474,1102 C473.447,1102 473,1102.45 473,1103 C473,1103.55 473.447,1104 474,1104 L479,1104 L479,1109 C479,1109.55 479.447,1110 480,1110 C480.553,1110 481,1109.55 481,1109 L481,1104 L486,1104 C486.553,1104 487,1103.55 487,1103 C487,1102.45 486.553,1102 486,1102 L486,1102 Z">
        </path>
    </g>
</svg></a>';

		return '<div class="' . self::prefix . 'repeatable ' . ( ! empty( $data['class'] ) ? ' ' . $data['class'] : '' ) . '" ' . self::getAttributes( $data ) . '>' .
		       '<div class="' . self::prefix . 'title">' . $data['title'] . $addRepeat . '</div>' .
		       '<div class="' . self::prefix . 'repeatable-wrap">';
	}

	public static function endrepeatable( $data ): string {
		if ( ! $data = self::checkData( $data ) ) {
			return '';
		}

		$addText   = ! empty( $data['add_text'] ) ? ' ' . $data['add_text'] : '';
		$addRepeat = '<a href="#" class="' . self::prefix . 'add-repeatable" data-position="end"><svg width="24px" height="24px" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
    <g transform="translate(-464.000000, -1087.000000)">
        <path d="M480,1117 C472.268,1117 466,1110.73 466,1103 C466,1095.27 472.268,1089 480,1089 C487.732,1089 494,1095.27 494,1103 C494,1110.73 487.732,1117 480,1117 L480,1117 Z M480,1087 C471.163,1087 464,1094.16 464,1103 C464,1111.84 471.163,1119 480,1119 C488.837,1119 496,1111.84 496,1103 C496,1094.16 488.837,1087 480,1087 L480,1087 Z M486,1102 L481,1102 L481,1097 C481,1096.45 480.553,1096 480,1096 C479.447,1096 479,1096.45 479,1097 L479,1102 L474,1102 C473.447,1102 473,1102.45 473,1103 C473,1103.55 473.447,1104 474,1104 L479,1104 L479,1109 C479,1109.55 479.447,1110 480,1110 C480.553,1110 481,1109.55 481,1109 L481,1104 L486,1104 C486.553,1104 487,1103.55 487,1103 C487,1102.45 486.553,1102 486,1102 L486,1102 Z">
        </path>
    </g>
</svg>' . $addText . '</a>';

		return '</div>' . $addRepeat . '</div>';
	}

	public static function startrepeatableelements( $data ): string {
		if ( ! $data = self::checkData( $data ) ) {
			return '';
		}

		$data         = wp_parse_args( $data, [ 'move_action' => true ] );
		$moveUpRepeat = $moveDownRepeat = '';

		if ( $data['move_action'] ) {
			$moveUpRepeat   = '<a href="#" class="' . self::prefix . 'move-up-repeatable"><svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M6 15L12 9L18 15" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg></a>';
			$moveDownRepeat = '<a href="#" class="' . self::prefix . 'move-down-repeatable"><svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M6 9L12 15L18 9" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg></a>';
		}

		$removeRepeat = '<a href="#" class="' . self::prefix . 'remove-repeatable"><svg width="24px" height="24px" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg">
    <g id="Page-1" stroke="none" stroke-width="1" fill-rule="evenodd">
        <g transform="translate(-516.000000, -1087.000000)">
            <path d="M532,1117 C524.268,1117 518,1110.73 518,1103 C518,1095.27 524.268,1089 532,1089 C539.732,1089 546,1095.27 546,1103 C546,1110.73 539.732,1117 532,1117 L532,1117 Z M532,1087 C523.163,1087 516,1094.16 516,1103 C516,1111.84 523.163,1119 532,1119 C540.837,1119 548,1111.84 548,1103 C548,1094.16 540.837,1087 532,1087 L532,1087 Z M538,1102 L526,1102 C525.447,1102 525,1102.45 525,1103 C525,1103.55 525.447,1104 526,1104 L538,1104 C538.553,1104 539,1103.55 539,1103 C539,1102.45 538.553,1102 538,1102 L538,1102 Z">
            </path>
        </g>
    </g>
</svg></a>';

		return '<div class="' . self::prefix . 'repeatable-fields-wrap" data-repeat-title="' . ( $data['title'] ?? '' ) . '">' .
		       '<div class="' . self::prefix . 'repeatable-actions">' . $moveUpRepeat . $moveDownRepeat . $removeRepeat . '</div>';
	}

	public static function endrepeatableelements( $data ): string {
		return '</div>';
	}

	public static function startgrid( $data ): string {
		if ( ! isset( $data['cols'] ) ) {
			$data['cols'] = 2;
		}

		return '<div class="' . self::prefix . 'grid ' . self::prefix . 'grid-cols-' . $data['cols'] . ( ! empty( $data['class'] ) ? ' ' . $data['class'] : '' ) . '">' .
		       '<div class="' . self::prefix . 'title">' . $data['title'] . '</div>' .
		       '<div class="' . self::prefix . 'fields-wrap">';
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

		return Notice::addAndDisplay( $key, $data['notices'], false );
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

		return '<img src="' . $data['src'] . '" class="' . self::getClass( $data, self::prefix . 'image' ) . '" ' . self::getAttributes( $data ) . '>';
	}

	public static function colorpalette( $data ) {
		if ( ! $data = self::checkData( $data ) ) {
			return '';
		}
		$id    = self::prefix . $data['type'] . '-' . $data['id'];
		$field = '';
		if ( ! empty( $data['title'] ) ) {
			$field .= '<label for="' . $id . '" class="' . self::prefix . 'input-label">' . $data['title'] . '</label>';
		}

		$colors = is_array( $data['setting_value'] ) ? $data['setting_value'] : [];

		$field .= '<div class="' . self::getClass( $data, self::prefix . 'color-palette ' . self::prefix . 'color-palette-' . $data['mode'] ) . '" ' . self::getAttributes( $data ) . '>';
		if ( count( $colors ) ) {
			$field .= '<div class="' . self::prefix . 'color-palette-items' . '">';
			foreach ( $colors as $i => $color ) {
				$field .= self::wpcolorpicker( array(
					'id'            => $data['id'],
					'type'          => 'wpcolorpicker',
					'default'       => '#ffffff',
					'setting_value' => $color,
					'is_multiple'   => $i,
					'wrap'          => false
				) );
			}
			$field .= '</div>';
		}

		if ( $data['addable'] ) {
			$addText = ! empty( $data['add_text'] ) ? ' ' . $data['add_text'] : '';
			$field   .= '<div class="' . self::prefix . 'add-color-wrap"><a href="#" class="' . self::prefix . 'add-color" ' . ( count( $colors ) >= $data['max_items'] ? 'disable="true"' : '' ) . '><i class="wa-icon-plus-circle"></i>' . $addText . '</a></div>';
		}

		$field .= '</div>';

		return self::wrap( $field, $data );
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

		if ( ! in_array( $data['type'], self::saveFields, true ) ) {
			if ( $data['type'] === 'image' && filter_var( $data['src'], FILTER_VALIDATE_URL ) ) {
				return false;
			}
			if ( $data['type'] === 'notice' && ( empty( $data['id'] ) || empty( $data['notices'] ) || ! is_array( $data['notices'] ) ) ) {
				return false;
			}
			if ( $data['type'] === 'startrepeatable' ) {
				if ( ! empty( $data['max_repeat'] ) ) {
					$attributes['data-max-repeat'] = (int) $data['max_repeat'];
				}
			}
			if ( $data['type'] === 'startrepeatableelements' ) {
				if ( isset( $data['move_action'] ) ) {
					$data['move_action'] = Sanitizing::bool( $data['move_action'] );
				}
			}

			$data['attributes'] = $attributes;

			return $data;
		}

		if ( ! isset( $data['wrap'] ) ) {
			$data['wrap'] = true;
		}

		if ( empty( $data['id'] ) && in_array( $data['type'], self::saveFields, true ) ) {
			return false;
		}
		$default = $data['default'] ?? '';
		if ( is_array( $default ) ) {
			$default = JSON::encode( $default );
		}
		$attributes['data-default'] = $default;

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
		if ( in_array( $data['type'], [ 'select', 'radioinline', 'checkboxinline', 'colorpalette' ] )
		     && ( ! isset( $data['options'] ) || ! is_array( $data['options'] ) ) ) {
			$data['options'] = array();
		}

		if ( $data['type'] === 'colorpalette' ) {
			if ( ! isset( $data['addable'] ) ) {
				$data['addable'] = false;
			}

			if ( ! isset( $data['removable'] ) ) {
				$data['removable'] = $data['addable'];
			}

			if ( ! isset( $data['mode'] ) || ! in_array( $data['mode'], [ 'vertical', 'horizontal' ], true ) ) {
				$data['mode'] = 'horizontal';
			}

			$attributes['data-addable']   = (int) $data['addable'];
			$attributes['data-removable'] = (int) $data['removable'];
			$attributes['data-mode']      = $data['mode'];

			if ( ! isset( $data['max_items'] ) ) {
				$data['max_items'] = 20;
			}

			if ( ! empty( $data['max_items'] ) ) {
				$attributes['data-max-items'] = (int) $data['max_items'];
			}
		}

		if ( in_array( $data['type'], [
				'userselect',
				'userroleselect',
				'posttypeselect',
				'postselect',
				'taxonomyselect',
				'termselect',
			] ) && ( ! isset( $data['args'] ) || ! is_array( $data['args'] ) ) ) {
			$data['args'] = array();
		}

		if ( $data['type'] === 'wpcolorpicker' ) {
			$data['class'] = self::getClass( $data, self::prefix . 'wp-color-picker' );
			$data['type']  = 'text';
		}
		$data['attributes'] = $attributes;

		return $data;
	}

	public static function getAttributes( $data, $default = [] ): string {
		$attributes = '';

		if ( is_array( $data['attributes'] ) && is_array( $default ) ) {
			$data['attributes'] = array_merge( $default, $data['attributes'] );
		}

		if ( ! empty( $data['attributes'] ) && is_array( $data['attributes'] ) ) {
			foreach ( $data['attributes'] as $key => $value ) {
				$attributes .= ' ' . $key . '="' . $value . '"';
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