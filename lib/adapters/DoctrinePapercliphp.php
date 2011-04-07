<?php
class DoctrinePapercliphp extends Papercliphp {
	public $name;
	protected $oldImage = 	null;
	protected $skip = 		false;
	public function __construct($name, $config=array()) {
		$this->name = $name;
		parent::__construct($config);
	}
	
	/**
	 * @param Doctrine_Record $record
	 * @return Papercliphp_Attachment
	 */
	public function extractAttachmentFrom(Doctrine_Record $record) {
		if($this->existsCachedAttachmentIn($record)) {
			return $record->{$this->name};
		} elseif ($this->existsAttachmentIn($record)) {
			return $this->createAttachment($record[$this->name . '_additional'], $record[$this->name . '_filename']);
		}
		return null;
	}
	
	/**
	 * @param Doctrine_Record $record
	 * @param Papercliphp_Attachment $attachment
	 */
	public function setAttachmentInto(Doctrine_Record $record, Papercliphp_Attachment $attachment) {
		$this->{$this->name} = $attachment;
    	$record[$this->name . '_additional'] = $attachment->additional();
    	$record[$this->name . '_filename']	 = $attachment->filename();
	}
	
	/**
	 * @param Doctrine_Record $record
	 * @return boolean
	 */
	public function existsAttachmentIn(Doctrine_Record $record) {
		return !empty($record[$this->name . '_filename']);
	}
	
	/**
	 * @param Doctrine_Record $record
	 * @return boolean
	 */
	public function existsCachedAttachmentIn(Doctrine_Record $record) {
		return isset($record->{$this->name}) && $record->{$this->name} instanceof Papercliphp_Attachment;
	}
	
	public function postDelete($event) {
		$invoker = $event->getInvoker();
    	$this->extractAttachmentFrom($invoker)->deleteAll();
	}
	
	public function preUpdate($event) {
		if($this->skip) { return; }
		$invoker = $event->getInvoker();
		$this->oldImage = $this->extractAttachmentFrom($invoker);
		$this->newImage = $this->createAttachment();
	}
	
	public function postSave($event) {
		if($this->skip) { return; }
		$invoker = $event->getInvoker();
		if(isset($this->oldImage)) {
			$this->oldImage->deleteAll();
		}
		$this->skip = true;
		$image ;
	}
}