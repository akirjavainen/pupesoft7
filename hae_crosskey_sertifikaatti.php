<?php

// Hakee UUDEN, Crosskeyn itse myöntämän allekirjoitussertifikaatin
// sepa_get_certificate_crosskey():lla ja TULOSTAA sen (EI tallenna kantaan --
// tarkista tuloste ja PEM-otsikoiden välissä oleva CN ensin, tallenna erikseen jos
// näyttää oikealta samaan tapaan kuin pankkiyhteysadmin.php tallentaa
// sepa_renew_certificate():n tuloksen).
//
// Käyttö:
//   php hae_crosskey_sertifikaatti.php <pankkiyhteys_tunnus> <salasana> [transfer_key] [tallenna]
//
// 4. parametri 'tallenna' kirjoittaa haetun sertifikaatin ja avaimen kantaan (salattuna)
// samassa ajossa. Uusinnassa (ei transfer_key) anna tyhjä: "" tallenna.
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
$tallenna = (trim($argv[4] ?? '') === 'tallenna');

ini_set("include_path", ini_get("include_path") . PATH_SEPARATOR . dirname(__FILE__));
error_reporting(E_ALL);
ini_set("display_errors", 1);

// xmlseclibs / wse-php (allekirjoitukset), kuten p.php:ssä
require "/var/www/robrichards/vendor/autoload.php";

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

if (!$tallenna) {
  echo "\nEI tallennettu kantaan (anna 4. parametriksi 'tallenna' tallentaaksesi).\n";
  echo "HUOM: transfer_key on kertakäyttöinen -- jos haluat tallentaa, se pitää tehdä\n";
  echo "SAMASSA ajossa kuin haku. Tämän ajon avain menetetään kun scripti päättyy.\n";
  exit(0);
}

// Tallennus samaan tapaan kuin pankkiyhteysadmin.php (salaa() + UPDATE pankkiyhteys).
// Kirjoitetaan ensin salaamaton varmuuskopio tiedostoihin (umask 0077), koska
// kertakäyttöistä avainta ei saa uudestaan jos tallennus kantaan epäonnistuu.
$backup_base = sys_get_temp_dir() . "/crosskey_tallenna_{$tunnus}_" . date("Ymd_His");
$vanha_umask = umask(0077);
file_put_contents("{$backup_base}.cert.pem", $tulos["signing_certificate"]);
file_put_contents("{$backup_base}.key.pem", $tulos["signing_private_key"]);
umask($vanha_umask);
echo "\nVarmuuskopio (SALAAMATON, poista kun kanta on varmistettu):\n";
echo "  {$backup_base}.cert.pem\n  {$backup_base}.key.pem\n";

$vanha = hae_pankkiyhteys($tunnus);
$vanha_backup = "{$backup_base}.vanha_rivi.json";
file_put_contents($vanha_backup, json_encode($vanha));
chmod($vanha_backup, 0600);
echo "  {$vanha_backup} (vanhat salatut arvot)\n";

$cert_salattu = salaa($tulos["signing_certificate"], $salasana);
$key_salattu = salaa($tulos["signing_private_key"], $salasana);
$valid_to = parse_sertificate($tulos["signing_certificate"]);
$valid_to = $valid_to["valid_to"];

print_r($tulos);

$query = "UPDATE pankkiyhteys
          SET signing_certificate = '{$cert_salattu}',
              signing_private_key = '{$key_salattu}',
              signing_certificate_valid_to = '{$valid_to}'
          WHERE tunnus = {$tunnus}";
pupe_query($query);

// Varmistus: puretaanko kannasta takaisin samalla salasanalla ja täsmääkö avain+sertifikaatti
$tarkistus = hae_pankkiyhteys($tunnus);
$_c = pura_salaus($tarkistus["signing_certificate"], $salasana);
$_k = pura_salaus($tarkistus["signing_private_key"], $salasana);

if (openssl_x509_check_private_key($_c, $_k)) {
  echo "\nTALLENNETTU ja varmistettu: pankkiyhteys.tunnus={$tunnus}, voimassa asti {$valid_to}.\n";
}
else {
  echo "\nVAROITUS: kantaan kirjoitettu, mutta sertifikaatti/avain ei täsmää luettaessa takaisin!\n";
  exit(1);
}
