<?php
/**
 * Dev.Benchmark
 * 
 * @package Dev\Benchmark
 * @version 0.1.2
 */

Core::load('Object');

/**
 * @package Dev\Benchmark
 */
class Dev_Benchmark implements Core_ModuleInterface {

const MODULE  = 'Dev.Benchmark';
const VERSION = '0.1.2';


/**
 * @return Dev_Benchmark_Timer
 */
static public function Timer() { return new Dev_Benchmark_Timer(); }

/**
 * @return Dev_Benchmark_Timer
 */
static public function start() { return Core::with(new Dev_Benchmark_Timer())->start(); }

}

/**
 * @package Dev\Benchmark
 */
class Dev_Benchmark_Event extends Object_Struct {
  protected $time;
  protected $note;
  protected $lap;
  protected $cumulative;
  protected $percentage;


/**
 * @param  $time
 * @param string $note
 * @param  $lap
 * @param  $cumulative
 * @param  $percentage
 */
  public function __construct($time, $note, $lap, $cumulative, $percentage) {
    $this->time = $time;
    $this->note = (string) $note;
    $this->lap  = $lap;
    $this->cumulative = $cumulative;
    $this->percentage = $percentage;
  }

}

/**
 * @package Dev\Benchmark
 */
class Dev_Benchmark_Timer implements
  Core_StringifyInterface,
  Core_PropertyAccessInterface {

  protected $started_at;
  protected $events = array();
  protected $stopped_at;


/**
 */
  public function start() {
    $this->events = array();
    $this->stopped_at = null;
    $this->started_at = microtime(true);
    return $this;
  }

/**
 * @return Dev_Benchmark_Timer
 */
  public function lap($note = '') {
    $this->events[] = array(microtime(true), (string) $note);
    return $this;
  }

/**
 * @return Dev_Benchmark_Timer
 */
  public function stop() {
    if ($this->stopped_at === null) $this->stopped_at = microtime(true);
    $this->events[] = array($this->stopped_at, '_stop_');
    return $this;
  }

/**
 * @param int $limit
 * @param array $call
 * @param string $note
 */
  public function repeat($note, $times, $call, $args = array()) {
    if (!$this->started_at) $this->start();
    for ($i = 0; $i < $times; $i++)  call_user_func_array($call, $args);
    return $this->lap($note);
  }



/**
 * @return string
 */
  public function __toString() { return $this->as_string(); }

/**
 * @return string
 */
  public function as_string() {
    $result = sprintf("    # NAME                             TIME    CUMULATIVE PERCENTAGE\n");

    foreach ($this->get_events() as $k => $v) {
      $result .= sprintf(
        " %4d %-28s %8.3f      %8.3f   %7.3f%%\n",
        $k, $v->note, $v->lap, $v->cumulative, $v->percentage);
    }

    return $result;
  }



/**
 * @param string $property
 * @return mixed
 */
  public function __get($property) {
    switch ($property) {
      case 'started_at':
      case 'stopped_at':
        return $this->$property;
      case 'total_time':
        return $this->stopped_at - $this->started_at;
      case 'events':
        return $this->get_events();
      default:
        throw new Core_MissingPropertyException($property);
    }
  }

/**
 * @param string $property
 * @param  $value
 * @return mixed
 */
  public function __set($property, $value) {
    throw $this->__isset($property) ?
      new Core_ReadOnlyPropertyException($property) :
      new Core_MissingPropertyException($property);
  }

/**
 * @param string $property
 * @return boolean
 */
  public function __isset($property) {
  switch ($property) {
    case 'started_at':
    case 'stopped_at':
    case 'total_time':
    case 'events':
    case 'summary':
      return true;
    default:
      return false;
  }
  }

/**
 * @param string $property
 */
  public function __unset($property) {
    throw $this->__isset($property) ?
      new Core_UndestroyablePropertyException($property) :
      new Core_MissingPropertyException($property);
  }



/**
 * @return ArrayObject
 */
  private function get_events() {
    if (!$this->started_at) $this->start();
    if (!$this->stopped_at) $this->stop();

    $total  = $this->stopped_at - $this->started_at;

    $result = new ArrayObject();

    foreach ($this->events as $k => $v) {
      $result->append(new Dev_Benchmark_Event(
        $v[0], $v[1],
        ($lap = ($v[0] - ($k > 0 ? $this->events[$k-1][0] : $this->started_at))),
        $v[0] - $this->started_at,
        $lap/$total*100 ));
    }
    return $result;
  }

}

