<?php
/**
 * Created on 16 lip 2013 08:20:15
 * @author Tomasz Gajewski
 * @package frontoffice
 * error prefix
 */
namespace braga\db\mysql;
use braga\db\DataSourceColumnMetaData;
use braga\db\DataSourceMetaData;
class MySQLMetaData implements DataSourceMetaData
{
	// -----------------------------------------------------------------------------------------------------------------
	protected $columnNumIndexedInfo = array();
	protected $columnNameIndexedInfo = array();
	protected $columnCount = 0;
	// -----------------------------------------------------------------------------------------------------------------
	protected $iteratorIndikator = true;
	// -----------------------------------------------------------------------------------------------------------------
	public function __construct(\PDOStatement $stm)
	{
		$this->columnCount = $stm->columnCount();
		for($i = 0; $i < $stm->columnCount(); $i++)
		{
			$tmp = $stm->getColumnMeta($i);
			$col = new DataSourceColumnMetaData();
			$col->setName($tmp["name"]);
			$col->setLength($tmp["len"]);
			// $col->setType($tmp["native_type"]);
			if(isset($tmp["native_type"]))
			{
				switch($tmp["native_type"])
				{
					case "TIMESTAMP":
					case "DATE":
						$col->setType("date");
						break;
					case "LONGLONG":
					case "DOUBLE":
					case "NEWDECIMAL":
						$col->setType("int");
						break;
					default :
						$col->setType("varchar");
						break;
				}
			}
			else
			{
				$col->setType("varchar");
			}
			$col->setNumIndex($i);
			$this->columnNumIndexedInfo[$i] = $col;
			$this->columnNameIndexedInfo[$col->getName()] = $col;
		}
	}
	// -----------------------------------------------------------------------------------------------------------------
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
	// -----------------------------------------------------------------------------------------------------------------
	public function current(): mixed
	{
		return current($this->columnNumIndexedInfo);
	}
	// -----------------------------------------------------------------------------------------------------------------
	public function next(): void
	{
		$this->iteratorIndikator = next($this->columnNumIndexedInfo) !== false;
	}
	// -----------------------------------------------------------------------------------------------------------------
	public function key(): mixed
	{
		return key($this->columnNumIndexedInfo);
	}
	// -----------------------------------------------------------------------------------------------------------------
	public function valid(): bool
	{
		return $this->iteratorIndikator;
	}
	// -----------------------------------------------------------------------------------------------------------------
	public function rewind(): void
	{
		reset($this->columnNumIndexedInfo);
	}
	// -----------------------------------------------------------------------------------------------------------------
	public function getColumnCount()
	{
		return $this->columnCount;
	}
	// -----------------------------------------------------------------------------------------------------------------
}
