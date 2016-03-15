<?php

namespace Core\Session;

class Db extends \Core\Session\Abs {
	protected static $session;
	public function __construct(\Core\Session\Base $session) {
		session_set_save_handler ( array (
				&$this,
				"open" 
		), array (
				&$this,
				"close" 
		), array (
				&$this,
				"read" 
		), array (
				&$this,
				"write" 
		), array (
				&$this,
				"destroy" 
		), array (
				&$this,
				"gc" 
		) );
		self::$session = $session;
		$this->initDb ();
		register_shutdown_function ( 'session_write_close' );
	}
	public function open($save_path, $session_name) {
		$_db = \Core\Db\Init::getDefaultAdapter ();
		self::destroy ( ( string ) self::$session->sid () );
		return $_db->insert ( '_session_db', array (
				'value' => serialize ( self::$session->getAll () ),
				'updated_on' => time (),
				'session_id' => ( string ) self::$session->sid () 
		) );
	}
	public function close() {
		return self::gc ( ini_get ( 'session.gc_maxlifetime' ) );
		unset ( $this );
	}
	public function read($id) {
		$_db = \Core\Db\Init::getDefaultAdapter ();
		$query = $_db->select ()->from ( '_session_db', 'value' )->where ( 'session_id = ?', ( string ) self::$session->sid () )->limit ( 1 );
		$result = $_db->fetchRow ( $query );
		if ($result) {
			return ( array ) unserialize ( $result ['value'] );
		}
		return array ();
	}
	public function write($id, $sess_data) {
		$_db = \Core\Db\Init::getDefaultAdapter ();
		// self::destroy((string)self::$session->sid());
		return $_db->replace ( '_session_db', array (
				'value' => serialize ( self::$session->getAll () ),
				'updated_on' => time (),
				'session_id' => ( string ) self::$session->sid () 
		) );
	}
	public function destroy($id) {
		$_db = \Core\Db\Init::getDefaultAdapter ();
		return $_db->delete ( '_session_db', array (
				'session_id = ?' => ( string ) self::$session->sid () 
		) );
	}
	public function gc($maxlifetime) {
		$_db = \Core\Db\Init::getDefaultAdapter ();
		return $_db->delete ( '_session_db', array (
				'updated_on <= ?' => (time () - $maxlifetime) 
		) );
	}
	private function initDb() {
		$_db = \Core\Db\Init::getDefaultAdapter ();
		$_db->query ( "
			CREATE TABLE IF NOT EXISTS `_session_db` (
			    `session_id` varchar(40) NOT NULL,
			    `value` text,
			    `updated_on` int(11) DEFAULT NULL,
			    PRIMARY KEY (`session_id`),
			    KEY (`updated_on`)
			  ) ENGINE=MyISAM;
		" );
	}
}

?>