<?php

/**
 * create 29-05-2012 07:48:24
 * @author Tomasz Gajewski
 * @package common
 * error prefix BR:100
 */
namespace braga\db\mysql;
use braga\db\ConnectionConfigurationSetter;
use braga\db\DataSource;
use braga\db\DataSourceMetaData;
use braga\db\exception\GeneralSqlException;
use braga\tools\benchmark\Benchmark;
use braga\tools\exception\BragaException;
use PDO;
use PDOStatement;

class DB implements DataSource
{
	use ConnectionConfigurationSetter;
	// -----------------------------------------------------------------------------------------------------------------
	public const INIT_COMMAND = "SET NAMES utf8 COLLATE 'utf8_polish_ci'";
	// -----------------------------------------------------------------------------------------------------------------
	protected static ?PDO $connectionObject = null;
	protected ?PDOStatement $statement = null;
	protected array $params = [];
	protected ?array $row = null;
	protected ?int $rowAffected = null;
	protected ?string $lastQuery = null;
	protected ?string $orginalQuery = null;
	protected ?int $limit = null;
	protected ?int $offset = null;
	protected ?DataSourceMetaData $metaData = null;
	protected static bool $inTransaction = false;
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
			self::connect();
			$this->prepare();
			if($this->rewind())
			{
				Benchmark::add(__METHOD__ . "_END");
			}
			else
			{
				$errors = $this->statement->errorInfo();
				throw new GeneralSqlException($this, $errors, 100001);
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
				return new BragaException("BR:10001 Dane są zablokowane do edycji, spróbuj ponownie za chwilę", 10001);
			case "SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock; try restarting transaction":
				return new \RuntimeException("BR:10002 Dane są zablokowane do edycji, spróbuj ponownie za chwilę", 10002);
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
	 * @return void
	 * @throws GeneralSqlException
	 */
	protected function prepare()
	{
		Benchmark::add(__METHOD__);
		$this->orginalQuery = $this->lastQuery;
		if(!is_null($this->limit))
		{
			$this->lastQuery .= " LIMIT " . $this->offset . ", " . $this->limit;
		}
		$this->statement = self::$connectionObject->prepare($this->lastQuery, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		if($this->statement === false)
		{
			throw new GeneralSqlException($this, "BR:10003 Błąd przygotowania zapytania SQL", 10003);
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
		$this->row = $this->statement->fetch();
		return $this->row !== false;
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
			$this->params = [];
		}
		$this->params[":" . $name] = $value;
	}
	// -------------------------------------------------------------------------
	public static function commit()
	{
		Benchmark::add(__METHOD__);
		self::connect();
		if(self::$inTransaction)
		{
			self::$inTransaction = false;
			if(!self::$connectionObject->commit())
			{
				throw new BragaException("BR:10004 Błąd wycofania transakcji", 10004);
			}
		}
		else
		{
			throw new BragaException("BR:10005 Zatwierdzenie transakcji na nierozpoczętej transakcji", 10005);
		}
	}
	// -------------------------------------------------------------------------
	public static function rollback()
	{
		Benchmark::add(__METHOD__);
		self::connect();
		if(self::$inTransaction)
		{
			self::$inTransaction = false;
			if(!self::$connectionObject->rollback())
			{
				throw new BragaException("BR:10006 Błąd wycofania transakcji", 10006);
			}
		}
		else
		{
			throw new BragaException("BR:10007 Wycofanie transakcji na nierozpoczętej transakcji", 10007);
		}
	}
	// -------------------------------------------------------------------------
	public static function startTransaction()
	{
		self::connect();
		if(!self::$inTransaction)
		{
			if(!self::$connectionObject->beginTransaction())
			{
				throw new BragaException("BR:10008 Rozpoczęcie transakcji nie powiodło się", 10008);
			}
			self::$inTransaction = true;
		}
	}
	// -------------------------------------------------------------------------
	public function getRowAffected()
	{
		if(is_null($this->rowAffected))
		{
			if(strtoupper(substr($this->lastQuery, 0, 1)) == "S")
			{
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
		}
		return $this->rowAffected;
	}
	// -------------------------------------------------------------------------
	/**
	 * @return DataSourceMetaData
	 */
	public function getMetaData()
	{
		if(is_null($this->metaData))
		{
			$this->setMetaData();
		}
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
	 * @return void
	 * @throws \Exception
	 */
	protected static function connect()
	{
		Benchmark::add(__METHOD__);
		if(empty(self::$connectionObject))
		{
			$limit = 3;
			$counter = 0;
			while(true)
			{
				try
				{
					self::$connectionObject = new PDO(self::getConnectionConfigration()->getConnectionString(), self::getConnectionConfigration()->getUserName(), self::getConnectionConfigration()->getPassword());
					self::$connectionObject->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					self::$connectionObject->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
					self::$connectionObject->setAttribute(PDO::ATTR_PERSISTENT, true);
					self::$connectionObject->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, self::getConnectionConfigration()->getInitCommand() ?? self::INIT_COMMAND);
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
