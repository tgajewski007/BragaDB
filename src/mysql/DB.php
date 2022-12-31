<?php

/**
 * create 29-05-2012 07:48:24
 * @author Tomasz Gajewski
 * @package common
 */
namespace braga\db\mysql;
use braga\db\ConnectionConfigurationSetter;
use braga\db\DataSource;
use braga\db\DataSourceMetaData;
use braga\db\exception\GeneralSqlException;
use braga\tools\benchmark\Benchmark;
use braga\tools\tools\JsonSerializer;
class DB implements DataSource
{
	use ConnectionConfigurationSetter;
	// -----------------------------------------------------------------------------------------------------------------
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
	// -----------------------------------------------------------------------------------------------------------------
	function __construct()
	{
		$this->params = array();
	}
	// -----------------------------------------------------------------------------------------------------------------
	public function rewind()
	{
		Benchmark::add(__METHOD__);
		return $this->statement->execute($this->params);
	}
	// -----------------------------------------------------------------------------------------------------------------
	public function query($sql)
	{
		$context = [];
		$context["sql"] = $sql;
		$context["param"] = $this->params;
		Benchmark::add(__METHOD__, $context);
		$this->lastQuery = $sql;
		try
		{
			if(self::connect())
			{
				if($this->prepare())
				{
					if($this->rewind())
					{
						if(strtoupper(substr($this->lastQuery, 0, 1)) == "S")
						{
							$this->setMetaData();
							$this->rowAffected = $this->getRecordFound();
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
						Benchmark::add(__METHOD__ . "_END");
					}
					else
					{
						$errors = $this->statement->errorInfo();
						throw new GeneralSqlException($this, $errors, 100001);
					}
				}
			}
		}
		catch(\PDOException $e)
		{
			if(!self::$inTransaction)
			{
				if($e->getCode() == 'HY000' && stristr($e->getMessage(), 'server has gone away'))
				{
					self::$connectionObject = null;
					self::connect();
					$this->query($sql);
				}
			}
			throw $this->translateException($e);
		}
	}
	// -------------------------------------------------------------------------
	protected function translateException(\Throwable $e)
	{
		switch($e->getMessage())
		{
			case "SQLSTATE[HY000]: General error: 1205 Lock wait timeout exceeded; try restarting transaction":
				return new \RuntimeException("BR:1205 Dane są zablokowane do edycji, spróbuj ponownie za chwilę", 1205);
			case "SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock; try restarting transaction":
				return new \RuntimeException("BR:1213 Dane są zablokowane do edycji, spróbuj ponownie za chwilę", 1213);
			default :
				return $e;
		}
	}
	// -------------------------------------------------------------------------
	protected function getRecordFound()
	{
		Benchmark::add(__METHOD__);
		$sql = "SELECT FOUND_ROWS()";
		$rs = self::$connectionObject->query($sql);
		$retval = (int)$rs->fetchColumn();
		return $retval;
	}
	// -------------------------------------------------------------------------
	protected function setMetaData()
	{
		$this->metaData = new MySQLMetaData($this->statement);
	}
	// -------------------------------------------------------------------------
	/**
	 * @return boolean
	 */
	protected function prepare()
	{
		Benchmark::add(__METHOD__);
		$this->orginalQuery = $this->lastQuery;
		if(!is_null($this->limit))
		{
			$this->lastQuery .= " LIMIT " . $this->offset . ", " . $this->limit;
		}
		$this->statement = self::$connectionObject->prepare($this->lastQuery, array(
			\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
		if($this->statement instanceof \PDOStatement)
		{
			return true;
		}
		else
		{
			return true;
		}
	}
	// ------------------------------------------------------------------------
	public function setLimit($offset, $limit = null)
	{
		$this->offset = intval($offset);
		$this->limit = is_null($limit) ? $limit : intval($limit);
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
		Benchmark::add(__METHOD__);
		if(self::connect())
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
		else
		{
			throw new \Exception("Connecion error", 100002);
		}
	}
	// -------------------------------------------------------------------------
	public static function rollback()
	{
		Benchmark::add(__METHOD__);
		if(self::connect())
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
		else
		{
			throw new \Exception("Connecion error", 100003);
		}
	}
	// -------------------------------------------------------------------------
	public static function startTransaction()
	{
		if(self::connect())
		{
			if(!self::$inTransaction)
			{
				self::$connectionObject->beginTransaction();
				self::$inTransaction = true;
			}
		}
		else
		{
			throw new \Exception("Connection error", 100004);
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
	protected static function connect()
	{
		Benchmark::add(__METHOD__);
		if(empty(self::$connectionObject))
		{
			$limit = 10;
			$counter = 0;
			while(true)
			{
				try
				{
					self::$connectionObject = new \PDO(self::getConnectionConfigration()->getConnectionString(), self::getConnectionConfigration()->getUserName(), self::getConnectionConfigration()->getPassword());
					self::$connectionObject->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
					// self::$connectionObject->setAttribute(\PDO::MYSQL_ATTR_FOUND_ROWS, true);
					self::$connectionObject->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
					self::$connectionObject->setAttribute(\PDO::ATTR_PERSISTENT, true);
					self::$connectionObject->setAttribute(\PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES utf8 COLLATE 'utf8_polish_ci'");
					break;
				}
				catch(\Exception $e)
				{
					self::$connectionObject = null;
					sleep(1);
					$counter++;
					if($counter >= $limit)
					{
						throw $e;
					}
				}
			}
		}

		return true;
	}
	// ------------------------------------------------------------------------
	public function count(): int
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