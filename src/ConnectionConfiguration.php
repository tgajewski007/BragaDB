<?php

namespace braga\db;

class ConnectionConfiguration
{
	protected string $connectionString;
	protected string $userName;
	protected string $password;
	protected ?string $initCommand = null;
	// -----------------------------------------------------------------------------------------------------------------
	public function __construct(string $connectionString, string $userName, string $password, ?string $initCommand = null)
	{
		$this->connectionString = $connectionString;
		$this->userName = $userName;
		$this->password = $password;
		$this->initCommand = $initCommand;
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
	/**
	 * @param string|null $initCommand
	 */
	public function setInitCommand(?string $initCommand): void
	{
		$this->initCommand = $initCommand;
	}
	// -----------------------------------------------------------------------------------------------------------------
	public function getInitCommand()
	{
		return $this->initCommand;
	}
	// -----------------------------------------------------------------------------------------------------------------
}