/* IE Clear type fix */
(function ($) {
    $.fn.customFadeIn = function (speed, callback) {
        $(this).fadeIn(speed, function () {
            if (jQuery.browser.msie) $(this).get(0).style.removeAttribute('filter');
            if (callback != undefined) callback();
        });
    };
    $.fn.customFadeOut = function (speed, callback) {
        $(this).fadeOut(speed, function () {
            if (jQuery.browser.msie) $(this).get(0).style.removeAttribute('filter');
            if (callback != undefined) callback();
        });
    };
    $.fn.customFadeTo = function (speed, callback) {
        $(this).fadeTo(speed, function () {
            if (jQuery.browser.msie) $(this).get(0).style.removeAttribute('filter');
            if (callback != undefined) callback();
        });
    };
    $.fn.customToggle = function (speed, callback) {
        $(this).toggle(speed, function () {
            if (jQuery.browser.msie) $(this).get(0).style.removeAttribute('filter');
            if (callback != undefined) callback();
        });
    };

})(jQuery);

jQuery.fn.extend({
	  slideRightShow: function() {
	    return this.each(function() {
	        $(this).show('slide', {direction: 'right'}, 1000);
	    });
	  },
	  slideLeftHide: function() {
	    return this.each(function() {
	      $(this).hide('slide', {direction: 'left'}, 1000);
	    });
	  },
	  slideRightHide: function() {
	    return this.each(function() {
	      $(this).hide('slide', {direction: 'right'}, 1000);
	    });
	  },
	  slideLeftShow: function() {
	    return this.each(function() {
	      $(this).show('slide', {direction: 'left'}, 1000);
	    });
	  }
});

$(document).ready(function(){
	$('.tooltip').simpletooltip();
});

$(document).ready(function() {
	//menu
	$('#menu ul li:has(ul) > a, #submenu ul li:has(ul) > a').addClass('more');
	$('#menu ul li a.more, #submenu ul li a.more').append('<span class="arrow">&nbsp;&nbsp;&raquo;</span>');
	$('#menu ul li, #submenu ul li').hover(function () {
		$(this).find('ul:first').stop(true, true).animate({opacity: 'toggle', height: 'toggle'}, 200).addClass('active_list');
	}, function () {
		$(this).children('ul.active_list').stop(true, true).animate({opacity: 'toggle', height: 'toggle'}, 200).removeClass('active_list');
	});
});