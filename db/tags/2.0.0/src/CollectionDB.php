<?php

/**
 * Created on 18-04-2011 12:56:04
 * @author Tomasz.Gajewski
 * @package common
 * error prefix
 */
namespace braga\db;
class CollectionDB implements DataSource
{
	// -------------------------------------------------------------------------
	/**
	 *
	 * @var Collection
	 */
	protected $arrayOfObjects;
	protected $translate;
	/**
	 *
	 * @var ArrayDBMetaData
	 */
	protected $metaData;
	// -------------------------------------------------------------------------
	function __construct(Collection $obj)
	{
		$this->arrayOfObjects = $obj;
		$this->metaData = new ArrayDBMetaData();
	}
	// -------------------------------------------------------------------------
	public function getMetaData()
	{
		return $this->metaData;
	}
	// -------------------------------------------------------------------------
	public function addTranslate($functionName, $index, $desc = null, $type = "varchar")
	{
		$this->translate[$index] = $functionName;

		$meta = new DataSourceColumnMetaData();
		if(null == $desc)
		{
			$meta->setName($functionName);
		}
		else
		{
			$meta->setName($desc);
		}
		$meta->setType($type);
		$meta->setNumIndex($index);
		$this->metaData->addColumn($meta);
	}
	// -------------------------------------------------------------------------
	public function f($index)
	{
		$retval = null;
		if(isset($this->translate[$index]))
		{
			if(is_array($this->translate[$index]))
			{
				$obj = $this->arrayOfObjects->current();
				$retval = call_user_func_array(array(
						$obj,
						$this->translate[$index][0]), $this->translate[$index][1]);
			}
			else
			{
				$obj = $this->arrayOfObjects->current();
				$executePath = explode(".", $this->translate[$index]);
				foreach($executePath as $functionName)
				{
					$obj = call_user_func(array(
							$obj,
							$functionName));
				}
				$retval = $obj;
			}
		}
		return $retval;
	}
	// -------------------------------------------------------------------------
	public function getCount()
	{
		return $this->arrayOfObjects->count();
	}
	// -------------------------------------------------------------------------
	public function nextRecord()
	{
		$this->arrayOfObjects->next();
		return $this->arrayOfObjects->valid();
	}
	// -------------------------------------------------------------------------
	public function rewind()
	{
		$this->arrayOfObjects->rewind();
	}
	// -------------------------------------------------------------------------
	public function query($sql)
	{
		return false;
	}
	// -------------------------------------------------------------------------
	public function setParam($name, $val)
	{
		return false;
	}
	// -------------------------------------------------------------------------
	public function getRowAffected()
	{
		return 0;
	}
	// -------------------------------------------------------------------------
	public static function commit()
	{
		return false;
	}
	// -------------------------------------------------------------------------
	public static function rollback()
	{
		return false;
	}
	// -------------------------------------------------------------------------
	public function count()
	{
		return $this->arrayOfObjects->count();
	}
	// -------------------------------------------------------------------------
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \braga\db\DataSource::startTransaction()
	 */
	public static function startTransaction()
	{
		return false;
	}
	// -------------------------------------------------------------------------
}
?>