<?php
/**
 * Lze zadat id nebo path
 * 	123
 *	/mistnosti/jidelna/
 */
class CategoryField extends CharField{
	function __construct($options = array()){
		$options["null_empty_output"] = true;
		$options += array(
			"consider_filter" => false,
			"follow_pointing_category" => true,
			"widget" => new TextInput(array(
				"attrs" => array(
					"data-suggest_url" => Atk14Url::BuildLink(array("namespace" => "api", "controller" => "categories_suggestions","action" => "index"))."?format=json&q=",
				),
			))
		);

		$this->consider_filter = $options["consider_filter"];
		$this->follow_pointing_category = $options["follow_pointing_category"];

		parent::__construct($options);

		$this->update_messages(array(
			"no_such_category" => "Taková kategorie neexistuje",
			"filter" => "Toto je filtr. Takovou kategorii nelze zvolit.",
		));
	}

	function format_initial_data($value){
		if(is_numeric($value)){
			$value = Category::FindById($value);
		}

		if(is_object($value)){
			$value = "/".$value->getPath()."/";
		}

		return $value;
	}

	function clean($value){
		list($err,$value) = parent::clean($value);
		if($err || !$value){ return array($err,$value); }

		if(is_numeric($value)){
			if(!$category = Category::FindById($value)){
				return array($this->messages["no_such_category"],null);
			}
		}else{
			$value = preg_replace('/^\//','',$value);
			$value = preg_replace('/\/$/','',$value); // "/mistnosti/jidelna/" => "mistnosti/jidelna"
			if(!$category = Category::GetInstanceByPath($value)){
				return array($this->messages["no_such_category"],null);
			}
		}

		if($this->follow_pointing_category && $category->isAlias()){
			$category = $category->getPointingToCategory();
		}

		if(!$this->consider_filter && $category->isFilter()){
			return array($this->messages["filter"],null);
		}

		return array(null,$category);
	}
}
