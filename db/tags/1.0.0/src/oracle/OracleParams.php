<?php
/**
 *
 * @package
 *
 * @author Tomasz Gajewski
 * Created on 19-12-2010 21:34:46
 */
namespace braga\db\oracle;
class OracleParams
{
	protected $listaParametrow = array();
	protected $blob = false;
	// -------------------------------------------------------------------------
	public function add($name, OracleParam $param)
	{
		if(mb_substr($name, 0, 1) == ":")
			$name = mb_substr($name, 1);
		$this->listaParametrow[$name] = $param;
		if(OCI_B_BLOB == $param->getTyp())
		{
			$this->blob = true;
		}
	}
	// -------------------------------------------------------------------------
	public function clear()
	{
		$this->listaParametrow = array();
		$this->blob = false;
	}
	// -------------------------------------------------------------------------
	public function getAll()
	{
		return $this->listaParametrow;
	}
	// -------------------------------------------------------------------------
	public function setAll($params)
	{
		$this->listaParametrow = $params;
	}
	// -------------------------------------------------------------------------
	public function haveBlob()
	{
		return $this->blob;
	}
	// -------------------------------------------------------------------------
	public function get($name)
	{
		return $this->listaParametrow[$name]->getValue();
	}
	// -------------------------------------------------------------------------
	public function loadBlobData()
	{
		foreach($this->listaParametrow as $value)
		{
			$value->loadBlobValue();
		}
	}
	// -------------------------------------------------------------------------
	public function bind($recordSet)
	{
		foreach($this->listaParametrow as $key => $value)
		{
			$value->bind($recordSet, $key);
		}
	}
	// -------------------------------------------------------------------------
}
?>