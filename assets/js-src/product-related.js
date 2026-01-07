jQuery(document).ready(function ($) {
  let relatedProducts = $('.related .products');

  if (relatedProducts.length === 0)
    return;

  relatedProducts.addClass('jetexir-related-products owl-carousel owl-theme');

  relatedProducts.owlCarousel({
    rtl: Jetexir.direction === 'rtl',
    loop: JetexirProductRelated.loop === '1',
    center: JetexirProductRelated.center === '1',
    margin: parseInt(JetexirProductRelated.margin),
    nav: JetexirProductRelated.arrow === '1',
    dots: JetexirProductRelated.dots === '1',
    autoplay: JetexirProductRelated.autoplay === '1',
    autoplayTimeout: parseInt(JetexirProductRelated.autoplayTimeout),
    autoplayHoverPause: JetexirProductRelated.autoplay === '1',
    responsiveClass: true,
    lazyLoad: true,
    navText: ['<i class="jetexir-icon-chevron-left"></i>', '<i class="jetexir-icon-chevron-right"></i>'],
    responsive: {
      0: {
        items: parseInt(JetexirProductRelated.mobileLimit),
      },
      600: {
        items: parseInt(JetexirProductRelated.tabletLimit),
      },
      1200: {
        items: parseInt(JetexirProductRelated.desktopLimit),
      }
    }
  })
});
