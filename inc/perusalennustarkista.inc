<?php

if (!function_exists("perusalennustarkista")) {
  function perusalennustarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set;

  }

  if (mysqli_field_name($result, $i) == "ryhma") {
    if (trim($t[$i]) == '') {
      $virhe[$i] = t("Tieto puuttuu");
    }
    else {
      if ($tunnus == "") {
        $query  = "SELECT yhtio
                   FROM perusalennus
                   WHERE yhtio = '$kukarow[yhtio]'
                   and ryhma   = '".trim($t[$i])."'";
        $chkressiresult = pupe_query($query);

        if (mysqli_num_rows($chkressiresult) > 0 or trim($t[$i]) == '**') {
          $virhe[$i] = t("Alennusryhmä on jo perustettu järjestelmään");
        }
      }
    }
  }
}
