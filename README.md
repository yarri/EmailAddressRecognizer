EmailAddressRecognizer
======================

Parses email addresses from the To: or Cc: header in a message.

Usage
-----

    $ear = new Yarri\EmailAddressRecognizer("john@doe.com, Samatha Doe <samatha@doe.com>");

    $addresses = $ear->toArray();

    sizeof($addresses); // how many addresses are there?

    $ear->isValid(); // are all the addresses valid or not?

    $addresses[0]->isValid(); // true
    echo $addresses[0]->getAddress(); // "john@doe.com"
    echo $addresses[0]->getName(); // ""
    echo $addresses[0]->getFullAddress(); // "john@doe.com"
    echo $addresses[0]->getDomain(); // "doe.com"
    echo $addresses[0]->getGroup(); // ""

    $addresses[1]->isValid(); // true
    echo $addresses[1]->getAddress(); // "samatha@doe.com"
    echo $addresses[1]->getName(); // "Samatha Doe"
    echo $addresses[1]->getFullAddress(); // "Samatha Doe <samatha@doe.com>"
    echo $addresses[1]->getDomain(); // "doe.com"
    echo $addresses[1]->getGroup(); // ""

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
