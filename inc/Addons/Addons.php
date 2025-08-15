<?php

namespace WooAssistant\Addons;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Admin\AdminPages;
use WooAssistant\Helper\Assets;
use WooAssistant\Helper\Cache;
use WooAssistant\Helper\Notice;
use WooAssistant\Helper\Param;
use WooAssistant\Helper\Sanitizing;
use WooAssistant\Helper\Validating;
use WooAssistant\Helper\WordPress;

class Addons {
    public const tab = 'addons';
    public const icon = '<svg fill="#873eff" viewBox="-1.6 -1.6 19.20 19.20" id="puzzle-16px" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"><path transform="translate(-1.6, -1.6), scale(0.6)" d="M16,26.067016359222563C19.01738168054809,25.872397068992516,22.262239430627645,26.517240315137514,24.60181605099669,24.60181605099669C27.111898440059782,22.546797774978174,28.145281087067747,19.24049670110675,28.296260704139346,16C28.455347682104016,12.585493874718981,27.663918047661323,9.155468547682407,25.442529248674575,6.557470751325425C23.042874131554072,3.750983787341677,19.68332835017655,1.3586644697408636,16,1.6190603700907609C12.450863972879969,1.8699694347691473,10.324639773941016,5.292883807362616,7.804411840049186,7.804411840049182C5.2767992788350275,10.323299008106623,1.883980163995068,12.445487433187566,1.5692449778710547,15.999999999999998C1.2381447568526107,19.73933372680073,3.1332333196882076,23.640132127311034,6.212055537857966,25.78794446214203C8.994928292718487,27.729299956421816,12.613919183695263,26.285416520322205,16,26.067016359222563" fill="#fff" strokewidth="0"></path></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path id="Path_56" data-name="Path 56" d="M-8.5,16h-4a.5.5,0,0,1-.5-.5v-1A1.5,1.5,0,0,0-14.5,13,1.5,1.5,0,0,0-16,14.5v1a.5.5,0,0,1-.5.5h-4a.5.5,0,0,1-.5-.5V3.5a.5.5,0,0,1,.5-.5H-17V2.5A2.5,2.5,0,0,1-14.5,0,2.5,2.5,0,0,1-12,2.5V3h3.5a.5.5,0,0,1,.5.5V7h.5A2.5,2.5,0,0,1-5,9.5,2.5,2.5,0,0,1-7.5,12H-8v3.5A.5.5,0,0,1-8.5,16ZM-12,15h3V11.5a.5.5,0,0,1,.5-.5h1A1.5,1.5,0,0,0-6,9.5,1.5,1.5,0,0,0-7.5,8h-1A.5.5,0,0,1-9,7.5V4h-3.5a.5.5,0,0,1-.5-.5v-1A1.5,1.5,0,0,0-14.5,1,1.5,1.5,0,0,0-16,2.5v1a.5.5,0,0,1-.5.5H-20V15h3v-.5A2.5,2.5,0,0,1-14.5,12,2.5,2.5,0,0,1-12,14.5Z" transform="translate(21)"></path> </g></svg>';
    public const menuIcon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
  <path fill="#873eff" d="M22 5.365h.75H22ZM9.455 13.303l.529-.532-.53.532Zm0-4.76.529.532-.53-.531Zm5.974 5.95-.53-.532.53.532Zm-4.78 0-.529.531.53-.531Zm9.866-5.066.53.532-.53-.532ZM22 5.858h-.75.75Zm-7.459-2.38-.529-.531.53.531Zm-2.168 9.167a.75.75 0 1 0-1.058-1.063l1.058 1.063ZM7.71 10.282l.53-.53V9.75l-.53.531Zm3.729-3.488a.75.75 0 1 0 .81-1.262l-.81 1.262Zm-.142-.983-.406.631.406-.63ZM9.06 4.666l.11-.742-.11.742ZM4.796 7.234l.53.531-.53-.531Zm2.71-2.37.287.693-.288-.693ZM5.35 8.453l.276-.698-.276.698Zm.107.043-.285.693.285-.693Zm1.415.954.53-.532-.53.532Zm-.082-.081-.522.538.522-.538Zm-1.77-1.047-.275.698.276-.698Zm8.714 8.015-.53.531a.713.713 0 0 0 .036.034l.494-.565Zm4.713-4.631a.75.75 0 1 0-1.258.816l1.258-.816Zm-.276.953-.629.408.63-.408Zm1.151 2.226.742-.11-.742.11Zm-2.579 4.246.53.532-.53-.532Zm2.38-2.698.692.29-.692-.29Zm-3.432 2.576.696-.278-.696.278Zm-.413-.949-.657.361.657-.36Zm-1.089-1.324.53-.532-.018-.017-.018-.015-.494.564Zm.712.753.59-.463-.59.463Zm1.769 1.715-.53-.531.53.531Zm-3.18-3.11-.26-.705.26.704Zm-5.629-5.659.696.28-.696-.28Zm-1.784 2.982a.75.75 0 0 0-1.06-1.061l1.06 1.061Zm-2.222 1.16-.53-.531.53.53ZM2.32 13.04l-.53-.53.53.53Zm.683.377a.75.75 0 0 0-1.06-1.061l1.06 1.061Zm8.604 5.57a.75.75 0 1 0-1.06-1.061l1.06 1.06Zm-2.222 1.159.53.53-.53-.53Zm1.538 1.536.53.53-.53-.53Zm.684.377a.75.75 0 1 0-1.06-1.061l1.06 1.06Zm-.973-4.873a.75.75 0 0 0-1.06-1.06l1.06 1.06ZM7.47 18.227a.75.75 0 0 0 1.06 1.061l-1.06-1.06Zm.387-3.814a.75.75 0 1 0-1.06-1.06l1.06 1.06ZM4.68 15.47a.75.75 0 1 0 1.061 1.06l-1.06-1.06ZM7.46 17.58a.75.75 0 1 0-1.048-1.072l1.048 1.072Zm-2.758.6a.75.75 0 1 0 1.049 1.072l-1.049-1.072Zm15.284-9.285L14.9 13.96l1.059 1.063 5.086-5.065-1.058-1.063Zm-8.808 5.065-1.194-1.19-1.059 1.063 1.195 1.19 1.059-1.063ZM9.985 9.075 15.07 4.01l-1.058-1.063-5.087 5.065 1.059 1.063Zm8.142-6.325h.495v-1.5h-.495v1.5Zm3.124 2.615v.493h1.5v-.493h-1.5ZM18.62 2.75c.818 0 1.356.002 1.755.055.378.05.516.135.6.22l1.06-1.064c-.41-.408-.918-.57-1.46-.643-.522-.07-1.179-.068-1.954-.068v1.5Zm4.13 2.615c0-.772.002-1.426-.069-1.946-.073-.542-.236-1.049-.647-1.458l-1.058 1.063c.084.084.168.22.219.595.053.397.055.932.055 1.746h1.5ZM9.984 12.771c-.579-.575-.958-.955-1.201-1.273-.231-.301-.268-.457-.268-.575h-1.5c0 .58.244 1.053.577 1.487.32.417.785.878 1.333 1.424l1.059-1.063Zm.136 2.253c.548.546 1.012 1.01 1.43 1.328.436.331.91.573 1.489.573v-1.5c-.121 0-.279-.038-.581-.268-.32-.242-.701-.62-1.28-1.196l-1.058 1.063ZM21.045 9.96c.688-.686 1.19-1.168 1.454-1.802l-1.384-.576c-.123.294-.353.542-1.129 1.315l1.058 1.063Zm.205-4.101c0 1.093-.013 1.43-.135 1.723l1.385.576c.263-.634.25-1.329.25-2.299h-1.5ZM15.07 4.01c.776-.773 1.026-1.002 1.322-1.125L15.82 1.5c-.636.262-1.119.762-1.808 1.448L15.07 4.01Zm3.056-2.76c-.975 0-1.671-.013-2.306.249l.572 1.386c.296-.122.637-.135 1.734-.135v-1.5ZM10.58 14.43l1.792-1.785-1.058-1.063-1.792 1.784 1.058 1.063Zm1.668-8.898-.548-.351-.81 1.261.547.352.81-1.262Zm-.548-.351c-.539-.347-.979-.63-1.359-.835-.389-.21-.76-.361-1.172-.422l-.22 1.484c.176.026.374.094.681.259.317.17.7.416 1.26.775l.81-1.261ZM5.325 7.765c.49-.488 1.014-1.01 1.485-1.438.234-.214.447-.396.628-.536.193-.148.308-.215.355-.234l-.575-1.386c-.236.098-.479.265-.695.431-.227.175-.474.389-.724.616-.5.456-1.048 1.002-1.532 1.484l1.058 1.063ZM9.17 3.924a3.694 3.694 0 0 0-1.952.247l.575 1.386a2.194 2.194 0 0 1 1.158-.15l.219-1.483ZM4.746 9.02l.33.13.551-1.395-.33-.13-.551 1.395Zm1.598.961.836.832 1.058-1.062-.836-.833-1.058 1.063Zm-1.269-.83.098.038.57-1.387-.116-.047-.552 1.395Zm2.327-.233-.089-.088L6.27 9.907l.075.074 1.058-1.063Zm-2.23.271c.409.168.781.412 1.097.718L7.313 8.83a4.976 4.976 0 0 0-1.57-1.028l-.57 1.387Zm-.905-2.487a1.414 1.414 0 0 0 .479 2.318l.552-1.395a.086.086 0 0 1 .027.14L4.267 6.702Zm12.923 5.82.353.545 1.259-.816-.354-.545-1.258.816Zm-.975 6.078-.073.073 1.058 1.063.073-.073-1.058-1.063Zm1.328-5.533c.36.556.608.938.779 1.253.166.306.233.502.26.676l1.483-.221c-.062-.413-.215-.783-.425-1.17-.205-.379-.49-.817-.838-1.354l-1.259.816Zm-.27 6.596c.485-.482 1.033-1.029 1.49-1.526.23-.249.443-.495.62-.721.166-.215.334-.457.433-.693l-1.384-.579c-.02.046-.085.16-.235.352-.14.18-.323.392-.538.626-.43.468-.954.99-1.444 1.478l1.058 1.063Zm1.308-4.667c.056.377.007.775-.15 1.148l1.385.579a3.658 3.658 0 0 0 .249-1.948l-1.484.22Zm-3.897 1.176-.455-.399-.988 1.129.455.398.988-1.128Zm1.704 2.56c-.186-.467-.3-.758-.451-1.033l-1.315.722c.097.177.174.368.373.866l1.393-.556Zm-2.727-1.465c.38.38.526.526.65.685l1.18-.926c-.193-.247-.416-.467-.772-.822l-1.058 1.063Zm2.276.432a4.966 4.966 0 0 0-.445-.673l-1.18.926c.116.148.22.305.31.47l1.315-.723Zm1.352-9.687a.945.945 0 0 1-1.331 0l-1.059 1.063a2.445 2.445 0 0 0 3.448 0L17.29 8.012Zm-1.331 0a.928.928 0 0 1 0-1.316l-1.059-1.063a2.428 2.428 0 0 0 0 3.442l1.059-1.063Zm0-1.316a.945.945 0 0 1 1.331 0l1.058-1.063a2.445 2.445 0 0 0-3.448 0l1.059 1.063Zm1.331 0a.928.928 0 0 1 0 1.316l1.058 1.063a2.428 2.428 0 0 0 0-3.442L17.29 6.696Zm-1.147 11.977a.15.15 0 0 1 .131-.042c.038.008.09.039.115.1l-1.393.556c.361.904 1.53 1.12 2.205.449l-1.058-1.063Zm-1.243-4.712c-.445.444-.777.774-1.062 1.02-.285.246-.47.358-.605.408l.517 1.408c.389-.143.735-.394 1.068-.68.334-.29.709-.663 1.14-1.093L14.9 13.961Zm-1.667 1.428a.54.54 0 0 1-.193.036v1.5c.247 0 .483-.044.71-.128l-.518-1.408Zm1.033.418-.245-.245-1.06 1.062.245.244 1.06-1.061Zm-5.34-7.795c-.421.42-.789.785-1.074 1.111-.285.324-.535.66-.684 1.032l1.391.56c.056-.138.174-.322.42-.603.245-.279.571-.604 1.006-1.037L8.925 8.012Zm-1.759 2.143a2.034 2.034 0 0 0-.151.768h1.5c0-.065.011-.13.043-.208l-1.391-.56Zm.013.658.153.153 1.06-1.061-.153-.154-1.06 1.062Zm-2.16 1.543-1.693 1.69 1.06 1.06 1.692-1.689-1.06-1.061Zm-2.17 1.215.153-.154-1.06-1.061-.153.153 1.06 1.062Zm0 .475a.335.335 0 0 1 0-.475l-1.06-1.062a1.835 1.835 0 0 0 0 2.598l1.06-1.061Zm.477 0a.338.338 0 0 1-.477 0l-1.06 1.06c.717.717 1.88.717 2.597 0l-1.06-1.06Zm7.22 3.88-1.692 1.69 1.06 1.06 1.692-1.69-1.06-1.06Zm.906 4.287.154-.154-1.06-1.061-.154.153 1.06 1.061Zm-2.598 0c.717.716 1.88.716 2.598 0l-1.06-1.062a.338.338 0 0 1-.478 0l-1.06 1.061Zm0-2.598a1.835 1.835 0 0 0 0 2.598l1.06-1.062a.335.335 0 0 1 0-.475l-1.06-1.06Zm.718-3.49L7.47 18.227l1.06 1.061 2.103-2.102-1.06-1.06Zm-2.776-2.772L4.68 15.47l1.061 1.06 2.117-2.117-1.06-1.06Zm-.384 3.156-1.71 1.672 1.049 1.072 1.71-1.672-1.05-1.072Z"/>
</svg>';

    public function __construct() {
        add_filter( 'woo_assistant_menus', [ $this, 'addMenu' ] );
        add_filter( 'woo_assistant_' . self::tab . '_settings', [ $this, 'settings' ] );
        add_filter( 'woo_assistant_' . self::tab . '_tab_display_notice', '__return_false' );
        add_filter( 'woo_assistant_' . self::tab . '_tab_content_display_notice', '__return_true' );
        add_filter( 'woo_assistant_' . self::tab . '_settings_display_reset_button', '__return_false' );
        add_filter( 'woo_assistant_settings_submit_button_title', [ $this, 'changeSubmitButtonTitle' ], 10, 2 );
        add_filter( 'woo_assistant_save_settings_success_message', [ $this, 'saveMessage' ], 10, 2 );
        add_filter( 'woo_assistant_dashboard_custom_links', [ $this, 'addDashboardLink' ] );
        add_action( 'woo_assistant_admin_init', [ $this, 'addRefreshNotice' ], 25 );
        add_action( 'admin_init', [ $this, 'flushRewriteRules' ] );
    }

    public function addDashboardLink( $links ) {
        $links[] = [
                'title' => __( 'Addons', 'wc-assistant' ),
                'desc'  => __( 'Woo Assistant Addons', 'wc-assistant' ),
                'link'  => AdminPages::link( [
                        'tab' => self::tab
                ] ),
                'icon'  => self::icon,
                'type'  => 'addons'
        ];

        return $links;
    }

    public function flushRewriteRules(): void {
        if ( AdminPages::isSettingPage() && Param::get( 'tab' ) === self::tab && Param::get( 'addons-refreshed' ) === '1' ) {
            flush_rewrite_rules();
            wp_safe_redirect( AdminPages::link( [ 'tab' => self::tab ] ) );
            exit();
        }
    }

    public function addRefreshNotice( $tab ): void {
        if ( $tab === self::tab && Cache::get( 'settings_saved' ) ) {
            Notice::add( self::tab, __( 'To load the add-on initial hooks, the page refreshes.', 'wc-assistant' ), 'warning' );
            ?>
            <script>
                setTimeout(function () {
                    window.location.href = '<?php
                            echo esc_url_raw( AdminPages::link( [ 'tab' => self::tab, 'addons-refreshed' => true ] ) )
                            ?>';
                }, 5000)
            </script>
            <?php
        }
    }

    public function addMenu( $menus ) {
        $menus[ self::tab ] = array(
                'title' => __( 'Addons', 'wc-assistant' ),
                'icon'  => self::menuIcon
        );

        return $menus;
    }

    public function saveMessage( $message, $tab ) {
        if ( $tab === self::tab ) {
            $message = __( 'Addons settings saved.', 'wc-assistant' );
        }

        return $message;
    }

    public function settings(): array {
        $addons    = apply_filters( 'woo_assistant_addons', array() );
        $addonList = array();
        $addonCats = self::getAddonCats();
        foreach ( array_keys( $addonCats ) as $addonCat ) {
            $addonList[ $addonCat ] = array();
        }

        foreach ( $addons as $addon ) {
            $cat = empty( $addon['cat'] ) || ! array_key_exists( $addon['cat'], $addonCats ) ? 'other' : $addon['cat'];

            if ( empty( $addon['id'] ) || empty( $addon['title'] ) || isset( $addonList[ $cat ][ $addon['id'] ] ) ) {
                continue;
            }

            $tags                 = is_array( $addon['tags'] ) ? $addon['tags'] : [];
            $icon                 = ! empty( $addon['icon'] ) && Assets::isSvgImageString( $addon['icon'] ) ? Assets::setSvgDimensions( $addon['icon'], 50 ) : '';
            $image                = ! empty( $addon['image'] ) && Validating::isUrl( $addon['image'] ) ? $addon['image'] : '';
            $imageLink            = ! empty( $addon['image_link'] ) && Validating::isUrl( $addon['image_link'] ) ? $addon['image_link'] : '';
            $moreInfo             = ! empty( $addon['more_info_link'] ) && Validating::isUrl( $addon['more_info_link'] ) ? $addon['more_info_link'] : '';
            $forceEnable          = Sanitizing::bool( $addon['force_enable'] ?? false );
            $canActivate          = empty( $addon['requires_plugins'] );
            $requirePluginsActive = 0;
            $actionLink           = '';
            $actionTitle          = __( 'Enable addon', 'wc-assistant' );

            if ( ! $canActivate && ! empty( $addon['requires_plugins'] ) && is_array( $addon['requires_plugins'] ) ) {
                foreach ( $addon['requires_plugins'] as $requirePluginPath => $requirePlugin ) {
                    $fileExists = file_exists( WP_PLUGIN_DIR . '/' . $requirePluginPath );

                    if (
                            ( $fileExists && is_plugin_active( $requirePluginPath ) ) ||
                            ( ! empty( $requirePlugin['function_check'] ) && function_exists( $requirePlugin['function_check'] ) ) ||
                            ( ! empty( $requirePlugin['class_check'] ) && class_exists( $requirePlugin['class_check'] ) )
                    ) {
                        $requirePluginsActive ++;

                    } elseif ( $fileExists ) {
                        $actionLink  = wp_nonce_url(
                                self_admin_url( 'addons.php?action=activate&addon=' . $requirePluginPath ),
                                'activate-plugin_' . $requirePluginPath
                        );
                        $actionTitle = __( 'Activate required addon', 'wc-assistant' );

                    } elseif ( isset( $requirePlugin['is_wp_plugin'] ) && $requirePlugin['is_wp_plugin'] ) {
                        $pluginSlug = WordPress::pluginPathToSlug( $requirePluginPath );

                        $actionLink  = wp_nonce_url(
                                self_admin_url( 'update.php?action=install-addon&addon=' . $pluginSlug ),
                                'install-plugin_' . $pluginSlug
                        );
                        $actionTitle = __( 'Install required addon', 'wc-assistant' );

                    } elseif ( ! empty( $requirePlugin['plugin_link'] ) && Validating::isUrl( $requirePlugin['plugin_link'] ) ) {
                        $actionLink  = $requirePlugin['plugin_link'];
                        $actionTitle = isset( $requirePlugin['is_free'] ) && $requirePlugin['is_free'] ? __( 'Download required addon', 'wc-assistant' ) : __( 'Buy required addon', 'wc-assistant' );

                    }

                    if ( ! empty( $actionLink ) ) {
                        break;
                    }
                }

                if ( $requirePluginsActive > 0 && $requirePluginsActive === count( $addon['requires_plugins'] ) ) {
                    $canActivate = true;
                }
            }

            if ( empty( $icon ) && empty( $image ) ) {
                $icon = self::icon;
            }

            $addonList[ $cat ][ $addon['id'] ] = array(
                    'id'                   => 'internal_addon_' . $addon['id'],
                    'title'                => $addon['title'],
                    'desc'                 => wp_trim_words( $addon['desc'] ?? '', 20, '' ),
                    'value'                => 1,
                    'default'              => 0,
                    'image'                => $image,
                    'image_link'           => $imageLink,
                    'icon'                 => $icon,
                    'tags'                 => $tags,
                    'cat'                  => $cat,
                    'more_info_link'       => $moreInfo,
                    'can_activate'         => $canActivate,
                    'action_link'          => $actionLink,
                    'action_link_external' => Validating::isExternalLink( $actionLink ),
                    'action_title'         => $actionTitle,
                    'force_enable'         => $forceEnable
            );
        }

        foreach ( $addonList as $cat => $addons ) {
            if ( empty( $addons ) ) {
                unset( $addonList[ $cat ] );
            }
        }

        $elementList = array();
        if ( count( $addonList ) ) {
            $lastKey = array_key_last( $addonList );

            foreach ( $addonList as $cat => $addons ) {
                if ( ! is_array( $addons ) || empty( $addons ) ) {
                    continue;
                }

                $elementList[ $cat . '_startaddons' ] = array(
                        'type'  => 'startaddons',
                        'title' => $addonCats[ $cat ],
                );

                foreach ( $addons as $addonID => $pluginOptions ) {
                    $elementList[ $addonID . '_plugin' ] = array_merge(
                            $pluginOptions, [
                                    'type' => 'addon',
                                    'name' => 'active_plugins[' . $addonID . ']'
                            ]
                    );
                }

                $elementList[ $cat . '_endaddons' ] = array(
                        'type' => 'endaddons'
                );

                if ( $cat !== $lastKey ) {
                    $elementList[ $cat . '_sep' ] = array(
                            'type' => 'hr'
                    );
                }
            }
        }

        return array(
                'title'    => __( 'Addons', 'wc-assistant' ),
                'desc'     => __( 'Woo Assistant integrates with WooCommerce to help you further enhance your website. You can enable or disable these integrations below.', 'wc-assistant' ),
                'settings' => $elementList
        );
    }

    public static function getAddonCats(): ?array {
        $cats = Cache::get( 'addon_cats', false );
        if ( is_array( $cats ) ) {
            return $cats;
        }

        $defaultCats = array(
                'recommended'    => __( 'Recommended', 'wc-assistant' ),
                'product'        => __( 'Product', 'wc-assistant' ),
                'cart'           => __( 'Cart', 'wc-assistant' ),
                'checkout'       => __( 'Checkout', 'wc-assistant' ),
                'order'          => __( 'Order', 'wc-assistant' ),
                'marketing'      => __( 'Marketing', 'wc-assistant' ),
                'payments'       => __( 'Payments', 'wc-assistant' ),
                'merchandising'  => __( 'Merchandising', 'wc-assistant' ),
                'shipping'       => __( 'Shipping', 'wc-assistant' ),
                'customizations' => __( 'Customizations', 'wc-assistant' ),
                'conversion'     => __( 'Conversion', 'wc-assistant' ),
                'seo'            => __( 'SEO', 'wc-assistant' ),
                'utility'        => __( 'Utility', 'wc-assistant' ),
        );

        $cats = apply_filters( 'woo_assistant_addon_cats', array() );
        $cats = is_array( $cats ) ? $cats : [];

        $cats = array_merge( $defaultCats, $cats, [ 'other' => __( 'Other addons', 'wc-assistant' ) ] );
        Cache::set( 'addon_cats', $cats );

        return $cats;
    }

    public function changeSubmitButtonTitle( $title, $tab ) {
        if ( $tab === self::tab ) {
            $title = __( 'Save active addons', 'wc-assistant' );
        }

        return $title;
    }
}