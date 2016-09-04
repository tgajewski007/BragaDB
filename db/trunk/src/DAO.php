<?php

/**
 * Created on 23-03-2013 07:50:47
 * author Tomasz Gajewski
 * package frontoffice
 * error prefix
 */
namespace braga\db;
interface DAO
{
	// -------------------------------------------------------------------------
	/**
	 *
	 * @param string $idDAO
	 * @return DAO
	 */
	static function get($idDAO = null);
	// -------------------------------------------------------------------------
	static function getByDataSource(DataSource $db);
	// -------------------------------------------------------------------------
	public function save();
	// -------------------------------------------------------------------------
	public function getKey();
	// -------------------------------------------------------------------------
}
?>