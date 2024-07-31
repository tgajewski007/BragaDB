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
	public function __construct(DataSource $db, $message = null, $code = null)
	{
		parent::__construct($message, $code ?? 0);
		$this->db = $db;
	}
	// -----------------------------------------------------------------------------------------------------------------
}
