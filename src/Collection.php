<?php

/**
 * Created on 24-03-2013 14:32:31
 * author Tomasz Gajewski
 * package frontoffice
 * error prefix
 */
namespace braga\db;
class Collection implements \Iterator, \Countable
{
	// -------------------------------------------------------------------------
	/**
	 *
	 * @var DataSource
	 */
	protected $database = null;
	/**
	 *
	 * @var DAO
	 */
	protected $prototype = null;
	/**
	 *
	 * @var DAO
	 */
	protected $currentObj = null;
	protected $inited = false;
	// -------------------------------------------------------------------------
	function __construct(DataSource $db, DAO $prototype)
	{
		$this->database = $db;
		$this->prototype = $prototype;
	}
	// -------------------------------------------------------------------------
	protected function materialize()
	{
		$this->currentObj = $this->prototype->getByDataSource($this->database);
	}
	// -------------------------------------------------------------------------
	public function next()
	{
		$this->inited = true;
		if($this->database->nextRecord())
		{
			$this->materialize();
		}
		else
		{
			$this->currentObj = null;
		}
	}
	// -------------------------------------------------------------------------
	public function current()
	{
		if(!$this->inited)
		{
			$this->next();
		}
		return $this->currentObj;
	}
	// -------------------------------------------------------------------------
	public function key()
	{
		return $this->currentObj->getKey();
	}
	// -------------------------------------------------------------------------
	public function valid()
	{
		return !is_null($this->currentObj);
	}
	// -------------------------------------------------------------------------
	public function rewind()
	{
		$this->inited = false;
		$this->database->rewind();
		$this->next();
	}
	// -------------------------------------------------------------------------
	public function count()
	{
		return $this->database->count();
	}
	// -------------------------------------------------------------------------
}
?>