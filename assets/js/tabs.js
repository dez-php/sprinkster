(function () {
    var g = function () {
        (function (h) {
            h.fn.idTabs = function () {
                var l = {};
                for (var k = 0; k < arguments.length; ++k) {
                    var j = arguments[k];
                    switch (j.constructor) {
                    case Object:
                        h.extend(l, j);
                        break;
                    case Boolean:
                        l.change = j;
                        break;
                    case Number:
                        l.start = j;
                        break;
                    case Function:
                        l.click = j;
                        break;
                    case String:
                        if (j.charAt(0) == ".") {
                            l.selected = j
                        } else {
                            if (j.charAt(0) == "!") {
                                l.event = j
                            } else {
                                l.start = j
                            }
                        }
                        break
                    }
                }
                if (typeof l["return"] == "function") {
                    l.change = l["return"]
                }
                return this.each(function () {
                    h.idTabs(this, l)
                })
            };
            h.idTabs = function (l, k) { 
                var o = (h.metadata) ? h(l).metadata() : {};
                var m = h.extend({}, h.idTabs.settings, o, k);
                if (m.selected.charAt(0) == ".") {
                    m.selected = m.selected.substr(1)
                }
                if (m.event.charAt(0) == "!") {
                    m.event = m.event.substr(1)
                }
                if (m.start == null) {
                    m.start = -1
                }
                var j = function () {
                    if (h(this).is("." + m.selected)) {
                        return m.change
                    }
                    var s = "#" + this.href.split("#")[1];
                    var q = [];
                    var r = [];
                    h("a", l).each(function () {
                        if (this.href.match(/#/)) {
                            q.push(this);
                            r.push("#" + this.href.split("#")[1])
                        }
                    });
                    if (m.click && !m.click.apply(this, [s, r, l, m])) {
                        return m.change
                    }
                    for (i in q) {
                        h(q[i]).removeClass(m.selected)
                    }
                    for (i in r) {
                        h(r[i]).hide()
                    }
                    h(this).addClass(m.selected);
                    h(s).show();
                    return m.change
                };
                var n = h("a[href*='#']", l).unbind(m.event, j).bind(m.event, j); 

                n.each(function () {
                    h("#" + this.href.split("#")[1]).hide();
                });
                var p = false;
                if ((p = n.filter("." + m.selected)).length) {} else {
                    if (typeof m.start == "number" && (p = n.eq(m.start)).length) {} else {
                        if (typeof m.start == "string" && (p = n.filter("[href*='#" + m.start + "']")).length) {}
                    }
                }
                if (p) {
                    p.removeClass(m.selected);
                    p.trigger(m.event)
                }
                return m
            };
            h.idTabs.settings = {
                start: 0,
                change: false,
                click: null,
                selected: ".selected",
                event: "!click"
            };
            h.idTabs.version = "2.2";
            h(function () {
                h(".idTabs").idTabs()
            })
        })(jQuery)
    };
    var a = function (j, h) {
        h = h.split(".");
        while (j && h.length) {
            j = j[h.shift()]
        }
        return j
    };
    var c = document.getElementsByTagName("head")[0];
    var e = function (h) {
        var j = document.createElement("script");
        j.type = "text/javascript";
        j.src = h;
        c.appendChild(j)
    };
    var d = document.getElementsByTagName("script");
    var f = d[d.length - 1].src;
    var b = true;
    if (b) {
        return g()
    }
    e(f)
})();