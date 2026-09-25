<?php

// Hakee tilin saldon POP Pankilta (Crosskey, 0710) ja tulostaa myös pankin raakavastauksen.
//
// Käyttö:
//   php hae_crosskey_saldo.php <pankkiyhteys_tunnus> <salasana> <IBAN>

if (php_sapi_name() != 'cli') {
  die("Tätä scriptiä voi ajaa vain komentoriviltä!\n");
}

if (trim($argv[1] ?? '') == '' or trim($argv[2] ?? '') == '' or trim($argv[3] ?? '') == '') {
  die("Käyttö: php hae_crosskey_saldo.php <pankkiyhteys_tunnus> <salasana> <IBAN>\n");
}

ini_set("include_path", ini_get("include_path") . PATH_SEPARATOR . dirname(__FILE__));
error_reporting(E_ALL);
ini_set("display_errors", 1);

// xmlseclibs / wse-php (allekirjoitukset), kuten p.php:ssä
require "/var/www/robrichards/vendor/autoload.php";

require "inc/connect.inc";
require "inc/functions.inc";
require "inc/pankkiyhteys_functions.inc";

$tulos = hae_tilin_saldo_crosskey(array(
  "pankkiyhteys_tunnus"   => (int) $argv[1],
  "pankkiyhteys_salasana" => $argv[2],
  "iban"                  => $argv[3],
  "debug"                 => 1,
));

echo "\n";
var_dump($tulos);
