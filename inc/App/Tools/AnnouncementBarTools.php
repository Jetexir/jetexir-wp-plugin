<?php

namespace WooAssistant\App\Tools;

use WooAssistant\Addons\Addon;
use WooAssistant\Helper\HTML;
use WooAssistant\Helper\Notice;
use WooAssistant\Helper\Templates;
use WooAssistant\Interfaces\AddonInterface;
use WooAssistant\Providers\UI\DataTableUI;
use WooAssistant\Settings\Settings;

class AnnouncementBarTools extends Addon implements AddonInterface {
	public string $addonID = 'announcement-bar-tools';
	private const sectionID = 'announcement-bar';

	public function initAction(): void {
		add_filter( 'woo_assistant_tools_settings_sections', [ $this, 'addSectionSettings' ] );
		add_action( 'woo_assistant_data_table_ui_announcement_bar_action', [ $this, 'dataTableActions' ], 10, 2 );
		add_filter( 'woo_assistant_tools_settings_display_footer', [ $this, 'displayFooterSettings' ], 10, 2 );
	}

	public function displayFooterSettings( $display, $section ): bool {
		if ( $section === self::sectionID ) {
			return false;
		}

		return $display;
	}

	private function getAnnouncement( $index ) {
		$announcements = Settings::get( 'announcement_bar_data', [], self::sectionID );
		if ( is_array( $announcements ) && ! empty( $announcements ) && isset( $announcements[ $index ] ) ) {
			return $announcements[ $index ];
		}

		return false;
	}

	public function dataTableActions( $index, $action ): void {
		if ( $action === 'reload_table' ) {
			$dataTable = $this->getDataTable();

			wp_send_json_success( [
				'table'     => $dataTable->renderHTML( Templates::getPath( 'data_table_table.php' ) ),
				'row_count' => $dataTable->getRowCount()
			] );

		} elseif ( $action === 'add_form' || $action === 'edit' ) {
			$data = [];
			if ( $index >= 0 && $announcement = $this->getAnnouncement( $index ) ) {
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
				$announcement = $this->getAnnouncement( $index );

				if ( $announcement === false ) {
					$errorMessage = __( 'Announcement not found!', 'woo-assistant' );
				}
			}

			if ( ! empty( $errorMessage ) ) {
				wp_send_json_error( [
					'error'   => 'required-field',
					'message' => Notice::addAndDisplay( self::sectionID, array(
						array(
							'type'    => 'error',
							'message' => $errorMessage
						)
					), false ),
				], 403 );
			}

			if ( $announcement !== false ) {
				$announcements           = Settings::get( 'announcement_bar_data', [], self::sectionID );
				$announcements[ $index ] = $formData;
				Settings::save( 'announcement_bar_data', $announcements, self::sectionID );
				$successMessage = __( 'The announcement was successfully saved.', 'woo-assistant' );

			} else {
				Settings::addToArray( 'announcement_bar_data', $formData, self::sectionID, true );
				$successMessage = __( 'Announcement added successfully.', 'woo-assistant' );
			}

			$dataTable = $this->getDataTable();

			wp_send_json_success( [
				'table'     => $dataTable->renderHTML( Templates::getPath( 'data_table_table.php' ) ),
				'row_count' => $dataTable->getRowCount(),
				'message'   => Notice::addAndDisplay( self::sectionID, array(
					array(
						'type'    => 'success',
						'message' => $successMessage,
					)
				), false ),
				//'refresh'   => true
			] );

		} elseif ( $action === 'delete' ) {
			if ( Settings::deleteFromArray( 'announcement_bar_data', $index, self::sectionID ) ) {
				$dataTable = $this->getDataTable();

				wp_send_json_success( [
					'table'     => $dataTable->renderHTML( Templates::getPath( 'data_table_table.php' ) ),
					'row_count' => $dataTable->getRowCount(),
					'message'   => Notice::addAndDisplay( self::sectionID, array(
						array(
							'type'    => 'success',
							'message' => __( 'Announcement removed!', 'woo-assistant' ),
						)
					), false ),
				] );

			} else {
				wp_send_json_error( [
					'error'   => 'required-field',
					'message' => Notice::addAndDisplay( self::sectionID, array(
						array(
							'type'    => 'error',
							'message' => __( 'Selected item not found!', 'woo-assistant' ),
						)
					), false ),
				], 403 );
			}
		}

		//wp_send_json_success( [ $rowID => $rowAction ] );
	}

	private function getDataTable(): DataTableUI {
		$dataTable = new DataTableUI();
		$dataTable->setID( 'announcement_bar' )
		          ->setRows( Settings::get( 'announcement_bar_data', [], self::sectionID ) )
		          ->setIdField( $dataTable::ROW_INDEX )
		          ->setTitle( __( 'Announcement Bars', 'woo-assistant' ) )
			//->setDesc( __( 'Announcement Bars', 'woo-assistant' ) )
			//->setActiveField( false )
			// ->setDisplayActiveField( false )
			      ->modalAddTitle( __( 'Add new announcement', 'woo-assistant' ) )
		          ->modalEditTitle( __( 'Edit announcement', 'woo-assistant' ) )
		          ->addNewButton( __( 'Add new', 'woo-assistant' ) )
		          ->addAction( 'edit', '<i class="wa-icon-edit"></i>', $dataTable::ACTION_EDIT )
		          ->addAction( 'delete', '<i class="wa-icon-trash"></i>', $dataTable::ACTION_DELETE )
		          ->setDisplayRowCount( true )
			/*->addColumn(
				__( '#', 'woo-assistant' ),
				$dataTable::ROW_INDEX,
				[
					'is_html'     => false,
					'is_sortable' => false,
				]
			)*/
			      ->addColumn( __( 'Title', 'woo-assistant' ), 'title', [
				'is_html'     => true,
				'is_sortable' => false,
			] );

		return $dataTable;
	}

	public function addSectionSettings( $sections ) {
		$dataTable = $this->getDataTable();

		$sections[ self::sectionID ] = array(
			'title'      => __( 'Announcement Bar', 'woo-assistant' ),
			'options_id' => self::sectionID,
			'settings'   => array(
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
				'id'    => 'bg-color-type',
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
				'max_items'     => 5,
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