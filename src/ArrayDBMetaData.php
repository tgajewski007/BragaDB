<?php

/**
 * Created on 17 lip 2013 07:25:06
 * @author Tomasz Gajewski
 * @package frontoffice
 * error prefix
 */
namespace braga\db;
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
	public function current(): mixed
	{
		return current($this->columnNumIndexedInfo);
	}
	// -------------------------------------------------------------------------
	public function next(): void
	{
		$this->iteratorIndikator = (next($this->columnNumIndexedInfo) !== false);
	}
	// -------------------------------------------------------------------------
	public function key(): mixed
	{
		return key($this->columnNumIndexedInfo);
	}
	// -------------------------------------------------------------------------
	public function valid(): bool
	{
		return $this->iteratorIndikator;
	}
	// -------------------------------------------------------------------------
	public function rewind(): void
	{
		reset($this->columnNumIndexedInfo);
	}
	// -------------------------------------------------------------------------
	public function getColumnCount()
	{
		return count($this->columnNumIndexedInfo);
	}
	// -------------------------------------------------------------------------
}
?>