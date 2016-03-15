<?php

namespace Core\Web;

abstract class BaseListView extends \Core\Base\Action {

	public $dataProvider;
	/**
	 * @var string the tag name for the view container. Defaults to 'div'.
	 */
	public $tagName='div';
	/**
	 * @var array the HTML options for the view container tag.
	 */
	public $htmlOptions=array();
	/**
	 * @var boolean whether to enable sorting. Note that if the {@link IDataProvider::sort} property
	 * of {@link dataProvider} is false, this will be treated as false as well. When sorting is enabled,
	 * sortable columns will have their headers clickable to trigger sorting along that column.
	 * Defaults to true.
	 * @see sortableAttributes
	*/
	public $enableSorting=true;
	/**
	 * @var boolean whether to enable pagination. Note that if the {@link IDataProvider::pagination} property
	 * of {@link dataProvider} is false, this will be treated as false as well. When pagination is enabled,
	 * a pager will be displayed in the view so that it can trigger pagination of the data display.
	 * Defaults to true.
	 */
	public $enablePagination=true;
	/**
	 * @var array|string the configuration for the pager. Defaults to <code>array('class'=>'\FM\Fmx\Widgets\Pager\Link')</code>.
	 * String value will be treated as the class name of the pager (<code>'ClassName'</code> value is similar
	 * to the <code>array('class'=>'ClassName')</code> value). See {@link CBasePager} and {@link \FM\Fmx\Widgets\Pager\Link}
	 * for more details about pager configuration array values.
	 * @see enablePagination
	 */
	public $pager=array('class'=>'\Core\Web\Pager\Link');
	/**
	 * @var string the template to be used to control the layout of various sections in the view.
	 * These tokens are recognized: {summary}, {items} and {pager}. They will be replaced with the
	 * summary text, the items, and the pager.
	*/
	public $template="{summary}\n{items}\n{pager}";
	/**
	 * @var string the summary text template for the view. These tokens are recognized and will be replaced
	 * with the corresponding values:
	 * <ul>
	 *   <li>{start}: the starting row number (1-based) currently being displayed</li>
	 *   <li>{end}: the ending row number (1-based) currently being displayed</li>
	 *   <li>{count}: the total number of rows</li>
	 *   <li>{page}: the page number (1-based) current being displayed, available since version 1.1.3</li>
	 *   <li>{pages}: the total number of pages, available since version 1.1.3</li>
	 * </ul>
	 */
	public $summaryText;
	/**
	 * @var string the message to be displayed when {@link dataProvider} does not have any data.
	 */
	public $emptyText;
	/**
	 * @var string the HTML tag name for the container of the {@link emptyText} property.
	 */
	public $emptyTagName='span';
	/**
	 * @var string the CSS class name for the container of all data item display. Defaults to 'items'.
	 */
	public $itemsCssClass='items';
	/**
	 * @var string the CSS class name for the summary text container. Defaults to 'summary'.
	 */
	public $summaryCssClass='summary';
	/**
	 * @var string the CSS class name for the pager container. Defaults to 'pager'.
	 */
	public $pagerCssClass='pager';
	/**
	 * @var string the CSS class name that will be assigned to the widget container element
	 * when the widget is updating its content via AJAX. Defaults to 'loading'.
	 * @since 1.1.1
	 */
	public $loadingCssClass='loading';
	
	/**
	 * Initializes the view.
	 * This method will initialize required property values and instantiate {@link columns} objects.
	 */
	public function init()
	{
		if($this->dataProvider===null) {
			throw new \Core\Exception('The "dataProvider" property cannot be empty.');
		}
	
		$this->dataProvider->info();
	
		if(isset($this->htmlOptions['id'])) {
			$this->id=$this->htmlOptions['id'];
		} else {
			$this->htmlOptions['id'] = $this->getId();
		}
		
// 		if($this->enableSorting && $this->dataProvider->getSort()===false)
// 			$this->enableSorting=false;
// 		if($this->enablePagination && $this->dataProvider->getPagination()===false)
// 			$this->enablePagination=false;
	}
	
	public function run()
	{
		$this->registerClientScript();
	
		echo \Core\Html::openTag($this->tagName,$this->htmlOptions)."\n";
	
		$this->renderContent();
		$this->renderKeys();
	
		echo \Core\Html::closeTag($this->tagName);
	}
	
	public function renderContent()
	{
		ob_start();
		echo preg_replace_callback("/{(\w+)}/",array($this,'renderSection'),$this->template);
		ob_end_flush();
	}
	
	protected function renderSection($matches)
	{
		$method='render'.$matches[1];
		if(method_exists($this,$method))
		{
			$this->$method();
			$html=ob_get_contents();
			ob_clean();
			return $html;
		}
		else
			return $matches[0];
	}
	
	/**
	 * Renders the empty message when there is no data.
	 */
	public function renderEmptyText()
	{
		$emptyText=$this->emptyText===null ? 'No results found.' : $this->emptyText;
		echo \Core\Html::tag($this->emptyTagName, array('class'=>'empty'), $emptyText);
	}
	
	/**
	 * Renders the key values of the data in a hidden tag.
	 */
	public function renderKeys()
	{
		echo \Core\Html::openTag('div',array(
				'class'=>'keys',
				'style'=>'display:none',
				'title'=>\Core\Http\Request::getInstance()->getBaseUrl(),
		));
		foreach($this->dataProvider->info('cols') as $key) {
			echo "<span>".\Core\Html::encode($key)."</span>";
		}
		echo "</div>\n";
	}
	
	/**
	 * Renders the summary text.
	 */
	public function renderSummary()
	{
		if(($count=$this->dataProvider->countBy())<=0) {
			return;
		}
	
		echo '<div class="'.$this->summaryCssClass.'">';
		$this->enablePagination = false;
		if($this->enablePagination)
		{
			$pagination=$this->dataProvider->getPagination();
			$total=$this->dataProvider->getTotalItemCount();
			$start=$pagination->currentPage*$pagination->pageSize+1;
			$end=$start+$count-1;
			if($end>$total)
			{
				$end=$total;
				$start=$end-$count+1;
			}
			if(($summaryText=$this->summaryText)===null)
				$summaryText='Displaying {start}-{end} of 1 result.|Displaying {start}-{end} of {count} results.';
			echo strtr($summaryText,array(
					'{start}'=>$start,
					'{end}'=>$end,
					'{count}'=>$total,
					'{page}'=>$pagination->currentPage+1,
					'{pages}'=>$pagination->pageCount,
			));
		}
		else
		{
			if(($summaryText=$this->summaryText)===null)
				$summaryText='Total 1 result.|Total {count} results.';
			echo strtr($summaryText,array(
					'{count}'=>$count,
					'{start}'=>1,
					'{end}'=>$count,
					'{page}'=>1,
					'{pages}'=>1,
			));
		}
		echo '</div>';
	}
	
	/**
	 * Renders the pager.
	 */
	public function renderPager()
	{
		if(!$this->enablePagination)
			return;
	
		$pager=array();
		$class='\FM\Fmx\Widgets\Pager\Link';
		if(is_string($this->pager))
			$class=$this->pager;
		elseif(is_array($this->pager))
		{
			$pager=$this->pager;
			if(isset($pager['class']))
			{
				$class=$pager['class'];
				unset($pager['class']);
			}
		}
		$pager['_pages']=$this->dataProvider->getPagination();
	
		if($pager['_pages']->getPageCount()>1)
		{
			echo '<div class="'.$this->pagerCssClass.'">';
			echo $this->widget($class,$pager);
			echo '</div>';
		}
		else
			echo $this->widget($class,$pager);
	}
	
	public function registerClientScript()
	{
	}
	
	abstract public function renderItems();
	
}