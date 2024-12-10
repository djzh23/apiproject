<?php


namespace App\Rules;

class AttributeHelper
{
    // Define the mapping as a static array
    protected static $mappings = [
        'firstname' => 'Vorname',
        'lastname' => 'Nachname',
        'city' => 'Stadt',
        'country' => 'Land',
        'number' => 'Rufnummer',
        'pzl' => 'Postleitzahl',
        'email' => 'Email',
        'password' => 'Kennwort',
        'steueridentifikationsnummer' =>'Steueridentifikationsnummer',
        'street' => 'Strasse',
        'bank_name' => 'Bankname',
        'bic' => 'BIC',
        'iban' => 'IBAN',
    ];

    // Static method to retrieve the mapped string
    public static function get($key)
    {
        return self::$mappings[$key] ?? null; // Return null if the key does not exist
    }

}
