<?php
/**
 *
 * @package
 *
 * @author Tomasz Gajewski
 * Created on 19-12-2010 21:38:15
 */
namespace braga\db\oracle;
class OracleParam
{
	protected $value;
	protected $blobValue;
	protected $size;
	protected $typ;
	// -------------------------------------------------------------------------
	function __construct($connectionObject, $value, $size, $typ)
	{
		$this->typ = $typ;
		$this->size = $size;
		if(OCI_B_BLOB == $typ)
		{
			$this->value = oci_new_descriptor($connectionObject, OCI_D_LOB);
			$this->blobValue = $value;
		}
		else
		{
			$this->value = $value;
		}
	}
	// -------------------------------------------------------------------------
	public function getValue()
	{
		if(OCI_B_BLOB == $this->typ)
		{
			if(null == $this->blobValue)
			{
				$this->blobValue = $this->value->load();
			}
			return $this->blobValue;
		}
		else
		{
			return $this->value;
		}
	}
	// -------------------------------------------------------------------------
	public function bind($recordSet, $name)
	{
		if(@!oci_bind_by_name($recordSet, $name, $this->value, $this->size, $this->typ))
		{
			AddErrorLog("ERROR: Zmienna: " . $name . "->" . $this->value . " Błąd bindowania");
		}
	}
	// -------------------------------------------------------------------------
	public function getSize()
	{
		return $this->size;
	}
	// -------------------------------------------------------------------------
	public function getTyp()
	{
		return $this->typ;
	}
	// -------------------------------------------------------------------------
	public function loadBlobValue()
	{
		if(OCI_B_BLOB == $this->typ)
		{
			$this->value->save($this->blobValue);
		}
	}
	// -------------------------------------------------------------------------
}
?>