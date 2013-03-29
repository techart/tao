<?php
/// <module name="Mail.Serialize" version="0.2.3" maintainer="timokhin@techart.ru">
///     <brief>Модуль для кодирования и декодирования письма</brief>
Core::load('MIME', 'MIME.Encode', 'MIME.Decode', 'Mail.Message');

/// <class name="Mail.Serialize" stereotype="module">
///   <depends supplier="Mail.Serialize.Encoder" stereotype="creates" />
///   <depends supplier="Mail.Serialize.Decoder" stereotype="creates" />
class Mail_Serialize implements Core_ModuleInterface {

///   <constants>
  const MODULE  = 'Mail.Serialize';
  const VERSION = '0.2.3';
///   </constants>

///   <protocol name="building">

///   <method name="Encoder" returns="Mail.Serialize.Encoder" scope="class">
///     <brief>Фабричный метод, возвращает объект класаа Mail.Serialize.Encoder</brief>
///     <body>
  static public function Encoder() { return new Mail_Serialize_Encoder(); }
///     </body>
///   </method>

///   <method name="Decoder" returns="Mail.Serialize.Decoder" scope="class">
///     <brief>Фабричный метод, возвращает объект класаа Mail.Serialize.Decoder</brief>
///     <body>
  static public function Decoder() { return new Mail_Serialize_Decoder(); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Mail.Serialize.Encoder">
///     <brief>Кодирое почтовое сообщение</brief>
///   <depends supplier="IO.Stream.AbstractStream" stereotype="uses" />
///   <depends supplier="Mail.Message.Part" stereotype="processes" />
///   <depends supplier="MIME.Encode" stereotype="uses" />
class Mail_Serialize_Encoder {

  protected $output = '';

///   <protocol name="configuring">

///   <method name="to_stream" returns="Mail.Serialize.Encoder">
///     <brief>Устанавливает запись результата в поток</brief>
///     <args>
///       <arg name="output" type="IO.Stream.AbstractStream" brief="поток" />
///     </args>
///     <body>
  public function to_stream(IO_Stream_AbstractStream $output) {
    $this->output = $output;
    return $this;
  }
///     </body>
///   </method>

///   <method name="to_string" returns="Mail.Serialize.Encoder">
///     <brief>Устанавливает запись результата в строку</brief>
///     <args>
///       <arg name="output" type="stream" default="''" brief="строка" />
///     </args>
///     <body>
  public function to_string($output = '') {
    $this->output = (string) $output;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="processing">

///   <method name="encode" returns="mixed">
///     <brief>Кодирует почтовое сообщение Mail.Message.Message</brief>
///     <args>
///       <arg name="message" type="Mail.Message.Part" brief="сообщение" />
///     </args>
///     <body>
  public function encode(Mail_Message_Part $message) {
    $this->encode_head($message);
    $this->write();
    $this->encode_body($message);
    return $this->output;
  }
///     </body>
///   </method>

///   <method name="encode_head" returns="mixed">
///     <brief>Кодирует заголовок письма</brief>
///     <args>
///       <arg name="message" type="Mail.Message.Part" brief="сообщение" />
///       <arg name="fields"  type="array" default="array(true)" brief="поля заголовка" />
///     </args>
///     <body>
  public function encode_head(Mail_Message_Part $message, array $fields = array(true)) {
    Core_Strings::begin_binary();

    $include_by_default = isset($fields[0]) ? (boolean) $fields[0] : true;

    foreach ($message->head as $field) {
      $name = $field->name;

      $include_field = isset($fields[$name]) ?
        $fields[$name] :
        $include_by_default;

      if ($include_field) $this->write($field->encode());
    }

    Core_Strings::end_binary();
    return $this->output;
  }
///     </body>
///   </method>

///   <method name="encode_body" returns="mixed">
///     <brief>Кодирует содержимое сообщения</brief>
///     <args>
///       <arg name="msg" type="Mail.Message.Part" brief="сообщение" />
///     </args>
///     <body>
  public function encode_body(Mail_Message_Part $msg) {
    Core_Strings::begin_binary();

    if ($msg instanceof Mail_Message_Message && $msg->is_multipart())
      $boundary = $msg->head['Content-Type']['boundary'];

    if (isset($boundary)) {
      if ($msg->preamble != '') $this->write($msg->preamble);

      foreach ($msg->body as $part) {
        $this->write("--$boundary");
        $this->encode($part);
      }
      $this->write("--$boundary--");

      if ($msg->epilogue != '') $this->write($msg->epilogue);
    } else {
      $body = ($msg->body instanceof IO_FS_File || $msg->body instanceof IO_Stream_ResourceStream) ?
        $msg->body->load() : $msg->body;
//      if ($msg->body instanceof IO_FS_File) {
//        foreach (
//          $this->encoder_for($msg)->
//            from_stream($msg->body->open()) as $line)
//              $this->write($line);
//        $msg->body->close();
//      } elseif ($msg->body instanceof IO_Stream_AbstractStream) {
//        foreach (
//          $this->encoder_for($msg)->from_stream($msg->body) as $line)
//            $this->write($line);
//      } else {
      $this->write($this->encoder_for($msg)->encode($body));
//      }
    }

    Core_Strings::end_binary();
    return $this->output;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="encoder_for">
///     <brief>Возвращает MIME-кодировщик для сообщения $msg</brief>
///     <args>
///       <arg name="msg" type="Mail.Message.Part" brief="сообщение" />
///     </args>
///     <body>
  protected function encoder_for(Mail_Message_Part $msg) {
    return MIME_Encode::encoder(isset($msg->head['Content-Transfer-Encoding']) ?
      $msg->head['Content-Transfer-Encoding']->value : null);
  }
///     </body>
///   </method>

///   <method name="write" returns="Mail.Serialize.Encoder">
///     <brief>Пишет результат кодирования</brief>
///     <body>
  protected function write($string = '') {
    if (substr($string, -1) != MIME::LINE_END) $string .= MIME::LINE_END;
    ($this->output instanceof IO_Stream_AbstractStream) ?
      $this->output->write($string) :
      $this->output .= $string;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Mail.Serialize.Decoder">
///     <brief>Разкодирует закодированное постовой сообщение</brief>
///   <depends supplier="IO.Stream.AbstractStream" stereotype="uses" />
///   <depends supplier="Mail.Message.Message" stereotype="creates" />
///   <depends supplier="MIME.Decode" stereotype="uses" />
class Mail_Serialize_Decoder {

  protected $input;

///   <protocol name="performing">

///   <method name="from" returns="Mail.Serializer.Decoder">
///     <brief>Устанвливает поток из которого береться сообщение</brief>
///     <args>
///       <arg name="stream" type="IO.Stream.AbstractStream" brief="поток" />
///     </args>
///     <body>
  public function from(IO_Stream_AbstractStream $stream) {
    $this->input = $stream;
    return $this;
  }
///     </body>
///   </method>

///   <method name="decode" returns="Mail.Message.Message">
///     <brief>Декодирует сообщение</brief>
///     <body>
  public function decode() {
    Core_Strings::begin_binary();
    $result = ($head = $this->decode_head()) ?
      $this->decode_part(Mail::Message()->head($head)) :
      null;
    Core_Strings::end_binary();
    return $result;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="decode_part" returns="Mail.Message.Part" access="protected">
///     <brief>Декодирует часть сообщения</brief>
///     <args>
///       <arg name="parent" type="Mail.Message.Part"/>
///     </args>
///     <body>
  protected function decode_part(Mail_Message_Part $parent) {
    if ($parent->is_multipart()) {

      $parent->preamble($this->skip_to_boundary($parent));

      while (true) {
          if (!$head = $this->decode_head()) break;

          if (Core_Regexps::match('{^[Mm]ultipart}', $head['Content-Type']->value)) {

          $parent->part($this->decode_part(Mail::Message()->head($head)));

          $parent->epilogue($this->skip_to_boundary($parent));

        } else {
          $decoder = MIME_Decode::decoder($head['Content-Transfer-Encoding']->value)->
            from_stream($this->input)->
            with_boundary($parent->head['Content-Type']['boundary']);

          $parent->part(Mail::Part()->
            head($head)->
            body($this->is_text_content_type($head['Content-Type']->value) ?
                   $decoder->to_string() :
                   $decoder->to_temporary_stream()));

          if ($decoder->is_last_part()) break;
        }
      }
    } else {
      $decoder = MIME_Decode::decoder($parent->head['Content-Transfer-Encoding']->value)->
        from_stream($this->input);

      $parent->body($this->is_text_content_type($head['Content-Type']->value) ?
                      $decoder->to_string() :
                      $decoder->to_temporary_stream());
    }
    return $parent;
  }
///     </body>
///   </method>

///   <method name="is_text_content_type" returns="boolean" access="protected">
///     <brief>Определяет является ли тип текстовым</brief>
///     <args>
///       <arg name="type" type="string" brief="тип" />
///     </args>
///     <body>
  protected function is_text_content_type($type) {
    return Core_Regexps::match('{^(text|message)/}', (string) $type);
  }
///     </body>
///   </method>

///   <method name="decode_head" returns="Mail.Message.Head" access="protected">
///     <brief>Декодирует заголовок сообщения</brief>
///     <body>
  protected function decode_head() {
    $data = '';

    while (($line = $this->input->read_line()) &&
           !Core_Regexps::match("{^\n\r?$}", $line)) $data .= $line;

    return $this->input->eof() ? null : Mail_Message_Head::from_string($data);
  }
///     </body>
///   </method>

///   <method name="skip_to_boundary" returns="string" access="protected">
///     <brief>Пролистывает сообщение до следующей границы</brief>
///     <args>
///       <arg name="boundary" type="string" brief="граница" />
///     </args>
///     <body>
  protected function skip_to_boundary(Mail_Message_Part $part) {
    $text = '';
    while (($line = $this->input->read_line()) &&
           !Core_Regexps::match("{^--".$part->head['Content-Type']['boundary']."(?:--)?\n\r?$}", $line)) $text .= $line;
    return $text;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
