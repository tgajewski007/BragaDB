<?php

namespace braga\db;

trait ConnectionConfigurationSetter
{
	// -----------------------------------------------------------------------------------------------------------------
	protected static ConnectionConfiguration $configuration;
	// -----------------------------------------------------------------------------------------------------------------
	public static function setConnectionConfigration(ConnectionConfiguration $configuration)
	{
		self::$configuration = $configuration;
	}
	// -----------------------------------------------------------------------------------------------------------------
	public static function getConnectionConfigration()
	{
		return self::$configuration;
	}
	// -----------------------------------------------------------------------------------------------------------------
}