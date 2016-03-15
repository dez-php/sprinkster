<?php

namespace Widget\Grid\Db2;

class DataColumn extends GridColumn {

	/**
	 * @var string the attribute name of the data model. Used for column sorting, filtering and to render the corresponding
	 * attribute value in each data cell. If {@link value} is specified it will be used to rendered the data cell instead of the attribute value.
	 * @see value
	 * @see sortable
	 */
	public $name;
	/**
	 * @var string a PHP expression that will be evaluated for every data cell using {@link evaluateExpression} and whose result will be rendered
	 * as the content of the data cell.
	 * In this expression, you can use the following variables:
	 * <ul>
	 *   <li><code>$row</code> the row number (zero-based).</li>
	 *   <li><code>$data</code> the data model for the row.</li>
	 * 	 <li><code>$this</code> the column object.</li>
	 * </ul>
	 * A PHP expression can be any PHP code that has a value. To learn more about what an expression is,
	 * please refer to the {@link http://www.php.net/manual/en/language.expressions.php php manual}.
	 */
	public $value;
	/**
	 * @var string the type of the attribute value. This determines how the attribute value is formatted for display.
	 * Valid values include those recognizable by {@link CGridView::formatter}, such as: raw, text, ntext, html, date, time,
	 * datetime, boolean, number, email, image, url. For more details, please refer to {@link CFormatter}.
	 * Defaults to 'text' which means the attribute value will be HTML-encoded.
	 */
	public $type='text';
	/**
	 * @var boolean whether the column is sortable. If so, the header cell will contain a link that may trigger the sorting.
	 * Defaults to true. Note that if {@link name} is not set, or if {@link name} is not allowed by {@link CSort},
	 * this property will be treated as false.
	 * @see name
	 */
	public $sortable=true;
	/**
	 * @var mixed the HTML code representing a filter input (eg a text field, a dropdown list)
	 * that is used for this data column. This property is effective only when
	 * {@link CGridView::filter} is set.
	 * If this property is not set, a text field will be generated as the filter input;
	 * If this property is an array, a dropdown list will be generated that uses this property value as
	 * the list options.
	 * If you don't want a filter for this data column, set this value to false.
	 * @since 1.1.1
	 */
	public $filter;
	public $atributes;
	public $label;
	
	/**
	 * Initializes the column.
	 */
	public function init()
	{
		parent::init();
		if($this->name===null)
			$this->sortable=false;
		if($this->name===null && $this->value===null)
			throw new \Core\Exception('Either "name" or "value" must be specified for \Core\Fmx\Widgets\Grid\DataColumn.');
	}
	
	/**
	 * Renders the filter cell content.
	 * This method will render the {@link filter} as is if it is a string.
	 * If {@link filter} is an array, it is assumed to be a list of options, and a dropdown selector will be rendered.
	 * Otherwise if {@link filter} is not false, a text field is rendered.
	 * @since 1.1.1
	 */
	protected function renderFilterCellContent()
	{
		if(is_string($this->filter))
			echo $this->filter;
		elseif($this->filter!==false && $this->grid->filter!==null && $this->name!==null && strpos($this->name,'.')===false)
		{
			if(is_array($this->filter))
				echo \Core\Html::activeDropDownList($this->grid->filter, $this->name, $this->filter, array('id'=>false,'prompt'=>''));
			elseif($this->filter===null)
			echo \Core\Html::activeTextField($this->grid->filter, $this->name, array('id'=>false));
		}
		else
			parent::renderFilterCellContent();
	}
	
	/**
	 * Renders the header cell content.
	 * This method will render a link that can trigger the sorting if the column is sortable.
	 */
	protected function renderHeaderCellContent()
	{
		if($this->grid->enableSorting && $this->sortable && $this->name!==null)
			echo $this->grid->dataProvider->getSort()->link($this->name,$this->header,array('class'=>'sort-link'));
		elseif($this->name!==null && $this->header===null)
		{
			if($this->grid->dataProvider instanceof \Core\DataProvider\ActiveDataProvider)
				echo \Core\Html::encode($this->grid->dataProvider->model->getAttributeLabel($this->name));
			else
				echo \Core\Html::encode($this->name);
		}
		else
			parent::renderHeaderCellContent();
	}
	
	/**
	 * Renders the data cell content.
	 * This method evaluates {@link value} or {@link name} and renders the result.
	 * @param integer $row the row number (zero-based)
	 * @param mixed $data the data associated with the row
	 */
	protected function renderDataCellContent($row,$data)
	{
		if($this->value!==null)
			$value=$this->evaluateExpression($this->value,array('data'=>$data,'row'=>$row));
		elseif($this->name!==null)
		$value=\Core\Html::value($data,$this->name);
		echo $value===null ? $this->grid->nullDisplay : \Core\Base\Action::getComponent('format')->format($value,$this->type);
	}
	
}