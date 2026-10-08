# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

EmailAddressRecognizer is a small PHP library (`yarri/email-address-recognizer`) that parses and validates email
addresses from `To:`/`Cc:`-style header values: plain addresses, named addresses (`John Doe <john@doe.com>`),
quoted display names (`"Doe, John" <john@doe.com>`), and RFC 2822 group syntax
(`IT: john@doe.com, jane@doe.com;`). PHP >= 5.6 is supported; CI tests against PHP 5.6 through 8.5, so avoid
syntax/features newer than PHP 5.6 in `src/`.

## Commands

Install dependencies:

    composer install --dev

Run the full test suite (must `cd test` first — the runner globs `tc_*.php` in the working directory):

    cd test && ../vendor/bin/run_unit_tests

Run a single test file:

    cd test && ../vendor/bin/run_unit_tests tc_email_address_recognizer

Run multiple specific test files:

    cd test && ../vendor/bin/run_unit_tests tc_email_address_recognizer tc_issue

There is no separate lint/build step; the composer autoloader is classmap-based (`src/`), so new files are picked
up automatically by `composer dump-autoload` (run as part of `composer install`).

## Architecture

Two classes, both under `src/`, autoloaded via classmap (filenames are snake_case, class/namespace names are
PascalCase — this mapping is implicit, not configured anywhere):

- `Yarri\EmailAddressRecognizer` (`src/email_address_recognizer.php`) — takes a raw header string and parses it
  into a list of address records. Implements `ArrayAccess`, `Countable`, `Iterator` so the whole object can be
  looped/counted/indexed directly; each access wraps the underlying record in a `RecognizedItem`.
- `Yarri\EmailAddressRecognizer\RecognizedItem` (`src/email_address_recognizer/recognized_item.php`) — represents
  a single parsed address. Extends `Dictionary` (from the `atk14/dictionary` package), so fields are accessed both
  via getters (`getAddress()`, `getName()`, `getFullAddress()`, `getDomain()`, `getGroup()`, `isValid()`) and via
  array access (`$item["address"]`, etc). Can also be constructed directly from a single address string.

### Parsing pipeline

`EmailAddressRecognizer::split_addresses()` is the core entry point (used by the constructor and by the static
helpers `get_address()`/`get_domain()`). It runs in three stages, each independently testable:

1. `_split_addresses_by_group($address)` — splits on `;` (via `_split_on_delimiter`) to separate RFC 2822 groups,
   then splits each segment on the first `:` to pull out an optional group name. Segments without a `:` get
   group `""`.
2. `_split_addresses_by_emails($group_addresses)` — splits each group's address list on `,` (via
   `_split_on_delimiter`) into individual full-address strings. A trailing empty segment (from a trailing comma)
   is silently dropped; empty segments *between* commas are kept so they surface later as invalid entries.
3. `_split_addresses_get_email($full_address)` — parses one full-address string into
   `address`/`domain`/`name`/`valid` via a cascade of regexes (bare `<addr>`, bare `addr`, `"name" <addr>`,
   `name <addr>`), then re-validates the extracted address against a stricter RFC-ish email regex (ported from
   ATK14's `EmailField`). If the strict regex fails, the whole record is marked invalid and `address` is cleared.

`_split_on_delimiter($str, $delimiter)` is the shared low-level splitter underpinning both the group and email
splits: it splits on a single character while respecting parenthesized comments `(...)`, double-quoted strings
`"..."`, and backslash-escaping, so delimiters inside names/comments don't cause incorrect splits.

Validity is per-record: `EmailAddressRecognizer::isValid()` is true only if *every* parsed item is valid;
individual `RecognizedItem`s carry their own `isValid()`.

### Tests

Tests live in `test/tc_*.php`, extending `TcBase` from `atk14/tester` (loaded via `test/initialize.php`, which
just requires the composer autoloader and defines `TEST`). `test/tc_email_address_recognizer.php` covers the
public API end-to-end (including `_split_on_delimiter` directly) and is the best reference for expected
parsing behavior across edge cases (trailing commas, group syntax, quoted names with escaped quotes, invalid
addresses, etc). `test/tc_issue.php` is for regression tests tied to specific reported issues.
