<?php

namespace Core\Search;

class Lucene implements \Core\Search\Lucene\InterfaceLucene {
	/**
	 * Default field name for search
	 *
	 * Null means search through all fields
	 *
	 * @var string
	 */
	private static $_defaultSearchField = null;
	
	/**
	 * Result set limit
	 *
	 * 0 means no limit
	 *
	 * @var integer
	 */
	private static $_resultSetLimit = 0;
	
	/**
	 * File system adapter.
	 *
	 * @var \Core\Search\Lucene\Storage\Directory
	 */
	private $_directory = null;
	
	/**
	 * File system adapter closing option
	 *
	 * @var boolean
	 */
	private $_closeDirOnExit = true;
	
	/**
	 * Writer for this index, not instantiated unless required.
	 *
	 * @var \Core\Search\Lucene\Index\Writer
	 */
	private $_writer = null;
	
	/**
	 * Array of \Core\Search\Lucene\Index\SegmentInfo objects for this index.
	 *
	 * @var array \Core\Search\Lucene\Index\SegmentInfo
	 */
	private $_segmentInfos = array ();
	
	/**
	 * Number of documents in this index.
	 *
	 * @var integer
	 */
	private $_docCount = 0;
	
	/**
	 * Flag for index changes
	 *
	 * @var boolean
	 */
	private $_hasChanges = false;
	
	/**
	 * Signal, that index is already closed, changes are fixed and resources are
	 * cleaned up
	 *
	 * @var boolean
	 */
	private $_closed = false;
	
	/**
	 * Number of references to the index object
	 *
	 * @var integer
	 */
	private $_refCount = 0;
	
	/**
	 * Current segment generation
	 *
	 * @var integer
	 */
	private $_generation;
	const FORMAT_PRE_2_1 = 0;
	const FORMAT_2_1 = 1;
	const FORMAT_2_3 = 2;
	
	/**
	 * Index format version
	 *
	 * @var integer
	 */
	private $_formatVersion;
	
	/**
	 * Create index
	 *
	 * @param mixed $directory        	
	 * @return \Core\Search\Lucene\InterfaceLucene
	 */
	public static function create($directory) {
		return new \Core\Search\Lucene\Proxy ( new \Core\Search\Lucene ( $directory, true ) );
	}
	
	/**
	 * Open index
	 *
	 * @param mixed $directory        	
	 * @return \Core\Search\Lucene\InterfaceLucene
	 */
	public static function open($directory) {
		return new \Core\Search\Lucene\Proxy ( new \Core\Search\Lucene ( $directory, false ) );
	}
	
	/**
	 * Generation retrieving counter
	 */
	const GENERATION_RETRIEVE_COUNT = 10;
	
	/**
	 * Pause between generation retrieving attempts in milliseconds
	 */
	const GENERATION_RETRIEVE_PAUSE = 50;
	
	/**
	 * Get current generation number
	 *
	 * Returns generation number
	 * 0 means pre-2.1 index format
	 * -1 means there are no segments files.
	 *
	 * @param \Core\Search\Lucene\Storage\Directory $directory        	
	 * @return integer
	 * @throws \Core\Search\Lucene\Exception
	 */
	public static function getActualGeneration(\Core\Search\Lucene\Storage\Directory $directory) {
		/**
		 * JO_Search_Lucene uses segments.gen file to retrieve current
		 * generation number
		 *
		 * Apache Lucene index format documentation mentions this method only as
		 * a fallback method
		 *
		 * Nevertheless we use it according to the performance considerations
		 *
		 * @todo check if we can use some modification of Apache Lucene
		 *       generation determination algorithm
		 *       without performance problems
		 */
		try {
			for($count = 0; $count < self::GENERATION_RETRIEVE_COUNT; $count ++) {
				// Try to get generation file
				$genFile = $directory->getFileObject ( 'segments.gen', false );
				
				$format = $genFile->readInt ();
				if ($format != ( int ) 0xFFFFFFFE) {
					throw new \Core\Search\Lucene\Exception ( 'Wrong segments.gen file format' );
				}
				
				$gen1 = $genFile->readLong ();
				$gen2 = $genFile->readLong ();
				
				if ($gen1 == $gen2) {
					return $gen1;
				}
				
				usleep ( self::GENERATION_RETRIEVE_PAUSE * 1000 );
			}
			
			// All passes are failed
			throw new \Core\Search\Lucene\Exception ( 'Index is under processing now' );
		} catch ( \Core\Search\Lucene\Exception $e ) {
			if (strpos ( $e->getMessage (), 'is not readable' ) !== false) {
				try {
					// Try to open old style segments file
					$segmentsFile = $directory->getFileObject ( 'segments', false );
					
					// It's pre-2.1 index
					return 0;
				} catch ( \Core\Search\Lucene\Exception $e ) {
					if (strpos ( $e->getMessage (), 'is not readable' ) !== false) {
						return - 1;
					} else {
						throw $e;
					}
				}
			} else {
				throw $e;
			}
		}
		
		return - 1;
	}
	
	/**
	 * Get segments file name
	 *
	 * @param integer $generation        	
	 * @return string
	 */
	public static function getSegmentFileName($generation) {
		if ($generation == 0) {
			return 'segments';
		}
		
		return 'segments_' . base_convert ( $generation, 10, 36 );
	}
	
	/**
	 * Get index format version
	 *
	 * @return integer
	 */
	public function getFormatVersion() {
		return $this->_formatVersion;
	}
	
	/**
	 * Set index format version.
	 * Index is converted to this format at the nearest upfdate time
	 *
	 * @param int $formatVersion        	
	 * @throws \Core\Search\Lucene\Exception
	 */
	public function setFormatVersion($formatVersion) {
		if ($formatVersion != self::FORMAT_PRE_2_1 && $formatVersion != self::FORMAT_2_1 && $formatVersion != self::FORMAT_2_3) {
			throw new \Core\Search\Lucene\Exception ( 'Unsupported index format' );
		}
		
		$this->_formatVersion = $formatVersion;
	}
	
	/**
	 * Read segments file for pre-2.1 Lucene index format
	 *
	 * @throws \Core\Search\Lucene\Exception
	 */
	private function _readPre21SegmentsFile() {
		$segmentsFile = $this->_directory->getFileObject ( 'segments' );
		
		$format = $segmentsFile->readInt ();
		
		if ($format != ( int ) 0xFFFFFFFF) {
			throw new \Core\Search\Lucene\Exception ( 'Wrong segments file format' );
		}
		
		// read version
		// $segmentsFile->readLong();
		$segmentsFile->readInt ();
		$segmentsFile->readInt ();
		
		// read segment name counter
		$segmentsFile->readInt ();
		
		$segments = $segmentsFile->readInt ();
		
		$this->_docCount = 0;
		
		// read segmentInfos
		for($count = 0; $count < $segments; $count ++) {
			$segName = $segmentsFile->readString ();
			$segSize = $segmentsFile->readInt ();
			$this->_docCount += $segSize;
			
			$this->_segmentInfos [$segName] = new \Core\Search\Lucene\Index\SegmentInfo ( $this->_directory, $segName, $segSize );
		}
		
		// Use 2.1 as a target version. Index will be reorganized at update
		// time.
		$this->_formatVersion = self::FORMAT_2_1;
	}
	
	/**
	 * Read segments file
	 *
	 * @throws \Core\Search\Lucene\Exception
	 */
	private function _readSegmentsFile() {
		$segmentsFile = $this->_directory->getFileObject ( self::getSegmentFileName ( $this->_generation ) );
		
		$format = $segmentsFile->readInt ();
		
		if ($format == ( int ) 0xFFFFFFFC) {
			$this->_formatVersion = self::FORMAT_2_3;
		} else if ($format == ( int ) 0xFFFFFFFD) {
			$this->_formatVersion = self::FORMAT_2_1;
		} else {
			throw new \Core\Search\Lucene\Exception ( 'Unsupported segments file format' );
		}
		
		// read version
		// $segmentsFile->readLong();
		$segmentsFile->readInt ();
		$segmentsFile->readInt ();
		
		// read segment name counter
		$segmentsFile->readInt ();
		
		$segments = $segmentsFile->readInt ();
		
		$this->_docCount = 0;
		
		// read segmentInfos
		for($count = 0; $count < $segments; $count ++) {
			$segName = $segmentsFile->readString ();
			$segSize = $segmentsFile->readInt ();
			
			// 2.1+ specific properties
			// $delGen = $segmentsFile->readLong();
			$delGenHigh = $segmentsFile->readInt ();
			$delGenLow = $segmentsFile->readInt ();
			if ($delGenHigh == ( int ) 0xFFFFFFFF && $delGenLow == ( int ) 0xFFFFFFFF) {
				$delGen = - 1; // There are no deletes
			} else {
				$delGen = ($delGenHigh << 32) | $delGenLow;
			}
			
			if ($this->_formatVersion == self::FORMAT_2_3) {
				$docStoreOffset = $segmentsFile->readInt ();
				
				if ($docStoreOffset != - 1) {
					$docStoreSegment = $segmentsFile->readString ();
					$docStoreIsCompoundFile = $segmentsFile->readByte ();
					
					$docStoreOptions = array (
							'offset' => $docStoreOffset,
							'segment' => $docStoreSegment,
							'isCompound' => ($docStoreIsCompoundFile == 1) 
					);
				} else {
					$docStoreOptions = null;
				}
			} else {
				$docStoreOptions = null;
			}
			
			$hasSingleNormFile = $segmentsFile->readByte ();
			$numField = $segmentsFile->readInt ();
			
			$normGens = array ();
			if ($numField != ( int ) 0xFFFFFFFF) {
				for($count1 = 0; $count1 < $numField; $count1 ++) {
					$normGens [] = $segmentsFile->readLong ();
				}
				
				throw new \Core\Search\Lucene\Exception ( 'Separate norm files are not supported. Optimize index to use it with JO_Search_Lucene.' );
			}
			
			$isCompoundByte = $segmentsFile->readByte ();
			
			if ($isCompoundByte == 0xFF) {
				// The segment is not a compound file
				$isCompound = false;
			} else if ($isCompoundByte == 0x00) {
				// The status is unknown
				$isCompound = null;
			} else if ($isCompoundByte == 0x01) {
				// The segment is a compound file
				$isCompound = true;
			}
			
			$this->_docCount += $segSize;
			
			$this->_segmentInfos [$segName] = new \Core\Search\Lucene\Index\SegmentInfo ( $this->_directory, $segName, $segSize, $delGen, $docStoreOptions, $hasSingleNormFile, $isCompound );
		}
	}
	
	/**
	 * Opens the index.
	 *
	 * IndexReader constructor needs Directory as a parameter. It should be
	 * a string with a path to the index folder or a Directory object.
	 *
	 * @param mixed $directory        	
	 * @throws \Core\Search\Lucene\Exception
	 */
	public function __construct($directory = null, $create = false) {
		if ($directory === null) {
			throw new \Core\Search\Exception ( 'No index directory specified' );
		}
		
		if ($directory instanceof \Core\Search\Lucene\Storage\Directory\Filesystem) {
			$this->_directory = $directory;
			$this->_closeDirOnExit = false;
		} else {
			$this->_directory = new \Core\Search\Lucene\Storage\Directory\Filesystem ( $directory );
			$this->_closeDirOnExit = true;
		}
		
		$this->_segmentInfos = array ();
		
		// Mark index as "under processing" to prevent other processes from
		// premature index cleaning
		\Core\Search\Lucene\LockManager::obtainReadLock ( $this->_directory );
		
		$this->_generation = self::getActualGeneration ( $this->_directory );
		
		if ($create) {
			try {
				\Core\Search\Lucene\LockManager::obtainWriteLock ( $this->_directory );
			} catch ( \Core\Search\Lucene\Exception $e ) {
				\Core\Search\Lucene\LockManager::releaseReadLock ( $this->_directory );
				
				if (strpos ( $e->getMessage (), 'Can\'t obtain exclusive index lock' ) === false) {
					throw $e;
				} else {
					throw new \Core\Search\Lucene\Exception ( 'Can\'t create index. It\'s under processing now' );
				}
			}
			
			if ($this->_generation == - 1) {
				// Directory doesn't contain existing index, start from 1
				$this->_generation = 1;
				$nameCounter = 0;
			} else {
				// Directory contains existing index
				$segmentsFile = $this->_directory->getFileObject ( self::getSegmentFileName ( $this->_generation ) );
				$segmentsFile->seek ( 12 ); // 12 = 4 (int, file format marker) + 8
				                         // (long, index version)
				
				$nameCounter = $segmentsFile->readInt ();
				$this->_generation ++;
			}
			
			\Core\Search\Lucene\Index\Writer::createIndex ( $this->_directory, $this->_generation, $nameCounter );
			
			\Core\Search\Lucene\LockManager::releaseWriteLock ( $this->_directory );
		}
		
		if ($this->_generation == - 1) {
			throw new \Core\Search\Lucene\Exception ( 'Index doesn\'t exists in the specified directory.' );
		} else if ($this->_generation == 0) {
			$this->_readPre21SegmentsFile ();
		} else {
			$this->_readSegmentsFile ();
		}
	}
	
	/**
	 * Close current index and free resources
	 */
	private function _close() {
		if ($this->_closed) {
			// index is already closed and resources are cleaned up
			return;
		}
		
		$this->commit ();
		
		// Release "under processing" flag
		\Core\Search\Lucene\LockManager::releaseReadLock ( $this->_directory );
		
		if ($this->_closeDirOnExit) {
			$this->_directory->close ();
		}
		
		$this->_directory = null;
		$this->_writer = null;
		$this->_segmentInfos = null;
		
		$this->_closed = true;
	}
	
	/**
	 * Add reference to the index object
	 *
	 * @internal
	 *
	 */
	public function addReference() {
		$this->_refCount ++;
	}
	
	/**
	 * Remove reference from the index object
	 *
	 * When reference count becomes zero, index is closed and resources are
	 * cleaned up
	 *
	 * @internal
	 *
	 */
	public function removeReference() {
		$this->_refCount --;
		
		if ($this->_refCount == 0) {
			$this->_close ();
		}
	}
	
	/**
	 * Object destructor
	 */
	public function __destruct() {
		$this->_close ();
	}
	
	/**
	 * Returns an instance of \Core\Search\Lucene\Index\Writer for the index
	 *
	 * @return \Core\Search\Lucene\Index\Writer
	 */
	private function _getIndexWriter() {
		if (! $this->_writer instanceof \Core\Search\Lucene\Index\Writer) {
			$this->_writer = new \Core\Search\Lucene\Index\Writer ( $this->_directory, $this->_segmentInfos, $this->_formatVersion );
		}
		
		return $this->_writer;
	}
	
	/**
	 * Returns the \Core\Search\Lucene\Storage\Directory instance for this
	 * index.
	 *
	 * @return \Core\Search\Lucene\Storage\Directory
	 */
	public function getDirectory() {
		return $this->_directory;
	}
	
	/**
	 * Returns the total number of documents in this index (including deleted
	 * documents).
	 *
	 * @return integer
	 */
	public function count() {
		return $this->_docCount;
	}
	
	/**
	 * Returns one greater than the largest possible document number.
	 * This may be used to, e.g., determine how big to allocate a structure
	 * which will have
	 * an element for every document number in an index.
	 *
	 * @return integer
	 */
	public function maxDoc() {
		return $this->count ();
	}
	
	/**
	 * Returns the total number of non-deleted documents in this index.
	 *
	 * @return integer
	 */
	public function numDocs() {
		$numDocs = 0;
		
		foreach ( $this->_segmentInfos as $segmentInfo ) {
			$numDocs += $segmentInfo->numDocs ();
		}
		
		return $numDocs;
	}
	
	/**
	 * Checks, that document is deleted
	 *
	 * @param integer $id        	
	 * @return boolean
	 * @throws \Core\Search\Lucene\Exception Exception is thrown if $id is out
	 *         of the range
	 */
	public function isDeleted($id) {
		if ($id >= $this->_docCount) {
			throw new \Core\Search\Lucene\Exception ( 'Document id is out of the range.' );
		}
		
		$segmentStartId = 0;
		foreach ( $this->_segmentInfos as $segmentInfo ) {
			if ($segmentStartId + $segmentInfo->count () > $id) {
				break;
			}
			
			$segmentStartId += $segmentInfo->count ();
		}
		
		return $segmentInfo->isDeleted ( $id - $segmentStartId );
	}
	
	/**
	 * Set default search field.
	 *
	 * Null means, that search is performed through all fields by default
	 *
	 * Default value is null
	 *
	 * @param string $fieldName        	
	 */
	public static function setDefaultSearchField($fieldName) {
		self::$_defaultSearchField = $fieldName;
	}
	
	/**
	 * Get default search field.
	 *
	 * Null means, that search is performed through all fields by default
	 *
	 * @return string
	 */
	public static function getDefaultSearchField() {
		return self::$_defaultSearchField;
	}
	
	/**
	 * Set result set limit.
	 *
	 * 0 (default) means no limit
	 *
	 * @param integer $limit        	
	 */
	public static function setResultSetLimit($limit) {
		self::$_resultSetLimit = $limit;
	}
	
	/**
	 * Set result set limit.
	 *
	 * 0 means no limit
	 *
	 * @return integer
	 */
	public static function getResultSetLimit() {
		return self::$_resultSetLimit;
	}
	
	/**
	 * Retrieve index maxBufferedDocs option
	 *
	 * maxBufferedDocs is a minimal number of documents required before
	 * the buffered in-memory documents are written into a new Segment
	 *
	 * Default value is 10
	 *
	 * @return integer
	 */
	public function getMaxBufferedDocs() {
		return $this->_getIndexWriter ()->maxBufferedDocs;
	}
	
	/**
	 * Set index maxBufferedDocs option
	 *
	 * maxBufferedDocs is a minimal number of documents required before
	 * the buffered in-memory documents are written into a new Segment
	 *
	 * Default value is 10
	 *
	 * @param integer $maxBufferedDocs        	
	 */
	public function setMaxBufferedDocs($maxBufferedDocs) {
		$this->_getIndexWriter ()->maxBufferedDocs = $maxBufferedDocs;
	}
	
	/**
	 * Retrieve index maxMergeDocs option
	 *
	 * maxMergeDocs is a largest number of documents ever merged by
	 * addDocument().
	 * Small values (e.g., less than 10,000) are best for interactive indexing,
	 * as this limits the length of pauses while indexing to a few seconds.
	 * Larger values are best for batched indexing and speedier searches.
	 *
	 * Default value is PHP_INT_MAX
	 *
	 * @return integer
	 */
	public function getMaxMergeDocs() {
		return $this->_getIndexWriter ()->maxMergeDocs;
	}
	
	/**
	 * Set index maxMergeDocs option
	 *
	 * maxMergeDocs is a largest number of documents ever merged by
	 * addDocument().
	 * Small values (e.g., less than 10,000) are best for interactive indexing,
	 * as this limits the length of pauses while indexing to a few seconds.
	 * Larger values are best for batched indexing and speedier searches.
	 *
	 * Default value is PHP_INT_MAX
	 *
	 * @param integer $maxMergeDocs        	
	 */
	public function setMaxMergeDocs($maxMergeDocs) {
		$this->_getIndexWriter ()->maxMergeDocs = $maxMergeDocs;
	}
	
	/**
	 * Retrieve index mergeFactor option
	 *
	 * mergeFactor determines how often segment indices are merged by
	 * addDocument().
	 * With smaller values, less RAM is used while indexing,
	 * and searches on unoptimized indices are faster,
	 * but indexing speed is slower.
	 * With larger values, more RAM is used during indexing,
	 * and while searches on unoptimized indices are slower,
	 * indexing is faster.
	 * Thus larger values (> 10) are best for batch index creation,
	 * and smaller values (< 10) for indices that are interactively maintained.
	 *
	 * Default value is 10
	 *
	 * @return integer
	 */
	public function getMergeFactor() {
		return $this->_getIndexWriter ()->mergeFactor;
	}
	
	/**
	 * Set index mergeFactor option
	 *
	 * mergeFactor determines how often segment indices are merged by
	 * addDocument().
	 * With smaller values, less RAM is used while indexing,
	 * and searches on unoptimized indices are faster,
	 * but indexing speed is slower.
	 * With larger values, more RAM is used during indexing,
	 * and while searches on unoptimized indices are slower,
	 * indexing is faster.
	 * Thus larger values (> 10) are best for batch index creation,
	 * and smaller values (< 10) for indices that are interactively maintained.
	 *
	 * Default value is 10
	 *
	 * @param integer $maxMergeDocs        	
	 */
	public function setMergeFactor($mergeFactor) {
		$this->_getIndexWriter ()->mergeFactor = $mergeFactor;
	}
	
	/**
	 * Performs a query against the index and returns an array
	 * of \Core\Search\Lucene\Search\QueryHit objects.
	 * Input is a string or \Core\Search\Lucene\Search\Query.
	 *
	 * @param mixed $query        	
	 * @return array \Core\Search\Lucene\Search\QueryHit
	 * @throws \Core\Search\Lucene\Exception
	 */
	public function find($query) {
		if (is_string ( $query )) {
			$query = \Core\Search\Lucene\Search\QueryParser::parse ( $query );
		}
		
		if (! $query instanceof \Core\Search\Lucene\Search\Query) {
			throw new \Core\Search\Lucene\Exception ( 'Query must be a string or \Core\Search\Lucene\Search\Query object' );
		}
		
		$this->commit ();
		
		$hits = array ();
		$scores = array ();
		$ids = array ();
		
		$query = $query->rewrite ( $this )->optimize ( $this );
		
		$query->execute ( $this );
		
		$topScore = 0;
		
		foreach ( $query->matchedDocs () as $id => $num ) {
			$docScore = $query->score ( $id, $this );
			if ($docScore != 0) {
				$hit = new \Core\Search\Lucene\Search\QueryHit ( $this );
				$hit->id = $id;
				$hit->score = $docScore;
				
				$hits [] = $hit;
				$ids [] = $id;
				$scores [] = $docScore;
				
				if ($docScore > $topScore) {
					$topScore = $docScore;
				}
			}
			
			if (self::$_resultSetLimit != 0 && count ( $hits ) >= self::$_resultSetLimit) {
				break;
			}
		}
		
		if (count ( $hits ) == 0) {
			// skip sorting, which may cause a error on empty index
			return array ();
		}
		
		if ($topScore > 1) {
			foreach ( $hits as $hit ) {
				$hit->score /= $topScore;
			}
		}
		
		if (func_num_args () == 1) {
			// sort by scores
			array_multisort ( $scores, SORT_DESC, SORT_NUMERIC, $ids, SORT_ASC, SORT_NUMERIC, $hits );
		} else {
			// sort by given field names
			
			$argList = func_get_args ();
			$fieldNames = $this->getFieldNames ();
			$sortArgs = array ();
			
			for($count = 1; $count < count ( $argList ); $count ++) {
				$fieldName = $argList [$count];
				
				if (! is_string ( $fieldName )) {
					throw new \Core\Search\Lucene\Exception ( 'Field name must be a string.' );
				}
				
				if (! in_array ( $fieldName, $fieldNames )) {
					throw new \Core\Search\Lucene\Exception ( 'Wrong field name.' );
				}
				
				$valuesArray = array ();
				foreach ( $hits as $hit ) {
					try {
						$value = $hit->getDocument ()->getFieldValue ( $fieldName );
					} catch ( \Core\Search\Lucene\Exception $e ) {
						if (strpos ( $e->getMessage (), 'not found' ) === false) {
							throw $e;
						} else {
							$value = null;
						}
					}
					
					$valuesArray [] = $value;
				}
				
				$sortArgs [] = $valuesArray;
				
				if ($count + 1 < count ( $argList ) && is_integer ( $argList [$count + 1] )) {
					$count ++;
					$sortArgs [] = $argList [$count];
					
					if ($count + 1 < count ( $argList ) && is_integer ( $argList [$count + 1] )) {
						$count ++;
						$sortArgs [] = $argList [$count];
					} else {
						if ($argList [$count] == SORT_ASC || $argList [$count] == SORT_DESC) {
							$sortArgs [] = SORT_REGULAR;
						} else {
							$sortArgs [] = SORT_ASC;
						}
					}
				} else {
					$sortArgs [] = SORT_ASC;
					$sortArgs [] = SORT_REGULAR;
				}
			}
			
			// Sort by id's if values are equal
			$sortArgs [] = $ids;
			$sortArgs [] = SORT_ASC;
			$sortArgs [] = SORT_NUMERIC;
			
			// Array to be sorted
			$sortArgs [] = &$hits;
			
			// Do sort
			call_user_func_array ( 'array_multisort', $sortArgs );
		}
		
		return $hits;
	}
	
	/**
	 * Returns a list of all unique field names that exist in this index.
	 *
	 * @param boolean $indexed        	
	 * @return array
	 */
	public function getFieldNames($indexed = false) {
		$result = array ();
		foreach ( $this->_segmentInfos as $segmentInfo ) {
			$result = array_merge ( $result, $segmentInfo->getFields ( $indexed ) );
		}
		return $result;
	}
	
	/**
	 * Returns a \Core\Search\Lucene\Document object for the document
	 * number $id in this index.
	 *
	 * @param integer|\Core\Search\Lucene\Search\QueryHit $id        	
	 * @return \Core\Search\Lucene\Document
	 */
	public function getDocument($id) {
		if ($id instanceof \Core\Search\Lucene\Search\QueryHit) {
			/* @var $id \Core\Search\Lucene\Search\QueryHit */
			$id = $id->id;
		}
		
		if ($id >= $this->_docCount) {
			throw new \Core\Search\Lucene\Exception ( 'Document id is out of the range.' );
		}
		
		$segmentStartId = 0;
		foreach ( $this->_segmentInfos as $segmentInfo ) {
			if ($segmentStartId + $segmentInfo->count () > $id) {
				break;
			}
			
			$segmentStartId += $segmentInfo->count ();
		}
		
		$fdxFile = $segmentInfo->openCompoundFile ( '.fdx' );
		$fdxFile->seek ( ($id - $segmentStartId) * 8, SEEK_CUR );
		$fieldValuesPosition = $fdxFile->readLong ();
		
		$fdtFile = $segmentInfo->openCompoundFile ( '.fdt' );
		$fdtFile->seek ( $fieldValuesPosition, SEEK_CUR );
		$fieldCount = $fdtFile->readVInt ();
		
		$doc = new \Core\Search\Lucene\Document ();
		for($count = 0; $count < $fieldCount; $count ++) {
			$fieldNum = $fdtFile->readVInt ();
			$bits = $fdtFile->readByte ();
			
			$fieldInfo = $segmentInfo->getField ( $fieldNum );
			
			if (! ($bits & 2)) { // Text data
				$field = new \Core\Search\Lucene\Field ( $fieldInfo->name, $fdtFile->readString (), 'UTF-8', true, $fieldInfo->isIndexed, $bits & 1 );
			} else { // Binary data
				$field = new \Core\Search\Lucene\Field ( $fieldInfo->name, $fdtFile->readBinary (), '', true, $fieldInfo->isIndexed, $bits & 1, true );
			}
			
			$doc->addField ( $field );
		}
		
		return $doc;
	}
	
	/**
	 * Returns true if index contain documents with specified term.
	 *
	 * Is used for query optimization.
	 *
	 * @param \Core\Search\Lucene\Index\Term $term        	
	 * @return boolean
	 */
	public function hasTerm(\Core\Search\Lucene\Index\Term $term) {
		foreach ( $this->_segmentInfos as $segInfo ) {
			if ($segInfo->getTermInfo ( $term ) instanceof \Core\Search\Lucene\Index\TermInfo) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Returns IDs of all the documents containing term.
	 *
	 * @param \Core\Search\Lucene\Index\Term $term        	
	 * @return array
	 */
	public function termDocs(\Core\Search\Lucene\Index\Term $term) {
		$result = array ();
		$segmentStartDocId = 0;
		
		foreach ( $this->_segmentInfos as $segInfo ) {
			$termInfo = $segInfo->getTermInfo ( $term );
			
			if (! $termInfo instanceof \Core\Search\Lucene\Index\TermInfo) {
				$segmentStartDocId += $segInfo->count ();
				continue;
			}
			
			$frqFile = $segInfo->openCompoundFile ( '.frq' );
			$frqFile->seek ( $termInfo->freqPointer, SEEK_CUR );
			$docId = 0;
			for($count = 0; $count < $termInfo->docFreq; $count ++) {
				$docDelta = $frqFile->readVInt ();
				if ($docDelta % 2 == 1) {
					$docId += ($docDelta - 1) / 2;
				} else {
					$docId += $docDelta / 2;
					// read freq
					$frqFile->readVInt ();
				}
				
				$result [] = $segmentStartDocId + $docId;
			}
			
			$segmentStartDocId += $segInfo->count ();
		}
		
		return $result;
	}
	
	/**
	 * Returns an array of all term freqs.
	 * Result array structure: array(docId => freq, ...)
	 *
	 * @param \Core\Search\Lucene\Index\Term $term        	
	 * @return integer
	 */
	public function termFreqs(\Core\Search\Lucene\Index\Term $term) {
		$result = array ();
		$segmentStartDocId = 0;
		foreach ( $this->_segmentInfos as $segmentInfo ) {
			$result += $segmentInfo->termFreqs ( $term, $segmentStartDocId );
			
			$segmentStartDocId += $segmentInfo->count ();
		}
		
		return $result;
	}
	
	/**
	 * Returns an array of all term positions in the documents.
	 * Result array structure: array(docId => array(pos1, pos2, ...), ...)
	 *
	 * @param \Core\Search\Lucene\Index\Term $term        	
	 * @return array
	 */
	public function termPositions(\Core\Search\Lucene\Index\Term $term) {
		$result = array ();
		$segmentStartDocId = 0;
		foreach ( $this->_segmentInfos as $segmentInfo ) {
			$result += $segmentInfo->termPositions ( $term, $segmentStartDocId );
			
			$segmentStartDocId += $segmentInfo->count ();
		}
		
		return $result;
	}
	
	/**
	 * Returns the number of documents in this index containing the $term.
	 *
	 * @param \Core\Search\Lucene\Index\Term $term        	
	 * @return integer
	 */
	public function docFreq(\Core\Search\Lucene\Index\Term $term) {
		$result = 0;
		foreach ( $this->_segmentInfos as $segInfo ) {
			$termInfo = $segInfo->getTermInfo ( $term );
			if ($termInfo !== null) {
				$result += $termInfo->docFreq;
			}
		}
		
		return $result;
	}
	
	/**
	 * Retrive similarity used by index reader
	 *
	 * @return \Core\Search\Lucene\Search\Similarity
	 */
	public function getSimilarity() {
		return \Core\Search\Lucene\Search\Similarity::getDefault ();
	}
	
	/**
	 * Returns a normalization factor for "field, document" pair.
	 *
	 * @param integer $id        	
	 * @param string $fieldName        	
	 * @return float
	 */
	public function norm($id, $fieldName) {
		if ($id >= $this->_docCount) {
			return null;
		}
		
		$segmentStartId = 0;
		foreach ( $this->_segmentInfos as $segInfo ) {
			if ($segmentStartId + $segInfo->count () > $id) {
				break;
			}
			
			$segmentStartId += $segInfo->count ();
		}
		
		if ($segInfo->isDeleted ( $id - $segmentStartId )) {
			return 0;
		}
		
		return $segInfo->norm ( $id - $segmentStartId, $fieldName );
	}
	
	/**
	 * Returns true if any documents have been deleted from this index.
	 *
	 * @return boolean
	 */
	public function hasDeletions() {
		foreach ( $this->_segmentInfos as $segmentInfo ) {
			if ($segmentInfo->hasDeletions ()) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Deletes a document from the index.
	 * $id is an internal document id
	 *
	 * @param integer|\Core\Search\Lucene\Search\QueryHit $id        	
	 * @throws \Core\Search\Lucene\Exception
	 */
	public function delete($id) {
		if ($id instanceof \Core\Search\Lucene\Search\QueryHit) {
			/* @var $id \Core\Search\Lucene\Search\QueryHit */
			$id = $id->id;
		}
		
		if ($id >= $this->_docCount) {
			throw new \Core\Search\Lucene\Exception ( 'Document id is out of the range.' );
		}
		
		$segmentStartId = 0;
		foreach ( $this->_segmentInfos as $segmentInfo ) {
			if ($segmentStartId + $segmentInfo->count () > $id) {
				break;
			}
			
			$segmentStartId += $segmentInfo->count ();
		}
		$segmentInfo->delete ( $id - $segmentStartId );
		
		$this->_hasChanges = true;
	}
	
	/**
	 * Adds a document to this index.
	 *
	 * @param \Core\Search\Lucene\Document $document        	
	 */
	public function addDocument(\Core\Search\Lucene\Document $document) {
		$this->_getIndexWriter ()->addDocument ( $document );
		$this->_docCount ++;
		
		$this->_hasChanges = true;
	}
	
	/**
	 * Update document counter
	 */
	private function _updateDocCount() {
		$this->_docCount = 0;
		foreach ( $this->_segmentInfos as $segInfo ) {
			$this->_docCount += $segInfo->count ();
		}
	}
	
	/**
	 * Commit changes resulting from delete() or undeleteAll() operations.
	 *
	 * @todo undeleteAll processing.
	 */
	public function commit() {
		if ($this->_hasChanges) {
			$this->_getIndexWriter ()->commit ();
			
			$this->_updateDocCount ();
			
			$this->_hasChanges = false;
		}
	}
	
	/**
	 * Optimize index.
	 *
	 * Merges all segments into one
	 */
	public function optimize() {
		// Commit changes if any changes have been made
		$this->commit ();
		
		if (count ( $this->_segmentInfos ) > 1 || $this->hasDeletions ()) {
			$this->_getIndexWriter ()->optimize ();
			$this->_updateDocCount ();
		}
	}
	
	/**
	 * Returns an array of all terms in this index.
	 *
	 * @return array
	 */
	public function terms() {
		$result = array ();
		
		$segmentInfoQueue = new \Core\Search\Lucene\Index\SegmentInfoPriorityQueue ();
		
		foreach ( $this->_segmentInfos as $segmentInfo ) {
			$segmentInfo->reset ();
			
			// Skip "empty" segments
			if ($segmentInfo->currentTerm () !== null) {
				$segmentInfoQueue->put ( $segmentInfo );
			}
		}
		
		while ( ($segmentInfo = $segmentInfoQueue->pop ()) !== null ) {
			if ($segmentInfoQueue->top () === null || $segmentInfoQueue->top ()->currentTerm ()->key () != $segmentInfo->currentTerm ()->key ()) {
				// We got new term
				$result [] = $segmentInfo->currentTerm ();
			}
			
			if ($segmentInfo->nextTerm () !== null) {
				// Put segment back into the priority queue
				$segmentInfoQueue->put ( $segmentInfo );
			}
		}
		
		return $result;
	}
	
	/**
	 * Terms stream queue
	 *
	 * @var \Core\Search\Lucene\Index\SegmentInfoPriorityQueue
	 */
	private $_termsStreamQueue = null;
	
	/**
	 * Last Term in a terms stream
	 *
	 * @var \Core\Search\Lucene\Index\Term
	 */
	private $_lastTerm = null;
	
	/**
	 * Reset terms stream.
	 */
	public function resetTermsStream() {
		$this->_termsStreamQueue = new \Core\Search\Lucene\Index\SegmentInfoPriorityQueue ();
		
		foreach ( $this->_segmentInfos as $segmentInfo ) {
			$segmentInfo->reset ();
			
			// Skip "empty" segments
			if ($segmentInfo->currentTerm () !== null) {
				$this->_termsStreamQueue->put ( $segmentInfo );
			}
		}
		
		$this->nextTerm ();
	}
	
	/**
	 * Skip terms stream up to specified term preffix.
	 *
	 * Prefix contains fully specified field info and portion of searched term
	 *
	 * @param \Core\Search\Lucene\Index\Term $prefix        	
	 */
	public function skipTo(\Core\Search\Lucene\Index\Term $prefix) {
		$segments = array ();
		
		while ( ($segmentInfo = $this->_termsStreamQueue->pop ()) !== null ) {
			$segments [] = $segmentInfo;
		}
		
		foreach ( $segments as $segmentInfo ) {
			$segmentInfo->skipTo ( $prefix );
			
			if ($segmentInfo->currentTerm () !== null) {
				$this->_termsStreamQueue->put ( $segmentInfo );
			}
		}
		
		$this->nextTerm ();
	}
	
	/**
	 * Scans terms dictionary and returns next term
	 *
	 * @return \Core\Search\Lucene\Index\Term null
	 */
	public function nextTerm() {
		while ( ($segmentInfo = $this->_termsStreamQueue->pop ()) !== null ) {
			if ($this->_termsStreamQueue->top () === null || $this->_termsStreamQueue->top ()->currentTerm ()->key () != $segmentInfo->currentTerm ()->key ()) {
				// We got new term
				$this->_lastTerm = $segmentInfo->currentTerm ();
				
				if ($segmentInfo->nextTerm () !== null) {
					// Put segment back into the priority queue
					$this->_termsStreamQueue->put ( $segmentInfo );
				}
				
				return $this->_lastTerm;
			}
			
			if ($segmentInfo->nextTerm () !== null) {
				// Put segment back into the priority queue
				$this->_termsStreamQueue->put ( $segmentInfo );
			}
		}
		
		// End of stream
		$this->_lastTerm = null;
		
		return null;
	}
	
	/**
	 * Returns term in current position
	 *
	 * @return \Core\Search\Lucene\Index\Term null
	 */
	public function currentTerm() {
		return $this->_lastTerm;
	}
	
	/**
	 * Close terms stream
	 *
	 * Should be used for resources clean up if stream is not read up to the end
	 */
	public function closeTermsStream() {
		while ( ($segmentInfo = $this->_termsStreamQueue->pop ()) !== null ) {
			$segmentInfo->closeTermsStream ();
		}
		
		$this->_termsStreamQueue = null;
		$this->_lastTerm = null;
	}
	
	/**
	 * ***********************************************************************
	 * 
	 * @todo UNIMPLEMENTED
	 *      
	 *       ***********************************************************************
	 */
	/**
	 * Undeletes all documents currently marked as deleted in this index.
	 *
	 * @todo Implementation
	 */
	public function undeleteAll() {
	}
}
