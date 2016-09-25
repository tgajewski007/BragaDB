<?php
/**
 * Created on 04.09.2016 18:57:27
 * error prefix
 * @author Tomasz Gajewski
 * @package
 *
 */
namespace braga\db;
interface BusinesObject
{
	// -------------------------------------------------------------------------
	public function save();
	// -------------------------------------------------------------------------
	public function kill();
	// -------------------------------------------------------------------------
}
?>