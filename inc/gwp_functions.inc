<?php

// MUOKKAUS: GWP A1-A3 (hiilijalanjälki) tarjous- ja tilausvahvistus-PDF:iin.
//
// Kulku:
//   1. tulosta_tarjous() / tulosta_tilausvahvistus_pdf.inc kutsuu ennen PDF:n
//      rakentamista gwp_aseta_pdf_yhteenveto($otunnus). Se pyytää Ikkunalaskurilta
//      (http://localhost/hinnasto/gwp.php?pupe_order=...) tilauksen ikkuna- ja
//      ovirivien GWP:n. Ikkunalaskuri laskee ne gwp-api.php:n kautta ja
//      tallentaa rivikohtaiset tulokset tauluun jwio_orders.orders_gwp.
//   2. alvierittely() (tarjous) ja alvierittely_lahete() (tilausvahvistus) tulostavat
//      ALV-erittelyn alle rivin "Hiilijalanjälki GWP A1-A3: 1 234 kg CO2e" ja pienellä
//      "EN 15804+A2, moduulit A1-A3", osittaisessa laskennassa lisäksi
//      "laskettu 8/10 ikkunalle tai ovelle".
//
// Virheet eivät koskaan kaada PDF:n tulostusta: jos Ikkunalaskuri tai rajapinta ei
// vastaa (aikakatkaisu GWP_PUPESOFT_TIMEOUT, oletus 30 s) tai yhtään riviä ei saada
// laskettua, GWP-riviä ei tulosteta lainkaan — nollaa ei koskaan näytetä asiakkaalle.

if (!function_exists('gwp_aseta_pdf_yhteenveto')) {

  // Hakee yhteenvedon ja asettaa sen seuraavan PDF:n käyttöön.
  // $otunnus <= 0 tyhjentää edellisen (kutsutaan PDF:n valmistuttua).
  function gwp_aseta_pdf_yhteenveto($otunnus) {
    $GLOBALS['gwp_pdf'] = array('yhteenveto' => null, 'tulostettu' => false);

    $otunnus = (int) $otunnus;
    if ($otunnus <= 0) {
      return null;
    }

    $GLOBALS['gwp_pdf']['yhteenveto'] = gwp_hae_yhteenveto($otunnus);
    return $GLOBALS['gwp_pdf']['yhteenveto'];
  }

  // Kutsuu Ikkunalaskurin hinnasto/gwp.php:tä. Palauttaa yhteenvetotaulukon tai null.
  function gwp_hae_yhteenveto($otunnus) {
    global $kukarow;

    try {
      // Vain asennuksissa, joissa Ikkunalaskuri (hinnasto) on Pupesoftin rinnalla:
      $hinnasto = dirname(dirname(dirname(__FILE__)))."/hinnasto";
      if (!is_dir($hinnasto) or !function_exists('curl_init')) {
        return null;
      }

      $url = defined('GWP_HINNASTO_URL') ? GWP_HINNASTO_URL : "http://localhost/hinnasto/gwp.php";
      $url .= "?".http_build_query(array(
        "pupe_order" => (int) $otunnus,
        "yhtio"      => isset($kukarow['yhtio']) ? $kukarow['yhtio'] : "",
      ));
      $aikaraja = defined('GWP_PUPESOFT_TIMEOUT') ? (int) GWP_PUPESOFT_TIMEOUT : 30;

      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
      curl_setopt($ch, CURLOPT_TIMEOUT, $aikaraja);
      $vastaus = curl_exec($ch);
      $virhe = curl_error($ch);
      curl_close($ch);

      if ($vastaus === false) {
        error_log("GWP: hinnasto/gwp.php ei vastannut (tilaus $otunnus): $virhe");
        return null;
      }

      $data = json_decode($vastaus, true);
      if (!is_array($data) or !isset($data['status'])) {
        error_log("GWP: hinnasto/gwp.php palautti virheellisen vastauksen (tilaus $otunnus)");
        return null;
      }
      if ($data['status'] == 'error' or $data['status'] == 'partial') {
        error_log("GWP: tilaus $otunnus, tila $data[status]: ".json_encode(isset($data['errors']) ? $data['errors'] : $data));
      }
      return $data;
    }
    catch (Throwable $e) {
      error_log("GWP: ".$e->getMessage());
      return null;
    }
  }

  // Palauttaa tulostettavat rivit (array('arvo' => ..., 'huomautus' => ...)) tai
  // tyhjän taulukon, jos GWP-riviä ei tulosteta.
  function gwp_pdf_tekstit($kieli = "") {
    if (empty($GLOBALS['gwp_pdf']['yhteenveto']) or !empty($GLOBALS['gwp_pdf']['tulostettu'])) {
      return array();
    }

    $y = $GLOBALS['gwp_pdf']['yhteenveto'];

    if (!in_array($y['status'], array('ok', 'partial')) or empty($y['quantityOk']) or !isset($y['gwpA1A3Total'])) {
      return array();
    }

    $kg = (float) $y['gwpA1A3Total'];
    $huomautus = t("EN 15804+A2, moduulit A1-A3", $kieli);

    if ($y['status'] == 'partial') {
      $huomautus .= ", ".sprintf(t("laskettu %s/%s ikkunalle tai ovelle", $kieli), (int) $y['quantityOk'], (int) $y['quantity']);
    }

    return array(
      'otsikko'   => t("Hiilijalanjälki GWP A1-A3", $kieli).":",
      'arvo'      => number_format($kg, ($kg < 100 ? 1 : 0), ',', ' '),
      'yksikko'   => "kg CO2e",
      'huomautus' => $huomautus,
    );
  }

  // Pystytila (pt), jonka GWP-rivit vievät ALV-erittelyn alla. Käytetään
  // sivunvaihtorajassa, jotta rivit eivät osu alatunnisteeseen.
  function gwp_pdf_tila() {
    return count(gwp_pdf_tekstit()) > 0 ? 28 : 0;
  }

  // Merkitsee GWP-rivit tulostetuiksi (ne tulostetaan vain kerran per PDF).
  function gwp_pdf_merkitse_tulostetuksi() {
    if (isset($GLOBALS['gwp_pdf'])) {
      $GLOBALS['gwp_pdf']['tulostettu'] = true;
    }
  }
}
