(function($){
	var magnificPopup = $.fn.magnificPopup || function() {};
	$.fn.magnificPopupLive = function (options) {
		options.selector = options.selector || 'a';
		var $this = $(this),
			items = $(options.selector,$this);
		options.items = items;
        $(this).on('click', options.selector, function() {
        	options.items = items;
        	var mfp = magnificPopup.apply(this, options);
        	mfp.click();

        	//var mp = $(this).magnificPopup(options);
        	//mp.magnificPopup('open');
        	//console.log($(options.selector,$this).index(this))
        	//$.fn.magnificPopup.apply(this, arguments);
        	return false;
        });
        $this.magnificPopupContentChange(function() {
        	items = $(options.selector,$this);
        });
    };
    
    $.fn.magnificPopupContentChange = function(callback) {
		return this.each(function() {
			var $this = $(this),
				self = this,
				html = $this.html(),
				d = function() {
					if(html != $this.html()) {
						html = $this.html();
						callback.call(self, $this);
					}
					setTimeout(function() { d(); },10);
				};
			d();
		});
	};
    
})(jQuery);