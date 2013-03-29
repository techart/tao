<?php
/// <module name="Service.Yandex.Direct.Manager" maintainer="timokhin@techart.ru" version="0.1.0">
Core::load('CLI.Application', 'IO.FS', 'Service.Yandex.Direct');

/// <class name="Service.Yandex.Direct.Manager" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <implements interface="CLI.RunInterface" />
///   <depends suppler="CLI.Application" stereotype="uses" />
///   <depends supplier="IO.FS" stereotype="uses" />
///   <depends supplier="Service.Yandex.Direct" stereotype="uses" />
class Service_Yandex_Direct_Manager implements Core_ModuleInterface, CLI_RunInterface {

///   <constants>
  const VERSION = '0.2.0';
///   </constants>

///   <protocol name="performing">

///   <method name="main" scope="class" returns="int">
///     <body>
  static public function main(array $argv) {
    return Core::with(new Service_Yandex_Direct_Manager_Application())->main($argv);
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>


/// <class name="Service.Yandex.Direct.Manager.Exception" extends="Core.Exception">
class Service_Yandex_Direct_Manager_Exception extends Core_Exception {}
/// </class>


/// <class name="Service.Yandex.Direct.Manager.MissingCertificateException" extends="Service.Yandex.Direct.Manager.Exception">
class Service_Yandex_Direct_Manager_MissingCertificateException extends Service_Yandex_Direct_Manager_Exception {
  protected $path;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="path" type="string" default="''" />
///     </args>
///     <body>
  public function __construct($path = '') {
    $this->path = $path;
    parent::__construct($path === '' ? 'Missing certificate' : "Missing certificate: $path");
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Service.Yandex.Direct.Manager.MissingTaskFileException" extends="Service.Yandex.Direct.Manager.Exception" stereotype="abstract">
class Service_Yandex_Direct_Manager_MissingTaskFileException extends Service_Yandex_Direct_Manager_Exception {

  protected $path;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="path" type="string" />
///     </args>
///     <body>
  public function __construct($path) {
    $this->path = $path;
    parent::__construct("Missing task file: $path");
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.Yandex.Direct.Manager.BadArgumentException" extends="Service.Yandex.Direct.Manager.Exception" stereotype="Exception">
class Service_Yandex_Direct_Manager_BadArgumentException extends Service_Yandex_Direct_Manager_Exception {
  protected $name;
  protected $value;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///     </args>
///     <body>
  public function __construct($name, $value) {
    $this->name = $name;
    $this->value = (string) $value;

    parent::__construct("Bad argument value for $name: $value");
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Service.Yandex.Direct.Manager.Task" stereotype="abstract">
class Service_Yandex_Direct_Manager_Task {

  protected $file;
  protected $name;
  protected $config;

  protected $log;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="file" type="IO.FS.File" />
///       <arg name="options" type="array" default="array" />
///     </args>
///     <body>
  public function __construct(IO_FS_File $file, Service_Yandex_Direct_Manager_Application $app) {
    $this->file = $file;
    $this->name = IO_FS::Path($file->path)->filename;
    $this->config = $app->config;
    $this->log = $app->log->context(array('task' => $this->name));
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="run">
///     <body>
  public function run() {
    $api = Service_Yandex_Direct::api();
    $this->log->debug('Task %s started', $this->name);
    try {
      $campaigns = (isset($this->config->preload) && $this->config->preload) ?
        ((isset($this->config->direct) && $this->config->direct) ?
          $api->all_campaigns() : $api->campaigns_for($this->name)) :
        new Service_Yandex_Direct_CampaignsCollection(array());
        //ob_start();
        include($this->file->path);
        //ob_end_clean();
    } catch (Exception $e) {
      $this->log->error("Task error: %s", $e->getMessage());
    }
  }
///     </body>
///   </method>

///   <method name="stay_special" returns="Service.Yandex.Direct.Manager.Task" access="protected">
///     <args>
///       <arg name="limit" type="float" />
///       <arg name="phrases" />
///       <arg name="delta" type="float" default="0" />
///     </args>
///     <body>
  protected function stay_special($limit, $phrases, $delta = 0) {
    $this->log->debug('Running stay_special, limit %.2f', $limit);

    $phrases = $this->get_phrases_for($phrases);
    $prices = $phrases->prices;
    
    foreach ($phrases as $phrase) {
      $d = is_string($delta) ? ((float)$delta)/100*$phrase->premium_min : $delta;
      $prices->by_id($phrase->id)->price = ($phrase->premium_min + $d) <= $limit ? $phrase->premium_min + $d : $limit;
    }
    return $this->update_prices($prices);
  }
///     </body>
///   </method>

///   <method name="stay_visible" returns="Service.Yandex.Direct.Manager.Task" access="protected">
///     <args>
///       <arg name="limit" type="float" />
///       <arg name="phrases" />
///       <arg name="delta" type="float" default="0" />
///     </args>
///     <body>
  protected function stay_visible($limit, $phrases, $delta = 0) {
    $this->log->debug('Running stay_visible, limit %.2f', $limit);

    $phrases = $this->get_phrases_for($phrases);
    $prices  = $phrases->prices;
    foreach ($phrases as $phrase) {
      $d = is_string($delta) ? ((float)$delta)/100*$phrase->min : $delta;
      $prices->by_id($phrase->id)->price =
        ($phrase->min + $d) <= $limit ? $phrase->min + $d : $limit;
    }

   return $this->update_prices($prices);
  }
///     </body>
///   </method>

///   <method name="only_special" returns="Service.Yandex.Direct.Manager.Task" access="protected">
///     <args>
///       <arg name="limit" type="float" />
///       <arg name="phrases" />
///       <arg name="delta" type="float" default="0" />
///      </args>
///      <body>
  protected function only_special($limit, $phrases, $delta = 0) {
    $this->log->debug('Running only_special, limit %.2f', $limit);
    $phrases = $this->get_phrases_for($phrases);
    $prices = $phrases->prices;
    foreach ($phrases as $phrase) {
      $d = is_string($delta) ? ((float)$delta)/100*$phrase->premium_min : $delta;
      $prices->by_id($phrase->id)->price = 
        ($phrase->premium_min + $d) <= $limit ? $phrase->premium_min + $d : 0.01;
    }
    return $this->update_prices($prices);
  }
///	</body>
///   </method>

///   <method name="try_first_stay_visible" returns="Service.Yandex.Direct.Manager.Task" access="protected">
///     <args>
///       <arg name="limit" type="float" />
///       <arg name="gap" type="float" />
///       <arg name="phrases" />
///       <arg name="delta" type="float" />
///     </args>
///     <body>
  protected function try_first_stay_visible($limit, $phrases, $delta = 0) {
    $this->log->debug('Running try_first_stay_visible, limit %.2f', $limit);
    $phrases = $this->get_phrases_for($phrases);
    $prices  = $phrases->prices;
    
    foreach ($phrases as $phrase) { 
      $price = $phrase->price; 
      $d = is_string($delta) ? ((float)$delta)/100*$phrase->min : $delta;
      switch (true) {
        case (($phrase->max + $d) <= $limit):
          $price = $phrase->max + $d;
          break;
        case ($phrase->min + $d) <= $limit:
          $price = $phrase->min + $d;
          break;
        default:
          $price = $limit;
      }
      $prices->by_id($phrase->id)->price = $price;
    }
    return $this->update_prices($prices);
  }
///    </body>
///  </method>

///   <method name="try_special_stay_visible" returns="Service.Yandex.Direct.Manager.Task" access="protected">
///     <args>
///       <arg name="limit" type="float" />
///       <arg name="gap" type="float" />
///       <arg name="phrases" />
///       <arg name="delta" type="float" />
///     </args>
///     <body>
  protected function try_special_stay_visible($limit, $gap, $phrases, $delta = 0) {
    $this->log->debug('Running try_special_stay_visible, limit %.2f', $limit);
    $phrases = $this->get_phrases_for($phrases);
    $prices  = $phrases->prices;
    
    foreach ($phrases as $phrase) { 
      $price = $phrase->price;
      switch (true) {
        case (($phrase->premium_min + $delta) <= $limit) && (($phrase->premium_min - $phrase->min) <= $gap):
          $price = $phrase->premium_min + $delta;
          break;
        case ($phrase->min + $delta) <= $limit:
          $price = $phrase->min + $delta;
          break;
        default:
          $price = $limit;
      }
      $prices->by_id($phrase->id)->price = $price;
    }
    return $this->update_prices($prices);
  }
///     </body>
///   </method>

///   <method name="try_special" returns="Service.Yandex.Direct.Manager.Task" access="protected">
///     <args>
///       <arg name="limit" type="float" />
///       <arg name="phrases" />
///       <arg name="delta" type="float" default="0" />
///     </args>
///     <body>
  protected function try_special($limit, $phrases, $delta = 0) {
    $this->log->debug('Running try_special, limit %.2f', $limit);

    $phrases = $this->get_phrases_for($phrases); 
    $prices = $phrases->prices;
    
    foreach ($phrases as $phrase)
      $prices->by_id($phrase->id)->price =
        ($phrase->premium_min + $delta < $limit) ?
          ($phrase->premium_min + $delta) :
          (($phrase->price < $phrase->min) ?
            ($phrase->min + $delta < $limit ?
              $phrase->min + $delta : $limit) :
              $limit);

    return $this->update_prices($prices);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="updated_prices" returns="Service.Yandex.Direct.Manager.Task" access="protected">
///     <args>
///       <arg name="prices" type="Service.Yandex.Direct.PricesCollection" />
///     </args>
///     <body>
  protected function update_prices(Service_Yandex_Direct_PricesCollection $prices) {
    if ($prices->update())
      $this->log->debug('Prices successfully updated');
    else
      $this->log->error('Prices not updated');
    return $this;
  }
///     </body>
///   </method>

///   <method name="get_phrases_for" returns="Service.Yandex.Direct.PhrasesCollection" access="private">
///     <args>
///       <arg name="phrases" />
///     </args>
///     <body>
  private function get_phrases_for($phrases) {
    switch (true) {
      case $phrases instanceof Service_Yandex_Direct_Campaign:
      case $phrases instanceof Service_Yandex_Direct_CampaignsCollection:
        $phrases = $phrases->all_banners()->all_phrases();
        break;
      case $phrases instanceof Service_Yandex_Direct_Banner:
      case $phrases instanceof Service_Yandex_Direct_BannersCollection:
        $phrases = $phrases->all_phrases();
        break;
      case $phrases instanceof Service_Yandex_Direct_PhrasesCollection:
        break;
      default:
        throw new Service_Yandex_Direct_Manager_BadArgumentException('stay_special->phrases', $phrases);
    }


    $phrases = $phrases->where(array('LowCTR' => 'No'));
    $this->log->debug('Got phrases: %d', count($phrases));
    return $phrases;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.Yandex.Direct.Manager.UserTask" extends="Service.Yandex.Direct.Manager.Task">
class Service_Yandex_Direct_Manager_UserTask extends Service_Yandex_Direct_Manager_Task {}
/// </class>


/// <class name="Service.Yandex.Direct.Manager.Application" extends="CLI.Application.Base">
class Service_Yandex_Direct_Manager_Application extends CLI_Application_Base {

  protected $processed = 0;
  protected $log;

///   <protocol name="performing">

///   <method name="run" returns="int">
///     <args>
///       <arg name="argv" type="array" />
///     </args>
///     <body>
  public function run(array $argv) {
    $this->
      check_certificate()->
      setup_api();
    
    return $this->config->run_all ? $this->run_all() : $this->run_tasks($argv);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="check_certificate" returns="Service.Yandex.Direct.Manager access="private">
///     <body>
  private function check_certificate() {
    if (!isset($this->config->cert)) throw new Service_Yandex_Direct_Manager_MissingCertificateException();
    if (!IO_FS::exists($p = $this->config->cert)) throw new Service_Yandex_Direct_Manager_MissingCertificateException($p);
    $this->log->debug('Using certificate: %s', $this->config->cert);
    return $this;
  }
///     </body>
///   </method>

///   <method name="configure_proxy" returns="array" access="private">
///     <body>
  private function configure_proxy() {
    $res = array();
    if (($proxy =  (isset($this->config->proxy) ?
           $this->config->proxy :
           ( ($p = getenv('http_proxy')) ? $p : ''))) &&
        ($m = Core_Regexps::match_with_results('{(?:https?://)?([^:]+):(?:(\d+))}', $proxy))) {
      if (isset($m[1])) $res['proxy_host'] = $m[1];
      if (isset($m[2])) $res['proxy_port'] = $m[2];
      $this->log->debug("Using proxy %s:%d", $m[1], $m[2]);
    }
    return $res;
  }
///     </body>
///   </method>

///   <method name="setup_api" returns="Service.Yandex.Direct.Manager" access="private">
///     <body>
  private function setup_api() {
    Service_Yandex_Direct::connect(
      array('local_cert' => $this->config->cert) + $this->configure_proxy());
    $this->log->debug('API initialized');
    return $this;
  }
///     </body>
///   </method>

///   <method name="run_all" returns="Service.Yandex.Direct.Manager" access="private">
///     <body>
  private function run_all() {
    $this->log->debug("Running all tasks for prefix %s", $this->config->prefix);
    foreach (IO_FS::Dir($this->config->prefix) as $file) {
      Core::with(new Service_Yandex_Direct_Manager_UserTask(IO_FS::File($file), $this))->run();
      $this->processed++;
    }
    $this->log->debug("All tasks complete");
  }
///     </body>
///   </method>

///   <method name="run_tasks" returns="Service.Yandex.Direct.Manager" access="private">
///     <args>
///       <arg name="tasks" type="array" />
///     </args>
///     <body>
  private function run_tasks(array $tasks) {
    $this->log->debug("Running task list");
    foreach ($tasks as $name) {
      $path = $this->config->prefix ? $this->config->prefix.$name.'.php' : $name;
      if (!IO_FS::exists($path)) throw new Service_Yandex_Direct_Manager_MissingTaskFileException($path);
      Core::with(new Service_Yandex_Direct_Manager_Task(IO_FS::File($path), $this))->run();
      $this->processed++;
    }
    $this->log->debug("Task list complete");

    return $this;
  }
///   </method>

///   <method name="setup" access="protected">
///     <body>
  protected function setup() {
    $this->options->
      brief('Service.Yandex.Direct.Manager '.Service_Yandex_Direct_Manager::VERSION.': Yandex.Direct campaigns manager')->
       string_option('cert',        '-c', '--cert',    'Client certificate')->
       string_option('config_file', '-s', '--config',  'Use configuration file')->
       string_option('proxy',       '-p', '--proxy',   'HTTP proxy')->
       string_option('prefix',      '-i', '--prefix',  'Tasks prefix')->
      boolean_option('preload',     '-l', '--preload', 'Preload campaigns')->
      boolean_option('run_all',     '-a', '--all',     'Run all tasks')->
      boolean_option('direct',      '-d', '--direct',  'Direct client, not agency');

    $this->config->certificate = null;
    $this->config->proxy       = null;
    $this->config->prefix      = '';
    $this->config->preload     = true;
    $this->config->run_all     = false;
    $this->config->direct      = false;
  }
///     </body>
///   </method>

///   <method name="configure" access="protected">
///     <body>
  protected function configure() {
    if ($this->config->config_file) {
      $this->log->debug('Using config: %s', $this->config->config_file);
      $this->load_config($this->config->config_file);
    }
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
?>
