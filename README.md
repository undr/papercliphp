Пример того, как использовать библиотеку с Doctrine:

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
	
	
    <?php
    if(!isset(User::$papercliphp)) {
        User::$papercliphp = new Papercliphp(array(
   		    "style" => array("tiny" => "20x20!", "small" => "50x50!"),
    	    "root"  => PUBLIC_DIR,
    	    "path"  => ":root/images/users/:additional/:filename/:style.:extension",
    	    "url"   => "/images/users/:additional/:style.:extension"));
    }
    ?>

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
    	if(!$image->existsAll()) {
    		$image->reprocess();
    	}
    }
    
    public function imageExists() {
    	isset($this->image) || !empty($this->avatar_filename);
    }
    
    public function postDelete($event) {
    	$this->getImage()->deleteAll();
    	// Или, в данном случае, можно:
    	// $this->getImage()->deleteDirectory();
    }

    
В контроллере приложения создайте метод который будет сохранять файл, например так:

    <?php
    ....
    public function uploadFor($user) {
	    if(isset($_FILES['avatar'])) {
		    $image = User::$papercliphp->createAttachment("{$user->id}", $_FILES['avatar']['filename']);
		    $image->createDirectory();
		
		    move_uploaded_file($image->path());
		    $user->setImage($image);
	    }
    }
    ....
    ?>

И сохраняете модель:

    $user->save();
	
Загруженное изображение с именем my_funny_cat.jpg будет сохраненно в папке PUBLIC_DIR/images/users/123/my_funny_cat под именем original.jpg. Так же будут созданны два изображения tiny.jpg и small.jpg с размерами: 20x20 - один и 50x50 - другой.

Можно манипуировать изображением из модели:

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
	
