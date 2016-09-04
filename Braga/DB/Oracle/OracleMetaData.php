<?php
/**
 * Created on 16 lip 2013 08:20:15
 * @author Tomasz Gajewski
 * @package frontoffice
 * error prefix
 */
namespace Braga;
class OracleMetaData implements DataSourceMetaData
{
	// -------------------------------------------------------------------------
	protected $columnNumIndexedInfo = array();
	protected $columnNameIndexedInfo = array();
	protected $columnCount = 0;
	// -------------------------------------------------------------------------
	protected $iteratorIndikator = true;
	// -------------------------------------------------------------------------
	function __construct($statment)
	{
		$this->columnCount = oci_num_fields($statment);
		for($i = 0; $i < $this->columnCount; $i++)
		{
			$col = new DataSourceColumnMetaData();
			$col->setName(oci_field_name($statment, $i + 1));
			$col->setLength(oci_field_size($statment, $i + 1));
			
			switch(oci_field_type($statment, $i + 1))
			{
				case "TIMESTAMP":
				case "DATE":
					$col->setType("date");
					break;
				case "NUMBER":
					$col->setType("int");
					break;
				default :
					$col->setType("varchar");
					break;
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