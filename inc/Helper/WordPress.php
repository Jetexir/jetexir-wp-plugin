<?php

namespace WooAssistant\Helper;

class WordPress {
	public static function getPageName(): string {
		if ( self::isHome() ) {
			return 'home';
		} elseif ( self::isBlog() ) {
			return 'blog';
		} elseif ( WooCommerce::isCart() ) {
			return 'cart';
		} elseif ( WooCommerce::isCheckout() ) {
			return 'checkout';
		} elseif ( WooCommerce::isShop() ) {
			return 'shop';
		} elseif ( WooCommerce::isProduct() ) {
			return 'product';
		} elseif ( WooCommerce::isProductCategory() ) {
			return 'product-category';
		} elseif ( WooCommerce::isProductTag() ) {
			return 'product-tag';
		} elseif ( WooCommerce::isProductTaxonomy() ) {
			return 'product-taxonomy';
		} elseif ( self::isSingle() ) {
			return 'single';
		} elseif ( self::isPage() ) {
			return 'single';
		} elseif ( self::isSingular() ) {
			return 'singular';
		} elseif ( self::is404() ) {
			return '404';
		}

		return '';
	}

	public static function getCurrentAction(): string {
		return current_action();
	}

	public static function isAction( $actions ): bool {
		$currentAction = self::getCurrentAction();

		if ( is_array( $actions ) ) {
			return in_array( $currentAction, $actions, true );
		}

		return $currentAction === $actions;
	}

	public static function isRTL(): bool {
		return is_rtl();
	}

	public static function isUserLoggedIn(): bool {
		return is_user_logged_in();
	}

	public static function getCurrentUserID(): int {
		return get_current_user_id();
	}

	public static function blogInfo( $show = '', $filter = 'raw' ) {
		return get_bloginfo( $show, $filter );
	}

	public static function isAjax(): bool {
		return wp_doing_ajax();
	}

	public static function isHome(): bool {
		return ( is_front_page() && is_home() ) || is_front_page();
	}

	public static function isBlog(): bool {
		return ! is_front_page() && is_home();
	}

	public static function isSingle( $post = '' ): bool {
		return is_single( $post );
	}

	public static function isSingular( $postTypes = '' ): bool {
		return is_singular( $postTypes );
	}

	public static function is404(): bool {
		return is_404();
	}

	public static function isPage( $page = '' ): bool {
		return is_page( $page );
	}
}