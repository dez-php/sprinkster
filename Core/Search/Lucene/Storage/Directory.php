<?php

namespace Core\Search\Lucene\Storage;

abstract class Directory {
	
	/**
	 * Closes the store.
	 *
	 * @return void
	 */
	abstract public function close();
	
	/**
	 * Returns an array of strings, one for each file in the directory.
	 *
	 * @return array
	 */
	abstract public function fileList();
	
	/**
	 * Creates a new, empty file in the directory with the given $filename.
	 *
	 * @param string $filename        	
	 * @return \Core\Search\Lucene\Storage\File
	 */
	abstract public function createFile($filename);
	
	/**
	 * Removes an existing $filename in the directory.
	 *
	 * @param string $filename        	
	 * @return void
	 */
	abstract public function deleteFile($filename);
	
	/**
	 * Purge file if it's cached by directory object
	 *
	 * Method is used to prevent 'too many open files' error
	 *
	 * @param string $filename        	
	 * @return void
	 */
	abstract public function purgeFile($filename);
	
	/**
	 * Returns true if a file with the given $filename exists.
	 *
	 * @param string $filename        	
	 * @return boolean
	 */
	abstract public function fileExists($filename);
	
	/**
	 * Returns the length of a $filename in the directory.
	 *
	 * @param string $filename        	
	 * @return integer
	 */
	abstract public function fileLength($filename);
	
	/**
	 * Returns the UNIX timestamp $filename was last modified.
	 *
	 * @param string $filename        	
	 * @return integer
	 */
	abstract public function fileModified($filename);
	
	/**
	 * Renames an existing file in the directory.
	 *
	 * @param string $from        	
	 * @param string $to        	
	 * @return void
	 */
	abstract public function renameFile($from, $to);
	
	/**
	 * Sets the modified time of $filename to now.
	 *
	 * @param string $filename        	
	 * @return void
	 */
	abstract public function touchFile($filename);
	
	/**
	 * Returns a \Core\Search\Lucene\Storage\File object for a given $filename
	 * in the directory.
	 *
	 * If $shareHandler option is true, then file handler can be shared between
	 * File Object
	 * requests. It speed-ups performance, but makes problems with file
	 * position.
	 * Shared handler are good for short atomic requests.
	 * Non-shared handlers are useful for stream file reading (especial for
	 * compound files).
	 *
	 * @param string $filename        	
	 * @param boolean $shareHandler        	
	 * @return \Core\Search\Lucene\Storage\File
	 */
	abstract public function getFileObject($filename, $shareHandler = true);
}

