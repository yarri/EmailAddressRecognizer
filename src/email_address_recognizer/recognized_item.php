<?php
namespace Yarri\EmailAddressRecognizer;

class RecognizedItem implements \ArrayAccess{

	protected $data;
	protected $str;

	function __construct($string_ordata){
		if(is_array($string_ordata)){
			$this->data = $string_ordata;
			$this->str = $this->data["full_address"];
		}else{
			$this->str = $string_ordata;
			$er = new \Yarri\EmailAddressRecognizer($string_ordata);
			$this->data = $er[0]->data;
		}

		//var_dump($this->data);
	}

	function toString(){ return $this->str; }
	function __toString(){ return $this->toString(); }

	function getId(){ return $this->toString(); }

	function toArray(){
		$i = $this->data;
		if($i["valid"]){
			// ha! originalni kod listonose spatne validuje emailovou adresu,
			// tady pouzijeme EmailField z Atk14
			$f = new \EmailField(array());
			list($err,$val) = $f->clean($i["address"]);
			if($err){
				$i["valid"] = false;
				$i["address"] = "";
			}
		}
		return array(
			"valid" => $i["valid"],
			"address" => $i["address"],
			"name" => $i["personal"],
			"domain" => $i["domain"],
			"group" => $i["group_name"],
		);
	}

	/* ArrayAccess methods */
	public function offsetExists ($offset){ return $a = $this->toArray(); return in_array("$offset",array_keys($a)); }
	public function offsetGet ( $offset ){ $a = $this->toArray(); return $a["$offset"]; }
	public function offsetSet ( $offset , $value ){ /* read only*/ }
	public function offsetUnset ( $offset ){ /* read only */ }
}
