(function($) {
	$.fn.iframeAddScript = function(src) {
		return this.each(function() {
			var iframe = $(this).contents();
			var script = iframe[0].createElement("script");
			script.type = "text/javascript";
			script.src = src;
			iframe[0].body.appendChild(script);
		});
	};
	$.fn.iframeOnChange = function(callback) {
		return this.each(function() {
			var iframe = $(this).contents(),
				self = this,
				html = iframe.find('body').html(),
				d = function() {
					if(html != iframe.find('body').html()) {
						html = iframe.find('body').html();
						callback.call(self, iframe);
					}
					setTimeout(function() { d(); },10);
				};
			d();
		});
	};
	$.fn.iframeBody = function(html) {
		return this.each(function() {
			$(this).contents().find('body').html(html);			
		});
	}
})(jQuery);