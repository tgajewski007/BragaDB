<?php
/**
 * create 29-05-2012 07:48:24
 * @author Tomasz Gajewski
 * @package common
 */
namespace braga\db\mysql;
use braga\db\DataSource;
use braga\tools\html\BaseTags;
use braga\db\DataSourceMetaData;
class DB implements DataSource
{
	// -------------------------------------------------------------------------
	/**
	 *
	 * @var \PDO
	 */
	protected static $connectionObject = null;
	/**
	 *
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
	 *
	 * @var DataSourceMetaData
	 */
	protected $metaData = null;
	/**
	 *
	 * @var boolean
	 */
	protected static $inTransaction = false;
	// -------------------------------------------------------------------------
	function __construct()
	{
		$this->params = array();
	}
	// -------------------------------------------------------------------------
	public function rewind()
	{
		return $this->statement->execute($this->params);
	}
	// -------------------------------------------------------------------------
	/**
	 *
	 * @return boolean
	 */
	public function query($sql)
	{
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
						return true;
					}
					else
					{
						$errors = $this->statement->errorInfo();
						addSQLError($errors[2] . BaseTags::p($this->lastQuery));
					}
				}
			}
		}
		catch(\PDOException $e)
		{
			if($e->getCode() != 'HY000' || !stristr($e->getMessage(), 'server has gone away'))
			{
				$this->presentError($e);
			}
			self::$connectionObject = null;
			self::connect();
			return $this->query($sql);
		}
		catch(\Exception $e)
		{
			$this->presentError($e);
		}
		return false;
	}
	// -------------------------------------------------------------------------
	protected function presentError(\Exception $e)
	{
		if(class_exists("BaseTags"))
		{
			$error = BaseTags::div($e->getMessage());
			$error .= BaseTags::hr("class='ui-state-highlight'");
			$error .= BaseTags::div($this->lastQuery);
			$error .= BaseTags::hr("class='ui-state-highlight'");
			$error .= BaseTags::div(str_replace("\n", BaseTags::br(), var_export($this->params, true)));
			addSQLError($error);
		}
		else
		{
			echo $e->getMessage() . "\n";
			echo $this->lastQuery . "\n";
			var_dump($this->params);
			echo "\n=================================================================================\n";
		}
	}
	// -------------------------------------------------------------------------
	protected function getRecordFound()
	{
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
	 *
	 * @return boolean
	 */
	protected function prepare()
	{
		try
		{
			$this->orginalQuery = $this->lastQuery;
			if(!is_null($this->limit))
			{
				$this->lastQuery .= " LIMIT " . $this->offset . ", " . $this->limit;
			}
			$this->statement = self::$connectionObject->prepare($this->lastQuery, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY ));
			if($this->statement instanceof \PDOStatement)
			{
				return true;
			}
			else
			{
				return true;
			}
		}
		catch(\Exception $e)
		{
			addMsg($e->getMessage());
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
	 *
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
			throw new \Exception("Connecion error");
		}
	}
	// -------------------------------------------------------------------------
	public static function rollback()
	{
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
			throw new \Exception("Connecion error");
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
			throw new \Exception("Connecion error");
		}
	}
	// -------------------------------------------------------------------------
	public function getRowAffected()
	{
		return $this->rowAffected;
	}
	// -------------------------------------------------------------------------
	/**
	 *
	 * @return DataSourceMetaData
	 */
	public function getMetaData()
	{
		return $this->metaData;
	}
	// -------------------------------------------------------------------------
	/**
	 *
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
	 *
	 * @return boolean
	 */
	protected static function connect()
	{
		if(empty(self::$connectionObject))
		{
			$limit = 10;
			$counter = 0;
			while(true)
			{
				try
				{
					self::$connectionObject = new \PDO(DB_CONNECTION_STRING, DB_USER, DB_PASS);
					self::$connectionObject->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
					// self::$connectionObject->setAttribute(\PDO::MYSQL_ATTR_FOUND_ROWS, true);
					self::$connectionObject->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
					self::$connectionObject->setAttribute(\PDO::ATTR_PERSISTENT, true);
					self::$connectionObject->query("SET NAMES utf8 COLLATE 'utf8_polish_ci'");
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