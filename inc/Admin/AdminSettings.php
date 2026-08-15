<?php

namespace Jetexir\Admin;

defined( 'ABSPATH' ) || exit;

use Jetexir\Helper\Cache;
use Jetexir\Helper\{HTML, Notice, Param, Sanitizing, Validating};
use Jetexir\Settings\Settings;

class AdminSettings {
  private static $settings = [];

  public function __construct() {
    add_action( 'jetexir_submit_settings_form', [ $this, 'saveForm' ], 0 );
  }

  public function saveForm( $tab ): void {
    $settings = self::getSettings( $tab );

    if ( $settings ) {
      $options        = [];
      $saveFields     = HTML::saveFields;
      $currentSection = null;
      $optionsName    = $settings['settings_key'] ?? null;

      if ( self::isSectionMode( $settings ) ) {
        $currentSection = self::getActiveSection( $settings );
        $tabSettings    = $settings['sections'][ $currentSection ]['settings'];
        $optionsName    = $settings['sections'][ $currentSection ]['settings_key'] ?? $optionsName;
      } else {
        $tabSettings = $settings['settings'];
      }

      if ( is_array( $tabSettings ) ) {
        $tabSettings = self::saveRepeatableSettings( $tabSettings, $optionsName );

        foreach ( $tabSettings as $setting ) {
          $setting['type'] = strtolower( $setting['type'] );

          if ( ( isset( $setting['save'] ) && $setting['save'] === false ) ||
               ( isset( $setting['is_repeatable'] ) && $setting['is_repeatable'] ) ||
               ! in_array( $setting['type'], $saveFields, true ) ) {
            continue;
          }

          $default = self::getSettingDefault( $setting );
          if ( in_array( $setting['type'], [ 'checkbox', 'toggle' ] ) ) {
            // PHPCS ignore reason: Nonce check is already happening before this logic in `AdminPages` class.
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
            $value = isset( $_POST[ JETEXIR_INPUT_PREFIX . $setting['id'] ] ) ? Param::post( JETEXIR_INPUT_PREFIX . $setting['id'], $default ) : false;
          } else {
            $value = Param::post( JETEXIR_INPUT_PREFIX . $setting['id'], $default );
          }

          $value = self::sanitizeSetting( $value, $setting );

          if ( is_array( $value ) ) {
            $value = self::sanitizeOptionsSetting( $value, $setting );
          }

          if ( $setting['type'] === 'colorpalette' ) {
            $value = array_values( $value );
          }

          /**
           * Filters a setting value before it is saved.
           *
           * @param mixed $value Setting value.
           * @param array $setting Setting field data.
           *
           * @return mixed Setting value.
           *
           * @since 1.0
           *
           */
          $value = apply_filters( 'jetexir_setting_value_before_save', $value, $setting );

          $options[ $setting['id'] ] = $value;
        }
      }

      /**
       * Filters all settings options before they are saved.
       *
       * @param array $options Settings options.
       * @param string $tab Current tab.
       *
       * @return array Settings options.
       *
       * @since 1.0
       *
       */
      $options = (array) apply_filters( 'jetexir_settings_before_save', $options, $tab );
      if ( ! empty( $options ) ) {
        $saved = Settings::saves( $options, $optionsName );

        if ( $saved ) {
          Cache::set( 'settings_saved', true );
          /**
           * Filters the success message shown after saving settings.
           *
           * @param string $message Success message.
           * @param string $tab Current tab.
           *
           * @return string Success message.
           *
           * @since 1.0
           *
           */
          Notice::add( $tab, (string) apply_filters( 'jetexir_save_settings_success_message', esc_html__( 'Settings saved.', 'jetexir' ), $tab ), 'success' );

          /**
           * Fires after settings are saved successfully.
           *
           * @param string $tab Current tab.
           * @param string|null $currentSection Current section.
           * @param array $options Saved options.
           *
           * @since 1.0
           *
           */
          do_action( 'jetexir_save_settings_success', $tab, $currentSection, $options );
        } else {
          /**
           * Filters the error message shown when saving settings fails.
           *
           * @param string $message Error message.
           * @param string $tab Current tab.
           *
           * @return string Error message.
           *
           * @since 1.0
           *
           */
          Notice::add( $tab, (string) apply_filters( 'jetexir_save_settings_error_message', esc_html__( 'Error saving settings!', 'jetexir' ), $tab ), 'error' );
        }
      }
    }
  }

  private static function saveRepeatableSettings( $settings, $optionsName ) {
    $types = array_column( $settings, 'type' );
    $types = array_map( 'strtolower', $types );

    if ( in_array( 'startrepeatableelements', $types, true ) ) {
      $saveValue              = [];
      $saveRepeatableElements = $repeatableSettingId = false;
      $maxRepeatElements      = $maxRepeat = 100;

      foreach ( $settings as $key => $setting ) {
        $setting['type'] = strtolower( $setting['type'] );

        if ( $setting['type'] === 'startrepeatable' ) {
          $maxRepeatElements = (int) ( $setting['max_repeat'] ?? $maxRepeat );
        }

        if ( $setting['type'] === 'startrepeatableelements' ) {
          $saveRepeatableElements            = true;
          $repeatableSettingId               = $setting['id'];
          $saveValue[ $repeatableSettingId ] = [];
        }

        if ( $saveRepeatableElements && ! in_array( $setting['type'], [
            'startrepeatableelements',
            'endrepeatableelements'
          ] ) ) {
          $setting['is_repeatable'] = true;
          $default                  = self::getSettingDefault( $setting );
          $rowKey                   = str_replace( JETEXIR_INPUT_PREFIX . $repeatableSettingId . '_', '', JETEXIR_INPUT_PREFIX . $setting['id'] );
          $value                    = Param::post( JETEXIR_INPUT_PREFIX . $setting['id'], $default );

          if ( is_array( $value ) ) {
            $count = count( $value );
            for ( $i = 0; $i < $count; $i ++ ) {
              $saveValue[ $repeatableSettingId ][ $i ][ $rowKey ] = $value[ $i ];
            }

            $saveValue[ $repeatableSettingId ] = array_slice( $saveValue[ $repeatableSettingId ], 0, $maxRepeatElements );
          }
        }

        if ( $setting['type'] === 'endrepeatableelements' ) {
          $saveRepeatableElements = false;
        }

        $settings[ $key ] = $setting;
      }

      if ( ! empty( $saveValue ) ) {
        foreach ( $saveValue as $settingKey => $settingValue ) {
          foreach ( $settingValue as $index => $value ) {
            $value = array_map( 'trim', $value );
            if ( implode( $value ) === '' ) {
              unset( $settingValue[ $index ] );
            }
          }
          $saveValue[ $settingKey ] = array_values( $settingValue );
        }

        Settings::saves( $saveValue, $optionsName );
      }
    }

    return $settings;
  }

  public static function getSettingDefault( $setting ) {
    $default = ! empty( $setting['default'] ) ? $setting['default'] : null;

    // Set default value for toggle, checkbox, addon
    if ( empty( $setting['default'] ) && in_array( $setting['type'], [ 'toggle', 'checkbox', 'addon' ], true ) ) {
      $default = 0;
    }

    /*if ( empty( $setting['default'] ) && in_array( $setting['type'], [ 'addon' ], true ) ) {
      $default = false;
    }*/

    // Set default value for imageSizeSelect
    if ( empty( $setting['default'] ) && $setting['type'] === 'imagesizeselect' ) {
      $default = 'thumbnail';
    }

    // Set default value for elements with multiple attr: select, termSelect, postSelect, imageSizeSelect
    if ( isset( $data['multiple'] ) && $data['multiple'] && empty( $setting['default'] ) && in_array( $setting['type'], [
        'select',
        'termselect',
        'menuselect',
        'postselect',
        'imagesizeselect',
        'orderstatusselect',
        'currencyselect',
      ] ) ) {
      $default = [];
    }

    if ( empty( $setting['default'] ) && $setting['type'] === 'checkboxinline' ) {
      $default = [];
    }

    return $default;
  }

  public static function sanitizeOptionsSetting( $value, $setting ) {
    if ( ! is_array( $value ) ) {
      return $value;
    }

    if ( empty( $setting['sanitize_options'] ) ) {
      if ( ( isset( $setting['multiple'] ) && $setting['multiple'] ) ) {
        if ( in_array( $setting['type'], [
          'termselect',
          'menuselect',
          'postselect',
          'userselect'
        ] ) ) {
          $setting['sanitize_options'] = 'int';
        }

        if ( in_array( $setting['type'], [ 'orderstatusselect', 'currencyselect' ] ) ) {
          $setting['sanitize_options'] = 'text';
        }
      }

      if ( $setting['type'] === 'colorpalette' ) {
        $setting['sanitize_options'] = 'color';
      }
    }

    if ( isset( $setting['sanitize_options'] ) && method_exists( Sanitizing::class, $setting['sanitize_options'] ) ) {
      $value = array_map( 'Jetexir\Helper\Sanitizing::' . $setting['sanitize_options'], $value );
    }

    return $value;
  }

  public static function sanitizeSetting( $value, $setting ) {
    if ( empty( $setting['sanitize'] ) ) {
      if ( in_array( $setting['type'], [ 'checkboxinline', 'colorpalette' ] ) ||
           ( isset( $setting['multiple'] ) && $setting['multiple'] &&
             in_array( $setting['type'], [
               'taxonomyselect',
               'termselect',
               'menuselect',
               'posttypeselect',
               'postselect',
               'imagesizeselect',
               'userroleselect',
               'userselect',
               'orderstatusselect',
               'currencyselect',
               'select'
             ] ) ) ) {
        $setting['sanitize'] = 'array';

      } else if ( in_array( $setting['type'], [
        'text',
        'search',
        'password',
        'tel',
        'hidden',
        'radio',
        'radioinline',
        'select',
        'posttypeselect',
        'taxonomyselect',
        'imagesizeselect',
        'orderstatusselect',
        'currencyselect',
        'userroleselect',
        'media'
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

      } elseif ( in_array( $setting['type'], [ 'color', 'wpcolorpicker' ] ) ) {
        $setting['sanitize'] = 'color';

      } elseif ( $setting['type'] === 'range' ) {
        $setting['sanitize'] = 'int';

      } elseif ( in_array( $setting['type'], [ 'postselect', 'termselect', 'menuselect', 'userselect' ] ) ) {
        $setting['sanitize'] = 'absint';

      } elseif ( $setting['type'] === 'addon' ) {
        $setting['sanitize'] = 'bool';

      } elseif ( $setting['type'] === 'gradientcolorpicker' ) {
        $setting['sanitize'] = 'jsonArray';
      }
    }

    if ( ! empty( $setting['sanitize'] ) && method_exists( Sanitizing::class, $setting['sanitize'] ) ) {
      $value = Sanitizing::{$setting['sanitize']}( $value );
    }

    return $value;
  }

  public static function getSettings( $tab ) {
    if ( ! isset( self::$settings[ $tab ] ) ) {
      /**
       * Filters the settings for a specific tab.
       *
       * @param array $settings Tab settings.
       *
       * @return array Tab settings.
       *
       * @since 1.0
       *
       */
      self::$settings[ $tab ] = (array) apply_filters( 'jetexir_' . $tab . '_settings', [] );
    }

    return self::$settings[ $tab ];
  }

  public static function allSettings( $tab = null ) {
    /**
     * Filters all Jetexir settings.
     *
     * @param array $settings All settings.
     *
     * @return array All settings.
     *
     * @since 1.0
     *
     */
    $settings = (array) apply_filters( 'jetexir_settings', [] );

    if ( ! is_null( $tab ) ) {
      return ! empty( $settings[ $tab ] ) ? $settings[ $tab ] : false;
    }

    return $settings;
  }

  public static function printPage( $currentTab, $settings ): void {
    $optionsName = $settings['settings_key'] ?? null;

    //echo '<div class="jetexir-content-body">';
    echo '<form method="post" id="jetexir-settings-form">';
    wp_nonce_field( 'settings_submit_' . $currentTab, '_form_nonce' );
    if ( self::isSectionMode( $settings ) ) {
      $currentSection  = self::getActiveSection( $settings );
      $currentSettings = $settings['sections'][ $currentSection ]['settings'] ?? [];
      $optionsName     = $settings['sections'][ $currentSection ]['settings_key'] ?? $optionsName;

      /**
       * Fires before printing a section settings content.
       *
       * @param string $currentTab Current tab.
       * @param string $currentSection Current section.
       * @param array $currentSettings Current section settings.
       *
       * @since 1.0
       *
       */
      do_action( 'jetexir_section_content', $currentTab, $currentSection, $currentSettings );
      self::printSettings( $currentSettings, $optionsName );
    } else {
      $currentSettings = $settings['settings'] ?? [];
      self::printSettings( $currentSettings, $optionsName );
    }
    echo '</form>';
    //echo '</div>';
  }

  private static function printSettings( $settings, $optionsName ): void {
    if ( is_array( $settings ) ) {
      $settings = self::checkRepeatableSettings( $settings, $optionsName );
      foreach ( $settings as $key => $field ) {
        if ( ! empty( $field['type'] ) && method_exists( HTML::class, strtolower( $field['type'] ) ) ) {
          $field['default'] = $field['default'] ?? null;

          if ( isset( $field['force_value'] ) ) {
            $field['setting_value'] = $field['force_value'];
          } elseif ( isset( $field['id'] ) ) {
            $field['setting_value'] = wp_unslash( Settings::get( $field['id'], $field['default'], $optionsName ) );
          }

          $field['type'] = strtolower( $field['type'] );

          // PHPCS ignore reason: `HTML::{$field['type']}()` returns HTML markup
          // where every dynamic value is escaped inside the field renderer.
          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
          echo HTML::{$field['type']}( $field );
        }
      }
    }
  }

  private static function checkRepeatableSettings( $settings, $optionsName ): array {
    $types = array_column( $settings, 'type' );
    $types = array_map( 'strtolower', $types );

    if ( in_array( 'startrepeatableelements', $types, true ) ) {
      $repeatableElementsCount        = ( array_count_values( $types ) )['startrepeatableelements'];
      $repeatableElementsProcessedIDs = [];
      $maxRepeatElements              = $maxRepeat = 100;

      for ( $repeatableElementsCountNumber = 0; $repeatableElementsCountNumber < $repeatableElementsCount; $repeatableElementsCountNumber ++ ) {
        $repeatableElements      = [];
        $saveRepeatableElements  = $repeatableSettingValue = $repeatableSettingId = false;
        $repeatableElementsIndex = 0;
        $index                   = - 1;

        foreach ( $settings as $key => $setting ) {
          $setting['type'] = strtolower( $setting['type'] );
          $index ++;

          if ( $setting['type'] === 'startrepeatable' ) {
            $maxRepeatElements = (int) ( $setting['max_repeat'] ?? $maxRepeat );
          }

          if ( $setting['type'] === 'startrepeatableelements' ) {
            $saveRepeatableElements  = true;
            $repeatableElementsIndex = $index;
            $repeatableSettingId     = $setting['id'];
            $repeatableSettingValue  = Settings::get( $repeatableSettingId, [], $optionsName );
          }

          if ( $saveRepeatableElements && in_array( $repeatableSettingId, $repeatableElementsProcessedIDs, true ) ) {
            $saveRepeatableElements = false;
            continue;
          }

          if ( $saveRepeatableElements ) {
            if ( ! in_array( $setting['type'], [ 'startrepeatableelements', 'endrepeatableelements' ] ) ) {
              $setting['is_repeatable'] = true;
            }

            $repeatableElements[ $key ] = $setting;
          }

          $settings[ $key ] = $setting;

          if ( $saveRepeatableElements && $setting['type'] === 'endrepeatableelements' ) {
            $saveRepeatableElements           = false;
            $addElements                      = [];
            $repeatableElementsProcessedIDs[] = $repeatableSettingId;

            if ( is_array( $repeatableSettingValue ) && count( $repeatableSettingValue ) ) {
              foreach ( $repeatableSettingValue as $rowIndex => $rowValue ) {
                foreach ( $repeatableElements as $repeatableElementIndex => $repeatableElement ) {
                  if ( ! in_array( $repeatableElement['type'], [
                    'startrepeatableelements',
                    'endrepeatableelements'
                  ] ) ) {
                    $repeatableRowKey = str_replace( $repeatableSettingId . '_', '', $repeatableElementIndex );

                    if ( isset( $rowValue[ $repeatableRowKey ] ) ) {
                      $repeatableElement['force_value'] = $rowValue[ $repeatableRowKey ];
                    }
                  }

                  $addElements[ $repeatableElementIndex . '_' . $rowIndex ] = $repeatableElement;
                }
              }
            }

            if ( ! empty( $addElements ) ) {
              $addElements = array_slice( $addElements, 0, $maxRepeatElements * count( $repeatableElements ) );
              //$settings = Helper::arrayInsertAfter( $settings, $repeatableElementsIndex, $addElements );

              if ( count( $addElements ) / count( $repeatableElements ) >= $maxRepeatElements ) {
                array_splice( $settings, $repeatableElementsIndex, count( $repeatableElements ), $addElements );
              } else {
                array_splice( $settings, $repeatableElementsIndex, 0, $addElements );
              }
            }

            break;
          }
        }
      }
    }

    return $settings;
  }

  public static function headerSettings( $currentTab, $settings ): void {
    if ( empty( $settings['title'] ) ) {
      return;
    }

    $currentSection = self::getActiveSection( $settings );
    /**
     * Filters the settings page header image.
     *
     * @param string $headerImage Header image URL.
     * @param string $currentTab Current tab.
     * @param string|false $currentSection Current section.
     * @param array $settings Tab settings.
     *
     * @return string Header image URL.
     *
     * @since 1.0
     *
     */
    $headerImage = (string) apply_filters( 'jetexir_settings_header_image', $settings['header_image'] ?? '', $currentTab, $currentSection, $settings );
    $headerImage = ! empty( $headerImage ) && Validating::isUrl( $headerImage ) ? $headerImage : false;

    echo '<header id="jetexir-settings-header" class="jetexir-header ' . ( $headerImage ? 'jetexir-has-header-image' : '' ) . '">';
    echo '<div class="jetexir-header-title" style="' . ( $headerImage ? 'background-image: url(' . esc_url( $headerImage ) . ');' : '' ) . '">';
    echo '<h1>' . esc_html( $settings['title'] ) . '</h1>';
    if ( ! empty( $settings['desc'] ) ) {
      echo '<p class="jetexir-description">' . esc_html( $settings['desc'] ) . '</p>';
    }
    echo '</div>';
    //echo '<hr class="jetexir-header-separator"/>';
    echo '<div class="jetexir-header-links">';
    self::printSections( $currentTab, $settings );
    echo '</div>';
    echo '</header>';

    /**
     * Filters whether to display notices before a tab content.
     *
     * @param bool $display Whether to display the notices.
     *
     * @return bool Whether to display the notices.
     *
     * @since 1.0
     *
     */
    $displayTabContentNotice = (bool) apply_filters( 'jetexir_' . $currentTab . '_tab_content_display_notice', false );

    if ( $displayTabContentNotice ) {
      Notice::display( '*' );
      Notice::display( $currentTab );
    }
  }

  public static function footerSettings( $currentTab, $currentSection ): void {
    /**
     * Filters whether to display the settings page footer.
     *
     * @param bool $display Whether to display the footer.
     * @param string $currentTab Current tab.
     * @param string|false $currentSection Current section.
     *
     * @return bool Whether to display the footer.
     *
     * @since 1.0
     *
     */
    $_jetexirSettingsDisplayFooter = (bool) apply_filters( 'jetexir_settings_display_footer', true, $currentTab, $currentSection );

    /**
     * Filters whether to display the settings page footer for a specific tab.
     *
     * @param bool $display Whether to display the footer.
     * @param string|false $currentSection Current section.
     *
     * @return bool Whether to display the footer.
     *
     * @since 1.0
     *
     */
    $_jetexirTabSettingsDisplayFooter = (bool) apply_filters( 'jetexir_' . $currentTab . '_settings_display_footer', true, $currentSection );

    if ( ! $_jetexirSettingsDisplayFooter || ! $_jetexirTabSettingsDisplayFooter ) {
      return;
    }

    echo '<footer id="jetexir-settings-footer" class="jetexir-footer jetexir-settings-footer">';

    /**
     * Filters the settings submit button title.
     *
     * @param string $title Button title.
     * @param string $currentTab Current tab.
     *
     * @return string Button title.
     *
     * @since 1.0
     *
     */
    $submitButtonTitle = (string) apply_filters( 'jetexir_settings_submit_button_title', esc_html__( 'Save changes', 'jetexir' ), $currentTab );

    echo HTML::button( [
      'id'          => 'settings-submit',
      'title'       => esc_html( $submitButtonTitle ),
      'button_type' => 'submit',
      'class'       => 'jetexir-button-primary',
      'attributes'  => [
        'form' => 'jetexir-settings-form'
      ]
    ] );

    /**
     * Filters whether to display the reset button in the settings page footer.
     *
     * @param bool $display Whether to display the reset button.
     *
     * @return bool Whether to display the reset button.
     *
     * @since 1.0
     *
     */
    $displayResetButton = (bool) apply_filters( 'jetexir_' . $currentTab . '_settings_display_reset_button', true );

    if ( $displayResetButton ) {
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      /**
       * Filters the settings reset button title.
       *
       * @param string $title Button title.
       * @param string $currentTab Current tab.
       *
       * @return string Button title.
       *
       * @since 1.0
       *
       */
      $resetButtonTitle = (string) apply_filters( 'jetexir_settings_reset_button_title', esc_html__( 'Discard changes', 'jetexir' ), $currentTab );

      echo HTML::button( [
        'id'          => 'settings-reset',
        'title'       => esc_html( $resetButtonTitle ),
        'button_type' => 'reset',
        'attributes'  => [
          'form' => 'jetexir-settings-form'
        ]
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

      echo '<div class="jetexir-section-links"><ul>';
      foreach ( $sections as $key => $section ) {
        echo '<li>';
        echo '<a href="' . esc_url( AdminPages::link( [
            'tab'     => $currentTab,
            'section' => $key
          ] ) ) . '" title="' . esc_html( $section['desc'] ) . '" class="jetexir-section-link' . ( $key === $currentSection ? ' jetexir-section-link-current' : '' ) . '">' . esc_html( $section['title'] ) . '</a>';
        echo '</li>';
      }
      echo '</ul>';

      /**
       * Filters whether to display the section description.
       *
       * @param bool $display Whether to display the description.
       * @param string $currentTab Current tab.
       * @param string $currentSection Current section.
       *
       * @return bool Whether to display the description.
       *
       * @since 1.0
       *
       */
      $displaySectionDescription = (bool) apply_filters( 'jetexir_display_section_description', false, $currentTab, $currentSection );

      if ( ! empty( $sections[ $currentSection ]['desc'] ) && $displaySectionDescription ) {
        echo '<p class="jetexir-description">' . esc_html( $sections[ $currentSection ]['desc'] ) . '</p>';
      }

      echo '</div>';
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

  public static function getCurrentSettings( $settings ) {
    if ( self::isSectionMode( $settings ) ) {
      $currentSection  = self::getActiveSection( $settings );
      $currentSettings = $settings['sections'][ $currentSection ]['settings'] ?? [];
    } else {
      $currentSettings = $settings['settings'] ?? [];
    }

    return $currentSettings;
  }

  public static function getActiveSection( $settings ) {
    if ( empty( $settings['sections'] ) ) {
      return false;
    }

    $sections = array_keys( $settings['sections'] );
    $sections = array_map( 'strtolower', $sections );
    $default  = current( $sections );
    $current  = strtolower( Param::get( 'section', $default ) );

    return in_array( $current, $sections, true ) ? $current : $default;
  }

  private static function isSectionMode( $settings ): bool {
    return ! empty( $settings['sections'] ) && is_array( $settings['sections'] );
  }
}
