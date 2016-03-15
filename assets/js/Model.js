function App() {
	this.optionsAjax = {
		url: '',
		data: {},
		type: 'GET',
		onComplete: function(){},
		onSuccess: function(){},
		async:false,
		dataType: 'jsonp'
	};
	//init svg
	var svg = new Image();
	svg.src = 'assets/images/loaders/spinning-loader.svg';
};
App.prototype = {
	History : window.History,
	State: this.History.getState(),
	HistoryIsLoad: false,
	Location: window.location.href,
	Title: document.title,
	//ajax jsonp
	_ajax: function(options){
		options = $.extend({},this.optionsAjax,options);
		options.url += options.url.indexOf('?') > -1 ? '&RSP=ajax' : '?RSP=ajax';
		return jQuery.ajax({
			url:options.url,
			type:options.type,
			dataType: options.dataType,
			cache:  options.cache ? options.cache : false,
			jsonpCallback: 'jsoncallback',
			data: options.data,
			complete: options.onComplete,
			success: options.onSuccess
		});
	},
	//like button
	likeLink: function(selector) {
		if(jQuery) {
			var self = this;
			$(selector || '.event-like-click').live('click', function(){
				var link = $(this);
				self.addLoaderFull(link);
				self._ajax({
					url: link.attr('href'),
					onSuccess: function(result){
						self.removeLoader(link);
						if(result.location) {
							window.location = result.location;
						} else if(result.error) {
							alert(result.error);
						} else if(result.trues) {
							link.addClass('active');
							if(link.data('textactive')) { link.text(link.data('textactive')); }
						} else if(result.falses) {
							link.removeClass('active');
							if(link.data('textinactive')) { link.text(link.data('textinactive')); }
						}
						if(result.info) {
							for(i in result.info.stats) {
								$('.event-stats-'+result.info.id+' .event-' + i).html(result.info.stats[i]);
							}
						}
						if(result.infouser) {
							for(i in result.infouser.stats) {
								$('.event-stats-user-'+result.infouser.id+' .event-' + i).html(result.infouser.stats[i]);
							}
						}
					}
				});
				return false;
			});
		}
	},
	//follow user button
	followUserLink: function(selector) {
		if(jQuery) {
			var self = this;
			selector = selector || '.event-follow-user';
			$(selector).live('click', function(){
				var link = $(this);
				self.addLoader(link);
				self._ajax({
					url: link.attr('href'),
					onSuccess: function(result){
						self.removeLoader(link);
						if(result.location) {
							window.location = result.location;
						} else if(result.error) {
							alert(result.error);
						} else if(result.isFollow === true) {
							link.removeClass('button-color-3').addClass('button-color-2');
							if(link.data('textactive')) { link.text(link.data('textactive')); }
							if(link.data('userid')) {
								$('a[data-userid="'+link.data('userid')+'"]').each(function(){
									$(this).removeClass('button-color-3').addClass('button-color-2');
									if($(this).data('textactive')) { $(this).text(link.data('textactive')); }
								});
							}
						} else if(result.isFollow === false) {
							link.removeClass('button-color-2').addClass('button-color-3');
							if(link.data('textinactive')) { link.text(link.data('textinactive')); }
							if(link.data('userid')) {
								$('a[data-userid="'+link.data('userid')+'"]').each(function(){
									$(this).removeClass('button-color-2').addClass('button-color-3');
									if($(this).data('textinactive')) { $(this).text(link.data('textinactive')); }
								});
							}
						}
						if(result.info) {
							for(i in result.info.stats) {
								$('.event-stats-user-'+result.info.id+' .event-' + i).html(result.info.stats[i]);
							}
						}
					}
				});
				return false;
			});
		}
	},
	//follow wishlist button
	followWishlistLink: function(selector) {
		if(jQuery) {
			var self = this;
			selector = selector || '.event-follow-wishlist1';
			$(selector).live('click', function(){
				var link = $(this);
				self.addLoader(link);
				self._ajax({
					url: link.attr('href'),
					onSuccess: function(result){
						self.removeLoader(link);
						if(result.location) {
							window.location = result.location;
						} else if(result.error) {
							alert(result.error);
						} else if(result.isFollow === true) {
							link.removeClass('button-color-2').addClass('button-color-3');
							if(link.data('textactive')) { link.text(link.data('textactive')); }
							if(link.data('userid')) {
								$('.event-follow-user[data-userid="'+link.data('userid')+'"]').each(function(){
									$(this).removeClass('button-color-3').addClass('button-color-2');
									if($(this).data('textactive')) { $(this).text(link.data('textactive')); }
								});
							}
							if(link.data('wishlistid')) {
								$('.event-follow-wishlist[data-wishlistid="'+link.data('wishlistid')+'"]').each(function(){
									$(this).removeClass('button-color-3').addClass('button-color-2');
									if($(this).data('textactive')) { $(this).text(link.data('textactive')); }
								});
							}
						} else if(result.isFollow === false) {
							link.removeClass('button-color-2').addClass('button-color-3');
							if(link.data('textinactive')) { link.text(link.data('textinactive')); }
							if(link.data('userid')) {
								if(result.isFollowUser === true) {
									$('.event-follow-user[data-userid="'+link.data('userid')+'"]').each(function(){
										$(this).removeClass('button-color-3').addClass('button-color-2');
										if($(this).data('textactive')) { $(this).text(link.data('textactive')); }
									});
								} else {
									$('.event-follow-user[data-userid="'+link.data('userid')+'"]').each(function(){
										$(this).removeClass('button-color-2').addClass('button-color-3');
										if($(this).data('textinactive')) { $(this).text(link.data('textinactive')); }
									});
								}
							}
							if(link.data('wishlistid')) {
								$('.event-follow-wishlist[data-wishlistid="'+link.data('wishlistid')+'"]').each(function(){
									$(this).removeClass('button-color-2').addClass('button-color-3');
									if($(this).data('textinactive')) { $(this).text(link.data('textinactive')); }
								});
							}
						}
						if(result.infouser) {
							for(i in result.infouser.stats) {
								$('.event-stats-user-'+result.infouser.id+' .event-' + i).html(result.infouser.stats[i]);
							}
						}
						if(result.infowishlist) {
							for(i in result.infowishlist.stats) {
								$('.event-stats-wishlist-'+result.infowishlist.id+' .event-' + i).html(result.infowishlist.stats[i]);
							}
						}
					}
				});
				return false;
			});
		}
	},
	//placeholder for inputs
	placeholder: function(selector){
		if(jQuery && jQuery.fn.joPlaceholder) {
			jQuery(selector || 'input,textarea').joPlaceholder();
		}
	},
	//fancybox from html
	fancybox: function(html){
			var self = this;
			$.fancybox({
					'content': html,
					'type': 'inline',
					'live': true,
					beforeLoad: function() {
							$("body").css({"overflow-y": "hidden"});
					},
					fitToView: false,
					autoCenter: true,
					afterClose: function() {
							$("body").css({"overflow-y": "visible"});
							$('.mfp-wrap').css({visibility: 'visible'});
							$('.auto-complete-list').remove();
					},
					beforeShow: function() {
							self.Title = self.Title || document.title;
					},
					afterShow: function() {
							self.selectBox();
							$('.mfp-wrap').css({visibility: 'hidden'});
					},
					padding: 0,
					margin: 0,
//                    scrolling: 'no',
					arrows: false,
					mouseWheel: false,
					helpers: {
							title: null,
							overlay: {closeClick: true}
					},
					openEffect: 'none',
					closeEffect: 'none',
					nextEffect: 'none',
					prevEffect: 'none'
			});
		},
	//fancybox ajax
	fancyboxAjax: function(selector){
		if(jQuery && jQuery.fn.fancybox) {
			var self = this;
			/*var overlay;*/
			$(selector || '.event-fancybox-ajax').fancybox({
				'type'		: 'ajax',
				'live'	  	: true,
				beforeLoad: function() {
					$("body").css({"overflow-y":"hidden"});
					this.original_href = this.href;
					this.href += (this.href.indexOf('?') > -1 ? '&RSP=ajax' : '?RSP=ajax');
				},
				fitToView: false,
				autoCenter: true,
				afterClose: function() {
					$("body").css({"overflow-y":"visible"});
					/*if(self.HistoryIsLoad) {
						self.History.pushState({link:self.Location}, self.Title, self.Location);
						self.HistoryIsLoad = false;
					}*/
					$('.mfp-wrap').css({visibility: 'visible'});
					$('.auto-complete-list').remove();
				},
				beforeShow: function() {
					self.Title = self.Title || document.title;
					/*if( $(this.element[0]).hasClass('event-width-100') ) {
						$('.fancybox-wrap').addClass('fancybox-width-100');
					}*/
					
					/*$('html').addClass('fancybox-margin fancybox-lock');
					$('body > .fancybox-wrap').wrapAll('<div class="fancybox-overlay fancybox-overlay-fixed" style="display: block; width: auto; height: auto;" />');
					$('#event-content').parents('.fancybox-wrap').addClass('top0');
					var numOverlays = $(".fancybox-overlay").size();
					if (numOverlays > 1) {
						$('body > .fancybox-overlay:last-child').remove();
					}*/
				},
				afterShow: function() {
					self.selectBox();
					elm = $(this.element[0]);
					if($('.fancybox-nav').size() < 1) {
						$('.event-next-pin, .event-prev-pin').remove();
					}
					/*if( elm.hasClass('event-width-100') ) {
						$('.event-prev-pin').click(function(){
							$('.fancybox-nav.fancybox-prev').click();
							return false;
						});
						$('.event-next-pin').click(function(){
							$('.fancybox-nav.fancybox-next').click();
							return false;
						});
					}
					if( elm.hasClass('event-history') ) {
						self.History.pushState({link:this.original_href}, this.title||document.title, this.original_href);
						self.HistoryIsLoad = true;
					}*/
					$('.mfp-wrap').css({visibility: 'hidden'});
				},
				padding: 0,
				margin: 0,
				scrolling: 'no',
				helpers: { 
					title: null,
					overlay : {closeClick: true}
				},
				openEffect:'none',
				closeEffect:'none',
				nextEffect:'none',
				prevEffect:'none'
			});
		}
	},
	//fancybox close
	fancyboxClose: function(selector) {
		if(jQuery && jQuery.fn.fancybox) {
			var self = this;
			$(selector || '.event-fancybox-close').live('click', function(){
				jQuery.fancybox.close();
				self.HistoryIsLoad = false;
				return false;
			});
		}
	},
	//select box
	selectBox: function(selector){
		if(jQuery && jQuery.fn.selectBox) {
			$(selector || 'select:not(.event-wishlist-init,.event-no-selectbox)').selectBox({
				onOpen: function (inst) {
					$(this).next().addClass("sbHolder-open");
				},
				onClose: function (inst) {
					$(this).next().removeClass("sbHolder-open");
				},
				effect: "fade"
			});
		}
	},
	//select box
	selectBoxWithWishlist: function(params){
		if(jQuery && jQuery.fn.selectbox) {
			options = $.extend( {}, {
				url:'',
				create:'Create',
				close:'Close',
				placeholder:'Create New Wishlist',
				chose_category:'Choose a Category',
				classname: '.event-wishlist-init',
				categories: {},
			}, params );
			$(options.classname).selectbox({
				onOpen: function (inst) {
					$(this).next().addClass("sbHolder-open");
				},
				onClose: function (inst) {
					$(this).next().removeClass("sbHolder-open");
				},
				effect: "fade"
			}).callbackAfter(function() {
				var instance = $(this).selectbox('instance');
				if(instance) {
					var html  = '<li class="createwishlist clearfix"><form action="'+options.url+'" method="post" id="event-new-wishlist-'+instance.uid+'">';
						html += '<select name="category_id" class="event-no-selectbox selectbox-in-selectbox">';
						html += '<option value="">'+options.chose_category+'</option>';
						for(i in options.categories) {
							html += '<option value="'+options.categories[i].id+'">'+options.categories[i].title+'</option>';
						}
						html += '</select>';
						html += '<input type="text" id="f-1" name="title" value="" placeholder="'+options.placeholder+'">';
						html += '<button class="event-loader-dynamic button button-color-1">'+options.create+'</button>';
						html += '</form></li>';
					$('#sbOptions_' + instance.uid).prepend(html).callbackAfter(function() {
						$select = this;
						$('form input[name=title]', $select).keydown(function(e) {
							var key = e.charCode ? e.charCode : e.keyCode ? e.keyCode : 0;
							if(key == 13) {
								$('form button', $select).click();
								return false;
							}
						});
						$('form', $select).submit(function() {
							return false;
						});
						$('form button', $select).click(function() {
							$('#sbOptions_' + instance.uid).parents('form').find('.notification.error').remove();
							var button = this;
							App.addLoader(this);
							App._ajax({
								url:options.url,
								type:'POST',
								data:$('#event-new-wishlist-' + instance.uid).serialize(),
								onSuccess: function(json) {
									App.removeLoader(button);
									if(json.location) {
										window.location = json.location;
									} else if(json.errors) {
										var error = '';
										for( i in json.errors ) {
											error += json.errors[i] + '<br />';
										}
										$('#sbOptions_' + instance.uid).parents('form').append('<div class="notification error">' + error + '<a class="button-close" href="javascript:void(0);">'+options.close+'</a></div>');
									} else if(json.ok) {
										$(instance.input).append('<option selected="selected" value="'+json.ok.id+'">'+json.ok.text+'</option>')
											.selectbox('change',json.ok.id,json.ok.text).selectbox('close');
									} else {
										console.log(json);
									}
								}
							});
							return false;
						});
					});
				}
			});
		}
	},
	//form submit
	post: function(selector, onSuccess) {
		if(jQuery) {
			var self = this;
			$(selector || 'form').bind('submit', function(){
				options = {
					url 		: $(this).attr('action'),
					type 		: 'POST',
					onSuccess	: $.isFunction(onSuccess) ? onSuccess : function(){},
					data		: $(this).serialize()
				};
				self._ajax(options);
				return false;
			});
		}
	},
	turboPin: function(){
		$('.event-turbo-click').live('click',function(){
			var self = this;
			var href = self.href;
			if(!$(this).hasClass('event-no-loader')) { 
				App.addLoaderNoBg(self);
				$('img:not(.svg-loader)',self).css({visibility: 'hidden'});
			}
			App._ajax({
				url: href,
				onSuccess: function(json) {
					App.removeLoader(self);
					$('#event-content').replaceWith(json.content);
					$('.mfp-wrap').scroll();
					$(window).scroll();
					History.pushState({link:self.href}, json.title, self.href);
					
				}
			});
	
			return false;
		});
		$(document).unbind('.popupNav').bind('keydown.popupNav','keydown',function(e){
			if(!$(e.target || e.srcElement).is('textarea')) {
				if(e.keyCode == 39) {
					$('.event-turbo-click.event-next').click();
				} else if(e.keyCode == 37) {
					$('.event-turbo-click.event-prev').click();
				}
			}
		});
	},
	scrolltotop: function(selector) {
		$(selector || '.event-scrolltotop').bind('click.smoothscroll',function(e){
			e.preventDefault();
			$('html, body').stop().animate({
				'scrollTop': 0
			}, 700, 'swing');
			return false;
		});
		if(!$(selector || '.event-scrolltotop').is(':visible')) {
			$(window).scroll(function() {
				if($(this).scrollTop() > $(window).height()) {
					$(selector || '.event-scrolltotop').show();
				} else {
					$(selector || '.event-scrolltotop').hide();
				}
			});
		}
	},
	addLoader: function(selector) {
		selector = $(selector);
		var absolute = selector.css('position') == 'absolute';
		var height = Math.min((selector.outerWidth() - 10),(selector.outerHeight() - 10));
		if(selector.data('loadersize'))
			 height = Math.min(parseInt(selector.data('loadersize')), height);
		var loader = $('<img height="'+height+'" src="assets/images/loaders/spinning-loader.svg" class="svg-loader" />').css({
		 'position'  : 'absolute',
		 'top'   : '50%',
		 'left': '50%',
		 'margin-top': '-' + (height / 2) + 'px',
		 'margin-left': '-' + (height / 2) + 'px'
		});
		var sstyle = selector.attr('style');
		if(selector.is('.button')) {
			loader.css({
				width: height,
				height: height
			});
		} else {
			selector.css({
				height: height
			});
		}
		selector.data('style', sstyle).css({
		 'position': absolute?'absolute':'relative'
		}).addClass('text-transparent').append(loader).attr('disabled', true);
	 },
	 addLoaderNoBg: function(selector) {
		selector = $(selector);
		var absolute = selector.css('position') == 'absolute';
		var height = Math.min((selector.outerWidth() - 10),(selector.outerHeight() - 10));
		var loader = $('<img height="'+height+'" src="assets/images/loaders/spinning-loader.svg" class="svg-loader" />').css({
		 'position'  : 'absolute',
		 'top'   : '50%',
		 'left': '50%',
		 'margin-top': '-' + (height / 2) + 'px',
		 'margin-left': '-' + (height / 2) + 'px'
		});
		var sstyle = selector.attr('style');
		if(selector.is('.button')) {
			loader.css({
				width: height,
				height: height
			});
		} else {
			selector.css({
				height: height
			});
		}
		selector.data('style', sstyle).css({
			'position': absolute?'absolute':'relative',
		 	'background-image': 'none'
		}).addClass('text-transparent no-bg-image').append(loader).attr('disabled', true);
	 },
	 addLoaderFull: function(selector) {
		selector = $(selector);
		var absolute = selector.css('position') == 'absolute';
		var height = Math.min((selector.outerWidth() - 4),(selector.outerHeight() - 4));
		var loader = $('<img height="'+height+'" src="assets/images/loaders/spinning-loader.svg" class="svg-loader" />').css({
		 'position'  : 'absolute',
		 'top'   : '50%',
		 'left': '50%',
		 'margin-top': '-' + (height / 2) + 'px',
		 'margin-left': '-' + (height / 2) + 'px'
		});
		var sstyle = selector.attr('style');
		if(selector.is('.button')) {
			loader.css({
				width: height,
				height: height
			});
		} else {
			selector.css({
				height: height
			});
		}
		selector.data('style', sstyle).css({
			'position': absolute?'absolute':'relative',
		 	'background-image': 'none'
		}).addClass('text-transparent').append(loader).attr('disabled', true);
	 },
	 removeLoader: function(selector) {
		selector = $(selector);
		selector.removeAttr('style').removeClass('text-transparent no-bg-image').attr('style',selector.data('style')).attr('disabled', false);
		selector.find('.svg-loader').remove();
	 },
	historyLink: function() {
		if($.browser.msie && this.State.hash.split('?')[0] && this.State.hash.split('?')[0] != './' && this.State.hash.split('?')[0] && this.State.hash.split('?')[0].replace('#','') == window.location.hash.split('?')[0].replace('#','')) {
			/*var link = $('<a>').attr('href', this.State.url.replace('#','')).addClass('event-fancybox-ajax event-history event-width-100');
			link.appendTo('body').click().remove();*/
			window.location = this.State.url.replace('#','');
		}
	},
	popupPin: function(selector) {
		var self = this;
		$(selector || '.event-popup-ajax').live('click', function() {
			link = this;
			
			href = link.href;
			href += link.href.indexOf('?') > -1 ? '&RSP=ajax&popup=true' : '?RSP=ajax&popup=true';
			
			$('<a href="'+href+'">').magnificPopup({ 
				type: 'ajax',
				ajax: {
					settings: {
						dataType: "jsonp"
					}
				},
				alignTop: true,
				overflowY: 'scroll',
				closeOnBgClick: false,
				showCloseBtn: false,
				callbacks: {
					beforeOpen: function() {
						self.Title = self.Title || document.title;
						self.Location = decodeURIComponent(document.URL);
					},
					beforeClose: function() {
						self.History.pushState({link:self.Location}, self.Title, self.Location);
						self.HistoryIsLoad = false;
						$(document).unbind('.popupNav');
						$('.tipr_container_bottom').hide();
					},
					parseAjax: function(json) {
						
						// Ajax content is loaded and appended to DOM
						self.History.pushState({link:link.href}, json.data.title, link.href);
						self.HistoryIsLoad = true;
						json.data = json.data.content;
						
					},
					open: function() {
						$(document).unbind('.popupNav').bind('keydown.popupNav','keydown',function(e){
							if(!$(e.target || e.srcElement).is('textarea')) {
								if(e.keyCode == 39) {
									$('.event-next').click();
								} else if(e.keyCode == 37) {
									$('.event-prev').click();
								}
							}
						});
					},
					ajaxContentAdded: function() {
						/* addthis */
						if (window.addthis) {
							window.addthis = null;
							window._adr = null;
							window._atc = null;
							window._atd = null;
							window._ate = null;
							window._atr = null;
							window._atw = null;
						} 
						var atUrl = "http://s7.addthis.com/js/250/addthis_widget.js";
						$.getScript(atUrl).done(function(script) {
							addthis.init();
						});
						var headbarOffsetY = parseInt($("#headbar").css("margin-top").replace("px", ""));
						var headbarHeight = parseInt($("#headbar").css("height").replace("px", ""));
						$('.mfp-wrap').scroll(function(event){
							if ($(this).scrollTop() >= headbarOffsetY){
								$("#headbar", this).addClass('fixed');
								$("#event-content", this).css({'padding-top': (headbarOffsetY+headbarHeight)+'px'});
							} else {
								$("#headbar", this).removeClass('fixed');
								$("#event-content", this).removeAttr('style');
							}
						});
						$('#content').live('click', function(e){
							if (e.target == this) {
								$.magnificPopup.close();
							}
						});
					}
				}
			}).click().remove();
			
			return false;
		});
	},
	//popup close
	popupClose: function(selector) {
		if(jQuery && jQuery.fn.magnificPopup) {
			var self = this;
			$(selector || '.event-popup-close').live('click', function(){
				$.magnificPopup.close();
				self.History.pushState({link:self.Location}, self.Title, self.Location);
				self.HistoryIsLoad = false;
				return false;
			});
		}
	},
	returnContainment: function (image) {
		image = $(image);
		if(image.size() != 1) {
			return [0,0,0,0];
		}
		var maskWidth  = image.parent().width();
		var maskHeight = image.parent().height();
		var imgPos     = image.offset();
		var imgWidth   = image.width();
		var imgHeight  = image.height();
		var offsetTopImg = imgPos.top - parseInt(image.css('top'));

		return [(imgPos.left + maskWidth) - imgWidth,(offsetTopImg + maskHeight) - imgHeight,imgPos.left,offsetTopImg];
	}
};
//init object
var App = new App();