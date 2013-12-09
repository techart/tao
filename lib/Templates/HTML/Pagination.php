<?php
/**
 * Templates.HTML.Pagination
 * 
 * Хелпер для вывода HTML-pager в шаблоне
 * 
 * @package Templates\HTML\Pagination
 * @version 0.2.1
 */

Core::load('Templates.HTML', 'Data.Pagination');

/**
 * @package Templates\HTML\Pagination
 */
class Templates_HTML_Pagination
  implements Core_ModuleInterface,
             Templates_HelperInterface {
  const VERSION = '0.2.1';


/**
 * Фабричный метод, возвращает объект класса Templates.HTML.Pagination
 * 
 * @return Templates_HTML_Pagination
 */
  static public function instance() { return new Templates_HTML_Pagination(); }



/**
 * Формирует HTML-pager
 * 
 * @param Templates_HTML_Template $t
 * @param Data_Pagination_Pager $pager
 * @param array $call
 * @param array $options
 * @return string
 */
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


}

