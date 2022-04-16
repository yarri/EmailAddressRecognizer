<?php
namespace Yarri\EmailAddressRecognizer;

class RecognizedItem extends \Dictionary {

	protected $str;

	function __construct($string_ordata){
		if(is_array($string_ordata)){
			parent::__construct($string_ordata);
			$this->str = $this["full_address"];
		}else{
			$this->str = $string_ordata;
			$er = new \Yarri\EmailAddressRecognizer($string_ordata);
			$items = $er->toArray();
			if(!$items){
				$ar = ["valid" => false];
			}else{
				$ar = $items[0]->toArray();
				if(sizeof($items)>1){
					$ar["valid"] = false;
				}
			}
			parent::__construct($ar);
		}
	}

	function toString(){ return $this->str; }
	function __toString(){ return $this->toString(); }

	function getId(){ return $this->toString(); }
}
