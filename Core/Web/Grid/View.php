<?php

namespace Core\Web\Grid;

class View extends \Core\Web\BaseListView {

	const FILTER_POS_HEADER='header';
	const FILTER_POS_FOOTER='footer';
	const FILTER_POS_BODY='body';
	
	private $_formatter;
	
	public $columns=array();
	
	public $rowCssClass=array('odd','even');
	
	public $rowCssClassExpression;
	
	public $rowHtmlOptionsExpression;
	
	public $showTableOnEmpty=true;
	
	public $ajaxUpdate;
	
	public $updateSelector='{page}, {sort}';
	
	public $ajaxUpdateError;
	
	public $ajaxVar='ajax';
	
	public $ajaxUrl;
	
	public $ajaxType;
	
	public $beforeAjaxUpdate;
	
	public $afterAjaxUpdate;
	
	public $selectionChanged;
	
	public $selectableRows=1;
	
	public $baseScriptUrl;
	
	public $cssFile;
	
	public $nullDisplay='&nbsp;';
	
	public $blankDisplay='&nbsp;';
	
	public $loadingCssClass='grid-view-loading';
	
	public $filterSelector='{filter}';
	
	public $filterCssClass='filters';
	
	public $filterPosition='body';
	
	public $filter;
	
	public $hideHeader=false;
	
	public $enableHistory=false;
	
	public function init()
	{
		//parent::init();
	
		if(empty($this->updateSelector)) {
			throw new \Core\Exception('The property updateSelector should be defined.');
		}
		if(empty($this->filterSelector)) {
			throw new \Core\Exception('The property filterSelector should be defined.');
		}
		
		if(!isset($this->htmlOptions['class'])) {
			$this->htmlOptions['class']='grid-view';
		}
		
		if($this->baseScriptUrl===null) {
			//$this->baseScriptUrl=\Core\Base\Action::getComponent('AssetManager')->publish(\Core\Alias::getPathOfAlias('Fmx.Widgets.Grid.gridview'));
		}
	
		if($this->cssFile!==false)
		{
			if($this->cssFile===null) {
				$this->cssFile=$this->baseScriptUrl.'/styles.css';
			}
			//\Core\Front::getComponent('document')->addCssFile($this->cssFile);
		}
	
		$this->initColumns();
	}
	
	/**
	 * Creates column objects and initializes them.
	 */
	protected function initColumns()
	{
		if($this->columns===array())
		{
			if($this->dataProvider instanceof \Core\Db\Table\AbstractTable) {
				$this->columns=$this->dataProvider->info('cols');
			} 
		}
		$id=$this->getId();
		foreach($this->columns as $i=>$column)
		{
			if(is_string($column))
				$column=$this->createDataColumn($column);
			else
			{
				if(!isset($column['class'])) {
					$column['class']='\Core\Web\Grid\DataColumn';
				}
				$column=\Core\Base\Action::setComponent($column, $this);
			}
			if(!$column->visible)
			{
				unset($this->columns[$i]);
				continue;
			}
			if($column->id===null)
				$column->id=$id.'_c'.$i;
			$this->columns[$i]=$column;
		}
	
		foreach($this->columns as $column) {
			$column->init();
		}
	}

	/**
	 * Creates a {@link CDataColumn} based on a shortcut column specification string.
	 * @param string $text the column specification string
	 * @return CDataColumn the column instance
	 */
	protected function createDataColumn($text)
	{
		if(!preg_match('/^([\w\.]+)(:(\w*))?(:(.*))?$/',$text,$matches))
			throw new \Core\Exception('The column must be specified in the format of "Name:Type:Label", where "Type" and "Label" are optional.');
		$column=new DataColumn($this);
		$column->name=$matches[1];
		if(isset($matches[3]) && $matches[3]!=='')
			$column->type=$matches[3];
		if(isset($matches[5]))
			$column->header=$matches[5];
		return $column;
	}

	/**
	 * Registers necessary client scripts.
	 */
	public function registerClientScript()
	{
		$id=$this->getId();

		if($this->ajaxUpdate===false)
			$ajaxUpdate=false;
		else
			$ajaxUpdate=array_unique(preg_split('/\s*,\s*/',$this->ajaxUpdate.','.$id,-1,PREG_SPLIT_NO_EMPTY));
		$options=array(
			'ajaxUpdate'=>$ajaxUpdate,
			'ajaxVar'=>$this->ajaxVar,
			'pagerClass'=>$this->pagerCssClass,
			'loadingClass'=>$this->loadingCssClass,
			'filterClass'=>$this->filterCssClass,
			'tableClass'=>$this->itemsCssClass,
			'selectableRows'=>$this->selectableRows,
			'enableHistory'=>$this->enableHistory,
			'updateSelector'=>$this->updateSelector,
			'filterSelector'=>$this->filterSelector
		);
		if($this->ajaxUrl!==null)
			$options['url']=\Core\Html::normalizeUrl($this->ajaxUrl);
		if($this->ajaxType!==null)
			$options['ajaxType']=strtoupper($this->ajaxType);
		if($this->enablePagination)
			$options['pageVar']=$this->dataProvider->getPagination()->pageVar;
		foreach(array('beforeAjaxUpdate', 'afterAjaxUpdate', 'ajaxUpdateError', 'selectionChanged') as $event)
		{
			if($this->$event!==null)
			{
				if($this->$event instanceof \FM\Encoders\JavaScriptExpression)
					$options[$event]=$this->$event;
				else
					$options[$event]=new \FM\Encoders\JavaScriptExpression($this->$event);
			}
		}

		$options=\FM\Encoders\Javascript::encode($options);
		$cs=\FM\Front::getComponent('document');
		$cs->addCoreScript('jquery');
		$cs->addCoreScript('bbq');
		if($this->enableHistory)
			$cs->addCoreScript('history');
		$cs->addScriptFile($this->baseScriptUrl.'/jquery.jfggridview.js',\FM\Document::POS_END);
		$cs->addScript(__CLASS__.'#'.$id,"jQuery('#$id').jfgGridView($options);");
	}

	/**
	 * Renders the data items for the grid view.
	 */
	public function renderItems()
	{
		if($this->dataProvider->countBy()>0 || $this->showTableOnEmpty)
		{
			echo "<table class=\"{$this->itemsCssClass}\">\n";
			$this->renderTableHeader();
			ob_start();
			$this->renderTableBody();
			$body=ob_get_clean();
			$this->renderTableFooter();
			echo $body; // TFOOT must appear before TBODY according to the standard.
			echo "</table>";
		}
		else
			$this->renderEmptyText();
	}

	/**
	 * Renders the table header.
	 */
	public function renderTableHeader()
	{
		if(!$this->hideHeader)
		{
			echo "<thead>\n";

			if($this->filterPosition===self::FILTER_POS_HEADER)
				$this->renderFilter();

			echo "<tr>\n";
			foreach($this->columns as $column)
				$column->renderHeaderCell();
			echo "</tr>\n";

			if($this->filterPosition===self::FILTER_POS_BODY)
				$this->renderFilter();

			echo "</thead>\n";
		}
		elseif($this->filter!==null && ($this->filterPosition===self::FILTER_POS_HEADER || $this->filterPosition===self::FILTER_POS_BODY))
		{
			echo "<thead>\n";
			$this->renderFilter();
			echo "</thead>\n";
		}
	}

	/**
	 * Renders the filter.
	 * @since 1.1.1
	 */
	public function renderFilter()
	{
		if($this->filter!==null)
		{
			echo "<tr class=\"{$this->filterCssClass}\">\n";
			foreach($this->columns as $column)
				$column->renderFilterCell();
			echo "</tr>\n";
		}
	}

	/**
	 * Renders the table footer.
	 */
	public function renderTableFooter()
	{
		$hasFilter=$this->filter!==null && $this->filterPosition===self::FILTER_POS_FOOTER;
		$hasFooter=$this->getHasFooter();
		if($hasFilter || $hasFooter)
		{
			echo "<tfoot>\n";
			if($hasFooter)
			{
				echo "<tr>\n";
				foreach($this->columns as $column)
					$column->renderFooterCell();
				echo "</tr>\n";
			}
			if($hasFilter)
				$this->renderFilter();
			echo "</tfoot>\n";
		}
	}

	/**
	 * Renders the table body.
	 */
	public function renderTableBody()
	{
		$data=$this->dataProvider->fetchAll(null,null,100);
		$n=$data->count();
		echo "<tbody>\n";

		if($n>0)
		{
			for($row=0;$row<$n;++$row)
				$this->renderTableRow($row, $data);
		}
		else
		{
			echo '<tr><td colspan="'.count($this->columns).'" class="empty">';
			$this->renderEmptyText();
			echo "</td></tr>\n";
		}
		echo "</tbody>\n";
	}

	/**
	 * Renders a table body row.
	 * @param integer $row the row number (zero-based).
	 */
	public function renderTableRow($row, $data)
	{
		$htmlOptions=array();
		if($this->rowHtmlOptionsExpression!==null)
		{
			$data=$this->dataProvider->data[$row];
			$options=$this->evaluateExpression($this->rowHtmlOptionsExpression,array('row'=>$row,'data'=>$data));
			if(is_array($options))
				$htmlOptions = $options;
		}

		if($this->rowCssClassExpression!==null)
		{
			$data=$this->dataProvider->data[$row];
			$class=$this->evaluateExpression($this->rowCssClassExpression,array('row'=>$row,'data'=>$data));
		}
		elseif(is_array($this->rowCssClass) && ($n=count($this->rowCssClass))>0)
			$class=$this->rowCssClass[$row%$n];

		if(!empty($class))
		{
			if(isset($htmlOptions['class']))
				$htmlOptions['class'].=' '.$class;
			else
				$htmlOptions['class']=$class;
		}

		echo \Core\Html::openTag('tr', $htmlOptions)."\n";
		foreach($this->columns as $column) {
			$column->renderDataCell($row, $data);
		}
		echo "</tr>\n";
	}

	/**
	 * @return boolean whether the table should render a footer.
	 * This is true if any of the {@link columns} has a true {@link CGridColumn::hasFooter} value.
	 */
	public function getHasFooter()
	{
		foreach($this->columns as $column)
			if($column->getHasFooter())
				return true;
		return false;
	}

	/**
	 * @return CFormatter the formatter instance. Defaults to the 'format' application component.
	 */
	public function getFormatter()
	{
		//if($this->_formatter===null)
			//$this->_formatter=jfg::app()->format;
		return $this->_formatter;
	}

	/**
	 * @param CFormatter $value the formatter instance
	 */
	public function setFormatter($value)
	{
		$this->_formatter=$value;
	}
	
}