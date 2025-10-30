<?php

namespace AssistantForWooCommerce\App\Product;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Utilities\I18nUtil;
use AssistantForWooCommerce\Addons\Addon;
use AssistantForWooCommerce\App\App;
use AssistantForWooCommerce\Helper\Assets;
use AssistantForWooCommerce\Helper\Cookie;
use AssistantForWooCommerce\Helper\JSON;
use AssistantForWooCommerce\Helper\Nonce;
use AssistantForWooCommerce\Helper\Notice;
use AssistantForWooCommerce\Helper\Param;
use AssistantForWooCommerce\Helper\Sanitizing;
use AssistantForWooCommerce\Helper\WooCommerce;
use AssistantForWooCommerce\Helper\WordPress;
use AssistantForWooCommerce\Interfaces\AddonInterface;

class ProductCompare extends Addon implements AddonInterface {
    public string $addonID = 'product-compare';
    public string $currentTab = 'product';
    public string $currentSection = 'compare';
    private const shortCode = 'asfowoo_products_compare';
    private const cookieName = 'wc_products_compare';
    private const maxItems = 4;

    public function initAction(): void {
        App::addShortcode( self::shortCode, [ $this, 'compareShortcode' ] );
        if ( $this->getSetting( 'product_compare_archive_button', false ) ) {
            add_action( 'woocommerce_after_shop_loop_item', [ $this, 'addButton' ], 9999 );
        }
        add_action( 'woocommerce_after_add_to_cart_button', [ $this, 'addButton' ], 9999 );
        add_action( 'wp_ajax_asfowoo_product_compare_add_remove', [ $this, 'addRemoveItem' ] );
        add_action( 'wp_ajax_nopriv_asfowoo_product_compare_add_remove', [ $this, 'addRemoveItem' ] );
    }

    public function compareShortcode( $atts ) {
        $atts = shortcode_atts( array(
                'max_items'  => null,
                'image_size' => null,
        ), $atts, self::shortCode );

        $maxItems = (int) ( is_null( $atts['max_items'] ) ? $this->getSetting( 'product_compare_max_items', 2 ) : $atts['max_items'] );
        $maxItems = min( $maxItems, self::maxItems );

        $imageSizes = array_keys( Assets::getImageSizes() );
        $imageSize  = is_null( $atts['image_size'] ) ? $this->getSetting( 'product_compare_image_size', 'thumbnail' ) : $atts['image_size'];
        $imageSize  = in_array( $imageSize, $imageSizes, true ) ? $imageSize : '';

        ob_start();

        $productIDs = $this->getStorageItems();

        if ( empty( $productIDs ) ) {
            Notice::addAndDisplay( 'product-compare', array(
                    array(
                            'type'    => 'warning',
                            'message' => esc_html__( 'Your product compare list is empty', 'assistant-for-woocommerce' ),
                    )
            ) );

        } else {
            $productIDs = array_slice( $productIDs, 0, $maxItems );
            $products   = WooCommerce::getProducts( array(
                    'limit'   => $maxItems,
                    'orderby' => 'date',
                    'order'   => 'DESC',
                    'include' => $productIDs
            ) );

            if ( count( $products ) ) {
                $addToCardButton = $this->getSetting( 'product_compare_add_to_cart_button', false );
                $fields          = Product::getFields();
                $attributes      = WooCommerce::getAttributeTaxonomies();
                $data            = [ 'count' => count( $products ) ];
                ?>
                <div class="asfowoo-product-compare-wrapper asfowoo-product-compare-cols-<?php echo esc_html( $data['count'] ) ?>">
                    <?php
                    /**
                     * \WC_Product $product
                     */
                    foreach ( $products as $product ) {
                        $productID = $product->get_id();
                        $imageID   = (int) $product->get_image_id();

                        $data['removeButton'][] = '<button type="button" class="button asfowoo-button asfowoo-product-compare-button asfowoo-button-remove" data-id="' . $productID . '" data-action="refresh"><svg width="28px" height="28px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 8L8 16M8.00001 8L16 16" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>';

                        $data['images'][] = ! empty( $imageSize ) && $imageID ? wp_get_attachment_image( $imageID, $imageSize, false,
                                [ 'class' => 'asfowoo-product-compare-image' ] ) : '';

                        if ( ! $product->is_visible() ) {
                            $data['title'][] = esc_html( $product->get_name() );
                        } else {
                            $data['title'][] = wp_sprintf( '<a href="%s">%s</a>', esc_url( $product->get_permalink() ),
                                    esc_html( $product->get_name() ) );
                        }

                        if ( $addToCardButton ) {
                            $button = '';
                            if ( $product->is_purchasable() && $product->is_in_stock() ) {
                                $button = WooCommerce::getAddToCartButton( $product );
                            }

                            $data['addToCard'][] = $button;
                        }

                        foreach ( $fields as $key => $field ) {
                            if ( $this->getSetting( 'product_compare_display_field_' . $key, false ) ) {
                                $value = false;

                                if ( $key === 'brand' ) {
                                    $value = do_shortcode( '[product_brand post_id="' . $productID . '" class="asfowoo-product-compare-brand"]' );

                                } elseif ( $key === 'dimensions' && $product->has_dimensions() ) {
                                    $value = preg_replace( '/ /', '', $product->get_dimensions(), 4 );

                                } elseif ( $key === 'weight' && $product->has_weight() ) {
                                    $weight_unit_label = I18nUtil::get_weight_unit_label( get_option( 'woocommerce_weight_unit',
                                            'g' ) );
                                    $value             = $product->get_weight() . ' ' . $weight_unit_label;

                                } elseif ( $key === 'stock' ) {
                                    $availability = $product->get_availability();
                                    $value        = sprintf( '<span class="%s">%s</span>',
                                            esc_attr( $availability['class'] ),
                                            $availability['availability'] ? esc_html( $availability['availability'] ) : esc_html__( 'In stock',
                                                    'assistant-for-woocommerce' ) );

                                } elseif ( $key === 'rating' && wc_review_ratings_enabled() ) {
                                    $value = wc_get_rating_html( $product->get_average_rating() );

                                } elseif ( $key === 'price' && $price_html = $product->get_price_html() ) {
                                    $value = sprintf( '<span class="price">%s</span>', $price_html );
                                }

                                if ( $value === false ) {
                                    continue;
                                }
                                $data['fields'][ $key ]['label']   = $field;
                                $data['fields'][ $key ]['value'][] = $value;
                            }
                        }

                        foreach ( $attributes as $key => $attribute ) {
                            if ( $this->getSetting( 'product_compare_display_attribute_' . $key, false ) ) {
                                $data['fields'][ $key ]['label']   = $attribute['label'];
                                $data['fields'][ $key ]['value'][] = $product->get_attribute( $attribute['name'] );
                            }
                        }
                    }

                    // Head
                    echo '<div class="asfowoo-product-compare-row asfowoo-product-compare-head">';
                    foreach ( $data['title'] as $i => $title ) {
                        $i = (int) $i;
                        echo '<div class="asfowoo-product-compare-col">';
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo $data['removeButton'][ $i ];
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo $data['images'][ $i ];
                        echo wp_kses_post( $title );
                        if ( ! empty( $data['addToCard'][ $i ] ) ) {
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo '<div class="asfowoo-product-compare-add-to-card">' . $data['addToCard'][ $i ] . '</div>';
                        }
                        echo '</div>';
                    }
                    echo '</div>';

                    // Fields
                    foreach ( $data['fields'] as $key => $field ) {
                        $value = array_filter( $field['value'] );
                        if ( empty( $value ) ) {
                            continue;
                        }

                        echo '<div class="asfowoo-product-compare-field-title">';
                        echo esc_html( $field['label'] );
                        echo '</div>';
                        echo '<div class="asfowoo-product-compare-row asfowoo-product-compare-row-field asfowoo-product-compare-row-' . esc_html( $key ) . '">';
                        foreach ( $field['value'] as $value ) {
                            echo '<div class="asfowoo-product-compare-col">';
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo empty( $value ) ? '---' : $value;
                            echo '</div>';
                        }
                        echo '</div>';
                    }
                    ?>
                </div>
                <?php

            } else {
                Notice::addAndDisplay( 'product-compare', array(
                        array(
                                'type'    => 'warning',
                                'message' => esc_html__( 'Your product compare list is empty', 'assistant-for-woocommerce' ),
                        )
                ) );
            }
        }

        return ob_get_clean();
    }

    /**
     * Add or remove product id from storage
     *
     * @return void
     */
    public function addRemoveItem(): void {
        if ( Nonce::verify() ) {
            $productID = Sanitizing::int( Param::post( 'product_id', 0 ) );
            $max       = $this->getSetting( 'product_compare_max_items', 2 );
            $update    = $this->updateStorage( $productID, $max );

            $data = array(
                    'status'   => $update['status'],
                    'count'    => $update['count'],
                    'max'      => (int) $max,
                    'redirect' => $update['status'] === 'max_exceeded' ? get_permalink( $this->getSetting( 'product_compare_page', 0 ) ) : ''
            );

            wp_send_json_success( $data );
        }

        wp_send_json_error( [
                'error'   => 'nonce-invalid',
                'message' => esc_html__( 'Security code is not valid, page will be refreshed.', 'assistant-for-woocommerce' ),
                'refresh' => true
        ], 403 );
    }

    /**
     * Update (add/remove) item in storage
     *
     * @param int $productID Product id
     * @param int $max Max items
     *
     * @return array Return status and count of items
     */
    private function updateStorage( int $productID, int $max = 2 ): array {
        $productIDs = $this->getStorageItems();
        $count      = count( $productIDs );
        $status     = 'added';

        if ( ( $key = array_search( $productID, $productIDs, true ) ) !== false ) {
            unset( $productIDs[ $key ] );
            $productIDs = array_values( $productIDs );
            $status     = 'removed';
            $count --;
        } else {
            if ( $count >= $max ) {
                return [ 'status' => 'max_exceeded', 'count' => $count ];
            }

            $productIDs[] = $productID;
            $count ++;
        }

        $productIDs = JSON::encode( $productIDs );
        $expire     = current_time( 'timestamp' ) + HOUR_IN_SECONDS;
        Cookie::set( self::cookieName, $productIDs, $expire );

        return [ 'status' => $status, 'count' => $count ];
    }

    /**
     * Print add to compare button
     *
     * @return void
     */
    public function addButton(): void {
        $productID = get_the_ID();
        $exists    = $this->checkExistsItem( $productID );
        echo '<button type="button" class="button asfowoo-button asfowoo-button-secondary asfowoo-product-compare-button' . ( $exists ? ' asfowoo-button-remove' : '' ) . '" data-id="' . esc_html( $productID ) . '" data-action="non">' .
             esc_html( $this->getSetting( 'product_compare_button_text', esc_html__( 'Compare', 'assistant-for-woocommerce' ) ) )
             . '</button>';
    }

    /**
     * Get all product ids
     *
     * @return array
     */
    private function getStorageItems(): array {
        $value      = Cookie::get( self::cookieName, '' );
        $productIDs = JSON::decode( $value, true );
        $productIDs = is_array( $productIDs ) ? $productIDs : [];
        $productIDs = array_filter( $productIDs );
        $productIDs = array_values( $productIDs );

        return array_map( 'intval', $productIDs );
    }

    /**
     * Check exists product id
     *
     * @param int $productID Product id
     *
     * @return bool Product id exists status
     */
    private function checkExistsItem( $productID ): bool {
        return in_array( $productID, $this->getStorageItems(), true );
    }

    /**
     * Enqueue style and script
     *
     * @return void
     */
    public function wpEnqueueScriptsAction(): void {
        if ( ! WooCommerce::isWoocommerce() && ! WordPress::isPage( $this->getSetting( 'product_compare_page', 0 ) ) ) {
            return;
        }

        $pluginVersion = Assets::getVersion();
        $debugName     = ASSISTANTFORWOOCOMMERCE_DEBUG_MODE ? '' : '.min';

        wp_enqueue_style( ASSISTANTFORWOOCOMMERCE_PLUGIN_KEY . '-product-compare-style',
                Assets::url( 'css/product-compare' . $debugName . '.css' ),
                false, $pluginVersion );

        wp_enqueue_script( ASSISTANTFORWOOCOMMERCE_PLUGIN_KEY . '-product-compare-script',
                Assets::url( 'js/product-compare.min.js' ),
                [ ASSISTANTFORWOOCOMMERCE_PLUGIN_SLUG . '-global' ], $pluginVersion, [ 'in_footer' => true ] );

        wp_localize_script( ASSISTANTFORWOOCOMMERCE_PLUGIN_KEY . '-product-compare-script', ASSISTANTFORWOOCOMMERCE_PLUGIN_KEYCAP . 'ProductCompare', array(
                'maxExceededMessage' => esc_html__( 'It is not possible to add more than %number% product to the comparison.', 'assistant-for-woocommerce' ),
        ) );
    }

    public function addSectionSettings( $sections ) {
        $settings = array(
                'start_grid_product_compare'         => array(
                        'title' => esc_html__( 'Product Compare', 'assistant-for-woocommerce' ),
                        'type'  => 'startgrid',
                ),
                'product_compare_button_text'        => array(
                        'id'      => 'product_compare_button_text',
                        'title'   => esc_html__( 'Button Text', 'assistant-for-woocommerce' ),
                        'type'    => 'text',
                        'default' => esc_html__( 'Compare', 'assistant-for-woocommerce' ),
                        'desc'    => esc_html__( 'Compare button text', 'assistant-for-woocommerce' )
                ),
                'product_compare_archive_button'     => array(
                        'id'       => 'product_compare_archive_button',
                        'title'    => esc_html__( 'Archive compare button', 'assistant-for-woocommerce' ),
                        'type'     => 'toggle',
                        'value'    => 1,
                        'default'  => false,
                        'desc'     => esc_html__( 'Display compare button in WooCommerce archive pages', 'assistant-for-woocommerce' ),
                        'sanitize' => 'bool'
                ),
                'product_compare_page'               => array(
                        'id'                => 'product_compare_page',
                        'title'             => esc_html__( 'Compare page', 'assistant-for-woocommerce' ),
                        'type'              => 'postSelect',
                        'args'              => array(
                                'post_type' => 'page'
                        ),
                        'default'           => 0,
                        'option_none'       => '---',
                        'option_none_value' => '',
                    /* translators: %s: Shortcode */
                        'desc'              => wp_sprintf( esc_html__( 'Insert shortcode in the compare page %s', 'assistant-for-woocommerce' ), '<code class="asfowoo-copy-text">[asfowoo_products_compare]</code>' )
                ),
                'product_compare_max_items'          => array(
                        'id'      => 'product_compare_max_items',
                        'title'   => esc_html__( 'Max product items', 'assistant-for-woocommerce' ),
                        'type'    => 'select',
                        'options' => array( 2, 3, 4 ),
                        'default' => 0,
                        'desc'    => esc_html__( 'Select max product items for comparing.', 'assistant-for-woocommerce' )
                ),
                'product_compare_image_size'         => array(
                        'id'                => 'product_compare_image_size',
                        'title'             => esc_html__( 'Image size', 'assistant-for-woocommerce' ),
                        'type'              => 'imageSizeSelect',
                        'default'           => 'thumbnail',
                        'option_none'       => '---',
                        'option_none_value' => '',
                        'desc'              => esc_html__( 'Select product image size', 'assistant-for-woocommerce' )
                ),
                'product_compare_add_to_cart_button' => array(
                        'id'       => 'product_compare_add_to_cart_button',
                        'title'    => esc_html__( 'Add to cart button', 'assistant-for-woocommerce' ),
                        'type'     => 'toggle',
                        'value'    => 1,
                        'default'  => false,
                        'desc'     => esc_html__( 'Display add to cart button in product compare page', 'assistant-for-woocommerce' ),
                        'sanitize' => 'bool'
                ),
                'end_grid_product_compare'           => array(
                        'type' => 'endgrid',
                ),

                'product_compare_sep_1' => array(
                        'type' => 'hr',
                ),

                'start_grid_product_compare_fields' => array(
                        'title' => esc_html__( 'Product fields', 'assistant-for-woocommerce' ),
                        'type'  => 'startgrid',
                ),
        );

        $fields = Product::getFields();
        foreach ( $fields as $key => $field ) {
            $settings[ 'product_compare_display_field_' . $key ] = array(
                    'title'    => $field,
                    'id'       => 'product_compare_display_field_' . $key,
                    'type'     => 'toggle',
                    'value'    => 1,
                    'default'  => false,
                    'sanitize' => 'bool'
            );
        }

        $settings['end_grid_product_compare_fields'] = array(
                'type' => 'endgrid',
        );

        $settings['product_compare_sep_2'] = array(
                'type' => 'hr',
        );

        $settings['start_grid_product_compare_attributes'] = array(
                'title' => esc_html__( 'Product attributes', 'assistant-for-woocommerce' ),
                'type'  => 'startgrid',
        );

        $attributes = WooCommerce::getAttributeTaxonomies();
        if ( empty( $attributes ) ) {
            $settings['product_compare_no_attributes_notice'] = array(
                    'id'      => 'product_compare_no_attributes_notice',
                    'notices' => array(
                            array(
                                    'message' => esc_html__( 'Your product attributes is empty, Add attribute in "Products > Attributes" menu.', 'assistant-for-woocommerce' ),
                                    'type'    => 'warning',
                            )
                    ),
                    'type'    => 'notice',
            );

        } else {
            foreach ( $attributes as $key => $attribute ) {
                $settings[ 'product_compare_display_attribute_' . $key ] = array(
                        'title'    => $attribute['label'],
                        'id'       => 'product_compare_display_attribute_' . $key,
                        'type'     => 'toggle',
                        'value'    => 1,
                        'default'  => false,
                        'sanitize' => 'bool'
                );
            }
        }

        $settings['end_grid_product_compare_attributes'] = array(
                'type' => 'endgrid',
        );

        $sections[ $this->currentSection ] = array(
                'title'        => esc_html__( 'Compare', 'assistant-for-woocommerce' ),
                'desc'         => esc_html__( 'Product compare', 'assistant-for-woocommerce' ),
                'settings_key' => $this->addonID,
                'settings'     => $settings
        );

        return $sections;
    }

    public function info(): array {
        $icon = '<svg viewBox="-2.4 -2.4 28.80 28.80" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"><path transform="translate(-2.4, -2.4), scale(0.8999999999999999)" d="M16,31.467482481684C18.84255985126034,31.603749562620035,20.89471133259648,28.7728702577441,22.93099749938608,26.784842624061575C24.638707111052902,25.11760451496447,25.610044723009644,23.001713938450422,26.79388692932484,20.929402991523848C28.10938702016916,18.62662545546618,30.136850140897902,16.598221143189893,30.26356761714277,13.949208581725268C30.399891215870568,11.099381263856515,29.483239328879527,8.08690269082912,27.506637601285018,6.029441761501056C25.55981630657403,4.00297952870398,22.571987119449656,3.520099739927723,19.800178373350956,3.057781008375736C17.288612971765605,2.6388690858612045,14.809062169926337,2.920832955109034,12.329059749747332,3.4979256349895618C9.714536660436975,4.106321063938669,6.9703594733662175,4.639424608045399,5.030307038786056,6.4947069406251945C3.0055797422511006,8.430964288415007,1.7678161300663606,11.124407653740711,1.4441838866866465,13.907189591523261C1.1250687666898305,16.651130623871314,1.730451040760381,19.51528845871897,3.247557410212421,21.823845391649566C4.664592284987071,23.980125102970675,7.425283274410284,24.583874538762366,9.483298967503366,26.14018473816519C11.767421461728864,27.86748115596164,13.139586432224295,31.330359526479455,16,31.467482481684" fill="#fff" strokewidth="0"></path></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g clip-path="url(#clip0_105_1836)"> <path d="M13 3.99976H6C4.89543 3.99976 4 4.89519 4 5.99976V17.9998C4 19.1043 4.89543 19.9998 6 19.9998H13M17 3.99976H18C19.1046 3.99976 20 4.89519 20 5.99976V6.99976M20 16.9998V17.9998C20 19.1043 19.1046 19.9998 18 19.9998H17M20 10.9998V12.9998M12 1.99976V21.9998" stroke="#873eff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path> </g> <defs> <clipPath id="clip0_105_1836"> <rect fill="white" height="24" transform="translate(0 -0.000244141)" width="24"></rect> </clipPath> </defs> </g></svg>';

        return array(
                'id'             => $this->addonID,
                'title'          => esc_html__( 'Products Compare', 'assistant-for-woocommerce' ),
                'desc'           => esc_html__( 'Enables customers to compare products.', 'assistant-for-woocommerce' ),
                'tags'           => [ esc_html__( 'Product', 'assistant-for-woocommerce' ) ],
                'cat'            => 'product',
                'icon'           => $icon,
                'more_info_link' => 'https://parsa.ws',
                'settings_key'   => $this->addonID,
        );
    }
}