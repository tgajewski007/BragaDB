<?php

namespace braga\db\mysql;

use braga\db\DataSourceColumnMetaData;
use braga\db\DataSourceMetaData;

class MySQLMetaData implements DataSourceMetaData
{
	// -----------------------------------------------------------------------------------------------------------------
	protected array $columnNumIndexedInfo = [];
	protected array $columnNameIndexedInfo = [];
	protected int $columnCount = 0;
	// -----------------------------------------------------------------------------------------------------------------
	protected bool $iteratorIndikator = true;
	// -----------------------------------------------------------------------------------------------------------------
	public function __construct(\PDOStatement $stm)
	{
		$this->columnCount = $stm->columnCount();

		for($i = 0; $i < $this->columnCount; $i++)
		{
			$tmp = $stm->getColumnMeta($i);

			$col = new DataSourceColumnMetaData();

			$col->setName($tmp["name"] ?? "");
			$col->setLength($tmp["len"] ?? null);
			$col->setNumIndex($i);

			$nativeType = strtoupper($tmp["native_type"] ?? "");

			$col->setType($this->translateType($nativeType));

			$this->columnNumIndexedInfo[$i] = $col;
			$this->columnNameIndexedInfo[$col->getName()] = $col;
		}
	}
	// -----------------------------------------------------------------------------------------------------------------
	protected function translateType(string $nativeType): string
	{
		switch($nativeType)
		{
			// integer
			case "TINY":
			case "SHORT":
			case "LONG":
			case "LONGLONG":
			case "INT24":
			case "YEAR":
				return "int";

			// floating point
			case "FLOAT":
			case "DOUBLE":
			case "DECIMAL":
			case "NEWDECIMAL":
				return "float";

			// boolean
			case "BIT":
			case "BOOL":
			case "BOOLEAN":
				return "bool";

			// date / time
			case "DATE":
			case "NEWDATE":
			case "DATETIME":
			case "TIMESTAMP":
			case "TIME":
				return "date";

			// binary
			case "TINY_BLOB":
			case "MEDIUM_BLOB":
			case "LONG_BLOB":
			case "BLOB":
			case "BINARY":
			case "VARBINARY":
				return "binary";

			// text / string
			case "VARCHAR":
			case "VAR_STRING":
			case "STRING":
			case "ENUM":
			case "SET":
			case "JSON":
			case "GEOMETRY":
				return "varchar";

			// NULL
			case "NULL":
				return "null";

			default:
				return "varchar";
		}
	}
	// -----------------------------------------------------------------------------------------------------------------
	public function get($index)
	{
		if(isset($this->columnNumIndexedInfo[$index]))
		{
			return $this->columnNumIndexedInfo[$index];
		}

		if(isset($this->columnNameIndexedInfo[$index]))
		{
			return $this->columnNameIndexedInfo[$index];
		}

		return new DataSourceColumnMetaData();
	}
	// -----------------------------------------------------------------------------------------------------------------
	public function current(): mixed
	{
		return current($this->columnNumIndexedInfo);
	}
	// -----------------------------------------------------------------------------------------------------------------
	public function next(): void
	{
		$this->iteratorIndikator = next($this->columnNumIndexedInfo) !== false;
	}
	// -----------------------------------------------------------------------------------------------------------------
	public function key(): mixed
	{
		return key($this->columnNumIndexedInfo);
	}
	// -----------------------------------------------------------------------------------------------------------------
	public function valid(): bool
	{
		return $this->iteratorIndikator;
	}
	// -----------------------------------------------------------------------------------------------------------------
	public function rewind(): void
	{
		reset($this->columnNumIndexedInfo);

		$this->iteratorIndikator =
			count($this->columnNumIndexedInfo) > 0;
	}
	// -----------------------------------------------------------------------------------------------------------------
	public function getColumnCount(): int
	{
		return $this->columnCount;
	}
}