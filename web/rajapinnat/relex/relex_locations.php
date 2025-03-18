<?php

/*
 * Siirretään varastotiedot Relexiin
 * 5.1 LOCATION DATA
*/

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

if (!isset($argv[1]) or $argv[1] == '') {
  die("Yhtiö on annettava!!");
}

ini_set("memory_limit", "5G");

// Otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))).PATH_SEPARATOR."/usr/share/pear");

require 'inc/connect.inc';
require 'inc/functions.inc';

// Logitetaan ajo
cron_log();

$ajopaiva  = date("Y-m-d");
$paiva_ajo = FALSE;
$ftppath = "/data/input";

if (isset($argv[2]) and $argv[2] != '') {

  if (mb_strpos($argv[2], "-") !== FALSE) {
    list($y, $m, $d) = explode("-", $argv[2]);
    if (is_numeric($y) and is_numeric($m) and is_numeric($d) and checkdate($m, $d, $y)) {
      $ajopaiva = $argv[2];
    }
  }
  $paiva_ajo = TRUE;
}

if (isset($argv[3]) and trim($argv[3]) != '') {
  $ftppath = trim($argv[3]);
}

// Yhtiö
$yhtio = sanitize_string($argv[1]);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

// Tallennetaan rivit tiedostoon
$filepath = "/tmp/location_update_{$yhtio}_$ajopaiva.csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus epäonnistui: $filepath\n");
}

// Otsikkotieto
$header = "code;name;replenished;chain_code\n";
fwrite($fp, $header);

// Haetaan varastot
$query = "SELECT
          yhtio.maa,
          varastopaikat.tunnus,
          concat_ws(' ', varastopaikat.nimitys, varastopaikat.nimi, varastopaikat.nimitark) nimi,
          varastopaikat.tyyppi
          FROM varastopaikat
          JOIN yhtio ON (varastopaikat.yhtio = yhtio.yhtio)
          WHERE varastopaikat.yhtio  = '$yhtio'
          AND varastopaikat.tyyppi  != 'P'
          ORDER BY varastopaikat.tunnus";
$res = pupe_query($query);

// Kerrotaan montako riviä käsitellään
$rows = mysqli_num_rows($res);

echo date("d.m.Y @ G:i:s") . ": Relex varastorivejä {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysqli_fetch_assoc($res)) {

  $replensihed = "yes";

  if ($row['tyyppi'] != "") {
    $replensihed = "no";
  }

  $rivi  = "{$row['maa']}-{$row['tunnus']};";
  $rivi .= pupesoft_csvstring($row['nimi']).";";
  $rivi .= "{$replensihed};";
  $rivi .= "";
  $rivi .= "\n";

  fwrite($fp, $rivi);

  $k_rivi++;
}

fclose($fp);

// Tehdään FTP-siirto
if ($paiva_ajo and !empty($relex_ftphost)) {
  $ftphost = $relex_ftphost;
  $ftpuser = $relex_ftpuser;
  $ftppass = $relex_ftppass;
  $ftpfile = $filepath;
  require "inc/ftp-send.inc";
}

echo date("d.m.Y @ G:i:s") . ": Relext varastorivit valmis.\n\n";
