## Работа Papercliphp с Doctrine:

Допустим есть модель:

    <?php
    class User extends Doctrine_Record {
	    public function setTableDefinition() {
        $this->setTableName('users');
            $this->hasColumn('id', 'integer', 8, array(
                 'type' => 'integer',
                 'primary' => true,
                 'length' => '8',
                ));
            $this->hasColumn('email', 'string', 255, array(
                 'type' => 'string',
                 'notblank' => true,
                 'length' => '255',
                 'unique' =>true,
                 ));
            $this->hasColumn('name', 'string', 255, array(
                 'type' => 'string',
                 'length' => '255',
                 ));
            $this->hasColumn('avatar_additional', 'string', 255, array(
                 'type' => 'string',
                 'length' => '255',
                 ));
            $this->hasColumn('avatar_filename', 'string', 255, array(
                 'type' => 'string',
                 'length' => '255',
                 ));
        }
    }
    ?>

Добавим в нее статический объект $papercliphp. И в самом начале скрипта, например при подключении к БД, инициализируйте его:

    <?php
    class User extends Doctrine_Record {
    
	    static private $papercliphp = null;
	    ....
    }
    ?>
	
	

    if(!isset(User::$papercliphp)) {
        User::$papercliphp = new Papercliphp(array(
   		    "styles" => array("tiny" => "20x20!", "small" => "50x50!"),
    	    "root"  => PUBLIC_DIR,
    	    "path"  => ":root/images/users/:additional/:filename/:style.:extension",
    	    "url"   => "/images/users/:additional/:filename/:style.:extension"));
    }
    

Добавьте в модель несколько методов для работы с объектом Papercliphp_Attachment и аттрибут $image:

    private $image = null;
    
    public function getImage() {
    	if(!isset($this->image) && !empty($this->avatar_filename)) {
    		$this->image = self::$papercliphp->createAttachment($this->avatar_additional, $this->avatar_filename);
    	} else {
    		return null;
    	}
    	return $this->image;
    }
    
    public function setImage(Papercliphp_Attachment $image) {
    	$this->image = $image;
    	$this->avatar_additional = $image->additional();
    	$this->avatar_filename	 = $image->filename();
    }
    
    public function imageExists() {
    	isset($this->image) || !empty($this->avatar_filename);
    }
    
    public function postDelete($event) {
    	$invoker = $event->getInvoker();
    	$invoker->getImage()->deleteAll();
    	// Или, в данном случае, можно:
    	// $invoker->getImage()->deleteDirectory();
    }
     
    
В контроллере приложения создайте метод который будет сохранять файл, например так:


    public function uploadFor($user) {
	    if(isset($_FILES['avatar'])) {
		    $image = User::$papercliphp->createAttachment("{$user->id}", $_FILES['avatar']['name']);
		    $image->upload($_FILES['avatar']['tmp_name']);
		    $user->setImage($image);
	    }
    }


И сохраняете модель:

    $user->save();
	
К примеру id пользователя равно 123. Загруженное изображение с именем my_funny_cat.jpg будет сохраненно в папке PUBLIC_DIR/images/users/123/my_funny_cat под именем original.jpg. Так же будут созданны два изображения tiny.jpg и small.jpg с размерами: 20x20 - один и 50x50 - другой.

Можно манипуировать изображением имея доступ к модели:

    $user->getImage()->url(); 			// => /images/users/123/my_funny_cat/original.jpg
    $user->getImage()->url("small");	// => /images/users/123/my_funny_cat/small.jpg
    
    $user->getImage()->path();			// => PUBLIC_DIR/images/users/123/my_funny_cat/original.jpg
    $user->getImage()->path("small");	// => PUBLIC_DIR/images/users/123/my_funny_cat/small.jpg
    
    $user->getImage()->directory();			// => PUBLIC_DIR/images/users/123/my_funny_cat
    $user->getImage()->directory("small");	// => PUBLIC_DIR/images/users/123/my_funny_cat
    
    
    $user->getImage()->filename();					// => my_funny_cat.jpg
    $user->getImage()->filenameWithoutExtension();	// => my_funny_cat
    $user->getImage()->extension();					// => jpg
    
    $user->getImage()->exists();		// Проверяет существует ли файл PUBLIC_DIR/images/users/123/my_funny_cat/original.jpg
    $user->getImage()->exists("small");	// Проверяет существует ли файл PUBLIC_DIR/images/users/123/my_funny_cat/small.jpg
    $user->getImage()->existsAll();		// Проверяет существуют ли все файлы
    
    $user->getImage()->delete();		// Удалить файл original.jpg
    $user->getImage()->delete("small");	// Удалить файл small.jpg
    $user->getImage()->unlink();		// синоним delete()
    
    $user->getImage()->deleteAll();		// Удалить все файлы (original.jpg, small.jpg и tiny.jpg)
    $user->getImage()->unlinkAll();		// синоним deleteAll()
    
    $user->getImage()->deleteAllWithoutOriginal(); 	// Удалить все файлы кроме оригинала (small.jpg и tiny.jpg)
    $user->getImage()->unlinkAllWithoutOriginal();	// синоним deleteAllWithoutOriginal()
    
    $user->getImage()->deleteDirectory();		// Удалить директорию PUBLIC_DIR/images/users/123/my_funny_cat со всем содержимом
    $user->getImage()->createDirectory();		// Создать директорию, если ее нет
    
    $user->getImage->process();		// Запустить прикрепленные процессоры, в данном случае Thunbnail
    $user->getImage->reprocess();	// Повторно запустить прикрепленные процессоры
	

## Работа Papercliphp с CakePhp

Создаем класс модели User:

	<?php
	class User extends AppModel {
		static public $papercliphp = null;
	    public function beforeSave() {
	        if (isset($this->data['avatar_file']) && count($this->data['avatar_file'])) {
	        	if(isset($this->data['avatar_filename'])) {
	        		$this->oldImage = self::$papercliphp->createAttachment("{$this->id}", $this->data['avatar_filename']);
	        	}
	        	$this->uploadFile = $this->data['avatar_file']['tmp_name'];
	        	$this->data['avatar_filename'] = $this->data['avatar_file']['name'];
	        	unset($this->data['avatar_file']);
	        }
	        return true;
	    }
	    
	    public function afterSave($created) {
	    	if(isset($this->oldImage)) {
	    		$this->oldImage->deleteDirectory();
	    		unset($this->oldImage);
	    	}
	        if (isset($this->data['avatar_filename'])) {
	        	$image = self::$papercliphp->createAttachment("{$this->id}", $this->data['avatar_filename']);
	        	if(!$image->upload($this->uploadFile)) {
	        		return false;
	        	}
	        	unset($this->uploadFile);
	        }
	        $this->data['User']['ava'] = Security::hash(Configure::read('Security.salt') . $this->data['User']['passwd']);
	        return true;
	    }
	    
	    public function afterDelete() {
	    	self::$papercliphp->createAttachment($this->data['avatar_additional'], $this->data['avatar_filename'])->deleteDirectory();
	    }
	    
	    public function afterFind($results) {
	    	foreach ($results as $key => $val) {
				if (isset($val['avatar_filename'])) {
					$results[$key]['avatar'] = self::$papercliphp->createAttachment($val['avatar_additional'], $var['avatar_filename']); 
				}
			}
			return $results;
	    }
	}
	if(!isset(User::$papercliphp)) {
        User::$papercliphp = new Papercliphp(array(
   		    "styles" => array("tiny" => "20x20!", "small" => "50x50!"),
    	    "root"  => WWW_ROOT,
    	    "path"  => ":root/images/users/:additional/:filename/:style.:extension",
    	    "url"   => "/images/users/:additional/:filename/:style.:extension"));
    }
	?>
	
В контроллере:
	
	if( empty($this->data) ) {
        $this->data = $this->User->read(null, $id);
    } else {
    	$this->data['avatar_file'] = $_FILES['avatar_file'];
        $this->User->set($this->data);
        if( $this->User->save() ) {
            $this->Session->setFlash(__('User successfully saved', true));
        }
    }
 
В шаблонах примерно так:

	<? foreach ($users as $user):?>
		<? if($user['avatar']->exists('tiny')): ?>
			<img src="<?=$user['avatar']->url('tiny')?>" />
		<? endif; ?>
	<? endforeach; ?>
	
Тут описан общий принцип, я могу ошибаться в деталях, так как давно не писал на CakePHP.

## Работа Papercliphp с WolfCMS

Модель выглядит примерно так:

	class Item extends Record {
		const TABLE_NAME = 'items';
		private $image = null;
		static public $papercliphp = null;

		public function getImage() {
			if(!isset($this->image) && isset($this->image_filename)) {
				$additional = isset($this->image_additional) ? $this->image_additional : "";
				$this->image = self::$papercliphp->createAttachment($additional, $this->image_filename);
			}
			return $this->image;
		}
	
		public function isImageExists() {
			return isset($this->image_filename) || isset($this->image);
		}
	
		public function setImage(Papercliphp_Attachment $image) {
			$this->image = $image;
			$this->image_additional = $image->additional();
			$this->image_filename = $image->filename();
			if(!$image->existsAll()) {
				$image->reprocess();
			}
		}
	
		public function afterDelete() { 
			if($this->isImageExists()) {
				$this->getImage()->deleteDirectory();
			}
			return true; 
		}
	}
	if(!isset(Item::$papercliphp)) {
		$settings = Plugin::getAllSettings('yagallery');
		$styles = unserialize($settings['yagallery_styles']);
		if(!isset($styles['admin_thmb'])) {
			$styles['admin_thmb'] = "50x50!";
		}
	    yagalleriesItem::$papercliphp = new Papercliphp(array(
   		    "styles" => array("tiny" => "20x20!", "small" => "50x50!"),
	        "root"  => CMS_ROOT,
	        "path"  => ":root/images/items/:additional/:filename/:style.:extension",
	        "url"   => "/images/items/:additional/:filename/:style.:extension"));
	}
	
В контроллере:

	if(isset($_POST['item']) && count($_POST['item']) && isset($_FILES['image_file'])) {
		$item = new Item($_POST['item']);
		if($item->save() === true) {
			$image = Item::$papercliphp->createAttachment($item->id, $_FILES['image_file']['name']);
			if($image->upload()) {
				$item->setImage($image);
				$item->save();
				Flash::set('success', 'Изображение сохранено');
				redirect(...);
			} else {
				Flash::setNow('error', "Произошла ошибка!");
			}
		} else {
			Flash::setNow('error', "Произошла ошибка!");
		}	
	} 
	
В шаблонах:

	<? foreach($items as $item):?>
	<img src="<?= $item->getImage()->url("small") ?>" />
	<? endforeach ?>