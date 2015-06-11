<?php
/**
 * Plugin element to render cascading dropdown
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.cascadingdropdown
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\String\String;
use Fabrik\Helpers\Worker;
use Fabrik\Helpers\ArrayHelper;

require_once JPATH_SITE . '/plugins/fabrik_element/databasejoin/databasejoin.php';

/**
 * Plugin element to render cascading dropdown
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.cascadingdropdown
 * @since       3.0
 */

class PlgFabrik_ElementCascadingdropdown extends PlgFabrik_ElementDatabasejoin
{
	/**
	 * J Parameter name for the field containing the label value
	 *
	 * @var string
	 */
	protected $labelParam = 'cascadingdropdown_label';

	/**
	 * J Parameter name for the field containing the concat label
	 *
	 * @var string
	 */
	protected $concatLabelParam = 'cascadingdropdown_label_concat';

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int  $repeatCounter  Repeat group counter
	 *
	 * @return  array
	 */

	public function elementJavascript($repeatCounter)
	{
		$input = $this->app->input;
		$id = $this->getHTMLId($repeatCounter);
		$params = $this->getParams();

		if ($this->getDisplayType() === 'auto-complete')
		{
			$autoOpts = array();
			$autoOpts['observerid'] = $this->getWatchId($repeatCounter);
			$autoOpts['formRef'] = $this->getFormModel()->jsKey();
			$autoOpts['storeMatchedResultsOnly'] = true;
			FabrikHelperHTML::autoComplete($id, $this->getElement()->id, $this->getFormModel()->getId(), 'cascadingdropdown', $autoOpts);
		}

		FabrikHelperHTML::script('media/com_fabrik/js/lib/Event.mock.js');
		$opts = $this->getElementJSOptions($repeatCounter);
		$opts->showPleaseSelect = $this->showPleaseSelect();
		$opts->watch = $this->getWatchId($repeatCounter);
		$watchElementModel = $this->getWatchElement();
		$opts->watchChangeEvent = $watchElementModel->getChangeEvent();
		$opts->displayType = $params->get('cdd_display_type', 'dropdown');
		$opts->id = $this->getId();
		$opts->listName = $this->getListModel()->getTable()->db_table_name;

		// This bizarre chunk of code handles the case of setting a CDD value on the QS on a new form
		$rowId = $input->get('rowid', '', 'string');
		$fullName = $this->getFullName();
		$watchName = $this->getWatchFullName();

		// If returning from failed posted validation data can be in an array
		$qsValue = $input->get($fullName, array(), 'array');
		$qsValue = ArrayHelper::getValue($qsValue, 0, null);
		$qsWatchValue = $input->get($watchName, array(), 'array');
		$qsWatchValue = ArrayHelper::getValue($qsWatchValue, 0, null);
		$useQsValue = $this->getFormModel()->hasErrors() && $this->isEditable() && $rowId === '' && !empty($qsValue) && !empty($qsWatchValue);
		$opts->def = $useQsValue ? $qsValue : $this->getValue(array(), $repeatCounter);

		// $$$ hugh - for reasons utterly beyond me, after failed validation, getValue() is returning an array.
		if (is_array($opts->def) && !empty($opts->def))
		{
			$opts->def = $opts->def[0];
		}

		$watchGroup = $this->getWatchElement()->getGroup()->getGroup();
		$group = $this->getGroup()->getGroup();
		$opts->watchInSameGroup = $watchGroup->id === $group->id;
		$opts->editing = ($this->isEditable() && $rowId !== '');
		$opts->showDesc = $params->get('cdd_desc_column', '') === '' ? false : true;
		$opts->advanced = $this->getAdvancedSelectClass() != '';
		$formId = $this->getFormModel()->getId();
		$opts->autoCompleteOpts = $opts->displayType == 'auto-complete'
				? FabrikHelperHTML::autoCompleteOptions($opts->id, $this->getElement()->id, $formId, 'cascadingdropdown') : null;
		$this->elementJavascriptJoinOpts($opts);

		return array('FbCascadingdropdown', $id, $opts);
	}

	/**
	 * Get the field name to use as the column that contains the join's label data
	 *
	 * @param   bool  $useStep  use step in element name
	 *
	 * @return	string join label column either returns concat statement or quotes `tablename`.`elementname`
	 */

	public function getJoinLabelColumn($useStep = false)
	{
		$params = $this->getParams();
		$join = $this->getJoin();
		$db = $this->getDb();

		if (($params->get('cascadingdropdown_label_concat') != '') && $this->app->input->get('override_join_val_column_concat') != 1)
		{
			$val = $params->get('cascadingdropdown_label_concat');

			if ($join)
			{
				$val = str_replace("{thistable}", $join->table_join_alias, $val);
			}

			$w = new Worker;
			$val = $w->parseMessageForPlaceHolder($val, array());

			return 'CONCAT_WS(\'\', ' . $val . ')';
		}

		$label = FabrikString::shortColName($join->params->get('join-label'));

		if ($label == '')
		{
			// This is being raised with checkbox rendering and using dropdown filter, everything seems to be working with using the element name though!
			// JError::raiseWarning(500, 'Could not find the join label for ' . $this->getElement()->name . ' try unlinking and saving it');
			$label = $this->getElement()->name;
		}

		if ($this->isJoin())
		{
			$joinTableName = $this->getDbName();
			$label = $this->getLabelParamVal();
		}
		else
		{
			$joinTableName = $join->table_join_alias;
		}

		return $useStep ? $joinTableName . '___' . $label : $db->quoteName($joinTableName . '.' . $label);
	}

	/**
	 * Reset cached data, needed when rendering table if CDD
	 * is in repeat group, so we can build optionVals
	 *
	 * @deprecated - not used
	 *
	 * @return  void
	 */

	protected function _resetCache()
	{
		unset($this->optionVals);
		unset($this->sql);
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           to pre-populate element with
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string	elements html
	 */

	public function render($data, $repeatCounter = 0)
	{
		$db = $this->getDb();
		$params = $this->getParams();
		$name = $this->getHTMLName($repeatCounter);
		$opts = array('raw' => 1);
		$default = (array) $this->getValue($data, $repeatCounter, $opts);

		/* $$$ rob don't bother getting the options if editable as the js event is going to get them.
		 * However if in readonly mode the we do need to get the options
		 * $$$ hugh - need to rethink this approach, see ticket #725. When editing, we need
		 * to build options and selection on server side, otherwise daisy chained CDD's don't
		 * work due to timing issues in JS between onComplete and get_options calls.
		 * $tmp = 	$this->isEditable() ? array() : $this->_getOptions($data);
		 * So ... we want to get options if not editable, or if editing an existing row.
		 * See also small change to attachedToForm() in JS, and new 'editing' option in
		 * elementJavascript() above, so the JS won't get options on init when editing an existing row
		 */
		$tmp = array();
		$rowId = $this->app->input->string('rowid', '', 'string');
		$show_please = $this->showPleaseSelect();

		// $$$ hugh testing to see if we need to load options after a validation failure, but I don't think we do, as JS will reload via AJAX
		if (!$this->isEditable() || ($this->isEditable() && $rowId !== ''))
		{
			$tmp = $this->_getOptions($data, $repeatCounter);
		}
		else
		{
			if ($show_please)
			{
				$tmp[] = $this->selectOption();
			}
		}

		$imageOpts = array('alt' => FText::_('PLG_ELEMENT_CALC_LOADING'), 'style' => 'display:none;padding-left:10px;', 'class' => 'loader');
		$this->loadingImg = FabrikHelperHTML::image("ajax-loader.gif", 'form', @$this->tmpl, $imageOpts);

		// Get the default label for the drop down (use in read only templates)
		$defaultLabel = '';
		$defaultValue = '';

		foreach ($tmp as $obj)
		{
			if (in_array($obj->value, $default))
			{
				$defaultValue = $obj->value;
				$defaultLabel = $obj->text;
				break;
			}
		}

		$id = $this->getHTMLId($repeatCounter);
		$class = 'fabrikinput inputbox ' . $params->get('bootstrap_class', '');
		$disabled = '';

		if (count($tmp) == 1)
		{
			$class .= " readonly";

			// Selects don't have readonly properties !
		}

		$w = new Worker;
		$default = $w->parseMessageForPlaceHolder($default);

		// Not yet implemented always going to use dropdown for now
		$displayType = $params->get('cdd_display_type', 'dropdown');
		$html = array();

		if ($this->canUse())
		{
			// $$$ rob display type not set up in parameters as not had time to test fully yet
			switch ($displayType)
			{
				case 'checkbox':
					$this->renderCheckBoxList($data, $repeatCounter, $html, $tmp, $default);
					$defaultLabel = implode("\n", $html);
					break;
				case 'radio':
					$this->renderRadioList($data, $repeatCounter, $html, $tmp, $defaultValue);
					$defaultLabel = implode("\n", $html);
					break;
				case 'multilist':
					$this->renderMultiSelectList($data, $repeatCounter, $html, $tmp, $default);
					$defaultLabel = implode("\n", $html);
					break;
				case 'auto-complete':
					$this->renderAutoComplete($data, $repeatCounter, $html, $default);
					break;
				default:
				case 'dropdown':
					// Jaanus: $maxWidth to avoid dropdowns become too large (when choosing options they would still be of their full length
					$maxWidth = $params->get('max-width', '') === '' ? '' : ' style="max-width:' . $params->get('max-width') . ';"';
					$advancedClass = $this->getAdvancedSelectClass();
					$attribs = 'class="' . $class . ' ' . $advancedClass . '" ' . $disabled . ' size="1"' . $maxWidth;
					$html[] = JHTML::_('select.genericlist', $tmp, $name, $attribs, 'value', 'text', $default, $id);
					break;
			}

			$html[] = $this->loadingImg;
			$html[] = ($displayType == 'radio') ? '</div>' : '';
		}

		if (!$this->isEditable())
		{
			if ($params->get('cascadingdropdown_readonly_link') == 1)
			{
				$listId = (int) $params->get('cascadingdropdown_table');

				if ($listId !== 0)
				{
					$query = $db->getQuery(true);
					$query->select('form_id')->from('#__{package}_lists')->where('id = ' . $listId);
					$db->setQuery($query);
					$popupFormId = $db->loadResult();
					$url = 'index.php?option=com_fabrik&view=details&formid=' . $popupFormId . '&listid=' . $listId . '&rowid=' . $defaultValue;
					$defaultLabel = '<a href="' . JRoute::_($url) . '">' . $defaultLabel . '</a>';
				}
			}

			return $defaultLabel . $this->loadingImg;
		}

		$this->renderDescription($html, $default);

		return implode("\n", $html);
	}

	/**
	 * Add the description to the element's form HTML
	 *
	 * @param   array  &$html    Output HTML
	 * @param   array  $default  Default values
	 *
	 * @return  void
	 */
	protected function renderDescription(&$html, $default)
	{
		$params = $this->getParams();

		if ($params->get('cdd_desc_column', '') !== '')
		{
			$html[] = '<div class="dbjoin-description">';

			for ($i = 0; $i < count($this->optionVals); $i++)
			{
				$opt = $this->optionVals[$i];
				$display = in_array($opt->value, $default) ? '' : 'none';
				$c = $i + 1;
				$html[] = '<div style="display:' . $display . '" class="notice description-' . $c . '">' . $opt->description . '</div>';
			}

			$html[] = '</div>';
		}
	}

	/**
	 * Does the element store its data in a join table (1:n)
	 *
	 * @return	bool
	 */

	public function isJoin()
	{
		if (in_array($this->getDisplayType(), array('checkbox', 'multilist')))
		{
			return true;
		}
		else
		{
			return parent::isJoin();
		}
	}

	/**
	 * Get the display type (list,checkbox,multiselect etc.)
	 *
	 * @since  3.0.7
	 *
	 * @return  string
	 */

	protected function getDisplayType()
	{
		return $this->getParams()->get('cdd_display_type', 'dropdown');
	}

	/**
	 * Get a list of the HTML options used in the database join drop down / radio buttons
	 *
	 * @param   array  $data           From current record (when editing form?)
	 * @param   int    $repeatCounter  Repeat group counter
	 * @param   bool   $incWhere       Do we include custom where in query
	 * @param   array  $opts           Additional options passed into _getOptionVals()
	 *
	 * @return  array	option objects
	 */

	protected function _getOptions($data = array(), $repeatCounter = 0, $incWhere = true, $opts = array())
	{
		$tmp = $this->_getOptionVals($data, $repeatCounter);

		return $tmp;
	}

	/**
	 * Gets the options for the drop down - used in package when forms update
	 *
	 * @return  void
	 */

	public function onAjax_getOptions()
	{
		$input = $this->app->input;
		$filterView = $this->app->input->get('filterview', '');
		$this->loadMeForAjax();

		/**
		 * $$$ hugh -added test for $filterView, and only do filter stuff if we are being
		 * called in a filter context, not in a regular form display context.
		 */

		if (!empty($filterView) && $this->getFilterBuildMethod() == 1)
		{
			// Get distinct records which have already been selected: http://fabrikar.com/forums/showthread.php?t=30450
			$listModel = $this->getListModel();
			$db = $listModel->getDb();
			$obs = $this->getWatchElement();
			$obsName = $obs->getFullName(false, false, false);

			// From a filter...
			if ($input->get('fabrik_cascade_ajax_update') == 1)
			{
				$obsValue = $input->get('v', array(), 'array');
			}
			else
			{
				// Standard
				$obsValue = (array) $input->get($obs->getFullName(true, false) . '_raw');
			}

			foreach ($obsValue as &$v)
			{
				$v = $db->q($v);
			}

			$where = $obsName . ' IN (' . implode(',', $obsValue) . ')';
			$opts = array('where' => $where);
			$ids = $listModel->getColumnData($this->getFullName(false, false, false), true, $opts);
			$key = $this->queryKey();

			if (is_array($ids))
			{
				array_walk($ids, create_function('&$val', '$db = $this->db;$val = $db->q($val);'));
				$this->autocomplete_where = empty($ids) ? '1 = -1' : $key . ' IN (' . implode(',', $ids) . ')';
			}
		}

		$filter = JFilterInput::getInstance();
		$data = $filter->clean($_POST, 'array');
		$opts = $this->_getOptionVals($data);
		$this->_replaceAjaxOptsWithDbJoinOpts($opts);

		echo json_encode($opts);
	}

	/**
	 * Test for db join element - if so update option labels with related join labels
	 *
	 * @param   array  &$opts  standard options
	 *
	 * @return  void
	 */

	protected function _replaceAjaxOptsWithDbJoinOpts(&$opts)
	{
		$groups = $this->getFormModel()->getGroupsHierarchy();
		$watch = $this->getWatchFullName();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				$fullName = $elementModel->getFullName();

				if ($fullName == $watch)
				{
					/**
					 * $$$ hugh - not sure what this is for, but changed class name to 3.x name,
					 * as it was still set to the old 2.1 naming.
					 */

					if (get_parent_class($elementModel) == 'plgFabrik_ElementDatabasejoin')
					{
						$data = array();
						$joinOpts = $elementModel->_getOptions($data);
					}

					/**
					 * $$$ hugh - I assume we can break out of both foreach now, as there shouldn't
					 * be more than one match for the $watch element.
					 */
					break 2;
				}
			}
		}

		if (isset($joinOpts))
		{
			$matrix = array();

			foreach ($joinOpts as $j)
			{
				$matrix[$j->value] = $j->text;
			}

			foreach ($opts as &$opt)
			{
				if (array_key_exists($opt->text, $matrix))
				{
					$opt->text = $matrix[$opt->text];
				}
			}
		}
	}

	/**
	 * Get array of option values
	 *
	 * @param   array  $data           Data
	 * @param   int    $repeatCounter  Repeat group counter
	 * @param   bool   $incWhere       Do we add custom where statement into sql
	 * @param   array  $opts           Additional options passed into buildQuery()
	 *
	 * @return  array	option values
	 */

	protected function _getOptionVals($data = array(), $repeatCounter = 0, $incWhere = true, $opts = array())
	{
		$params = $this->getParams();

		if (!isset($this->optionVals))
		{
			$this->optionVals = array();
		}

		$db = $this->getDb();
		$opts = array();
		$opts['repeatCounter'] = $repeatCounter;
		$sql = $this->buildQuery($data, $incWhere, $opts);
		$sqlKey = (string) $sql;
		$sqlKey .= $this->isEditable() ? '0' : '1';
		$db->setQuery($sql);

		if (array_key_exists($sqlKey, $this->optionVals))
		{
			return $this->optionVals[$sqlKey];
		}

		FabrikHelperHTML::debug($db->getQuery(), 'cascadingdropdown _getOptionVals');
		$this->optionVals[$sqlKey] = $db->loadObjectList();
		$eval = $params->get('cdd_join_label_eval', '');

		if (trim($eval) !== '')
		{
			foreach ($this->optionVals[$sqlKey] as $key => &$opt)
			{
				// Allow removing an option by returning false
				if (eval($eval) === false)
				{
					unset($this->optionVals[$sqlKey][$key]);
				}
			}
		}

		/*
		 * If it's a filter, need to use filterSelectLabel() regardless of showPleaseSelect()
		 * (should probably shift this logic into showPleaseSelect, and have that just do this
		 * test, and return the label to use.
		 */
		$filterView = $this->app->input->get('filterview', '');

		if ($filterView == 'table')
		{
			array_unshift($this->optionVals[$sqlKey], JHTML::_('select.option', '', $this->filterSelectLabel()));
		}
		else
		{
			if ($this->showPleaseSelect())
			{
				array_unshift($this->optionVals[$sqlKey], $this->selectOption());
			}
		}

		// Remove tags from labels
		if ($this->canUse() && in_array($this->getDisplayType(), array('multilist', 'dropdown')))
		{
			foreach ($this->optionVals[$sqlKey] as $key => &$opt)
			{
				$opt->text = strip_tags($opt->text);
			}
		}

		return $this->optionVals[$sqlKey];
	}

	/**
	 * Create the select option for dropdown
	 *
	 *  @return  object
	 */

	private function selectOption()
	{
		$params = $this->getParams();
		return JHTML::_('select.option', $params->get('cascadingdropdown_noselectionvalue', ''), $this->_getSelectLabel());
	}

	/**
	 * Do you add a please select option to the cdd list
	 *
	 * @since 3.0b
	 *
	 * @return  bool
	 */

	protected function showPleaseSelect()
	{
		$params = $this->getParams();

		if (!$this->canUse())
		{
			return false;
		}

		if (!$this->isEditable())
		{
			return false;
		}

		if (in_array($this->getDisplayType(), array('checkbox', 'multilist', 'radio')))
		{
			return false;
		}

		return (bool) $params->get('cascadingdropdown_showpleaseselect', true);
	}

	/**
	 * Get the full name of the element to observe. When this element changes
	 * state, the cdd should perform an ajax lookup to update its options
	 *
	 * @return  string
	 */

	protected function getWatchFullName()
	{
		$elementModel = $this->getWatchElement();

		return $elementModel->getFullName();
	}

	/**
	 * Get the HTML id for the watch element
	 *
	 * @param   int  $repeatCounter  repeat group counter
	 *
	 * @return  string
	 */

	protected function getWatchId($repeatCounter = 0)
	{
		$elementModel = $this->getWatchElement();

		return $elementModel->getHTMLId($repeatCounter);
	}

	/**
	 * Get the element to watch. Changes to this element will trigger the cdd's lookup
	 *
	 * @return  plgFabrik_Element
	 */

	protected function getWatchElement()
	{
		if (!isset($this->watchElement))
		{
			$watch = $this->getParams()->get('cascadingdropdown_observe');

			if ($watch == '')
			{
				throw new RuntimeException('No watch element set up for cdd' . $this->getElement()->id, 500);
			}

			$this->watchElement = $this->getFormModel()->getElement($watch, true);
		}

		return $this->watchElement;
	}

	/**
	 * Create the sql query used to get the possible selectionable value/labels used to create
	 * the dropdown/checkboxes
	 *
	 * @param   array  $data      data
	 * @param   bool   $incWhere  include where
	 * @param   array  $opts      query options
	 *
	 * @return  mixed	JDatabaseQuery or false if query can't be built
	 */

	protected function buildQuery($data = array(), $incWhere = true, $opts = array())
	{
		$input = $this->app->input;
		$sig = isset($this->autocomplete_where) ? $this->autocomplete_where . '.' . $incWhere : $incWhere;
		$sig .= '.' . serialize($opts);
		$repeatCounter = ArrayHelper::getValue($opts, 'repeatCounter', 0);
		$db = Worker::getDbo();

		if (isset($this->sql[$sig]))
		{
			return $this->sql[$sig];
		}

		$params = $this->getParams();
		$watch = $this->getWatchFullName();
		$whereVal = null;
		$groups = $this->getForm()->getGroupsHierarchy();
		$formModel = $this->getFormModel();
		$watchElement = $this->getWatchElement();

		// Test for ajax update
		if ($input->get('fabrik_cascade_ajax_update') == 1)
		{
			// Allow for multiple values - e.g. when observing a db join rendered as a checkbox
			$whereVal = $input->get('v', array(), 'array');
		}
		else
		{
			if (isset($formModel->data) || isset($formModel->formData))
			{
				$watchOpts = array('raw' => 1);

				if (isset($formModel->data))
				{
					if ($watchElement->isJoin())
					{
						$id = $watchElement->getFullName(true, false) . '_id';
						$whereVal = ArrayHelper::getValue($formModel->data, $id);
					}
					else
					{
						$whereVal = $watchElement->getValue($formModel->data, $repeatCounter, $watchOpts);
					}
				}
				else
				{
					/*
					 * If we're running onAfterProcess, formData will have short names in it, which means getValue()
					 * won't find the watch element, as it's looking for full names.  So if it exists, use formDataWithTableName.
					 */
					if (is_array($formModel->formDataWithTableName) && array_key_exists($watch, $formModel->formDataWithTableName))
					{
						$whereVal = $watchElement->getValue($formModel->formDataWithTableName, $repeatCounter, $watchOpts);
					}
					else
					{
						$whereVal = $watchElement->getValue($formModel->formData, $repeatCounter, $watchOpts);
					}
				}

				// $$$ hugh - if not set, set to '' to avoid selecting entire table
				if (!isset($whereVal))
				{
					$whereVal = '';
				}
			}
			else
			{
				// $$$ hugh - probably rendering table view ...
				$watch_raw = $watch . '_raw';

				if (isset($data[$watch_raw]))
				{
					$whereVal = $data[$watch_raw];
				}
				else
				{
					// $$$ hugh ::sigh:: might be coming in via swapLabelsForvalues in pre_process phase
					// and join array in data will have been flattened.  So try regular element name for watch.
					$no_join_watch_raw = $watchElement->getFullName(true, false) . '_raw';

					if (isset($data[$no_join_watch_raw]))
					{
						$whereVal = $data[$no_join_watch_raw];
					}
					else
					{
						// $$$ hugh - if watched element has no value, we have been selecting all rows from CDD table
						// but should probably select none.

						// Unless its a cdd autocomplete list filter - seems sensible to populate that with the values matching the search term
						if ($this->app->input->get('method') !== 'autocomplete_options')
						{
							$whereVal = '';
						}
					}
				}
			}
		}

		$where = '';
		$whereKey = $params->get('cascadingdropdown_key');

		if (!is_null($whereVal) && $whereKey != '')
		{
			$whereBits = strstr($whereKey, '___') ? explode('___', $whereKey) : explode('.', $whereKey);
			$whereKey = array_pop($whereBits);

			if (is_array($whereVal))
			{
				foreach ($whereVal as &$v)
				{

					// Jaanus: Solving bug: imploded arrays when chbx in repeated group         
					
					if (is_array($v)) 
					{
						foreach ($v as &$vchild)
						{
							$vchild = FabrikString::safeQuote($vchild);
						}
						$v = implode(',', $v);
					}
					else
					{
						$v = FabrikString::safeQuote($v);
					}
				}
      
				// Jaanus: if count of where values is 0 or if there are no letters or numbers, only commas in imploded array
				
				$where .= count($whereVal) == 0 || !preg_match('/\w/', implode(',', $whereVal)) ? '4 = -4' : $whereKey . ' IN ' . '(' . str_replace(',,', ',\'\',', implode(',', $whereVal)) . ')';
			}
			else
			{
				$where .= $whereKey . ' = ' . $db->q($whereVal);
			}
		}

		$filter = $params->get('cascadingdropdown_filter');

		/* $$$ hugh - temporary hack to work around this issue:
		 * http://fabrikar.com/forums/showthread.php?p=71288#post71288
		 * ... which is basically that if they are using {placeholders} in their
		 * filter query, there's no point trying to apply that filter if we
		 * aren't in form view, for instance when building a search filter
		 * or in table view when the cdd is in a repeat group, 'cos there won't
		 * be any {placeholder} data to use.
		 * So ... for now, if the filter contains {...}, and view!=form ... skip it
		 * $$$ testing fix for the bandaid, ccd JS should not be submitting data from form
		 */
		if (trim($filter) != '')
		{
			$where .= ($where == '') ? ' ' : ' AND ';
			$where .= $filter;
		}

		$w = new Worker;

		// $$$ hugh - add some useful stuff to search data
		$placeholders = is_null($whereVal) ? array() : array('whereval' => $whereVal, 'wherekey' => $whereKey);
		$join = $this->getJoin();
		$where = str_replace("{thistable}", $join->table_join_alias, $where);

		if (!empty($this->autocomplete_where))
		{
			$where .= $where !== '' ? ' AND ' . $this->autocomplete_where : $this->autocomplete_where;
		}

		$data = array_merge($data, $placeholders);
		$where = $w->parseMessageForPlaceHolder($where, $data);
		$table = $this->getDbName();
		$key = $this->queryKey();
		$orderBy = 'text';
		$tables = $this->getFormModel()->getLinkedFabrikLists($params->get('join_db_name'));
		$listModel = new \Fabrik\Admin\Models\Lizt;;
		$val = $params->get('cascadingdropdown_label_concat');

		if (!empty($val))
		{
			$val = str_replace("{thistable}", $join->table_join_alias, $val);
			$val = $w->parseMessageForPlaceHolder($val, $data);
			$val = 'CONCAT_WS(\'\', ' . $val . ')';
			$orderBy = $val;
		}
		else
		{
			$val = FabrikString::safeColName($params->get($this->labelParam));
			$val = preg_replace("#^`($table)`\.#", $db->quoteName($join->table_join_alias) . '.', $val);

			foreach ($tables as $tid)
			{
				$listModel->setId($tid);
				$listModel->getTable();
				$formModel = $this->getForm();
				$formModel->getGroupsHierarchy();
				$orderBy = $val;

				// See if any of the tables elements match the db joins val/text
				foreach ($groups as $groupModel)
				{
					$elementModels = $groupModel->getPublishedElements();

					foreach ($elementModels as $elementModel)
					{
						$element = $elementModel->element;

						if ($element->name == $val)
						{
							$val = $elementModel->modifyJoinQuery($val);
						}
					}
				}
			}
		}

		$val = str_replace($db->quoteName($table), $db->quoteName($join->table_join_alias), $val);
		$query = $db->getQuery(true);
		$query->select('DISTINCT(' . $key . ') AS value, ' . $val . 'AS text');
		$desc = $params->get('cdd_desc_column', '');

		if ($desc !== '')
		{
			$query->select(FabrikString::safeColName($desc) . ' AS description');
		}

		$query->from($db->quoteName($table) . ' AS ' . $db->quoteName($join->table_join_alias));
		$query = $this->buildQueryJoin($query);
		$where = FabrikString::rtrimword($where);

		if ($where !== '')
		{
			$query->where($where);
		}

		if (!String::stristr($where, 'order by'))
		{
			$query->order($orderBy . ' ASC');
		}

		$this->sql[$sig] = $query;
		FabrikHelperHTML::debug($this->sql[$sig]);

		return $this->sql[$sig];
	}

	/**
	 * Get the the field name used for the foo AS value part of the query
	 *
	 * @since   3.0.8
	 *
	 * @return  string
	 */

	protected function queryKey()
	{
		$db = Worker::getDbo();
		$join = $this->getJoin();
		$table = $this->getDbName();
		$params = $this->getParams();
		$key = FabrikString::safeColName($params->get('cascadingdropdown_id'));
		$key = str_replace($db->quoteName($table), $db->quoteName($join->table_join_alias), $key);

		return $key;
	}

	/**
	 * Get the element name or concat statement used to build the dropdown labels or
	 * table data field
	 *
	 * @return  string
	 */

	protected function getLabelOrConcatVal()
	{
		$params = $this->getParams();
		$join = $this->getJoin();

		if ($params->get('cascadingdropdown_label_concat') == '')
		{
			return $this->getLabelParamVal();
		}
		else
		{
			$w = new Worker;
			$val = str_replace("{thistable}", $join->table_join_alias, $params->get('cascadingdropdown_label_concat'));
			$val = $w->parseMessageForPlaceHolder($val, array());

			return 'CONCAT_WS(\'\', ' . $val . ')';
		}
	}

	/**
	 * Load connection object
	 *
	 * @return  object	connection table
	 */

	protected function loadConnection()
	{
		$params = $this->getParams();
		$id = $params->get('cascadingdropdown_connection');
		$cid = $this->getlistModel()->getConnection()->getConnection()->id;

		if ($cid == $id)
		{
			$this->cn = $this->getlistModel()->getConnection();
		}
		else
		{
			$this->cn = new Fabrik\Admin\Models\Connection;
			$this->cn->set('id', $id);
		}

		return $this->cn->getConnection();
	}

	/**
	 * Get the cdd's database name
	 *
	 * @return  db name or false if unable to get name
	 */

	protected function getDbName()
	{
		if (!isset($this->dbname) || $this->dbname == '')
		{
			$params = $this->getParams();
			$id = $params->get('cascadingdropdown_table');

			if ($id == '')
			{
				throw new RuntimeException('Unable to get table for cascading dropdown (ignore if creating a new element)');

				return false;
			}

			$db = Worker::getDbo(true);
			$query = $db->getQuery(true);
			$query->select('db_table_name')->from('#__fabrik_lists')->where('id = ' . (int) $id);
			$db->setQuery($query);
			$this->dbname = $db->loadResult();
		}

		return $this->dbname;
	}

	/**
	 * If used as a filter add in some JS code to watch observed filter element's changes
	 * when it changes update the contents of this elements dd filter's options
	 *
	 * @param   bool    $normal     is the filter a normal (true) or advanced filter
	 * @param   string  $container  container
	 *
	 * @return  void
	 */

	public function filterJS($normal, $container)
	{
		$element = $this->getElement();
		$observerId = $this->getWatchId();
		$observerId .= 'value';
		$formModel = $this->getFormModel();
		$formId = $formModel->get('id');

		// 3.1 Cdd filter set up elsewhere
		if ($element->get('filter_type') == 'dropdown')
		{
			$default = $this->getDefaultFilterVal($normal);
			$filterid = $this->getHTMLId() . 'value';
			FabrikHelperHTML::script('plugins/fabrik_element/cascadingdropdown/filter.js');
			$opts = new stdClass;
			$opts->formid = $formId;
			$opts->filterid = $filterid;
			$opts->elid = $this->getId();
			$opts->def = $default;
			$opts->filterobj = 'Fabrik.filter_' . $container;
			$opts = json_encode($opts);
			$plugin = $element->get('plugin');

			return "Fabrik.filter_{$container}.addFilter('$plugin', new CascadeFilter('$observerId', $opts));\n";
		}
	}

	/**
	 * Get the class to manage the form element
	 * to ensure that the file is loaded only once
	 *
	 * @param   array   &$srcs   Scripts previously loaded
	 * @param   string  $script  Script to load once class has loaded
	 * @param   array   &$shim   Dependant class names to load before loading the class - put in requirejs.config shim
	 *
	 * @return void
	 */

	public function formJavascriptClass(&$srcs, $script = '', &$shim = array())
	{
		$s = new stdClass;
		$s->deps = array('fab/element', 'element/databasejoin/databasejoin', 'fab/encoder');
		$shim['element/cascadingdropdown/cascadingdropdown'] = $s;
		parent::formJavascriptClass($srcs, $script, $shim);
	}

	/**
	 * Get the field name for the joined tables' pk
	 *
	 * @since  3.0.7
	 *
	 * @return  string
	 */

	protected function getJoinValueFieldName()
	{
		$params = $this->getParams();
		$full = $params->get('cascadingdropdown_id');

		return FabrikString::shortColName($full);
	}

	/**
	 * Get the observed element's element model
	 *
	 * @return mixed element model or false
	 */

	protected function _getObserverElement()
	{
		$params = $this->getParams();
		$observer = $params->get('cascadingdropdown_observe');
		$formModel = $this->getForm();
		$groups = $formModel->getGroupsHierarchy();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getMyElements();

			foreach ($elementModels as $elementModel)
			{
				$element = $elementModel->getElement();

				if ($observer == $element->get('name'))
				{
					return $elementModel;
				}
			}
		}

		return false;
	}

	/**
	 * Run before the element is saved
	 *
	 * @param   object  &$row  that is going to be updated
	 *
	 * @return null
	 */

	public function beforeSave(&$row)
	{
		/*
		 * do nothing, just here to prevent join element method from running
		 * instead (which removed join table
		 * entry if not pluginname==databasejoin
		 */
		return true;
	}

	/**
	 * Get an array of element html ids and their corresponding
	 * js events which trigger a validation.
	 * Examples of where this would be overwritten include timedate element with time field enabled
	 *
	 * @param   int  $repeatCounter  repeat group counter
	 *
	 * @return  array  html ids to watch for validation
	 */

	public function getValidationWatchElements($repeatCounter)
	{
		$id = $this->getHTMLId($repeatCounter);
		$ar = array('id' => $id, 'triggerEvent' => 'change');

		return array($ar);
	}

	/**
	 * When copying elements from an existing table
	 * once a copy of all elements has been made run them through this method
	 * to ensure that things like watched element id's are updated
	 *
	 * @param   array  $elementMap  copied element ids (keyed on original element id)
	 *
	 * @throws RuntimeException
	 *
	 * @return  mixed JError:void
	 */

	public function finalCopyCheck($elementMap)
	{
		$element = $this->getElement();
		unset($this->params);
		$params = $this->getParams();
		$oldObserverId = $params->get('cascadingdropdown_observe');

		if (!array_key_exists($oldObserverId, $elementMap))
		{
			throw new RuntimeException('cascade dropdown: no id ' . $oldObserverId . ' found in ' . implode(",", array_keys($elementMap)));
		}

		$newObserveId = $elementMap[$oldObserverId];
		$params->set('cascadingdropdown_observe', $newObserveId);

		// Save params
		$element->params = $params->toString();

		if (!$element->store())
		{
			return JError::raiseWarning(500, $element->getError());
		}
	}

	/**
	 * Get select option label
	 *
	 * @param  bool  $filter  get alt label for filter, if present using :: splitter
	 *
	 * @return  string
	 */

	protected function _getSelectLabel($filter = false)
	{
		$params = $this->getParams();
		$label = $params->get('cascadingdropdown_noselectionlabel');

		if (strstr($label, '::'))
		{
			$labels = explode('::', $label);
			$label = $filter ? $labels[1] : $labels[0];
		}

		if (!$filter && $label == '')
		{
			$label = 'COM_FABRIK_PLEASE_SELECT';
		}

		return FText::_($label);
	}

	/**
	 * Should the 'label' field be quoted.  Overridden by databasejoin and extended classes,
	 * which may use a CONCAT'ed label which mustn't be quoted.
	 *
	 * @since	3.0.6
	 *
	 * @return boolean
	 */

	protected function quoteLabel()
	{
		$params = $this->getParams();

		return $params->get('cascadingdropdown_label_concat', '') == '';
	}

	/**
	 * If filterValueList_Exact incjoin value = false, then this method is called
	 * to ensure that the query produced in filterValueList_Exact contains at least the database join element's
	 * join
	 *
	 * @return  string  required join text to ensure exact filter list code produces a valid query.
	 */

	protected function buildFilterJoin()
	{
		$joinTable = FabrikString::safeColName($this->getDbName());
		$join = $this->getJoin();
		$joinTableName = FabrikString::safeColName($join->table_join_alias);
		$joinKey = $this->getJoinValueColumn();
		$elName = FabrikString::safeColName($this->getFullName(true, false));

		return 'INNER JOIN ' . $joinTable . ' AS ' . $joinTableName . ' ON ' . $joinKey . ' = ' . $elName;
	}

	/**
	 * Use in list model storeRow() to determine if data should be stored.
	 * Currently only supported for db join elements whose values are default values
	 * avoids casing '' into 0 for int fields
	 *
	 * Extended this from dbjoin element as an empty string should be possible in cdd, if no options selected.
	 * Otherwise previously selected values are kept
	 *
	 * @param   array  $data  Data being inserted
	 * @param   mixed  $val   Element value to insert into table
	 *
	 * @since   3.0.7
	 *
	 * @return boolean
	 */

	public function dataIsNull($data, $val)
	{
		return false;
	}

	/**
	 * Get drop-down filter select label
	 *
	 * @return  string
	 */

	protected function filterSelectLabel()
	{
		$params = $this->getParams();
		$label = $this->_getSelectLabel(true);

		if (empty($label))
		{
			$label = $params->get('filter_required') == 1 ? FText::_('COM_FABRIK_PLEASE_SELECT') : FText::_('COM_FABRIK_FILTER_PLEASE_SELECT');
		}

		return $label;
	}
}
