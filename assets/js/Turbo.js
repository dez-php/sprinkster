var Turbo = {
	head : function(o) {
		var count = 0;
		var scriptTag, linkTag, id;
		var scriptFiles = o.js || {};
		var cssFiles = o.css || {};
		var head = document.getElementsByTagName('head')[0];

		for ( var k in cssFiles) {
			id = Turbo._makeId(cssFiles[k]);
			if(!document.getElementById(id)) {
				linkTag = document.createElement('link');
				linkTag.type = 'text/css';
				linkTag.rel = 'stylesheet';
				linkTag.href = cssFiles[k];
				linkTag.id = id;
				head.appendChild(linkTag);
			}
		}
		for ( var k in scriptFiles) {
			id = Turbo._makeId(scriptFiles[k]);
			scriptTag  = document.getElementById(id);
			if(!scriptTag) {
				scriptTag = document.createElement('script');
				scriptTag.type = 'text/javascript';
				scriptTag.src = scriptFiles[k];
				scriptTag.id = id;
				head.appendChild(scriptTag);
				if (typeof o.callback == "function") {
					if (scriptTag.readyState) { //IE
						scriptTag.onreadystatechange = function() {
							if (scriptTag.readyState == "loaded"
									|| scriptTag.readyState == "complete") {
								count++;
								if (count == scriptFiles.length)
									o.callback.call();
							}
						};
					} else { // other browsers
						scriptTag.onload = function() {
							count++;
							if (count == scriptFiles.length)
								o.callback.call();
						}
					}
				}
			} else {
				if (typeof o.callback == "function") {
					count++;
					if(count == scriptFiles.length) {
						o.callback.call();
					}
				}
			}
		}
	},
	_makeId : function(url) {
		var parts = url.split('?');
		if( parts.length == 2 && /^([\d]{10})$/.exec(parts[1]) ) {
			url = parts[0];
		}
		return url.replace(/[^a-z0-9-_]/gi,'-').replace(/([-]{2,})/gi,'-');
	}
}