<?php

namespace WooAssistant\Helper;

defined( 'ABSPATH' ) || exit;

class FeedReader {
	private array $args;
	private array $defaultArgs;

	private array $feedItems = [];
	private \WP_Error $error;

	public function __construct( $args ) {
		$this->args = $this->defaultArgs = array(
			'url'          => '',
			'cache_key'    => '',
			'cache_time'   => DAY_IN_SECONDS,
			'items_number' => 10,
			'fields'       => [ 'link', 'title', 'description', 'author', 'datetime' ],
		);
		$this->setArgs( $args );
	}

	public function setArgs( array $args ): void {
		$this->args                 = wp_parse_args( $args, $this->args );
		$this->args['url']          = Validating::isUrl( $this->args['url'] ) ? $this->args['url'] : '';
		$this->args['cache_key']    = empty( $this->args['cache_key'] ) ? 'feed_' . Helper::urlToKey( $this->args['url'] ) : $this->args['cache_key'];
		$this->args['cache_time']   = is_numeric( $this->args['cache_time'] ) ? (int) $this->args['cache_time'] : DAY_IN_SECONDS;
		$this->args['items_number'] = (int) $this->args['items_number'];
		$this->args['fields']       = is_array( $this->args['fields'] ) && ! empty( $this->args['fields'] ) ? $this->args['fields'] : $this->defaultArgs['fields'];
	}

	public function getError(): \WP_Error {
		return $this->error;
	}

	public function getFeedItems(): array {
		return $this->feedItems;
	}

	public function getFeedLinks( $fields = null ): array {
		$fields = $printFields = is_null( $fields ) ? $this->defaultArgs['fields'] : $fields;
		if ( empty( $this->feedItems ) ) {
			return [];
		}

		if ( empty( $printFields ) ) {
			return [];
		}

		$dateFormat = get_option( 'date_format', 'F j, Y' );
		$timeFormat = get_option( 'time_format', 'g:i a' );

		$links = [];
		if ( ( $key = array_search( 'link', $printFields, true ) ) !== false ) {
			unset( $printFields[ $key ] );
		}

		foreach ( $this->feedItems as $item ) {
			$title = '';
			foreach ( $printFields as $field ) {
				if ( ! empty( $item[ $field ] ) && in_array( $field, $this->defaultArgs['fields'], true ) ) {
					$value = $item[ $field ];
					if ( $field === 'datetime' ) {
						$value = wp_date( $dateFormat . ', ' . $timeFormat, strtotime( $value ) );
					}
					$title .= '<span class="wa-feed-' . $field . '">' . $value . '</span>';
				}
			}

			if ( ! empty( $title ) ) {
				if ( in_array( 'link', $fields, true ) ) {
					$links[] = '<a href="' . $item['link'] . '" class="wa-feed-link" target="_blank">' . $title . '</a>';
				} else {
					$links[] = $title;
				}
			}
		}

		return $links;
	}

	/**
	 * @param bool $useCache Use Cache
	 *
	 * @return FeedReader
	 */
	public function read( bool $useCache = true ): FeedReader {
		if ( empty( $this->args['url'] ) ) {
			return $this;
		}

		if ( $useCache ) {
			$feedItems = Cache::get( $this->args['cache_key'] );

			if ( is_array( $feedItems ) ) {
				$this->feedItems = $feedItems;

				return $this;
			}
		}

		$feed = fetch_feed( $this->args['url'] );

		if ( is_wp_error( $feed ) ) {
			$this->error = $feed;

			return $this;
		}

		if ( ! $feed->get_item_quantity() ) {
			$this->error = new \WP_Error( 'feed_empty', __( 'Feed is empty.', 'woo-assistant' ), $this->args );
			$feed->__destruct();
			unset( $feed );

			return $this;
		}

		$itemsNumber = (int) $this->args['items_number'];
		if ( $itemsNumber < 1 || 20 < $itemsNumber ) {
			$itemsNumber = 10;
		}

		$feedItems = [];
		foreach ( $feed->get_items( 0, $itemsNumber ) as $item ) {
			$feedItem = [];
			if ( in_array( 'link', $this->args['fields'], true ) ) {
				$link = $item->get_link();
				while ( ! empty( $link ) && stristr( $link, 'http' ) !== $link ) {
					$link = substr( $link, 1 );
				}
				$feedItem['link'] = esc_url( strip_tags( $link ) );
			}

			if ( in_array( 'title', $this->args['fields'], true ) ) {
				$title = esc_html( trim( strip_tags( $item->get_title() ) ) );
				if ( empty( $title ) ) {
					$title = __( 'Untitled' );
				}

				$feedItem['title'] = $title;
			}

			if ( in_array( 'description', $this->args['fields'], true ) ) {
				$desc                    = html_entity_decode( $item->get_description(), ENT_QUOTES, get_option( 'blog_charset' ) );
				$desc                    = esc_attr( wp_trim_words( $desc, 55, ' [&hellip;]' ) );
				$feedItem['description'] = $desc;
			}

			if ( in_array( 'datetime', $this->args['fields'], true ) ) {
				$feedItem['datetime']  = $item->get_date( 'Y-m-d H:i:s' );
				$feedItem['timestamp'] = $item->get_date( 'U' );
			}

			if ( in_array( 'author', $this->args['fields'], true ) ) {
				$author = $item->get_author();
				if ( is_object( $author ) ) {
					$author = $author->get_name();
					$author = esc_html( strip_tags( $author ) );
				}
				$feedItem['author'] = $author;
			}

			if ( ! empty( $feedItem ) ) {
				$feedItems[] = $feedItem;
			}
		}

		if ( ! empty( $feedItems ) ) {
			Cache::set( $this->args['cache_key'], $feedItems, $this->args['cache_time'] );
			$this->feedItems = $feedItems;
		}

		return $this;
	}
}