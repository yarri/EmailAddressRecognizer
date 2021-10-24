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
			parent::__construct($er[0]->toArray());
		}

		//var_dump($this->data);
	}

	function toString(){ return $this->str; }
	function __toString(){ return $this->toString(); }

	function getId(){ return $this->toString(); }

	function toArray(){
		if($this["valid"]){
			// ha! originalni kod listonose spatne validuje emailovou adresu,
			// tady pouzijeme EmailField z Atk14
			$f = new \EmailField(array());
			list($err,$val) = $f->clean($this["address"]);
			if($err){
				$this["valid"] = false;
				$this["address"] = "";
			}
		}
		return parent::toArray();
	}
}
