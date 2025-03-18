<?php

if (!function_exists("yhteyshenkilotarkista")) {
  function yhteyshenkilotarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set;

    static $liitostunnus_tarkista, $tyyppi_tarkista;

    if (mysqli_field_name($result, $i) == "nimi" and $t[$i] == "") {
      $virhe[$i] = t("Tieto puuttuu");
    }

    if (mysqli_field_name($result, $i) == "tyyppi") {
      $tyyppi_tarkista = $t[$i];

      if ($t[$i] != "A" and $t[$i] != "T") {
        $virhe[$i] = t("Virheellinen tyyppi");
      }
    }

    if (mysqli_field_name($result, $i) == "liitostunnus") {
      $liitostunnus_tarkista = $t[$i];
    }

    if (mysqli_field_name($result, $i) == "oletusyhteyshenkilo" and $t[$i] != "") {
      $query = "SELECT tunnus
                FROM yhteyshenkilo
                WHERE yhtio              = '$kukarow[yhtio]'
                and oletusyhteyshenkilo != ''
                and liitostunnus         = '$liitostunnus_tarkista'
                and tyyppi               = '$tyyppi_tarkista'";
      $result = pupe_query($query);

      if (mysqli_num_rows($result) > 1) {
        $virhe[$i] = t("Oletusyhteyshenkilö on jo valittu")."!";
      }
    }
  }
}
