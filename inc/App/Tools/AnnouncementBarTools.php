<?php

namespace Jetexir\App\Tools;

defined( 'ABSPATH' ) || exit;

use Jetexir\Addons\Addon;
use Jetexir\App\App;
use Jetexir\Helper\{Assets, Helper, HTML, Notice, Param, Sanitizing, Templates, WordPress};
use Jetexir\Interfaces\AddonInterface;
use Jetexir\Providers\UI\DataTableUI;

class AnnouncementBarTools extends Addon implements AddonInterface {
  public string $addonID = 'announcement-bar-tools';
  public string $currentTab = 'tools';
  public string $currentSection = 'announcement-bar';
  private const shortCode = 'jetexir_announcement_bar';

  public function initAction(): void {
    App::addShortcode( self::shortCode, [ $this, 'announcementBarShortcode' ] );
    add_action( 'jetexir_data_table_ui_announcement_bar_action', [
      $this,
      'dataTableActions'
    ], 10, 2 );
    add_filter( 'jetexir_tools_settings_display_footer', [
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

    return wp_kses_post( $this->getAnnouncement( $announcement, false ) );
  }

  public function wpBodyOpenAction(): void {
    $announcements = $this->getAnnouncements( [ 'top', 'sticky-top' ] );

    foreach ( $announcements as $announcement ) {
      if ( $this->checkDisplay( $announcement ) ) {
        echo wp_kses_post( $this->getAnnouncement( $announcement ) );
      }
    }
  }

  public function wpFooterAction(): void {
    $announcements = $this->getAnnouncements( 'sticky-bottom' );

    foreach ( $announcements as $announcement ) {
      if ( $this->checkDisplay( $announcement ) ) {
        echo wp_kses_post( $this->getAnnouncement( $announcement ) );
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
    $textColor = sanitize_hex_color( $announcement['text_color'] ?? '' );
    $textColor = $textColor ?: '#333';
    $style     = '--jetexir-announcement-bar-text-color: ' . esc_attr( $textColor ) . ';';

    $bgColorType = $announcement['bg_color_type'] ?? 'solid';
    if ( $bgColorType === 'gradient' ) {
      $bgColorGradient = $announcement['bg_color_gradient'] ?? [];
      $style           .= '--jetexir-announcement-bar-bg: ' . esc_attr( Assets::cssGradient( $bgColorGradient ) ) . ';';

    } else {
      $bgColorSolid = sanitize_hex_color( $announcement['bg_color_solid'] ?? '' );
      $bgColorSolid = $bgColorSolid ?: '#ebe5ff';
      $style        .= '--jetexir-announcement-bar-bg: ' . esc_attr( $bgColorSolid ) . ';';
    }

    $tag         = 'div';
    $withButtons = false;

    if ( empty( $announcement['primary_button'] ) && ! empty( $announcement['primary_button_url'] ) ) {
      $tag = 'a';
    }
    if ( ! empty( $announcement['primary_button'] ) || ! empty( $announcement['secondary_button'] ) ) {
      $withButtons = true;
    }

    $code          = esc_attr( $announcement['code'] ?? '' );
    $positionClass = $position ? ' jetexir-announcement-bar-fixed jetexir-announcement-bar-' . esc_attr( $announcement['position'] ?? '' ) : ' jetexir-announcement-bar-inline';

    $output = '<' . $tag . ( $tag === 'a' ? ' href="' . esc_url( $announcement['primary_button_url'] ) . '"' : '' ) . ' id="jetexir-announcement-bar-' . $code . '" class="jetexir-announcement-bar' . $positionClass . ( ! $withButtons ? ' jetexir-announcement-bar-center' : '' ) . '" style="' . $style . '">';
    $output .= '<span class="jetexir-announcement-bar-container">';
    $output .= '<span class="jetexir-announcement-bar-text">' . esc_html( $announcement['text'] ?? '' ) . '</span>';
    if ( $withButtons ) {
      $output .= '<span class="jetexir-announcement-bar-buttons">';
      if ( ! empty( $announcement['primary_button'] ) && ! empty( $announcement['primary_button_url'] ) ) {
        $output .= '<a href="' . esc_url( $announcement['primary_button_url'] ) . '" class="jetexir-button jetexir-button-primary">' . esc_html( $announcement['primary_button'] ) . '</a>';
      }
      if ( ! empty( $announcement['secondary_button'] ) && ! empty( $announcement['secondary_button_url'] ) ) {
        $output .= '<a href="' . esc_url( $announcement['secondary_button_url'] ) . '" class="jetexir-button jetexir-button-secondary">' . esc_html( $announcement['secondary_button'] ) . '</a>';
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
      $rowIDs        = array_map( 'Jetexir\Helper\Sanitizing::int', Sanitizing::array( Param::post( 'row_ids' ) ) );
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
      $formData     = \Jetexir\AppHelper\DataTableUI::getFormData( $this->getFields() );
      $errorMessage = '';
      $announcement = false;

      if ( empty( $formData['title'] ) ) {
        /* translators: %s: Title */
        $errorMessage = sprintf( esc_html__( '%s field is empty!', 'jetexir' ), esc_html__( 'Title', 'jetexir' ) );
      } elseif ( empty( $formData['text'] ) ) {
        /* translators: %s: Text */
        $errorMessage = sprintf( esc_html__( '%s field is empty!', 'jetexir' ), esc_html__( 'Text', 'jetexir' ) );
      }

      if ( $index >= 0 ) {
        $announcement = $this->getAnnouncementByIndex( $index );

        if ( $announcement === false ) {
          $errorMessage = esc_html__( 'Announcement not found!', 'jetexir' );
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
        $successMessage = esc_html__( 'The announcement was successfully saved.', 'jetexir' );

      } else {
        $formData['code'] = Helper::randomString( 6, true, false, true );
        $this->addToArraySetting( 'announcement_bar_data', $formData, true );
        $successMessage = esc_html__( 'Announcement added successfully.', 'jetexir' );
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
              'message' => esc_html__( 'Announcement removed!', 'jetexir' ),
            )
          ), false ),
        ] );

      } else {
        wp_send_json_error( [
          'error'   => 'required-field',
          'message' => Notice::addAndDisplay( $this->currentSection, array(
            array(
              'type'    => 'error',
              'message' => esc_html__( 'Selected item not found!', 'jetexir' ),
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
              ->setTitle( esc_html__( 'Announcement Bars', 'jetexir' ) )
              ->modalAddTitle( esc_html__( 'Add new announcement', 'jetexir' ) )
              ->modalEditTitle( esc_html__( 'Edit announcement', 'jetexir' ) )
              ->addNewButton( esc_html__( 'Add new', 'jetexir' ) )
              ->addAction( 'edit', '<i class="jetexir-icon-edit"></i>', $dataTable::ACTION_EDIT )
              ->addAction( 'delete', '<i class="jetexir-icon-trash"></i>', $dataTable::ACTION_DELETE )
              ->addAction( 'bulk_enable', esc_html__( 'Enable', 'jetexir' ), $dataTable::ACTION_NONE, [], $dataTable::ACTION_BULK )
              ->addAction( 'bulk_disable', esc_html__( 'Disable', 'jetexir' ), $dataTable::ACTION_NONE, [], $dataTable::ACTION_BULK )
              ->addAction( 'bulk_delete', esc_html__( 'Delete', 'jetexir' ), $dataTable::ACTION_DELETE, [], $dataTable::ACTION_BULK )
              ->addColumn( esc_html__( 'Title', 'jetexir' ), 'title' )
              ->addColumn( esc_html__( 'ShortCode', 'jetexir' ), 'code', function ( $row ) {
                return '<code class="jetexir-copy-text" title="' . esc_html__( 'Copy shortcode', 'jetexir' ) . '">[' . self::shortCode . ' code="' . $row['code'] . '"]</code>';
              }, [ 'is_html' => true, 'hide_on_mobile' => true ] )
              ->addColumn( esc_html__( 'Status', 'jetexir' ), $dataTable::ACTIVE_FIELD );

    return $dataTable;
  }

  public function addSectionSettings( $sections ) {
    $dataTable = $this->getDataTable();

    $sections[ $this->currentSection ] = array(
      'title'        => esc_html__( 'Announcement Bar', 'jetexir' ),
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
        'title'         => esc_html__( 'Title', 'jetexir' ),
        /* translators: %s: Shortcode */
        'desc'          => isset( $data['code'] ) && $data['code'] ? wp_sprintf( esc_html__( 'Announcement Bar shortcode: %s', 'jetexir' ), '<code class="jetexir-copy-text">[' . self::shortCode . ' code="' . $data['code'] . ']</code>' ) : '',
        'placeholder'   => esc_html__( 'Announcement title', 'jetexir' ),
        'type'          => 'text',
        'setting_value' => $data['title'] ?? ''
      ),
      array(
        'id'            => 'text',
        'title'         => esc_html__( 'Text', 'jetexir' ),
        'placeholder'   => esc_html__( 'Announcement text', 'jetexir' ),
        'type'          => 'textarea',
        'attributes'    => array(
          'resize' => 'none'
        ),
        'setting_value' => $data['text'] ?? ''
      ),
      array(
        'id'            => 'primary_button',
        'title'         => esc_html__( 'Primary button', 'jetexir' ),
        'placeholder'   => esc_html__( 'Primary button text', 'jetexir' ),
        'desc'          => esc_html__( 'If you leave the field blank, the announcement bar will be linked.', 'jetexir' ),
        'type'          => 'text',
        'setting_value' => $data['primary_button'] ?? ''
      ),
      array(
        'id'            => 'primary_button_url',
        'title'         => esc_html__( 'Primary link', 'jetexir' ),
        'placeholder'   => esc_html__( 'Primary button link', 'jetexir' ),
        'type'          => 'url',
        'setting_value' => $data['primary_button_url'] ?? ''
      ),
      array(
        'id'            => 'secondary_button',
        'title'         => esc_html__( 'Secondary button', 'jetexir' ),
        'placeholder'   => esc_html__( 'Secondary button text', 'jetexir' ),
        'type'          => 'text',
        'setting_value' => $data['secondary_button'] ?? ''
      ),
      array(
        'id'            => 'secondary_button_url',
        'title'         => esc_html__( 'Secondary link', 'jetexir' ),
        'placeholder'   => esc_html__( 'Secondary button link', 'jetexir' ),
        'type'          => 'url',
        'setting_value' => $data['secondary_button_url'] ?? ''
      ),
      array(
        'type' => 'hr',
      ),
      array(
        'title' => esc_html__( 'Display on', 'jetexir' ),
        'type'  => 'startgrid',
      ),
      array(
        'id'                => 'position',
        'title'             => esc_html__( 'Position', 'jetexir' ),
        'type'              => 'select',
        'options'           => array(
          'top'           => esc_html__( 'Top', 'jetexir' ),
          'sticky-top'    => esc_html__( 'Sticky on top', 'jetexir' ),
          'sticky-bottom' => esc_html__( 'Sticky on bottom', 'jetexir' ),
        ),
        'option_none'       => esc_html__( 'Use shortcode', 'jetexir' ),
        'option_none_value' => '',
        'default'           => 'top',
        'setting_value'     => $data['position'] ?? 'top',
        'sanitize'          => 'text',
      ),
      array(
        'id'            => 'post_ids',
        'title'         => esc_html__( 'Single post/page/product', 'jetexir' ),
        'placeholder'   => '1,25,87',
        'desc'          => esc_html__( 'Enter the post, page, or product IDs, separated by commas.', 'jetexir' ),
        'type'          => 'text',
        'setting_value' => $data['post_ids'] ?? ''
      ),
      array(
        'id'               => 'display_on',
        'title'            => esc_html__( 'Select page types', 'jetexir' ),
        'type'             => 'checkboxInline',
        'setting_value'    => $data['display_on'] ?? [ 'all' ],
        'options'          => array(
          'all'              => esc_html__( 'All pages', 'jetexir' ),
          'home'             => esc_html__( 'Home', 'jetexir' ),
          'blog'             => esc_html__( 'Blog', 'jetexir' ),
          'cart'             => esc_html__( 'Cart', 'jetexir' ),
          'checkout'         => esc_html__( 'Checkout', 'jetexir' ),
          'shop'             => esc_html__( 'Shop', 'jetexir' ),
          'product'          => esc_html__( 'Product', 'jetexir' ),
          'product-category' => esc_html__( 'Product category', 'jetexir' ),
          'product-tag'      => esc_html__( 'Product tag', 'jetexir' ),
          'product-taxonomy' => esc_html__( 'Product taxonomy', 'jetexir' ),
          'category'         => esc_html__( 'Category', 'jetexir' ),
          'tag'              => esc_html__( 'Tag', 'jetexir' ),
          'page'             => esc_html__( 'Page', 'jetexir' ),
          'post'             => esc_html__( 'Post', 'jetexir' ),
          'singular'         => esc_html__( 'All single post types', 'jetexir' ),
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
        'title' => esc_html__( 'Style', 'jetexir' ),
        'type'  => 'startgrid',
      ),
      array(
        'id'            => 'text_color',
        'title'         => esc_html__( 'Text color', 'jetexir' ),
        'type'          => 'wpColorPicker',
        'sanitize'      => 'color',
        'setting_value' => $data['text_color'] ?? '#333'
      ),
      array(
        'title' => esc_html__( 'Background color type', 'jetexir' ),
        'type'  => 'startInlineElements',
      ),
      array(
        'id'            => 'bg_color_type',
        'title'         => esc_html__( 'Solid color', 'jetexir' ),
        'type'          => 'radio',
        'default'       => 'solid',
        'value'         => 'solid',
        'setting_value' => $data['bg_color_type'] ?? 'solid',
        'sanitize'      => 'text'
      ),
      array(
        'id'            => 'bg_color_type',
        'title'         => esc_html__( 'Gradient color', 'jetexir' ),
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
        'title'         => esc_html__( 'Background solid color', 'jetexir' ),
        'type'          => 'wpColorPicker',
        'setting_value' => $data['bg_color_solid'] ?? '#ebe5ff',
        'sanitize'      => 'color'
      ),
      array(
        'id'            => 'bg_color_gradient',
        'title'         => esc_html__( 'Background gradient color', 'jetexir' ),
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
    $debugName     = JETEXIR_DEBUG_MODE ? '' : '.min';

    wp_enqueue_style( JETEXIR_PLUGIN_KEY . '-announcement-bar-style',
      Assets::url( 'css/announcement-bar' . $debugName . '.css' ),
      false, $pluginVersion );
  }

  public function info(): array {
    $icon = '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" width="200" height="200" fill="#873eff" stroke="#873eff" stroke-width=".002" viewBox="0 0 239.56 239.56"><path d="M146.962 36.978h-1.953L85.568 69.611H42.605C19.113 69.611 0 88.723 0 112.216c0 21.012 15.301 38.474 35.334 41.943L21.56 202.585h47.523l13.584-47.756h2.901l59.443 32.628h1.953c12.585 0 22.826-10.239 22.826-22.826V59.803c-.003-12.584-10.244-22.825-22.828-22.825zm-89.37 150.388H41.71l8.352-29.364h15.882zm51.867-36.785-19.988-10.972H42.605c-15.103 0-27.388-12.29-27.388-27.393s12.285-27.388 27.388-27.388h46.866l19.988-10.974zm45.111 14.05c0 3.637-2.567 6.683-5.978 7.431l-23.916-13.127V65.502l23.916-13.13c3.414.748 5.978 3.797 5.978 7.434zM198.989 79.377 188.106 90.26c5.623 7.789 8.976 17.32 8.976 27.637 0 10.32-3.353 19.851-8.976 27.637l10.883 10.883c8.326-10.629 13.31-24 13.31-38.52s-4.984-27.89-13.31-38.52z"/><path d="m218.358 60.009-10.794 10.794c10.482 12.856 16.782 29.252 16.782 47.094 0 17.845-6.3 34.238-16.782 47.094l10.794 10.794c13.216-15.648 21.205-35.849 21.205-57.888s-7.989-42.24-21.205-57.888z"/></svg>';

    return array(
      'id'             => $this->addonID,
      'title'          => esc_html__( 'Announcement Bar', 'jetexir' ),
      'desc'           => esc_html__( 'Promote sales using multiple announcement bar banner types.', 'jetexir' ),
      'tags'           => [ esc_html__( 'Notification', 'jetexir' ) ],
      'cat'            => 'customizations',
      'icon'           => $icon,
      'more_info_link' => '{jetexir_website}/addons/announcement-bar',
      'settings_key'   => $this->addonID,
    );
  }
}
