<?php
abstract class Papercliphp_Processor {
	
	/**
	 * @var Papercliphp
	 */
	protected $papercliphp = null;
	
	public function __construct(Papercliphp $papercliphp) {
		$this->papercliphp = $papercliphp;
	}
	
	public function process(Papercliphp_Attachment $attachment, $extra=array()) {
		throw new Exception("You must implement this");
		return true;
	}
}