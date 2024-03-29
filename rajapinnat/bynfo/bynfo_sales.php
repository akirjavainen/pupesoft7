<?php

/*
 * Siirretään myynnit Bynfoon
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
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))));

require 'inc/connect.inc';
require 'inc/functions.inc';

// Logitetaan ajo
cron_log();

// Yhtiö
$yhtio = sanitize_string($argv[1]);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

// Tallennetaan rivit tiedostoon
$filepath = "/tmp/sales_{$yhtio}_".date("Y-m-d").".csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus epäonnistui: $filepath\n");
}

// Otsikkotieto

$header  = "laskunro;";
$header .= "asiakasid;";
$header .= "laskutuspvm;";
$header .= "toimituspvm;";
$header .= "tuoteno;";
$header .= "määrä;";
$header .= "rivihinta;";
$header .= "rivikate;";
$header .= "kehahin";
$header .= "\n";

fwrite($fp, $header);

// Haetaan tapahtumat
$query = "SELECT lasku.laskunro,
          lasku.liitostunnus,
          tilausrivi.laskutettuaika,
          left(tilausrivi.toimitettuaika, 10) toimitettuaika,
          tilausrivi.tuoteno,
          tilausrivi.kpl,
          tilausrivi.rivihinta,
          tilausrivi.kate,
          round((tilausrivi.kate-tilausrivi.rivihinta)*-1/tilausrivi.kpl, 2) keha
          FROM tilausrivi
          INNER JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio
            AND tuote.tuoteno           = tilausrivi.tuoteno
            AND tuote.myynninseuranta   = '')
          INNER JOIN lasku USE INDEX (PRIMARY) ON (lasku.yhtio = tilausrivi.yhtio
            AND lasku.tunnus            = tilausrivi.otunnus)
          INNER JOIN asiakas USE INDEX (PRIMARY) ON (asiakas.yhtio = lasku.yhtio
            AND asiakas.tunnus          = lasku.liitostunnus
            AND asiakas.myynninseuranta = '')
          WHERE tilausrivi.yhtio        = '{$yhtio}'
          AND tilausrivi.tyyppi         = 'L'
          AND tilausrivi.laskutettuaika >= '2015-06-01'
          ORDER BY lasku.laskunro,
          tilausrivi.tuoteno";
$res = pupe_query($query);

// Kerrotaan montako riviä käsitellään
$rows = mysqli_num_rows($res);

echo "Tapahtumarivejä {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysqli_fetch_assoc($res)) {

  $rivi  = "{$row['laskunro']};";
  $rivi .= "{$row['liitostunnus']};";
  $rivi .= "{$row['laskutettuaika']};";
  $rivi .= "{$row['toimitettuaika']};";
  $rivi .= pupesoft_csvstring($row['tuoteno']).";";
  $rivi .= "{$row['kpl']};";
  $rivi .= "{$row['rivihinta']};";
  $rivi .= "{$row['kate']};";
  $rivi .= "{$row['keha']}";
  $rivi .= "\n";

  fwrite($fp, $rivi);

  $k_rivi++;

  if ($k_rivi % 1000 == 0) {
    echo "Käsitellään riviä {$k_rivi}\n";
  }
}

fclose($fp);

echo "Valmis.\n";
