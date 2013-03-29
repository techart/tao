<?php
/// <module name="Dev.DB.Log" maintainer="timokhin@techart.ru" version="0.2.0">

Core::load('DB', 'IO.Stream');

/// <class name="Dev.DB.Log" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="Dev.DB.Log.Logger" stereotype="creates" />
class Dev_DB_Log implements Core_ModuleInterface {

///   <constants>
  const VERSION        = '0.2.0';
///   </constants>

///   <protocol name="building">

///   <method name="Logger" returns="Dev.DB.Log.Logger" scope="class">
///     <args>
///       <arg name="stream" type="IO.Stream.AbstractStream" />
///       <arg name="explain" type="bool" />
///     </args>
///     <body>
  static public function Logger(IO_Stream_AbstractStream $stream = null) {
    return new Dev_DB_Log_Logger($stream);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Dev.DB.Log.Logger">
///   <implements interface="DB.QueryExecutionListener" />
///   <depends supplier="DB.Cursor" stereotype="uses" />
///   <depends supplier="IO.Stream.AbstractStream" stereotype="uses" />
class Dev_DB_Log_Logger implements DB_QueryExecutionListener {

  protected $stream;
  protected $explain = false;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="stream" type="IO.Stream.AbstractStream" />
///       <arg name="explain" type="bool" />
///     </args>
///     <body>
  public function __construct(IO_Stream_AbstractStream $stream = null, $explain = false) {
    $this->stream = Core::if_null($stream, IO::stderr());
    $this->explain = $explain;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="listening">

///   <method name="on_execute">
///     <args>
///       <arg name="cursor" type="DB.Cursor" />
///     </args>
///     <body>
  public function on_execute(DB_Cursor $cursor) {
    $time = Time::now()->as_string();
    $this->stream->format(
      "sql %s %f %d %s\n",
      $time, $cursor->execution_time,
      $cursor->num_of_rows,
      str_replace("\n", ' ', $cursor->sql));

    if ($this->explain) {
      foreach ($cursor->explain() as $e)
        $this->stream->format(
          "exp %s %d %s %s [%s] %d %s %s %d [%s]\n",
          $time,
          $e->id,
          $e->select_type,
          $e->table,
          Core::if_null($e->possible_keys,'-'),
          Core::if_null($e->key_len, '-'),
          Core::if_null($e->key, '-'),
          Core::if_null($e->ref, '-'),
          Core::if_null($e->rows, '-'),
          Core::if_null($e->extra, ''));
    }
  }
///     </body>
///   </method>

  public function write($type, $message) {
    $this->stream->format("%s %s %s\n",
    substr($type,0,3),
    Time::now()->as_string(),
      str_replace("\n", ' ', $message));
  }

///   </protocol>
}
/// </class>

/// </module>
