<?php

// Startaan bufferi, koska pitää tehdä keksejä keskenkaiken
ob_start();

require "inc/parametrit.inc";
require "inc/pankkiyhteys_functions.inc";

echo "<font class='head'>" . t('SEPA-pankkiyhteys') . "</font>";
echo "<hr>";

// Varmistetaan, että sepa pankkiyhteys on kunnossa. Funkio kuolee, jos ei ole.
sepa_pankkiyhteys_kunnossa();

toggle_all("viite_toggler", "viite_boxes");
toggle_all("tiliote_toggler", "tiliote_boxes");
toggle_all("factoring_tiliote_toggler", "factoring_tiliote_boxes");
toggle_all("factoring_viite_toggler", "factoring_viite_boxes");
toggle_all("finvoice_toggler", "finvoice_boxes");
toggle_all("palaute_toggler", "palaute_boxes");

$tee = empty($tee) ? '' : $tee;
$toim = empty($toim) ? '' : $toim;
$hae_tiliotteet = empty($hae_tiliotteet) ? '' : $hae_tiliotteet;
$hae_viitteet = empty($hae_viitteet) ? '' : $hae_viitteet;
$hae_laskut = empty($hae_laskut) ? '' : $hae_laskut;
$hae_palautteet = empty($hae_palautteet) ? '' : $hae_palautteet;

// Otetaan defaultit, jos ei olla yliajettu salasanat.php:ssä
$verkkolaskut_in     = empty($verkkolaskut_in)     ? "/home/verkkolaskut"        : rtrim($verkkolaskut_in, "/");
$verkkolaskut_ok     = empty($verkkolaskut_ok)     ? "/home/verkkolaskut/ok"     : rtrim($verkkolaskut_ok, "/");
$verkkolaskut_orig   = empty($verkkolaskut_orig)   ? "/home/verkkolaskut/orig"   : rtrim($verkkolaskut_orig, "/");
$verkkolaskut_error  = empty($verkkolaskut_error)  ? "/home/verkkolaskut/error"  : rtrim($verkkolaskut_error, "/");
$verkkolaskut_reject = empty($verkkolaskut_reject) ? "/home/verkkolaskut/reject" : rtrim($verkkolaskut_reject, "/");

$pankkitiedostot = array();
$virheet_count = 0;
$cookie_secret = "pupesoft_pankkiyhteys_secret";
$cookie_tunnus = "pupesoft_pankkiyhteys_tunnus";

// Jos meillä on vielä cookie voimassa, mennään suoraan valintaan
if ($tee == "" and isset($_COOKIE[$cookie_secret])) {
  $tee = "valitse";
}

// Jos meillä on vielä cookie voimassa, mennään suoraan valintaan
if ($indexvas == "1" and isset($_COOKIE[$cookie_secret])) {
  $tee = "kirjaudu_ulos";
}

// Kirjaudutaan pankkiin
if ($tee == "kirjaudu") {

  if (empty($salasana)) {
    virhe("Salasana täytyy antaa!");
    $virheet_count++;
  }

  if ($virheet_count == 0) {
    $pankki = hae_pankkiyhteys_ja_pura_salaus($pankkiyhteys_tunnus, $salasana);

    if ($pankki === false) {
      virhe("Antamasi salasana on väärä!");
      $virheet_count++;
    }
  }

  // Tarkistetaan päivämäärät
  if ($virheet_count == 0) {
    $sertit = pankkiyhteyden_paivamaarat($pankki);
    if ($sertit === false) {
      $virheet_count++;
    }
  }

  if ($virheet_count > 0) {
    $tee = "kirjaudu_ulos";
  }
  else {
    // Setataan SECURE cookiet, HTTP only
    setcookie($cookie_secret, $salasana, time() + 300, '/', $pupesoft_server, false, false); // MODIFIED, allow HTTP or HTTPS for cookies
    setcookie($cookie_tunnus, $pankkiyhteys_tunnus, time() + 300, '/', $pupesoft_server, false, false); // MODIFIED, allow HTTP or HTTPS for cookies

    // Laitetaan samantien myös globaaliin
    $_COOKIE[$cookie_secret] = $salasana;
    $_COOKIE[$cookie_tunnus] = $pankkiyhteys_tunnus;

    $tee = "valitse";
  }
}

// Kirjaudutaan ulos pankista
if ($tee == "kirjaudu_ulos") {
  // Unsetataan cookiet
  setcookie($cookie_secret, "deleted", time() - 43200, '/', $pupesoft_server, false, false); // MODIFIED, allow HTTP or HTTPS for cookies
  setcookie($cookie_tunnus, "deleted", time() - 43200, '/', $pupesoft_server, false, false); // MODIFIED, allow HTTP or HTTPS for cookies

  // Poistetaan myös globaalista
  unset($_COOKIE[$cookie_secret]);
  unset($_COOKIE[$cookie_tunnus]);

  $tee = "";
}

// Jos meillä ei ole cookieta, niin mennään aina kirjautumiseen
if ($tee != "" and !isset($_COOKIE[$cookie_secret])) {
  $tee = "";
}

// Jos toim on tyhjää, tehdään tiliotteen ja viitteen hommia
if ($toim == "") {
  require 'inc/pankkiyhteys_tilioteviite.inc';
}

// Jos toim on "palaute", tehdään maksuaineistojen palautteiden hommia
if ($toim == "palaute") {
  require 'inc/pankkiyhteys_palautteet.inc';
}

// Jos toim on "laheta", tehdään lähetyshommia
if ($toim == "laheta") {
  require 'inc/pankkiyhteys_send.inc';
}

// Sisäänkirjautumisen käyttöliittymä
if ($tee == "") {
  $formi  = 'pankkiyhteys';
  $kentta = 'salasana';

  $kaytossa_olevat_pankkiyhteydet = hae_pankkiyhteydet();

  if ($kaytossa_olevat_pankkiyhteydet) {

    echo "<form name='pankkiyhteys' method='post' action='pankkiyhteys.php'>";
    echo "<input type='hidden' name='tee' value='kirjaudu'/>";
    echo "<input type='hidden' name='toim' value='$toim'/>";
    echo "<table>";
    echo "<tbody>";

    echo "<tr>";
    echo "<th>";
    echo t("Valitse pankki");
    echo "</th>";
    echo "<td>";
    echo "<select name='pankkiyhteys_tunnus'>";

    foreach ($kaytossa_olevat_pankkiyhteydet as $pankkiyhteys) {
      $selected = $pankkiyhteys_tunnus == $pankkiyhteys["tunnus"] ? " selected" : "";

      echo "<option value='{$pankkiyhteys["tunnus"]}'{$selected}>";
      echo "{$pankkiyhteys["pankin_nimi"]}</option>";
      
      // MODIFIED, added CLI interface for scheduled retrieving:
      // ---
      if (php_sapi_name() == 'cli') {
	$viite_references = array();
    	$pankki = hae_pankkiyhteys_ja_pura_salaus($pankkiyhteys["tunnus"], $sepa_pankkiyhteys_salasana);

        $params = array(
          "file_type"             => "KTL",
          "status"                => "NEW", // NEW, DLD tai ALL
          "pankkiyhteys_tunnus"   => $pankkiyhteys["tunnus"],
          "pankkiyhteys_salasana" => $sepa_pankkiyhteys_salasana
        );

        $viite_tiedostot = sepa_download_file_list($params);
	if (empty($viite_tiedostot["files"])) exit("Ei uusia ladattavia aineistoja pankissa (1).\n\n\n");
        print_r($viite_tiedostot);
        unset($params);
  
        $params = array(
          "file_type"             => "KTL",
          "viitteet"              => $viite_tiedostot,
          "pankkiyhteys_tunnus"   => $pankkiyhteys["tunnus"],
          "pankkiyhteys_salasana" => $sepa_pankkiyhteys_salasana
        );

        $tiedostot = sepa_download_files($params);

        if ($tiedostot) {
          foreach ($tiedostot as $aineisto) {
            if (strlen($aineisto['data']) > 0) {
              $filenimi = tempnam("{$pupe_root_polku}/datain", "pankkiaineisto");
              $data = base64_decode($aineisto['data']);
              $status = file_put_contents($filenimi, $data);
              $aineistotunnus = tallenna_tiliote_viite($filenimi);
              kasittele_tiliote_viite($aineistotunnus);
              //unlink($filenimi);
            } else {
              echo "Aineisto $aineistotunnus oli tyhjä...\n\n\n";
            }
          }
        } else {
          echo "Ei uusia ladattavia aineistoja pankissa (2).\n\n\n";
        }
        exit("");
      }
      // ---

    }

    echo "</select>";
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<th><label for='salasana'>" . t("Salasana") . "</label></th>";
    echo "<td><input type='password' name='salasana' id='salasana' value='$sepa_pankkiyhteys_salasana'/></td>";
    echo "</tr>";

    echo "</tbody>";
    echo "</table>";

    echo "<br>";
    echo "<input type='submit' value='" . t('Kirjaudu') . "'>";

    echo "</form>";
  }
  else {
    viesti("Yhtään pankkiyhteyttä ei ole vielä luotu.");
  }
}

require 'inc/footer.inc';

// Flushataan bufferi tässä, koska cookiet on nyt done.
ob_end_flush();
