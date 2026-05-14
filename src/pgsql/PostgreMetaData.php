<?php

namespace braga\db\pgsql;

use braga\db\DataSourceColumnMetaData;
use braga\db\DataSourceMetaData;

class PostgreMetaData implements DataSourceMetaData
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
			// daty
			case "DATE":
			case "TIMESTAMP":
			case "TIMESTAMPTZ":
				return "date";

			// integer
			case "INT2":
			case "INT4":
			case "INT8":
			case "SERIAL":
			case "BIGSERIAL":
				return "int";

			// numeric
			case "NUMERIC":
			case "FLOAT4":
			case "FLOAT8":
			case "MONEY":
				return "float";

			// bool
			case "BOOL":
			case "BOOLEAN":
				return "bool";

			// tekst
			case "VARCHAR":
			case "TEXT":
			case "BPCHAR":
			case "CHAR":
			case "UUID":
			case "JSON":
			case "JSONB":
				return "varchar";

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
		$this->iteratorIndikator = count($this->columnNumIndexedInfo) > 0;
	}
	// -----------------------------------------------------------------------------------------------------------------
	public function getColumnCount(): int
	{
		return $this->columnCount;
	}
}