<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

if (isset($_REQUEST["tee"]) and $_REQUEST["tee"] == "NAYTATILAUS") {
  $no_head = "yes";

  if (isset($_REQUEST["xml"])) {
    header("Content-type: text/xml");
  }

  if (isset($_REQUEST["pdf"])) {
    // Tässä on "//NO_MB_OVERLOAD"-kommentti
    // jotta UTF8-konversio ei osu tähän riviin
    $pdf_size = strlen(urldecode($_REQUEST["pdf"])); //NO_MB_OVERLOAD

    header("Content-type: application/pdf");
    header("Content-length: {$pdf_size}");
    header("Content-Disposition: inline; filename=Pupesoft_lasku");
    header("Content-Description: Pupesoft_lasku");
  }

  flush();
}

require "inc/parametrit.inc";

if (isset($livesearch_tee) and $livesearch_tee == "TOIMITTAJAHAKU") {
  livesearch_toimittajahaku();
  exit;
}

if ($_REQUEST["tee"] == "NAYTATILAUS" and isset($_REQUEST["xml"])) {
  $xml = urldecode($_REQUEST["xml"]);

  $xml = str_replace("\"Finvoice.dtd\"", "\"{$palvelin2}datain/Finvoice.dtd\"", $xml);
  $xml = str_replace("\"Finvoice.xsl\"", "\"{$palvelin2}datain/Finvoice.xsl\"", $xml);

  $xml = str_replace("\"datain/Finvoice.dtd\"", "\"{$palvelin2}datain/Finvoice.dtd\"", $xml);
  $xml = str_replace("\"datain/Finvoice.xsl\"", "\"{$palvelin2}datain/Finvoice.xsl\"", $xml);

  echo $xml;
  exit;
}

if ($_REQUEST["tee"] == "NAYTATILAUS" and isset($_REQUEST["pdf"])) {
  $pdf = urldecode($_REQUEST["pdf"]);
  echo $pdf;
  exit;
}

enable_ajax();

// Otetaan defaultit, jos ei olla yliajettu salasanat.php:ssä
$verkkolaskut_in     = empty($verkkolaskut_in)     ? "/home/verkkolaskut"        : rtrim($verkkolaskut_in, "/");
$verkkolaskut_ok     = empty($verkkolaskut_ok)     ? "/home/verkkolaskut/ok"     : rtrim($verkkolaskut_ok, "/");
$verkkolaskut_orig   = empty($verkkolaskut_orig)   ? "/home/verkkolaskut/orig"   : rtrim($verkkolaskut_orig, "/");
$verkkolaskut_error  = empty($verkkolaskut_error)  ? "/home/verkkolaskut/error"  : rtrim($verkkolaskut_error, "/");
$verkkolaskut_reject = empty($verkkolaskut_reject) ? "/home/verkkolaskut/reject" : rtrim($verkkolaskut_reject, "/");

$verkkolaskuvirheet_kasittele  = $verkkolaskut_in;
$verkkolaskuvirheet_vaarat    = $verkkolaskut_error;
$verkkolaskuvirheet_poistetut  = $verkkolaskut_reject;

echo "<font class='head'>".t("Virheelliset verkkolaskut")."</font><hr>";

// VIRHE: verkkolasku-kansiot on väärin määritelty!
if (!is_dir($verkkolaskuvirheet_poistetut) or !is_dir($verkkolaskuvirheet_vaarat) or !is_dir($verkkolaskuvirheet_kasittele)) {
  echo t("Kansioissa ongelmia").": $verkkolaskuvirheet_poistetut, $verkkolaskuvirheet_vaarat, $verkkolaskuvirheet_kasittele<br>";
  exit;
}

// ekotetaan javascriptiä jotta saadaan pdf:ät uuteen ikkunaan
js_openFormInNewWindow();

if (isset($tiedosto)) {
  if ($tapa == 'U') {
    rename($verkkolaskuvirheet_vaarat."/".$tiedosto, $verkkolaskuvirheet_kasittele."/".$tiedosto);
    echo "<font class='message'>".t("Tiedosto käsitellään uudestaan")."</font><br>";
  }

  if ($tapa == 'U_JA_P') {
    // Päivitetään toimittajan tunnus aineistoon, niin saadaan lasku reskontraan
    if ($kumpivoice == "FINVOICE") {
      $finkkari = simplexml_load_file($verkkolaskuvirheet_vaarat."/".$tiedosto);

      if (!isset($toimittaja_haku)) {
        $finkkari->SellerPartyDetails->addChild('SellerPupesoftId', $tunnus);
        $muutos_ok = true;
      }
      else {
        $toimittaja = hae_toimittaja($toimittaja_haku);

        if (!empty($toimittaja['tunnus'])) {
          $finkkari->SellerPartyDetails->addChild('SellerPupesoftId', $toimittaja['tunnus']);
          $muutos_ok = true;
        }
        //unsetataan, ettei päivity domin formeihin
        unset($toimittaja_haku);
      }
    }
    elseif ($kumpivoice == 'TECCOM') {
      $toimittaja = hae_toimittaja($toimittaja_haku);

      if (isset($finkkari->InvoiceHeader->SellerParty->PartyNumber) and !empty($toimittaja['toimittajanro'])) {
        $finkkari->InvoiceHeader->SellerParty->PartyNumber = $toimittaja['toimittajanro'];
        $muutos_ok = true;
      }
      //unsetataan, ettei päivity domin formeihin
      unset($toimittaja_haku);
    }

    if ($muutos_ok) {
      file_put_contents($verkkolaskuvirheet_vaarat."/".$tiedosto, $finkkari->asXML());

      rename($verkkolaskuvirheet_vaarat."/".$tiedosto, $verkkolaskuvirheet_kasittele."/".$tiedosto);
      echo "<font class='message'>".t("Tiedosto käsitellään uudestaan")."</font><br>";
    }
    else {
      echo "<font class='message'>".t("Tiedoston korjaamisessa tapahtui virhe")."</font><br>";
    }
  }

  if ($tapa == 'P') {
    rename($verkkolaskuvirheet_vaarat."/".$tiedosto, $verkkolaskuvirheet_poistetut."/".$tiedosto);
    echo "<font class='message'>".t("Tiedosto hylättiin")."</font><br>";
  }
}

$laskuri = 0;
$valitutlaskut = 0;

require "inc/verkkolasku-in-erittele-laskut.inc";

if ($handle = opendir($verkkolaskuvirheet_vaarat)) {

  require "inc/verkkolasku-in.inc";

  echo "<table><tr>";
  echo "<th>".t("Vastaanottaja")."<br>".t("Yhtiö")."</th><th>".t("Toiminto")."</th><th>".t("Ovttunnus")."<br>".t("Y-tunnus")."</th><th>".t("Toimittaja")."</th><th>".t("Laskunumero")."<br>".t("Maksutili")."<br>".t("Summa")."</th><th>".t("Pvm")."</th></tr><tr>";

  while (($file = readdir($handle)) !== FALSE) {

    if (is_file($verkkolaskuvirheet_vaarat."/".$file) and mb_substr($file, 0, 1) != ".") {
      unset($yhtiorow);
      unset($xmlstr);

      // Napataan alkuperäinen kukarow
      $vv_kukarow = $kukarow;

      $returni = verkkolasku_in($verkkolaskuvirheet_vaarat."/".$file, FALSE);

      if (is_array($returni)) {
        $lasku_yhtio    = $returni[0];
        $lasku_toimittaja  = $returni[1];
      }
      elseif (mb_stripos($returni, "ei ole validi XML") !== FALSE) {

        $laskut = $verkkolaskuvirheet_vaarat;

        $luotiinlaskuja = erittele_laskut($verkkolaskuvirheet_vaarat."/".$file);

        // Jos tiedostosta luotiin laskuja siirretään se tieltä pois
        if ($luotiinlaskuja > 0) {
          rename($verkkolaskuvirheet_vaarat."/".$file, $verkkolaskut_orig."/".$file);
        }

        $lasku_yhtio["yhtio"] = "EI KIITOS TÄLLÄ KERTAA";
      }

      // Palautetaan alkuperäinen kukarow
      $kukarow = $vv_kukarow;

      if ($lasku_yhtio["yhtio"] == $kukarow["yhtio"] or $lasku_yhtio["yhtio"] == "") {

        $valitutlaskut++;

        // Otetaan tarvittavat muuttujat tännekin
        $xml = simplexml_load_string($xmlstr);

        // Katsotaan mitä aineistoa käpistellään
        if (mb_strpos($file, "finvoice") !== false or mb_strpos($file, "maventa") !== false or mb_strpos($file, "apix") !== false) {
          require "inc/verkkolasku-in-finvoice.inc";
          $kumpivoice = "FINVOICE";
        }
        elseif (mb_strpos($file, "teccominvoice") !== false) {
          require "inc/verkkolasku-in-teccom.inc";
          $kumpivoice = "TECCOM";
        }
        else {
          require "inc/verkkolasku-in-pupevoice.inc";
          $kumpivoice = "PUPEVOICE";
        }

        $laskuttajan_osoite   = "";
        $laskuttajan_postitp   = "";
        $laskuttajan_postino   = "";
        $laskuttajan_maa     = "";
        $laskuttajan_tilino   = "";

        if ($kumpivoice == "PUPEVOICE") {
          $laskuttajan_osoite   = array_shift($xml->xpath('Group2/NAD[@e3035="II"]/@eC059.3042.1'));
          $laskuttajan_postitp   = array_shift($xml->xpath('Group2/NAD[@e3035="II"]/@e3164'));
          $laskuttajan_postino   = array_shift($xml->xpath('Group2/NAD[@e3035="II"]/@e3251'));
          $laskuttajan_maa     = array_shift($xml->xpath('Group2/NAD[@e3035="II"]/@e3207'));

          $laskuttajan_tilino   = array_shift($xml->xpath('Group2/FII[@e3035="BF"]/@eC078.3194'));
        }
        elseif ($kumpivoice == "TECCOM") {
          $laskuttajan_osoite   = $xml->InvoiceHeader->SellerParty->Address->Street1;
          $laskuttajan_postitp   = $xml->InvoiceHeader->SellerParty->Address->City;
          $laskuttajan_postino   = $xml->InvoiceHeader->SellerParty->Address->PostalCode;
          $laskuttajan_maa     = $xml->InvoiceHeader->SellerParty->Address->CountryCode;
          $laskuttajan_tilino    = $lasku_toimittaja["ultilno"];
          $laskuttajan_ovt    = $lasku_toimittaja["ovt_tunnus"];
          $laskuttajan_vat    = $lasku_toimittaja["ytunnus"];

        }
        elseif ($kumpivoice == "FINVOICE") {
          $laskuttajan_osoite   = $xml->SellerPartyDetails->SellerPostalAddressDetails->SellerStreetName;
          $laskuttajan_postitp   = $xml->SellerPartyDetails->SellerPostalAddressDetails->SellerTownName;
          $laskuttajan_postino   = $xml->SellerPartyDetails->SellerPostalAddressDetails->SellerPostCodeIdentifier;
          $laskuttajan_maa     = $xml->SellerPartyDetails->SellerPostalAddressDetails->CountryCode;

          $laskuttajan_tilino    = $xml->SellerInformationDetails->SellerAccountDetails->SellerAccountID;
        }

        echo "<tr>";

        if ($lasku_yhtio["yhtio"] == $kukarow["yhtio"]) {
          echo "<td>$yhtio<br>$yhtiorow[nimi]</td>";
        }
        else {
          echo "<td>$yhtio<br>{$ostaja_asiakkaantiedot["nimi"]}<br><font class='error'>".t("HUOM: Laskun vastaanottaja epäselvä")."!</font></td>";
        }

        echo "<td nowrap>";

        // Olisiko toimittaja sittenkin jossain (väärin perustettu)
        if ($lasku_toimittaja["tunnus"] == 0) {

          $toimittaja_array = array();

          // $laskuttajan_ovt --> voi olla ovttunnus, ytunnus, tai vatnumero
          // $laskuttajan_vat --> voisi olla vatnumero
          // $laskuttajan_nimi --> voisi olla laskuttajan nimi
          // $laskuttajan_toimittajanumero --> toimittajanumero
          $laskuttajan_ovt          = trim($laskuttajan_ovt);
          $laskuttajan_vat          = trim($laskuttajan_vat);
          $laskuttajan_nimi         = trim($laskuttajan_nimi);
          $laskuttajan_toimittajanumero  = trim($laskuttajan_toimittajanumero);
          $laskun_toimitunnus       = (int) $laskun_toimitunnus;

          if ($laskun_toimitunnus > 0) {
            // 0 etsitään toimittaja tunnuksella
            $query  = "SELECT *
                       FROM toimi
                       WHERE tunnus  = '{$laskun_toimitunnus}'
                       and yhtio     = '{$kukarow["yhtio"]}'
                       and tyyppi   != 'P'";
            $result = pupe_query($query);

            while ($trow = mysqli_fetch_assoc($result)) {
              $toimittaja_array[$trow["tunnus"]] = $trow;
            }
          }

          if (!isset($trow) and $laskuttajan_ovt != "") {
            // 1 etsitään toimittaja ovttunnuksella
            $query  = "SELECT *
                       FROM toimi
                       WHERE ovttunnus  = '$laskuttajan_ovt'
                       and ovttunnus    not in ('','0')
                       and yhtio        = '{$kukarow["yhtio"]}'
                       and tyyppi      != 'P'";
            $result = pupe_query($query);

            while ($trow = mysqli_fetch_assoc($result)) {
              $toimittaja_array[$trow["tunnus"]] = $trow;
            }
          }

          if (!isset($trow) and $laskuttajan_ovt != "") {
            // 2 etsitään toimittaja ovt-tunnuksella ilman tarkenteita
            $yovt = mb_substr($laskuttajan_ovt, 0, 12); // Poistetaan mahdolliset ovt-tunnuksen tarkenteet

            $query  = "SELECT *
                       FROM toimi
                       WHERE ovttunnus  = '$yovt'
                       and ovttunnus    not in ('','0')
                       and yhtio        = '{$kukarow["yhtio"]}'
                       and tyyppi      != 'P'";
            $result = pupe_query($query);

            while ($trow = mysqli_fetch_assoc($result)) {
              $toimittaja_array[$trow["tunnus"]] = $trow;
            }
          }

          if (!isset($trow) and $laskuttajan_vat != "") {
            // 3 etsitään toimittaja vat-numerolla
            $query  = "SELECT *
                       FROM toimi
                       WHERE ovttunnus  = '$laskuttajan_vat'
                       and ovttunnus    not in ('','0')
                       and yhtio        = '{$kukarow["yhtio"]}'
                       and tyyppi      != 'P'";
            $result = pupe_query($query);

            while ($trow = mysqli_fetch_assoc($result)) {
              $toimittaja_array[$trow["tunnus"]] = $trow;
            }
          }

          if (!isset($trow) and $laskuttajan_vat != "") {
            // 4 etsitään toimittaja vat-numerolla
            $query  = "SELECT *
                       FROM toimi
                       WHERE ytunnus  = '$laskuttajan_vat'
                       and ytunnus    not in ('','0')
                       and yhtio      = '{$kukarow["yhtio"]}'
                       and tyyppi    != 'P'";
            $result = pupe_query($query);

            while ($trow = mysqli_fetch_assoc($result)) {
              $toimittaja_array[$trow["tunnus"]] = $trow;
            }
          }

          if (!isset($trow) and $laskuttajan_ovt != "") {
            // 5 etsitään toimittaja ytunnuksella
            $yovt1 = mb_substr(preg_replace("/^0037/", "", $laskuttajan_ovt), 0, 8); // mahdollisella etunollalla
            $yovt2 = (int) $yovt1; // ilman etunollaa

            $query  = "SELECT *
                       FROM toimi
                       WHERE ytunnus  in ('$yovt1', '$yovt2')
                       and ytunnus    not in ('','0')
                       and yhtio      = '{$kukarow["yhtio"]}'
                       and tyyppi    != 'P'";
            $result = pupe_query($query);

            while ($trow = mysqli_fetch_assoc($result)) {
              $toimittaja_array[$trow["tunnus"]] = $trow;
            }
          }

          if (!isset($trow) and $laskuttajan_vat != "") {
            $intvat = preg_replace("/[^0-9]/", "", $laskuttajan_vat);
            $intvat2 = (int) $intvat; // ilman etunollaa

            // 6 etsitään toimittaja vat-numerolla ilman FI-etuliitettä
            $query  = "SELECT *
                       FROM toimi
                       WHERE REPLACE(REPLACE(REPLACE(ytunnus,'FI',''),'fi',''),'-','') in ('$intvat', '$intvat2')
                       and ytunnus  not in ('','0')
                       and yhtio    = '{$kukarow["yhtio"]}'
                       and tyyppi  != 'P'";
            $result = pupe_query($query);

            while ($trow = mysqli_fetch_assoc($result)) {
              $toimittaja_array[$trow["tunnus"]] = $trow;
            }
          }

          if (!isset($trow) and $laskuttajan_toimittajanumero != "") {
            // 9 etsitään toimittaja teccomi-specific
            $query = "SELECT *
                      FROM toimi
                      WHERE yhtio        = '{$kukarow["yhtio"]}'
                      and toimittajanro  = '$laskuttajan_toimittajanumero'
                      and tyyppi        != 'P'";
            $result = pupe_query($query);

            while ($trow = mysqli_fetch_assoc($result)) {
              $toimittaja_array[$trow["tunnus"]] = $trow;
            }
          }

          if (!isset($trow) and $laskuttajan_nimi != "") {
            // 7 etsitään toimittaja nimellä
            $query = "SELECT *
                      FROM toimi
                      WHERE yhtio  = '{$kukarow["yhtio"]}'
                      and nimi     = '$laskuttajan_nimi'
                      and nimi     not in ('','0')
                      and tyyppi  != 'P'";
            $result = pupe_query($query);

            while ($trow = mysqli_fetch_assoc($result)) {
              $toimittaja_array[$trow["tunnus"]] = $trow;
            }
          }

          if (!isset($trow) and $laskuttajan_nimi != "") {

            $laskuttajan_nimi = str_replace(" ", "", $laskuttajan_nimi);

            // 8 etsitään IBAN-numerolla. (Maventa special: Jos laskulta puuttuu ytunnus, niin IBAN-työnnetaan laskun nimi-kenttään...)
            $query = "SELECT *
                      FROM toimi
                      WHERE yhtio  = '{$kukarow["yhtio"]}'
                      and ultilno  = '$laskuttajan_nimi'
                      and ultilno  not in ('','0')
                      and tyyppi  != 'P'";
            $result = pupe_query($query);

            while ($trow = mysqli_fetch_assoc($result)) {
              $toimittaja_array[$trow["tunnus"]] = $trow;
            }
          }

          // vaihtoehtoiset_verkkolaskutunnukset poikkeus tarkistus
          if (!isset($trow)) {
            $poikkeus_arvot = array(
              'nimi' => $laskuttajan_nimi,
              'ytunnus' => $laskuttajan_vat,
              'ovt_tunnus' => $laskuttajan_ovt
            );

            foreach ($poikkeus_arvot as $poikkeus_sarake => $poikkeus_arvo) {
              $query = "SELECT *
                        FROM vaihtoehtoiset_verkkolaskutunnukset
                        WHERE yhtio      = '{$kukarow["yhtio"]}'
                        AND kohde_sarake = '{$poikkeus_sarake}'
                        AND arvo         = '{$poikkeus_arvo}'";
              $result = pupe_query($query);
              $poikkeus = mysqli_fetch_assoc($result);

              if (!empty($poikkeus)) {
                $query = "SELECT *
                          FROM toimi
                          WHERE yhtio  = '{$kukarow["yhtio"]}'
                          and tunnus   = '{$poikkeus['toimi_tunnus']}'
                          and tyyppi  != 'P'";
                $result = pupe_query($query);

                if (mysqli_num_rows($result) == 1) {
                  $toimittaja_array[$trow["tunnus"]] = $trow;
                }
              }
            }
          }
        }

        if ($lasku_toimittaja["tunnus"] == 0) {
          if (count($toimittaja_array) > 0) {

            if ($kumpivoice == "FINVOICE") {
              echo t("Valitse toimittaja ja käsittele lasku uudestaan").":<br>";
              echo "<form method='post'>
                  <input type='hidden' name = 'tiedosto' value='$file'>
                  <input type='hidden' name = 'tapa' value='U_JA_P'>
                  <input type='hidden' name = 'kumpivoice' value='$kumpivoice'>
                  <select name='tunnus'>";

              foreach ($toimittaja_array as $lahellarow) {
                echo "<option value='$lahellarow[tunnus]'>$lahellarow[nimi] $lahellarow[nimitark]</option>";
              }

              echo "</select><input type='submit' value ='".t("Käsittele uudestaan")."'></form><br><br>";
            }

            echo t("Muuta toimittajan tietoja").":<br>";
            echo "<form action='{$palvelin2}yllapito.php' method='post'>
                <input type = 'hidden' name = 'toim' value = 'toimi'>
                <input type = 'hidden' name = 'lopetus' value = '".$palvelin2."verkkolaskuvirheet.php////'>
                <select name='tunnus'>";

            foreach ($toimittaja_array as $lahellarow) {
              echo "<option value='$lahellarow[tunnus]'>$lahellarow[nimi] $lahellarow[nimitark]</option>";
            }

            echo "</select><input type='submit' value ='".t("Päivitä")."'></form><br><br>";
          }

          echo "<form name='toimittajahaku_form' action='' method='POST'>";
          echo "<input type='hidden' name = 'tiedosto' value='$file'>";
          echo "<input type='hidden' name = 'kumpivoice' value='$kumpivoice'>";
          echo "<input type='hidden' name='tapa' value='U_JA_P' />";

          echo t('Etsi toimittaja ja käsittele lasku uudestaan').':<br>';
          echo livesearch_kentta("eisaaollaoikee", "TOIMITTAJAHAKU", "toimittaja_haku", 140, $toimittaja_haku, '', '', 'toimittaja_haku', 'ei_break_all');
          echo "<input type='submit' value='".t("Käsittele uudestaan")."' />";
          echo "</form>";
          echo "<br/>";
          echo "<br/>";

          echo t("Perusta uusi toimittaja").":<br>";
	  $lvat = preg_replace("[\D]", "", $laskuttajan_ovt); // MUOKKAUS: haetaan Y-tunnus OVT:sta, caset laskuttajan_nimi & osoite & postitp, tilinumero & Y-tunnus oikealle kohtaa
          echo "<form action='{$palvelin2}yllapito.php' method='post'>
              <input type = 'hidden' name = 'toim' value = 'toimi'>
              <input type = 'hidden' name = 'uusi' value = '1'>
              <input type = 'hidden' name = 't[1]' value = '" . ucfirst(mb_strtolower($laskuttajan_nimi)) . "'>
              <input type = 'hidden' name = 't[3]' value = '" . ucfirst(mb_strtolower($laskuttajan_osoite)) . "'>
              <input type = 'hidden' name = 't[5]' value = '$laskuttajan_postino'>
              <input type = 'hidden' name = 't[6]' value = '" . mb_strtoupper($laskuttajan_postitp) . "'>
              <input type = 'hidden' name = 't[7]' value = '$laskuttajan_maa'>
              <input type = 'hidden' name = 't[28]' value = '$laskuttajan_tilino'>
              <input type = 'hidden' name = 't[65]' value = '$lvat'>
              <input type = 'hidden' name = 't[66]' value = '" . substr($laskuttajan_ovt, -8) . "'>
              <input type = 'hidden' name = 'lopetus' value = '".$palvelin2."verkkolaskuvirheet.php////'>
              <input type='submit' value = '".t("Perusta")."'></form><br><br>";
        }
        else {
          echo t("Käsittele lasku uudestaan").":<br>";
          echo "<form method='post'>
              <input type='hidden' name = 'tiedosto' value ='$file'>
              <input type='hidden' name = 'tapa' value ='U'>
              <input type='submit' value = '".t("Käsittele uudestaan")."'></form><br><br>";
        }

        echo t("Hylkää lasku").":<br>";
        echo "<form method='post'>
            <input type='hidden' name = 'tiedosto' value ='$file'>
            <input type='hidden' name = 'tapa' value ='P'>
            <input type='submit' value = '".t("Hylkää")."'></form>";

        echo "</td>";

        echo "<td>$laskuttajan_ovt<br>$laskuttajan_vat</td>";
        echo "<td>$laskuttajan_nimi<br>$laskuttajan_osoite<br>$laskuttajan_postino<br>$laskuttajan_postitp<br>$laskuttajan_maa</td>";
        echo "<td>$laskun_numero<br>$laskuttajan_tilino<br>$laskun_summa_eur<br>";

        if ($kumpivoice == "PUPEVOICE") {
          $verkkolaskutunnus = $yhtiorow['verkkotunnus_vas'];
          $salasana       = $yhtiorow['verkkosala_vas'];

          $timestamppi = gmdate("YmdHis")."Z";

          $urlhead = "http://www.verkkolasku.net";
          $urlmain = "/view/ebs-2.0/$verkkolaskutunnus/visual?DIGEST-ALG=MD5&DIGEST-KEY-VERSION=1&EBID=$laskun_ebid&TIMESTAMP=$timestamppi&VERSION=ebs-2.0";

          $digest   = md5($urlmain . "&" . $salasana);
          $url   = $urlhead.$urlmain."&DIGEST=$digest";

          echo "<a href='$url' target='laskuikkuna'>". t('Näytä lasku')."</a>";
        }
        else {

          // Maventa- tai APIX-lasku, niin yritetään hakea laskun kuva mukaan
          if (preg_match("/(maventa|apix)_(.*?)_(maventa|apix)/", basename($file), $match)) {

            // Haetaan liitteet
            unset($liitefilet);
            $liitteet = exec("find $verkkolaskut_orig -name \"*{$match[1]}_{$match[2]}_{$match[1]}*\"", $liitefilet);

            if ($liitteet != "") {
              foreach ($liitefilet as $liitefile) {
                if (mb_strtoupper(mb_substr($liitefile, -4)) == ".PDF") {
                  echo "<form id='form_1_$valitutlaskut' name='form_1_$valitutlaskut' method='post'>
                    <input type='hidden' name = 'tee' value ='NAYTATILAUS'>
                    <input type='hidden' name = 'pdf' value ='".urlencode(file_get_contents($liitefile))."'>
                    <input type='submit' value = '".t("Näytä Pdf")."' onClick=\"js_openFormInNewWindow('form_1_$valitutlaskut', 'form_1_$valitutlaskut'); return false;\"></form>";
                }
              }
            }
          }

          echo "<form id='form_2_$valitutlaskut' name='form_2_$valitutlaskut' method='post'>
            <input type='hidden' name = 'tee' value ='NAYTATILAUS'>
            <input type='hidden' name = 'xml' value ='".urlencode($xmlstr)."'>
            <input type='submit' value = '".t("Näytä Finvoice")."' onClick=\"js_openFormInNewWindow('form_2_$valitutlaskut', 'form_2_$valitutlaskut'); return false;\"></form>";
        }

        echo "</td>";

        $tpp = mb_substr($laskun_tapvm, 6, 2);
        $tpk = mb_substr($laskun_tapvm, 4, 2);
        $tpv = mb_substr($laskun_tapvm, 0, 4);

        echo "<td>".tv1dateconv($tpv."-".$tpk."-".$tpp)."</td>";
        echo "</tr>";
      }
    }
  }
  closedir($handle);
  echo "</table>";
}

if ($valitutlaskut == 0) {
  echo "<font class='message'>".t("Ei hylättyjä laskuja")."</font><br>";
}

require "inc/footer.inc";
