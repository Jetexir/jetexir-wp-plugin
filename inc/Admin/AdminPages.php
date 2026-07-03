<?php

namespace Jetexir\Admin;

defined( 'ABSPATH' ) || exit;

use Jetexir\Helper\{Cache, DebugTrait, Notice, Assets, Param, Sanitizing};

class AdminPages {
  use DebugTrait;

  public function __construct() {
    add_action( 'admin_init', [ $this, 'init' ] );
    add_action( 'jetexir_admin_init', [ $this, 'checkSubmitForm' ], 15 );
    add_action( 'admin_menu', array( $this, 'adminMenuInit' ), 0 );
    add_action( 'admin_menu', array( $this, 'addMenu' ), PHP_INT_MAX );
    add_action( 'jetexir_notice', [ $this, 'displayNotices' ] );
    add_action( 'jetexir_header', [ $this, 'pageHeader' ] );
    add_action( 'jetexir_content', [ $this, 'pageContent' ] );
    add_action( 'jetexir_footer', [ $this, 'pageFooter' ] );
    add_action( 'admin_footer', [ $this, 'flushRewriteRules' ] );
  }

  public function flushRewriteRules(): void {
    if ( Cache::get( 'settings_saved' ) ) {
      flush_rewrite_rules();
    }
  }

  public function checkSubmitForm(): void {
    $tab = self::getActiveTab();
    if ( isset( $_POST['_form_nonce'] ) && check_admin_referer( 'settings_submit_' . $tab, '_form_nonce' ) ) {
      do_action( 'jetexir_submit_settings_form', $tab );
    }
  }

  public function pageHeader( $currentTab ): void {
    AdminSettings::headerSettings( $currentTab, AdminSettings::getSettings( $currentTab ) );
  }

  public function pageContent( $currentTab ): void {
    $settings = AdminSettings::getSettings( $currentTab );

    if ( $settings && apply_filters( 'jetexir_display_tab_settings', true, $currentTab ) ) {
      AdminSettings::printPage( $currentTab, $settings );
    }
  }

  public function pageFooter( $currentTab ): void {
    $settings = AdminSettings::getSettings( $currentTab );
    if ( empty( $settings ) ) {
      return;
    }

    $currentSection = AdminSettings::getActiveSection( $settings );
    $currentSection = $currentSection ?: null;
    AdminSettings::footerSettings( $currentTab, $currentSection );
  }

  public function init(): void {
    if ( self::isSettingPage() ) {
      do_action( 'jetexir_admin_init', self::getActiveTab() );
    }
  }

  public function adminMenuInit(): void {
    if ( self::isSettingPage() ) {
      do_action( 'jetexir_admin_init_menu', self::getActiveTab() );
    }
  }

  public function displayNotices( $tab ): void {
    if ( apply_filters( 'jetexir_' . $tab . '_tab_display_notice', true ) ) {
      Notice::display( '*' );
      Notice::display( $tab );
    }
  }

  public function addMenu(): void {
    $icon = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTA4IiBoZWlnaHQ9IjE1NSIgdmlld0JveD0iMCAwIDEwOCAxNTUiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxnIGNsaXAtcGF0aD0idXJsKCNjbGlwMF8yMDAzXzc0KSI+CjxwYXRoIGQ9Ik02MC4wMTQ3IDYuNzM5MjZDNTkuODA4MSAxMy41MjIgNjEuMTc4NiAyMy40Mzk2IDY0LjEyNiAzNi40OTIyQzY4LjU0NzMgNTYuMDcxIDgzLjI4ODcgNjQuNDYxNiA5OS41NjI2IDk2LjAzMjJDMTA2LjExNyAxMDguNzQ5IDEwNC4yMjcgMTMyLjk2NiA5MC43OTYgMTQ3LjMwNEM4OS41MDI2IDEzNy4xMDIgODEuMjAwOCAxMzEuNzE4IDcxLjUyOTQgMTIyLjU4NEM4MC4yMDIgMTMyLjk3NiA4Mi4wNDA2IDE0MC4wNDQgNzYuOTU4MSAxNDQuMDE1QzUzLjE2NTEgMTU4LjQ2MyAyNC43NTE4IDE0OC44MTggMzEuNTIwNiAxMTQuNzM3QzI3LjAzNjkgMTIzLjkzNCAyMC41MzQgMTM1LjU2MiAyMC41NDMgMTQ1LjAxOEMyMC40NjY0IDE0NC45MjcgMjAuMzg4NiAxNDQuODM3IDIwLjMxMjYgMTQ0Ljc0NkMtNi45NzQ0MSAxMTIuMDc3IDEyLjg0MTYgNzAuODE1IDI4LjE4NDYgNDEuNzc4M0MzOC40MTMzIDIyLjQyMDUgNDkuMDIzOSAxMC43NDA5IDYwLjAxNDcgNi43MzkyNloiIGZpbGw9IndoaXRlIi8+CjxwYXRoIGQ9Ik02MC4xMDczIDk5Ljg5ODdDNjIuMDE3NyAxMDEuMDc4IDYzLjc3NDUgMTAyLjY4MiA2NS4zMTU4IDEwNC42NjRDNjguOCAxMDkuMTQ2IDcxLjA0NDUgMTE1LjUwNyA3MS4wMDg1IDEyMi41ODVDNzAuOTcyNiAxMjkuNjYzIDY4LjY2MzQgMTM1Ljk5NSA2NS4xMzQzIDE0MC40MzRDNjEuNjA1NCAxNDQuODczIDU3LjAwNDYgMTQ3LjM4NyA1Mi4wMTE5IDE0Ny4zNTdDNDcuMDE5MiAxNDcuMzI3IDQyLjQ0MDggMTQ0Ljc1OSAzOC45NTYgMTQwLjI3N0MzNS40NzEyIDEzNS43OTYgMzMuMjI2NiAxMjkuNDM1IDMzLjI2MjcgMTIyLjM1NkMzMy4yNzk5IDExOC45NDggMzMuODI4NiAxMTUuNzE1IDM0Ljc4MjUgMTEyLjc4MUMzOS4xNTU0IDEyMC45NTcgNDYuMDI3NyAxMjUuOTMgNTIuMDc4IDEyNC41NDJDNTkuNTkwNyAxMjIuODE2IDYzLjA3NDQgMTExLjkxMiA2MC4xMDczIDk5Ljg5ODdaIiBmaWxsPSJ1cmwoI3BhaW50MF9saW5lYXJfMjAwM183NCkiLz4KPHBhdGggZD0iTTI4LjA1NTIgMzAuNDIxQzQxLjM0NjkgMzkuNjE1OSAyOC44NzMxIDQ4LjUzNDQgMzEuMjE2MyA2NC4wNTA1QzIxLjc1NDcgNDYuMzM2OSAzMS4zMTgxIDQxLjUwNzkgMjguMDU1MiAzMC40MjFaTTcuNzUxNTIgNzYuNzI1N0MyMy44NjM2IDgxLjQzNDEgMTMuNjgxNyA5MS4xOTYyIDI2LjE2NDUgMTA5LjI3NUM3LjE1NjA5IDk5LjI3NTcgMTQuMjczMSA4OC4zMzg4IDcuNzUxNTIgNzYuNzI1N1pNMy40NDg1NiAxMTIuMzFDMTguMzI0NCAxMTAuNTExIDE1LjU2ODcgMTI1Ljc0NSAyNy4wNjE1IDEzNi4xNTZDOS4zNDI1OSAxMjguODE1IDEyLjczNzYgMTE4LjY4NSAzLjQ0ODU2IDExMi4zMVpNNjMuNDY5OSAxNy44MTE3QzY3LjEwMjUgMTIuMDEyNyA3Mi4wNDIxIDkuMzUzMDEgNzMuNzAwMiAyLjUyNTVDODAuNDE4MSAxNi43MjU5IDY0LjkyMDEgMTkuMDExNyA1OS4zMzQgMzMuMjE5NEM1OS41ODE2IDI1LjkwMDYgNjEuMjkwNSAyMS4yOTE0IDYzLjQ2OTkgMTcuODExN1pNNzUuMzU3MiA0OC43OTQ4Qzc5LjE2NTIgNDIuOTcyNSA4NS4yNDU3IDM4LjM1MDUgOTQuOTMzIDM3LjcyMTNDODIuNTM2MiA0NS40ODIzIDkwLjE2MjkgNjcuNjAxOCA2OS40MTk0IDY3LjA2MTNDNzAuMDEzOCA2MS42Mzc4IDcxLjU0OTIgNTQuNjE2NyA3NS4zNTcyIDQ4Ljc5NDhaTTc3LjM2NTEgNzkuNjgxNEM3OC45OTQ3IDc3LjAxMDggODEuMzAyIDc0LjgxNzUgODQuMjA5OSA3Mi44OTM5Qzc3Ljk2OTMgODQuODg1NiA4OS45MjQ0IDg3LjIxNzUgNzUuMjI1NyA5Ny45Nzg3QzczLjgxNjQgODkuOTA4OSA3NC42NDk3IDg0LjEzMjIgNzcuMzY1MSA3OS42ODE0Wk05Mi43MiA4MC4wNjkxQzEwNS4wNDMgOTguMDQ2NyA4OC4xMjAxIDEwNi4yMzMgOTMuMzUwNyAxMjYuMzNDODAuMDUwOCAxMDAuMjUyIDk1LjIzODYgOTQuMjAyNyA5Mi43MiA4MC4wNjkxWiIgZmlsbD0id2hpdGUiIGZpbGwtb3BhY2l0eT0iMC44Ii8+CjxwYXRoIGQ9Ik05Mi41NzI1IDEwMS4wMTdDOTguMjIzMyAxMTAuMTg2IDk4LjMzMzcgMTI0LjQwNiA5NC43MzIyIDEzNS42MjdDOTQuMjIxNSAxMzcuMjU3IDkyLjQ0MjIgMTQxLjgyNCA5MS45NzEgMTQyLjYxOEw5MS44MzkyIDE0Mi41NzhDOTEuOTAyNiAxNDAuNjI2IDkyLjY1OTcgMTM0LjY0NCA5MC44Mjc0IDEyNy4yNTJDODkuMzA2OSAxMjEuMTAzIDg2LjE0NSAxMTUuOTg3IDgxLjA5MiAxMTIuMDc3Qzc2LjIwNTQgMTA4LjMwMyA3NS44MjQ4IDEwMy4yMSA3Ni4yOTc4IDEwMi4zMjhDNzkuNDU2NSAxMDUuODc4IDc5Ljc4ODMgMTA2LjE1MSA4MS4wNDI3IDEwNi41NkM4Mi42NDQ1IDEwNy4xMTcgODQuMTczNSAxMDYuMDkxIDgzLjkxMjUgMTAyLjg3NEM4My4zMTc4IDk1LjYxMDEgNzUuMzY5MiA4OS41MDgyIDc3LjIxMzEgNzguNTE2NEM3Ny4zNDcxIDc3Ljc0MjQgNzcuODk0MyA3NS43NzIyIDc4LjI3NDggNzQuOTcyMkM3OC4zMzQgNzQuOTY3MiA3OC4zODI0IDc0Ljk5NzMgNzguNDI5OSA3NC45OTg5Qzc4LjcwNzQgNzYuMjMyNSA3OC44NTkxIDc5LjUxMjcgODEuNDYzMyA4NC45MjEyQzg0LjEzNzMgOTAuNTUwOCA4OC40NDggOTQuMzM2OSA5Mi41NzI1IDEwMS4wMTdaTTE1LjYwNDEgOTQuNjM1OUMxMi40MDUgOTEuNzE4NiA3LjQ4MTI3IDg3LjMyNzggNi41MTA3NiA4MC41NzEyQzUuOTAxMDcgNzYuMjQ4NSA3LjI5MDE1IDcyLjUxNTYgMTAuMDE0OCA2OS4yMzIyQzExLjYyNDIgNjcuMjg4NSAxMy42Njg5IDY1LjY0NTIgMTUuNjMxOSA2NC41MTM3QzE1LjY1NzQgNjQuNTU3OCAxNS42ODIyIDY0LjYwMDggMTUuNzMwNyA2NC42MzA5QzE1LjY3NDggNjQuNzIyIDE1LjU4NDUgNjQuODMxMyAxNS41MSA2NC45MDM3QzExLjM0MTcgNjguNzU4IDkuNzIyNSA3Ni45MjAzIDE3LjUwMSA4My4zODE2QzI3Ljg3ODQgOTEuOTczOCAzNi4wMzU4IDExMC41NTUgMjkuMzA2OSAxMjcuOTM0QzI5LjE5ODUgMTI4LjE5OSAyOS4yMzgxIDEyOC42NyAyOC43NzI3IDEyOC42MTlDMjguNzEzNiAxMjguNjI0IDI4LjYzOTcgMTI4LjA4NiAyOC42Nzg4IDEyNy44MDJDMjkuMzIyIDEyMi4xNzcgMjguNjMyNCAxMTYuNzEgMjYuOCAxMTEuMzc5QzI0LjI5IDEwMy45NzEgMjAuNTU2NSA5OS4xMjYyIDE1LjYwNDEgOTQuNjM1OVpNMjguMjk4MiA1Ny41MDNDMzIuNDI0MyA2NC41MzA5IDQzLjQ4MDMgNTguNTY0NCA1NS4zODc5IDcxLjk2NDlDNTguNDg2OCA3NS40NjI1IDYxLjI2NDYgNzkuODEyOCA2MS45NTggODEuMjEzMUw2MS44NzY2IDgxLjI2QzYxLjE5NzIgODAuODM4NyA1OS40NTggNzguMzg2NCA1NC41MTY2IDc2Ljg4QzUxLjkyOTkgNzYuMDc3NSA0OS4yOTcxIDc1LjQ0NjggNDYuNjUxNSA3NC43OTQ0QzM5Ljc5MDcgNzMuMDg3OCAzMi4yMzUzIDcxLjA4NDkgMjcuODMzNyA2MS44NjQyQzI1LjMxNzEgNTYuNjA4NSAyNS45MzI3IDU0LjQ1MzYgMjYuMDU3OSA1Mi40MDczQzI2LjA2NjggNTIuMzE1NSAyNi4xMTIyIDUyLjI1OTkgMjYuMTY2MyA1Mi4xNDE2QzI2LjIyOTQgNTIuMjUwNyAyNi4yNzc3IDUyLjI4MDYgMjYuMjc5NiA1Mi4zMzc2QzI2LjU3MjUgNTQuMjAxIDI3LjM1MzggNTUuODY5OSAyOC4yOTgyIDU3LjUwM1pNMjQuNTkwOSA2My4yMTA5QzI0LjY1MDIgNjMuMjA2MSAyNC42ODUxIDYzLjE4NiAyNC43NDc5IDYzLjE3OTJDMjcuNDI3MiA3MC4wMjUgMjkuMDY4OCA3NC42MjM4IDM0LjI0NTMgNzcuNTkxNUMzOC41NDUgODAuMDQ4NSA0Mi42NyA4MS44NTE0IDQ3LjM3MzQgODguMDIzOUM1MS41Mjc4IDkzLjQ5NjkgNTMuNTc1NiA5Ny41NDIzIDUzLjk1MTcgMTAzLjQyMkM1My45NDI5IDEwMy41MTQgNTMuOTQ1OCAxMDMuNiA1My45NDYxIDEwMy43MTZDNTMuODk5NiAxMDMuNzQyIDUzLjg2MzcgMTAzLjczNCA1My44Mjg4IDEwMy43NTRDNTEuNTIxNyA5Ny44NTUxIDUwLjM1NzUgOTUuMTg3NSA0NS41NzU3IDkyLjM5ODhDMzkuOTU0NCA4OS4xNjQ5IDM0LjcyNTMgODcuMDExNCAyOS44MzcyIDgwLjc3MTFDMjUuMjE1NiA3NC44NDE2IDIzLjkxODcgNjkuMjg4NiAyNC41OTA5IDYzLjIxMDlaTTIzLjY1MTQgMTQxLjc5QzIzLjU5NjcgMTQxLjk5NiAyMy41MjgyIDE0Mi4xOCAyMy40MTQ1IDE0Mi41MzZDMjIuNDU3NSAxNDAuODgxIDE5LjQxMzEgMTM2LjIyIDE4LjUyMjcgMTI5Ljg1M0MxNy43ODY4IDEyNC41NTggMTkuMDQ4MSAxMjAuMjAyIDE5LjM3NjkgMTE0Ljc4N0MxOS41NzEzIDExMS40NTIgMTguNDU4OCAxMDguNTIyIDE2LjU2MDEgMTA1Ljg0M0MxNS45MzczIDEwNC45NTMgMTUuMjI4MSAxMDQuMTQzIDE0LjU3NzQgMTAzLjI3QzE0LjUwMzcgMTAzLjE5NiAxNC40NjQgMTAzLjA3NCAxNC40NTgzIDEwMi45MDNDMTQuNjI2OSAxMDIuOTggMTQuODA2IDEwMy4wNTEgMTQuOTQ2NiAxMDMuMTQ0QzE2LjgzODkgMTA0LjQwNSAxOC40MjM0IDEwNS45ODggMTkuNzE4NSAxMDcuODI1QzIzLjU2NzUgMTEzLjE4NCAyNS4yNDA2IDExOS4yNDUgMjUuNTk3NyAxMjUuNzQ2QzI1Ljg2ODcgMTMxLjE5MyAyNS4xODY2IDEzNi41NSAyMy42NTE0IDE0MS43OVpNMjguODc5NCAzMC45NTIyQzI5Ljk5NiAzMS4zMjQ4IDM0LjI2NzUgMzQuNDM2NiAzNS40MTIyIDM5LjU4MzJDMzYuMTEwNiA0Mi43NTE1IDM0LjYxOCA0NS43MDIyIDM0LjEzNTEgNDkuNDkzM0MzMy43MzY4IDUyLjYyNjEgMzUuMDQzOCA1NS4wMzcyIDM1Ljg0MTQgNTYuNjY3OEMzNS41OTE0IDU2LjYzNzcgMzUuNDY2MyA1Ni42MjI2IDM1LjM4NDQgNTYuNTgyOEMzMS41MTQ0IDU0LjUxNjggMjguODgzMyA1MS40NzU1IDI4LjAxMTEgNDcuMTAwOUMyNy4zNTI2IDQzLjg1MTUgMjguNTM3NyA0MS4yMjMyIDI5LjQ2MSAzNy40Mzk1QzMwLjM2OTMgMzMuNzgwNCAyOC45NzU5IDMxLjYyMjQgMjguODQyMyAzMS4wODk3QzI4LjgwNjMgMzEuMDgxIDI4LjgyMjkgMzEuMDQyMSAyOC44Nzk0IDMwLjk1MjJaTTg1LjUxNDYgNTkuOTk5MkM4NS40OTkzIDU5LjM2OTMgODUuNjQgNTkuNDYyNCA4NS43NjE5IDU5LjQyMTJDODcuNzg1NCA2My42MjU3IDg4LjM3MjkgNjcuNzU3OSA4Ni44MTMxIDcyLjQwMjFDODQuMTcxMiA4MC4yNTQgODUuOTc5IDg0Ljk4OTIgODYuNDU1MiA4Ni43MTc5Qzg2LjM0NTcgODYuNjM1OCA4Ni4yNzMxIDg2LjU5MDcgODYuMjM1MyA4Ni41MjUzQzg0LjYwOTEgODMuNjU5NiA4My4xMDEzIDgwLjc1NDUgODIuMTg3NSA3Ny41NjUyQzgwLjM5NzYgNzEuMjUxOCA4My4zMiA2Ni43NTEzIDg0LjY0ODEgNjQuMDY5NkM4NS4yOTIgNjIuNzY5NCA4NS41MzM1IDYxLjQzOTkgODUuNTE0NiA1OS45OTkyWiIgZmlsbD0iIzcyMEVFQyIvPgo8L2c+CjxkZWZzPgo8bGluZWFyR3JhZGllbnQgaWQ9InBhaW50MF9saW5lYXJfMjAwM183NCIgeDE9IjUyLjMxMjUiIHkxPSIxMDEuMDg3IiB4Mj0iNjIuOTIxNiIgeTI9IjE0My45NSIgZ3JhZGllbnRVbml0cz0idXNlclNwYWNlT25Vc2UiPgo8c3RvcCBzdG9wLWNvbG9yPSIjNjYyOUMzIi8+CjxzdG9wIG9mZnNldD0iMSIgc3RvcC1jb2xvcj0iIzg3M0VGRiIvPgo8L2xpbmVhckdyYWRpZW50Pgo8L2RlZnM+Cjwvc3ZnPgo=';

    add_menu_page( esc_html__( 'Jetexir', 'jetexir' ), esc_html__( 'Jetexir', 'jetexir' ), 'manage_options', JETEXIR_PLUGIN_SLUG,
      [ $this, 'mainPage' ], $icon, '55.5' );
  }

  public function mainPage(): void {
    $currentTab = self::getActiveTab();
    ?>
    <div class="wrap ">
      <div
        class="jetexir-wrap jetexir-<?php echo esc_html( $currentTab ) ?>-wrap jetexir-wrapper">
        <div class="jetexir-sidebar" id="jetexir-sidebar">
          <div class="jetexir-sidebar-head">
            <img src="<?php echo esc_url( Assets::url( 'images/jetexir.svg' ) ) ?>" alt="Logo"
                 class="jetexir-logo">
            <a href="#" class="jetexir-hide-sidebar" id="jetexir-hide-sidebar">
              <i class="jetexir-icon-close"></i>
            </a>
          </div>
          <div class="menu-items">
            <?php
            do_action( 'jetexir_start_menus' );
            $menus    = self::getMenus();
            $addonSep = false;
            foreach ( $menus as $tab => $menu ) {
              if ( ! $addonSep && ! in_array( $tab, self::defaultTabs(), true ) ) {
                echo '<hr>';
                $addonSep = true;
              }

              echo wp_kses( self::menuItem( $tab, $menu ), Sanitizing::svgAllowedTags() );
            }
            do_action( 'jetexir_end_menus' );
            ?>
          </div>
        </div>
        <div class="jetexir-display-sidebar">
          <a href="#" id="jetexir-display-sidebar">
            <i class="jetexir-icon-menu"></i>
          </a>
        </div>
        <div class="jetexir-content jetexir-<?php echo esc_html( $currentTab ) ?>-content"
             id="jetexir-content-wrap">
          <?php
          // Display tab header
          do_action( 'jetexir_' . $currentTab . '_tab_header' );
          do_action( 'jetexir_header', $currentTab );

          echo '<div class="jetexir-content-body">';
          // Display notice
          do_action( 'jetexir_notice', $currentTab );
          do_action( 'jetexir_' . $currentTab . '_tab_notice' );

          // Display tab content
          do_action( 'jetexir_' . $currentTab . '_tab_content' );
          do_action( 'jetexir_content', $currentTab );
          echo '</div>';

          // Display tab footer
          do_action( 'jetexir_' . $currentTab . '_tab_footer' );
          do_action( 'jetexir_footer', $currentTab );
          ?>
        </div>
      </div>
    </div>
    <?php
  }

  public static function getMenus(): array {
    return apply_filters( 'jetexir_menus', [] );

    /*$settings = AdminSettings::defaultSettings();
    return array_map( static function ( $setting ) {
        return $setting['title'];
    }, $settings );*/
  }

  public static function menuItem( $tab, $menu, $link = null ): string {
    $current = self::getActiveTab();
    $link    = empty( $link ) ? self::link( [ 'tab' => $tab ] ) : $link;

    if ( ! is_array( $menu ) || ! isset( $menu['title'] ) ) {
      return '';
    }

    $icon = Assets::isSvgImageString( $menu['icon'] ) ? Assets::setSvgDimensions( $menu['icon'], 20 ) : '';

    return '<a href="' . esc_url( $link ) . '" class="menu-item' . ( $current === $tab ? ' menu-item-current' : '' ) . '">' . $icon . '<span>' . esc_html( $menu['title'] ) . '</span></a>';
  }

  public static function getActiveTab(): string {
    $default = 'dashboard';
    $current = strtolower( Param::get( 'tab', $default ) );
    $tabs    = array_merge( self::defaultTabs(), array_keys( self::getMenus() ) );

    return in_array( $current, $tabs, true ) ? $current : $default;
  }

  private static function defaultTabs(): array {
    return [ 'dashboard', 'product', 'order', 'cart', 'checkout', 'tools', 'addons', 'general', 'wordpress' ];
  }

  public static function isSettingPage(): bool {
    return is_admin() && Param::get( 'page' ) === JETEXIR_PLUGIN_SLUG;
  }

  public static function link( $query ): ?string {
    $query = is_array( $query ) ? $query : array();
    $data  = array_merge( array( 'page' => JETEXIR_PLUGIN_SLUG ), $query );
    $query = http_build_query( $data );

    return admin_url( 'admin.php?' . $query );
  }
}
