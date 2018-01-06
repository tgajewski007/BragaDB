<?php

/**
 * Created on 25-10-2011 19:17:14
 * @author Tomasz Gajewski
 * @package common
 * error prefix
 */
namespace braga\db;
interface DataSource extends \Countable
{
	// -------------------------------------------------------------------------
	public function f($index);
	// -------------------------------------------------------------------------
	/**
	 *
	 * @param string $SQL
	 * @return boolean
	 */
	public function query($sql);
	// -------------------------------------------------------------------------
	public function rewind();
	// -------------------------------------------------------------------------
	/**
	 *
	 * @return boolean
	 */
	public function nextRecord();
	// -------------------------------------------------------------------------
	public function setParam($name, $val);
	// -------------------------------------------------------------------------
	public function getRowAffected();
	// -------------------------------------------------------------------------
	public static function commit();
	// -------------------------------------------------------------------------
	public static function rollback();
	// -------------------------------------------------------------------------
	public static function startTransaction();
	// -------------------------------------------------------------------------
	/**
	 *
	 * @return DataSourceMetaData
	 */
	public function getMetaData();
	// -------------------------------------------------------------------------
}
?>