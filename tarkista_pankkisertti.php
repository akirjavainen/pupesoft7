<?php

// Tulostaa sertifikaatin Subject/Issuer/voimassaolo + tietokannan customer_id:n,
// EI yksityistä avainta eikä sertifikaatin raakaa PEM-sisältöä.
//
// Käyttö: php tarkista_pankkisertti.php <pankkiyhteys_tunnus> <salasana>

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

ini_set("include_path", ini_get("include_path") . PATH_SEPARATOR . dirname(__FILE__));
error_reporting(E_ALL);
ini_set("display_errors", 1);

require "inc/connect.inc";
require "inc/functions.inc";
require "inc/pankkiyhteys_functions.inc";

$pankkiyhteys = hae_pankkiyhteys($tunnus);

if (empty($pankkiyhteys)) {
  die("Pankkiyhteyttä tunnuksella {$tunnus} ei löytynyt!\n");
}

$_pk = pura_salaus($pankkiyhteys["signing_private_key"], $salasana);

if (!openssl_pkey_get_private($_pk)) {
  die("Salasana on väärä, tai signing_private_key ei purkaudu!\n");
}

$signing_cert_pem = pura_salaus($pankkiyhteys["signing_certificate"], $salasana);
$encryption_cert_pem = pura_salaus($pankkiyhteys["encryption_certificate"], $salasana);

echo "=== Pankkiyhteys (tunnus {$tunnus}) ===\n";
echo "pankki (koodi kannassa): " . $pankkiyhteys["pankki"] . "\n";
echo "customer_id (kannassa, ApplicationRequestiin menevä arvo): " . $pankkiyhteys["customer_id"] . "\n";
echo "\n";

function tulosta_sertti_tiedot($nimi, $pem) {
  echo "=== {$nimi} ===\n";

  if (empty($pem)) {
    echo "(tyhjä - ei asetettu)\n\n";
    return;
  }

  $parsed = openssl_x509_parse($pem);

  if ($parsed === false) {
    echo "VIRHE: sertifikaatti ei jäsentynyt!\n\n";
    return;
  }

  echo "Subject:\n";
  foreach ($parsed["subject"] as $key => $value) {
    echo "  {$key} = {$value}\n";
  }

  echo "Issuer:\n";
  foreach ($parsed["issuer"] as $key => $value) {
    echo "  {$key} = {$value}\n";
  }

  echo "Voimassa alkaen: " . date("Y-m-d H:i:s", $parsed["validFrom_time_t"]) . "\n";
  echo "Voimassa asti:   " . date("Y-m-d H:i:s", $parsed["validTo_time_t"]) . "\n";
  echo "Vanhentunut jo:  " . ($parsed["validTo_time_t"] < time() ? "KYLLÄ" : "ei") . "\n";
  echo "\n";
}

tulosta_sertti_tiedot("Signing certificate (allekirjoitus)", $signing_cert_pem);
tulosta_sertti_tiedot("Encryption certificate (mTLS)", $encryption_cert_pem);

echo "=== Vertailu Crosskeyn PDF-spesifikaation kanssa ===\n";
echo "PDF (PFT Technical Description 2.5, kohta 3.1) vaatii: CN=[CustomerId, korkeintaan 10 numeroa, ei alkunollia]\n";

$parsed_signing = empty($signing_cert_pem) ? false : openssl_x509_parse($signing_cert_pem);

if ($parsed_signing !== false) {
  $cn = $parsed_signing["subject"]["CN"] ?? "(ei CN:ää)";
  $customer_id = $pankkiyhteys["customer_id"];

  echo "Sertifikaatin CN: {$cn}\n";
  echo "Kannan customer_id: {$customer_id}\n";

  if ($cn === $customer_id) {
    echo "-> TÄSMÄÄ. CN sisältää CustomerId:n kuten PDF vaatii.\n";
  }
  else {
    echo "-> EI TÄSMÄÄ! CN ei ole sama kuin customer_id -- tämä on todennäköinen syy '07 Sopimus ei voimassa' -vastaukseen,\n";
    echo "   koska Crosskeyn spesifikaatio vaatii CustomerId:n nimenomaan CN-kentässä.\n";
  }
}
