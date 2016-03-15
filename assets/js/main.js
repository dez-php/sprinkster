//abort ajax
var abortAjaxRequests = function() {};

(function() {
	$.xhrPool = [];
	$.xhrPool.abortAll = function(url) {
	    $(this).each(function(i, jqXHR) { //  cycle through list of recorded connection
	    	console && console.log && console.log('xhrPool.abortAll ' + jqXHR.requestURL);
	        if (!url || url === jqXHR.requestURL) {
	            jqXHR.abort(); //  aborts connection
	            $.xhrPool.splice(i, 1); //  removes from list by index
	        }
	    });
	};
	$.ajaxSetup({
	    beforeSend: function(jqXHR) {
	        $.xhrPool.push(jqXHR); //  add connection to list
	    },
	    complete: function(jqXHR) {
	        var i = $.xhrPool.indexOf(jqXHR); //  get index for current connection completed
	        if (i > -1) $.xhrPool.splice(i, 1); //  removes from list by index
	    }
	});
	$.ajaxPrefilter(function(options, originalOptions, jqXHR) {
		console && console.log && console.log('ajaxPrefilter ' + options.url);
	    jqXHR.requestURL = options.url;
	});
})(jQuery);


$(document).ready(function () {

    /* close magnific/dialog */
    $('[class*="-cancel"]').on('click', function() {
        $.magnificPopup.close();
        $(this).closest('.event-dialog-popup-holder').dialog('close');
        return false;
    })


    /* Store profile toggle */
    $('.event-toggle-profilebox').on('click', function() {
        $(this).toggleClass('active');
        $('.profiletogglebox').slideToggle(function(){
            $(document).trigger('store-open');
        });
        return false;
    });

    /* Dropdowns */
    if (!browserDetect.isDesktop){
        $('li, .dropdown-show').on('click', function(e){
            $(this).find('.dropdown').show();
        });
        $('.list-23 > li > a.member').on('click', function(e) {
            e.preventDefault();
            $(this).parent().find('.dropdown').show();
        });
    }

    $(document).on('click touchstart', function(e) {
        var tag = $(e.target||e.toElement);
        var tagName = tag.get(0).tagName;
        if (!browserDetect.isDesktop){
            if (
                tagName != 'SPAN' &&
                tagName != 'A' &&
                tagName != 'IMG' &&
                !tag.hasClass('event-scroll-cats') &&
                !tag.parents('.dropdown').size()
                ) {
                $('.dropdown').hide();
            }
        }
        if (tagName != 'A' && !tag.parents('.event-custom-options').size()) {
            $('.event-custom-options').hide();
        }
        if (!tag.parents('.autocomplete').size()) {
            $('.autocomplete').addClass('hide');
        }
    });

	/*	scroll to top */
	$(window).scroll(function() {
		if ($(this).scrollTop() > 200) {
			$('.event-scrolltotop').fadeIn();
     	}
    	else {
      		$('.event-scrolltotop').fadeOut();
     	}
 	});
    $('.event-scrolltotop').click(function () {
        $('body,html').animate({
            scrollTop: 0
        }, 500);
        return false;
    });
    
    /*	fittext */
    $('.event-fittext').fitText(1.5);

	/*	selectBox */
	$('select').selectbox({
		speed: 0,
		effect: 'fade'
	});

	/*	tooltip */
    if ($(window).width() > 1250) {
        $('.event-tooltip').aToolTip({
    		xOffset: 10,
            yOffset: 5
    	});
    }

	$("textarea.event-elastic").elastic();

	$(".event-focus").trigger("focus");

	/*	footer toggle */
	$('footer .toggle').on('click', function (event) {
		event.preventDefault();
		$(this).parent('footer').toggleClass('active');
		$(this).toggleClass('active').parent().find('.footer-hidden-part').toggle();
	});
	
	/*	magnific popup */
    var isIOS8 = function() {
      var deviceAgent = navigator.userAgent.toLowerCase();
      return /(iphone|ipod|ipad).* os 8_/.test(deviceAgent);
    }
	var link = window.location.href, title = document.title;
	window.popup_opener = null;
	$(document).off('.event-popup').on('contextmenu.event-popup', function (e) {
		var $target = $(e.srcElement || e.target || e.toElement);
		if ($target.hasClass('.event-popup .event-popup-image')) {
			e.preventDefault();
			$(this).click();
		} else if ($target.closest('.event-popup, .event-popup-image').size()) {
			e.preventDefault();
			$target.closest('.event-popup, .event-popup-image').click();
		}
	}).on('click.event-popup', '.event-popup:not(.event-history)', function () {
        if(isIOS8() && $(this).find('.icon-16').size())
            return
        $(this).magnificPopup({
			fixedContentPos:true,
			type: 'ajax',
			closeOnBgClick: false,
			enableEscapeKey: false,
			callbacks: {
				ajaxContentAdded: function () {
					$("select").selectbox({
						speed: 1,
						effect: 'fade'
					});
					$("textarea.event-elastic").elastic();
					$(".event-tooltip").aToolTip();
					$(".event-focus").trigger("focus");
                    $("body").css({ overflow: 'hidden' });
					initCLEditor();
					WMApp.jsPane();
				},
                close: function() {
                    $("body").css({ overflow: 'inherit' });
                }
			}
		}).magnificPopup('open');
		return false;
	}).on('click.event-popup', '.event-popup.event-history', function () {
		window.popup_opener = $(this);
		$(this).magnificPopup({
			fixedContentPos:true,
			type: 'ajax',
			ajax: {
				settings: {
					dataType: "jsonp"
				}
			},
			closeOnBgClick: false,
			callbacks: {
				beforeClose: function () {
					History.pushState({link: link}, title, link);
				},
				ajaxContentAdded: function () {
					$("select").selectbox({
						speed: 1,
						effect: 'fade'
					});
					$("textarea.event-elastic").elastic();
					$(".event-tooltip").aToolTip();
					$(".event-focus").trigger("focus");

					initCLEditor();
                    $("body").css({ overflow: 'hidden' });
				},
				parseAjax: function(json) {
					this.contentContainer.empty();
					History.pushState({link: json.data.url}, json.data.title, json.data.url);
					json.data = json.data.content;
                    $("body").css({ overflow: 'hidden' });
				},
                close: function() {
                    $("body").css({ overflow: 'inherit' });
                }
			}
		}).magnificPopup('open');
		return false;
	}).on('click.event-popup', '.event-popup-image', function () {
		$(this).magnificPopup({
			type: 'image',
			closeOnBgClick: false
		}).magnificPopup('open');
	}).on('click.event-popup', '.event-popup-close', function () {
		if($(this).closest('.event-dialog-popup').size()) {
			$(this).closest('.event-dialog-popup').find('.event-dialog-close').click();
		} else if ($('.mfp-container').size()) {
			$.magnificPopup.close();
		}
		return false;
	}).on('click.event-popup', '.event-dialog-popup', function () {
		var url = $(this).attr('href') ? $(this).attr('href') : '';
		url += url.indexOf('?') > -1 ? '&' : '?';
		url += 'RSP=ajax';
		$('<div class="event-dialog-popup-holder">').dialog({
	 		resizable: false,
	 		draggable: false,
	 		autoResize: false,
	 		minHeight: '0',
	 		maxHeight: '0',
	 		width: '100%',
	 		center: false,
	 		modal: true,
	 		dialogClass: 'dialog',
	 		closeOnEscape: false,
	 		open: function(event, ui) {
                $("body").css({ overflow: 'hidden' });
                var $this = $(this);
	 			$(this).parent().wrapInner('<div class="dialog-content-wrapper"><div class="dialog-content"></div></div>');
	 			$('html').addClass('dialog-popup-open-ajax');
	 			$.ajax({
	 				'url' : url,
	 				success: function(res) {
	 					var $res = $(res);
	 					//$("select", $res).selectbox();
	 					$('select', $res).selectbox();
	 					$($res).prepend('<button class="event-dialog-close" />');
	 					$this.append($res);
						$('.mfp-wrap').hide();
	 				}
	 			});
	 		},
	 		close: function( event, ui ) {
                $("body").css({ overflow: 'inherit' });
                $(this).remove();
	 			$('html').removeClass('dialog-popup-open-ajax');
				$('.mfp-wrap').show();
	 		}
		});
		
		return false;
	}).on('click', ".event-dialog-close, .event-dialog-close-button", function(){
			$(this).closest('.event-dialog-popup-holder').dialog('close');
			return false;
	}).on('keyup', function(e) {
		if((e.keyCode || e.which) == 27 && $('.event-dialog-popup').size()) {
			$('.event-dialog-popup:last-child').find('.event-dialog-close').click();
	        e.stopPropagation();
			return false;
		} else if ((e.keyCode || e.which) == 27 && $('.mfp-container').size()) {
			$.magnificPopup.close();
	        e.stopPropagation();
			return false;
		}
	}).on('click', '.event-dialog-close-all', function() {
		$('.event-dialog-popup-holder').each(function() {
			$(this).find('.event-dialog-close').click();
		});
	});	
	$(window).resize(function() {
		$(".event-dialog-popup").size() ? $(".event-dialog-popup").dialog("option", "position", {my: "center", at: "center", of: window}) : null;
	});

	/* search submit */
	$(document).on('submit.loader', '.event-pin-filter-form', function() {
		App.addLoader($('.event-button-loader',this));
	});
	
	/* pin like */
	$(document).off('click.event-like-click').on("click.event-like-click", ".event-like-click", function() {
		var link = $(this);
		WMApp.isLogged(function(logged) {
			if(!logged)
				return link.removeClass('event-like-click').addClass('event-dialog-popup').click();
			
			App.addLoaderNoBg($('a[href="'+link.attr('href')+'"]'));

			App._ajax({
				url: link.attr('href'),
				onSuccess: function(result){
					App.removeLoader($('a[href="'+link.attr('href')+'"]'));
					if(result.location) {
						window.location = result.location;
					} else if(result.error) {
						alert(result.error);
					} else if(result.trues) {
						link.addClass('active');
						if(link.data('textactive')) {
							$('a[href="'+link.attr('href')+'"]').each(function() {
								$(this).attr('title', $(this).data('textactive'));
								if($(this).hasClass('event-text-like-change')) { $(this).text($(this).data('textactive')); }
							});
						}
					} else if(result.falses) {
						link.removeClass('active');
						if(link.data('textinactive')) { 
							$('a[href="'+link.attr('href')+'"]').each(function() {
								$(this).attr('title', $(this).data('textinactive'));
								if($(this).hasClass('event-text-like-change')) { $(this).text($(this).data('textinactive')); }
							}); 
						}
					}
					$('#aToolTip').remove();
					link.aToolTip({
						xOffset: 10, 
				        yOffset: 5 
					});
					if(result.info) {
						for(i in result.info.stats) {
							$('.event-stats-'+result.info.id+' .event-' + i).html(result.info.stats[i]);
							if(i == 'likes')
								$('.event-stats-'+result.info.id+' .event-' + i)[result.info.stats[i]?'removeClass':'addClass']('hide');
							if(i == 'repins')
								$('.event-stats-'+result.info.id+' .event-' + i)[result.info.stats[i]?'removeClass':'addClass']('hide');
							
						}
					}
					if(result.infouser) {
						for(i in result.infouser.stats) {
							$('.event-stats-user-'+result.infouser.id+' .event-' + i).html(result.infouser.stats[i]);
						}
					}
				}
			});
			
		});
		
		return false;
	});
	
	// // Observe a specific DOM element:
	// observeDOM( document.getElementById('body'), function() {
	// 	$('select').selectbox();

	//     $('.event-popup').magnificPopup({
	// 		type:'ajax',
	// 		closeOnBgClick: false,
	// 		callbacks: {
	// 			ajaxContentAdded: function() {
	// 				$('select').selectbox();
	// 			}
	// 		}
	// 	});
	// });

	//js pane
	WMApp.jsPane();
	
	//user pane tabs
	WMApp.userPaneTabs();
	
	
	/*	dialog */
//	$('.event-dialog').click(function() {    
//		$('#test-dialog').dialog({
//     		resizable: false,
//     		draggable: false,
//     		autoResize: false,
//     		minHeight: '0',
//     		maxHeight: '0',
//     		width: '100%',
//     		center: false,
//     		modal: true,
//     		dialogClass: 'dialog',
//     		open: function(event, ui) {
//     			$(this).prepend('<button class="event-dialog-close" />');
//     			$(this).parent().wrapInner('<div class="dialog-content-wrapper"><div class="dialog-content"></div></div>');
//     			$('html').css({'overflow':'hidden'});
//     			$(".event-dialog-close").on('click', function(){
//     				$(this).parent().dialog('close');
//				});	
//     		},
//     		close: function( event, ui ) {
//     			$(this).dialog('destroy');	
//     		}
//		});
//        return false;
//    });
    /*$(window).resize(function() {
    	$("#test-dialog").dialog("option", "position", {my: "center", at: "center", of: window});
	});*/
 
	

	/*	thumbs scroller */
	$(".event-thumbs-scroller").mThumbnailScroller({
		axis: "x",
		easing: "easeInOutSmooth",
		speed: 60
	});

	$('img[data-original], .event-load-lazy-load').LazyLoad();

	$(document).on('click', '.event-follow-single', function (event) {
		var link = $(this);
		event.preventDefault();
		WMApp.isLogged(function(logged) {
			if(!logged)
				return link.removeClass('event-follow-single').addClass('event-dialog-popup').click();
			
			App.addLoader(link);
			App._ajax({
				url: link.attr('href'),
				onSuccess: function (result) {
					App.removeLoader(link);

					if (result.location)
					{
						if(result.popup)
							return $('<a href="' + result.location + '" class="hide event-dialog-popup"></a>').appendTo("body").trigger("click").remove();

						window.location = result.location;
						return;
					}

					if (result.error) {
						alert(result.error);
						return;
					}

					if (result.isFollow === true && link.data('textactive')) {
						link.text(link.data('textactive')).removeClass('button-color-2').addClass('button-color-3');
					}

					if (result.isFollow === false && link.data('textinactive')) {
						link.text(link.data('textinactive')).removeClass('button-color-3').addClass('button-color-2');
					}

					if (result.info && result.info.group) {
						for(i in result.info.stats) {
							$('.event-' + i + '-' + result.info.group + '-' + result.info.id).html(result.info.stats[i]);
						}
					}

				}
			});
			
		});
		
		return false;
	});

	$(document).on('click', '.event-follow-user', function (event) {
		event.preventDefault();
		var link = $(this);
		WMApp.isLogged(function(logged) {
			if(!logged)
				return link.removeClass('event-follow-user').addClass('event-dialog-popup').click();
			
			App.addLoader(link);
			App._ajax({
				url: link.attr('href'),
				onSuccess: function (result) {
					App.removeLoader(link);

					if (result.location) {
						if(result.popup)
							return $('<a href="' + result.location + '" class="hide event-popup"></a>').appendTo("body").trigger("click").remove();

						window.location = result.location;
						return;
					}

					if (result.error) {
						alert(result.error);
						return;
					}

					if (result.isFollow === true) {
						$('a[data-userid="' + link.data('userid') + '"]').each(function () {
							$(this).removeClass('button-color-2').addClass('button-color-3');
							if($(this).data('textactive')) { $(this).text($(this).data('textactive')); }
						});
					}

					if (result.isFollow === false) {
						$('a[data-userid="' + link.data('userid') + '"]').each(function () {
							$(this).removeClass('button-color-3').addClass('button-color-2');
							if($(this).data('textinactive')) { $(this).text($(this).data('textinactive')); }
						});
					}

					if (result.info) {
						$('.event-followers-' + result.info.id).html(result.info.stats.followers);
					}
				}
			});
			
		});
		
		return false;
		
	});


	$(document).on('click', '.event-follow-wishlist', function (event) {
		event.preventDefault();
		var link = $(this);
		WMApp.isLogged(function(logged) {
			if(!logged)
				return link.removeClass('event-like-click').addClass('event-dialog-popup').click();
			
			App.addLoader(link);
			App._ajax({
				url: link.attr('href'),
				onSuccess: function (result) {
					App.removeLoader(link);

					if (result.location) {
						if(result.popup)
							return $('<a href="' + result.location + '" class="hide event-popup"></a>').appendTo("body").trigger("click").remove();

						window.location = result.location;
						return;
					}

					if (result.error) {
						alert(result.error);
						return;
					}

					if (result.isFollow === true) {
						$('.event-follow-user[data-userid="' + link.data('userid') + '"]').each(function () {
							$(this).removeClass('button-color-2').addClass('button-color-3');
							if($(this).data('textactive')) { $(this).text($(this).data('textactive')); }
						});

						$('.event-follow-wishlist[data-wishlistid="' + link.data('wishlistid') + '"]:not(.event-follow-user)').each(function () {
							$(this).removeClass('button-color-2').addClass('button-color-3');
							if($(this).data('textactive')) { $(this).text($(this).data('textactive')); }
						});
					}

					if (result.isFollow === false) {
						if (result.isFollowUser === true) {
							$('.event-follow-user[data-userid="' + link.data('userid') + '"]').each(function () {
								$(this).removeClass('button-color-2').addClass('button-color-3');
								if($(this).data('textactive')) { $(this).text($(this).data('textactive')); }
							});
						} else {
							$('.event-follow-user[data-userid="' + link.data('userid') + '"]').each(function () {
								$(this).removeClass('button-color-3').addClass('button-color-2');
								if($(this).data('textinactive')) { $(this).text($(this).data('textinactive')); }
							});
						}

						$('.event-follow-wishlist[data-wishlistid="' + link.data('wishlistid') + '"]:not(.event-follow-user)').each(function () {
							$(this).removeClass('button-color-3').addClass('button-color-2');
							if($(this).data('textinactive')) { $(this).text($(this).data('textinactive')); }
						});
					}
			
					if (result.infouser) {
						for (i in result.infouser.stats) {
							$('.event-stats-user-' + result.infouser.id + ' .event-' + i).html(result.infouser.stats[i]);
						}
					}
					
					if (result.infowishlist) {
						for (i in result.infowishlist.stats) {
							$('.event-stats-wishlist-' + result.infowishlist.id + ' .event-' + i).html(result.infowishlist.stats[i]);
						}
					}
				}
			});
			
		});
		return false;
	});

	initCLEditor();
	
	//ajax request
	var oldbeforeunload = window.onbeforeunload;
	window.onbeforeunload = function() {
		var r = oldbeforeunload ? oldbeforeunload() : undefined;
		if (r == undefined) {
			// only cancel requests if there is no prompt to stay on the page
			// if there is a prompt, it will likely give the requests enough time to finish
			$.xhrPool.abortAll();
		}
		return r;
	}
	//end ajax request
	
	//prev next item buttons
	$(document).on('click','.event-turbo-click',function() {
		//abort Ajax Requests
		$.xhrPool.abortAll();
		var $this = $(this),
			link = $this.attr('href');
		link += link.indexOf('?') > -1 ? '&' : '?';
		link += 'RSP=html&nolayout=true';
		if(!$this.hasClass('no-loader')) {
			if($this.hasClass('event-full-loader')) {
				App.addLoaderFull( $this );
			} else if($this.hasClass('event-nobg-loader')) {
				App.addLoaderNoBg( $this );
			} else {
				App.addLoader( $this );
			}
		}
		$('.event-item-contaner').load(link + ' .event-item-contaner>.container', function(data) {
			$('<div>').append(data).find("script").each(function(i) {
	            try { eval($(this).text()); } catch(e) {  }
	        }).remove();
	        History.pushState({link: $this.attr('href')}, $(data).find('.event-item-title').text(), $this.attr('href'));
	        $("body,.mfp-wrap").scrollTop(0);
            $('.event-item-contaner').append('<button class="mfp-close" type="button" title="Close (Esc)">×</button>');
            return data;
		});

		return false;
	});

    if (browserDetect.isMobile || browserDetect.isTablet) {
        var $body = jQuery('body');

        /* bind events */
        $(document)
        .on('focus', 'input', function(e) {
            $body.addClass('fixfixed');
        })
        .on('blur', 'input', function(e) {
            $body.removeClass('fixfixed');
        });
    }

	/*	ie dropdown fix */
	/*if (navigator.userAgent.match(/msie/i) ){
		$('li, .dropdown-show').hover(function() {
			$(this).find(".dropdown").finish().fadeIn(125);
		}, function() {
			$(this).find(".dropdown").finish().fadeOut(125);	
		});
	}*/

});

function initCLEditor()
{
	if($('textarea.event-wysiwyg-editor').length && $('textarea.event-wysiwyg-editor').cleditor)
	{
		$('textarea.event-wysiwyg-editor').cleditor({
			controls: "bold italic underline strikethrough | subscript superscript style | bullets numbering | link unlink | removeformat",
			 styles: [
				[pin18n.get('Paragraph'), "<p>"],
				[pin18n.get('Header 1'), "<h4>"],
				[pin18n.get('Header 2'), "<h5>"],
				[pin18n.get('Header 3'), "<h6>"]
			 ],
			 docType: '<!DOCTYPE html>'
		});
	}
}