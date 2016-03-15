var ws = null;
var online_users = 0;
var windows = getCookie("ws-chat-windows");
var expanded = getCookie("ws-chat-active");

var io = !io ? null : io;

var bci = "bg-color-5";
var bcn = "bg-color-4";
var bca = "bg-color-10";

var ci = "color-7";
var cn = "color-8";
var ca = "color-8";

var all_bcc = [ bci, bcn, bca, ci, cn, ca ].join(" ");
var inactive_c = [ bci, ci ].join(" ");
var normal_c = [ bcn, cn ].join(" ");
var active_c = [ bca, ca ].join(" ");

var mobile = "undefined" !== typeof(chat) && chat && chat.mobile;

$(window).on('beforeunload', function() {
	if("undefined" === typeof(ws) || !ws)
		return;

	ws.disconnect();
	ws.close();
});

/*if(mobile)
	$(".container.chat").addClass("hide");*/

if (null !== io/* && !mobile*/) {
	if (!windows)
	{
		createCookie("ws-chat-windows", "", new Date(Date.now() + (30 * 24 * 60 * 60 * 1000)), "/");
		windows = getCookie("ws-chat-windows");
	}

	windows = windows ? windows.split(',') : [];

	if (!expanded)
	{
		createCookie("ws-chat-active", "0", new Date(Date.now() + (30 * 24 * 60 * 60 * 1000)), "/");
		expanded = getCookie("ws-chat-active");
	}

	$(document).ready(function () {

		Notify.request();

		ws = io.connect(chat.url, { reconnect: false });
		online_users = 0;

		ws.emit("auth", chat.token);

		ws.on("auth", function (response) {
		});

		function escapeRegExp(str) {
			return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
		}

		ws.on("contacts", function (contacts) {
			$("#contacts").html("");

			if (contacts && 0 < contacts.length)
				$.each(contacts, function () {
					$("#follow-list").append('<li data-user-id="' + this.id + '" data-user-name="' + this.name + '" data-user-status="' + this.status + '" class="start-talk contact_' + this.id + ' ' + this.status + '"><p class="avatar"><img src="' + this.avatar.image + '" alt="' + this.username + '"></p><p class="text-2 color-7">' + this.name + '</p></li>');
					$(".event-ws-status-badge-" + this.id).removeClass("icon-68 icon-76").addClass("online" === this.status ? "icon-68" : "icon-76");
				});

			var online_users = parseInt($(".chat").find(".start-talk.online").length);

			if (isNaN(online_users))
				online_users = 0;

			$('.online_users').html(online_users);

			$(".chat").find(".ws-title").removeClass(all_bcc);
			$(".chat").find(".ws-title").addClass(0 < online_users ? normal_c : inactive_c);

			$('.start-talk').on('click', function (e) {
				var id = $(this).attr('data-user-id');
				var name = $(this).attr('data-user-name');
				var status = $(this).attr('data-user-status');

				if ($(".history_" + id).length)
				{
					$(".ws-title").removeClass(all_bcc).addClass(normal_c);

					$c = $(".history_" + id);
					$c.hide().appendTo(".conversation-container").fadeIn(100);
					$c.find(".ws-title").removeClass(all_bcc).addClass(normal_c);

					$("#chat_" + id).scrollTop($("#chat_" + id)[0].scrollHeight);

					if (e.originalEvent)
						$c.find("input").trigger("focus");

					return false;
				}

				rememberWindow(id);

				var template = $("#conversation-box").html();

				var content = template.replace(new RegExp(escapeRegExp('${id}'), 'g'), id)
						.replace(new RegExp(escapeRegExp('${name}'), 'g'), name)
						.replace(new RegExp(escapeRegExp('${status}'), 'g'), status)
						.replace(new RegExp(escapeRegExp('${icon-css}'), 'g'), 'online' === status ? '68' : '76')

				$(".ws-title").removeClass(all_bcc).addClass(normal_c);

				var $c = $(content);
				$c.hide().appendTo(".conversation-container").fadeIn(100);
				$c.find(".ws-title").removeClass(all_bcc).addClass(normal_c);

				ws.emit("history", id);

				$(".ws-message").on("keyup", function (e) {
					var code = e.which || e.keyCode || e.charCode;

					if (13 !== code)
						return;

					ws.emit("message", {recipient: $(this).data("id"), text: $(this).val()});
					$(this).val("");
				});

				$(".ws-chat a.close").on("click", function () {
					$(this).closest(".ws-chat").fadeOut(100, function () {
						$(this).remove();

						forgetWindow($(this).data("id"));
					});

					var cookie = getCookie("ws-chat-windows");
				});

				$(".ws-chat").on("focusin", function () {
					$(".ws-title").removeClass(all_bcc).addClass(normal_c);
					$(this).find(".ws-title").removeClass(all_bcc).addClass(normal_c);
				});

				if (e.originalEvent)
					$c.find("input").trigger("focus");

				$('.list-chat').on('DOMMouseScroll mousewheel', preventOuterScroll);

			});

			if (windows && 0 < windows.length)
				for (var i = 0; i < windows.length; i++)
					$(".contact_" + windows[i]).trigger("click");
		});

		ws.on("status", function (status) {
			$(".contact_" + status.user.id).removeClass("online offline");

			switch (status.status)
			{
				case 'online':
					$(".contact_" + status.user.id).addClass("online");
					$(".icon-id-" + status.user.id).removeClass('icon-76').addClass("icon-68");
					$(".event-ws-status-badge-" + status.user.id).removeClass("icon-76").addClass("icon-68");

					break;
				case 'offline':
					$(".contact_" + status.user.id).addClass("offline");
					$(".icon-id-" + status.user.id).removeClass('icon-68').addClass("icon-76");
					$(".event-ws-status-badge-" + status.user.id).removeClass("icon-68").addClass("icon-76");


					break;
			}

			var online_users = parseInt($(".chat").find(".start-talk.online").length);

			if (isNaN(online_users))
				online_users = 0;

			$('.online_users').html(online_users);

			$(".chat").find(".ws-title").removeClass(all_bcc);
			$(".chat").find(".ws-title").addClass(0 < online_users ? normal_c : inactive_c);

		});

		ws.on("copy", function (message) {
			chatAppendMessage(message, message.recipient.id);
		});

		ws.on("message", function (message) {
			notify(message);
			chatAppendMessage(message, message.sender.id);
		});

		ws.on("history", function (data) {
			$("#chat_" + data.id).html("");

			$.each(data.log, function () {
				var avatar = this.sender.avatar ? this.sender.avatar.image : 'no-avatar';
				$('#chat_' + data.id).append('<li><p class="avatar"><img alt="' + this.sender.name + '" src="' + avatar + '" /></p><p class="text-2 color-7">' + this.text + '</p></li>');
			});

			$(".history_" + data.id).val("");


			$.each(data, function () {
				chatAppendMessage(this);
			});

			if ($("#chat_" + data.id)[0])
			{
				$("#chat_" + data.id).scrollTop($("#chat_" + data.id)[0].scrollHeight);
				$("#chat_" + data.id).linkify();
			}
		});

		ws.on("is_online", function(userId, online) {
			// console.log(userId, online);
		});

		ws.on("set_status", function(status) {
			$("#ws-online-status").attr("checked", !!status);
			$(".event-ws-status-badge-" + chat.me).removeClass("icon-68 icon-76").addClass(!!status ? "icon-68" : "icon-76");
		});

		/*	chat */
		//$('.event-chat-window').hide();


		$('.event-chat-show').on('click', function () {
			$('.event-chat-window').show().removeClass("hide");
			$(this).hide().addClass("hide");

			deleteCookie("ws-chat-active");
			createCookie("ws-chat-active", "1", new Date(Date.now() + (30 * 24 * 60 * 60 * 1000)), "/");

			return false;
		});

		$('.event-chat-close').on('click', function () {
			$('.event-chat-window').hide().addClass("hide");
			$('.event-chat-show').show().removeClass("hide");

			deleteCookie("ws-chat-active");
			createCookie("ws-chat-active", "0", new Date(Date.now() + (30 * 24 * 60 * 60 * 1000)), "/");

			return false;
		});

		$("#ws-online-status").on("change", function() {
			ws.emit("set_status", $(this).is(":checked"));
		});

		if (expanded && "1" == expanded && $(".event-chat-show").is(":visible"))
		{
			$(".event-chat-show").hide().addClass("hide");
			$(".event-chat-window").show().removeClass("hide");
		}

		$('.list-chat').on('DOMMouseScroll mousewheel', preventOuterScroll);

		$("body").addClass("event-chat-body");
});

}
function chatAppendMessage(message, windowId)
{
	if (!message || !message.sender || !message.text)
		return;

	var avatar = message.sender.avatar ? message.sender.avatar.image : 'no-avatar';
	var date = new Date(message.date);
	var text = message.text;

	//var text = date ? '[' + date.format("yyyy-mm-dd HH:MM:ss") + '] ' : '';
	//text += message.sender.name + ": " + message.text + "\n";

	$("#chat_" + windowId).append('<li><p class="avatar"><img alt="' + message.sender.name + '" src="' + avatar + '" /></p><p class="text-2 color-7">' + text + '</p></li>');

	if ($("#chat_" + windowId)[0])
		$("#chat_" + windowId).scrollTop($("#chat_" + windowId)[0].scrollHeight);

	$("#chat_" + windowId).find("li:last p").linkify();
}

function notify(message)
{
	if (!message || !message.text)
		return;

	Notify.icon = chat.icon;

	Notify.show({
		title: chat.l10n.new_message + (message.sender ? ' ' + chat.l10n.from + ' ' + message.sender.name : ''),
		message: message.text,
		onclick: function () {
			window.focus();
		}
	});

	if (!message.sender)
		return;

	if (!$(".history_" + message.sender.id).length)
		$(".contact_" + message.sender.id).trigger("click");

	$(".history_" + message.sender.id).find(".ws-title").removeClass(all_bcc).addClass(active_c);
}

function createCookie(name, value, expires, path, domain)
{
	var cookie = name + "=" + value + ";";

	if (expires)
	{
		if (expires instanceof Date)
			expires = !isNaN(expires.getTime()) ? expires : new Date();
		else
			expires = new Date(new Date().getTime() + parseInt(expires) * 1000 * 60 * 60 * 24);

		cookie += "expires=" + expires.toGMTString() + ";";
	}

	if (path)
		cookie += "path=" + path + ";";

	if (domain)
		cookie += "domain=" + domain + ";";

	document.cookie = cookie;
}


function getCookie(name)
{
	var regexp = new RegExp("(?:" + name + "|;\s*" + name + ")=(.*?)(?:;|$)", "g");
	var result = regexp.exec(document.cookie);

	return (result === null) ? null : result[1];
}

function deleteCookie(name, path, domain)
{
	if (getCookie(name))
		createCookie(name, "", -1, path, domain);
}

function preventOuterScroll(ev)
{
	var $this = $(this),
			scrollTop = this.scrollTop,
			scrollHeight = this.scrollHeight,
			height = $this.height(),
			delta = (ev.type == 'DOMMouseScroll' ?
					ev.originalEvent.detail * -40 :
					ev.originalEvent.wheelDelta),
			up = delta > 0;

	var prevent = function () {
		ev.stopPropagation();
		ev.preventDefault();
		ev.returnValue = false;
		return false;
	}

	if (!up && -delta > scrollHeight - height - scrollTop) {
		// Scrolling down, but this will take us past the bottom.
		$this.scrollTop(scrollHeight);

		return prevent();
	} else if (up && delta > scrollTop) {
		// Scrolling up, but this will take us past the top.
		$this.scrollTop(0);
		return prevent();
	}
}

function rememberWindow(id)
{
	var data = getCookie("ws-chat-windows");
	data = data ? data.split(",") : [];

	var idx = $.inArray(id, data);

	if (-1 < idx)
		return;

	data.push(id);

	createCookie("ws-chat-windows", data.join(','), new Date(Date.now() + (30 * 24 * 60 * 60 * 1000)), "/");
}

function forgetWindow(id)
{
	var data = getCookie("ws-chat-windows");
	data = data ? data.split(",") : [];

	var idx = $.inArray("" + id, data);

	if (-1 === idx)
		return;

	delete(data[idx]);

	createCookie("ws-chat-windows", data.join(','), new Date(Date.now() + (30 * 24 * 60 * 60 * 1000)), "/");
}