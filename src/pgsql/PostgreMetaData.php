<?php
namespace braga\db\pgsql;
use braga\db\DataSourceColumnMetaData;
use braga\db\DataSourceMetaData;

/**
 * Created on 16 lip 2013 08:20:15
 * @author Tomasz Gajewski
 * @package frontoffice
 * error prefix
 */
class PostgreMetaData implements DataSourceMetaData
{
	// -------------------------------------------------------------------------
	protected $columnNumIndexedInfo = array();
	protected $columnNameIndexedInfo = array();
	protected $columnCount = 0;
	// -------------------------------------------------------------------------
	protected $iteratorIndikator = true;
	// -------------------------------------------------------------------------
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
		return $this->columnCount;
	}
	// -------------------------------------------------------------------------
}
?>