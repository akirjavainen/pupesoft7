<?php

if (!function_exists("toimitustavan_lahdottarkista")) {
  function toimitustavan_lahdottarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set, $upd, $uusi;

    if ((mysqli_field_name($result, $i) == "kerailyn_aloitusaika") or (mysqli_field_name($result, $i) == "viimeinen_tilausaika") or (mysqli_field_name($result, $i) == "lahdon_kellonaika")) {

      list($hh, $mm, $loput) = explode(":", $t[$i]);

      if (mb_strpos($t[$i], " ") !== FALSE or !is_numeric($hh) or !is_numeric($mm)) {
        $virhe[$i] .= t("Anna kellonaika muodossa hh:mm")."!";
      }
      elseif ($hh > 23 or $mm > 59 or $hh < 0 or $mm < 0) {
        $virhe[$i] .= t("Virheellinen kellonaina")."!";
      }
    }

    if (mysqli_field_name($result, $i) == "lahdon_viikonpvm") {
      if (trim($t[$i]) == '') {
        $virhe[$i] .= t("Lähdön viikonpäivä puuttuu")."!";
      }
      else {
        $t[$i] = trim(sanitize_string($t[$i]));
      }
    }

    if (mysqli_field_name($result, $i) == "terminaalialue") {
      $sresult = t_avainsana('TERMINAALIALUE', '', " and selite != '' ");

      if (trim($t[$i]) == '' and mysqli_num_rows($sresult) >0) {
        $virhe[$i] .= t("Terminaalialue puuttuu")."!";
      }
      else {
        $t[$i] = trim(sanitize_string($t[$i]));
      }
    }

    if (mysqli_field_name($result, $i) == "varasto") {
      if (trim($t[$i]) == "") {
        $virhe[$i] .= t("Varasto puuttuu")."!";
      }
      else {
        $t[$i] = (int) $t[$i];
      }
    }
  }
}
