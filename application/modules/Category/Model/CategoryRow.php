<?php

namespace Category;

class CategoryRow extends \Core\Db\Table\Row {
	
	protected $_temporary = array();
	
	/**
	 * Retrieve row field value
	 *
	 * @param string $columnName
	 *        	The user-specified column name.
	 * @return string The corresponding column value.
	 * @throws \Core\Db\Table\Row\Exception if the $columnName is not a column
	 *         in the row.
	 */
	public function __get($columnName) {
		$columnName = $this->_transformColumn ( $columnName );
		if (! array_key_exists ( $columnName, $this->_data )) {
			if (! array_key_exists ( $columnName, $this->_temporary )) {
				require_once 'Db/Table/Row/Exception.php';
				throw new \Core\Db\Table\Row\Exception ( "Specified column \"$columnName\" is not in the row" );
			}
			return $this->_temporary[$columnName];
		}
		return $this->_data [$columnName];
	}
	
	/**
	 * Set row field value
	 *
	 * @param string $columnName
	 *        	The column key.
	 * @param mixed $value
	 *        	The value for the property.
	 * @return void
	 * @throws \Core\Db\Table\Row\Exception
	 */
	public function __set($columnName, $value) {
		$columnName = $this->_transformColumn ( $columnName );
		if (! array_key_exists ( $columnName, $this->_data )) {
			$this->_temporary[$columnName] = $value;
		} else {
			$this->_data [$columnName] = $value;
			$this->_modifiedFields [$columnName] = true;
		}
	}
	
	/**
	 * Unset row field value
	 *
	 * @param string $columnName
	 *        	The column key.
	 * @return \Core\Db\Table\Row\AbstractRow
	 * @throws \Core\Db\Table\Row\Exception
	 */
	public function __unset($columnName) {
		$columnName = $this->_transformColumn ( $columnName );
		if (! array_key_exists ( $columnName, $this->_data )) {
			if (! array_key_exists ( $columnName, $this->_temporary )) {
				require_once 'Db/Table/Row/Exception.php';
				throw new \Core\Db\Table\Row\Exception ( "Specified column \"$columnName\" is not in the row" );
			}
			unset ( $this->_temporary [$columnName] );
			return $this;
		}
		if ($this->isConnected () && in_array ( $columnName, $this->_table->info ( 'primary' ) )) {
			require_once 'Db/Table/Row/Exception.php';
			throw new \Core\Db\Table\Row\Exception ( "Specified column \"$columnName\" is a primary key and should not be unset" );
		}
		unset ( $this->_data [$columnName] );
		return $this;
	}
	
	/**
	 * Test existence of row field
	 *
	 * @param string $columnName
	 *        	The column key.
	 * @return boolean
	 */
	public function __isset($columnName) {
		$columnName = $this->_transformColumn ( $columnName );
		return array_key_exists ( $columnName, $this->_data ) ? true : array_key_exists ( $columnName, $this->_temporary );
	}
	
    /**
     * Allows pre-insert logic to be applied to row.
     * Subclasses may override this method.
     *
     * @return void
     */
    protected function _insert()
    {
    	$this->date_added = \Core\Date::getInstance(null,\Core\Date::SQL_FULL, true)->toString();
    	$this->date_modified = $this->date_added;
    }

    /**
     * Allows pre-update logic to be applied to row.
     * Subclasses may override this method.
     *
     * @return void
     */
    protected function _update()
    {
    	$this->date_modified = \Core\Date::getInstance(null,\Core\Date::SQL_FULL, true)->toString();
    }

    /**
     * Allows post-delete logic to be applied to row.
     * Subclasses may override this method.
     *
     * @return void
     */
    protected function _postDelete()
    {
    	$categoryDescriptionTable = new \Category\CategoryDescription();
    	$categoryDescriptionTable->delete($categoryDescriptionTable->makeWhere(array('category_id' => $this->id)));
    }
    
    /////
    
    public function getImage($width = 0, $height = 0, $scaled = true, $crop = false, $method = 'resize') {
    	static $imageLib = null, $no_image = null;
    	if(is_null($imageLib))
    		$imageLib = new \Local\Library\Live();
    	if(is_null($no_image))
    		$no_image = \Base\Config::get('no_image');
    	$image = null;
    	if($this->image)
    		return $imageLib->$method( 'data/' . $this->image , $width, $height, $scaled, $crop );
    	if( ($pin = $this->Pin()) !== null ) 
    		return \Pin\Helper\Image::getImage('small', $pin)->image;
    	if($no_image)
    		return $imageLib->$method( 'data/' . $no_image , $width, $height, $scaled, $crop );
    	return null;
    }
	
}