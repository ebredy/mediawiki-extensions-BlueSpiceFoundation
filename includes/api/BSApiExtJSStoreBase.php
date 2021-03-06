<?php
/**
 *  This class serves as a backend for ExtJS stores. It allows all
 * necessary parameters and provides convenience methods and a standard ouput
 * format
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * This file is part of BlueSpice for MediaWiki
 * For further information visit http://www.blue-spice.org
 *
 * @author     Robert Vogel <vogel@hallowelt.com>
 * @author     Patric Wirth <wirth@hallowelt.com>
 * @package    Bluespice_Foundation
 * @copyright  Copyright (C) 2015 Hallo Welt! - Medienwerkstatt GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
 * @filesource
 *
 * Example request parameters of an ExtJS store

	_dc:1430126252980
	filter:[
		{
			"type":"string",
			"comparison":"ct",
			"value":"some text ...",
			"field":"someField"
		}
	]
	group:[
		{
			"property":"someOtherField",
			"direction":"ASC"
		}
	]
	sort:[
		{
			"property":"someOtherField",
			"direction":"ASC"
		}
	]
	page:1
	start:0
	limit:25
 */
abstract class BSApiExtJSStoreBase extends BSApiBase {

	/**
	 * The current parameters sent by the ExtJS store
	 * @var BsExtJSStoreParams
	 */
	protected $oStoreParams = null;

	/**
	 * Automatically set within 'postProcessData' method
	 * @var int
	 */
	protected $iFinalDataSetCount = 0;

	/**
	 * May be overwritten by subclass
	 * @var string
	 */
	protected $root = 'results';

	/**
	 * May be overwritten by subclass
	 * @var string
	 */
	protected $totalProperty = 'total';

	public function execute() {
		$sQuery = $this->getParameter( 'query' );
		$aData = $this->makeData( $sQuery );
		$FinalData = $this->postProcessData( $aData );
		$this->returnData( $FinalData );
	}

	/**
	 * @param string $sQuery Potential query provided by ExtJS component.
	 * This is some kind of preliminary filtering. Subclass has to decide if
	 * and how to process it
	 * @return array - Full list of of data objects. Filters, paging, sorting
	 * will be done by the base class
	 */
	protected abstract function makeData( $sQuery = '' );

	/**
	 * Creates a proper output format based on the classes properties
	 * @param array $aData An array of plain old data objects
	 */
	public function returnData($aData) {
		wfRunHooks( 'BSApiExtJSStoreBaseBeforeReturnData', array( $this, &$aData ) );
		$result = $this->getResult();
		$result->setIndexedTagName( $aData, $this->root );
		$result->addValue( null, $this->root, $aData );
		$result->addValue( null, $this->totalProperty, $this->iFinalDataSetCount );
	}

	/**
	 *
	 * @return BsExtJSStoreParams
	 */
	protected function getStoreParams() {
		if( $this->oStoreParams === null ) {
			$this->oStoreParams = BsExtJSStoreParams::newFromRequest();
		}
		return $this->oStoreParams;
	}

	public function getAllowedParams() {
		return array(
			'sort' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false,
				ApiBase::PARAM_DFLT => '[]'
			),
			'group' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false,
				ApiBase::PARAM_DFLT => '[]'
			),
			'filter' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false,
				ApiBase::PARAM_DFLT => '[]'
			),
			'page' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => false,
				ApiBase::PARAM_DFLT => 0
			),
			'limit' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => false,
				ApiBase::PARAM_DFLT => 25
			),
			'start' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => false,
				ApiBase::PARAM_DFLT => 0
			),

			'callback' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false
			),

			'query' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false
			),
			'_dc' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => false
			),
			'format' => array(
				ApiBase::PARAM_DFLT => 'json',
				ApiBase::PARAM_TYPE => array( 'json', 'jsonfm' )
			)
		);
	}

	public function getParamDescription() {
		return array(
			'sort' => 'JSON string with sorting info; deserializes to array of objects that hold filed name and direction for each sorting option',
			'group' => 'JSON string with grouping info; deserializes to array of objects that hold filed name and direction for each grouping option',
			'filter' => 'JSON string with filter info; deserializes to array of objects that hold filed name, filter type, and filter value for each sorting option',
			'page' => 'Allows server side calculation of start/limit',
			'limit' => 'Number of results to return',
			'start' => 'The offset to start the result list from',
			'query' => 'This is similar to "filter", but the provided value serves as a filter only for the "value" field of an ExtJS component',
			'callback' => 'The offset to start the result list from',
			'_dc' => '"Disable cache" flag',
			'format' => 'The format of the output (only JSON or formatted JSON)'
		);
	}

	protected function getParameterFromSettings($paramName, $paramSettings, $parseLimit) {
		$value = parent::getParameterFromSettings($paramName, $paramSettings, $parseLimit);
		//Unfortunately there is no way to register custom types for parameters
		if( in_array( $paramName, array( 'sort', 'group', 'filter' ) ) ) {
			$value = FormatJson::decode( $value );
			if( empty($value) ) {
				return array();
			}
		}
		return $value;
	}

	public function getParameter( $paramName, $parseLimit = true ) {
		//Make this public, so hook handler could get the params
		return parent::getParameter( $paramName, $parseLimit );
	}

	/**
	 * Filter, sort and trim the result according to the call parameters and
	 * apply security trimming
	 * @param array $aData
	 * @return array
	 */
	public function postProcessData( $aData ) {
		if( !wfRunHooks( 'BSApiExtJSStoreBaseBeforePostProcessData', array( $this, &$aData ) ) ) {
			return $aData;
		}

		$aProcessedData = array();

		//First, apply filter
		$aProcessedData = array_filter($aData, array( $this, 'filterCallback') );
		wfRunHooks( 'BSApiExtJSStoreBaseAfterFilterData', array( $this, &$aProcessedData ) );

		//Next, apply sort
		//usort($aProcessedData, array( $this, 'sortCallback') ); <-- had some performance issues
		$aProcessedData = $this->sortData( $aProcessedData );

		//Before we trim, we save the count
		$this->iFinalDataSetCount = count( $aProcessedData );

		//Last, do trimming
		$aProcessedData = $this->trimData( $aProcessedData );

		return $aProcessedData;
	}

	/**
	 * Applies all sorters provided by the store
	 * --> has performance issues on large dataset; Code kept for documentation
	 * @param object $oA
	 * @param object $oB
	 * @return int
	 */
	public function sortCallback( $oA, $oB ) {
		$aSort = $this->getParameter('sort');
		$iCount = count( $aSort );
		for( $i = 0; $i < $iCount; $i++ ) {
			$sProperty = $aSort[$i]->property;
			$sDirection = strtoupper( $aSort[$i]->direction );

			if( $oA->$sProperty !== $oB->$sProperty ) {
				if( $sDirection === 'ASC' ) {
					return $oA->$sProperty < $oB->$sProperty ? -1 : 1;
				}
				else { //'DESC'
					return $oA->$sProperty > $oB->$sProperty ? -1 : 1;
				}
			}
		}
		return 0;
	}

	/**
	 *
	 * @param object $aDataSet
	 * @return boolean
	 */
	public function filterCallback( $aDataSet ) {
		$aFilter = $this->getParameter( 'filter' );
		foreach( $aFilter as $oFilter ) {
			//If just one of these filters does not apply, the dataset needs
			//to be removed

			if( $oFilter->type == 'string' ) {
				$bFilterApplies = $this->filterString( $oFilter, $aDataSet );
				if( !$bFilterApplies ) {
					return false;
				}
			}
			if( $oFilter->type == 'list' ) {
				$bFilterApplies = $this->filterList( $oFilter, $aDataSet );
				if( !$bFilterApplies ) {
					return false;
				}
			}
			if( $oFilter->type == 'numeric' ) {
				$bFilterApplies = $this->filterNumeric( $oFilter, $aDataSet );
				if( !$bFilterApplies ) {
					return false;
				}
			}
			//TODO: Implement for type 'date', 'datetime' and 'boolean'
		}

		return true;
	}

	/**
	 * Performs string filtering based on given filter of type string on a dataset
	 * @param object $oFilter
	 * @param oject $aDataSet
	 * @return boolean true if filter applies, false if not
	 */
	public function filterString( $oFilter, $aDataSet ) {
		if( !is_string( $oFilter->value ) ) {
			return true; //TODO: Warning
		}
		$sFieldValue = $aDataSet->{$oFilter->field};
		$sFilterValue = $oFilter->value;

		//TODO: Add string functions to BsStringHelper
		//HINT: http://stackoverflow.com/a/10473026 + Case insensitive
		switch( $oFilter->comparison ) {
			case 'sw':
				return $sFilterValue === '' ||
					strripos($sFieldValue, $sFilterValue, -strlen($sFieldValue)) !== false;
			case 'ew':
				return $sFilterValue === '' ||
					(($temp = strlen($sFieldValue) - strlen($sFilterValue)) >= 0
					&& stripos($sFieldValue, $sFilterValue, $temp) !== false);
			case 'ct':
				return stripos($sFieldValue, $sFilterValue) !== false;
			case 'nct':
				return stripos($sFieldValue, $sFilterValue) === false;
			case 'eq':
				return $sFieldValue === $sFilterValue;
			case 'neq':
				return $sFieldValue !== $sFilterValue;
		}
	}

	/**
	 * Performs numeric filtering based on given filter of type integer on a
	 * dataset
	 * @param object $oFilter
	 * @param oject $aDataSet
	 * @return boolean true if filter applies, false if not
	 */
	public function filterNumeric( $oFilter, $aDataSet ) {
		if( !is_numeric( $oFilter->value ) ) {
			return true; //TODO: Warning
		}
		$sFieldValue = $aDataSet->{$oFilter->field};
		$iFilterValue = (int) $oFilter->value;

		switch( $oFilter->comparison ) {
			case 'gt':
				return $sFieldValue < $iFilterValue;
			case 'lt':
				return $sFieldValue > $iFilterValue;
			case 'eq':
				return $sFieldValue === $iFilterValue;
			case 'neq':
				return $sFieldValue !== $iFilterValue;
		}
	}

	/**
	 * Performs list filtering based on given filter of type array on a dataset
	 * @param object $oFilter
	 * @param oject $aDataSet
	 * @return boolean true if filter applies, false if not
	 */
	public function filterList( $oFilter, $aDataSet ) {
		if( !is_array( $oFilter->value ) ) {
			return true; //TODO: Warning
		}
		$aFieldValues = $aDataSet->{$oFilter->field};
		if( empty( $aFieldValues ) ) {
			return false;
		}
		$aFilterValues = $oFilter->value;
		$aTemp = array_intersect( $aFieldValues, $aFilterValues );
		if( empty( $aTemp ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Applies pagination on the result
	 * @param array $aProcessedData The filtered result
	 * @return array a trimmed version of the result
	 */
	public function trimData( $aProcessedData ) {
		$iStart = $this->getParameter( 'start' );
		$iEnd = $this->getParameter( 'limit' ) + $iStart;

		if( $iEnd > $this->iFinalDataSetCount || $iEnd === 0 ) {
			$iEnd = $this->iFinalDataSetCount;
		}

		$aTrimmedData = array();
		for( $i = $iStart; $i < $iEnd; $i++ ) {
			$aTrimmedData[] = $aProcessedData[$i];
		}

		return $aTrimmedData;
	}

	/**
	 * Performs fast sorting on the results. Thanks at user "pigpen"
	 * @param array $aProcessedData
	 * @return array The sorted results
	 */
	public function sortData($aProcessedData) {
		$aSort = $this->getParameter('sort');
		$iCount = count( $aSort );
		for( $i = 0; $i < $iCount; $i++ ) {
			$sProperty = $aSort[$i]->property;
			$sDirection = strtoupper( $aSort[$i]->direction );
			$a{$sProperty} = array();
			foreach( $aProcessedData as $iKey => $aDataSet ) {
				$a{$sProperty}[$iKey] = $aDataSet->{$sProperty};
			}

			$aParams[] = $a{$sProperty};
			$aParams[] = SORT_NATURAL; //We might tweak this depending on the data type of the field. Mabye we should make some "getSort( $fieldName )" method
			if( $sDirection === 'ASC' ) {
				$aParams[] = SORT_ASC;
			}
			else {
				$aParams[] = SORT_DESC;
			}
		}
		$aParams[] = &$aProcessedData;

		call_user_func_array( 'array_multisort', $aParams );
		return $aProcessedData;
	}

}
