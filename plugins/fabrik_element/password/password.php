<?php
/**
 * Plugin element to render 2 fields to capture and confirm a password
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.password
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugins\Element;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\ArrayHelper;

/**
 * Plugin element to render 2 fields to capture and confirm a password
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.password
 * @since       3.5
 */
class Password extends Element
{
	/**
	 * States if the element contains data which is recorded in the database
	 * some elements (e.g. buttons) don't
	 *
	 * @param   array $data Posted data
	 *
	 * @return  bool
	 */

	public function recordInDatabase($data = null)
	{
		$element = $this->getElement();

		// If storing from inline edit then key may not exist
		if (!array_key_exists($element->name, $data))
		{
			return false;
		}

		if (trim($data[$element->name]) === '')
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * Manipulates posted form data for insertion into database
	 *
	 * @param   mixed $val  This elements posted form data
	 * @param   array $data Posted form data
	 *
	 * @return  mixed
	 */

	public function storeDatabaseFormat($val, $data)
	{
		jimport('joomla.user.helper');
		$salt  = JUserHelper::genRandomPassword(32);
		$crypt = JUserHelper::getCryptedPassword($val, $salt);
		$val   = $crypt . ':' . $salt;

		return $val;
	}

	/**
	 * Determines if the element can contain data used in sending receipts,
	 * e.g. fabrikfield returns true
	 *
	 * @deprecated - not used
	 *
	 * @return  bool
	 */

	public function isReceiptElement()
	{
		return true;
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array $data          To pre-populate element with
	 * @param   int   $repeatCounter Repeat group counter
	 *
	 * @return  string    elements html
	 */

	public function render($data, $repeatCounter = 0)
	{
		$layout     = $this->getLayout('form');
		$layoutData = new stdClass;
		$element    = $this->getElement();
		$value      = '';
		$params     = $this->getParams();

		if (!$this->isEditable())
		{
			return '***********';
		}

		$bits                = $this->inputProperties($repeatCounter, 'password');
		$bits['value']       = $value;
		$bits['placeholder'] = FText::_('PLG_ELEMENT_PASSWORD_TYPE_PASSWORD');

		$layoutData->pw1Attributes = $bits;

		$origname            = $element->name;
		$element->name       = $element->name . '_check';
		$name                = $this->getHTMLName($repeatCounter);
		$bits['placeholder'] = FText::_('PLG_ELEMENT_PASSWORD_CONFIRM_PASSWORD');
		$bits['class'] .= ' fabrikSubElement';
		$bits['name'] = $name;
		$bits['id']   = $name;

		$layoutData->pw2Attributes     = $bits;
		$element->name                 = $origname;
		$layoutData->showStrengthMeter = $params->get('strength_meter', 1) == 1;

		return $layout->render($layoutData);

	}

	/**
	 * Internal element validation
	 *
	 * @param   array $data          Form data
	 * @param   int   $repeatCounter Repeat group counter
	 *
	 * @return bool
	 */

	public function validate($data, $repeatCounter = 0)
	{
		if ($this->isEditable() === false)
		{
			return true;
		}

		$input    = $this->app->input;
		$k        = $this->getlistModel()->getTable()->get('list.db_primary_key');
		$k        = FabrikString::safeColNameToArrayKey($k);
		$element  = $this->getElement();
		$origname = $element->name;

		/**
		 * $$$ hugh - need to fetch the value for the main data, as well as the confirmation,
		 * rather than using $data, to avoid issues with things like "foo%20bar" getting incorrectly
		 * decoded as "foo bar" in $data.
		 */
		$value      = urldecode($this->getValue($_REQUEST, $repeatCounter));
		$name       = $this->getFullName(true, false);
		$check_name = str_replace($element->name, $element->name . '_check', $name);

		/**
		 * $$$ hugh - there must be a better way of doing this, but ...
		 * if ajax, and the _check element isn't there, then this is probably an inline edit, and
		 * this isn't the element being edited, we're just being called as part of the generic form validation,
		 * so just return true;
		 */
		$ajax = $input->getBool('fabrik_ajax', false);

		if ($ajax && !array_key_exists($check_name, $_REQUEST))
		{
			return true;
		}

		$this->setFullName($check_name, true, false);
		$this->reset();
		$checkvalue    = urldecode($this->getValue($_REQUEST, $repeatCounter));
		$element->name = $origname;

		if ($this->getParams()->get('password_j_validate', false))
		{
			if (!$this->validateJRule('password', $value, JPATH_LIBRARIES . '/cms/form/rule'))
			{
				return false;
			}
		}

		if ($checkvalue != $value)
		{
			$this->validationError = FText::_('PLG_ELEMENT_PASSWORD_PASSWORD_CONFIRMATION_DOES_NOT_MATCH');

			return false;
		}
		else
		{
			$rowId = $input->get('rowid', '', 'string');

			// If its coming from an ajax form submit then the key is possibly an array.
			$keyVal = ArrayHelper::getValue($_REQUEST, $k);

			if (is_array($keyVal))
			{
				$keyVal = ArrayHelper::getValue($keyVal, 0);
			}

			// $$$ rob add rowid test as well as if using row=-1 and usekey=field $k may have a value
			if (($rowId === '' || empty($rowId)) && $keyVal === 0 && $value === '')
			{
				/**
				 * Why are we using .= here, but nowhere else?
				 */
				$this->validationError .= FText::_('PLG_ELEMENT_PASSWORD_PASSWORD_CONFIRMATION_EMPTY_NOT_ALLOWED');

				return false;
			}

			return true;
		}
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int $repeatCounter Repeat group counter
	 *
	 * @return  array
	 */

	public function elementJavascript($repeatCounter)
	{
		$id                    = $this->getHTMLId($repeatCounter);
		$opts                  = $this->getElementJSOptions($repeatCounter);
		$formparams            = $this->getForm()->getParams();
		$opts->ajax_validation = $formparams->get('ajax_validations') === '1';
		$opts->progressbar     = true;

		JText::script('PLG_ELEMENT_PASSWORD_STRONG');
		JText::script('PLG_ELEMENT_PASSWORD_MEDIUM');
		JText::script('PLG_ELEMENT_PASSWORD_WEAK');
		JText::script('PLG_ELEMENT_PASSWORD_TYPE_PASSWORD');
		JText::script('PLG_ELEMENT_PASSWORD_MORE_CHARACTERS');

		return array('FbPassword', $id, $opts);
	}

	/**
	 * Get an array of element html ids and their corresponding
	 * js events which trigger a validation.
	 * Examples of where this would be overwritten include timedate element with time field enabled
	 *
	 * @param   int $repeatCounter Repeat group counter
	 *
	 * @return  array  html ids to watch for validation
	 */

	public function getValidationWatchElements($repeatCounter)
	{
		$id = $this->getHTMLId($repeatCounter) . '_check';
		$ar = array('id' => $id, 'triggerEvent' => 'blur');

		return array($ar);
	}

	/**
	 * Return an internal validation icon
	 *
	 * @return  string
	 */

	public function internalValidationIcon()
	{
		return 'star';
	}

	/**
	 * Return internal validation hover text
	 *
	 * @return  string
	 */

	public function internalValidataionText()
	{
		return FText::_('PLG_ELEMENT_PASSWORD_VALIDATION_TIP');
	}
}
