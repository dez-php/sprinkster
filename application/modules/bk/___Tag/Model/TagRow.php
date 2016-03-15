<?php

namespace Tag;

class TagRow extends \Core\Db\Table\Row {

	/**
	 * Allows pre-insert logic to be applied to row.
	 * Subclasses may override this method.
	 *
	 * @return void
	 */
	protected function _insert()
	{
		$this->letter_id = TagLetter::getByTag($this->tag);
	}

	/**
	 * Allows pre-update logic to be applied to row.
	 * Subclasses may override this method.
	 *
	 * @return void
	 */
	protected function _update()
	{
		$this->_insert();
	}
}