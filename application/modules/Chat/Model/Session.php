<?php
namespace Chat;

use \DateTime;
use \User\User;

class Session extends \Core\Db\Table\AbstractTable {

	protected $_name = 'chat_session';

	protected $_referenceMap = [
		'User' => [
			'columns'                  => 'user_id',
			'refTableClass'            => 'User\User',
			'refColumns'               => 'id',
		],
	];

	const TokenLength = 40;

	public static function register()
	{
		$context = new self;
		$user = User::getUserData();	
		$ip = self::ip();

		if(!$user || !$user->id)
			return FALSE;

		$expiration = new DateTime();
		$expiration->modify('+' . ((int) \Base\Config::get('chat_token_expiration_days') ?: 1) . ' day');

		$existing = $context->fetchRow([ 'user_id = ?' => (int) $user->id, 'ip = ?' => $ip ]);

		if($existing)
		{
			$existing->valid = 1;
			$existing->modifed_at = $expiration->format('Y-m-d H:i:s');
			$existing->expiration = $expiration->format('Y-m-d H:i:s');

			if(!$existing->save())
				return FALSE;

			return $existing;
		}

		$new = $context->fetchNew();
		$new->token = self::generate();
		$new->user_id = (int) $user->id;
		$new->created_at = date('Y-m-d H:i:s');
		$new->expiration = $expiration->format('Y-m-d H:i:s');
		$new->ip = $ip;

		if(!$new->save())
			return FALSE;

		return $new;
	}

	public static function validate($token, $ip)
	{
		if(!self::isValidToken($token) || !$ip)
			return FALSE;

		$ip = self::ip($ip);
		
		$session = new self;
		$session = $session->fetchRow([ 'token = ?' => $token, 'ip = ?' => $ip, 'valid = 1',  'expiration > NOW()' ]);

		if(!$session)
			return FALSE;

		return $session;
	}

	public static function invalidate($token)
	{
		if(!self::isValidToken($token))
			return FALSE;

		$session = (new self);
		$session = $session->fetchRow([ 'token = ?' => $token, 'valid = 1', 'expiration > NOW()' ]);

		if(!$session)
			return FALSE;

		$session->valid = FALSE;

		return (bool) $session->save();
	}

	protected static function generate($length = NULL)
	{
		$length = 0 < (int) $length ? (int) $length : self::TokenLength;
		$source = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
		$result = '';

		while($length > 0)
		{
			$index = mt_rand(0, strlen($source) - 1);
			$result .= $source[$index];
			$length--;
			usleep(500);
		}

		return $result;
	}

	public static function isValidToken($token)
	{
		return $token && self::TokenLength === strlen($token);
	}

	public static function contacts($token, $ip)
	{
		if(!self::isValidToken($token) || !$ip)
			return FALSE;

		$session = self::validate($token, $ip);

		if(!$session)
			return FALSE;

		$user = new User;
		$user = $user->get($session ? $session->user_id : 0);

		if(!$user || !$user->id)
			return FALSE;

		$sql = '
			SELECT
				u.id
			FROM user u
			INNER JOIN user_follow f ON (f.follow_id = u.id)
			INNER JOIN user_follow i ON (i.user_id = u.id)
			WHERE
				f.user_id = ?
			AND
				i.follow_id = ?
			GROUP BY
				u.id
		';

		$uids = \Core\Db\Init::getDefaultAdapter()->fetchCol($sql, [ (int) $user->id, (int) $user->id ]);

		array_walk($uids, 'intval');

		if(empty($uids))
			$uids[] = 0;

		$contacts = new User;
		$contacts = $contacts->fetchAll('id IN (' . implode(', ', $uids) . ')', 'username');

		return $contacts;
	}

	public static function ip($ip = NULL)
	{
		if(NULL === $ip)
		{
			$ip = \Core\Http\Request::getInstance()->getServer('X-Forwarded-For');

			if(!$ip)
				$ip = \Core\Http\Request::getInstance()->getServer('HTTP_X_FORWARDED_FOR');

			if(!$ip)
				$ip = \Core\Http\Request::getInstance()->getServer('REMOTE_ADDR');
		}

		if(0 == strpos($ip, '::ffff:'))
			$ip = str_replace('::ffff:', '', $ip);

		return $ip;
	}

};