<?php
namespace Yarri;

class EmailAddressRecognizer implements \ArrayAccess, \Countable, \Iterator{

	protected $_str;
	protected $_ary = array();
	protected $charset = null;

	function __construct($str_addresses,$options = []){
		$options += [
			"charset" => defined("DEFAULT_CHARSET") ? constant("DEFAULT_CHARSET") : "UTF-8",
		];

		$this->_str = (string)$str_addresses;
		$this->_ary = self::split_addresses($str_addresses);
		$this->charset = $options["charset"];
	}

	function toString(){ return $this->_str; }
	function __toString(){ return $this->toString(); }

	function getId(){ return $this->toString(); }

	/* ArrayAccess methods */
	public function offsetExists ( $offset ){ return isset($this->_ary[$offset]); }
	public function offsetGet ( $offset ){ return new EmailAddressRecognizer\RecognizedItem($this->_ary[$offset]); }
	public function offsetSet ( $offset , $value ){ /* read only */ }
	public function offsetUnset ( $offset ){ /* read only */ }

	/* Countable method */
	public function count(){ return sizeof($this->_ary); }

	/* Iterator methods */
	public function current (){ $item = current($this->_ary); return $item ? new EmailAddressRecognizer\RecognizedItem($item) : null; }
	public function key (){ return key($this->_ary); }
	public function next (){ $item = next($this->_ary); return $item ? new EmailAddressRecognizer\RecognizedItem($item) : null; }
	public function rewind (){ reset($this->_ary); }
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


// nasleduje puvodni metody

/*
	function justify_body($body) // zarovna radky v bnody podle konstanty MAX_LINE_CHARS


*/

//jedna radka emailu: 76 characters (bez CRLF)
/*
I'm wondering if there is a way to control how MH generates RFC email
addresses?  From what I understand, there are two styles:

1) Full Name <user@domain.name>
2) user@domain.name (Full Name)

vole, bude nutne tracovat backslashes: 
http://www.zvon.org/tmRFC/RFC2822/Output/chapter9.html#sub1
From: "Joe Q. Public" <john.q.public@example.com>
To: Mary Smith <mary@x.test>, jdoe@example.org, Who? <one@y.test>
Cc: <boss@nil.test>, "Giant; \"Big\" Box" <sysservices@example.net>
Date: Tue, 1 Jul 2003 10:52:37 +0200
Message-ID: <5678.21-Nov-1997@example.com>

9.1.3. A.1.3. Group addresses

----
From: Pete <pete@silly.example>
To: A Group:Chris Jones <c@a.test>,joe@where.test,John <jdoe@one.test>;
Cc: Undisclosed recipients:;
Date: Thu, 13 Feb 1969 23:32:54 -0330
Message-ID: <testabcd.1234@silly.example>

Testing.
----

In this message, the "To:" field has a single group recipient named A Group which contains 3 addresses, and a "Cc:" field with an empty group recipient named Undisclosed recipients. 


 White space, including folding white space, and comments can be inserted between many of the tokens of fields. Taking the example from A.1.3, white space and comments can be inserted into all of the fields.

----
From: Pete(A wonderful \) chap) <pete(his account)@silly.test(his host)>
To:A Group(Some people)
     :Chris Jones <c@(Chris's host.)public.example>,
         joe@example.org,
  John <jdoe@one.test> (my dear friend); (the end of the group)
Cc:(Empty list)(start)Undisclosed recipients  :(nobody(that I know))  ;
Date: Thu,
      13
        Feb
          1969
      23:32
               -0330 (Newfoundland Time)
Message-ID:              <testabcd.1234@silly.test>

Testing.
----

The above example is aesthetically displeasing, but perfectly legal. Note particularly (1) the comments in the "From:" field (including one that has a ")" character appearing as part of a quoted-pair); (2) the white space absent after the ":" in the "To:" field as well as the comment and folding white space after the group name, the special character (".") in the comment in Chris Jones's address, and the folding white space before and after "joe@example.org,"; (3) the multiple and nested comments in the "Cc:" field as well as the comment immediately following the ":" after "Cc"; (4) the folding white space (but no comments except at the end) and the missing seconds in the time of the date field; and (5) the white space before (but not within) the identifier in the "Message-ID:" field.


*/

	//vrati raw_adresu, nebo "" pokud neuspeje..
	//je to pro rychle ziskani adresy
	static function get_address($address){
		$_ar = self::split_addresses($address);
		if(sizeof($_ar)==0){
			return "";
		}
		return $_ar[0]["address"];
	}

	static function get_domain($address){
		$_ar = self::split_addresses($address);
		if(sizeof($_ar)==0){
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
			reset($group_addresses);
			foreach($group_addresses as $FULL_ADDRESS){
				//echo $GROUP_NAME." --> ".$FULL_ADDRESS."<br>";
				$_ar = EmailAddressRecognizer::_split_addresses_get_email($FULL_ADDRESS);
				if(!$_ar["valid"]){
					//continue;
				}
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
	
		for($i=0;$i<strlen($address);$i++){

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

			
			if($char==":" && $prev_char!="\\" && !$_in_group && !$_in_comment && !$_in_comment && !$_in_doublequote){
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
				$group = "";
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
	
		for($i=0;$i<strlen($address);$i++){

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
		settype($address,"string");
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
			$out["address"] = trim($out["address"]); // TODO: Tady bylo strtolower, asi bych zmensil jenom domenu, nebo nejak takto: JOHN@DOE.COM -> john@doe.com -> John@doe.com -> John@doe.com
			$out["name"] = trim($out["name"]);
			$_ar = explode("@",$out["address"]);
			$out["domain"] = "$_ar[1]";
		}

		//zustavaji mi na koci uvozovky
		$out["name"] = preg_replace('/"$/','',$out["name"]);

		if($out["valid"]){
			// ha! originalni kod listonose spatne validuje emailovou adresu,
			// tady pouzijeme EmailField z Atk14
			$f = new \EmailField(array());
			list($err,$val) = $f->clean($out["address"]);
			if($err){
				$out["valid"] = false;
				$out["address"] = "";
			}
		}

		return $out;
	}

	static function quote_email_term($term,$charset = DEFAULT_CHARSET){
		//tab muze prijit a crlf
		//Encoded lines must not be longer than 76 characters, not counting the trailing CRLF.
		//pozor velka pismena!!!
		/*
		http://www.zvon.org/tmRFC/RFC2049/Output/chapter3.html
		In particular, the only characters that are
          known to be consistent across all gateways are the 73
          characters that correspond to the upper and lower case
          letters A-Z and a-z, the 10 digits 0-9, and the
          following eleven special characters:

            "'"  (US-ASCII decimal value 39)
            "("  (US-ASCII decimal value 40)
            ")"  (US-ASCII decimal value 41)
            "+"  (US-ASCII decimal value 43)
            ","  (US-ASCII decimal value 44)
            "-"  (US-ASCII decimal value 45)
            "."  (US-ASCII decimal value 46)
            "/"  (US-ASCII decimal value 47)
            ":"  (US-ASCII decimal value 58)
            "="  (US-ASCII decimal value 61)
            "?"  (US-ASCII decimal value 63)

          A maximally portable mail representation will confine
          itself to relatively short lines of text in which the
          only meaningful characters are taken from this set of
          73 characters.  The base64 encoding follows this rule.

			*/

		if(preg_match('/^[-a-zA-Z0-9\'()+,\\.\\/:=? !]*$/',$term)){
			return $term;
		}
		
		$out = "=?$charset?Q?";
		for($i=0;$i<strlen($term);$i++){
			$char = $term[$i];
			if(
				("$char">="0" && "$char"<="9") ||
				("$char">="a" && "$char"<="z") ||
				("$char">="A" && "$char"<="Z")
			){
				$out .= $char;
			}elseif("$char" == " "){
				$out .= "_";
			}else{
				$ord = ord($char);
				$out .= "=".strtoupper(dechex($ord));
			}
		}
		$out .= "?=";
		return $out;
	}

	static function justify_body($body){

		//$body = str_replace("\n\r","\n",$body);
		$body = str_replace("\r\n","\n",$body);
		$body = str_replace("\r","\n",$body);
		$body = str_replace("\t","  ",$body);
		
		$ar = explode("\n",$body);
		$out = array();
		for($i=0;$i<sizeof($ar);$i++){
			$ar[$i] = preg_replace('/\s*$/','',$ar[$i]);
			if(strlen($ar[$i])<=MAX_LINE_CHARS){
				$out[] = $ar[$i];
			}else{
				$offset = 0;
				$get_line = true;
				while($get_line){

					$offset_increment = MAX_LINE_CHARS;

					if($offset+$offset_increment>strlen($ar[$i])){
						$out[] = substr($ar[$i],$offset,$offset_increment); 
						break; /* while($get_line) */
					}


					//echo $offset_increment."<br>";

					$got_space = false;
					$space_decrement = 0;
					while(!$got_space){
						//TODO: na nasledujicim radku je chyba, napr.: Uninitialized string offset: 146
						if($ar[$i][$offset + $offset_increment - $space_decrement] == " "){
							//echo "z<br>";
							$got_space = true;
							$offset_increment = $offset_increment - $space_decrement;
							break;
						}
						$space_decrement++;
						//echo "x<br>";
						if($space_decrement>(MAX_LINE_CHARS/2)){
							//echo "y<br>";
							break;
						}
					}

					//echo $offset_increment."<br>";

					$out[] = substr($ar[$i],$offset,$offset_increment);

					
					if(($offset+$offset_increment)>=strlen($ar[$i])){
						$get_line = false;
						break;
					}
					$offset = $offset + $offset_increment;
				}
			}
			
		}
		return join("\n",$out);
	}

	static function check_address($mail){
 	 if($mail==""){
 	   return false;
 	 }
 	 if(!preg_match('/^[-0-9a-z_][-0-9a-z._]{1,}@[-0-9a-z][-0-9a-z._]{1,}\.[a-z]{2,10}$/',$mail)){
 	   return false;
 	 }
 	 return true;
	}

	protected function _sendmail_render_email_address($from,$from_name){
		return $from_name ? _sendmail_escape_email_name($from_name)." <$from>" : $from;
	}

	protected function _sendmail_escape_email_name($from_name){
		$out = _sendmail_escape_subject($from_name);
		if($out==$from_name){
			$out = '"'.str_replace('"','\"',$out).'"';
		}
		return $out;
	}

	protected function _sendmail_escape_subject($subject){
		$charset = $this->charset;
		if(Translate::CheckEncoding($subject,"ascii")){ return $subject; }

		$out = array();
		$escape_in_use = false;
		$out[] = "=?$charset?Q?";
		for($i=0;$i<strlen($subject);$i++){
			$c = $subject[$i];
			if(in_array($c,array("=","?",":","/","_","[","]")) || !Translate::CheckEncoding($c,"ascii")){
				$out[] = "=".strtoupper(dechex(ord($c)));
				$escape_in_use = true;
			}else{
				// RFC 2047 dovoluje mezeru nahradit podtrzitkem
				$out[] = ($c==" ")?"_":$c;
				if ($c==" ") $escape_in_use = true;
			}
		}
		if(!$escape_in_use){ return $subject; }

		$out[] = "?=";
		return join("",$out);
	}
}
