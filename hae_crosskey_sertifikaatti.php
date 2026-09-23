<?php

// Hakee UUDEN, Crosskeyn itse myöntämän allekirjoitussertifikaatin
// sepa_get_certificate_crosskey():lla ja TULOSTAA sen (EI tallenna kantaan --
// tarkista tuloste ja PEM-otsikoiden välissä oleva CN ensin, tallenna erikseen jos
// näyttää oikealta samaan tapaan kuin pankkiyhteysadmin.php tallentaa
// sepa_renew_certificate():n tuloksen).
//
// Käyttö:
//   php hae_crosskey_sertifikaatti.php <pankkiyhteys_tunnus> <salasana> [transfer_key]
//
// transfer_key annettuna = ensimmäinen haku (esim. juuri saatu 16-numeroinen
// kertakäyttösalasana, kahdesta osasta yhteen liitettynä). Jätä pois = uusinta
// nykyisellä, voimassa olevalla Crosskey-sertifikaatilla (ei toimi ennen kuin
// meillä ylipäätään ON sellainen).

$php_cli = (php_sapi_name() == 'cli');

if (!$php_cli) {
  die("Tätä scriptiä voi ajaa vain komentoriviltä!\n");
}

if (trim($argv[1] ?? '') == '') {
  die("Anna pankkiyhteys_tunnus ensimmäisenä parametrina!\n");
}

if (trim($argv[2] ?? '') == '') {
  die("Anna salasana toisena parametrina!\n");
}

$tunnus = (int) $argv[1];
$salasana = $argv[2];
$transfer_key = trim($argv[3] ?? '');

ini_set("include_path", ini_get("include_path") . PATH_SEPARATOR . dirname(__FILE__));
error_reporting(E_ALL);
ini_set("display_errors", 1);

require "inc/connect.inc";
require "inc/functions.inc";
require "inc/pankkiyhteys_functions.inc";

// generoi_private_key_ja_csr() lukee $yhtiorow["maa"] CSR:n Country-kenttää varten --
// tässä scriptissä ei ole kirjautunutta Pupesoft-sessiota joka täyttäisi sen normaalisti
// (inc/parametrit.inc), joten viime ajossa se oli null ja openssl jätti C:n kokonaan pois
// ("No value provided for subject name attribute C, skipped"). Yhtiö on suomalainen --
// Crosskeyn omissa esimerkeissä ja sertifikaateissa on aina C=FI -- niin tämä tässä:
global $yhtiorow;
$yhtiorow = array("maa" => "FI");

$params = array(
  "pankkiyhteys_tunnus" => $tunnus,
  "pankkiyhteys_salasana" => $salasana,
  "transfer_key" => $transfer_key,
);

echo "=== sepa_get_certificate_crosskey() ===\n";
echo ($transfer_key === '')
  ? "(uusinta -- allekirjoitetaan nykyisellä sertifikaatilla)\n\n"
  : "(ensimmäinen haku -- transfer_key annettu)\n\n";

$tulos = sepa_get_certificate_crosskey($params);

echo "\n=== Tulos ===\n";

if ($tulos === false) {
  echo "EPÄONNISTUI -- ks. virheilmoitukset yllä.\n";
  exit(1);
}

echo "ONNISTUI!\n\n";

$parsed = openssl_x509_parse($tulos["signing_certificate"]);

if ($parsed !== false) {
  echo "Uuden sertifikaatin Subject:\n";
  foreach ($parsed["subject"] as $key => $value) {
    echo "  {$key} = {$value}\n";
  }
  echo "Uuden sertifikaatin Issuer:\n";
  foreach ($parsed["issuer"] as $key => $value) {
    echo "  {$key} = {$value}\n";
  }
  echo "Voimassa asti: " . date("Y-m-d H:i:s", $parsed["validTo_time_t"]) . "\n";
}

echo "\nSertifikaattia (signing_certificate) EI tallennettu kantaan -- tarkista Issuer\n";
echo "yllä (pitäisi olla Crosskey/POP Pankki, ei Samlink) ja CN (pitäisi olla\n";
echo "customer_id, {$tunnus} rivin customer_id, ei yhtiön nimi). Jos näyttää oikealta,\n";
echo "sanotaan miten tallennus tehdään.\n";
