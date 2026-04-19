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

	// Splits $str on $delimiter, respecting parenthesized comments and double-quoted strings.
	// Returns an array of substrings (the delimiter itself is not included).
	static function _split_on_delimiter($str, $delimiter){
		$out = [];
		$current = "";
		$in_comment = false;
		$in_doublequote = false;
		$prev_char = null;
		$len = strlen($str);
		for($i=0;$i<$len;$i++){
			$char = $str[$i];
			if($char=='(' && $prev_char!="\\" && !$in_comment && !$in_doublequote){
				$in_comment = true;
				$current .= $char;
			}elseif($char==')' && $prev_char!="\\" && $in_comment){
				$in_comment = false;
				$current .= $char;
			}elseif($char=='"' && $prev_char!="\\" && !$in_comment){
				$in_doublequote = !$in_doublequote;
				$current .= $char;
			}elseif($char==$delimiter && $prev_char!="\\" && !$in_comment && !$in_doublequote){
				$out[] = $current;
				$current = "";
			}else{
				$current .= $char;
			}
			$prev_char = $char;
		}
		$out[] = $current;
		return $out;
	}

	static function _split_addresses_by_group($address){
		$address = trim($address);
		$out = [];
		foreach(self::_split_on_delimiter($address, ';') as $segment){
			$segment = trim($segment);
			if($segment==='') continue;
			$parts = self::_split_on_delimiter($segment, ':');
			if(count($parts)>1){
				$out[] = array(
					"group" => trim($parts[0]),
					"addresses" => trim(join(':', array_slice($parts, 1))),
				);
			}else{
				$out[] = array(
					"group" => "",
					"addresses" => $segment,
				);
			}
		}
		return $out;
	}

	static function _split_addresses_by_emails($address){
		$out = [];
		$parts = self::_split_on_delimiter($address, ',');
		// The last part is what remains after the final comma (or the whole string
		// if there is no comma). It is discarded when empty to silently ignore
		// a trailing comma. Middle empty parts are kept so they surface as invalid
		// addresses and cause isValid() to return false.
		$last = array_pop($parts);
		foreach($parts as $item){
			$out[] = trim($item);
		}
		$last = trim($last);
		if(strlen($last)>0){
			$out[] = $last;
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
