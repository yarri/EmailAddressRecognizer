EmailAddressRecognizer
======================

Usage
-----

	  $ear = new Yarri\EmailAddressRecognizer('john@doe.com, Samatha Doe <samatha@doe.com>');
    $ear->isValid(); // true or false; true means that all the given addresses are correct
    foreach($ear->toAddress() as $address){
      $address->isValid(); // true or false
      echo $address->getAddress(); // "john@doe.com", "samatha@doe.com"
      echo $address->getName(); // "", "Samatha Doe"
      echo $address->getFullAddress(); // "john@doe.com", "Samatha Doe <samatha@doe.com>"
      echo $address->getDomain(); // "doe.com", "doe.com"
      echo $address->getGroup(); // "", ""
    }

Installation
------------

Just use the Composer:

    composer require yarri/email-address-recognizer

Testing
-------

Install required dependencies for development:

    composer update --dev

Run tests:

    cd test
    ../vendor/bin/run_unit_tests

License
-------

EmailAddressRecognizer is free software distributed [under the terms of the MIT license](http://www.opensource.org/licenses/mit-license)

[//]: # ( vim: set ts=2 et: )
