<?php

class Thumbnail extends Papercliphp_Processor {
	
	static private $resizeTypes = array("!" => "ignoreAR", ">" => "shrinkLarger", "<" => "enlargeSmaller", "^" => "crop");
	
	/**
	 * @var Papercliphp_Attachment
	 */
	private $attachment = null;
	private $originalSize = null;
	private $imageType = null;
	protected $styles = array();
	
	public function process(Papercliphp_Attachment $attachment, $extra=array()) {
		$this->originalSize = null;
		$this->imageType = null;
		$this->attachment = $attachment;
		$this->styles = $extra;
		$result = true;
		
		if(!file_exists($attachment->path())) {
			return false;
		}
		foreach ($this->styles as $stylename => $style) {
			$result = $result && $this->processStyle($stylename, $style);
		}
		return $result;
	}
	
	protected function processStyle($stylename, $style) {
		try {
			$styleData = $this->parseStyle($style);
			return call_user_func_array(array(&$this, $styleData['type'] . "Resize"), array($styleData, $stylename));
		} catch (Exception $e) {
			//throw $e;
			return false;
		}
	}
	
	protected function parseStyle($style) {
		$symbol = $style[strlen($style) - 1];
		$sizeString = substr($style, 0, strlen($style) - 1);
		$size = explode("x", $sizeString);
		return array('width' => $size[0], "height" => isset($size[1]) ? $size[1] : $size[0], "type" => self::$resizeTypes[$symbol]);
	}
	
	protected function shrinkLargerResize($data, $style) {
		$originalSize = $this->originalSize();
		if($originalSize[0] <= $data['width'] && $originalSize[1] <= $data['height']) { 
			//Просто копируем
			copy($this->attachment->path(), $this->attachment->path($style));
			return true;
		}
	
		if($this->imageType() == "unknown") { return false; }
		if($originalSize[0] > $originalSize[1]) { $ratio = $originalSize[0]/$data['width']; } 
		elseif ($originalSize[0] <= $originalSize[1]) { $ratio = $originalSize[1]/$data['height']; }
		
		$target_width = round($originalSize[0] / $ratio);
		$target_height = round($originalSize[1] / $ratio);
		
		return $this->resize($target_width, $target_height, $style);
	}
	
	protected function enlargeSmallerResize($data, $style) {
		throw new Exception("Не поддерживается");
	}
	
	protected function cropResize($data, $style) {
		throw new Exception("Не поддерживается");
	}
	
	protected function ignoreARResize($data, $style) {
		return $this->resize($data['width'], $data['height'], $style);
	}
	
	private function resize($target_width, $target_height, $style) {
		$originalSize = $this->originalSize();
		$createFunc = create_function('$filename', "return imagecreatefrom{$this->imageType()}(\$filename);");
		$saveFunc = create_function('$img, $filename', "return image{$this->imageType()}(\$img, \$filename);");
		
	    $img = $createFunc($this->attachment->path());
	    $target = imagecreatetruecolor($target_width, $target_height);
	    imagecopyresized($target, $img, 0, 0, 0, 0, $target_width, $target_height, $originalSize[0], $originalSize[1]);
		return $saveFunc($target, $this->attachment->path($style));
	}
	
	private function originalSize() {
		if(!isset($this->originalSize)) {
			$this->buildImageInfo();
		}
		return $this->originalSize;
	}
	
	private function imageType() {
		if(!isset($this->imageType)) {
			$this->buildImageInfo();
		}
		return $this->imageType;
	}
	
	private function buildImageInfo() {
		if (!function_exists('getimagesize')){
			throw new Exception("Не установленна библиотека GD");
		}
		if(!file_exists($this->attachment->path()) ) {
			throw new Exception("Файл изображения не найден");
		}
        if (false !== ($D = @getimagesize($this->attachment->path()))) {
            $types = array(1 => 'gif', 2 => 'jpeg', 3 => 'png');
            $this->originalSize[0]	= $D['0'];
            $this->originalSize[1]  = $D['1'];
            $this->imageType       	= ( ! isset($types[$D['2']])) ? 'unknown' : $types[$D['2']];
        }
	}
}