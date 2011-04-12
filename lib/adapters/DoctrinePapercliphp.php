<?php
require_once(dirname(dirname(__FILE__)) . "/Papercliphp.php");
class DoctrinePapercliphp extends Papercliphp {
	public $name;
	
	/**
	 * @param string $name
	 * @param array $config
	 */
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
		$record->{$this->name} = $attachment;
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
		if($this->existsCachedAttachmentIn($invoker) || $this->extractAttachmentFrom($invoker)) {
    		$this->extractAttachmentFrom($invoker)->deleteAll();
		}
	}
	
	/**
	 * @param Doctrine_Record $record
	 * @param string $additional
	 * @param string $file
	 * @return boolean
	 */
	public function saveAttachmentInto(Doctrine_Record $record, $additional, $file) {
		$attachment = $this->createAttachment($additional, $file['name']);
		if($this->existsAttachmentIn($record)) {
			$oldAttachment = $this->extractAttachmentFrom($record);
		}
		$this->setAttachmentInto($record, $attachment);
		$conn = Doctrine_Manager::connection();
		
		try {
			$conn->beginTransaction();
			if($record->trySave()) {
				if(isset($oldAttachment)) {
					$oldAttachment->deleteAll();
				}
				print_r($attachment);
				if($attachment->upload($file['tmp_name']) && $attachment->process()) {
					$conn->commit();
					return true;
				}
			}
		} catch (Exception $e) {}
		
		$conn->rollback();
		return false;
	}
}