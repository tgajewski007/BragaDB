<?php

/**
 * Created on 16 lip 2013 08:30:57
 * @author Tomasz Gajewski
 * @package frontoffice
 * error prefix
 */
namespace Braga\DB;
class DataSourceColumnMetaData
{
	// -------------------------------------------------------------------------
	protected $name;
	protected $type;
	protected $length;
	protected $scale;
	protected $precision;
	protected $numIndex;
	// -------------------------------------------------------------------------
	public function setName($name)
	{
		$this->name = $name;
	}
	// -------------------------------------------------------------------------
	public function setType($type)
	{
		$this->type = $type;
	}
	// -------------------------------------------------------------------------
	public function setLength($length)
	{
		$this->length = $length;
	}
	// -------------------------------------------------------------------------
	public function setScale($scale)
	{
		$this->scale = $scale;
	}
	// -------------------------------------------------------------------------
	public function setPrecision($precision)
	{
		$this->precision = $precision;
	}
	// -------------------------------------------------------------------------
	public function setNumIndex($numIndex)
	{
		$this->numIndex = $numIndex;
	}
	// -------------------------------------------------------------------------
	public function getName()
	{
		return $this->name;
	}
	// -------------------------------------------------------------------------
	public function getType()
	{
		return $this->type;
	}
	// -------------------------------------------------------------------------
	public function getLength()
	{
		return $this->length;
	}
	// -------------------------------------------------------------------------
	public function getScale()
	{
		return $this->scale;
	}
	// -------------------------------------------------------------------------
	public function getPrecision()
	{
		return $this->precision;
	}
	// -------------------------------------------------------------------------
	public function getNumIndex()
	{
		return $this->numIndex;
	}
	// -------------------------------------------------------------------------
}
?>