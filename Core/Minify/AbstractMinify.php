<?php

namespace Core\Minify;

abstract class AbstractMinify {
	
	/**
	 * The data to be minified
	 *
	 * @var array
	 */
	protected $data = array ();
	
	/**
	 * Init the minify class - optionally, css may be passed along already.
	 *
	 * @param string[optional] $css        	
	 */
	public function __construct() {
		// it's possible to add the css through the constructor as well ;)
		$arguments = func_get_args ();
		if (func_num_args ())
			call_user_func_array ( array (
					$this,
					'add' 
			), $arguments );
	}
	
	/**
	 * Add a file or straight-up code to be minified.
	 *
	 * @param string $data        	
	 */
	public function add($data) {
		// this method can be overloaded
		foreach ( func_get_args () as $data ) {
			// redefine var
			$data = ( string ) $data;
			
			// load data
			$value = $this->load ( $data );
			$key = ($data != $value) ? $data : 0;
			
			// initialize key
			if (! array_key_exists ( $key, $this->data ))
				$this->data [$key] = '';
				
				// store data
			$this->data [$key] .= $value;
		}
	}
	
	/**
	 * Load data.
	 *
	 * @param string $data
	 *        	Either a path to a file or the content itself.
	 * @return string
	 */
	protected function load($data) {
		// check if the data is a file
		if (@file_exists ( $data ) && is_file ( $data )) {
			// grab content
			return @file_get_contents ( $data );
		} 		

		// no file, just return the data itself
		else
			return $data;
	}
	
	/**
	 * Save to file
	 *
	 * @param string $content
	 *        	The minified data.
	 * @param string $path
	 *        	The path to save the minified data to.
	 */
	public function save($content, $path) {
		if (@file_put_contents ( $path, $content ) === false)
			throw new \Core\Minify\Exception ( 'The file "' . $path . '" could not be opened. Check if PHP has enough permissions.' );
		
		try {
			$gzdata = gzencode ( $content, \Core\Http\Response::getInstance ()->getLevel () );
			file_put_contents ( $path . '.gz', $gzdata );
		} catch ( \Core\Exception $e ) {
		}
	}
}