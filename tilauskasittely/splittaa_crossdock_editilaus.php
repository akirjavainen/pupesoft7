<?php

if (php_sapi_name() != 'cli') die("CLI only!");

if (!isset($argv[1])) {
  echo "Anna tiedostonimi!!!\n";
  die;
}

if (!isset($argv[2])) {
  echo "Anna hakemisto!!!\n";
  die;
}

$filename = $argv[1];
if (!is_file($filename)) {
  echo "Ei ole tiedosto!!!\n";
  die;
}

$kohdehakemisto = $argv[2];
if (!is_dir($kohdehakemisto) and !is_writable($kohdehakemisto)) {
  echo "Ei ole hakemisto!!!\n";
  die;
}

$kohdehakemisto = rtrim($kohdehakemisto, "/");

$order_tietosisalto = file_get_contents($filename);
$order_tietosisalto = str_replace("\n", "", $order_tietosisalto);
$order_tietosisalto = explode("'", $order_tietosisalto);

// Toimitus ja tuotetiedot
$arska = array();

$current_customer      = '';
$current_product       = '';
$current_product_price = '';
$current_product_desc  = '';
$delivery_date         = '';
$edi_rivi_temp         = array();

$edi_rivi_temp[] = "EDIFACT_CROSSDOCK_SPLIT";


$tilaustyyppi = 'varastotilaus';
// Etsitään LOC-elementtejä jotta voidaan eroitella CrossDock sekä Varastotilaus toisistaan
foreach ($order_tietosisalto as $tieto) {
  if (mb_strpos($tieto, 'LOC+8') !== false) $tilaustyyppi = 'crossdock';
}

foreach ($order_tietosisalto as $key => $value) {

  $value = trim($value);

  if ($value != "") {
    if (mb_substr($value, 0, 6) == "NAD+CN" and $tilaustyyppi == 'varastotilaus') {
      // Toimitusosoite
      // Varastotoimituksissa puuttuu LOC mutta korvataan tieto NAD+CN:lla
      $value = str_replace("NAD+CN", "NAD+DP", $value);
      $arska[$value][] = array();
      $current_customer = $value;
    }
    elseif (mb_substr($value, 0, 3) == "LOC") {
      // Toimitusosoite
      $value = str_replace("LOC+8", "NAD+DP", $value);
      $arska[$value][] = array();
      $current_customer = $value;
    }
    elseif (mb_substr($value, 0, 3) == "LIN") {
      // Toimitusosoite - Tuotteet
      // Tuotteen vaihtuessa nollataan current_customer ja price
      if ($current_product != $value and $tilaustyyppi == 'crossdock') {
        $current_customer = '';
        $current_product_price = '';
      }
      $arska[$current_customer][] = $value;
      $current_product = $value;
    }
    elseif (mb_substr($value, 0, 6) == "NAD+CZ") {
      // Joissakin keisseissä NAD+SE puuttuu mutta tilalla on NAD+CZ
      $value = str_replace("NAD+CZ", "NAD+SE", $value);
      $edi_rivi_temp[] = $value;
    }
    elseif (mb_substr($value, 0, 3) == "PRI") {
      // Tuotehinta
      if (!empty($current_product)) $current_product_price = $value;
    }
    elseif (mb_substr($value, 0, 3) == "IMD") {
      // Tuotekuvaus
      if (!empty($current_product)) $current_product_desc = $value;
    }
    elseif (mb_substr($value, 0, 3) == "DTM") {
      // Tuotekohtainen toimitusaika
      if (!empty($current_product)) $delivery_date = $value;
    }
    elseif (mb_substr($value, 0, 3) == "QTY") {
      // Toimitusosoite - Tuote -> Kpl - Hinta
      if (!empty($current_product) and !empty($current_customer)) $arska[$current_customer][][$current_product] = array($value, $current_product_price, $current_product_desc, $delivery_date);
    }
    else {
      // Kaikki tilauksen muut rivit talteen
      $edi_rivi_temp[] = $value;
    }
  }
}

unset($order_tietosisalto);

// Etsitään otsikon päättävän rivin index
$found = array_search("UNS+D", $edi_rivi_temp);
$otsikonloppu = $edi_rivi_temp[$found];
unset($edi_rivi_temp[$found]);

// Erotellaan sanoman alku ja loppu
$edi_rivi_loppu = array_splice($edi_rivi_temp, $found);
$edi_rivi_alku = array_diff($edi_rivi_temp, $edi_rivi_loppu);

$edi_rivi_alku = implode("'", $edi_rivi_alku);
$edi_rivi_loppu = implode("'", $edi_rivi_loppu);

foreach ($arska as $key => &$value) {

  // Tuunataan jokaiselle toimitusosoitteelle oma editilaus ja ajetaan sisään
  if ($key != '') {
    // Laitetaan toimitusosoite vielä header-segmenttiin($edi_rivi_alku) ennen headerin lopputagia
    $edi_rivi = $edi_rivi_alku."'$key'";
    $edi_rivi .= $otsikonloppu."'";

    foreach ($value as $k => $v) {
      foreach ($v as $kii => $kippari) {
        // EAN-koodi
        $edi_rivi .= $kii."'";
        // Tuotekuvaus
        $edi_rivi .= $kippari[2]."'";
        // Kappalemaara
        $edi_rivi .= $kippari[0]."'";
        // Toimituspaivamaara
        $edi_rivi .= $kippari[3]."'";
        // Tällä segmentillä kerrotaan editilaus_in:lle että yksittäisen tilausrivin tiedot loppuu ja voidaan lisätä tuote tilaukselle
        $edi_rivi .= "PCD+12'";
      }
    }
    // Lisätään koko sanoman lopputagit
    $edi_rivi .= $edi_rivi_loppu;
    // Splitataan nätisti 80merkkisiksi riveiksi
    $edi_rivi = chunk_split($edi_rivi, 80, "\n");

    // Luodaan tiedosto suoraan normaalien editilausten kansioon
    $filename = "$kohdehakemisto/ediorders911_crossdock_split".uniqid(time()."_").".txt";
    file_put_contents($filename, $edi_rivi);

    $edi_rivi = $filename = '';
  }
}
