<?php

/*
 * Siirretään asiakastiedot Bynfoon
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
$filepath = "/tmp/customers_{$yhtio}_".date("Y-m-d").".csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus epäonnistui: $filepath\n");
}

// Otsikkotieto
$header  = "asiakasid;";
$header .= "asiakasnumero;";
$header .= "nimi;";
$header .= "ryhmä;";
$header .= "osasto;";
$header .= "kustannuspaikka;";
$header .= "toimipaikka";
$header .= "\n";

fwrite($fp, $header);

// Haetaan asiakkaat
$query = "SELECT asiakas.tunnus,
          asiakas.asiakasnro,
          concat_ws(' ', asiakas.nimi, asiakas.nimitark) AS nimi,
          asiakas.ryhma,
          asiakas.osasto,
          kustannuspaikka.nimi AS kustannuspaikka,
          asiakas.toimipaikka
          FROM asiakas
          LEFT JOIN kustannuspaikka ON (kustannuspaikka.yhtio = asiakas.yhtio
            AND kustannuspaikka.tunnus = asiakas.kustannuspaikka)
          WHERE asiakas.yhtio          = '{$yhtio}'
          AND asiakas.laji             NOT IN ('P','R')
          AND asiakas.myynninseuranta  = ''
          ORDER BY asiakas.tunnus";
$res = pupe_query($query);

// Kerrotaan montako riviä käsitellään
$rows = mysqli_num_rows($res);

echo "Asiakasrivejä {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysqli_fetch_assoc($res)) {
  $rivi  = "{$row['tunnus']};";
  $rivi .= "{$row['asiakasnro']};";
  $rivi .= pupesoft_csvstring($row['nimi']).";";
  $rivi .= pupesoft_csvstring($row['ryhma']).";";
  $rivi .= pupesoft_csvstring($row['osasto']).";";
  $rivi .= pupesoft_csvstring($row['kustannuspaikka']).";";
  $rivi .= "{$row['toimipaikka']}";
  $rivi .= "\n";

  fwrite($fp, $rivi);

  $k_rivi++;

  if ($k_rivi % 1000 == 0) {
    echo "Käsitellään riviä {$k_rivi}\n";
  }
}

fclose($fp);

echo "Valmis.\n";
