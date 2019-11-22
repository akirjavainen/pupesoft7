<?php

/*
 * katelaskenta_functions.php
 *
 * Sisältää entistä koodia, jota on jaettu pienempiin funktioihin.
 * Tarvitaan, jotta tietyt toiminnot katelaskennan haku osiossa toimii.
 * Varmuutta ei ole mitä kaikkea nämä tekevät ja mitä ei tarvita.
 * Siistitään tiedostoa kun tulee tarpeellisesti.
 *
 * Katelaskennan omat funktiot ovat functions.katelaskenta.php -tiedostossa.
 */

/**
 * Funktio valmistelee hakutulokset templatea varten.
 *
 * Palauttaa muokatun hakutulostaulukon.
 *
 * @param type    $tuotteet
 */


function valmistele_hakutulokset($tuotteet) {
  foreach ($tuotteet as $avain => $arvo) { // $rows muuttuja tulee templaten ulkopuolelta
    // Merkitään nimitykseen "poistuva"
    if (mb_strtoupper($arvo["status"]) == "P") {
      $tuotteet[$avain]["nimitys"] .= "<br> * " . t("Poistuva tuote");
    }

    $tuotteet[$avain]["myyntihinta"] = hintapyoristys($arvo["myyntihinta"], 2);
    $tuotteet[$avain]["myymalahinta"] = hintapyoristys($arvo["myymalahinta"], 2);
    $tuotteet[$avain]["nettohinta"] = hintapyoristys($arvo["nettohinta"], 2);
    $tuotteet[$avain]["kehahin"] = hintapyoristys($arvo["kehahin"], 2);
  }

  return $tuotteet;
}
