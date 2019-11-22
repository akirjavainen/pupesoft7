<?php

if (!function_exists("tuotteen_toimittajat_tuotenumerottarkista")) {
  function tuotteen_toimittajat_tuotenumerottarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set, $upd, $uusi, $toim_tuoteno, $liitostunnus;

    static $chk_tuoteno, $chk_viivakoodi, $chk_toim_tuoteno_tunnus;

    if (mysqli_field_name($result, $i) == "toim_tuoteno_tunnus") {
      $chk_toim_tuoteno_tunnus = $t[$i];
    }

    if (mysqli_field_name($result, $i) == "tuoteno") {
      $chk_tuoteno = $t[$i];
    }

    if (mysqli_field_name($result, $i) == "viivakoodi") {
      $chk_viivakoodi = $t[$i];

      // Tsekataan ettei kannassa ole samanlaista settiä
      $query = "SELECT *
                FROM tuotteen_toimittajat_tuotenumerot
                WHERE yhtio              = '{$kukarow['yhtio']}'
                AND toim_tuoteno_tunnus  = '{$chk_toim_tuoteno_tunnus}'
                AND tuoteno              = '{$chk_tuoteno}'
                AND viivakoodi           = '{$chk_viivakoodi}'
                AND tunnus              != '{$tunnus}'";
      $chk_res = pupe_query($query);

      if (mysqli_num_rows($chk_res) != 0) {
        $virhe[$i] = t("Samoilla tiedoilla oleva rivi on jo tietokannassa")."!";
      }
    }
  }
}
