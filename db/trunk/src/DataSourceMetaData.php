<?php
/**
 * Created on 16 lip 2013 08:17:42
 * @author Tomasz Gajewski
 * @package frontoffice
 * error prefix
 */
namespace Braga\DB;
interface DataSourceMetaData extends \Iterator
{
	// -------------------------------------------------------------------------
	/**
	 *
	 * @param string $index
	 * @return DataSourceColumnMetaData
	 */
	public function get($index);
	// -------------------------------------------------------------------------
	// -------------------------------------------------------------------------
	/**
	 *
	 * @return int
	 */
	public function getColumnCount();
	// -------------------------------------------------------------------------
}
?>