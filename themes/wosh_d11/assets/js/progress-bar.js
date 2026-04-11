"use strict";
window.jQuery = window.$ = jQuery;

$(function () {

/*	PROGRESS BAR  */
    $(window).scroll(function () {
        if ($("div").hasClass("progress-content")) {
		if (($(this).scrollTop() + $(this).height()) >= $(".progress-content").offset().top) {
            $('.progress-bar').each(function () {
                var getPercent = $(this).data('percent') / 100;
                var getProgressWrapWidth = $(this).width();
                var progressTotal = getPercent * getProgressWrapWidth;
                var animationLength = 1500;
                $(this).stop().animate({
                    left: progressTotal
                }, animationLength);
            });
        }
		}
    });
});
