<?php
namespace Chat;

use \Core\Http\Url;
use \Base\Config;

class Module extends \Core\Base\Module {

	public function getConfig()
	{
		return include __DIR__ . '/config/module.config.php';
	}

	public function registerEvent( \Core\Base\Event $e, $application ) {
		$e->register('onBootstrap', [$this , 'onBootstrap']);
	}

	public function addScript()
	{
		$me = \User\User::getUserData();
		$session = NULL;
		$action = \Core\Base\Action::getInstance();
		$chat_url = Config::get('chat_server_url');

		if(!$me->id  || !Url::ping($chat_url . '/socket.io/socket.io.js'))
			return;

		if($me && $me->id)
			$session = Session::register();

		// if($this->isMobile())
		// 	return;

		$dir = $this->getComponent('Alias')->get(__NAMESPACE__) . '/asset/';
		$document = $this->getComponent('document');
		$asset = $this->getComponent('AssetManager');
		$asset->publish($dir);

		$document->addScriptFile(Url::build($chat_url . '/socket.io/socket.io.js'));
		$document->addScriptFile($asset->getPublishedUrl($dir) . '/js/desktopnotify.js');
		$document->addScriptFile($asset->getPublishedUrl($dir) . '/js/linkify.js');
		$document->addScriptFile($asset->getPublishedUrl($dir) . '/js/main.js');
		
		$document->addCssFile($asset->getPublishedUrl($dir) . '/css/main.css');

		$icon = Url::to($asset->getPublishedUrl($dir) . '/images/notification.png');

		if(NULL !== ($favicon = \Base\Config::get('favicon')))
			$icon = Url::to('uploads/data/' . $favicon);

		ob_start();
		?>
		var chat = {
			url: "<?php echo Url::build(Config::get('chat_server_url'), Url::WS) ?>",
			me: "<?php echo $me->id ?>",
			token: "<?php echo $session ? $session->token : '' ?>",
			l10n: {
				new_message: "<?php echo $action->_('New message') ?>",
				from: "<?php echo $action->_('from') ?>"
			},
			icon: "<?php echo $icon ?>",
			mobile: <?php echo $this->isMobile() ? 'true' : 'false' ?>
		};
		<?php
		$document->addScript('chat-init', ob_get_clean(), \Core\Document::POS_HEAD);
	}

	function isMobile()
	{
		return false;
 		return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
	}
	
	public function onBootstrap() {
		\Welcome\Module::setAllowed([ 'module' => [ 'chat' ] ]);
	}
}