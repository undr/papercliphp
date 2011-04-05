<?php
if(!defined('ROOT_PAPERCLIPHP')) {
	define("ROOT_PAPERCLIPHP", dirname(dirname(__FILE__)));
}
class Papercliphp {
	
	protected $name = null;
	protected $config = array();
	protected $styles = array("thumbnail" => array());
	protected $processors = null;
	protected $cachedProcessors = array();
	
	public function __construct($name, $config) {
		$this->name = $name;
		if(!isset($config['processors'])) {
			$this->processors = array("Thumbnail" => ROOT_PAPERCLIPHP . "/lib/processors/");
		} else {
			$this->processors = $config['processors'];
			unset($config['processors']);
		}
		if(!isset($config['styles'])) {
			$this->styles = $config['styles'];
			unset($config['styles']);
		}
		$defaultConfig = array(
			"path"			=> ":root/images/:directory/:filename/:style.:extension",
			"url"			=> "/images/:directory/:filename/:style.:extension",
			"root"			=> ROOT_PAPERCLIPHP,
			"default_style" => "original");
			
		$this->config = array_merge($defaultConfig, $config);
	}
	
	/**
	 * Enter description here...
	 *
	 * @return Papercliphp_Attachment
	 */
	public function createAttachment() {
		$args = func_get_args();
		$argsCount = func_num_args();
		if($argsCount == 2) {
			$path = $args[0];
			$name = $args[1];
			$ext  = explode('.', $name);
			$ext  = "." . end($ext);
			$name = str_replace($ext, "", $name);
		} elseif ($argsCount == 3) {
			$path = $args[0];
			$name = $args[1];
			$ext  = $args[2];
		} else {
			throw new Exception("Неверное количество аргументов");
		}
		return new Papercliphp_Attachment($this, $path, $name, $ext);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $name
	 * @return Papercliphp_Processor
	 */
	public function createProcessor($name) {
		if(!isset($this->processors[$name])) {
			return new Papercliphp_Processor_Default($this);
		}
		if(!isset($this->cachedProcessors[$name])) {
			require_once($this->processors[$name]);
			$this->cachedProcessors[$name] = new $name($this);
		}
		return $this->cachedProcessors[$name];
	}
	
	public function process(Papercliphp_Attachment $attachment) {
		$result = true;
		foreach ($this->processors as $processor) {
			$styles = isset($this->styles[strtolower($processor)]) ? $this->styles[strtolower($processor)] : array();
			$result = $result && $this->createProcessor($processor)->process($attachment, $styles);
		}
		return $result;
	}
	
	public function styleExists($stylename) {
		return isset($this->styles[$stylename]);
	}
	
	public function config($name) {
		if(isset($config[$name])) {
			return $config[$name];
		}
		return null;
	}
	
	public function styles($serialized=true) {
		if($serialized) {
			return serialize($this->styles);
		} else {
			return $this->styles;
		}
	}
}