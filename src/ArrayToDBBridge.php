<?php

/**
 * Created on 18-04-2011 12:56:04
 * @author Tomasz.Gajewski
 * @package common
 * error prefix
 */
namespace braga\db;
class ArrayToDBBridge implements DataSource
{
	// -------------------------------------------------------------------------
	protected $arrayOfObjects;
	protected $translate;
	protected $firstNextRec = false;
	/**
	 * @var ArrayDBMetaData
	 */
	protected $metaData;
	// -------------------------------------------------------------------------
	function __construct(Array $obj)
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
	public function addTranslate($functionName, $index, $desc = null, $type = "text")
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
			if(is_callable($this->translate[$index]))
			{
				$obj = current($this->arrayOfObjects);
				return $this->translate[$index]($obj);
			}
			else
			{
				if(is_array($this->translate[$index]))
				{
					$obj = current($this->arrayOfObjects);
					$retval = call_user_func_array(array(
									$obj,
									$this->translate[$index][0] ), $this->translate[$index][1]);
				}
				else
				{
					$obj = current($this->arrayOfObjects);
					$executePath = explode(".", $this->translate[$index]);
					foreach($executePath as $functionName)
					{
						$obj = call_user_func(array(
										$obj,
										$functionName ));
					}
					$retval = $obj;
				}
			}
		}
		return $retval;
	}
	// -------------------------------------------------------------------------
	public function getCount()
	{
		return count($this->arrayOfObjects);
	}
	// -------------------------------------------------------------------------
	public function nextRecord()
	{
		if(!$this->firstNextRec)
		{
			$this->firstNextRec = true;
			if(0 == count($this->arrayOfObjects))
			{
				return false;
			}
			else
			{
				return true;
			}
		}
		else
		{
			if(next($this->arrayOfObjects) === false)
			{
				return false;
			}
			else
			{
				return true;
			}
		}
	}
	// -------------------------------------------------------------------------
	public function rewind()
	{
		reset($this->arrayOfObjects);
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
		return count($this->arrayOfObjects);
	}
	// -------------------------------------------------------------------------
	/**
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