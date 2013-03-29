<?php
/// <module name="Templates.HTML.Pagination" version="0.2.1" maintainer="timokhin@techart.ru">
///   <brief>Хелпер для вывода HTML-pager в шаблоне</brief>

Core::load('Templates.HTML', 'Data.Pagination');

/// <class name="Templates.HTML.Pagination" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <implements interface="Templates.HelperInterface" />
class Templates_HTML_Pagination
  implements Core_ModuleInterface,
             Templates_HelperInterface {
///   <constants>
  const VERSION = '0.2.1';
///   </constants>

///   <protocol name="building">

///   <method name="instance" returns="Templates.HTML.Pagination" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Templates.HTML.Pagination </brief>
///     <body>
  static public function instance() { return new Templates_HTML_Pagination(); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="generating">

///   <method name="pager" returns="string">
///     <brief>Формирует HTML-pager</brief>
///     <args>
///       <arg name="t" type="Templates.HTML.Template" brief="шаблон" />
///       <arg name="pager" type="Data.Pagination.Pager" />
///       <arg name="call" type="array" brief="callback массив" />
///       <arg name="options" type="array" default="array()" brief="массив опций" />
///     </args>
///     <body>
  public function pager(Templates_HTML_Template $t, Data_Pagination_Pager $pager, array $call, array $options = array()) {
    $res = '';

    if (isset($options['info']) && $options['info'])
      $res = $t->content_tag(
        'div',
        sprintf('%d &ndash; %d / %d',
          $pager->current->first_item,
          $pager->current->last_item,
          $pager->num_of_items), array('class' => 'info'));

    foreach ($pager->current->window(Core::if_not_set($options, 'padding', 10))->pages as $page) {
      $res .= $t->link_to(
        call_user_func_array(
          array($call[0], $call[1]),
          count($call) > 2 ?  array_merge($call[2], array('page' => $page->number)) : array($page->number)),
        $page->number,
        array('class' => $page->number == $pager->current->number ? 'active' : '') );
    }

    return $t->content_tag(
      'div',
      $res,
      array('class' => (isset($options['class']) ? $options['class'].' ' : '').'pager'));
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>

/// </module>
