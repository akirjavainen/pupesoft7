<?php

if (!function_exists("tuoteperhetarkista")) {
  function tuoteperhetarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set;

    $fieldname = mysqli_field_name($result, $i);

    //tarkistetaan, että tuotenumerot on olemassa ennenkuin yritetään lisätä ne tuoteperheet sinne kantaan!
    if ($fieldname == "tuoteno" or $fieldname == "isatuoteno") {
      if (trim($t[$i]) == '') {
        $virhe[$i] = t("Tieto puuttuu");
      }
      elseif ($trow["luedata_toiminto"] != "POISTA") {
        $olemassa = hae_tuote(trim($t[$i]));
        if (empty($olemassa)) {
          $virhe[$i] = t("Tuotenumeroa ei ole olemassa");
        }
      }
    }
  }
}
