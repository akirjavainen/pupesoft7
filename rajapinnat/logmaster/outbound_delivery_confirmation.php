<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!\n");
}

date_default_timezone_set('Europe/Helsinki');

if (trim($argv[1]) == '') {
  die ("Et antanut lähettävää yhtiötä!\n");
}

if (trim($argv[2]) == '') {
  die ("Et antanut luettavien tiedostojen polkua!\n");
}

if (trim($argv[3]) == '') {
  die ("Et antanut sähköpostiosoitetta!\n");
}

// lisätään includepathiin pupe-root
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))));
ini_set("display_errors", 1);

error_reporting(E_ALL);

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";
require "rajapinnat/logmaster/logmaster-functions.php";

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
pupesoft_flock();

$yhtio = sanitize_string(trim($argv[1]));
$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow = hae_kukarow('admin', $yhtio);

if (empty($kukarow)) {
  die ("Käyttäjää ei admin löytynyt!\n");
}

$path = trim($argv[2]);
$path = rtrim($path, '/').'/';

$error_email = trim($argv[3]);
$email_array = array();

$handle = opendir($path);

if ($handle === false) {
  die ("Hakemistoa {$path} ei löydy!\n");
}

while (false !== ($file = readdir($handle))) {
  $full_filepath = $path.$file;
  $message_type = logmaster_message_type($full_filepath);

  if ($message_type != 'OutboundDeliveryConfirmation') {
    continue;
  }

  $xml = simplexml_load_file($full_filepath);

  pupesoft_log('logmaster_outbound_delivery_confirmation', "Käsitellään sanoma {$file}");

  $otunnus = (int) $xml->CustPackingSlip->PickingListId;

  if ($otunnus == 0) {
    pupesoft_log('logmaster_outbound_delivery_confirmation', "Tilausnumeroa ei löytynyt sanomasta {$file}");

    $email_array[] = t("Tilausnumeroa ei löytynyt sanomasta %s", "", $file);

    rename($full_filepath, $path.'error/'.$file);

    continue;
  }

  if (isset($xml->CustPackingSlip->DeliveryDate)) {
    //<DeliveryDate>20-04-2016</DeliveryDate>
    $delivery_date = $xml->CustPackingSlip->DeliveryDate;
    $toimaika = date("Y-m-d 00:00:00", strtotime($delivery_date));
  }
  elseif (isset($xml->CustPackingSlip->Deliverydate)) {
    //HHV-case
    //<Deliverydate>2016-04-20T12:34:56</Deliverydate>
    $delivery_date = $xml->CustPackingSlip->Deliverydate;
    $toimaika = date("Y-m-d H:i:s", strtotime($delivery_date));
  }
  else {
    $toimaika = '0000-00-00 00:00:00';
  }

  $toimitustavan_tunnus = (int) $xml->CustPackingSlip->TransportAccount;

  $query = "SELECT *
            FROM lasku
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = '{$otunnus}'
            AND tila    IN ('L', 'V', 'G', 'S')
            AND alatila IN ('A', 'E', 'J')";
  $laskures = pupe_query($query);

  $tuotteiden_paino = 0;
  $kerayspoikkeama = $tilausrivit = $tilausrivit_error = array();

  if (mysqli_num_rows($laskures) > 0) {
    $laskurow = mysqli_fetch_assoc($laskures);

    # katsotaan missä rivit ovat
    if (isset($xml->Lines)) {
      $lines = $xml->Lines;
    }
    elseif (isset($xml->CustPackingSlip->Lines)) {
      $lines = $xml->CustPackingSlip->Lines;
    }
    else {
      pupesoft_log('logmaster_outbound_delivery_confirmation', "Rivit-elementtiä ei löytynyt sanomasta {$file}. Skipataan sanoma.");

      $email_array[] = t("Rivit-elementtiä ei löytynyt sanomasta %s", "", $file);

      rename($full_filepath, $path.'error/'.$file);

      continue;
    }

    # katsotaan missä yksittäinen rivi sijaitsee
    if (isset($lines->Line->TransId)) {
      $lines = $lines->Line;
    }
    elseif (isset($lines->TransId)) {
      # rivit onkin suoraan lines elementtejä
    }
    else {
      pupesoft_log('logmaster_outbound_delivery_confirmation', "Rivin TransId-elementtiä ei löytynyt sanomasta {$file}. Skipataan sanoma.");

      $email_array[] = t("Rivin TransId-elementtiä ei löytynyt sanomasta %s", "", $file);

      rename($full_filepath, $path.'error/'.$file);

      continue;
    }

    foreach ($lines as $line) {

      $tilausrivin_tunnus = (int) $line->TransId;

      if (!isset($tilausrivit[$tilausrivin_tunnus])) {
        $tilausrivit[$tilausrivin_tunnus] = array(
          'item_number' => sanitize_string($line->ItemNumber),
          'keratty'     => (float) $line->DeliveredQuantity,
        );
      }
      else {
        $tilausrivit[$tilausrivin_tunnus]['keratty'] += (float) $line->DeliveredQuantity;
      }
    }

    pupesoft_log('logmaster_outbound_delivery_confirmation', "Sanomassa {$file} ".count($tilausrivit)." uniikkia tilausriviä.");

    $paivitettiin_tilausrivi_onnistuneesti = false;

    foreach ($tilausrivit as $tilausrivin_tunnus => $data) {

      $item_number = $data['item_number'];
      $keratty     = $data['keratty'];

      $logmaster_itemnumberfield = logmaster_field('ItemNumber');
      $tuotelisa = "AND tuote.{$logmaster_itemnumberfield} = '{$item_number}'";

      $query = "SELECT tilausrivi.*
                FROM tilausrivi
                JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio {$tuotelisa} AND tuote.tuoteno = tilausrivi.tuoteno)
                WHERE tilausrivi.yhtio     = '{$kukarow['yhtio']}'
                AND tilausrivi.tunnus      = '{$tilausrivin_tunnus}'
                AND tilausrivi.tunnus     != 0
                AND tilausrivi.otunnus     = '{$laskurow['tunnus']}'
                AND tilausrivi.keratty     = ''
                AND tilausrivi.toimitettu  = ''";
      $tilausrivi_res = pupe_query($query);

      if (mysqli_num_rows($tilausrivi_res) != 1) {
        pupesoft_log('logmaster_outbound_delivery_confirmation', "Tilausriviä {$tilausrivin_tunnus} ei löytynyt. Sanoma {$file}");

        $tilausrivit_error[$tilausrivin_tunnus] = $data;
        continue;
      }

      $tilausrivi_row = mysqli_fetch_assoc($tilausrivi_res);

      $varattuupdate = "";

      // Verkkokaupassa etukäteen maksettu tuote!
      if ($laskurow["mapvm"] != '' and $laskurow["mapvm"] != '0000-00-00') {
        $a = (int) ($tilausrivi_row['tilkpl'] * 10000);
        $b = (int) ($keratty * 10000);

        if ($a != $b) {
          $kerayspoikkeama[$tilausrivi_row['tuoteno']]['tilauksella'] = round($tilausrivi_row['tilkpl']);
          $kerayspoikkeama[$tilausrivi_row['tuoteno']]['keratty'] = $keratty;
        }
      }
      else {
        // Jos ei oo etukäteen maksettu, niin tehdääb keräyspoikkeama
        $varattuupdate = ", tilausrivi.varattu = '{$keratty}' ";
      }

      if ($laskurow["tila"] == "V" or $laskurow["tila"] == "S" or $laskurow["tila"] == "G") {
        $toimitettu_lisa = "";
      }
      else {
        $toimitettu_lisa = ", tilausrivi.toimitettu = '{$kukarow['kuka']}',
                              tilausrivi.toimitettuaika = '{$toimaika}'";
      }

      $query = "UPDATE tilausrivi
                JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio {$tuotelisa} AND tuote.tuoteno = tilausrivi.tuoteno)
                SET tilausrivi.keratty = '{$kukarow['kuka']}',
                tilausrivi.kerattyaika = '{$toimaika}'
                {$toimitettu_lisa}
                {$varattuupdate}
                WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                AND tilausrivi.tunnus  = '{$tilausrivin_tunnus}'";
      pupe_query($query);

      $paivitettiin_tilausrivi_onnistuneesti = true;

      $query = "SELECT SUM(tuote.tuotemassa) paino
                FROM tilausrivi
                JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
                WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                AND tilausrivi.tunnus  = '{$tilausrivin_tunnus}'";
      $painores = pupe_query($query);
      $painorow = mysqli_fetch_assoc($painores);

      $tuotteiden_paino += $painorow['paino'];
    }

    // Päivitetään saldottomat tuotteet myös toimitetuksi
    $query = "UPDATE tilausrivi
              JOIN tuote ON (
                tuote.yhtio              = tilausrivi.yhtio AND
                tuote.tuoteno            = tilausrivi.tuoteno AND
                tuote.ei_saldoa         != ''
              )
              SET tilausrivi.keratty = '{$kukarow['kuka']}',
              tilausrivi.kerattyaika     = '{$toimaika}',
              tilausrivi.toimitettu      = '{$kukarow['kuka']}',
              tilausrivi.toimitettuaika  = '{$toimaika}'
              WHERE tilausrivi.yhtio     = '{$kukarow['yhtio']}'
              AND tilausrivi.otunnus     = '{$otunnus}'";
    pupe_query($query);

    $query  = "INSERT INTO rahtikirjat SET
               toimitustapa   = '{$laskurow['toimitustapa']}',
               kollit         = 1,
               kilot          = {$tuotteiden_paino},
               pakkaus        = '',
               pakkauskuvaus  = '',
               rahtikirjanro  = '',
               otsikkonro     = '{$otunnus}',
               tulostuspaikka = '{$laskurow['varasto']}',
               yhtio          = '{$kukarow['yhtio']}',
               viesti         = ''";
    $result_rk = pupe_query($query);

    if ($paivitettiin_tilausrivi_onnistuneesti) {

      if ($laskurow["tila"] == "G") {
        if ($laskurow["tilaustyyppi"] != 'M') {
          $tilalisa = "tila = 'G', alatila = 'C'";
        }
        else {
          $tilalisa = "tila = 'G', alatila = 'D'";
        }

      }
      elseif ($laskurow["tila"] == "V") {
        $tilalisa = "tila = 'V', alatila = 'C'";
      }
      elseif ($laskurow["tila"] == "S") {
        $tilalisa = "tila = 'S', alatila = 'C'";
      }
      else {
        $tilalisa = "tila = 'L', alatila = 'D'";
      }

      $query = "UPDATE lasku SET
                {$tilalisa}
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$laskurow['tunnus']}'";
      $upd_res = pupe_query($query);

      paivita_rahtikirjat_tulostetuksi_ja_toimitetuksi(array('otunnukset' => $laskurow['tunnus'], 'kilotyht' => $tuotteiden_paino));

      pupesoft_log('logmaster_outbound_delivery_confirmation', "Keräyskuittaus tilauksesta {$otunnus} päivitettiin toimitetuksi");
    }

    if (count($tilausrivit_error) > 0) {
      pupesoft_log('logmaster_outbound_delivery_confirmation', "Sanomassa {$file} oli ".count($tilausrivit_error)." virheellistä tilausriviä.");
    }

    pupesoft_log('logmaster_outbound_delivery_confirmation', "Keräyskuittaus tilauksesta {$otunnus} vastaanotettu");

    $avainsanaresult = t_avainsana("ULKJARJLAHETE");
    $avainsanarow = mysqli_fetch_assoc($avainsanaresult);

    if ($avainsanarow['selite'] != '') {

      // Tulostetaan lähete
      $params = array(
        'extranet_tilausvahvistus' => "",
        'kieli'                    => "",
        'komento'                  => "asiakasemail{$avainsanarow['selite']}",
        'lahetekpl'                => "",
        'laskurow'                 => $laskurow,
        'naytetaanko_rivihinta'    => "",
        'sellahetetyyppi'          => "",
        'tee'                      => "",
        'toim'                     => "",
      );

      pupesoft_tulosta_lahete($params);

      pupesoft_log('logmaster_outbound_delivery_confirmation', "Lähetettiin lähete tilauksesta {$laskurow['tunnus']} osoitteeseen {$avainsanarow['selite']}");
    }
  }
  else {
    // Laitetaan sähköpostia tuplakeräyksestä - ollaan yritetty merkitä kerätyksi jo käsin kerättyä tilausta
    $email_array[] = t("Pupessa jo kerätyksi merkitty tilaus %d yritettiin merkitä kerätyksi keräyssanomalla", "", $otunnus);

    pupesoft_log('logmaster_outbound_delivery_confirmation', "Vastaanotettiin duplikaatti keräyssanoma tilaukselle {$otunnus}");
  }

  if (count($kerayspoikkeama) != 0 and !empty($error_email)) {

    $email_array[] = t("Tilauksen %d keräyksessä on havaittu poikkeamia", "", $otunnus).":";
    $email_array[] = t("Tuoteno")." ".t("Kerätty")." ".t("Tilauksella");

    foreach ($kerayspoikkeama as $tuoteno => $_arr) {
      $email_array[] = "{$tuoteno} {$_arr['keratty']} {$_arr['tilauksella']}";
    }

    pupesoft_log('logmaster_outbound_delivery_confirmation', "Keräyspoikkeamia tilauksessa {$otunnus}");
  }

  if (count($tilausrivit_error) > 0 and !empty($error_email)) {

    $email_array[] = t("Tilauksessa %d on havaittu virheellisiä rivejä", "", $otunnus).":";
    $email_array[] = t("Rivitunnus")." ".t("Tuoteno")." ".t("Kerätty");

    foreach ($tilausrivit_error as $rivitunnus => $_arr) {
      $email_array[] = "{$rivitunnus} {$_arr['item_number']} {$_arr['keratty']}";
    }

    pupesoft_log('logmaster_outbound_delivery_confirmation', "Keräyksen kuittauksen sanomassa {$file} virheellisiä rivejä tilauksessa {$otunnus}");
  }

  $params = array(
    'email' => $error_email,
    'email_array' => $email_array,
    'log_name' => 'logmaster_outbound_delivery_confirmation',
  );

  logmaster_send_email($params);

  // siirretään tiedosto done-kansioon
  rename($full_filepath, $path.'done/'.$file);
}

closedir($handle);
