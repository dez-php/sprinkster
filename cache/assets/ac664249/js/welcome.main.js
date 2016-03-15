$(document).ready(function() {
	/*	magnific popup */
	var link = window.location.href, title = document.title;
	$(document).on('contextmenu', function (e) {
		var $target = $(e.srcElement||e.target||e.toElement);
		if( $target.hasClass('.event-popup .event-popup-image') ) {
			e.preventDefault();
			$(this).click();
		} else if(  $target.closest('.event-popup, .event-popup-image').size() ) {
			e.preventDefault();
			$target.closest('.event-popup, .event-popup-image').click();
		}

	}).on('click', '.event-popup', function(e) { 
		var opener = $(this);

		e.preventDefault();

		if(typeof opener.data('has_history') == 'undefined')
			opener.data('has_history', $(this).hasClass('event-history'));

		$(this).magnificPopup({
			type: 'ajax',
			closeOnBgClick: false,
			callbacks: {
				beforeClose: function() {
					opener.data('has_history') ? History.pushState({link:link}, title, link) : false;
				},
				open: function() {
					opener.data('has_history') ? History.pushState({link:opener.attr('href')}, opener.attr('title'), opener.attr('href')) : false;
				},
				ajaxContentAdded: function() {},
				parseAjax: function(json) {
					console.log(json);
					this.contentContainer.empty();

					if("object" === typeof(json.data) && json.data.location)
					{
						$.magnificPopup.instance.close();
						return window.location.href = json.data.location;
					}
				}
			}
		}).click();
	}).on('click', '.event-popup-image', function() {
		$(this).magnificPopup({
			type: 'image',
			closeOnBgClick: false
		});
	}).on('click', '.event-popup-close', function(e) {
		e.preventDefault();

		if(!$('.mfp-container').size())
			return;
		
		$.magnificPopup.close();
	});
});