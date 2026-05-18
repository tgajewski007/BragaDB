<?php
namespace braga\db\pgsql;
use braga\db\ConnectionConfigurationSetter;
use braga\db\DataSource;
use braga\db\DataSourceMetaData;
use braga\db\exception\GeneralSqlException;
use braga\tools\benchmark\Benchmark;
use braga\tools\exception\BragaException;
use PDO;
use PDOException;
use Throwable;

/**
 * create 29-05-2012 07:48:24
 * @author Tomasz Gajewski
 * @package common
 */
class DB implements DataSource
{
	use ConnectionConfigurationSetter;
	// -----------------------------------------------------------------------------------------------------------------
	protected static ?PDO $connectionObject = null;
	protected ?\PDOStatement $statement = null;
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
				$this->setRowAffected();
			}
			else
			{
				$errors = $this->statement->errorInfo();
				throw new GeneralSqlException($this, $errors, 100001);
			}
		}
		catch(PDOException $e)
		{
			throw $this->translateException($e);
		}
	}
	// -----------------------------------------------------------------------------------------------------------------
	protected function translateException(PDOException $e): Throwable
	{
		switch($e->getCode())
		{
			// deadlock_detected
			case '40P01':
				return new \RuntimeException(
					"BR:20001 Wykryto deadlock PostgreSQL, spróbuj ponownie",
					20001,
					$e
				);

			// serialization_failure
			case '40001':
				return new \RuntimeException(
					"BR:20002 Konflikt transakcji PostgreSQL, spróbuj ponownie",
					20002,
					$e
				);

			// lock_not_available
			case '55P03':
				return new \RuntimeException(
					"BR:20003 Dane są aktualnie zablokowane",
					20003,
					$e
				);

			// unique_violation
			case '23505':
				return new \RuntimeException(
					"BR:20004 Naruszenie unikalności danych",
					20004,
					$e
				);

			// foreign_key_violation
			case '23503':
				return new \RuntimeException(
					"BR:20005 Naruszenie integralności relacji",
					20005,
					$e
				);

			default:
				return $e;
		}
	}
	// -----------------------------------------------------------------------------------------------------------------
	private function setRowAffected(): void
	{
		$this->rowAffected = $this->statement->rowCount();
	}
	// -----------------------------------------------------------------------------------------------------------------
	protected function getRecordFound()
	{
		return $this->rowAffected;
	}
	// -----------------------------------------------------------------------------------------------------------------
	protected function setMetaData()
	{
		$this->metaData = new PostgreMetaData($this->statement);
	}
	// -----------------------------------------------------------------------------------------------------------------
	protected function prepare()
	{
		Benchmark::add(__METHOD__);
		$this->orginalQuery = $this->lastQuery;
		if(!is_null($this->limit))
		{
			$this->lastQuery .= " LIMIT {$this->limit} OFFSET {$this->offset}";
		}

		$this->statement = self::$connectionObject->prepare($this->lastQuery, [ PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY ]);

		if($this->statement === false)
		{
			throw new GeneralSqlException($this, "BR:10103 Błąd przygotowania zapytania SQL", 10103);
		}
	}
	// -----------------------------------------------------------------------------------------------------------------
	public function setLimit($offset, $limit = null)
	{
		$this->offset = intval($offset);
		$this->limit = is_null($limit) ? $limit : intval($limit);
	}
	// -----------------------------------------------------------------------------------------------------------------
	/**
	 * @return boolean
	 */
	public function nextRecord()
	{
		$tmp = $this->statement->fetch();
		if($tmp === false)
		{
			return false;
		}
		else
		{
			$this->row = $tmp;
			return true;
		}
	}
	// -----------------------------------------------------------------------------------------------------------------
	public function f($index)
	{
		if(isset($this->row[$index]))
		{
			return $this->row[$index];
		}
		else
		{
			return null;
		}
	}    // -----------------------------------------------------------------------------------------------------------------
	/**
	 * @param $name
	 * @param $value
	 * @param int $type
	 * @return void
	 */
	public function setParam($name, $value, int $type = PDO::PARAM_STR)
	{
		$this->params[":" . $name] = [ "value" => $value, "type" => $type ];
	}
	// -----------------------------------------------------------------------------------------------------------------
	public static function commit()
	{
		Benchmark::add(__METHOD__);
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
	// -----------------------------------------------------------------------------------------------------------------
	public static function rollback()
	{
		Benchmark::add(__METHOD__);
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
	// -----------------------------------------------------------------------------------------------------------------
	public static function startTransaction()
	{
		self::connect();
		if(!self::$inTransaction)
		{
			if(!self::$connectionObject->beginTransaction())
			{
				throw new BragaException("BR:10108 Rozpoczęcie transakcji nie powiodło się", 10108);
			}
			self::$inTransaction = true;
		}
	}
	// -----------------------------------------------------------------------------------------------------------------
	public function getRowAffected()
	{
		return $this->rowAffected;
	}
	// -----------------------------------------------------------------------------------------------------------------
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
	// -----------------------------------------------------------------------------------------------------------------
	/**
	 * @return int
	 */
	public function getLastInsertID()
	{
		return self::$connectionObject->lastInsertId();
	}
	// -----------------------------------------------------------------------------------------------------------------
	protected static function connect()
	{
		if(empty(self::$connectionObject))
		{
			self::$connectionObject = new PDO(
				self::getConnectionConfigration()->getConnectionString(),
				self::getConnectionConfigration()->getUserName(),
				self::getConnectionConfigration()->getPassword()
			);

			self::$connectionObject->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			self::$connectionObject->setAttribute(PDO::ATTR_PERSISTENT, true);

			if(self::getConnectionConfigration()->getInitCommand())
			{
				self::$connectionObject->exec(
					self::getConnectionConfigration()->getInitCommand()
				);
			}
		}
	}
	// ------------------------------------------------------------------------
	public function count(): int
	{
		return $this->getRowAffected();
	}
	// -----------------------------------------------------------------------------------------------------------------
	static function getParameName($length = 8)
	{
		return "P" . strtoupper(getRandomStringLetterOnly($length));
	}
	// -----------------------------------------------------------------------------------------------------------------
	public function getRow()
	{
		return $this->row;
	}
	// -----------------------------------------------------------------------------------------------------------------
	public static function isTransactionStarted(): bool
	{
		return self::$inTransaction;
	}
	// -----------------------------------------------------------------------------------------------------------------
}
