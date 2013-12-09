<?php
/**
 * Dev.DB.Log
 * 
 * @package Dev\DB\Log
 * @version 0.2.0
 */

Core::load('DB', 'IO.Stream');

/**
 * @package Dev\DB\Log
 */
class Dev_DB_Log implements Core_ModuleInterface {

  const VERSION        = '0.2.0';


/**
 * @param IO_Stream_AbstractStream $stream
 * @param bool $explain
 * @return Dev_DB_Log_Logger
 */
  static public function Logger(IO_Stream_AbstractStream $stream = null) {
    return new Dev_DB_Log_Logger($stream);
  }

}


/**
 * @package Dev\DB\Log
 */
class Dev_DB_Log_Logger implements DB_QueryExecutionListener {

  protected $stream;
  protected $explain = false;


/**
 * @param IO_Stream_AbstractStream $stream
 * @param bool $explain
 */
  public function __construct(IO_Stream_AbstractStream $stream = null, $explain = false) {
    $this->stream = Core::if_null($stream, IO::stderr());
    $this->explain = $explain;
  }



/**
 * @param DB_Cursor $cursor
 */
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

  public function write($type, $message) {
    $this->stream->format("%s %s %s\n",
    substr($type,0,3),
    Time::now()->as_string(),
      str_replace("\n", ' ', $message));
  }

}

