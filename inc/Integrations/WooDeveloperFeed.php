<?php

namespace Jetexir\Integrations;

defined( 'ABSPATH' ) || exit;

use Jetexir\Addons\Addon;
use Jetexir\Helper\FeedReader;
use Jetexir\Helper\Templates;
use Jetexir\Interfaces\AddonInterface;

class WooDeveloperFeed extends Addon implements AddonInterface {
  public string $addonID = 'woo-developer-feed';

  public function content(): void {
    $feedReader = new FeedReader( [ 'url' => 'https://developer.woocommerce.com/feed/' ] );
    $feedItems  = $feedReader->replaceDescText( array(
      [ 'The post %title% appeared first on The WooCommerce Developer Blog.', '' ]
    ) )->read()->getFeedLinks();

    Templates::load( Templates::getPath( 'feed-reader/feed_list.php' ), array( 'items' => $feedItems ) );
  }

  public function info(): array {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 183.6 47.5"><path d="M70.141 3.572c-3.638 0-6.011 1.187-8.125 5.167L52.36 26.945V10.77c0-4.827-2.287-7.198-6.521-7.198-4.237 0-6.014 1.439-8.132 5.504l-9.145 17.869V10.94c0-5.167-2.119-7.368-7.284-7.368H10.776c-3.981 0-6.182 1.864-6.182 5.25 0 3.389 2.116 5.422 6.013 5.422h4.319v20.41c0 5.757 3.895 9.146 9.486 9.146 5.59 0 8.13-2.203 10.924-7.37l6.097-11.431v9.655c0 5.671 3.726 9.146 9.402 9.146 5.674 0 7.79-1.949 11.011-7.37l14.055-23.711c3.051-5.167.933-9.147-5.842-9.147h.082Zm36.908 0c-11.517 0-20.24 8.554-20.24 20.157 0 11.601 8.806 20.071 20.24 20.071s20.157-8.553 20.242-20.071c0-11.603-8.808-20.157-20.242-20.157m0 27.863c-4.319 0-7.283-3.217-7.283-7.706 0-4.49 2.964-7.792 7.283-7.792 4.32 0 7.285 3.302 7.285 7.792 0 4.489-2.879 7.706-7.285 7.706m51.794-27.863c-11.431 0-20.242 8.554-20.242 20.157 0 11.601 8.811 20.071 20.242 20.071 11.435 0 20.241-8.553 20.241-20.071s-8.806-20.157-20.241-20.157m0 27.863c-4.404 0-7.197-3.217-7.197-7.706 0-4.49 2.879-7.792 7.197-7.792 4.319 0 7.284 3.302 7.284 7.792 0 4.489-2.88 7.706-7.284 7.706" stroke="#873eff" stroke-width="7px" style="stroke:#873eff;fill:none;paint-order:fill;stroke-width:7px;fill-rule:evenodd;clip-rule:evenodd"/></svg>';

    return array(
      'id'             => $this->addonID,
      'title'          => esc_html__( 'Woo Developer Feed', 'jetexir' ),
      'desc'           => esc_html__( 'WooCommerce Developer Blog RSS Feed', 'jetexir' ),
      'has_page'       => true,
      'force_enable'   => false,
      'content_header' => true,
      'icon'           => $svg,
      'image_link'     => 'https://developer.woocommerce.com',
      'tags'           => [ esc_html__( 'Feed', 'jetexir' ) ],
      'cat'            => 'utility',
    );
  }
}
