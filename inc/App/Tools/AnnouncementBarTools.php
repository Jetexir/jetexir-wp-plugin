<?php

namespace AssistantForWooCommerce\App\Tools;

defined( 'ABSPATH' ) || exit;

use AssistantForWooCommerce\Addons\Addon;
use AssistantForWooCommerce\App\App;
use AssistantForWooCommerce\Helper\Assets;
use AssistantForWooCommerce\Helper\Helper;
use AssistantForWooCommerce\Helper\HTML;
use AssistantForWooCommerce\Helper\Notice;
use AssistantForWooCommerce\Helper\Param;
use AssistantForWooCommerce\Helper\Sanitizing;
use AssistantForWooCommerce\Helper\Templates;
use AssistantForWooCommerce\Helper\WordPress;
use AssistantForWooCommerce\Interfaces\AddonInterface;
use AssistantForWooCommerce\Providers\UI\DataTableUI;

class AnnouncementBarTools extends Addon implements AddonInterface {
	public string $addonID = 'announcement-bar-tools';
	public string $currentTab = 'tools';
	public string $currentSection = 'announcement-bar';
	private const shortCode = 'asfowoo_announcement_bar';

	public function initAction(): void {
		App::addShortcode( self::shortCode, [ $this, 'announcementBarShortcode' ] );
		add_action( 'assistant_for_woocommerce_data_table_ui_announcement_bar_action', [
			$this,
			'dataTableActions'
		], 10, 2 );
		add_filter( 'assistant_for_woocommerce_tools_settings_display_footer', [
			$this,
			'displayFooterSettings'
		], 10, 2 );
	}

	public function announcementBarShortcode( $atts ): string {
		$atts = shortcode_atts( array(
			'code' => '',
		), $atts, self::shortCode );

		if ( empty( $atts['code'] ) ) {
			return '';
		}

		$announcement = $this->getAnnouncementByCode( $atts['code'] );
		if ( ! $announcement ) {
			return '';
		}

		return $this->getAnnouncement( $announcement, false );
	}

	public function wpBodyOpenAction(): void {
		$announcements = $this->getAnnouncements( [ 'top', 'sticky-top' ] );

		foreach ( $announcements as $announcement ) {
			if ( $this->checkDisplay( $announcement ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $this->getAnnouncement( $announcement );
			}
		}
	}

	public function wpFooterAction(): void {
		$announcements = $this->getAnnouncements( 'sticky-bottom' );

		foreach ( $announcements as $announcement ) {
			if ( $this->checkDisplay( $announcement ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $this->getAnnouncement( $announcement );
			}
		}
	}

	private function checkDisplay( $announcement ): bool {
		$postIds   = $announcement['post_ids'] ?? '';
		$displayOn = $announcement['display_on'] ?? [];

		$display = false;

		if ( in_array( 'all', $displayOn, true ) ) {
			$display = true;
		}

		if ( ! $display && in_array( WordPress::getPageName(), $displayOn, true ) ) {
			$display = true;
		}

		if ( ! $display && ! empty( $postIds ) && WordPress::isSingular() ) {
			$postIds = explode( ',', $postIds );
			$postIds = array_filter( $postIds );
			$postIds = array_map( 'intval', $postIds );
			$display = in_array( get_the_ID(), $postIds, true );
		}

		return $display;
	}

	public function getAnnouncement( $announcement, $position = true ): string {
		$style = '--asfowoo-announcement-bar-text-color: ' . ( $announcement['text_color'] ?? '#333' ) . ';';

		$bgColorType = $announcement['bg_color_type'] ?? 'solid';
		if ( $bgColorType === 'gradient' ) {
			$bgColorGradient = $announcement['bg_color_gradient'] ?? [];
			$style           .= '--asfowoo-announcement-bar-bg: ' . Assets::cssGradient( $bgColorGradient ) . ';';

		} else {
			$style .= '--asfowoo-announcement-bar-bg: ' . $announcement['bg_color_solid'] ?? '#ebe5ff' . ';';
		}

		$tag         = 'div';
		$withButtons = false;

		if ( empty( $announcement['primary_button'] ) && ! empty( $announcement['primary_button_url'] ) ) {
			$tag = 'a';
		}
		if ( ! empty( $announcement['primary_button'] ) || ! empty( $announcement['secondary_button'] ) ) {
			$withButtons = true;
		}

		$output = '<' . $tag . ( $tag === 'a' ? ' href="' . $announcement['primary_button_url'] . '"' : '' ) . ' id="asfowoo-announcement-bar-' . $announcement['code'] . '" class="asfowoo-announcement-bar' . ( $position ? ' asfowoo-announcement-bar-fixed asfowoo-announcement-bar-' . $announcement['position'] : ' asfowoo-announcement-bar-inline' ) . ( ! $withButtons ? ' asfowoo-announcement-bar-center' : '' ) . '" style="' . $style . '">';
		$output .= '<span class="asfowoo-announcement-bar-container">';
		$output .= '<span class="asfowoo-announcement-bar-text">' . esc_html( $announcement['text'] ) . '</span>';
		if ( $withButtons ) {
			$output .= '<span class="asfowoo-announcement-bar-buttons">';
			if ( ! empty( $announcement['primary_button'] ) && ! empty( $announcement['primary_button_url'] ) ) {
				$output .= '<a href="' . $announcement['primary_button_url'] . '" class="asfowoo-button asfowoo-button-primary">' . $announcement['primary_button'] . '</a>';
			}
			if ( ! empty( $announcement['secondary_button'] ) && ! empty( $announcement['secondary_button_url'] ) ) {
				$output .= '<a href="' . $announcement['secondary_button_url'] . '" class="asfowoo-button asfowoo-button-secondary">' . $announcement['secondary_button'] . '</a>';
			}
			$output .= '</span>';
		}
		$output .= '</span></' . $tag . '>';

		return $output;
	}

	public function getAnnouncements( $position = null ): array {
		$announcements = $this->getSetting( 'announcement_bar_data', [] );
		$announcements = is_array( $announcements ) ? $announcements : [];

		if ( ! empty( $announcements ) ) {
			$announcements = array_filter( $announcements, static function ( $announcement ) use ( $position ) {
				return $announcement['is_active'] && ( is_null( $position ) || $position === $announcement['position'] || ( is_array( $position ) && in_array( $announcement['position'], $position, true ) ) );
			} );
		}

		return $announcements;
	}

	public function displayFooterSettings( $display, $section ): bool {
		if ( $section === $this->currentSection ) {
			return false;
		}

		return $display;
	}

	private function getAnnouncementByCode( $code ) {
		$announcements = $this->getSetting( 'announcement_bar_data', [] );
		if ( is_array( $announcements ) && ! empty( $announcements ) ) {
			foreach ( $announcements as $announcement ) {
				if ( $announcement['code'] === $code ) {
					return $announcement;
				}
			}
		}

		return false;
	}

	private function getAnnouncementByIndex( $index ) {
		$announcements = $this->getSetting( 'announcement_bar_data', [] );
		if ( is_array( $announcements ) && ! empty( $announcements ) && isset( $announcements[ $index ] ) ) {
			return $announcements[ $index ];
		}

		return false;
	}

	public function dataTableActions( $index, $action ): void {
		if ( $action === 'reload_table' ) {
			$dataTable = $this->getDataTable();

			wp_send_json_success( [
				'table'     => $dataTable->renderHTML( Templates::getPath( 'data-table/data_table_table.php' ) ),
				'row_count' => $dataTable->getRowCount()
			] );

		} elseif ( $action === 'bulk_action' ) {
			$bulkAction    = Sanitizing::text( Param::post( 'bulk_action' ) );
			$rowIDs        = array_map( 'AssistantForWooCommerce\Helper\Sanitizing::int', Sanitizing::array( Param::post( 'row_ids' ) ) );
			$announcements = $this->getSetting( 'announcement_bar_data', [] );

			foreach ( $announcements as $announcementIndex => $announcement ) {
				if ( in_array( $announcementIndex, $rowIDs, true ) ) {
					if ( $bulkAction === 'bulk_delete' ) {
						unset( $announcements[ $announcementIndex ] );

					} elseif ( $bulkAction === 'bulk_enable' ) {
						$announcements[ $announcementIndex ]['is_active'] = true;

					} elseif ( $bulkAction === 'bulk_disable' ) {
						$announcements[ $announcementIndex ]['is_active'] = false;
					}
				}
			}

			$announcements = array_values( $announcements );
			$this->saveSetting( 'announcement_bar_data', $announcements );
			$dataTable = $this->getDataTable();

			wp_send_json_success( [
				'table'     => $dataTable->renderHTML( Templates::getPath( 'data-table/data_table_table.php' ) ),
				'row_count' => $dataTable->getRowCount(),
			] );

		} elseif ( $action === 'add_form' || $action === 'edit' ) {
			$data = [];
			if ( $index >= 0 && $announcement = $this->getAnnouncementByIndex( $index ) ) {
				$data = $announcement;
			}

			$form = HTML::printFields( $this->getFields( $index, $data ), false );

			wp_send_json_success( [ 'content' => $form ] );

		} elseif ( $action === 'save_form' ) {
			$formData     = \AssistantForWooCommerce\AppHelper\DataTableUI::getFormData( $this->getFields() );
			$errorMessage = '';
			$announcement = false;

			if ( empty( $formData['title'] ) ) {
				/* translators: %s: Title */
				$errorMessage = sprintf( esc_html__( '%s field is empty!', 'assistant-for-woocommerce' ), esc_html__( 'Title', 'assistant-for-woocommerce' ) );
			} elseif ( empty( $formData['text'] ) ) {
				/* translators: %s: Text */
				$errorMessage = sprintf( esc_html__( '%s field is empty!', 'assistant-for-woocommerce' ), esc_html__( 'Text', 'assistant-for-woocommerce' ) );
			}

			if ( $index >= 0 ) {
				$announcement = $this->getAnnouncementByIndex( $index );

				if ( $announcement === false ) {
					$errorMessage = esc_html__( 'Announcement not found!', 'assistant-for-woocommerce' );
				}
			}

			if ( ! empty( $errorMessage ) ) {
				wp_send_json_error( [
					'error'   => 'required-field',
					'message' => Notice::addAndDisplay( $this->currentSection, array(
						array(
							'type'    => 'error',
							'message' => $errorMessage
						)
					), false ),
				], 403 );
			}

			if ( $announcement !== false ) {
				$announcements                   = $this->getSetting( 'announcement_bar_data', [] );
				$announcements[ $index ]         = $formData;
				$announcements[ $index ]['code'] = $announcement['code'];
				$this->saveSetting( 'announcement_bar_data', $announcements );
				$successMessage = esc_html__( 'The announcement was successfully saved.', 'assistant-for-woocommerce' );

			} else {
				$formData['code'] = Helper::randomString( 6, true, false, true );
				$this->addToArraySetting( 'announcement_bar_data', $formData, true );
				$successMessage = esc_html__( 'Announcement added successfully.', 'assistant-for-woocommerce' );
			}

			$dataTable = $this->getDataTable();

			wp_send_json_success( [
				'table'     => $dataTable->renderHTML( Templates::getPath( 'data-table/data_table_table.php' ) ),
				'row_count' => $dataTable->getRowCount(),
				'message'   => Notice::addAndDisplay( $this->currentSection, array(
					array(
						'type'    => 'success',
						'message' => $successMessage,
					)
				), false ),
				//'refresh'   => true
			] );

		} elseif ( $action === 'delete' ) {
			if ( $this->deleteFromArraySetting( 'announcement_bar_data', $index ) ) {
				$dataTable = $this->getDataTable();

				wp_send_json_success( [
					'table'     => $dataTable->renderHTML( Templates::getPath( 'data-table/data_table_table.php' ) ),
					'row_count' => $dataTable->getRowCount(),
					'message'   => Notice::addAndDisplay( $this->currentSection, array(
						array(
							'type'    => 'success',
							'message' => esc_html__( 'Announcement removed!', 'assistant-for-woocommerce' ),
						)
					), false ),
				] );

			} else {
				wp_send_json_error( [
					'error'   => 'required-field',
					'message' => Notice::addAndDisplay( $this->currentSection, array(
						array(
							'type'    => 'error',
							'message' => esc_html__( 'Selected item not found!', 'assistant-for-woocommerce' ),
						)
					), false ),
				], 403 );
			}
		}
	}

	private function getDataTable(): DataTableUI {
		$dataTable = new DataTableUI();
		$dataTable->setID( 'announcement_bar' )
		          ->setRows( $this->getSetting( 'announcement_bar_data', [] ) )
		          ->setIdField( $dataTable::ROW_INDEX )
		          ->setTitle( esc_html__( 'Announcement Bars', 'assistant-for-woocommerce' ) )
		          ->modalAddTitle( esc_html__( 'Add new announcement', 'assistant-for-woocommerce' ) )
		          ->modalEditTitle( esc_html__( 'Edit announcement', 'assistant-for-woocommerce' ) )
		          ->addNewButton( esc_html__( 'Add new', 'assistant-for-woocommerce' ) )
		          ->addAction( 'edit', '<i class="asfowoo-icon-edit"></i>', $dataTable::ACTION_EDIT )
		          ->addAction( 'delete', '<i class="asfowoo-icon-trash"></i>', $dataTable::ACTION_DELETE )
		          ->addAction( 'bulk_enable', esc_html__( 'Enable', 'assistant-for-woocommerce' ), $dataTable::ACTION_NONE, [], $dataTable::ACTION_BULK )
		          ->addAction( 'bulk_disable', esc_html__( 'Disable', 'assistant-for-woocommerce' ), $dataTable::ACTION_NONE, [], $dataTable::ACTION_BULK )
		          ->addAction( 'bulk_delete', esc_html__( 'Delete', 'assistant-for-woocommerce' ), $dataTable::ACTION_DELETE, [], $dataTable::ACTION_BULK )
		          ->addColumn( esc_html__( 'Title', 'assistant-for-woocommerce' ), 'title' )
		          ->addColumn( esc_html__( 'ShortCode', 'assistant-for-woocommerce' ), 'code', function ( $row ) {
			          return '<code class="asfowoo-copy-text" title="' . esc_html__( 'Copy shortcode', 'assistant-for-woocommerce' ) . '">[' . self::shortCode . ' code="' . $row['code'] . '"]</code>';
		          }, [ 'is_html' => true, 'hide_on_mobile' => true ] )
		          ->addColumn( esc_html__( 'Status', 'assistant-for-woocommerce' ), $dataTable::ACTIVE_FIELD );

		return $dataTable;
	}

	public function addSectionSettings( $sections ) {
		$dataTable = $this->getDataTable();

		$sections[ $this->currentSection ] = array(
			'title'        => esc_html__( 'Announcement Bar', 'assistant-for-woocommerce' ),
			'settings_key' => $this->currentSection,
			'settings'     => array(
				'data_table_ui' => array(
					'id'         => 'announcement_bar',
					'type'       => 'dataTable',
					'data_table' => $dataTable->render()
				),
			)
		);

		return $sections;
	}

	private function getFields( $index = - 1, $data = [] ): array {
		return array(
			array(
				'id'            => 'row_id',
				'type'          => 'hidden',
				'save'          => false,
				'setting_value' => $index
			),
			array(
				'id'            => 'title',
				'title'         => esc_html__( 'Title', 'assistant-for-woocommerce' ),
				/* translators: %s: Shortcode */
				'desc'          => isset( $data['code'] ) && $data['code'] ? wp_sprintf( esc_html__( 'Announcement Bar shortcode: %s', 'assistant-for-woocommerce' ), '<code class="asfowoo-copy-text">[' . self::shortCode . ' code="' . $data['code'] . ']</code>' ) : '',
				'placeholder'   => esc_html__( 'Announcement title', 'assistant-for-woocommerce' ),
				'type'          => 'text',
				'setting_value' => $data['title'] ?? ''
			),
			array(
				'id'            => 'text',
				'title'         => esc_html__( 'Text', 'assistant-for-woocommerce' ),
				'placeholder'   => esc_html__( 'Announcement text', 'assistant-for-woocommerce' ),
				'type'          => 'textarea',
				'attributes'    => array(
					'resize' => 'none'
				),
				'setting_value' => $data['text'] ?? ''
			),
			array(
				'id'            => 'primary_button',
				'title'         => esc_html__( 'Primary button', 'assistant-for-woocommerce' ),
				'placeholder'   => esc_html__( 'Primary button text', 'assistant-for-woocommerce' ),
				'desc'          => esc_html__( 'If you leave the field blank, the announcement bar will be linked.', 'assistant-for-woocommerce' ),
				'type'          => 'text',
				'setting_value' => $data['primary_button'] ?? ''
			),
			array(
				'id'            => 'primary_button_url',
				'title'         => esc_html__( 'Primary link', 'assistant-for-woocommerce' ),
				'placeholder'   => esc_html__( 'Primary button link', 'assistant-for-woocommerce' ),
				'type'          => 'url',
				'setting_value' => $data['primary_button_url'] ?? ''
			),
			array(
				'id'            => 'secondary_button',
				'title'         => esc_html__( 'Secondary button', 'assistant-for-woocommerce' ),
				'placeholder'   => esc_html__( 'Secondary button text', 'assistant-for-woocommerce' ),
				'type'          => 'text',
				'setting_value' => $data['secondary_button'] ?? ''
			),
			array(
				'id'            => 'secondary_button_url',
				'title'         => esc_html__( 'Secondary link', 'assistant-for-woocommerce' ),
				'placeholder'   => esc_html__( 'Secondary button link', 'assistant-for-woocommerce' ),
				'type'          => 'url',
				'setting_value' => $data['secondary_button_url'] ?? ''
			),
			array(
				'type' => 'hr',
			),
			array(
				'title' => esc_html__( 'Display on', 'assistant-for-woocommerce' ),
				'type'  => 'startgrid',
			),
			array(
				'id'                => 'position',
				'title'             => esc_html__( 'Position', 'assistant-for-woocommerce' ),
				'type'              => 'select',
				'options'           => array(
					'top'           => esc_html__( 'Top', 'assistant-for-woocommerce' ),
					'sticky-top'    => esc_html__( 'Sticky on top', 'assistant-for-woocommerce' ),
					'sticky-bottom' => esc_html__( 'Sticky on bottom', 'assistant-for-woocommerce' ),
				),
				'option_none'       => 'Use shortcode',
				'option_none_value' => '',
				'default'           => 'top',
				'setting_value'     => $data['position'] ?? 'top',
				'sanitize'          => 'text',
			),
			array(
				'id'            => 'post_ids',
				'title'         => esc_html__( 'Single post/page/product', 'assistant-for-woocommerce' ),
				'placeholder'   => '1,25,87',
				'desc'          => esc_html__( 'Enter the post, page, or product IDs, separated by commas.', 'assistant-for-woocommerce' ),
				'type'          => 'text',
				'setting_value' => $data['post_ids'] ?? ''
			),
			array(
				'id'               => 'display_on',
				'title'            => esc_html__( 'Select page types', 'assistant-for-woocommerce' ),
				'type'             => 'checkboxInline',
				'setting_value'    => $data['display_on'] ?? [ 'all' ],
				'options'          => array(
					'all'              => esc_html__( 'All pages', 'assistant-for-woocommerce' ),
					'home'             => esc_html__( 'Home', 'assistant-for-woocommerce' ),
					'blog'             => esc_html__( 'Blog', 'assistant-for-woocommerce' ),
					'cart'             => esc_html__( 'Cart', 'assistant-for-woocommerce' ),
					'checkout'         => esc_html__( 'Checkout', 'assistant-for-woocommerce' ),
					'shop'             => esc_html__( 'Shop', 'assistant-for-woocommerce' ),
					'product'          => esc_html__( 'Product', 'assistant-for-woocommerce' ),
					'product-category' => esc_html__( 'Product category', 'assistant-for-woocommerce' ),
					'product-tag'      => esc_html__( 'Product tag', 'assistant-for-woocommerce' ),
					'product-taxonomy' => esc_html__( 'Product taxonomy', 'assistant-for-woocommerce' ),
					'category'         => esc_html__( 'Category', 'assistant-for-woocommerce' ),
					'tag'              => esc_html__( 'Tag', 'assistant-for-woocommerce' ),
					'page'             => esc_html__( 'Page', 'assistant-for-woocommerce' ),
					'post'             => esc_html__( 'Post', 'assistant-for-woocommerce' ),
					'singular'         => esc_html__( 'All single post types', 'assistant-for-woocommerce' ),
				),
				'not_equal'        => true,
				'sanitize'         => 'array',
				'sanitize_options' => 'text'
			),
			array(
				'type' => 'endgrid',
			),
			array(
				'type' => 'hr',
			),
			array(
				'title' => esc_html__( 'Style', 'assistant-for-woocommerce' ),
				'type'  => 'startgrid',
			),
			array(
				'id'            => 'text_color',
				'title'         => esc_html__( 'Text color', 'assistant-for-woocommerce' ),
				'type'          => 'wpColorPicker',
				'sanitize'      => 'color',
				'setting_value' => $data['text_color'] ?? '#333'
			),
			array(
				'title' => esc_html__( 'Background color type', 'assistant-for-woocommerce' ),
				'type'  => 'startInlineElements',
			),
			array(
				'id'            => 'bg_color_type',
				'title'         => esc_html__( 'Solid color', 'assistant-for-woocommerce' ),
				'type'          => 'radio',
				'default'       => 'solid',
				'value'         => 'solid',
				'setting_value' => $data['bg_color_type'] ?? 'solid',
				'sanitize'      => 'text'
			),
			array(
				'id'            => 'bg_color_type',
				'title'         => esc_html__( 'Gradient color', 'assistant-for-woocommerce' ),
				'type'          => 'radio',
				'default'       => 'solid',
				'value'         => 'gradient',
				'setting_value' => $data['bg_color_type'] ?? 'solid',
				'sanitize'      => 'text'
			),
			array(
				'type' => 'endInlineElements',
			),
			array(
				'id'            => 'bg_color_solid',
				'title'         => esc_html__( 'Background solid color', 'assistant-for-woocommerce' ),
				'type'          => 'wpColorPicker',
				'setting_value' => $data['bg_color_solid'] ?? '#ebe5ff',
				'sanitize'      => 'color'
			),
			array(
				'id'            => 'bg_color_gradient',
				'title'         => esc_html__( 'Background gradient color', 'assistant-for-woocommerce' ),
				'type'          => 'gradientColorPicker',
				'addable'       => true,
				'removable'     => true,
				'max_colors'    => 5,
				'setting_value' => $data['bg_color_gradient'] ?? array(
						'function' => 'linear-gradient',
						'rotate'   => 90,
						'colors'   => array(
							0   => '#dddeff',
							20  => '#ddeeff',
							80  => '#e0e5c0',
							100 => '#c0e5dd',
						)
					),
			),
			array(
				'type' => 'endgrid',
			),
			array(
				'type' => 'space',
				'size' => 30
			),
		);
	}

	public function wpEnqueueScriptsAction(): void {
		$pluginVersion = Assets::getVersion();
		$debugName     = ASSISTANTFORWOOCOMMERCE_DEBUG_MODE ? '' : '.min';

		wp_enqueue_style( ASSISTANTFORWOOCOMMERCE_PLUGIN_KEY . '-announcement-bar-style',
			Assets::url( 'css/announcement-bar' . $debugName . '.css' ),
			false, $pluginVersion );
	}

	public function info(): array {
		$icon = '<svg viewBox="-2.4 -2.4 28.80 28.80" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"><path transform="translate(-2.4, -2.4), scale(0.8999999999999999)" d="M16,30.34219599266847C19.53854807102261,29.988282275140428,21.989671739665788,27.000879805461366,24.2825855858541,24.282585585854104C26.320012422667293,21.867176213806044,27.80378894063114,19.14624528623579,28.097814702739317,16C28.427019799015813,12.47731554222226,28.193866509703074,8.777741066663864,25.99023362521868,6.009766374781325C23.558883780392648,2.955757295058568,19.900147867998868,0.9691490147634005,16.000000000000004,0.803982024391491C11.948862212034367,0.6324207762884754,7.464055646601889,1.8132576668453049,5.12619728832431,5.126197288324306C2.9534664946819245,8.205137424957991,5.1419810888702076,12.234031029187655,5.276551432907581,15.999999999999998C5.402044120395668,19.511929545284723,3.772168539228551,23.30903988384227,5.876499363903365,26.12350063609663C8.152243863634983,29.167220521558622,12.218440241290523,30.720414924670298,16,30.34219599266847" fill="#fff" strokewidth="0"></path></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M22 7.99992V11.9999M10.25 5.49991H6.8C5.11984 5.49991 4.27976 5.49991 3.63803 5.82689C3.07354 6.11451 2.6146 6.57345 2.32698 7.13794C2 7.77968 2 8.61976 2 10.2999L2 11.4999C2 12.4318 2 12.8977 2.15224 13.2653C2.35523 13.7553 2.74458 14.1447 3.23463 14.3477C3.60218 14.4999 4.06812 14.4999 5 14.4999V18.7499C5 18.9821 5 19.0982 5.00963 19.1959C5.10316 20.1455 5.85441 20.8968 6.80397 20.9903C6.90175 20.9999 7.01783 20.9999 7.25 20.9999C7.48217 20.9999 7.59826 20.9999 7.69604 20.9903C8.64559 20.8968 9.39685 20.1455 9.49037 19.1959C9.5 19.0982 9.5 18.9821 9.5 18.7499V14.4999H10.25C12.0164 14.4999 14.1772 15.4468 15.8443 16.3556C16.8168 16.8857 17.3031 17.1508 17.6216 17.1118C17.9169 17.0756 18.1402 16.943 18.3133 16.701C18.5 16.4401 18.5 15.9179 18.5 14.8736V5.1262C18.5 4.08191 18.5 3.55976 18.3133 3.2988C18.1402 3.05681 17.9169 2.92421 17.6216 2.88804C17.3031 2.84903 16.8168 3.11411 15.8443 3.64427C14.1772 4.55302 12.0164 5.49991 10.25 5.49991Z" stroke="#873eff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>';

		return array(
			'id'             => $this->addonID,
			'title'          => esc_html__( 'Announcement Bar', 'assistant-for-woocommerce' ),
			'desc'           => esc_html__( 'Promote sales using multiple announcement bar banner types.', 'assistant-for-woocommerce' ),
			'tags'           => [ esc_html__( 'Notification', 'assistant-for-woocommerce' ) ],
			'cat'            => 'customizations',
			'icon'           => $icon,
			'more_info_link' => 'https://parsa.ws',
			'settings_key'   => $this->addonID,
		);
	}
}