(function ($) {
    Drupal.behaviors.carousel = {
        attach: function (context, settings) {
            $('.owl-slider-wrapper', context).each(function () {
                var $this = $(this);
                var $this_settings = $.parseJSON($this.attr('data-settings'));
                $this.owlCarousel($this_settings);
            });

        }
    };
	if ( $(window).width() < 767) {
            $('.owl-responsive').owlCarousel({
				responsiveClass: true,
				margin: 0,
				items: 1,
                nav: true,
                responsive: {
                  0: {
                    items: 1,
                    nav: true,
					margin: 0,
					autoplay: true,
					loop: true,
					dots: false
                  },
                  480: {
                    items: 2,
                    nav: true,
					margin: 20,
					autoplay: true,
					loop: true,
					dots: false
                  }
                }

            });

	};
})(jQuery);