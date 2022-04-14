<?php
class TcEmailAddressRecognizer extends TcBase{

	function test1(){
		$item = new Yarri\EmailAddressRecognizer\RecognizedItem("john@doe.com");
		$this->assertEquals(true,$item["valid"]);

		$item = new Yarri\EmailAddressRecognizer\RecognizedItem("john@doe.com, samantha@doe.cz");
		$this->assertEquals(false,$item["valid"]);
	}

	function test_basic_usage(){
		$ear = new Yarri\EmailAddressRecognizer("john@doe.com");
		$this->assertEquals(true,$ear->isValid());
		$this->assertEquals("john@doe.com",(string)$ear);
		$items = $ear->toArray();
		$this->assertEquals(1,sizeof($items));

		$ear = new Yarri\EmailAddressRecognizer('John Doe <john@doe.com>');
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
