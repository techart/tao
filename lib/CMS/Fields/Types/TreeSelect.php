<?php
/**
 * @package CMS\Fields\Types\TreeSelect
 */


Core::load('CMS.Fields.Types.Select');

class CMS_Fields_Types_TreeSelect extends CMS_Fields_AbstractField implements Core_ModuleInterface {

	const VERSION = '0.0.0';

	public function view_value($value,$name,$data) {
		$value = parent::view_value($value,$name,$data);
		if (isset($data['items'])) {
			$items = CMS::items_for_select($data['items']);
			$outs = $this->process_items($items, $data);
			$value = $outs[1][$value];
		}
		return $value;
	}

	protected function stdunset($data) {
		$res = parent::stdunset($data);
		return $this->punset($res, 'text-wrap', 'flat', 'level_up', 'create_tree', 'container_style', 'empty_value');
	}
	
	protected function layout_preprocess($l, $name, $data) {
		$id = $this->url_class();
		$code = "; $(function() { $('.{$id} div.tree_select').filter('.name_{$name}').each(
					function() {
	 					TAO.fields.tree_select($(this));
						}
		)});";
		$l->append_to('js', $code);
		$l->with('url_class', $id);
		
		$l->use_scripts(CMS::stdfile_url('scripts/fields/tree-select.js'));
		$l->use_styles(CMS::stdfile_url('styles/fields/tree-select.css'));
		$l->use_scripts(CMS::stdfile_url('scripts/jquery/scroll-to.js'));
		return parent::layout_preprocess($l, $name, $data);
	}
	
	protected function preprocess($template, $name, $data) {
		$t = parent::preprocess($template, $name, $data);
		$items = isset($data['__items']) ? $data['__items'] : CMS::items_for_select($data['items']);
		$empty_value = (isset($data['empty_value'])) ? $data['empty_value'] : null;
		$outs = $this->process_items($items, $data);
	
		$t->with(array(
			'wrap' => $wrap = !isset($data['text-wrap']) ? 'none' : $data['text-wrap'],
			'node_wrap_class' => "text-wrap_".$wrap."_node",
			'caption_wrap_class' => "text-wrap_".$wrap."_caption",
			'selectioner_wrap_class' => "text-wrap_".$wrap."_selectioner",
			'container_style' => isset($data['container_style'])?$data['container_style']:null,
			'level_up' => !isset($data['level_up']) ? 1 : $data['level_up'],
			'empty_value' => $empty_value,
		));
		
		return $t->with('flat_items', $outs[1],'items', $outs[0]);
	}
	
	public function process_items($items, $data) {
		$tree_items = isset($data['flat']) && $data['flat'] === false ? $items : Core_Arrays::create_tree($items, $data['create_tree']);
		$out = array();
		$flat_out = array();
		
		$options = isset($data['create_tree'])? $data['create_tree'] : array();
		$options['id_name'] = isset($options['id_name']) ? $options['id_name'] : 'id';
		$options['title_name'] = isset($options['title_name']) ? $options['title_name'] : 'title';
		$options['childs_name'] = isset($options['childs_name']) ? $options['childs_name'] : 'childs';
		$options['disabled_name'] = isset($options['disabled_name']) ? $options['disabled_name'] : 'disabled';
		
		$this->tree_generation($out, $flat_out, $tree_items, $options, $data);
		$outs = array($out, $flat_out);
		return $outs;
	}
	
	//TODO: use create_tree options
	public function tree_generation(&$out, &$flat_out, $tree_items, $options, $data) {
		$exclude = null;
		if (isset($data['exclude_current']) && $data['exclude_current'] && isset($data['__item'])) {
			$exclude = $data['__item']->id();
		}
		foreach ($tree_items as $key => $item) {
			if ($exclude && $exclude == $key) continue;
			if (is_string($item)) {
				$out[$key] = $item;
				$flat_out[$key] = $item;
			}
			else {// !empty -> isset
				if (isset($item[$options['title_name']])) {
					$out[$key][$options['title_name']] = $item[$options['title_name']];
					$flat_out[$key] = $item[$options['title_name']];
				}
				else {
					$out[$key][$options['title_name']] = (string) $item;
					$flat_out[$key] = (string) $item;
				}
				if (!empty($item[$options['disabled_name']])) {
					$out[$key][$options['disabled_name']] = $item[$options['disabled_name']];
				}
				if (!empty($item[$options['childs_name']])) {
					$out[$key][$options['childs_name']] = array();
					$this->tree_generation($out[$key][$options['childs_name']], $flat_out, $item[$options['childs_name']], $options, $data);
				}
			}
		}
		return $out;
	}
	
	//TODO:
	public function node_template($name, $data, $items, $node_wrap_class, $caption_wrap_class, $level_up, $current_level, $top_caption) {
		$i = 0;
		$size = sizeof($items);
		
		$res = '';
		foreach($items as $key => $item) {
			$suff = '';
			if ($i == 0) {
				$suff .= 'tree_item_first ';
			}
			if ($i == $size-1) {
				$suff .= 'tree_item_last ';
			}
			$class_active = ($key == $data['__item'][$name]) ? 'tree_select_caption_active' : '';
			$disabled = '';
			if (is_array($item)&&isset($item['disabled'])&&$item['disabled']) {
				$disabled = "tree_select_node_disabled";
			}
			if (is_array($item['childs']) && !empty($item['childs'])) {
				$title = CMS::lang($item['title']);
				$elbow_open_icon = ($level_up > $current_level) ? 'tree_select_elbow_open_icon' : '';
				$array_open_icon = ($level_up > $current_level) ? 'tree_select_array_open_icon' : '';
				$sub_display = ($level_up > $current_level) ? 'block' : 'none';

				print <<<HTML
				<div class="tree_select_array $suff" id="$key">
					<div class="tree_select_icon tree_select_elbow_icon $elbow_open_icon"></div>
					<div class="tree_select_node $disabled tree_select_item $node_wrap_class">
 						<div class="tree_select_caption tree_select_caption_array $caption_wrap_class $class_active"> 
							$title
						</div>
					</div>
					<div class="tree_select_sub" style="display: $sub_display">
HTML;
						print $this->node_template($name, $data, $item['childs'], $node_wrap_class, $caption_wrap_class, $level_up, $current_level+1,$top_caption);
				print <<<HTML
					</div>
				</div>
HTML;
			}
			else {
				if (is_string($item)) {
					$title = CMS::lang((string) $item);
				}	
				else {
					$title = CMS::lang($item['title']);
				}
				print <<<HTML
				<div class="tree_select_one $suff" id="$key">
					<div class="tree_select_branch"></div>
					<div class="tree_select_node $disabled $node_wrap_class">
						<div class="tree_select_caption tree_select_caption_one $caption_wrap_class $class_active">$title</div>
					</div>
				</div>
HTML;
			}
			$i++;
		}
		return $res;
	}
	
}
