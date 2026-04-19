<?php
namespace Yarri;

class EmailAddressRecognizer implements \ArrayAccess, \Countable, \Iterator{

	protected $_ary = array();

	function __construct($str_addresses){
		$this->_ary = self::split_addresses($str_addresses);
	}

	function toString(){
		return join(", ",$this->toArray());
	}
	function __toString(){ return $this->toString(); }

	function getId(){ return $this->toString(); }

	/* ArrayAccess methods */
	#[\ReturnTypeWillChange]	
	public function offsetExists ( $offset ){ return isset($this->_ary[$offset]); }
	#[\ReturnTypeWillChange]
	public function offsetGet ( $offset ){ return new EmailAddressRecognizer\RecognizedItem($this->_ary[$offset]); }
	#[\ReturnTypeWillChange]
	public function offsetSet ( $offset , $value ){ /* read only */ }
	#[\ReturnTypeWillChange]
	public function offsetUnset ( $offset ){ /* read only */ }

	/* Countable method */
	#[\ReturnTypeWillChange]
	public function count(){ return count($this->_ary); }

	/* Iterator methods */
	#[\ReturnTypeWillChange]
	public function current (){ $item = current($this->_ary); return $item ? new EmailAddressRecognizer\RecognizedItem($item) : null; }
	#[\ReturnTypeWillChange]
	public function key (){ return key($this->_ary); }
	#[\ReturnTypeWillChange]
	public function next (){ next($this->_ary); }
	#[\ReturnTypeWillChange]
	public function rewind (){ reset($this->_ary); }
	#[\ReturnTypeWillChange]
	public function valid (){
		$key = key($this->_ary);
		return ($key !== null && $key !== false);
	}

	function toArray(){
		$out = [];
		foreach($this as $item){ $out[] = $item; }
		return $out;
	}

	function isValid(){
		foreach($this as $item){
			if(!$item["valid"]){ return false; }
		}
		return true;
	}

	//vrati raw_adresu, nebo "" pokud neuspeje..
	//je to pro rychle ziskani adresy
	static function get_address($address){
		$_ar = self::split_addresses($address);
		if(count($_ar)==0){
			return "";
		}
		return $_ar[0]["address"];
	}

	static function get_domain($address){
		$_ar = self::split_addresses($address);
		if(count($_ar)==0){
			return "";
		}
		return $_ar[0]["domain"];	
	}

	static function split_addresses($address){
		$out = array();
		
		$groups = EmailAddressRecognizer::_split_addresses_by_group($address);

		foreach($groups as $group_item){
			$GROUP_NAME = $group_item["group"];
			$group_addresses = EmailAddressRecognizer::_split_addresses_by_emails($group_item["addresses"]);
			foreach($group_addresses as $FULL_ADDRESS){
				$_ar = EmailAddressRecognizer::_split_addresses_get_email($FULL_ADDRESS);
				$ADDRESS = $_ar["address"];
				$DOMAIN = $_ar["domain"];
				$PERSONAL = $_ar["name"];

				$out[] = array(
					"group" => $GROUP_NAME,
					"full_address" => $FULL_ADDRESS,
					"address" => $ADDRESS,
					"domain" => $DOMAIN,
					"name" => $PERSONAL,
					"valid" => $_ar["valid"],
				);
			}
		}

		return $out;
	}

	static function _split_addresses_by_group($address){
		$address = trim($address);

		$out = array();

		$_item = "";
		$_in_comment = false;
		$_in_doublequote = false;
		$_in_group = false;
		$_group = "";

		$char = null;
		$prev_char = null;
	
		$len = strlen($address);
		for($i=0;$i<$len;$i++){

			$char = $address[$i];

			if($char=='(' && $prev_char!="\\" && !$_in_comment && !$_in_doublequote){
				$_in_comment = true;
				$_item .= $char;
				$prev_char = $char;
				continue;
			}

			if($char==')' && $prev_char!="\\" && $_in_comment){
				$_in_comment = false;
				$_item .= $char;
				$prev_char = $char;
				continue;
			}

			if($char=='"' && $prev_char!="\\" && !$_in_comment){
				if($_in_doublequote){
					$_in_doublequote = false;
				}else{
					$_in_doublequote = true;
				}
				$_item .= $char;
				$prev_char = $char;
				continue;
			}

			
			if($char==":" && $prev_char!="\\" && !$_in_group && !$_in_comment && !$_in_doublequote){
				$_in_group = true;
				$_group = $_item;
				$prev_char = $char;
				$_item = "";
				continue;
			}

			if($char ==";" && $prev_char!="\\" && $_in_group && !$_in_comment && !$_in_doublequote){
				$_in_group = false;
				$out[] = array(
					"group" => trim($_group),
					"addresses" => trim($_item)
				);
				$prev_char = $char;
				$_group = "";
				$_item = "";
				continue;
			}

			$_item .= $char;
			
			$prev_char = $char;
		}
		if(strlen(trim($_item))>0){
			$out[] = array(
				"group" => trim($_group),
				"addresses" => trim($_item)
			);
		}
		return $out;
	}

	static function _split_addresses_by_emails($address){
		$out = array();

		$_item = "";
		$_in_comment = false;
		$_in_doublequote = false;

		$char = null;
		$prev_char = null;
	
		$len = strlen($address);
		for($i=0;$i<$len;$i++){

			$char = $address[$i];

			if($char=='(' && $prev_char!="\\" && !$_in_comment && !$_in_doublequote){
				$_in_comment = true;
				$_item .= $char;
				$prev_char = $char;
				continue;
			}

			if($char==')' && $prev_char!="\\" && $_in_comment){
				$_in_comment = false;
				$_item .= $char;
				$prev_char = $char;
				continue;
			}

			if($char=='"' && $prev_char!="\\" && !$_in_comment){
				if($_in_doublequote){
					$_in_doublequote = false;
				}else{
					$_in_doublequote = true;
				}
				$_item .= $char;
				$prev_char = $char;
				continue;
			}


			if($char =="," && $prev_char!="\\" && !$_in_comment && !$_in_doublequote){
				$out[] = trim($_item);
				$prev_char = $char;
				$_item = "";
				continue;
			}

			$_item .= $char;
			
			$prev_char = $char;
		}

		if(strlen(trim($_item))>0){
			$out[] = trim($_item);
		}
		
		return $out;	
	}

	static function _split_addresses_get_email($address){
		$address = (string)$address;
		$address = trim($address);

		$out = array(
			"valid" => false,
			"address" => "",
			"domain" => "",
			"name" => ""
		);

		if(preg_match('/^[^"\s]+@.+$/',$address,$pieces)){
			$out["valid"] = true;
			$out["address"] = $address;
		}elseif(preg_match('/^"?(.*)"?\s*<(.+@.+)>$/',$address,$pieces)){
			$out["valid"] = true;
			$out["address"] = trim($pieces[2]);
			$out["name"] = trim($pieces[1]);
		}elseif(preg_match('/^"?(.*)"?\s*(\\b.+@.+)$/',$address,$pieces)){
			$out["valid"] = true;
			$out["address"] = trim($pieces[2]);
			$out["name"] = trim($pieces[1]);
		}

		if($out["valid"]==true){
			$out["address"] = trim($out["address"]);
			$out["name"] = trim($out["name"]);
			$_ar = explode("@",$out["address"]);
			$out["domain"] = end($_ar);
		}

		$out["name"] = preg_replace('/"$/','',$out["name"]); //zustavaji mi na koci uvozovky
		$out["name"] = strtr($out["name"],[
			'\"' => '"',
			"\\\\" => "\\",
		]);

		if($out["valid"]){
			// spolehlivejsi validace prevzata z EmailField z frameworku ATK14
			$email_pattern = "/^([-!#$%&'*+\\/=?^_`{}|~0-9A-Z]+(\.[-!#$%&'*+\\/=?^_`{}|~0-9A-Z]+)*".'|"([\001-\010\013\014\016-\037!#-\[\]-\177]|\\[\001-011\013\014\016-\177])*"'.')@(?:[A-Z0-9-]+\.)+[A-Z]{2,14}$/i';
			if(!preg_match($email_pattern,$out["address"])){
				$out["valid"] = false;
				$out["address"] = "";
			}
		}

		return $out;
	}
}
