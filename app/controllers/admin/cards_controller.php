<?php
class CardsController extends AdminController{
	function index(){
		$this->page_title = _("List of Products");

		$q = ($d = $this->form->validate($this->params)) ? $d["search"] : "";

		$conditions = $bind_ar = array();
		$conditions = array("deleted='f'");

		$q_up = Translate::Upper($q);

		if($ft_cond = FullTextSearchQueryLike::GetQuery("UPPER(".join("||' '||",array(
				"id",
				"COALESCE((SELECT body FROM translations WHERE record_id=cards.id AND table_name='cards' AND key='name' AND lang=:lang),'')",
				"COALESCE((SELECT body FROM translations WHERE record_id=cards.id AND table_name='cards' AND key='shortinfo' AND lang=:lang),'')",
			)).")",$q_up,$bind_ar)
		){
			$bind_ar[":lang"] = $this->lang;
			$ft_cond = array($ft_cond);

			$ft_cond[] = "cards.id IN (SELECT card_id FROM products WHERE ".FullTextSearchQueryLike::GetQuery("UPPER(".join("||' '||",array(
				"catalog_id",
				"COALESCE((SELECT body FROM translations WHERE record_id=products.id AND table_name='products' AND key='name' AND lang=:lang),'')",
				"COALESCE((SELECT body FROM translations WHERE record_id=products.id AND table_name='products' AND key='shortinfo' AND lang=:lang),'')",
			)).")",$q_up,$bind_ar).")";

			$ft_cond[] = "cards.id IN (SELECT card_id FROM card_sections WHERE ".FullTextSearchQueryLike::GetQuery("UPPER(".join("||' '||",array(
				"COALESCE((SELECT body FROM translations WHERE record_id=card_sections.id AND table_name='card_sections' AND key='name' AND lang=:lang),'')",
				"COALESCE((SELECT body FROM translations WHERE record_id=card_sections.id AND table_name='card_sections' AND key='body' AND lang=:lang),'')",
			)).")",$q_up,$bind_ar).")";

			$conditions[] = '('.join(') OR (',$ft_cond).')';
		}

		$this->sorting->add("name", array("order_by" => Translation::BuildOrderSqlForTranslatableField("cards", "name")));
		$this->sorting->add("created_at",array("reverse" => true));
		$this->sorting->add("updated_at",array("reverse" => true));
		$this->sorting->add("has_variants");

		$this->tpl_data["finder"] = Card::Finder(array(
			"conditions" => $conditions,
			"bind_ar" => $bind_ar,
			"order_by" => $this->sorting,
			"offset" => $this->params->getInt("offset"),
		));

	}

	function create_new() {
		$this->page_title = _("Adding a product");
		$this->_save_return_uri();
		if ($this->request->post() && ($d=$this->form->validate($this->params))) {
			/*
			$category_ids = $d["category_ids"];
			unset($d["category_ids"]); */

			$tags = $d["tags"];
			unset($d["tags"]);

			$catalog_id = $d["catalog_id"];
			unset($d["catalog_id"]);
			$d["has_variants"] = !$catalog_id; // pokud je catalog_id vyplneno - predpokladame, ze varianty nejsou

			$product_card = Card::CreateNewRecord($d);
			$product_card->setTags($tags);
			if($catalog_id){
				$product_card->createProduct(array(
					"catalog_id" => $catalog_id,
				));
			}

			$url = $this->_link_to(array("action" => "edit", "id" => $product_card));
			
			$this->flash->success(_("The product has been created").'<br><a href="'.$url.'">'._("Would you like to edit the product and add some extra data?")."</a>");
			$this->_redirect_back();
		}
	}

	function edit(){
		$this->page_title = _("Editing of the product");

		$first_product = $this->tpl_data["first_product"] = $this->card->getFirstProduct(array("visible" => null));

		$this->form->set_initial($this->card);
		$this->form->set_initial("tags",$this->card->getTags());
		//$this->form->set_initial("category_ids", $this->card->getCategories());
		if(!$this->card->hasVariants() && $first_product){
			$this->form->set_initial(array(
				"catalog_id" => $first_product->getCatalogId(),
			));
		}
		$this->_save_return_uri();

		$this->tpl_data["add_to_category_form"] = $this->_get_form("AddToCategoryForm");
		$this->tpl_data["products"] = $this->card->getProducts(array("visible" => null));


		if ($this->request->post() && ($d=$this->form->validate($this->params))) {
			/*
			$category_ids = $d["category_ids"];
			unset($d["category_ids"]); */

			$tags = $d["tags"];
			unset($d["tags"]);

			if(!$this->card->hasVariants()){
				$first_product_d = array(
					"catalog_id" => $d["catalog_id"],
				);
				$first_product->s($first_product_d);
			}
			unset($d["catalog_id"]);

			$this->card->s($d);
			$this->card->setTags($tags);

			/*
			$this->card->getCategoryLister()->clear();
			foreach($category_ids as $cat) {
				$this->card->addToCategory($cat);
			}*/

			$this->flash->success(_("Changes have been saved"));
			$this->_redirect_back();
		}

		$this->_prepare_categories();
	}

	function destroy(){
		if(!$this->request->post()){
			return $this->_execute_action("error404");
		}
		$this->card->destroy();
	}

	function enable_variants(){
		if(!$this->request->post() || $this->card->hasVariants()){
			return $this->_execute_action("error404");
		}

		$this->card->s("has_variants",true);
		
		// zde se nakopiruje name a shortinfo do prvni varianty
		$d = array();
		$card_ar = $this->card->toArray();
		foreach($GLOBALS["ATK14_GLOBAL"]->getSupportedLangs() as $l){
			$d["name_$l"] = $card_ar["name_$l"];
			$d["shortinfo_$l"] = $card_ar["shortinfo_$l"];
		}
		if($first_product = $this->card->getFirstProduct()){
			$first_product->s($d);
		}

		$this->flash->success(_("Varianty byly zaktivněny."));
		$this->_redirect_to(array(
			"action" => "edit",
			"id" => $this->card,
		));
	}

	function add_to_category(){
		if(!$this->request->post()){ return $this->_execute_action("error404"); }

		if($d = $this->form->validate($this->params)){
			$this->card->addToCategory($d["category"]);
		}

		$this->_prepare_categories();
	}

	function remove_from_category(){
		if(!$this->request->post()){ return $this->_execute_action("error404"); }

		$this->card->removeFromCategory($this->category);
	}

	function append_external_source() {
		$this->page_title = _("Připojení odkazu k produktu");
		$this->_save_return_uri();
		if($this->request->post() && ($d=$this->form->validate($this->params))) {

			$external_source = $this->_find("external_source","external_source_id");
			$this->card->appendExternalSource($external_source);
			$this->_redirect_back();
		}
	}

	function remove_external_source() {
		if(!$this->request->post()){ return $this->_execute_action("error404"); }

		$this->_save_return_uri();

		$external_source = $this->_find("external_source","source_id");
		$this->card->removeExternalSource($external_source);
		if (!$this->request->xhr()) {
			$this->flash->success(_("Odkaz odpojen"));
			return $this->_redirect_back();
		}
	}

	function _setup_breadcrumbs_filter() {
		if ($this->action=="index") {
			$this->breadcrumbs->addTextItem(_("Produkty"));
		} else {
			$this->breadcrumbs->addItem(_("Produkty"), $this->_link_to("cards/index"));
		}
		if ($this->action=="edit") {
			$this->breadcrumbs->addTextItem( sprintf(_("Karta produktu '%s' (id: %d)"), $this->card->getName(), $this->card->getId()) );
		}
	}

	function _before_filter() {
		if (in_array($this->action, array("edit","destroy","enable_variants","add_to_category","remove_from_category","append_external_source","remove_external_source"))) {
			$this->_find("card");
		}

		if ($this->action=="remove_from_category"){
			$this->_find("category","category_id");
		}
	}

	function _prepare_categories(){
		// nebudeme zobrazovat filtracni kategorie, na to je jina akce: card_filters/edit
		$categories = array();
		$filter_categories_count = 0;
		$filters = array();
		foreach($this->card->getCategories() as $c){
			if(($p = $c->getParentCategory()) && $p->isFilter()){
				$p_id = $p->getId();
				if(!isset($filters[$p_id])){ $filters[$p_id] = array("filter" => $p, "items" => array()); }
				$filters[$p_id]["items"][] = $c;
				$filter_categories_count++;
				continue;
			}
			$categories[] = $c;
		}
		$this->tpl_data["categories"] = $categories;
		$this->tpl_data["filter_categories_count"] = $filter_categories_count;
		$this->tpl_data["filters"] = $filters;
	}
}