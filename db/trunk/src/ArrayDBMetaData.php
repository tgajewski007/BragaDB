<?php

/**
 * Created on 17 lip 2013 07:25:06
 * @author Tomasz Gajewski
 * @package frontoffice
 * error prefix
 */
namespace Braga\DB;
class ArrayDBMetaData implements DataSourceMetaData
{
	// -------------------------------------------------------------------------
	protected $columnNumIndexedInfo = array();
	protected $columnNameIndexedInfo = array();
	// -------------------------------------------------------------------------
	protected $iteratorIndikator = true;
	// -------------------------------------------------------------------------
	public function addColumn(DataSourceColumnMetaData $colMetaData)
	{
		$this->columnNameIndexedInfo[$colMetaData->getName()] = $colMetaData;
		$this->columnNumIndexedInfo[$colMetaData->getNumIndex()] = $colMetaData;
	}
	// -------------------------------------------------------------------------
	public function get($index)
	{
		if(isset($this->columnNumIndexedInfo[$index]))
		{
			return $this->columnNumIndexedInfo[$index];
		}
		elseif(isset($this->columnNameIndexedInfo[$index]))
		{
			return $this->columnNameIndexedInfo[$index];
		}
		else
		{
			return new DataSourceColumnMetaData();
		}
	}
	// -------------------------------------------------------------------------
	public function current()
	{
		return current($this->columnNumIndexedInfo);
	}
	// -------------------------------------------------------------------------
	public function next()
	{
		$this->iteratorIndikator = next($this->columnNumIndexedInfo);
		return $this->iteratorIndikator;
	}
	// -------------------------------------------------------------------------
	public function key()
	{
		return key($this->columnNumIndexedInfo);
	}
	// -------------------------------------------------------------------------
	public function valid()
	{
		return $this->iteratorIndikator;
	}
	// -------------------------------------------------------------------------
	public function rewind()
	{
		return reset($this->columnNumIndexedInfo);
	}
	// -------------------------------------------------------------------------
	public function getColumnCount()
	{
		return count($this->columnNumIndexedInfo);
	}
	// -------------------------------------------------------------------------
}
?>