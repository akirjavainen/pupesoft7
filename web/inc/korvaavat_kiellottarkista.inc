<?php

if (!function_exists("korvaavat_kiellottarkista")) {
  function korvaavat_kiellottarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set;

    if (mysqli_field_name($result, $i) == "ytunnus" and $t[$i] != '*') {

      $query   = "select tunnus from asiakas where yhtio='$kukarow[yhtio]' and ytunnus='$t[$i]'";
      $sresult = pupe_query($query);

      if (mysqli_num_rows($sresult) == 0) {
        $virhe[$i] = t("Ytunnus ei löydy!");
      }

    }
  }
}
