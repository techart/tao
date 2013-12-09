<?php
/**
 * Mail.List
 * 
 * Модуль формирует массовую персонализированную рассылку
 * 
 * <p>
 * Решается проблема персонализированной массовой рассылки.
 * Т.е. Надо рассылать кучу писем с возможностью вставлять в письмо какие-либо параметры, индивидуальные для каждого получателя.
 * При этом надо учитывать , что письмо должно кодироваться один раз и соответствовать стандартам MIME (не превышать заданной длины в 76 символов).
 * Плюс ко всему имеются ограничения на хостинге по количеству отправляемых писем в минуту и ограничение на загрузку процессора.
 * </p>
 * <p>
 * Воплощено в жизнь следующее решение.
 * Рассылка формируется в виде файлов. Один файл - само закодированное письмо со всеми вложениями и т.д.
 * И куча файлов представляющих собой заголовки и параметры получателя; как минимум туда записывается адрес получателя т.е. To: ...
 * Далее пишется shell скрипт, который запускается по крону (cron) и отправляет заданной количество писем, объединяя индивидуальные заголовки получателя и само письмо (например через sendmail)
 * Остается проблема подстановки параметров в тело письма. Для решения поступаем так:
 * Текст письма кодируется в одну длинную строку, параметры тоже кодируются в одну строку, т.е. все кодирование происходит один раз, во время создания файлов.
 * Shell скрипт усложняется: ищет шаблоны в тексте письма и подставляет параметры получателя, после чего разбивает текст на строки длиной 76 символов.
 * Таким образом скрипт усложнился, но все равно выполняет только элементарные операции со строка, что делается достаточно быстро.
 * </p>
 * <p>Теперь уже о модуле</p>
 * <p>Модуль является конфигурируемым.
 * </p>root
 * Путь куда будут писаться файлы с письмами и параметрами (по умолчанию '.')
 * headers
 * Массив в котором перечислены заголовки письма, всё остальное будет считать параметрами ( по умолчанию array('To', 'Subject', 'List-Unsubscribe'))
 * 
 * Параметры и заголовки кодируются по разному, поэтому и нужен список headers.
 * 
 * <p>
 * В модуле имеется единственный класс Spawner, на вход которому подается Mail.Message.Message и список параметров $list.
 * Список  - это любой итерируемый объект, ключами которого являются числовые индексы (эти индексы используются в наименовании файлов),
 * а значение в свою очередь тоже является итерируемым объектом, где ключ - это имя параметра, а значение - соответственно значение параметра.
 * Под параметрами подразумеваются заголовки (такие как To) и параметры вставляемый в шаблон письма.
 * </p>
 * <p>
 * У объекта Spawner есть метод id() [без параметров - на чтение, с переданным значением на запись], с помощью которого можно узнать или задать значение идентификатора под которым сохраняется письмо и параметры.
 * По умолчанию id формируется как md5 от текущего timestamp.
 * И имеется метод spawn(), который формирует все необходимые файлы и возвращает id.
 * </p>
 * <p>
 * Теперь подробнее о файлах которые создаются после вызова spawn().
 * В каталоге Mail_List::option('root') создаются два подкаталога: messages и recipients.
 * </p>messages
 * Здесь сохраняются файлы id.body - закодированное письмо
 * recipients
 * Здесь сохраняются файлы $k.id - закодированные параметры и заголовки ($k - индекс $list)
 * <p>Пример</p>
 * <code>
 * $list  = array();
 * foreach ($this->db()->mail->subscribers->for_list($this->list) as $n =>  $s){ //список подписчиков для рассылки $this->list
 * $list[$n]['To'] = (($s->name) ? "{$s->name} " : '')."email}>"; //формируем список параметров
 * $list[$n]['Unsubscribe-List'] = $s->del_url;
 * $list[$n]['del_url'] = $s->del_url;
 * }
 * Mail_List::Spawner($this->as_message(), $list)->id($this->id)->spawn(); //сами задаем id и понеслась
 * </code>
 * 
 * @package Mail\List
 * @version 0.2.1
 */


Core::load('Mail', 'IO.FS', 'Digest', 'Time', 'MIME');

/**
 * @package Mail\List
 */
class Mail_List implements Core_ConfigurableModuleInterface {

  static protected $options = array(
    'root' => '.',
    'headers' => array('To', 'Subject', 'List-Unsubscribe') );
  const VERSION = '0.2.1';


/**
 * Инициализация
 * 
 * @param array $options
 */
  static public function initialize(array $options = array()) {
    self::options($options);
  }



/**
 * устанавливает опции модуля
 * 
 * @param array $options
 * @return mixed
 */
  static public function options(array $options = array()) {
    if (count($options)) Core_Arrays::update(self::$options, $options);
    return self::$options;
  }

/**
 * Установка или чтение опции
 * 
 * @param string $name
 * @param  $value
 * @return mixed
 */
  static public function option($name, $value = null) {
    $prev = isset(self::$options[$name]) ? self::$options[$name] : null;
    if ($value !== null) self::options(array($name => $value));
    return $prev;
  }



/**
 * Фабричный метод, возвращает объект класса Mail.List.Spawner
 * 
 * @param Mail_Message_Message $message
 * @param  $list
 * @return Mail_List_Spawner
 */
  static public function Spawner(Mail_Message_Message $message, $list) {
    return new Mail_List_Spawner($message, $list);
  }

}


/**
 * Класс исключения
 * 
 * @package Mail\List
 */
class Mail_List_Exception extends Mail_Exception {}


/**
 * Формирует персонализированные письма ввиде файлов
 * 
 * @package Mail\List
 */
class Mail_List_Spawner {
  protected $message;
  protected $list;
  protected $id;


/**
 * Конструктор
 * 
 * @param Mail_Message_Message $message
 * @param array() $list
 */
  public function __construct(Mail_Message_Message $message, $list) {
    $this->list = $list;
    $this->message = $message;
    $this->id = Digest_MD5::hexdigest(Time::now()->timestamp);
  }



/**
 * Делает всю работу - формирует файлы с залоговками и письмом
 * 
 */
  public function spawn() {
    $this->body()->heads();
    return $this->id;
  }



/**
 * Устанавливает или считывает идентификатор
 * 
 * @param string $value
 */
  public function id($value = null) {
    if ($value !== null) {
      $this->id = (string) $value;
      return $this;
    }
    return $this->id;
  }




/**
 * Формирует основную часть письма
 * 
 */
  protected function body() {
    $messages_path = Mail_List::option('root').'/messages';
    IO_FS::mkdir($messages_path);
    $path = sprintf('%s/%s.body', $messages_path, $this->id);
    IO_FS::rm($path);
    $f = IO_FS::File($path);
    $f->
        open('w')->
          write(Core::with(new Mail_List_Encoder())->encode($this->message))->
        close();
    $f->chmod(0664);

    return $this;
  }

/**
 * Формирует файлы с заголовками и параметрами
 * 
 */
  protected function heads() {
    IO_FS::mkdir(Mail_List::option('root').'/recipients');
    foreach ($this->list as $k => $v)
      $this->head($v, $k);
    return $this;
  }

/**
 * Формирует один фаил с заголовками и параметрами
 * 
 * @param  $container
 * @param int $index
 */
  protected function head($container, $index) {
    $values = array();
    $headers = Mail_Message::Head();
    foreach ($container as $k => $v) {
      if (array_search($k, Mail_List::option('headers'), true) !== false)
        $headers->field($k, $v);
      else
        $values[] = sprintf("-%s: %s", $k,  MIME::encode_qp($v, null));
    }
      $path = sprintf('%s/%s.%06d', Mail_List::option('root').'/recipients', $this->id, $index);
      IO_FS::rm($path);
      $f = IO_FS::File($path);
      $f->
        open('w')->
          write($headers->encode().(count($values) ? implode("\n", $values)."\n" : ''))->
        close();
     $f->chmod(0664);
  }


}

/**
 * Кодировщич писема для массовой рассылки
 * 
 * @package Mail\List
 */
class Mail_List_Encoder  extends Mail_Serialize_Encoder{

/**
 * Возвращает кодировщик для письма $msg
 * 
 * @param Mail_Message_Part $msg
 */
  protected function encoder_for(Mail_Message_Part $msg) {
     return ($msg->head['Content-Transfer-Encoding']->value == MIME::ENCODING_QP) ?
      parent::encoder_for($msg)->line_length(null) : parent::encoder_for($msg);
  }

}

