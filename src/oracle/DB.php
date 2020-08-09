<?php

/**
 * @package common
 * @author Tomasz.Gajewski
 * Created on 2006-03-27
 * klasa zapewniająca łączność z bazą danych Oracle
 * error prefix EN:016
 */
namespace braga\db\oracle;

use braga\db\DataSource;
use braga\db\DataSourceMetaData;
use braga\db\exception\GeneralSqlException;

class DB implements DataSource
{
	// -------------------------------------------------------------------------
	protected $serwer;
	protected $port;
	protected $sid;
	protected $userName = DB_USER;
	protected $password = DB_PASS;
	protected $database = DB_CONNECTION_STRING;
	// -------------------------------------------------------------------------
	static $paramCount = 0;
	// -------------------------------------------------------------------------
	protected static $connectionObiect;
	protected $error = "";
	protected $trasaction = OCI_COMMIT_ON_SUCCESS;
	protected $statement = null;
	/**
	 * @var OracleParams
	 */
	protected $params = null;
	protected $row = null;
	protected $rowAffected = -1;
	protected $lastQuery = null;
	protected $orginalQuery = null;
	protected $limit = null;
	protected $offset = null;
	protected $fetchMode = null;
	/**
	 * @var DataSourceMetaData
	 */
	protected $metaData = null;
	/**
	 * @var boolean
	 */
	protected static $inTransaction = false;
	// -------------------------------------------------------------------------
	public function __construct()
	{
		$this->fetchMode = OCI_BOTH + OCI_RETURN_NULLS;
		$this->params = new OracleParams();
		$this->trasaction = OCI_COMMIT_ON_SUCCESS;
	}
	// -------------------------------------------------------------------------
	public function rewind()
	{
		if($this->params->haveBlob())
		{
			// zapytanie z BLOBAMI
			if(oci_execute($this->statement, OCI_DEFAULT))
			{
				$this->params->loadBlobData();
				if(OCI_COMMIT_ON_SUCCESS == $this->trasaction)
				{
					$this->commit();
				}
				return true;
			}
			else
			{
				$this->saveErrors("Błąd wykonania");
				return false;
			}
		}
		else
		{
			// zwykłe zapytanie
			if(oci_execute($this->statement, $this->trasaction))
			{
				return true;
			}
			else
			{
				$this->saveErrors("Błąd wykonania");
				return false;
			}
		}
	}
	// -------------------------------------------------------------------------
	/**
	 * query
	 * Funkcja wykonuje właściwe zapytanie do bazy danych
	 * @return bool true jeżeli ok false jeżeli zapytanie kończy się błędem
	 * @var string $sql zapytania SQL
	 */
	public function query($sql)
	{
		$this->lastQuery = $sql;
		if(self::$inTransaction)
		{
			$this->trasaction = OCI_DEFAULT;
		}
		if($this->connect())
		{
			if($this->prepare())
			{
				if($this->rewind())
				{
					if(strtoupper(substr($this->lastQuery, 0, 1)) == "S")
					{
						$this->setMetaData();
					}
					else
					{
						$this->rowAffected = oci_num_rows($this->statement);
					}
					return true;
				}
				else
				{
					$this->saveErrors("Błąd wykonania");
					return false;
				}
			}
			else
			{
				$this->saveErrors("Błąd wykonania");
				return false;
			}
		}
		else
		{
			return false;
		}
	}
	// -------------------------------------------------------------------------
	protected function setMetaData()
	{
		$this->metaData = new OracleMetaData($this->statement);
	}
	// -------------------------------------------------------------------------
	/**
	 * @return boolean
	 */
	protected function prepare()
	{
		try
		{
			$this->orginalQuery = $this->lastQuery;
			if(!is_null($this->limit))
			{
				$this->lastQuery = "SELECT * FROM (SELECT regular.*, ROWNUM db_numer_recordu FROM (" . $this->lastQuery . ") regular ) WHERE db_numer_recordu BETWEEN :REC_LIMIT_FROM AND :REC_LIMIT_TO ";
				$this->setParam("REC_LIMIT_FROM", $this->offset + 1);
				$this->setParam("REC_LIMIT_TO", $this->offset + $this->limit);
			}
			$this->statement = oci_parse($this->getConnectionObject(), $this->lastQuery);
			if($this->statement === false)
			{
				return false;
			}
			else
			{
				$this->addParam();
				return true;
			}
		}
		catch(\Exception $e)
		{
			$this->saveErrors($e->getMessage());
			return false;
		}
	}
	// -------------------------------------------------------------------------
	public function setLimit($arg1, $arg2 = null)
	{
		if(null == $arg2)
		{
			$this->limit = intval($arg1);
			$this->offset = 0;
		}
		else
		{
			$this->limit = intval($arg2);
			$this->offset = intval($arg1);
		}
	}
	// -------------------------------------------------------------------------
	public function nextRecord()
	{
		if($this->connect())
		{
			$this->row = oci_fetch_array($this->statement, $this->fetchMode);
			if(is_array($this->row))
			{
				return true;
			}
			else
			{
				return false;
			}
		}
	}
	// -------------------------------------------------------------------------
	public function f($fieldName)
	{
		if(isset($this->row[$fieldName]))
		{
			return $this->row[$fieldName];
		}
		else
		{
			return null;
		}
	}
	// -------------------------------------------------------------------------
	public function setParam($name, $value = "", $clear = false, $length = -1, $type = SQLT_CHR)
	{
		if($clear)
		{
			$this->params->clear();
		}
		$this->params->add($name, new OracleParam($this->getConnectionObject(), $value, $length, $type));
	}
	// -------------------------------------------------------------------------
	public static function commit()
	{
		oci_commit(self::$connectionObiect);
	}
	// -------------------------------------------------------------------------
	public static function startTransaction()
	{
		self::$inTransaction = true;
	}
	// -------------------------------------------------------------------------
	public static function rollback()
	{
		oci_rollback(self::$connectionObiect);
	}
	// -------------------------------------------------------------------------
	public function getRowAffected()
	{
		return $this->rowAffected;
	}
	// -------------------------------------------------------------------------
	/**
	 * @return int
	 */
	public function getLastInsertID()
	{
		return null;
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
	public function setFetchMode($fetchMode)
	{
		$this->fetchMode = $fetchMode;
	}
	// -------------------------------------------------------------------------
	protected function getConnectionObject()
	{
		return self::$connectionObiect;
	}
	// -------------------------------------------------------------------------
	protected function setConnectionObject($connectionObject)
	{
		self::$connectionObiect = $connectionObject;
	}
	// -------------------------------------------------------------------------
	public function connect()
	{
		if(is_null($this->getConnectionObject()))
		{
			putenv("NLS_LANG=Polish_Poland.UTF8");
			if(empty($this->database))
			{
				$this->database = "(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP)(HOST = " . $this->serwer . ")(PORT = " . $this->port . "))(CONNECT_DATA = (SID = " . $this->sid . ")))";
			}

			$this->setConnectionObject(oci_pconnect($this->userName, $this->password, $this->database, 'UTF8'));
			if(is_resource($this->getConnectionObject()))
			{
				$this->fastQuery("ALTER SESSION SET NLS_DATE_FORMAT = '" . ORACLE_DATE_FORMAT . "'");
				$this->fastQuery("ALTER SESSION SET NLS_TIMESTAMP_FORMAT = '" . ORACLE_DATETIME_FORMAT . "'");
				$this->fastQuery("ALTER SESSION SET NLS_NUMERIC_CHARACTERS = '.`'");
				return true;
			}
			else
			{
				$this->saveErrors("Błąd połączenia");
				return false;
			}
		}
		else
		{
			return (bool)$this->getConnectionObject();
		}
	}
	// -------------------------------------------------------------------------
	protected function fastQuery($sql)
	{
		$stmt = oci_parse($this->getConnectionObject(), $sql);
		oci_execute($stmt, $this->trasaction);
	}
	// -------------------------------------------------------------------------
	static protected function getCount(DataSource $db)
	{
		$dbCount = new DB();
		$sql = "SELECT Count(*) ";
		$sql .= "FROM (" . $db->orginalQuery . ") ";
		$listaParametrow = $db->params->getAll();
		if(isset($listaParametrow["REC_LIMIT_FROM"]))
		{
			unset($listaParametrow["REC_LIMIT_FROM"]);
		}
		if(isset($listaParametrow["REC_LIMIT_TO"]))
		{
			unset($listaParametrow["REC_LIMIT_TO"]);
		}

		$dbCount->params = new OracleParams();
		$dbCount->params->setAll($listaParametrow);
		$dbCount->query($sql);
		if($dbCount->nextRecord())
		{
			$ilosc = $dbCount->f(0);
		}
		else
		{
			$ilosc = 0;
		}
		return $ilosc;
	}
	// ------------------------------------------------------------------------
	public function count()
	{
		return self::getCount($this);
	}
	// -------------------------------------------------------------------------
	protected function close()
	{
		if(is_resource($this->statement))
		{
			oci_cancel($this->statement);
		}
	}
	// -------------------------------------------------------------------------
	public function clearParam()
	{
		$this->params->clear();
	}
	// -------------------------------------------------------------------------
	protected function addParam()
	{
		$this->params->bind($this->statement);
	}
	// -------------------------------------------------------------------------
	public function getParam($name)
	{
		return $this->params->get($name);
	}
	// -------------------------------------------------------------------------
	static function getParamName($length = 8)
	{
		$p = "P" . str_pad(strval(self::$paramCount), $length, "0", STR_PAD_LEFT);
		self::$paramCount++;
		return $p;
	}
	// -------------------------------------------------------------------------
	protected function saveErrors($errorDesc)
	{
		$c = $this->getConnectionObject();
		if($c)
		{
			$ociErrors = oci_error($c);
		}
		else
		{
			$ociErrors = oci_error();
		}
		$code = @$ociErrors["code"];
		$message = @$ociErrors["message"];

		throw new GeneralSqlException($this, $errorDesc . " " . $message, $code);
	}
	// -------------------------------------------------------------------------
	public function __destruct()
	{
		$this->close();
	}
	// -------------------------------------------------------------------------
}
?>