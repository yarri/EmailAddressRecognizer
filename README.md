EmailAddressRecognizer
======================

[![Tests](https://github.com/yarri/EmailAddressRecognizer/actions/workflows/tests.yml/badge.svg)](https://github.com/yarri/EmailAddressRecognizer/actions/workflows/tests.yml)

Parses email addresses from a `To:` or `Cc:` header value. Handles plain addresses, named addresses, quoted display names, and RFC 2822 group syntax. The result is iterable, countable, and accessible as an array.

Installation
------------

    composer require yarri/email-address-recognizer

Basic Usage
-----------

```php
$ear = new Yarri\EmailAddressRecognizer('John Doe <john@doe.com>, samantha@doe.com');

count($ear);        // 2
$ear->isValid();    // true
echo $ear;          // "John Doe <john@doe.com>, samantha@doe.com"

$ear[0]->getAddress();     // "john@doe.com"
$ear[0]->getName();        // "John Doe"
$ear[0]->getFullAddress(); // "John Doe <john@doe.com>"
$ear[0]->getDomain();      // "doe.com"
$ear[0]->isValid();        // true

$ear[1]->getAddress();     // "samantha@doe.com"
$ear[1]->getName();        // ""
$ear[1]->getFullAddress(); // "samantha@doe.com"
```

Supported Address Formats
--------------------------

```php
// Plain address
new Yarri\EmailAddressRecognizer('john@doe.com');

// Named address
new Yarri\EmailAddressRecognizer('John Doe <john@doe.com>');

// Quoted display name (allows commas and special characters in the name)
new Yarri\EmailAddressRecognizer('"Doe, John" <john@doe.com>');

// RFC 2822 group syntax
new Yarri\EmailAddressRecognizer('IT: John Doe <john@doe.com>, jane@doe.com;');

// Multiple addresses
new Yarri\EmailAddressRecognizer('john@doe.com, Jane Doe <jane@doe.com>');
```

Iterating Over Addresses
------------------------

`EmailAddressRecognizer` implements `Iterator`, `Countable`, and `ArrayAccess`:

```php
$ear = new Yarri\EmailAddressRecognizer('john@doe.com, Jane Doe <jane@doe.com>');

foreach ($ear as $item) {
    echo $item->getAddress();  // "john@doe.com", then "jane@doe.com"
}

count($ear);  // 2
$ear[0];      // RecognizedItem for john@doe.com
```

Validation
----------

`isValid()` returns `true` only when every parsed address is valid. Individual addresses can be checked separately:

```php
$ear = new Yarri\EmailAddressRecognizer('john@doe.com, not-an-address');

$ear->isValid();       // false — at least one address is invalid

$ear[0]->isValid();    // true
$ear[1]->isValid();    // false
$ear[1]->getAddress(); // "" (empty for invalid addresses)
```

Input is normalized on output — extra whitespace is trimmed and trailing commas are dropped:

```php
echo new Yarri\EmailAddressRecognizer('  john@doe.com ,  jane@doe.com  ');
// "john@doe.com, jane@doe.com"
```

RFC 2822 Groups
---------------

Group names are available via `getGroup()`:

```php
$ear = new Yarri\EmailAddressRecognizer('IT: John Doe <john@doe.com>, jane@doe.com;');

$ear[0]->getGroup(); // "IT"
$ear[1]->getGroup(); // "IT"
```

RecognizedItem
--------------

A single address string can be parsed directly using `RecognizedItem`:

```php
$item = new Yarri\EmailAddressRecognizer\RecognizedItem('"Doe, John" <john@doe.com>');

$item->isValid();        // true
$item->getAddress();     // "john@doe.com"
$item->getName();        // "Doe, John"
$item->getFullAddress(); // '"Doe, John" <john@doe.com>'
$item->getDomain();      // "doe.com"
$item->getGroup();       // ""
echo $item;              // '"Doe, John" <john@doe.com>'
```

`RecognizedItem` is invalid if the input contains zero or more than one address:

```php
(new Yarri\EmailAddressRecognizer\RecognizedItem(''))->isValid();                             // false
(new Yarri\EmailAddressRecognizer\RecognizedItem('john@doe.com, jane@doe.com'))->isValid();   // false
```

Static Helpers
--------------

Quick extraction without creating a full object:

```php
Yarri\EmailAddressRecognizer::get_address('John Doe <john@doe.com>'); // "john@doe.com"
Yarri\EmailAddressRecognizer::get_domain('John Doe <john@doe.com>');  // "doe.com"
```

Testing
-------

    composer install --dev
    cd test
    ../vendor/bin/run_unit_tests

License
-------

EmailAddressRecognizer is free software distributed [under the terms of the MIT license](http://www.opensource.org/licenses/mit-license)

[//]: # ( vim: set ts=2 et: )
