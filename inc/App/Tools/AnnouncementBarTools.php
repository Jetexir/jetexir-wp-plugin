<?php

namespace WooAssistant\App\Tools;

use WooAssistant\Addons\Addon;
use WooAssistant\App\App;
use WooAssistant\Helper\Assets;
use WooAssistant\Helper\Helper;
use WooAssistant\Helper\HTML;
use WooAssistant\Helper\Notice;
use WooAssistant\Helper\Param;
use WooAssistant\Helper\Sanitizing;
use WooAssistant\Helper\Templates;
use WooAssistant\Helper\WordPress;
use WooAssistant\Interfaces\AddonInterface;
use WooAssistant\Providers\UI\DataTableUI;

class AnnouncementBarTools extends Addon implements AddonInterface {
	public string $addonID = 'announcement-bar-tools';
	public string $currentTab = 'tools';
	public string $currentSection = 'announcement-bar';
	private const shortCode = 'wa_announcement_bar';

	public function initAction(): void {
		App::addShortcode( self::shortCode, [ $this, 'announcementBarShortcode' ] );
		add_action( 'woo_assistant_data_table_ui_announcement_bar_action', [ $this, 'dataTableActions' ], 10, 2 );
		add_filter( 'woo_assistant_tools_settings_display_footer', [ $this, 'displayFooterSettings' ], 10, 2 );
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
				echo $this->getAnnouncement( $announcement );
			}
		}
	}

	public function wpFooterAction(): void {
		$announcements = $this->getAnnouncements( 'sticky-bottom' );

		foreach ( $announcements as $announcement ) {
			if ( $this->checkDisplay( $announcement ) ) {
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
		$style = '--wa-announcement-bar-text-color: ' . ( $announcement['text_color'] ?? '#333' ) . ';';

		$bgColorType = $announcement['bg_color_type'] ?? 'solid';
		if ( $bgColorType === 'gradient' ) {
			$bgColorGradient = $announcement['bg_color_gradient'] ?? [];
			$style           .= '--wa-announcement-bar-bg: ' . Assets::cssGradient( $bgColorGradient ) . ';';

		} else {
			$style .= '--wa-announcement-bar-bg: ' . $announcement['bg_color_solid'] ?? '#ebe5ff' . ';';
		}

		$tag         = 'div';
		$withButtons = false;

		if ( empty( $announcement['primary_button'] ) && ! empty( $announcement['primary_button_url'] ) ) {
			$tag = 'a';
		}
		if ( ! empty( $announcement['primary_button'] ) || ! empty( $announcement['secondary_button'] ) ) {
			$withButtons = true;
		}

		$output = '<' . $tag . ( $tag === 'a' ? ' href="' . $announcement['primary_button_url'] . '"' : '' ) . ' id="wa-announcement-bar-' . $announcement['code'] . '" class="wa-announcement-bar' . ( $position ? ' wa-announcement-bar-fixed wa-announcement-bar-' . $announcement['position'] : ' wa-announcement-bar-inline' ) . ( ! $withButtons ? ' wa-announcement-bar-center' : '' ) . '" style="' . $style . '">';
		$output .= '<span class="wa-announcement-bar-container">';
		$output .= '<span class="wa-announcement-bar-text">' . $announcement['text'] . '</span>';
		if ( $withButtons ) {
			$output .= '<span class="wa-announcement-bar-buttons">';
			if ( ! empty( $announcement['primary_button'] ) && ! empty( $announcement['primary_button_url'] ) ) {
				$output .= '<a href="' . $announcement['primary_button_url'] . '" class="wa-button wa-button-primary">' . $announcement['primary_button'] . '</a>';
			}
			if ( ! empty( $announcement['secondary_button'] ) && ! empty( $announcement['secondary_button_url'] ) ) {
				$output .= '<a href="' . $announcement['secondary_button_url'] . '" class="wa-button wa-button-secondary">' . $announcement['secondary_button'] . '</a>';
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
			$rowIDs        = array_map( 'WooAssistant\Helper\Sanitizing::int', Sanitizing::array( Param::post( 'row_ids' ) ) );
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
			$formData     = \WooAssistant\AppHelper\DataTableUI::getFormData( $this->getFields() );
			$errorMessage = '';
			$announcement = false;

			if ( empty( $formData['title'] ) ) {
				$errorMessage = sprintf( __( '%s field is empty!', 'woo-assistant' ), __( 'Title', 'woo-assistant' ) );
			} elseif ( empty( $formData['text'] ) ) {
				$errorMessage = sprintf( __( '%s field is empty!', 'woo-assistant' ), __( 'Text', 'woo-assistant' ) );
			}

			if ( $index >= 0 ) {
				$announcement = $this->getAnnouncementByIndex( $index );

				if ( $announcement === false ) {
					$errorMessage = __( 'Announcement not found!', 'woo-assistant' );
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
				$successMessage = __( 'The announcement was successfully saved.', 'woo-assistant' );

			} else {
				$formData['code'] = Helper::randomString( 6, true, false, true );
				$this->addToArraySetting( 'announcement_bar_data', $formData, true );
				$successMessage = __( 'Announcement added successfully.', 'woo-assistant' );
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
							'message' => __( 'Announcement removed!', 'woo-assistant' ),
						)
					), false ),
				] );

			} else {
				wp_send_json_error( [
					'error'   => 'required-field',
					'message' => Notice::addAndDisplay( $this->currentSection, array(
						array(
							'type'    => 'error',
							'message' => __( 'Selected item not found!', 'woo-assistant' ),
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
		          ->setTitle( __( 'Announcement Bars', 'woo-assistant' ) )
		          ->modalAddTitle( __( 'Add new announcement', 'woo-assistant' ) )
		          ->modalEditTitle( __( 'Edit announcement', 'woo-assistant' ) )
		          ->addNewButton( __( 'Add new', 'woo-assistant' ) )
		          ->addAction( 'edit', '<i class="wa-icon-edit"></i>', $dataTable::ACTION_EDIT )
		          ->addAction( 'delete', '<i class="wa-icon-trash"></i>', $dataTable::ACTION_DELETE )
		          ->addAction( 'bulk_enable', __( 'Enable', 'woo-assistant' ), $dataTable::ACTION_NONE, [], $dataTable::ACTION_BULK )
		          ->addAction( 'bulk_disable', __( 'Disable', 'woo-assistant' ), $dataTable::ACTION_NONE, [], $dataTable::ACTION_BULK )
		          ->addAction( 'bulk_delete', __( 'Delete', 'woo-assistant' ), $dataTable::ACTION_DELETE, [], $dataTable::ACTION_BULK )
		          ->addColumn( __( 'Title', 'woo-assistant' ), 'title' )
		          ->addColumn( __( 'ShortCode', 'woo-assistant' ), 'code', function ( $row ) {
			          return '<code>[' . self::shortCode . ' code="' . $row['code'] . '"]</code>';
		          }, [ 'is_html' => true ] )
		          ->addColumn( __( 'Status', 'woo-assistant' ), $dataTable::ACTIVE_FIELD );

		return $dataTable;
	}

	public function addSectionSettings( $sections ) {
		$dataTable = $this->getDataTable();

		$sections[ $this->currentSection ] = array(
			'title'        => __( 'Announcement Bar', 'woo-assistant' ),
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
				'title'         => __( 'Title', 'woo-assistant' ),
				'placeholder'   => __( 'Announcement title', 'woo-assistant' ),
				'type'          => 'text',
				'setting_value' => $data['title'] ?? ''
			),
			array(
				'id'            => 'text',
				'title'         => __( 'Text', 'woo-assistant' ),
				'placeholder'   => __( 'Announcement text', 'woo-assistant' ),
				'type'          => 'textarea',
				'attributes'    => array(
					'resize' => 'none'
				),
				'setting_value' => $data['text'] ?? ''
			),
			array(
				'id'            => 'primary_button',
				'title'         => __( 'Primary button', 'woo-assistant' ),
				'placeholder'   => __( 'Primary button text', 'woo-assistant' ),
				'desc'          => __( 'If you leave the field blank, the announcement bar will be linked.', 'woo-assistant' ),
				'type'          => 'text',
				'setting_value' => $data['primary_button'] ?? ''
			),
			array(
				'id'            => 'primary_button_url',
				'title'         => __( 'Primary link', 'woo-assistant' ),
				'placeholder'   => __( 'Primary button link', 'woo-assistant' ),
				'type'          => 'url',
				'setting_value' => $data['primary_button_url'] ?? ''
			),
			array(
				'id'            => 'secondary_button',
				'title'         => __( 'Secondary button', 'woo-assistant' ),
				'placeholder'   => __( 'Secondary button text', 'woo-assistant' ),
				'type'          => 'text',
				'setting_value' => $data['secondary_button'] ?? ''
			),
			array(
				'id'            => 'secondary_button_url',
				'title'         => __( 'Secondary link', 'woo-assistant' ),
				'placeholder'   => __( 'Secondary button link', 'woo-assistant' ),
				'type'          => 'url',
				'setting_value' => $data['secondary_button_url'] ?? ''
			),
			array(
				'type' => 'hr',
			),
			array(
				'title' => __( 'Display on', 'woo-assistant' ),
				'type'  => 'startgrid',
			),
			array(
				'id'                => 'position',
				'title'             => __( 'Position', 'woo-assistant' ),
				'type'              => 'select',
				'options'           => array(
					'top'           => __( 'Top', 'woo-assistant' ),
					'sticky-top'    => __( 'Sticky on top', 'woo-assistant' ),
					'sticky-bottom' => __( 'Sticky on bottom', 'woo-assistant' ),
				),
				'option_none'       => 'Use shortcode',
				'option_none_value' => '',
				'default'           => 'top',
				'setting_value'     => $data['position'] ?? 'top',
				'sanitize'          => 'text',
			),
			array(
				'id'            => 'post_ids',
				'title'         => __( 'Single post/page/product', 'woo-assistant' ),
				'placeholder'   => '1,25,87',
				'desc'          => __( 'Insert post, page, product IDs, Separated with comma.', 'woo-assistant' ),
				'type'          => 'text',
				'setting_value' => $data['post_ids'] ?? ''
			),
			array(
				'id'               => 'display_on',
				'title'            => __( 'Select page types', 'woo-assistant' ),
				'type'             => 'checkboxInline',
				'setting_value'    => $data['display_on'] ?? [ 'all' ],
				'options'          => array(
					'all'              => __( 'All pages', 'woo-assistant' ),
					'home'             => __( 'Home', 'woo-assistant' ),
					'blog'             => __( 'Blog', 'woo-assistant' ),
					'cart'             => __( 'Cart', 'woo-assistant' ),
					'checkout'         => __( 'Checkout', 'woo-assistant' ),
					'shop'             => __( 'Shop', 'woo-assistant' ),
					'product'          => __( 'Product', 'woo-assistant' ),
					'product-category' => __( 'Product category', 'woo-assistant' ),
					'product-tag'      => __( 'Product tag', 'woo-assistant' ),
					'product-taxonomy' => __( 'Product taxonomy', 'woo-assistant' ),
					'category'         => __( 'Category', 'woo-assistant' ),
					'tag'              => __( 'Tag', 'woo-assistant' ),
					'page'             => __( 'Page', 'woo-assistant' ),
					'post'             => __( 'Post', 'woo-assistant' ),
					'singular'         => __( 'All single post types', 'woo-assistant' ),
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
				'title' => __( 'Style', 'woo-assistant' ),
				'type'  => 'startgrid',
			),
			array(
				'id'            => 'text_color',
				'title'         => __( 'Text color', 'woo-assistant' ),
				'type'          => 'wpColorPicker',
				'sanitize'      => 'color',
				'setting_value' => $data['text_color'] ?? '#333'
			),
			array(
				'title' => __( 'Background color type', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			array(
				'id'            => 'bg_color_type',
				'title'         => __( 'Solid', 'woo-assistant' ),
				'type'          => 'radio',
				'default'       => 'solid',
				'value'         => 'solid',
				'setting_value' => $data['bg_color_type'] ?? 'solid',
				'sanitize'      => 'text'
			),
			array(
				'id'            => 'bg_color_type',
				'title'         => __( 'Gradient', 'woo-assistant' ),
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
				'title'         => __( 'Background solid color', 'woo-assistant' ),
				'type'          => 'wpColorPicker',
				'setting_value' => $data['bg_color_solid'] ?? '#ebe5ff',
				'sanitize'      => 'color'
			),
			array(
				'id'            => 'bg_color_gradient',
				'title'         => __( 'Background gradient color', 'woo-assistant' ),
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
		);
	}

	public function wpEnqueueScriptsAction(): void {
		$pluginVersion = Assets::getVersion();
		$debugName     = WOOASSISTANT_DEBUG_MODE ? '' : '.min';

		wp_enqueue_style( WOOASSISTANT_PLUGIN_KEY . '-announcement-bar-style',
			Assets::url( 'css/announcement-bar' . $debugName . '.css' ),
			false, $pluginVersion );
	}

	public function info(): array {
		$icon = '<svg viewBox="-2.4 -2.4 28.80 28.80" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"><path transform="translate(-2.4, -2.4), scale(0.8999999999999999)" d="M16,30.34219599266847C19.53854807102261,29.988282275140428,21.989671739665788,27.000879805461366,24.2825855858541,24.282585585854104C26.320012422667293,21.867176213806044,27.80378894063114,19.14624528623579,28.097814702739317,16C28.427019799015813,12.47731554222226,28.193866509703074,8.777741066663864,25.99023362521868,6.009766374781325C23.558883780392648,2.955757295058568,19.900147867998868,0.9691490147634005,16.000000000000004,0.803982024391491C11.948862212034367,0.6324207762884754,7.464055646601889,1.8132576668453049,5.12619728832431,5.126197288324306C2.9534664946819245,8.205137424957991,5.1419810888702076,12.234031029187655,5.276551432907581,15.999999999999998C5.402044120395668,19.511929545284723,3.772168539228551,23.30903988384227,5.876499363903365,26.12350063609663C8.152243863634983,29.167220521558622,12.218440241290523,30.720414924670298,16,30.34219599266847" fill="#fff" strokewidth="0"></path></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M22 7.99992V11.9999M10.25 5.49991H6.8C5.11984 5.49991 4.27976 5.49991 3.63803 5.82689C3.07354 6.11451 2.6146 6.57345 2.32698 7.13794C2 7.77968 2 8.61976 2 10.2999L2 11.4999C2 12.4318 2 12.8977 2.15224 13.2653C2.35523 13.7553 2.74458 14.1447 3.23463 14.3477C3.60218 14.4999 4.06812 14.4999 5 14.4999V18.7499C5 18.9821 5 19.0982 5.00963 19.1959C5.10316 20.1455 5.85441 20.8968 6.80397 20.9903C6.90175 20.9999 7.01783 20.9999 7.25 20.9999C7.48217 20.9999 7.59826 20.9999 7.69604 20.9903C8.64559 20.8968 9.39685 20.1455 9.49037 19.1959C9.5 19.0982 9.5 18.9821 9.5 18.7499V14.4999H10.25C12.0164 14.4999 14.1772 15.4468 15.8443 16.3556C16.8168 16.8857 17.3031 17.1508 17.6216 17.1118C17.9169 17.0756 18.1402 16.943 18.3133 16.701C18.5 16.4401 18.5 15.9179 18.5 14.8736V5.1262C18.5 4.08191 18.5 3.55976 18.3133 3.2988C18.1402 3.05681 17.9169 2.92421 17.6216 2.88804C17.3031 2.84903 16.8168 3.11411 15.8443 3.64427C14.1772 4.55302 12.0164 5.49991 10.25 5.49991Z" stroke="#873eff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>';

		return array(
			'id'             => $this->addonID,
			'title'          => __( 'Announcement Bar', 'woo-assistant' ),
			'desc'           => __( 'Promote sales with multiple announcement bar banner types', 'woo-assistant' ),
			'tags'           => [ __( 'Notification', 'woo-assistant' ) ],
			'cat'            => 'customizations',
			'icon'           => $icon,
			'more_info_link' => 'https://parsa.ws',
			'settings_key'   => $this->addonID,
		);
	}
}