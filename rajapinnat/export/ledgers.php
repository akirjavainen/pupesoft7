<?php

/*
 * Siirretään osto- ja myyntireskontrat
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

$scp_siirto = "";

if (!empty($argv[2])) {
  $scp_siirto = $argv[2];
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
$filepath1 = "/tmp/accounts_receivable_{$yhtio}_".date("Y-m-d").".csv";

if (!$fp1 = fopen($filepath1, 'w+')) {
  die("Tiedoston avaus epäonnistui: $filepath1\n");
}

$filepath2 = "/tmp/accounts_payable_{$yhtio}_".date("Y-m-d").".csv";

if (!$fp2 = fopen($filepath2, 'w+')) {
  die("Tiedoston avaus epäonnistui: $filepath2\n");
}

// Otsikkotieto
$header  = "Invoice number;";
$header .= "Invoice date;";
$header .= "Customer;";
$header .= "Name;";
$header .= "Open amount $yhtiorow[valkoodi];";
$header .= "Open amount in invoice currency;";
$header .= "Currency;";
$header .= "Due date;";
$header .= "Cash discount due date;";
$header .= "Cash discount amount $yhtiorow[valkoodi];";
$header .= "Cash discount amount in invoice currency;";
$header .= "Payer;";
$header .= "Reference number;";
$header .= "Reference text;";
$header .= "Payment reminders;";
$header .= "Last Payment reminder";
$header .= "\n";

fwrite($fp1, $header);

$header = str_replace("Customer;", "Supplier;", $header);
$header = str_replace(";Payment reminders;Last Payment reminder", "", $header);

fwrite($fp2, $header);

// Haetaan avoimet myyntilaskut
$query = "(SELECT
           lasku.tunnus,
           'SALESINVOICE' tyyppi,
           lasku.laskunro,
           lasku.viite,
           lasku.viesti,
           lasku.tapvm,
           if(lasku.kapvm=0, '', lasku.kapvm) kapvm,
           if(asiakas.asiakasnro in ('0',''), asiakas.ytunnus, asiakas.asiakasnro) asiakasnro,
           concat_ws(' ', lasku.nimi, lasku.nimitark) nimi,
           lasku.erpcm,
           lasku.valkoodi,
           if(lasku.kasumma=0, '', lasku.kasumma) kasumma_valuutassa,
           if(lasku.kasumma=0, '', round(lasku.kasumma * lasku.vienti_kurssi, 2)) kasumma,
           sum(tiliointi.summa) avoinsaldo,
           sum(lasku.summa_valuutassa-lasku.saldo_maksettu_valuutassa) laskuavoinsaldo_valuutassa
           FROM lasku use index (yhtio_tila_mapvm)
           JOIN asiakas on (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus)
           JOIN tiliointi use index (tositerivit_index) ON (lasku.yhtio = tiliointi.yhtio
             and lasku.tunnus       = tiliointi.ltunnus
             and tiliointi.tilino   in ('$yhtiorow[myyntisaamiset]', '$yhtiorow[factoringsaamiset]', '$yhtiorow[konsernimyyntisaamiset]')
             and tiliointi.korjattu = ''
             and tiliointi.tapvm    <= current_date)
           WHERE lasku.yhtio        = '{$yhtio}'
           and lasku.mapvm          = '0000-00-00'
           and lasku.tapvm          <= current_date
           and lasku.tapvm          > '0000-00-00'
           and lasku.tila           = 'U'
           and lasku.alatila        = 'X'
           GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12)

           UNION

           (SELECT
            lasku.tunnus,
           'SUPPLIERINVOICE' tyyppi,
           if(lasku.laskunro > 0, lasku.laskunro, if(lasku.viite!='', lasku.viite, lasku.viesti)) laskunro,
           lasku.viite,
           lasku.viesti,
           lasku.tapvm,
           if(lasku.kapvm=0, '', lasku.kapvm) kapvm,
           if(toimi.toimittajanro in ('0',''), toimi.ytunnus, toimi.toimittajanro) asiakasnro,
           concat_ws(' ', lasku.nimi, lasku.nimitark) nimi,
           lasku.erpcm,
           lasku.valkoodi,
           if(lasku.kasumma=0, '', lasku.kasumma) kasumma_valuutassa,
           if(lasku.kasumma=0, '', round(lasku.kasumma * lasku.vienti_kurssi, 2)) kasumma,
           tiliointi.summa * -1 avoinsaldo,
           lasku.summa laskuavoinsaldo_valuutassa
           FROM lasku use index (yhtio_tila_tapvm)
           JOIN toimi on (toimi.yhtio = lasku.yhtio and toimi.tunnus = lasku.liitostunnus)
           JOIN tiliointi use index (tositerivit_index) ON (lasku.yhtio=tiliointi.yhtio
             and lasku.tunnus       = tiliointi.ltunnus
             and lasku.tapvm        = tiliointi.tapvm
             and tiliointi.tilino   IN ('$yhtiorow[ostovelat]','$yhtiorow[konserniostovelat]')
             and tiliointi.korjattu = '' )
           WHERE lasku.yhtio        = '{$yhtio}'
           and mapvm                = '0000-00-00'
           and lasku.tapvm          <= current_date
           and lasku.tapvm          > '0000-00-00'
           and tila                 in ('H','Y','M','P','Q'))

           ORDER BY tyyppi, erpcm, laskunro";
$res = pupe_query($query);

// Kerrotaan montako riviä käsitellään
$rows = mysqli_num_rows($res);

echo "Reskontrarivejä {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysqli_fetch_assoc($res)) {
  $rivi  = pupesoft_csvstring($row['laskunro']).";";
  $rivi .= "{$row['tapvm']};";
  $rivi .= pupesoft_csvstring($row['asiakasnro']).";";
  $rivi .= pupesoft_csvstring($row['nimi']).";";
  $rivi .= "{$row['avoinsaldo']};";
  $rivi .= "{$row['laskuavoinsaldo_valuutassa']};";
  $rivi .= "{$row['valkoodi']};";
  $rivi .= "{$row['erpcm']};";
  $rivi .= "{$row['kapvm']};";
  $rivi .= "{$row['kasumma']};";
  $rivi .= "{$row['kasumma_valuutassa']};";
  $rivi .= ";";
  $rivi .= pupesoft_csvstring($row['viite']).";";

  if (empty($row['viite'])) {
    $rivi .= pupesoft_csvstring($row['viesti']);
  }

  if ($row['tyyppi'] == "SALESINVOICE") {
    $rivi .= ";";

    $query = "SELECT
              max(karhukierros.pvm) as kpvm,
              count(distinct karhu_lasku.ktunnus) ktun
              FROM karhu_lasku
              JOIN karhukierros ON (karhukierros.tunnus = karhu_lasku.ktunnus)
              WHERE karhu_lasku.ltunnus = {$row['tunnus']}";
    $karhukertares = pupe_query($query);
    $karhukertarow = mysqli_fetch_assoc($karhukertares);

    $rivi .= "{$karhukertarow['ktun']};";
    $rivi .= "{$karhukertarow['kpvm']}";
  }

  $rivi .= "\n";

  if ($row['tyyppi'] == "SALESINVOICE") {
    fwrite($fp1, $rivi);
  }
  else {
    fwrite($fp2, $rivi);
  }

  $k_rivi++;

  if ($k_rivi % 1000 == 0) {
    echo "Käsitellään riviä {$k_rivi}\n";
  }
}

fclose($fp1);
fclose($fp2);

if (!empty($scp_siirto)) {
  // Siirretään toiselle palvelimelle
  system("scp {$filepath1} {$filepath2} $scp_siirto");
}

echo "Valmis.\n";
