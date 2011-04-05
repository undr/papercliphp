<?php

class Papercliphp_Image {
	private $path = "";
	private $name = "";
	private $ext  = "";
	private $styles = "";
	private $originalSize = null;
	private $type = null;
	static private $resizeTypes = array("!" => "ignoreAR", ">" => "shrinkLarger", "<" => "enlargeSmaller", "^" => "crop");
	
	public function __construct() {
		$args = func_get_args();
		$argsCount = func_num_args();
		if($argsCount == 3) {
			$path = $args[0];
			$filename = $args[1];
			$ext = explode('.', $filename);
			$ext = "." . end($ext);
			$name = str_replace($ext, "", $filename);
			$styles = $args[2];
		} elseif ($argsCount == 4) {
			$path = $args[0];
			$name = $args[1];
			$ext = $args[2];
			$styles = $args[3];
		} else {
			throw new Exception("Неверное количество аргументов");
		}
		
		$this->path = $path;
		$this->name = $name;
		$this->ext = $ext;
		if(!is_array($styles)) {
			$styles = unserialize($styles);
		}
		if(!isset($styles['admin_thmb'])) {
			$styles['admin_thmb'] = "50x50!";
		}
		$this->styles = $styles;
	}
	
	public function url($style="") {
		if(!$this->styleExists($style)) {
			throw new Exception("Нет такого стиля для изображения");
		}
		if(!empty($style)) {
			$style = ".".$style;
		}
		$path = URI_PUBLIC . $this->path . $this->name . "$style" . $this->ext;
		return str_replace("//", "/", $path);
	}
	
	public function realPath($style="") {
		if(!$this->styleExists($style)) {
			throw new Exception("Нет такого стиля для изображения");
		}
		if(!empty($style)) {
			$style = ".".$style;
		}
		$path = CMS_ROOT . DS . $this->path . $this->name . "$style" . $this->ext;
		return str_replace("//", "/", $path);
	}
	
	public function process() {
		if(!file_exists($this->realPath()) || !defined("CMS_BACKEND")) {
			return false;
		}
		$result = true;
		foreach ($this->styles as $stylename => $style) {
			$result = $result && $this->processStyle($stylename, $style);
		}
		return $result;
	}
	
	public function styleExists($name) {
		return isset($this->styles[$name]) || empty($name);
	}
	
	public function path() {
		return $this->path;
	}
	
	public function name() {
		return $this->name;
	}
	
	public function ext() {
		return $this->ext;
	}
	
	public function styles($serialized=true) {
		if($serialized) {
			return serialize($this->styles);
		} else {
			return $this->styles;
		}
	}
	
	private function processStyle($stylename, $style) {
		try {
			$styleData = $this->parseStyle($style);
			return call_user_func_array(array(&$this, $styleData['type'] . "Resize"), array($styleData, $stylename));
		} catch (Exception $e) {
			throw $e;
			return false;
		}
	}

	private function parseStyle($style) {
		$symbol = $style[strlen($style) - 1];
		$sizeString = substr($style, 0, strlen($style) - 1);
		$size = explode("x", $sizeString);
		return array('width' => $size[0], "height" => isset($size[1]) ? $size[1] : $size[0], "type" => self::$resizeTypes[$symbol]);
	}
	
	private function shrinkLargerResize($data, $style) {
		$originalSize = $this->originalSize();
		if($originalSize[0] <= $data['width'] && $originalSize[1] <= $data['height']) { 
			//Просто копируем
			copy($this->realPath(), $this->realPath($style));
			return true;
		}
	
		if($this->imageType() == "unknown") { return false; }
		if($originalSize[0] > $originalSize[1]) { $ratio = $originalSize[0]/$data['width']; } 
		elseif ($originalSize[0] <= $originalSize[1]) { $ratio = $originalSize[1]/$data['height']; }
		
		$target_width = round($originalSize[0] / $ratio);
		$target_height = round($originalSize[1] / $ratio);
		
		return $this->resize($target_width, $target_height, $style);
	}
	
	private function enlargeSmallerResize($data, $style) {
		throw new Exception("Не поддерживается");
	}
	
	private function cropResize($data, $style) {
		throw new Exception("Не поддерживается");
	}
	
	private function ignoreARResize($data, $style) {
		return $this->resize($data['width'], $data['height'], $style);
	}
	
	private function resize($target_width, $target_height, $style) {
		//$createFunc = $this->type . "CreateFunc";
		//$saveFunc = $this->type . "SaveFunc";
		// Меняем размер
		$originalSize = $this->originalSize();
		$createFunc = create_function('$filename', "return imagecreatefrom{$this->imageType()}(\$filename);");
		$saveFunc = create_function('$img, $filename', "return image{$this->imageType()}(\$img, \$filename);");
		
	    $img = $createFunc($this->realPath());
	    $target = imagecreatetruecolor($target_width, $target_height);
	    imagecopyresized($target, $img, 0, 0, 0, 0, $target_width, $target_height, $originalSize[0], $originalSize[1]);
		return $saveFunc($target, $this->realPath($style));
	}
	
	private function originalSize() {
		if(!isset($this->originalSize)) {
			$this->buildImageInfo();
		}
		return $this->originalSize;
	}
	
	private function imageType() {
		if(!isset($this->type)) {
			$this->buildImageInfo();
		}
		return $this->type;
	}
	
	private function buildImageInfo() {
		if (!function_exists('getimagesize')){
			throw new Exception("Не установленна библиотека GD");
		}
		if(!file_exists($this->realPath()) ) {
			throw new Exception("Файл изображения не найден");
		}
        if (false !== ($D = @getimagesize($this->realPath()))) {
            $types = array(1 => 'gif', 2 => 'jpeg', 3 => 'png');
            $this->originalSize[0]	= $D['0'];
            $this->originalSize[1]  = $D['1'];
            $this->type       		= ( ! isset($types[$D['2']])) ? 'unknown' : $types[$D['2']];
        }
	}
}