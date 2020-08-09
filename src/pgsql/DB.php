<?php

namespace braga\db\pgsql;

use braga\db\DataSource;
use braga\db\DataSourceMetaData;
use braga\db\exception\GeneralSqlException;

/**
 * create 29-05-2012 07:48:24
 * @author Tomasz Gajewski
 * @package common
 */
class DB implements DataSource
{
	// -------------------------------------------------------------------------
	/**
	 * @var \PDO
	 */
	protected static $connectionObject = null;
	/**
	 * @var \PDOStatement
	 */
	protected $statement = null;
	protected $params = null;
	protected $row = null;
	protected $rowAffected = -1;
	protected $lastQuery = null;
	protected $orginalQuery = null;
	protected $limit = null;
	protected $offset = null;
	/**
	 * @var DataSourceMetaData
	 */
	protected $metaData = null;
	/**
	 * @var boolean
	 */
	protected static $inTransaction = false;
	// -------------------------------------------------------------------------
	function __construct($transaction = true)
	{
		$this->transaction = $transaction;
		$this->params = array();
	}
	// -------------------------------------------------------------------------
	public function rewind()
	{
		return $this->statement->execute($this->params);
	}
	// -------------------------------------------------------------------------
	/**
	 * @return boolean
	 */
	public function query($sql)
	{
		$this->lastQuery = $sql;

		if($this->connect())
		{
			if($this->prepare())
			{
				if($this->rewind())
				{
					if(strtoupper(substr($this->lastQuery, 0, 1)) == "S")
					{
						$this->setMetaData();
						$this->rowAffected = $this->statement->rowCount();
					}
					else
					{
						if($this->statement->rowCount() == 0)
						{
							$this->rowAffected = 1;
						}
						else
						{
							$this->rowAffected = $this->statement->rowCount();
						}
					}
					return true;
				}
				else
				{
					$errors = $this->statement->errorInfo();
					throw new GeneralSqlException($this, $errors, 100002);
				}
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}
	// -------------------------------------------------------------------------
	protected function getRecordFound()
	{
		return $this->rowAffected;
	}
	// -------------------------------------------------------------------------
	protected function setMetaData()
	{
		$this->metaData = new PostgreMetaData($this->statement);
	}
	// -------------------------------------------------------------------------
	/**
	 * @return boolean
	 */
	protected function prepare()
	{
		$this->orginalQuery = $this->lastQuery;
		if(!is_null($this->limit))
		{
			$this->lastQuery .= " LIMIT " . $this->limit . " OFFSET " . $this->offset;
		}
		$this->statement = self::$connectionObject->prepare($this->lastQuery, array(
						\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY ));
		if($this->statement instanceof \PDOStatement)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	// -------------------------------------------------------------------------
	public function setLimit($offset, $limit = PAGELIMIT)
	{
		$this->offset = intval($offset);
		$this->limit = intval($limit);
	}
	// -------------------------------------------------------------------------
	/**
	 * @return boolean
	 */
	public function nextRecord()
	{
		$this->row = $this->statement->fetch(\PDO::FETCH_BOTH);
		if($this->row !== false)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	// -------------------------------------------------------------------------
	public function f($fieldIndex)
	{
		if(isset($this->row[$fieldIndex]))
		{
			return $this->row[$fieldIndex];
		}
		else
		{
			return null;
		}
	}
	// -------------------------------------------------------------------------
	public function setParam($name, $value, $clear = false)
	{
		if($clear)
		{
			$this->params = array();
		}
		$this->params[":" . $name] = $value;
	}
	// -------------------------------------------------------------------------
	public static function commit()
	{
		if(self::$inTransaction)
		{
			self::$inTransaction = false;
			return self::$connectionObject->commit();
		}
		else
		{
			return true;
		}
	}
	// -------------------------------------------------------------------------
	public static function rollback()
	{
		if(self::$inTransaction)
		{
			self::$inTransaction = false;
			return self::$connectionObject->rollback();
		}
		else
		{
			return true;
		}
	}
	// -------------------------------------------------------------------------
	public static function startTransaction()
	{
		if(!self::$inTransaction)
		{
			self::$connectionObject->beginTransaction();
			self::$inTransaction = true;
		}
	}
	// -------------------------------------------------------------------------
	public function getRowAffected()
	{
		return $this->rowAffected;
	}
	// -------------------------------------------------------------------------
	/**
	 * @return DataSourceMetaData
	 */
	public function getMetaData()
	{
		return $this->metaData;
	}
	// -------------------------------------------------------------------------
	/**
	 * @return int
	 */
	public function getLastInsertID()
	{
		return self::$connectionObject->lastInsertId();
	}
	// -------------------------------------------------------------------------
	public function setFetchMode($fetchMode)
	{
		$this->fetchMode = $fetchMode;
	}
	// -------------------------------------------------------------------------
	/**
	 * @return boolean
	 */
	protected function connect()
	{
		if(empty(self::$connectionObject))
		{
			self::$connectionObject = new \PDO(DB_CONNECTION_STRING, DB_USER, DB_PASS);
			self::$connectionObject->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			self::$connectionObject->query("SET NAMES 'UTF8'");
		}
		return true;
	}
	// ------------------------------------------------------------------------
	public function count()
	{
		return $this->getRowAffected();
	}
	// -------------------------------------------------------------------------
	static function getParameName($length = 8)
	{
		return "P" . strtoupper(getRandomStringLetterOnly($length));
	}
	// -------------------------------------------------------------------------
}
?>