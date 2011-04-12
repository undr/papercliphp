<?php

class Papercliphp_Attachment {
	private $additional = "";
	private $filename = "";
	private $extension  = "";
	
	/**
	 * @var Papercliphp
	 */
	protected $papercliphp = null;

	public function __construct($papercliphp, $additional, $filename, $extension) {
		$this->additional = $additional;
		$this->filename = $filename;
		$this->extension = $extension;
		$this->papercliphp = $papercliphp;
	}
	
	public function url($stylename="") {
		if(!empty($stylename) && !$this->papercliphp->styleExists($stylename)) {
			throw new Exception("Нет такого стиля для изображения '$stylename', только: " . implode(", ", $this->papercliphp->styles(false)));
		} elseif(empty($stylename)) {
			$stylename = $this->papercliphp->config("default_style");
		}
		$url = $this->papercliphp->config('url');
		$url = str_replace(":style", $stylename, $url);
		$url = str_replace(":filename", $this->filename, $url);
		$url = str_replace(":extension", $this->extension, $url);
		$url = str_replace(":additional", $this->additional, $url);
		return str_replace("//", "/", $url);
	}
	
	public function path($stylename="") {
		if(!empty($stylename) && !$this->papercliphp->styleExists($stylename)) {
			throw new Exception("Нет такого стиля для изображения '$stylename', только: " . implode(", ", $this->papercliphp->styles(false)));
		} elseif(empty($stylename)) {
			$stylename = $this->papercliphp->config("default_style");
		}
		$path = $this->papercliphp->config('path');
		$root = $this->papercliphp->config('root');
		$path = str_replace(":style", $stylename, $path);
		$path = str_replace(":filename", $this->filename, $path);
		$path = str_replace(":extension", $this->extension, $path);
		$path = str_replace(":additional", $this->additional, $path);
		$path = str_replace(":root", $root, $path);
		return str_replace("//", "/", $path);
	}
	
	public function directory($stylename="") {
		return dirname($this->path($stylename));
	}
	
	public function createDirectory($stylename="") {
		if(!is_dir($this->directory($stylename))) {
			mkdir($this->directory($stylename), 0700, true);
		}
	}

	public function deleteDirectory($stylename="") {
		if(is_dir($this->directory($stylename))) {
			rrmdir($this->directory($stylename));
		}
	}

	public function exists($stylename="") {
		return file_exists($this->path($stylename));
	}
	
	public function existsAll() {
		$styles = array_keys($this->papercliphp->styles(false));
		$styles[] = "";
		foreach ($styles as $stylename) {
			if(!$this->exists($stylename)) {
				return false;
			}
		}
		return true;
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
	
	public function deleteAllWithoutOriginal() {
		$this->unlinkAllWithoutOriginal();
	}
	
	public function unlinkAll() {
		$this->unlinkAllWithoutOriginal();
		$this->unlink();
	}
	
	public function unlinkAllWithoutOriginal() {
		$styles = array_keys($this->papercliphp->styles(false));
		foreach ($styles as $stylename) {
			$this->unlink($stylename);
		}
	}
	
	public function additional() {
		return $this->additional;
	}
	
	public function filename() {
		return $this->filename . "." . $this->extension;
	}
	
	public function filenameWithoutExtension() {
		return $this->filename;
	}
	
	public function extension() {
		return $this->extension;
	}
	
	public function upload($filename) {
		if (is_uploaded_file($filename)) {
			$this->createDirectory();
	        if (move_uploaded_file($filename, $this->path())) {
	            if(!$this->process()) {
	              	return false;
	            }
	        } else {
	            return false;
	        }     
	    } else {
	        return false;
        }
        return true;
	}
	
	public function process() {
		return $this->papercliphp->process($this);
	}
	
	public function reprocess() {
		$this->unlinkAllWithoutOriginal();
		$this->process();
	}
}