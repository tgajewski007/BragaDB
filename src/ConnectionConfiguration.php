<?php

namespace braga\db;

class ConnectionConfiguration
{
	protected string $connectionString;
	protected string $userName;
	protected string $password;
	// -----------------------------------------------------------------------------------------------------------------
	public function __construct(string $connectionString, string $userName, string $password)
	{
		$this->connectionString = $connectionString;
		$this->userName = $userName;
		$this->password = $password;
	}
	// -----------------------------------------------------------------------------------------------------------------
	/**
	 * @return string
	 */
	public function getConnectionString(): string
	{
		return $this->connectionString;
	}
	// -----------------------------------------------------------------------------------------------------------------
	/**
	 * @param string $connectionString
	 */
	public function setConnectionString(string $connectionString): void
	{
		$this->connectionString = $connectionString;
	}
	// -----------------------------------------------------------------------------------------------------------------
	/**
	 * @return string
	 */
	public function getUserName(): string
	{
		return $this->userName;
	}
	// -----------------------------------------------------------------------------------------------------------------
	/**
	 * @param string $userName
	 */
	public function setUserName(string $userName): void
	{
		$this->userName = $userName;
	}
	// -----------------------------------------------------------------------------------------------------------------
	/**
	 * @return string
	 */
	public function getPassword(): string
	{
		return $this->password;
	}
	// -----------------------------------------------------------------------------------------------------------------
	/**
	 * @param string $password
	 */
	public function setPassword(string $password): void
	{
		$this->password = $password;
	}
	// -----------------------------------------------------------------------------------------------------------------
}