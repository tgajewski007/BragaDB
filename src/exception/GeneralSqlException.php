<?php

namespace braga\db\exception;

use braga\db\DataSource;

class GeneralSqlException extends \Exception
{
	/**
	 * @var DataSource
	 */
	protected $db;
	// -----------------------------------------------------------------------------------------------------------------
	function __construct(DataSource $db, $message = null, $code = null)
	{
		$this->db = $db;
		$this->message = $message;
		$this->code = $code;
	}
	// -----------------------------------------------------------------------------------------------------------------
}
