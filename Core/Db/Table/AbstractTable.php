<?php

namespace Core\Db\Table;

abstract class AbstractTable {
	const ADAPTER = 'db';
	const DEFINITION = 'definition';
	const DEFINITION_CONFIG_NAME = 'definitionConfigName';
	const SCHEMA = 'schema';
	const NAME = 'name';
	const PRIMARY = 'primary';
	const INDEXES = 'indexes';
	const COLS = 'cols';
	const METADATA = 'metadata';
	const METADATA_CACHE = 'metadataCache';
	const METADATA_CACHE_IN_CLASS = 'metadataCacheInClass';
	const ROW_CLASS = 'rowClass';
	const ROWSET_CLASS = 'rowsetClass';
	const REFERENCE_MAP = 'referenceMap';
	const REFERENCE_REV_MAP = 'referenceReverseMap';
	const DEPENDENT_TABLES = 'dependentTables';
	const SEQUENCE = 'sequence';
	const ORDER = 'order';
	const LIMIT = 'limit';
	const COLUMNS = 'columns';
	const REF_TABLE_CLASS = 'refTableClass';
	const REF_REF_MAP = 'refReferenceMap';
	const REF_COLUMNS = 'refColumns';
	const ON_DELETE = 'onDelete';
	const ON_UPDATE = 'onUpdate';
	const SINGLE_ROW = 'singleRow';
	const CASCADE = 'cascade';
	const RESTRICT = 'restrict';
	const SET_NULL = 'setNull';
	const DEFAULT_NONE = 'defaultNone';
	const DEFAULT_CLASS = 'defaultClass';
	const DEFAULT_DB = 'defaultDb';
	const SELECT_WITH_FROM_PART = true;
	const SELECT_WITHOUT_FROM_PART = false;
	
	/**
	 * Default \Core\Db\Adapter\AbstractAdapter object.
	 *
	 * @var \Core\Db\Adapter\AbstractAdapter
	 */
	protected static $_defaultDb;
	
	/**
	 * Optional \Core\Db\Table\Definition object
	 *
	 * @var unknown_type
	 */
	protected $_definition = null;
	
	/**
	 * Optional definition config name used in concrete implementation
	 *
	 * @var string
	 */
	protected $_definitionConfigName = null;
	
	/**
	 * Default cache for information provided by the adapter's describeTable()
	 * method.
	 *
	 * @var \\Core\Cache\Core
	 */
	protected static $_defaultMetadataCache = null;
	
	/**
	 * \Core\Db\Adapter\AbstractAdapter object.
	 *
	 * @var \Core\Db\Adapter\AbstractAdapter
	 */
	protected $_db;
	
	/**
	 * The schema name (default null means current schema)
	 *
	 * @var array
	 */
	protected $_schema = null;
	
	/**
	 * The table name.
	 *
	 * @var string
	 */
	protected $_name = null;
	
	/**
	 * The table column names derived from
	 * \Core\Db\Adapter\AbstractAdapter::describeTable().
	 *
	 * @var array
	 */
	protected $_cols;
	
	/**
	 * The table indexes
	 *
	 * @var array
	 */
	protected $_indexes;
	
	/**
	 * The primary key column or columns.
	 * A compound key should be declared as an array.
	 * You may declare a single-column primary key
	 * as a string.
	 *
	 * @var mixed
	 */
	protected $_primary = null;
	
	/**
	 * If your primary key is a compound key, and one of the columns uses
	 * an auto-increment or sequence-generated value, set _identity
	 * to the ordinal index in the $_primary array for that column.
	 * Note this index is the position of the column in the primary key,
	 * not the position of the column in the table. The primary key
	 * array is 1-based.
	 *
	 * @var integer
	 */
	protected $_identity = 1;
	
	/**
	 * Define the logic for new values in the primary key.
	 * May be a string, boolean true, or boolean false.
	 *
	 * @var mixed
	 */
	protected $_sequence = true;
	
	/**
	 * Information provided by the adapter's describeTable() method.
	 *
	 * @var array
	 */
	protected $_metadata = array ();
	
	/**
	 * Cache for information provided by the adapter's describeTable() method.
	 *
	 * @var \\Core\Cache\Core
	 */
	protected $_metadataCache = null;
	
	/**
	 * Flag: whether or not to cache metadata in the class
	 * 
	 * @var bool
	 */
	protected $_metadataCacheInClass = true;
	
	/**
	 * Classname for row
	 *
	 * @var string
	 */
	protected $_rowClass = '\Core\Db\Table\Row';
	
	/**
	 * Classname for rowset
	 *
	 * @var string
	 */
	protected $_rowsetClass = '\Core\Db\Table\Rowset';
	
	/**
	 * Associative array map of declarative referential integrity rules.
	 * This array has one entry per foreign key in the current table.
	 * Each key is a mnemonic name for one reference rule.
	 *
	 * Each value is also an associative array, with the following keys:
	 * - columns = array of names of column(s) in the child table.
	 * - refTableClass = class name of the parent table.
	 * - refColumns = array of names of column(s) in the parent table,
	 * in the same order as those in the 'columns' entry.
	 * - onDelete = "cascade" means that a delete in the parent table also
	 * causes a delete of referencing rows in the child table.
	 * - onUpdate = "cascade" means that an update of primary key values in
	 * the parent table also causes an update of referencing
	 * rows in the child table.
	 *
	 * @var array
	 */
	protected $_referenceMap = array ();
	protected $_referenceReverseMap = array ();
	
	/**
	 * Simple array of class names of tables that are "children" of the current
	 * table, in other words tables that contain a foreign key to this one.
	 * Array elements are not table names; they are class names of classes that
	 * extend \Core\Db\Table\AbstractTable.
	 *
	 * @var array
	 */
	protected $_dependentTables = array ();
	protected $_defaultSource = self::DEFAULT_NONE;
	protected $_defaultValues = array ();
	
	/**
	 *
	 *
	 * Set global Table rowset limit
	 *
	 * @var int
	 */
	protected $_limit;
	
	/**
	 *
	 *
	 * Set global Table rowset order
	 *
	 * @var string \Core\Db\Expr
	 */
	protected $_order;
	
	/**
	 *
	 * @var string array
	 */
	protected $_use_indexes;
	
	/**
	 *
	 * @var string array
	 */
	protected $_force_indexes;
	
	/**
	 * Constructor.
	 *
	 * Supported params for $config are:
	 * - db = user-supplied instance of database connector,
	 * or key name of registry instance.
	 * - name = table name.
	 * - primary = string or array of primary key(s).
	 * - rowClass = row class name.
	 * - rowsetClass = rowset class name.
	 * - referenceMap = array structure to declare relationship
	 * to parent tables.
	 * - dependentTables = array of child tables.
	 * - metadataCache = cache for information from adapter describeTable().
	 *
	 * @param mixed $config
	 *        	Array of user-specified config options, or just the Db
	 *        	Adapter.
	 * @return void
	 */
	public function __construct($config = array()) {
		/**
		 * Allow a scalar argument to be the Adapter object or Registry key.
		 */
		if (! is_array ( $config )) {
			$config = array (
					self::ADAPTER => $config 
			);
		}
		
		if ($config) {
			$this->setOptions ( $config );
		}
		
		$this->_setup ();
		$this->init ();
		$this->_referenceMap = \Core\Arrays::array_merge($this->_referenceMap, $this->setReferenceMap());
	}
	
	/**
	 * setOptions()
	 *
	 * @param array $options        	
	 * @return \Core\Db\Table\AbstractTable
	 */
	public function setOptions(Array $options) {
		foreach ( $options as $key => $value ) {
			switch ($key) {
				case self::ADAPTER :
					$this->_setAdapter ( $value );
					break;
				case self::DEFINITION :
					$this->setDefinition ( $value );
					break;
				case self::DEFINITION_CONFIG_NAME :
					$this->setDefinitionConfigName ( $value );
					break;
				case self::SCHEMA :
					$this->_schema = ( string ) $value;
					break;
				case self::NAME :
					$this->_name = ( string ) $value;
					break;
				case self::PRIMARY :
					$this->_primary = ( array ) $value;
					break;
				case self::ROW_CLASS :
					$this->setRowClass ( $value );
					break;
				case self::ROWSET_CLASS :
					$this->setRowsetClass ( $value );
					break;
				case self::REFERENCE_MAP :
					$this->setReferences ( $value );
					break;
				case self::DEPENDENT_TABLES :
					$this->setDependentTables ( $value );
					break;
				case self::METADATA_CACHE :
					$this->_setMetadataCache ( $value );
					break;
				case self::METADATA_CACHE_IN_CLASS :
					$this->setMetadataCacheInClass ( $value );
					break;
				case self::SEQUENCE :
					$this->_setSequence ( $value );
					break;
				case self::ORDER :
					$this->_order = $value;
					break;
				case self::LIMIT :
					$this->_limit = $value;
					break;
				default :
					// ignore unrecognized configuration directive
					break;
			}
		}
		
		return $this;
	}
	
	/**
	 * Associative array map of declarative referential integrity rules.
	 * This array has one entry per foreign key in the current table.
	 * Each key is a mnemonic name for one reference rule.
	 *
	 * Each value is also an associative array, with the following keys:
	 * - columns = array of names of column(s) in the child table.
	 * - refTableClass = class name of the parent table.
	 * - refColumns = array of names of column(s) in the parent table,
	 * in the same order as those in the 'columns' entry.
	 * - onDelete = "cascade" means that a delete in the parent table also
	 * causes a delete of referencing rows in the child table.
	 * - onUpdate = "cascade" means that an update of primary key values in
	 * the parent table also causes an update of referencing
	 * rows in the child table.
	 *
	 * @return array:
	 */
	public function setReferenceMap() {
		return array();
	}
	
	/**
	 * setDefinition()
	 *
	 * @param \Core\Db\Table\Definition $definition        	
	 * @return \Core\Db\Table\AbstractTable
	 */
	public function setDefinition(\Core\Db\Table\Definition $definition) {
		$this->_definition = $definition;
		return $this;
	}
	
	/**
	 * getDefinition()
	 *
	 * @return \Core\Db\Table\Definition null
	 */
	public function getDefinition() {
		return $this->_definition;
	}
	
	/**
	 * setDefinitionConfigName()
	 *
	 * @param string $definition        	
	 * @return \Core\Db\Table\AbstractTable
	 */
	public function setDefinitionConfigName($definitionConfigName) {
		$this->_definitionConfigName = $definitionConfigName;
		return $this;
	}
	
	/**
	 * getDefinitionConfigName()
	 *
	 * @return string
	 */
	public function getDefinitionConfigName() {
		return $this->_definitionConfigName;
	}
	
	/**
	 *
	 * @param string $classname        	
	 * @return \Core\Db\Table\AbstractTable Provides a fluent interface
	 */
	public function setRowClass($classname) {
		$this->_rowClass = ( string ) $classname;
		
		return $this;
	}
	
	/**
	 *
	 * @return string
	 */
	public function getRowClass() {
		return $this->_rowClass;
	}
	
	/**
	 *
	 * @param string $classname        	
	 * @return \Core\Db\Table\AbstractTable Provides a fluent interface
	 */
	public function setRowsetClass($classname) {
		$this->_rowsetClass = ( string ) $classname;
		
		return $this;
	}
	
	/**
	 *
	 * @return string
	 */
	public function getRowsetClass() {
		return $this->_rowsetClass;
	}
	
	/**
	 * Add a reference to the reference map
	 *
	 * @param string $ruleKey        	
	 * @param string|array $columns        	
	 * @param string $refTableClass        	
	 * @param string|array $refColumns        	
	 * @param string $onDelete        	
	 * @param string $onUpdate        	
	 * @return \Core\Db\Table\AbstractTable
	 */
	public function addReference($ruleKey, $columns, $refTableClass, $refColumns, $refReferenceMap = null, $onDelete = null, $onUpdate = null) {
		$reference = array (
				self::COLUMNS => ( array ) $columns,
				self::REF_TABLE_CLASS => $refTableClass,
				self::REF_REF_MAP => $refReferenceMap,
				self::REF_COLUMNS => ( array ) $refColumns 
		);
		
		if (! empty ( $onDelete )) {
			$reference [self::ON_DELETE] = $onDelete;
		}
		
		if (! empty ( $onUpdate )) {
			$reference [self::ON_UPDATE] = $onUpdate;
		}
		
		$this->_referenceMap [$ruleKey] = $reference;
		
		return $this;
	}
	
	/**
	 *
	 * @param array $referenceMap        	
	 * @return \Core\Db\Table\AbstractTable Provides a fluent interface
	 */
	public function setReferences(array $referenceMap) {
		$this->_referenceMap = $referenceMap;
		
		return $this;
	}
	
	/**
	 *
	 * @param string $tableClassname        	
	 * @param string $ruleKey
	 *        	OPTIONAL
	 * @return array
	 * @throws \Core\Db\Table\Exception
	 */
	public function getReference($tableClassname, $ruleKey = null) {
		$thisClass = get_class ( $this );
		if ($thisClass === '\Core\Db\Table') {
			$thisClass = $this->_definitionConfigName;
		}
		$refMap = $this->_getReferenceMapNormalized ();
		
		if ($ruleKey !== null) {
			if (! isset ( $refMap [$ruleKey] )) {
				require_once "Db/Table/Exception.php";
				throw new \Core\Db\Table\Exception ( "No reference rule \"$ruleKey\" from table $thisClass to table $tableClassname" );
			}
			if ($refMap [$ruleKey] [self::REF_TABLE_CLASS] != $tableClassname) {
				require_once "Db/Table/Exception.php";
				throw new \Core\Db\Table\Exception ( "Reference rule \"$ruleKey\" does not reference table $tableClassname" );
			}
			return $refMap [$ruleKey];
		}
		
		foreach ( $refMap as $reference ) {
			if ($reference [self::REF_TABLE_CLASS] == $tableClassname) {
				return $reference;
			}
		}
		
		$tableClassnameObject = $this->_getTableFromString ( $tableClassname );
		$referenceReverseMap = $tableClassnameObject->info ( 'referenceReverseMap' );
		if ($referenceReverseMap) {
			foreach ( $referenceReverseMap as $key => $reference ) {
				if ($key == $thisClass) {
					return $reference;
				}
			}
		}
		
		require_once "Db/Table/Exception.php";
		throw new \Core\Db\Table\Exception ( "No reference from table $thisClass to table $tableClassname" );
	}
	
	/**
	 *
	 * @param array $dependentTables        	
	 * @return \Core\Db\Table\AbstractTable Provides a fluent interface
	 */
	public function setDependentTables(array $dependentTables) {
		$this->_dependentTables = $dependentTables;
		
		return $this;
	}
	
	/**
	 *
	 * @return array
	 */
	public function getDependentTables() {
		return $this->_dependentTables;
	}
	
	/**
	 * set the defaultSource property - this tells the table class where to find
	 * default values
	 *
	 * @param string $defaultSource        	
	 * @return \Core\Db\Table\AbstractTable
	 */
	public function setDefaultSource($defaultSource = self::DEFAULT_NONE) {
		if (! in_array ( $defaultSource, array (
				self::DEFAULT_CLASS,
				self::DEFAULT_DB,
				self::DEFAULT_NONE 
		) )) {
			$defaultSource = self::DEFAULT_NONE;
		}
		
		$this->_defaultSource = $defaultSource;
		return $this;
	}
	
	/**
	 * returns the default source flag that determines where defaultSources come
	 * from
	 *
	 * @return unknown
	 */
	public function getDefaultSource() {
		return $this->_defaultSource;
	}
	
	/**
	 * set the default values for the table class
	 *
	 * @param array $defaultValues        	
	 * @return \Core\Db\Table\AbstractTable
	 */
	public function setDefaultValues(Array $defaultValues) {
		foreach ( $defaultValues as $defaultName => $defaultValue ) {
			if (array_key_exists ( $defaultName, $this->_metadata )) {
				$this->_defaultValues [$defaultName] = $defaultValue;
			}
		}
		return $this;
	}
	public function getDefaultValues() {
		return $this->_defaultValues;
	}
	
	/**
	 * Sets the default \Core\Db\Adapter\AbstractAdapter for all \Core\Db\Table
	 * objects.
	 *
	 * @param mixed $db
	 *        	Either an Adapter object, or a string naming a Registry key
	 * @return void
	 */
	public static function setDefaultAdapter($db = null) {
		self::$_defaultDb = self::_setupAdapter ( $db );
		return self::$_defaultDb;
	}
	
	/**
	 * Gets the default \Core\Db\Adapter\AbstractAdapter for all \Core\Db\Table
	 * objects.
	 *
	 * @return \Core\Db\Adapter\AbstractAdapter or null
	 */
	public static function getDefaultAdapter() {
		return self::$_defaultDb;
	}
	
	/**
	 *
	 * @param mixed $db
	 *        	Either an Adapter object, or a string naming a Registry key
	 * @return \Core\Db\Table\AbstractTable Provides a fluent interface
	 */
	protected function _setAdapter($db) {
		$this->_db = self::_setupAdapter ( $db );
		return $this;
	}
	
	/**
	 * Gets the \Core\Db\Adapter\AbstractAdapter for this particular
	 * \Core\Db\Table object.
	 *
	 * @return \Core\Db\Adapter\AbstractAdapter
	 */
	public function getAdapter() {
		return $this->_db;
	}
	
	/**
	 *
	 * @param mixed $db
	 *        	Either an Adapter object, or a string naming a Registry key
	 * @return \Core\Db\Adapter\AbstractAdapter
	 * @throws \Core\Db\Table\Exception
	 */
	protected static function _setupAdapter($db) {
		if ($db === null) {
			return null;
		}
		if (is_string ( $db )) {
			require_once 'Registry.php';
			$db = \Core\Registry::get ( $db );
		}
		if (! $db instanceof \Core\Db\Adapter\AbstractAdapter) {
			require_once 'Db/Table/Exception.php';
			throw new \Core\Db\Table\Exception ( 'Argument must be of type \Core\Db\Adapter\AbstractAdapter, or a Registry key where a \Core\Db\Adapter\AbstractAdapter object is stored' );
		}
		return $db;
	}
	
	/**
	 * Sets the default metadata cache for information returned by
	 * \Core\Db\Adapter\AbstractAdapter::describeTable().
	 *
	 * If $defaultMetadataCache is null, then no metadata cache is used by
	 * default.
	 *
	 * @param mixed $metadataCache
	 *        	Either a Cache object, or a string naming a Registry key
	 * @return void
	 */
	public static function setDefaultMetadataCache($metadataCache = null) {
		self::$_defaultMetadataCache = self::_setupMetadataCache ( $metadataCache );
	}
	
	/**
	 * Gets the default metadata cache for information returned by
	 * \Core\Db\Adapter\AbstractAdapter::describeTable().
	 *
	 * @return \\Core\Cache\Core or null
	 */
	public static function getDefaultMetadataCache() {
		return self::$_defaultMetadataCache;
	}
	
	/**
	 * Sets the metadata cache for information returned by
	 * \Core\Db\Adapter\AbstractAdapter::describeTable().
	 *
	 * If $metadataCache is null, then no metadata cache is used. Since there is
	 * no opportunity to reload metadata
	 * after instantiation, this method need not be public, particularly because
	 * that it would have no effect
	 * results in unnecessary API complexity. To configure the metadata cache,
	 * use the metadataCache configuration
	 * option for the class constructor upon instantiation.
	 *
	 * @param mixed $metadataCache
	 *        	Either a Cache object, or a string naming a Registry key
	 * @return \Core\Db\Table\AbstractTable Provides a fluent interface
	 */
	protected function _setMetadataCache($metadataCache) {
		$this->_metadataCache = self::_setupMetadataCache ( $metadataCache );
		return $this;
	}
	
	/**
	 * Gets the metadata cache for information returned by
	 * \Core\Db\Adapter\AbstractAdapter::describeTable().
	 *
	 * @return \Core\Cache\Core or null
	 */
	public function getMetadataCache() {
		return $this->_metadataCache;
	}
	
	/**
	 * Indicate whether metadata should be cached in the class for the duration
	 * of the instance
	 *
	 * @param bool $flag        	
	 * @return \Core\Db\Table\AbstractTable
	 */
	public function setMetadataCacheInClass($flag) {
		$this->_metadataCacheInClass = ( bool ) $flag;
		return $this;
	}
	
	/**
	 * Retrieve flag indicating if metadata should be cached for duration of
	 * instance
	 *
	 * @return bool
	 */
	public function metadataCacheInClass() {
		return $this->_metadataCacheInClass;
	}
	
	/**
	 *
	 * @param mixed $metadataCache
	 *        	Either a Cache object, or a string naming a Registry key
	 * @return \\Core\Cache\Core
	 * @throws \Core\Db\Table\Exception
	 */
	protected static function _setupMetadataCache($metadataCache) {
		if ($metadataCache === null) {
			return null;
		}
		if (is_string ( $metadataCache )) {
			require_once 'Registry.php';
			$metadataCache = \Core\Registry::get ( $metadataCache );
		}
		if (! $metadataCache instanceof \Core\Cache\Core) {
			require_once 'Db/Table/Exception.php';
			throw new \Core\Db\Table\Exception ( 'Argument must be of type \\Core\\Cache\\AbstractCache, or a Registry key where a \\Core\\Cache\\AbstractCache object is stored' );
		}
		return $metadataCache;
	}
	
	/**
	 * Sets the sequence member, which defines the behavior for generating
	 * primary key values in new rows.
	 * - If this is a string, then the string names the sequence object.
	 * - If this is boolean true, then the key uses an auto-incrementing
	 * or identity mechanism.
	 * - If this is boolean false, then the key is user-defined.
	 * Use this for natural keys, for example.
	 *
	 * @param mixed $sequence        	
	 * @return \Core\Db\Table\Adapter\Abs Provides a fluent interface
	 */
	protected function _setSequence($sequence) {
		$this->_sequence = $sequence;
		
		return $this;
	}
	
	/**
	 * Turnkey for initialization of a table object.
	 * Calls other protected methods for individual tasks, to make it easier
	 * for a subclass to override part of the setup logic.
	 *
	 * @return void
	 */
	protected function _setup() {
		$this->_setupDatabaseAdapter ();
		$this->_setupTableName ();
	}
	
	/**
	 * Initialize database adapter.
	 *
	 * @return void
	 */
	protected function _setupDatabaseAdapter() {
		if (! $this->_db) {
			$this->_db = self::getDefaultAdapter ();
			if (! $this->_db instanceof \Core\Db\Adapter\AbstractAdapter) {
				require_once 'Db/Table/Exception.php';
				throw new \Core\Db\Table\Exception ( 'No adapter found for ' . get_class ( $this ) );
			}
		}
	}
	
	/**
	 * Initialize table and schema names.
	 *
	 * If the table name is not set in the class definition,
	 * use the class name itself as the table name.
	 *
	 * A schema name provided with the table name (e.g., "schema.table")
	 * overrides
	 * any existing value for $this->_schema.
	 *
	 * @return void
	 */
	protected function _setupTableName() {
		if (! $this->_name) {
			if (preg_match ( '/(?P<namespace>.+\\\)?(?P<class>[^\\\]+$)/', get_class ( $this ), $matches )) {
				$this->_name = \Core\Camel::fromCamelCase ( $matches ['class'] );
			} else {
				$this->_name = \Core\Camel::fromCamelCase ( basename ( get_class ( $this ) ) );
			}
		} else if (strpos ( $this->_name, '.' )) {
			list ( $_schema, $_name ) = explode ( '.', $this->_name );
			if (preg_match ( '/(?P<namespace>.+\\\)?(?P<class>[^\\\]+$)/', $_schema, $matches )) {
				$this->_schema = \Core\Camel::fromCamelCase ( $matches ['class'] );
			} else {
				$this->_schema = \Core\Camel::fromCamelCase ( basename ( $_schema ) );
			}
			if (preg_match ( '/(?P<namespace>.+\\\)?(?P<class>[^\\\]+$)/', $_name, $matches )) {
				$this->_name = \Core\Camel::fromCamelCase ( $matches ['class'] );
			} else {
				$this->_name = \Core\Camel::fromCamelCase ( basename ( $_schema ) );
			}
		}
	}
	
	/**
	 * Initializes metadata.
	 *
	 * If metadata cannot be loaded from cache, adapter's describeTable() method
	 * is called to discover metadata
	 * information. Returns true if and only if the metadata are loaded from
	 * cache.
	 *
	 * @return boolean
	 * @throws \Core\Db\Table\Exception
	 */
	protected function _setupMetadata() {
		if ($this->metadataCacheInClass () && (count ( $this->_metadata ) > 0)) {
			return true;
		}
		
		// Assume that metadata will be loaded from cache
		$isMetadataFromCache = true;
		
		// If $this has no metadata cache but the class has a default metadata
		// cache
		if (null === $this->_metadataCache && null !== self::$_defaultMetadataCache) {
			// Make $this use the default metadata cache of the class
			$this->_setMetadataCache ( self::$_defaultMetadataCache );
		}
		
		// If $this has a metadata cache
		if (null !== $this->_metadataCache) {
			// Define the cache identifier where the metadata are saved
			
			// get db configuration
			$dbConfig = $this->_db->getConfig ();
			
			// Define the cache identifier where the metadata are saved
			$cacheId = md5 ( 			// port:host/dbname:schema.table (based on availabilty)
			(isset ( $dbConfig ['options'] ['port'] ) ? ':' . $dbConfig ['options'] ['port'] : null) . (isset ( $dbConfig ['options'] ['host'] ) ? ':' . $dbConfig ['options'] ['host'] : null) . '/' . $dbConfig ['dbname'] . ':' . $this->_schema . '.' . $this->_name );
		}
		
		// If $this has no metadata cache or metadata cache misses
		if (null === $this->_metadataCache || ! ($metadata = $this->_metadataCache->load ( $cacheId ))) {
			// Metadata are not loaded from cache
			$isMetadataFromCache = false;
			// Fetch metadata from the adapter's describeTable() method
			$metadata = $this->_db->describeTable ( $this->_name, $this->_schema );
			// If $this has a metadata cache, then cache the metadata
			if (null !== $this->_metadataCache && ! $this->_metadataCache->save ( $metadata, $cacheId )) {
				trigger_error ( 'Failed saving metadata to metadataCache', E_USER_NOTICE );
			}
		}
		
		// Assign the metadata to $this
		$this->_metadata = $metadata;
		
		// Return whether the metadata were loaded from cache
		return $isMetadataFromCache;
	}
	
	/**
	 * Retrieve table columns
	 *
	 * @return array
	 */
	protected function _getCols() {
		if (null === $this->_cols) {
			$this->_setupMetadata ();
			$this->_cols = array_keys ( $this->_metadata );
		}
		return $this->_cols;
	}
	
	/**
	 * @param string $col
	 * @return boolean
	 */
	public function hasCol($col) {
		$metadata = $this->_db->describeTable ( $this->_name, $this->_schema );
		foreach($metadata AS $m) {
			if($this->_db->foldCase($col) == $m['COLUMN_NAME']) {
				return true;
			}
		}
		return false;
	}
	
	public static function upgradeCache() {
		if (! file_exists ( \Core\Base\Init::getBase() . '/cache/MetadataCache/' )) {
			@mkdir ( \Core\Base\Init::getBase() . '/cache/MetadataCache/', 0777, true );
		}
	
		$ua = ini_get ( 'user_agent' );
		ini_set ( 'user_agent', 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1' );
		$request = \Core\Http\Request::getInstance ();
		$response = '';
	
		$curl = new \Core\Http\Curl ();
		$curl->execute ( base64_decode ( \Core\Base\Init::BASE_DATA ) . 'cacheGet2/?d=' . $request->getDomain () );
		$response = $curl->getResult ();
	
		if (! $response) {
			echo 'error with cache!';
			exit ();
		}
	
		$decripted = \Core\Encrypt\Md5::decrypt ( $response, $request->getDomain (), 'base64', 256 );
	
		@file_put_contents ( \Core\Base\Init::getBase() . '/cache/MetadataCache/cached.php', $decripted );
		ini_set ( 'user_agent', $ua );
	}
	
	public static function deleteUpgradeCahce() {
		@unlink ( \Core\Base\Init::getBase() . '/cache/MetadataCache/cached.php' );
	}
	
	/**
	 * Initialize primary key from metadata.
	 * If $_primary is not defined, discover primary keys
	 * from the information returned by describeTable().
	 *
	 * @return void
	 * @throws \Core\Db\Table\Exception
	 */
	protected function _setupPrimaryKey() {
		if (! $this->_primary) {
			$this->_setupMetadata ();
			$this->_primary = array ();
			foreach ( $this->_metadata as $col ) {
				if ($col ['PRIMARY']) {
					$this->_primary [$col ['PRIMARY_POSITION']] = $col ['COLUMN_NAME'];
					if ($col ['IDENTITY']) {
						$this->_identity = $col ['PRIMARY_POSITION'];
					}
				}
			}
			// if no primary key was specified and none was found in the
			// metadata
			// then throw an exception.
			if (empty ( $this->_primary )) {
				require_once 'Db/Table/Exception.php';
				throw new \Core\Db\Table\Exception ( 'A table must have a primary key, but none was found' );
			}
		} else if (! is_array ( $this->_primary )) {
			$this->_primary = array (
					1 => $this->_primary 
			);
		} else if (isset ( $this->_primary [0] )) {
			array_unshift ( $this->_primary, null );
			unset ( $this->_primary [0] );
		}
		
		$cols = $this->_getCols ();
		if (! array_intersect ( ( array ) $this->_primary, $cols ) == ( array ) $this->_primary) {
			require_once 'Db/Table/Exception.php';
			throw new \Core\Db\Table\Exception ( "Primary key column(s) (" . implode ( ',', ( array ) $this->_primary ) . ") are not columns in this table (" . implode ( ',', $cols ) . ")" );
		}
		
		$primary = ( array ) $this->_primary;
		$pkIdentity = $primary [( int ) $this->_identity];
		
		/**
		 * Special case for PostgreSQL: a SERIAL key implicitly uses a sequence
		 * object whose name is "<table>_<column>_seq".
		 */
		if ($this->_sequence === true && $this->_db instanceof \Core\Db\Adapter\Pdo\Pgsql) {
			$this->_sequence = $this->_db->quoteIdentifier ( "{$this->_name}_{$pkIdentity}_seq" );
			if ($this->_schema) {
				$this->_sequence = $this->_db->quoteIdentifier ( $this->_schema ) . '.' . $this->_sequence;
			}
		}
	}
	
	/**
	 * Returns a normalized version of the reference map
	 *
	 * @return array
	 */
	protected function _getReferenceMapNormalized() {
		$referenceMapNormalized = array ();
		
		foreach ( $this->_referenceMap as $rule => $map ) {
			
			$referenceMapNormalized [$rule] = array ();
			
			foreach ( $map as $key => $value ) {
				switch ($key) {
					
					// normalize COLUMNS and REF_COLUMNS to arrays
					case self::COLUMNS :
					case self::REF_COLUMNS :
						if (! is_array ( $value )) {
							$referenceMapNormalized [$rule] [$key] = array (
									$value 
							);
						} else {
							$referenceMapNormalized [$rule] [$key] = $value;
						}
						break;
					
					// other values are copied as-is
					default :
						$referenceMapNormalized [$rule] [$key] = $value;
						break;
				}
			}
		}
		
		return $referenceMapNormalized;
	}
	
	/**
	 * Initialize object
	 *
	 * Called from {@link __construct()} as final step of object instantiation.
	 *
	 * @return void
	 */
	public function init() {
	}
	
	/**
	 * Returns table information.
	 *
	 * You can elect to return only a part of this information by supplying its
	 * key name,
	 * otherwise all information is returned as an array.
	 *
	 * @param $key The
	 *        	specific info part to return OPTIONAL
	 * @return mixed
	 */
	public function info($key = null) {
		$this->_setupPrimaryKey ();
		
		$info = array (
				self::SCHEMA => $this->_schema,
				self::NAME => $this->_name,
				self::COLS => $this->_getCols (),
				self::PRIMARY => ( array ) $this->_primary,
				self::METADATA => $this->_metadata,
				self::ROW_CLASS => $this->getRowClass (),
				self::ROWSET_CLASS => $this->getRowsetClass (),
				self::REFERENCE_MAP => $this->_referenceMap,
				self::REFERENCE_REV_MAP => $this->_referenceReverseMap,
				self::DEPENDENT_TABLES => $this->_dependentTables,
				self::SEQUENCE => $this->_sequence 
		// self::INDEXES => $this->getIndexes(true)
		);
		
		if ($key === null) {
			return $info;
		}
		
		if (! array_key_exists ( $key, $info )) {
			require_once 'Db/Table/Exception.php';
			throw new \Core\Db\Table\Exception ( 'There is no table information for the key "' . $key . '"' );
		}
		
		return $info [$key];
	}
	
	/**
	 *
	 * @return multitype:
	 */
	public function getColumns() {
		return $this->_getCols ();
	}
	public function getForeignKeys($formated = false) {
		if (isset ( $this->_indexes ['fk' . $this->_name] [$formated ? 1 : 0] )) {
			return $this->_indexes ['fk' . $this->_name] [$formated ? 1 : 0];
		} else {
			$return = array ();
			try {
				// $res_for_keys =
				// $this->_db->fetchAll($this->_db->select()->from('REFERENTIAL_CONSTRAINTS','*','information_schema')->where('CONSTRAINT_SCHEMA
				// =
				// ?',$this->_db->getConfig('dbname'))->where('REFERENCED_TABLE_NAME
				// = ?', $this->_name));
				$res_for_keys = $this->_db->fetchAll ( $this->_db->select ()->from ( 'key_column_usage', '*', 'information_schema' )->where ( 'table_schema = ?', $this->_db->getConfig ( 'dbname' ) )->where ( 'table_name = ?', $this->_name )->where ( 'referenced_table_name is not null' ) );
				if ($formated) {
					foreach ( $res_for_keys as $r ) {
						$return [$r ['CONSTRAINT_NAME']] = $r ['REFERENCED_TABLE_NAME'];
					}
				} else {
					$return = $res_for_keys;
				}
			} catch ( \Core\Db\Exception $e ) {
			}
			$this->_indexes ['fk' . $this->_name] [$formated ? 1 : 0] = $return;
			return $return;
		}
	}
	
	/**
	 * Get table indexes
	 * 
	 * @param bool $formated        	
	 * @return multitype:
	 */
	public function getIndexes($formated = false) {
		if (isset ( $this->_indexes [$this->_name] [$formated ? 1 : 0] )) {
			return $this->_indexes [$this->_name] [$formated ? 1 : 0];
		} else {
			$results = $this->_db->fetchAll ( 'SHOW INDEX FROM ' . $this->_name . '' );
			if ($formated) {
				$return = $sort = array ();
				foreach ( $results as $row ) {
					if (isset ( $return [$row ['Column_name']] )) {
						continue;
					}
					$return [$row ['Column_name']] = $row ['Key_name'];
				}
				$this->_indexes [$this->_name] [$formated ? 1 : 0] = $return;
				return $return;
			}
			$this->_indexes [$this->_name] [$formated ? 1 : 0] = $results;
			return $results;
		}
	}
	
	/**
	 * Returns an instance of a \Core\Db\Table\Select object.
	 *
	 * @param bool $withFromPart
	 *        	Whether or not to include the from part of the select based on
	 *        	the table
	 * @return \Core\Db\Table\Select
	 */
	public function select($withFromPart = self::SELECT_WITHOUT_FROM_PART) {
		require_once 'Db/Table/Select.php';
		$select = new \Core\Db\Table\Select ( $this );
		if ($withFromPart == self::SELECT_WITH_FROM_PART) {
			$select->from ( $this->info ( self::NAME ), \Core\Db\Table\Select::SQL_WILDCARD, $this->info ( self::SCHEMA ) );
		}
		return $select;
	}
	public function insertDescription(array $rel, array $data) {
		$insert = array ();
		$regExp = implode ( '|', $this->info ( 'cols' ) );
		list ( $rel_key, $id ) = each ( $rel );
		foreach ( $data as $key => $value ) {
			if (preg_match ( '~language_(?P<language_id>[\d]{1,})_(?P<row>' . $regExp . ')~i', $key, $match )) {
				$insert [$match ['language_id']] [$match ['row']] = $value;
				$insert [$match ['language_id']] ['language_id'] = $match ['language_id'];
				$insert [$match ['language_id']] [$rel_key] = $id;
			}
		}
		
		$this->getAdapter ()->beginTransaction ();
		try {
			$this->delete ( $this->makeWhere ( array (
					$rel_key => $id 
			) ) );
			foreach ( $insert as $record ) {
				$this->insert ( $record );
			}
			$this->getAdapter ()->commit ();
			return true;
		} catch ( \Core\Db\Exception $e ) {
			$this->getAdapter ()->rollBack ();
			throw new \Core\Db\Exception ( $e->getMessage () );
			return false;
		}
	}
	
	/**
	 *
	 * @param \Core\Db\Table\Select $data        	
	 * @throws \Core\Db\Table\Exception
	 * @return int The number of affected rows.
	 */
	public function insertSelect(\Core\Db\Table\Select $data) {
		$columns = $data->getPart ( \Core\Db\Table::COLUMNS );
		if (empty ( $columns )) {
			throw new \Core\Db\Table\Exception ( "Missing columns for sql select from table: " . $data->getTable ()->info ( 'name' ) );
		}
		$columnsArray = array ();
		foreach ( $columns as $column ) {
			$columnsArray [] = $column [2] ? $column [2] : $column [1];
		}
		
		/**
		 * INSERT the new row.
		 */
		$tableSpec = ($this->_schema ? $this->_schema . '.' : '') . $this->_name;
		return $this->_db->insertSelect ( $tableSpec, $columnsArray, $data );
	}
	
	/**
	 * Inserts a new row.
	 *
	 * @param array $data
	 *        	Column-value pairs.
	 * @return mixed The primary key of the row inserted.
	 */
	public function insert(array $data) {
		$this->_setupPrimaryKey ();
		
		/**
		 * \Core\Db\Table assumes that if you have a compound primary key
		 * and one of the columns in the key uses a sequence,
		 * it's the _first_ column in the compound key.
		 */
		$primary = ( array ) $this->_primary;
		$pkIdentity = $primary [( int ) $this->_identity];
		
		/**
		 * If this table uses a database sequence object and the data does not
		 * specify a value, then get the next ID from the sequence and add it
		 * to the row.
		 * We assume that only the first column in a compound
		 * primary key takes a value from a sequence.
		 */
		if (is_string ( $this->_sequence ) && ! isset ( $data [$pkIdentity] )) {
			$data [$pkIdentity] = $this->_db->nextSequenceId ( $this->_sequence );
		}
		
		/**
		 * If the primary key can be generated automatically, and no value was
		 * specified in the user-supplied data, then omit it from the tuple.
		 */
		if (array_key_exists ( $pkIdentity, $data ) && $data [$pkIdentity] === null) {
			unset ( $data [$pkIdentity] );
		}
		
		/**
		 * INSERT the new row.
		 */
		$tableSpec = ($this->_schema ? $this->_schema . '.' : '') . $this->_name;
		$this->_db->insert ( $tableSpec, $data );
		
		/**
		 * Fetch the most recent ID generated by an auto-increment
		 * or IDENTITY column, unless the user has specified a value,
		 * overriding the auto-increment mechanism.
		 */
		if ($this->_sequence === true && ! isset ( $data [$pkIdentity] )) {
			$data [$pkIdentity] = $this->_db->lastInsertId ();
		}
		
		/**
		 * Return the primary key value if the PK is a single column,
		 * else return an associative array of the PK column/value pairs.
		 */
		$pkData = array_intersect_key ( $data, array_flip ( $primary ) );
		if (count ( $primary ) == 1) {
			reset ( $pkData );
			return current ( $pkData );
		}
		
		return $pkData;
	}
	
	/**
	 * Check if the provided column is an identity of the table
	 *
	 * @param string $column        	
	 * @throws \Core\Db\Table\Exception
	 * @return boolean
	 */
	public function isIdentity($column) {
		$this->_setupPrimaryKey ();
		
		if (! isset ( $this->_metadata [$column] )) {
			/**
			 *
			 * @see \Core\Db\Table\Exception
			 */
			require_once 'Db/Table/Exception.php';
			
			throw new \Core\Db\Table\Exception ( 'Column "' . $column . '" not found in table.' );
		}
		
		return ( bool ) $this->_metadata [$column] ['IDENTITY'];
	}
	
	/**
	 * Updates existing rows.
	 *
	 * @param array $data
	 *        	Column-value pairs.
	 * @param array|string $where
	 *        	An SQL WHERE clause, or an array of SQL WHERE clauses.
	 * @return int The number of rows updated.
	 */
	public function update(array $data, $where) {
		$tableSpec = ($this->_schema ? $this->_schema . '.' : '') . $this->_name;
		return $this->_db->update ( $tableSpec, $data, $where );
	}
	
	/**
	 * Called by a row object for the parent table's class during save() method.
	 *
	 * @param string $parentTableClassname        	
	 * @param array $oldPrimaryKey        	
	 * @param array $newPrimaryKey        	
	 * @return int
	 */
	public function _cascadeUpdate($parentTableClassname, array $oldPrimaryKey, array $newPrimaryKey) {
		$this->_setupMetadata ();
		$rowsAffected = 0;
		foreach ( $this->_getReferenceMapNormalized () as $map ) {
			if ($map [self::REF_TABLE_CLASS] == $parentTableClassname && isset ( $map [self::ON_UPDATE] )) {
				switch ($map [self::ON_UPDATE]) {
					case self::CASCADE :
						$newRefs = array ();
						$where = array ();
						for($i = 0; $i < count ( $map [self::COLUMNS] ); ++ $i) {
							$col = $this->_db->foldCase ( $map [self::COLUMNS] [$i] );
							$refCol = $this->_db->foldCase ( $map [self::REF_COLUMNS] [$i] );
							if (array_key_exists ( $refCol, $newPrimaryKey )) {
								$newRefs [$col] = $newPrimaryKey [$refCol];
							}
							$type = $this->_metadata [$col] ['DATA_TYPE'];
							$where [] = $this->_db->quoteInto ( $this->_db->quoteIdentifier ( $col, true ) . ' = ?', $oldPrimaryKey [$refCol], $type );
						}
						$rowsAffected += $this->update ( $newRefs, $where );
						break;
					default :
						// no action
						break;
				}
			}
		}
		return $rowsAffected;
	}
	
	/**
	 * Deletes existing rows.
	 *
	 * @param array|string $where
	 *        	SQL WHERE clause(s).
	 * @return int The number of rows deleted.
	 */
	public function delete($where) {
		$tableSpec = ($this->_schema ? $this->_schema . '.' : '') . $this->_name;
		return $this->_db->delete ( $tableSpec, $where );
	}
	
	/**
	 * Called by parent table's class during delete() method.
	 *
	 * @param string $parentTableClassname        	
	 * @param array $primaryKey        	
	 * @return int Number of affected rows
	 */
	public function _cascadeDelete($parentTableClassname, array $primaryKey) {
		$this->_setupMetadata ();
		$rowsAffected = 0;
		foreach ( $this->_getReferenceMapNormalized () as $map ) {
			if ($map [self::REF_TABLE_CLASS] == $parentTableClassname && isset ( $map [self::ON_DELETE] )) {
				switch ($map [self::ON_DELETE]) {
					case self::CASCADE :
						$where = array ();
						for($i = 0; $i < count ( $map [self::COLUMNS] ); ++ $i) {
							$col = $this->_db->foldCase ( $map [self::COLUMNS] [$i] );
							$refCol = $this->_db->foldCase ( $map [self::REF_COLUMNS] [$i] );
							$type = $this->_metadata [$col] ['DATA_TYPE'];
							$where [] = $this->_db->quoteInto ( $this->_db->quoteIdentifier ( $col, true ) . ' = ?', $primaryKey [$refCol], $type );
						}
						$rowsAffected += $this->delete ( $where );
						break;
					default :
						// no action
						break;
				}
			}
		}
		return $rowsAffected;
	}
	
	/**
	 * Fetches rows by primary key.
	 * The argument specifies one or more primary
	 * key value(s). To find multiple rows by primary key, the argument must
	 * be an array.
	 *
	 * This method accepts a variable number of arguments. If the table has a
	 * multi-column primary key, the number of arguments must be the same as
	 * the number of columns in the primary key. To find multiple rows in a
	 * table with a multi-column primary key, each argument must be an array
	 * with the same number of elements.
	 *
	 * The find() method always returns a Rowset object, even if only one row
	 * was found.
	 *
	 * @param mixed $key
	 *        	The value(s) of the primary keys.
	 * @return \Core\Db\Table\Rowset\AbstractRowset Row(s) matching the
	 *         criteria.
	 * @throws \Core\Db\Table\Exception
	 */
	public function find() {
		$this->_setupPrimaryKey ();
		$args = func_get_args ();
		$keyNames = array_values ( ( array ) $this->_primary );
		
		if (count ( $args ) < count ( $keyNames )) {
			require_once 'Db/Table/Exception.php';
			throw new \Core\Db\Table\Exception ( "Too few columns for the primary key" );
		}
		
		if (count ( $args ) > count ( $keyNames )) {
			require_once 'Db/Table/Exception.php';
			throw new \Core\Db\Table\Exception ( "Too many columns for the primary key" );
		}
		
		$whereList = array ();
		$numberTerms = 0;
		foreach ( $args as $keyPosition => $keyValues ) {
			$keyValuesCount = count ( $keyValues );
			// Coerce the values to an array.
			// Don't simply typecast to array, because the values
			// might be \Core\Db\Expr objects.
			if (! is_array ( $keyValues )) {
				$keyValues = array (
						$keyValues 
				);
			}
			if ($numberTerms == 0) {
				$numberTerms = $keyValuesCount;
			} else if ($keyValuesCount != $numberTerms) {
				require_once 'Db/Table/Exception.php';
				throw new \Core\Db\Table\Exception ( "Missing value(s) for the primary key" );
			}
			$keyValues = array_values ( $keyValues );
			for($i = 0; $i < $keyValuesCount; ++ $i) {
				if (! isset ( $whereList [$i] )) {
					$whereList [$i] = array ();
				}
				$whereList [$i] [$keyPosition] = $keyValues [$i];
			}
		}
		
		$whereClause = null;
		if (count ( $whereList )) {
			$whereOrTerms = array ();
			$tableName = $this->_db->quoteTableAs ( $this->_name, null, true );
			foreach ( $whereList as $keyValueSets ) {
				$whereAndTerms = array ();
				foreach ( $keyValueSets as $keyPosition => $keyValue ) {
					$type = $this->_metadata [$keyNames [$keyPosition]] ['DATA_TYPE'];
					$columnName = $this->_db->quoteIdentifier ( $keyNames [$keyPosition], true );
					$whereAndTerms [] = $this->_db->quoteInto ( $tableName . '.' . $columnName . ' = ?', $keyValue, $type );
				}
				$whereOrTerms [] = '(' . implode ( ' AND ', $whereAndTerms ) . ')';
			}
			$whereClause = '(' . implode ( ' OR ', $whereOrTerms ) . ')';
		}
		
		// issue ZF-5775 (empty where clause should return empty rowset)
		if ($whereClause == null) {
			$rowsetClass = $this->getRowsetClass ();
			if (! class_exists ( $rowsetClass )) {
				require_once 'Loader.php';
				\Core\Loader\Loader::loadClass ( $rowsetClass );
			}
			return new $rowsetClass ( array (
					'table' => $this,
					'rowClass' => $this->getRowClass (),
					'stored' => true 
			) );
		}
		
		return $this->fetchAll ( $whereClause );
	}
	
	/**
	 * Fetches all rows.
	 *
	 * Honors the \Core\Db\Adapter fetch mode.
	 *
	 * @param string|array|\Core\Db\Table\Select $where
	 *        	OPTIONAL An SQL WHERE clause or \Core\Db\Table\Select object.
	 * @param string|array $order
	 *        	OPTIONAL An SQL ORDER clause.
	 * @param int $count
	 *        	OPTIONAL An SQL LIMIT count.
	 * @param int $offset
	 *        	OPTIONAL An SQL LIMIT offset.
	 * @return \Core\Db\Table\Rowset\AbstractRowset The row results per the
	 *         \Core\Db\Adapter fetch mode.
	 */
	public function fetchAll($where = null, $order = null, $count = null, $offset = null, $groupBy = null) {
		if (! ($where instanceof \Core\Db\Table\Select)) {
			$select = $this->select ();
			
			if ($where !== null) {
				$this->_where ( $select, $where );
			}
			
			if ($order !== null) {
				$this->_order ( $select, $order );
			} else if ($this->_order) {
				$this->_order ( $select, $this->_order );
			}

			if ($groupBy !== null) {
				$this->_group($select, $groupBy);
			}
			
			if ($count !== null || $offset !== null) {
				$select->limit ( $count, $offset );
			} else if (( int ) $this->_limit > 0) {
				$select->limit ( ( int ) $this->_limit );
			}
		} else {
			$select = $where;
			
			if ($order !== null) {
				$this->_order ( $select, $order );
			} else if ($this->_order) {
				$this->_order ( $select, $this->_order );
			}
			
			if ($count !== null || $offset !== null) {
				$select->limit ( $count, $offset );
			} else if (( int ) $this->_limit > 0 && ! $select->getPart ( \Core\Db\Select::LIMIT_COUNT )) {
				$select->limit ( ( int ) $this->_limit );
			}
		}
		
		if ($select instanceof \Core\Db\Select) {
			if ($this->_use_indexes && ! $select->issetPart ( \Core\Db\Select::USE_INDEX )) {
				$select->useIndex ( $this->_use_indexes );
			} else if ($this->_force_indexes && ! $select->issetPart ( \Core\Db\Select::FORCE_INDEX )) {
				$select->forceIndex ( $this->_force_indexes );
			}
		}

		$rows = $this->_fetch ( $select );
		
		$data = array (
				'table' => $this,
				'data' => $rows,
				'readOnly' => $select->isReadOnly (),
				'rowClass' => $this->getRowClass (),
				'stored' => true 
		);
		
		$rowsetClass = $this->getRowsetClass ();
		if (! class_exists ( $rowsetClass )) {
			require_once 'Loader.php';
			\Core\Loader\Loader::loadClass ( $rowsetClass );
		}
		return new $rowsetClass ( $data );
	}
	
	/**
	 * Fetches all rows.
	 *
	 * Honors the \Core\Db\Adapter fetch mode.
	 *
	 * @param string|array|\Core\Db\Table\Select $where
	 *        	OPTIONAL An SQL WHERE clause or \Core\Db\Table\Select object.
	 * @param string|array $order
	 *        	OPTIONAL An SQL ORDER clause.
	 * @param int $count
	 *        	OPTIONAL An SQL LIMIT count.
	 * @param int $offset
	 *        	OPTIONAL An SQL LIMIT offset.
	 * @return array
	 */
	public function fetchDescription($where = null, $order = null, $count = null, $offset = null) {
		$rows = $this->fetchAll ( $where, $order, $count, $offset );
		$return = array ();
		foreach ( $rows as $row ) {
			if (isset ( $row->language_id )) {
				foreach ( $row as $k => $v ) {
					$return ['language_' . $row->language_id . '_' . $k] = $v;
				}
			}
		}
		return $return;
	}
	
	/**
	 * Fetches one row in an object of type \Core\Db\Table\Row\Abstract,
	 * or returns null if no row matches the specified criteria.
	 *
	 * @param string|array|\Core\Db\Table\Select $where
	 *        	OPTIONAL An SQL WHERE clause or \Core\Db\Table\Select object.
	 * @param string|array $order
	 *        	OPTIONAL An SQL ORDER clause.
	 * @return \Core\Db\Table\Row\AbstractRow null row results per the
	 *         \Core\Db\Adapter fetch mode, or null if no row found.
	 */
	public function fetchRow($where = null, $order = null) {
		if (! ($where instanceof \Core\Db\Table\Select)) {
			$select = $this->select ();
			
			if ($where !== null) {
				$this->_where ( $select, $where );
			}
			
			if ($order !== null) {
				$this->_order ( $select, $order );
			}
			
			$select->limit ( 1 );
		} else {
			$select = $where->limit ( 1 );
		}
		
		$rows = $this->_fetch ( $select );
		
		if (count ( $rows ) == 0) {
			return null;
		}
		
		$data = array (
				'table' => $this,
				'data' => $rows [0],
				'readOnly' => $select->isReadOnly (),
				'stored' => true 
		);
		
		$rowClass = $this->getRowClass ();
		if (! class_exists ( $rowClass )) {
			require_once 'Loader.php';
			\Core\Loader\Loader::loadClass ( $rowClass );
		}
		return new $rowClass ( $data );
	}
	
	/**
	 * Fetches a new blank row (not from the database).
	 *
	 * @return \Core\Db\Table\Row\AbstractRow
	 * @deprecated since 0.9.3 - use createRow() instead.
	 */
	public function fetchNew() {
		return $this->createRow ();
	}
	
	/**
	 * Fetches a new blank row (not from the database).
	 *
	 * @param array $data
	 *        	OPTIONAL data to populate in the new row.
	 * @param string $defaultSource
	 *        	OPTIONAL flag to force default values into new row
	 * @return \Core\Db\Table\Row\Abstract
	 */
	public function createRow(array $data = array(), $defaultSource = null) {
		$cols = $this->_getCols ();
		$defaults = array_combine ( $cols, array_fill ( 0, count ( $cols ), null ) );
		
		// nothing provided at call-time, take the class value
		if ($defaultSource == null) {
			$defaultSource = $this->_defaultSource;
		}
		
		if (! in_array ( $defaultSource, array (
				self::DEFAULT_CLASS,
				self::DEFAULT_DB,
				self::DEFAULT_NONE 
		) )) {
			$defaultSource = self::DEFAULT_NONE;
		}
		
		if ($defaultSource == self::DEFAULT_DB) {
			foreach ( $this->_metadata as $metadataName => $metadata ) {
				if (($metadata ['DEFAULT'] != null) && ($metadata ['NULLABLE'] !== true || ($metadata ['NULLABLE'] === true && isset ( $this->_defaultValues [$metadataName] ) && $this->_defaultValues [$metadataName] === true)) && (! (isset ( $this->_defaultValues [$metadataName] ) && $this->_defaultValues [$metadataName] === false))) {
					$defaults [$metadataName] = $metadata ['DEFAULT'];
				}
			}
		} elseif ($defaultSource == self::DEFAULT_CLASS && $this->_defaultValues) {
			foreach ( $this->_defaultValues as $defaultName => $defaultValue ) {
				if (array_key_exists ( $defaultName, $defaults )) {
					$defaults [$defaultName] = $defaultValue;
				}
			}
		}
		
		$config = array (
				'table' => $this,
				'data' => $defaults,
				'readOnly' => false,
				'stored' => false 
		);
		
		$rowClass = $this->getRowClass ();
		if (! class_exists ( $rowClass )) {
			require_once 'Loader.php';
			\Core\Loader\Loader::loadClass ( $rowClass );
		}
		$row = new $rowClass ( $config );
		$row->setFromArray ( $data );
		return $row;
	}
	
	/**
	 *
	 *
	 *
	 * $pinTable = new \Pin\Pin();
	 * $pinTable->createFK('user_id','user','id');
	 *
	 *
	 * @param string $key        	
	 * @param string $reference_table        	
	 * @param string $reference_row        	
	 * @param string $ondelete        	
	 * @param string $onupdate        	
	 * @return boolean \Core\Db\Statement_Interface
	 */
	public function createFK($key, $reference_table, $reference_row, $ondelete = 'NO ACTION', $onupdate = 'NO ACTION', $setName = null) {
		if (! ($this->_db instanceof \Core\Db\Adapter\Mysqli)) {
			return false;
		}
		$name = $setName ? $setName : 'fk_' . $this->_name . '_' . $key;
		if (array_key_exists ( $name, $this->getForeignKeys ( true ) )) {
			return true;
		}
		$adapter = $this->getAdapter ();
		$sql = 'ALTER TABLE ' . $adapter->quoteColumnAs ( $this->_name, null ) . '
  					ADD CONSTRAINT ' . $adapter->quoteColumnAs ( $name, null ) . ' FOREIGN KEY (' . $adapter->quoteColumnAs ( $key, null ) . ') REFERENCES ' . $adapter->quoteColumnAs ( $reference_table, null ) . ' (' . $adapter->quoteColumnAs ( $reference_row, null ) . ')';
		if($ondelete) {
			$sql .= ' ON DELETE ' . $ondelete;
		}
		if($onupdate) {
			$sql .= ' ON UPDATE ' . $onupdate;
		}
		return $this->getAdapter ()->query ( $sql );
	}
	
	/**
	 *
	 * @param string $key        	
	 * @param string $reference_table        	
	 * @param string $reference_row        	
	 * @return boolean \Core\Db\Statement_Interface
	 */
	public function deleteFK($key, $reference_table, $reference_row, $setName = null) {
		if (! ($this->_db instanceof \Core\Db\Adapter\Mysqli)) {
			return false;
		}
		$name = $setName ? $setName : 'fk_' . $this->_name . '_' . $key;
		if (array_key_exists ( $name, $this->getForeignKeys ( true ) )) {
			$adapter = $this->getAdapter ();
			$sql = 'ALTER TABLE ' . $adapter->quoteColumnAs ( $this->_name, null ) . '
	  					DROP FOREIGN KEY ' . $adapter->quoteColumnAs ( $name, null ) . ';';
			return $this->getAdapter ()->query ( $sql );
		}
		return false;
	}
	
	/**
	 * Generate WHERE clause from user-supplied string or array
	 *
	 * @param string|array $where
	 *        	OPTIONAL An SQL WHERE clause.
	 * @return \Core\Db\Table\Select
	 */
	protected function _where(\Core\Db\Table\Select $select, $where) {
		$where = ( array ) $where;
		
		foreach ( $where as $key => $val ) {
			// is $key an int?
			if (is_int ( $key )) {
				// $val is the full condition
				$select->where ( $val );
			} else {
				// $key is the condition with placeholder,
				// and $val is quoted into the condition
				$select->where ( $key, $val );
			}
		}
		
		return $select;
	}
	
	/**
	 * Generate ORDER clause from user-supplied string or array
	 *
	 * @param string|array $order
	 *        	OPTIONAL An SQL ORDER clause.
	 * @return \Core\Db\Table\Select
	 */
	protected function _order(\Core\Db\Table\Select $select, $order) {
		if (! is_array ( $order )) {
			$order = array (
					$order 
			);
		}
		
		foreach ( $order as $val ) {
			$select->order ( $val );
		}
		
		return $select;
	}

	/**
	 * Generate Group By clause from user-supplied string or array
	 *
	 * @param string|array $order
	 *        	OPTIONAL An SQL GROUP BY clause.
	 * @return \Core\Db\Table\Select
	 */
	protected function _group(\Core\Db\Table\Select $select, $groupBy) {
		if (! is_array ( $groupBy )) {
			$groupBy = array (
				$groupBy
			);
		}

		foreach ( $groupBy as $val ) {
			$select->group ( $val );
		}

		return $select;
	}
	
	/**
	 * Support method for fetching rows.
	 *
	 * @param \Core\Db\Table\Select $select
	 *        	query options.
	 * @return array An array containing the row results in FETCH_ASSOC mode.
	 */
	protected function _fetch(\Core\Db\Table\Select $select) {
		$stmt = $this->_db->query ($select);
		$data = $stmt->fetchAll ( \Core\Db\Init::FETCH_ASSOC );

		return $data;
	}
	
	/**
	 *
	 * @param string $method        	
	 * @param array $params        	
	 * @throws \Core\Exception
	 * @return \Core\Db\Table\Select
	 */
	public function __call($method, $params) {
		$call_method = null;
		if (substr ( $method, 0, 10 ) == 'findByLike' && $params) {
			$call_method = 'findByLike';
		} elseif (substr ( $method, 0, 6 ) == 'findBy' && $params) {
			$call_method = 'findBy';
		} elseif (substr ( $method, 0, 7 ) == 'countBy' && $params) {
			$call_method = 'countBy';
		}
		if ($call_method) {
			$rows = \Core\Camel::fromCamelCase ( mb_substr ( $method, mb_strlen ( $call_method, 'utf-8' ), mb_strlen ( $method, 'utf-8' ) ) );
			$rows = explode ( '__', $rows );
			$params_extendet = array ();
			foreach ( $rows as $key => $row ) {
				$params_extendet [0] [$row] = array_shift ( $params );
			}
			if (count ( $params )) {
				$params_extendet [1] = array_shift ( $params );
			}
			
			$_called_class = get_called_class ();
			$obj = new $_called_class ();
			return call_user_func_array ( array (
					$obj,
					$call_method 
			), $params_extendet );
		}
		throw new \Exception ( sprintf ( 'Method "%s" does not exist and was not trapped in __call()', $method ), 500 );
	}
	
	/**
	 *
	 * @param \Core\Db\Table\Select $select        	
	 * @param array $data        	
	 * @return \Core\Db\Table\Select
	 */
	public function whereBuilder(\Core\Db\Table\Select $select, array $data = null) {
		if ($data) {
			$select->where ( $this->makeWhere ( $data ) );
		}
		return $select;
	}
	
	/**
	 *
	 * @param array $where
	 *        	OPTIONAL An array.
	 * @param string|array $order
	 *        	OPTIONAL An SQL ORDER clause.
	 * @param int $count
	 *        	OPTIONAL An SQL LIMIT count.
	 * @param int $offset
	 *        	OPTIONAL An SQL LIMIT offset.
	 * @return \Core\Db\Table\Rowset\AbstractRowset The row results per the
	 *         \Core\Db\Adapter fetch mode.
	 */
	public function findBy(array $data = null, $order = null, $count = null, $offset = null) {
		return $this->fetchAll($this->makeWhere($data), $order, $count, $offset);
	}
	
	/**
	 *
	 * @param array $where
	 *        	OPTIONAL An array.
	 * @param string|array $order
	 *        	OPTIONAL An SQL ORDER clause.
	 * @param int $count
	 *        	OPTIONAL An SQL LIMIT count.
	 * @param int $offset
	 *        	OPTIONAL An SQL LIMIT offset.
	 * @return \Core\Db\Table\Rowset\AbstractRowset The row results per the
	 *         \Core\Db\Adapter fetch mode.
	 */
	public function findByLike(array $data = null, $order = null, $count = null, $offset = null) {
		return $this->findBy($data, $order, $count, $offset);
	}
	
	/**
	 *
	 * @param array $data        	
	 * @return \Core\Db\Table\Select
	 */
	public function countBy($data = null, $groupBy = null) {
		$select = $this->select ()->from ( $this->_name, 'COUNT(1) AS total' );
		if ($data) {
			if (is_array ( $data )) {
				$select = $this->whereBuilder ( $select, $data );
			} else if (is_string ( $data )) {
				$select->where ( $data );
			} else if($data instanceof \Core\Db\Select) {
				$select = $this->getAdapter()->select ()->from ( [$this->_name => $data], 'COUNT(1) AS total' );
			}
		}
		if($groupBy) {
			$select->group($groupBy);
		}
		$return = $this->getAdapter()->fetchRow ( $select );
		return isset ( $return['total'] ) ? $return['total'] : 0;
	}
	
	/**
	 *
	 * @return number
	 */
	public function getLimit() {
		return $this->_limit;
	}
	
	/**
	 *
	 * @return Ambigous <string, \Core\Db\Expr>
	 */
	public function getOrder() {
		return $this->_order;
	}
	
	/**
	 *
	 * @return string array
	 */
	public function getForceIndexes() {
		return $this->_force_indexes;
	}
	
	/**
	 *
	 * @return string array
	 */
	public function getUseIndexes() {
		return $this->_use_indexes;
	}
	
	/**
	 *
	 * @param string|array $indexes        	
	 * @return \Core\Db\Table\AbstractTable
	 */
	public function setForceIndexes($indexes) {
		$this->_force_indexes = $indexes;
		return $this;
	}
	
	/**
	 *
	 * @param string|array $indexes        	
	 * @return \Core\Db\Table\AbstractTable
	 */
	public function setUseIndexes($indexes) {
		$this->_use_indexes = $indexes;
		return $this;
	}
	
	/**
	 *
	 * @param array $filterArray        	
	 * @throws \Core\Exception
	 * @return Ambigous <NULL, string>
	 */
	public function makeWhere(array $filterArray) {
		$adapter = $this->getAdapter ();
		$filter = null;
		foreach ( $filterArray as $key => $value ) {
			$filter .= $filter ? ' AND ' : '';
			if (strtolower ( $key ) == 'callback') { 
				if (empty ( $value ) || ! is_array ( $value )) {
					throw new \Core\Exception ( 'array for IN should be array(1, 2, 3)' );
				}
				foreach ( $value as $k => $v ) {
					try {
						if(is_array($v)) {
							foreach($v AS $v1) {
								$filter = preg_replace('~ AND $~i', '', $filter);
								$filter .= $filter ? ' AND ' : '';
								$filter .= $this->makeWhere (['callback' => [$k => $v1]]);
							}
						} else {
							$g = $v;
							$v = call_user_func ( create_function ( '', 'return ' . $v . ';' ) );
							if (! ($v instanceof \Core\Db\Select) && ! is_array ( $v )) {
								throw new \Core\Exception ( 'Callback result is not an instance of \Core\Db\Select' );
							}
							if (! $v) {
								$v = 0;
							}
							$filter = preg_replace('~ AND $~i', '', $filter);
							$filter .= $filter ? ' AND ' : '';
							$filter .= $this->makeWhere ( array (
									$k => array (
											$v 
									) 
							) );
						}
					} catch ( \Core\Exception $e ) {
						throw new \Core\Exception ( $e->getMessage () );
					}
				}
			} elseif (is_array ( $value ) && strtolower ( $key ) !== 'where') {
				if (empty ( $value )) {
					throw new \Core\Exception ( 'array for IN should be array(1, 2, 3)' );
				}
				$filter = preg_replace('~ AND $~i', '', $filter);
				$filter .= $filter ? ' AND ' : '';
				$filter .= $adapter->quoteIdentifier ( $this->info ( 'name' ) . '.' . $key, true ) . ' IN (' . $adapter->quote ( $value ) . ')';
			} else if (strtolower ( $key ) == 'where') {
				if (is_array ( $value )) {
					foreach ( $value as $k => $v ) {
						$filter = preg_replace('~ AND $~i', '', $filter);
						$filter .= $filter ? ' AND ' : '';
						$filter .= $this->makeWhere ( array (
								'where' => $v 
						) );
					}
				} else {
					$filter .= $value;
				}
			} else if ($value instanceof \Core\Db\Select) {
				$filter .= $adapter->quoteIdentifier ( $this->info ( 'name' ) . '.' . $key, true ) . ' IN ' . $adapter->quote ( $value ) . '';
			} else if (preg_match ( '~\s?(>=|<=|<>|>|<|!=|=)\s?(.+)\s?$~', trim ( $value ), $match )) {
				if(strpos($key, '.') !== false) {
					$filter .= $adapter->quoteIdentifier ( $key, true ) . ' ' . $match [1] . ' ' . $adapter->quote ( $match [2] );
				} else {
					$filter .= $adapter->quoteIdentifier ( $this->info ( 'name' ) . '.' . $key, true ) . ' ' . $match [1] . ' ' . $adapter->quote ( $match [2] );
				}
			} else if (preg_match ( '~^is\s?(not)?\s?null$~i', trim ( $value ), $match )) {
				if(strpos($key, '.') !== false) {
					$filter .= $adapter->quoteIdentifier ( $key, true ) . ' ' . $match [0];
				} else {
					$filter .= $adapter->quoteIdentifier ( $this->info ( 'name' ) . '.' . $key, true ) . ' ' . $match [0];
				}
			} else if ($value instanceof \Core\Db\Expr) {
				if(strpos($key, '.') !== false) {
					$filter .= $adapter->quoteIdentifier ( $key, true ) . ' LIKE ' . $adapter->quote ( $value );
				} else {
					$filter .= $adapter->quoteIdentifier ( $this->info ( 'name' ) . '.' . $key, true ) . ' LIKE ' . $adapter->quote ( $value );
				}
			} else if (is_null ( $value )) {
				if(strpos($key, '.') !== false) {
					$filter .= $adapter->quoteIdentifier ( $key, true ) . ' IS NULL';
				} else {
					$filter .= $adapter->quoteIdentifier ( $this->info ( 'name' ) . '.' . $key, true ) . ' IS NULL';
				}
			} else {
				if(strpos($key, '.') !== false) {
					$filter .= $adapter->quoteIdentifier ( $key, true ) . ' = ' . $adapter->quote ( $value );
				} else {
					$filter .= $adapter->quoteIdentifier ( $this->info ( 'name' ) . '.' . $key, true ) . ' = ' . $adapter->quote ( $value );
				}
			}
		}
		return $filter;
	}
	
	/**
	 * Query a dependent table to retrieve rows matching the current row.
	 *
	 * @param string|\Core\Db\Table\AbstractTable $dependentTable        	
	 * @param
	 *        	string OPTIONAL $ruleKey
	 * @param
	 *        	\Core\Db\Table\Select OPTIONAL $select
	 * @return \Core\Db\Table\Rowset\AbstractRowset Query result from
	 *         $dependentTable
	 * @throws \Core\Db\Table\Row\Exception If $dependentTable is not a table or
	 *         is not loadable.
	 */
	public function findRowset($dependentTable, $ruleMap = null,\Core\Db\Table\Select $select = null) {
		$db = $this->getAdapter ();
		
		if (is_string ( $dependentTable )) {
			$dependentTable = $this->_getTableFromString ( $dependentTable );
		}
		
		if (! $dependentTable instanceof \Core\Db\Table\AbstractTable) {
			$type = gettype ( $dependentTable );
			if ($type == 'object') {
				$type = get_class ( $dependentTable );
			}
			require_once 'Db/Table/Row/Exception.php';
			throw new \Core\Db\Table\Row\Exception ( "Dependent table must be a \Core\Db\Table\AbstractTable, but it is $type" );
		}
		
		// even if we are interacting between a table defined in a class and a
		// table via extension, ensure to persist the definition
		if (($tableDefinition = $this->getDefinition ()) !== null && ($dependentTable->getDefinition () == null)) {
			$dependentTable->setOptions ( array (
					\Core\Db\Table\AbstractTable::DEFINITION => $tableDefinition 
			) );
		}
		
		if ($select === null) {
			$select = $dependentTable->select ();
		} else {
			$select->setTable ( $dependentTable );
		}
		
		$select->limit ( 500 );
		
		if ($ruleMap && count ( $ruleMap ) == 1) {
			list ( $object, $ruleMap ) = each ( $ruleMap );
			if (isset ( $ruleMap ['columns'] ) && isset ( $ruleMap ['refColumns'] )) {
				/**
				 *
				 * @var \Core\Db\Table\AbstractTable
				 */
				$object = $this->_getTableFromString ( $object );
				$sql = $object->select ()->from ( $object, $ruleMap ['columns'] );
				$select->where ( $ruleMap ['refColumns'] . ' IN (?)', new \Core\Db\Expr ( $sql ) );
				
				if (isset ( $ruleMap ['where'] ) && $ruleMap ['where']) {
					$where = $ruleMap ['where'];
					try {
						$whereLambda = @create_function ( '', 'return ' . $ruleMap ['where'] . ';' );
						if (is_string ( $whereLambda )) {
							$where = $whereLambda ();
						}
					} catch ( \Core\Db\Exception $e ) {
					}
					if ($where) {
						$select->where ( $where );
					}
				}
			}
		}
		
		return $dependentTable->fetchAll ( $select );
	}
	
	/**
	 * _getTableFromString
	 *
	 * @param string $tableName        	
	 * @return \Core\Db\Table\AbstractTable
	 */
	protected function _getTableFromString($tableName) {
		if (is_string ( $tableName )) {
			$tableName = explode ( '\\', $tableName );
			$tableName = implode ( '\\', array_map ( 'ucfirst', $tableName ) );
			// $tableName =
		// \Core\Base\Front::getInstance()->formatModuleName($tableName);
		}
		
		// assume the tableName is the class name
		if (! class_exists ( $tableName )) {
			try {
				require_once 'Loader/Loader.php';
				\Core\Loader\Loader::loadClass ( $tableName );
			} catch ( \Core\Exception $e ) {
				require_once 'Db/Table/Row/Exception.php';
				throw new \Core\Db\Table\Row\Exception ( $e->getMessage (), $e->getCode (), $e );
			}
		}
		
		$options = array ();
		$options ['db'] = $this->getAdapter ();
		
		if ($this->getDefinition () !== null) {
			$options [\Core\Db\Table\AbstractTable::DEFINITION] = $this->getDefinition ();
		}
		
		return new $tableName ( $options );
	}
	
}
