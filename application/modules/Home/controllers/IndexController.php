<?php

namespace Home;

use Helper\SSOHelper;

class IndexController extends \Base\PermissionController {
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());

		$login_discourse = \Core\Session\Base::get('login_discourse');
		if(!empty($login_discourse)){
			echo '<script>
				/*var ok = confirm("Login discourse?");*/
				/*if(ok){
					
				}*/
				var win = window.location.href = "'.$login_discourse.'";
			</script>';
			\Core\Session\Base::clear('login_discourse');
		}

		if(isset($_GET['sso']) && isset($_GET['sig'])) {
			$sso = new SSOHelper();
			$secret = 'ldktech!@#$%';
			$sso->setSecret( $secret );
			// load the payload passed in by Discourse
			$payload = $_GET['sso'];
			$signature = $_GET['sig'];

			// validate the payload
			if (!($sso->validatePayload($payload,$signature))) {
			    // invaild, deny
			    header("HTTP/1.1 403 Forbidden");
			    echo("Bad SSO request");
			    die();
			}

			$nonce = $sso->getNonce($payload);

			$user = \User\User::getUserData();

			$user_id = $user->id;

			$user_email = $user->email;

			$avatar = (array) \User\Helper\Avatar::getImage('medium', $user);
			//echo $avatar['image'];exit;
			$extraParameters = array(
			    'username' => $user->username,
			    'name' => $user->firstname . ' ' . $user->lastname,
			    'about_me' => '',
			    'avatar_url' => $avatar['image'],
			);
			
			$query = $sso->getSignInString($nonce, $user_id, $user_email, $extraParameters);
			header('Location: http://boards.sprinkster.com/session/sso_login?' . $query);
			exit;
		}
	}
	
	public function indexAction() {

		if($this->isModuleAccessible('Homepage') && \Base\Config::get('config_home_page_display_type') != 'base') {
			$this->forward('index', [], 'index', 'homepage');
		}
		
		//update currency
		if(\Base\Config::get('config_autoupdate_currency')) {
			\Currency\Helper\Format::updateCurrencies();
		} 
		

// 		(new \Pin\Pin())->extendDelete(292);
		
// 		echo $this->guid('0Business ProfileUserMenu');exit;
// 		$mt = new \Base\Menu();
// 		$m = $mt->fetchAll();
// 		foreach($m AS $r) {
// 			$guid = $this->guid( (int)$r->parent_id . $r->title . $r->group_id );
// 			$r->guid = $guid;
// 			var_dump($r->save());
// 		}
// 		exit;
		
// 		$self = $this;
// 		echo $this->widget('widget.grid.db2', array(
// 				'head' => array(
// 						'title' => ('Pins')
// 				),
// 				'atributes' => 'class="display"',
// 				'dataProvider' => new \Pin\Pin(),
// 				'columns' => array(
// 						'id',
// 						array(
// 								'atributes' => 'class="left" style="width:140px;"',
// 								'name' => 'user_id',
// 								'value' => 'User.username',
// 								'label' => ('User')
// 		),
// 		array(
// 				'atributes' => 'class="left" style="width:140px;"',
// 				'name' => 'source_id',
// 				'value' => 'Source.name',
// 				'label' => ('Source')
// 		),
// 		'likes',
// 		'repins',
// 		array(
// 				'atributes' => 'class="left"',
// 				'name' => 'description',
// 				'label' => ('Description'),
// 				'value' => function($data) { return (string)new \Core\Utf8\SplitText($data->description, 30, '...'); }
// 		)
// 				),
// 				'filter' => array(
// 						'status' => 1
// 				),
// 		));
		
// 		exit;
		
// 		function makeFilter($name, $type, $length) {
// 			if($type == 'tinyint' && $length == 1) {
// 				$select = '<select name="'.$name.'"/><option value=""></option>';
// 				$select .= '<option value="0">No</option>';
// 				$select .= '<option value="1">Yes</option>';
// 				$select .= '</select>';
// 				return $select;
// 			} elseif(strpos($type,'int')!==false) {
// 				return '<input name="'.$name.'" type="number" min="0" '.($length?'max="'.$length.'"':'').' step="1" />';
// 			} elseif(strpos($type,'char')!==false || strpos($type,'text')!==false) {
// 				return '<input name="'.$name.'" type="text" '.($length?'maxlength="'.$length.'"':'').'  />';
// 			} elseif(strpos($type,'datetime')!==false) {
// 				return '<input name="'.$name.'" type="datetime-local" />';
// 			} elseif(preg_match('~(enum|set)\((.*)\)$~i',$type,$m)) {
// 				$m = array_map(function($a) { return trim($a,'\'');},explode(',',$m[2]));
// 				$select = '<select name="'.$name.'"/><option></option>';
// 				foreach($m AS $e) {
// 					$select .= '<option value="'.$e.'">'.$e.'</option>';
// 				}
// 				$select .= '</select>';
// 				return $select;
// 			}
// 			return $type;
// 		}
		
// 		$user = new \User\User();
		
// 		echo '<table border="1"><tr>';
// 		foreach($user->info('metadata') AS $key => $data) {
// 			echo '<td>'.$data['COLUMN_NAME'].'</td>';
// 		}
// 		echo '</tr>';
// 		echo '<tr>';
// 		foreach($user->info('metadata') AS $key => $data) {
// 			echo '<td>'.makeFilter($data['COLUMN_NAME'], $data['DATA_TYPE'],$data['LENGTH']).'</td>';
// 		}
// 		echo '</tr>';
// 		echo '<table>';
		
// 		exit;
		
// 		$i = new \Core\Web\Grid\View();
// 		$i->dataProvider = new \Pin\Pin();
// 		$i->init();
// 		$i->renderContent();
// 		exit;
		
		
// 		$path = $this->getComponent('Alias')->get('Cart.Web');
// 		$this->getComponent('AssetManager')->publish($path);
// 		var_dump($path,$this->getComponent('AssetManager')->getPublishedUrl($path));
// 		exit;

// 		===================== search =====================
		
// 		$pinTable = new \Pin\Pin();
// 		$pins = $pinTable->fetchAll($pinTable->makeWhere(array('description'=>'!=""')));
		
// 		#indexing
// 		if(!is_dir(BASE_PATH . '/cache/lucene/') || !is_writable(BASE_PATH . '/cache/lucene/')) {
// 			mkdir(BASE_PATH . '/cache/lucene/', 0755);
// 		}
		
// 		\Core\Search\Lucene\Search\QueryParser::setDefaultEncoding('UTF-8');
// 		\Core\Search\Lucene\Analysis\Analyzer::setDefault(new \Core\Search\Lucene\Analysis\Analyzer\Common\Utf8Num\CaseInsensitive());
// 		try {
// 			$index = \Core\Search\Lucene::open(BASE_PATH . '/cache/lucene/');
// 		} catch (\Core\Exception $e) {
// 			$index = \Core\Search\Lucene::create(BASE_PATH . '/cache/lucene/');
// 		}
		
// 		foreach($pins AS $pin) {
// 			// Add new document
// 			$doc = new \Core\Search\Lucene\Document();
// 			$doc->addField(\Core\Search\Lucene\Field::keyword('id', $pin->id));
// 			$doc->addField(\Core\Search\Lucene\Field::text('description', $pin->description));
// 			$index->addDocument($doc);
			
// 		}
// 		$index->commit();
//		#optimaze
// 		$item_index = \Core\Search\Lucene::open(BASE_PATH . '/cache/lucene/');
// 		$item_index->optimize();

// 		#search
// 		\Core\Search\Lucene\Search\QueryParser::setDefaultEncoding('UTF-8');
// 		\Core\Search\Lucene\Analysis\Analyzer::setDefault(new \Core\Search\Lucene\Analysis\Analyzer\Common\Utf8Num\CaseInsensitive());
		
// 		$index = \Core\Search\Lucene::open(BASE_PATH . '/cache/lucene/');
		
// 		\Core\Search\Lucene\Search\QueryParser::setDefaultOperator(\Core\Search\Lucene\Search\QueryParser::B_OR);
// 		//$query = \Core\Search\Lucene\Search\QueryParser::parse("*Photograph%Beware%New%Pads*", 'UTF-8');
// 		$query = \Core\Search\Lucene\Search\QueryParser::parse("*Ph?t?g?a?h OR a?a?i?g*", 'UTF-8');
// 		$hits = $index->find($query);
		
// 		foreach($hits AS $hit) {
// 			var_dump($hit->description);
// 		}
// 		exit;
// 		var_dump($hits);exit;
	
// 		new \Cart\Install\Module();exit;
		
		//render script
		$this->render('index');
		
	}
	
	public function error404Action() {
		$this->noLayout(true);
		header('Content-type: image/png');
		echo base64_decode('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');
		exit;
// 		var_dump($this->getRequest()->getParams());
// 		var_dump(\User\Helper\Avatar::noImage($this->getRequest()->getParam('size')));
	}

	public function guidAction() {
		// ID Like: '0CouponsAdminMenu'
		$this->noLayout(true);
		die("<pre><b>{$this->guid($this->getRequest()->getRequest('id'))}</b></pre>");
	}
	
}

?>