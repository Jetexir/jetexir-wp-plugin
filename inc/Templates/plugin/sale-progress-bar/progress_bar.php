<?php
defined( 'ABSPATH' ) or die();

if ( ! isset( $args ) ) {
    return;
}

$style = [];
if ( ! empty( $args['progress_bar_height'] ) ) {
    $style[] = '--wa-progress-bar-height: ' . esc_html( $args['progress_bar_height'] ) . 'px';
}
if ( ! empty( $args['progress_bar_bg_color'] ) ) {
    $style[] = '--wa-progress-bar-bg-color: ' . esc_html( $args['progress_bar_bg_color'] );
}
?>

<div class="wa-sale-progress-bar"
     style="<?php echo implode( '; ', $style ) ?>">
    <div class="wa-spb-stock-info">
        <div class="wa-spb-total-sold">
            <?php echo esc_html( $args['sold_title'] ) ?>
            <span><?php echo esc_html( $args['sold'] ) ?></span>
        </div>
        <div class="wa-spb-current-stock">
            <?php echo esc_html( $args['remaining_title'] ) ?>
            <span><?php echo esc_html( $args['stock'] ) ?></span>
        </div>
    </div>
    <div class="wa-spb-progress-area">
        <div class="wa-spb-progress-bar" style="width:<?php echo esc_html( $args['sold_percent'] ) ?>%;"></div>
    </div>
</div>
