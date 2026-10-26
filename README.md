# Mitgliedsbeitrag / Membership fee
Membership fee calculates membership fees based on a member's role affiliations.

Membership fee is a plugin for the online membership management system Admidio (https://www.admidio.org) and cannot be used without it.

Online documentation is available here: https://www.admidio.org/dokuwiki/doku.php?id=de:plugins:mitgliedsbeitrag#mitgliedsbeitrag

## Default values and languages
Default values that the plugin writes to the database come from the language files, in the language that is active at that moment:

- the default settings in `system/configdata.php` (fee text prefix and suffix, family role prefix and description, example bank and account holder, invoice file name, mandate reference prefixes), written when the plugin is first started or a setting is added;
- the descriptions of the profile fields the plugin creates (`system/install_db.php`);
- the e-mail texts, written when they are first shown.

These language keys start with `PLG_MEMBERSHIPFEE_DEFAULT_`. Once written, a value belongs to the organisation: changing the language later does not change it. The default fee text prefix contains the current year. Values that end up in SEPA files only use characters allowed there.

The payment information ID in the SEPA XML file (`PLG_MEMBERSHIPFEE_SEPA_PAYMENT_ID`) is also taken from the language files.

## Change history
The [change history](https://www.admidio.org/dokuwiki/doku.php?id=de:plugins:mitgliedsbeitrag#aenderungshistorie) provides detailed information about the bug fixes and improvements in each version.

