<?php

class Papercliphp_Attachment {
	private $directory = "";
	private $name = "";
	private $extension  = "";
	
	/**
	 * @var Papercliphp
	 */
	protected $papercliphp = null;

	public function __construct($papercliphp, $directory, $filename, $extension) {
		$this->directory = $directory;
		$this->filename = $filename;
		$this->extension = $extension;
		$this->papercliphp = $papercliphp;
	}
	
	public function url($stylename="") {
		if(!$this->papercliphp->styleExists($stylename)) {
			throw new Exception("Нет такого стиля для изображения");
		}
		$url = $this->papercliphp->config('url');
		$url = str_replace(":style", $stylename, $url);
		$url = str_replace(":filename", $this->name, $url);
		$url = str_replace(":extension", $this->extension, $url);
		$url = str_replace(":directory", $this->directory, $url);
		return str_replace("//", "/", $url);
	}
	
	public function path($stylename="") {
		if(!$this->papercliphp->styleExists($stylename)) {
			throw new Exception("Нет такого стиля для изображения");
		}
		$path = $this->papercliphp->config('path');
		$root = $this->papercliphp->config('root');
		$path = str_replace(":style", $stylename, $path);
		$path = str_replace(":filename", $this->name, $path);
		$path = str_replace(":extension", $this->extension, $path);
		$path = str_replace(":directory", $this->directory, $path);
		$path = str_replace(":root", $root, $path);
		return str_replace("//", "/", $path);
	}
	
	public function direcrory($stylename="") {
		return dirname($this->path($stylename));
	}
	
	public function createDirectory($stylename="") {
		if(!is_dir($this->direcrory($stylename))) {
			mkdir($this->direcrory($stylename), 0700, true);
		}
	}
	
	public function delete($stylename="") {
		$this->unlink($stylename);
	}
	
	public function unlink($stylename="") {
		if(file_exists($this->path($stylename))) {
			unlink($this->path($stylename));
		}
	}
	
	public function deleteAll() {
		$this->unlinkAll();
	}
	
	public function unlinkAll() {
		$styles = array_merge(array_keys($this->papercliphp->styles(false)), array($this->papercliphp->config("default_style")));
		foreach ($styles as $stylename) {
			$this->unlink($stylename);
		}
	}
	
	/**
	 * @todo Сменить название аттрибута directory и(или) название функции 
	 */
	public function directory() {
		return $this->directory;
	}
	
	public function name() {
		return $this->name;
	}
	
	public function extension() {
		return $this->extension;
	}
	
	public function process() {
		return $this->papercliphp->process($this);
	}
}