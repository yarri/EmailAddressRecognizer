<?php
class TcEmailAddressRecognizer extends TcBase{

	function test_basic_usage(){
		$ear = new Yarri\EmailAddressRecognizer("john@doe.com");
		$this->assertEquals(true,$ear->isValid());
		$this->assertEquals("john@doe.com",(string)$ear);
		$items = $ear->toArray();
		$this->assertEquals(1,sizeof($items));

		$ear = new Yarri\EmailAddressRecognizer('John Doe <john@doe.com>');
		$this->assertEquals(true,$ear->isValid());
		$this->assertEquals("John Doe <john@doe.com>",(string)$ear);
		$items = $ear->toArray();
		$this->assertEquals(1,sizeof($items));

		// two valid addresses
		$ear = new Yarri\EmailAddressRecognizer('John Doe <john@doe.com>, samantha@doe.com');
		$this->assertEquals(true,$ear->isValid());
		$this->assertEquals("John Doe <john@doe.com>, samantha@doe.com",(string)$ear);
		$items = $ear->toArray();
		$this->assertEquals(2,sizeof($items));
		$this->assertEquals("John Doe <john@doe.com>",(string)$items[0]);
		$this->assertEquals("samantha@doe.com",(string)$items[1]);

		$ear = new Yarri\EmailAddressRecognizer('John Doe <john@doe.com>, , samantha@doe.com');
		$this->assertEquals(false,$ear->isValid());
		$this->assertEquals("John Doe <john@doe.com>, , samantha@doe.com",(string)$ear);
		$items = $ear->toArray();
		$this->assertEquals(3,sizeof($items));
		$this->assertEquals("John Doe <john@doe.com>",(string)$items[0]);
		$this->assertEquals(true,$items[0]["valid"]);
		$this->assertEquals("",(string)$items[1]);
		$this->assertEquals(false,$items[1]["valid"]);
		$this->assertEquals("samantha@doe.com",(string)$items[2]);
		$this->assertEquals(true,$items[2]["valid"]);

		// invalid email address
		$ear = new Yarri\EmailAddressRecognizer('john.doe');
		$this->assertEquals(false,$ear->isValid());
		$this->assertEquals("john.doe",(string)$ear);
		$items = $ear->toArray();
		$this->assertEquals(1,sizeof($items));
		$this->assertEquals("john.doe",(string)$items[0]);
		$this->assertEquals(false,$items[0]["valid"]);

		// empty input
		$ear = new Yarri\EmailAddressRecognizer(' ');
		$this->assertEquals(true,$ear->isValid());
		$this->assertEquals(" ",(string)$ear);
		$items = $ear->toArray();
		$this->assertEquals(0,sizeof($items));

		// valid & invalid email address
		$ear = new Yarri\EmailAddressRecognizer('John Doe <john@doe.com>, samantha@@doe.com');
		$this->assertEquals(false,$ear->isValid());
		$this->assertEquals("John Doe <john@doe.com>, samantha@@doe.com",(string)$ear);
		$items = $ear->toArray();
		$this->assertEquals(2,sizeof($items));
		$this->assertEquals("John Doe <john@doe.com>",(string)$items[0]);
		$this->assertEquals(true,$items[0]["valid"]);
		$this->assertEquals("samantha@@doe.com",(string)$items[1]);
		$this->assertEquals(false,$items[1]["valid"]);
	}

	function test_RecognizedItem(){
		foreach([
			"john@doe.com",
			"John@doe.com",
			"JOHN@DOE.COM",
			"john@doe.com",
			// "<john@doe.com>", // ??
			"John Doe <john@doe.com>",
			'"Doe, John" <john@doe.com>'
		] as $email_address){
			$item = new Yarri\EmailAddressRecognizer\RecognizedItem($email_address);
			$this->assertEquals(true,$item["valid"],$email_address);
		}

		foreach([
			"",
			"xxx",
			"john.doe.com",
			"@doe.com",
			"john@",
			"john@@doe.com",
			"john@doe@com",
			"john@doe.com, samantha@doe.cz",
			"John Doe <john.doe.com>",
			"John Doe <>",
		] as $email_address){
			$item = new Yarri\EmailAddressRecognizer\RecognizedItem($email_address);
			$this->assertEquals(false,$item["valid"],$email_address);
		}
	}


	function test_empty_list(){
		$ers = new Yarri\EmailAddressRecognizer("");
		$this->assertTrue(is_object($ers));
		$this->assertEquals(0,sizeof($ers));

		$ers = new Yarri\EmailAddressRecognizer(" ");
		$this->assertTrue(is_object($ers));
		$this->assertEquals(0,sizeof($ers));
	}

	function test_array_access(){
		$ers = new Yarri\EmailAddressRecognizer('John Doe <john@doe.com>, "Smantha Doe" samantha@doe.com');
		$out = array();
		foreach($ers as $er){
			$this->assertTrue(is_object($er));
			$this->assertTrue($er["valid"]);
			$out[] = $er["address"];
		}
		$this->assertEquals("john@doe.com, samantha@doe.com",join(", ",$out));
	}

	function test(){
		$er = new Yarri\EmailAddressRecognizer\RecognizedItem('"John Doe" <john.doe@example.com>');
		$this->assertEquals(array(
			"valid" => true,
			"address" => "john.doe@example.com",
			"full_address" => '"John Doe" <john.doe@example.com>',
			"name" => "John Doe",
			"domain" => "example.com",
			"group" => "",
			"valid" => true,
		),$er->toArray());
		$this->assertEquals('"John Doe" <john.doe@example.com>',"$er");
		$this->assertEquals("john.doe@example.com",$er["address"]);
		$this->assertEquals("example.com",$er["domain"]);
		$this->assertEquals("John Doe",$er["name"]);
		$this->assertEquals("",$er["group"]);
		$this->assertEquals(true,$er["valid"]);

		$er = new Yarri\EmailAddressRecognizer\RecognizedItem('Hacker: Ian Fear <me-123@ian.fear.com>');
		$this->assertEquals(array(
			"valid" => true,
			"address" => "me-123@ian.fear.com",
			"full_address" => 'Ian Fear <me-123@ian.fear.com>',
			"name" => "Ian Fear",
			"domain" => "ian.fear.com",
			"group" => "Hacker",
			"valid" => true,
		),$er->toArray());
		$this->assertEquals('Hacker: Ian Fear <me-123@ian.fear.com>',"$er");
		$this->assertEquals("me-123@ian.fear.com",$er["address"]);
		$this->assertEquals("ian.fear.com",$er["domain"]);
		$this->assertEquals("Ian Fear",$er["name"]);
		$this->assertEquals("Hacker",$er["group"]);
		$this->assertEquals(true,$er["valid"]);
	}

	function test_2_addresses(){
		$er = new Yarri\EmailAddressRecognizer("john.doe@yahoo.com, Jack Daniels <jack@daniels.com>");
		$this->assertEquals("john.doe@yahoo.com",$er[0]["address"]);
		$this->assertEquals("jack@daniels.com",$er[1]["address"]);


		$er = new Yarri\EmailAddressRecognizer("Jan Tuna <tuna@nova.cz>");
		$this->assertEquals(1,sizeof($er));
		$this->assertEquals("Jan Tuna <tuna@nova.cz>","$er");
		$this->assertEquals("Jan Tuna <tuna@nova.cz>","$er[0]");
		$this->assertEquals("tuna@nova.cz",$er[0]["address"]);

		$er = new Yarri\EmailAddressRecognizer("Jan Tuna <tuna@nova.cz>, Bob Brown <BobBrown@nova.cz>");
		$this->assertEquals(2,sizeof($er));
		$this->assertEquals("Jan Tuna <tuna@nova.cz>, Bob Brown <BobBrown@nova.cz>","$er");
		// prvni adresa
		$this->assertEquals("Jan Tuna <tuna@nova.cz>","$er[0]");
		$this->assertEquals("tuna@nova.cz",$er[0]["address"]);
		// druha adresa
		$this->assertEquals("Bob Brown <BobBrown@nova.cz>","$er[1]");
		$this->assertEquals("BobBrown@nova.cz",$er[1]["address"]);
	}

	function test_invalid_address(){
		$ers = new Yarri\EmailAddressRecognizer("Some <dope>, Joke@xxx.com");
		
		$this->assertEquals(2,sizeof($ers)); // je tady 1 adresa

		$this->assertEquals("Some <dope>","$ers[0]");
		$this->assertEquals(false,$ers[0]["valid"]);
		$this->assertEquals("",$ers[0]["address"]);

		$this->assertEquals("Joke@xxx.com","$ers[1]");
		$this->assertEquals(true,$ers[1]["valid"]);
		$this->assertEquals("Joke@xxx.com",$ers[1]["address"]);

		$er = new Yarri\EmailAddressRecognizer\RecognizedItem("test@@tes.cz");

		$this->assertEquals(false,$er["valid"]);
	}
}
