var WMApp = (new function() {
	this.extend = function(data) {
		$.extend(true, this, data);
	};
	this.def = {};
	this.define = function(key, value) {
		if(typeof value == 'undefined') {
			return typeof this.def[key] == 'undefined' ? null : this.def[key];
		}
		this.def[key] = value;
	}
});

//link actions
WMApp.extend({
	'popup': function(link) {
		$('<a class="event-dialog-popup">')
			.attr('href', link)
			.appendTo('body')
			.click();
		return this;
	},
	'like': function(link) { 
		$('a[href="'+link+'"]')
			.removeClass('event-dialog-popup')
			.addClass('event-like-click')
			.eq(0)
			.click();
		return this;
	},
	'follow-user': function(link) { 
		$('a[href="'+link+'"]')
			.removeClass('event-dialog-popup')
			.addClass('event-follow-user')
			.eq(0)
			.click();
		return this;
	},
	'follow-wishlist': function(link) { 
		$('a[href="'+link+'"]')
			.removeClass('event-dialog-popup')
			.addClass('event-follow-wishlist')
			.eq(0)
			.click();
		return this;
	},
	'follow-single': function(link) { 
		$('a[href="'+link+'"]')
			.removeClass('event-dialog-popup')
			.addClass('event-follow-single')
			.eq(0)
			.click();
		return this;
	}
});

//user
WMApp.extend({
	'isLogged': function(callback) {
		$.ajax({
			url: '?widget=user.widget.login&waction=check-loged',
			dataType: 'jsonp',
			success: function(json) {
				if($.isFunction(callback))
					callback.call(this, json);
			}
		});
	},
	'getUserData': function(callback) {
		$.ajax({
			url: '?widget=user.widget.login&waction=get-data',
			dataType: 'jsonp',
			success: function(json) {
				if($.isFunction(callback))
					callback.call(json, this);
			}
		});
	}
});

//pin
WMApp.extend({
	'pinNavigation': function(link, $link) {
		var result = null;
		for(i in this.pinNavigationDefinitions) {
			if(this.pinNavigationDefinitions[i] && this.pinNavigationDefinitions[i].check && $.isFunction(this.pinNavigationDefinitions[i].check) && this.pinNavigationDefinitions[i].nav && $.isFunction(this.pinNavigationDefinitions[i].nav) && this.pinNavigationDefinitions[i].check(link), element = this.pinNavigationDefinitions[i].check(link)) {
				this.pinNavigationDefinitions[i].nav(link, $link, element);
				return;
			}
		}
		return this;
	},
	'pinNavigationDefinitions': {
		'popup_opener' : {
			'check' : function(link) {
				if(window.popup_opener && $('.event-box .event-popup[href^="' + link + '"]:first', $(window.popup_opener).closest('div[class*=event-masonry]')).size()) 
					return $('.event-box .event-popup[href^="' + link + '"]:first', $(window.popup_opener).closest('div[class*=event-masonry]'));
				return false;
			},
			'nav' : function(link, $link, $element) {
				if($link.hasClass('event-js-prev')) {
					var type = 'prevAll';
				} else {
					var type = 'nextAll';
				}

				var box = $element.closest('.event-box')[type]('.event-item').eq(0),
					ajax_link = box.find('.event-popup.event-history');
				
				if(ajax_link.size()) {
					$link.removeClass('button-color-1').addClass('button-color-2').click(function() {
						$.xhrPool.abortAll();
						App.addLoader( $link );
						var link_call = ajax_link.attr('href');
						jQuery.ajax({
							url:link_call,
							type:'GET',
							dataType: "jsonp",
							jsonpCallback: 'callback',
							data: {'RSP':'ajax'},
							success: function(json) {
								App.removeLoader( $link );
								html = $(json.content);
								$('.container:first', html).append('<button title="Close (Esc)" type="button" class="mfp-close event-popup-close">×</button>');
								//console.log('trish stratas');
								//$('.mfp-content').children().unbind();
								console.log('pistotatatatata');
								$('.mfp-content').empty();
								$('.mfp-content').html(html);
								History.pushState({link: json.url}, json.title, json.url);
							}
						});
						
						var window_top = $(window).scrollTop();
						var box_top = parseInt(box.css('top'));
						if(box_top && box_top > window_top) {
							$('body,html').animate({
								scrollTop: box_top
							}, 800);
						}
						return false;
					});
				} else {
					$link.removeClass('button-color-2').addClass('button-color-1');
				}
			}
		},
		'default' : {
			'check' : function(link) {
				if($('.event-masonry .event-box .event-popup[href^="' + link + '"]:first').size()) 
					return $('.event-masonry .event-box .event-popup[href^="' + link + '"]:first');
				return false;
			},
			'nav' : function(link, $link, $element) {
				WMApp.pinNavigationDefinitions.popup_opener.nav.call(this, link, $link, $element);
			}
		},
		'virtual' : {
			'check' : function(link) {
				if($('.event-pin-popup-navigation .event-pin-navigation[data-href^="' + link + '"]').size()) 
					return $('.event-pin-popup-navigation .event-pin-navigation[data-href^="' + link + '"]:first');
				return false;
			},
			'nav' : function(link, $link, $element) {
				if($link.hasClass('event-js-prev')) {
					var type = 'prevAll';
				} else {
					var type = 'nextAll';
				}
				
				var box = $element[type]('.event-pin-navigation').eq(0),
					ajax_link = box;

				if(box.size()) {
					$link.removeClass('button-color-1').addClass('button-color-2').click(function() {
						$.xhrPool.abortAll();
						App.addLoader( $link );
						var link_call = ajax_link.data('href');
						jQuery.ajax({
							url:link_call,
							type:'GET',
							dataType: "jsonp",
							jsonpCallback: 'callback',
							data: {'RSP':'ajax'},
							success: function(json) {
								App.removeLoader( $link );
								html = $(json.content);
								$('.container:first', html).append('<button title="Close (Esc)" type="button" class="mfp-close event-popup-close">×</button>');

								//$('.mfp-content').children().unbind();
								console.log('wwwwwwwwwwwwww');
								$('.mfp-content').empty();
								$('.mfp-content').html(html);
								History.pushState({link: json.url}, json.title, json.url);
							}
						});
						
						var window_top = $(window).scrollTop();
						var box_top = parseInt(box.css('top'));
						if(box_top && box_top > window_top) {
							$('body,html').animate({
								scrollTop: box_top
							}, 800);
						}
						return false;
					});
				} else {
					$link.removeClass('button-color-2').addClass('button-color-1');
				}
			}
		}
	}
});

//globals
WMApp.extend({
	'userPaneTabs': function() {
		/*	menu tabs */
		$('.event-tabs .event-tab').hide();
		$('.event-tabs .event-tab:first').show();
		$('.event-tabs .event-btns li:first').addClass('active');
		
		$('.event-tabs .event-btns li a').click(function(){
			$('.event-tabs .event-btns li').removeClass('active');
			$(this).parent().addClass('active');
			var currentTab = $(this).attr('href');
			$('.event-tabs .event-tab').hide();
			$(currentTab).show(0,function() {
				var jsp = $(this).find('.event-scroll').data('jsp');
				if(jsp) { jsp.reinitialise(); }
			});
			return false;
		});
	},
	'jsPane': function() {
		/*	scroll pane */
		$('.event-scroll').each(
				function ()
				{
					$(this).jScrollPane(
							{
								showArrows: $(this).is('.arrow'),
								verticalGutter: 5,
								mouseWheelSpeed: 25,
								autoReinitialise: true
							}
					).bind('mousewheel', function(e) {
						e.preventDefault();
					});
					var api = $(this).data('jsp');
					var throttleTimeout;
					$(window).bind(
							'resize',
							function ()
							{
								// IE fires multiple resize events while you are dragging the browser window which
								// causes it to crash if you try to update the scrollpane on every one. So we need
								// to throttle it to fire a maximum of once every 50 milliseconds...
								if (!throttleTimeout) {
									throttleTimeout = setTimeout(
											function ()
											{
												api.reinitialise();
												throttleTimeout = null;
											},
											50
											);
								}
							}
					);
				}
		);
	},
	HTML5FormValidate: function(selector) {
		$(selector).each(function() {
			var form = this;
			H5F.setup(form, {
			    validClass: "valid",
			    invalidClass: "field-color-2",
			    requiredClass: "field-color-2"
			});
		});
	}
});
