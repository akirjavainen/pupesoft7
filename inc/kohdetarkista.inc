<?php

if (!function_exists("kohdetarkista")) {
  function kohdetarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set, $alasveto, $asiakasid;

    if (mysqli_field_name($result, $i) == "asiakas") {
      $chastunn = trim($t[$i]);

      // Ei tsekata turhaan kun rivi dellataan luedatasta
      if ($chastunn != '' and (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA")) {

        unset($chasresult);

        if (is_numeric($chastunn) and $chastunn > 0) {
          $query  = "SELECT *
                     FROM asiakas
                     WHERE yhtio  = '$kukarow[yhtio]'
                     and laji    != 'P'
                     and tunnus   = '$chastunn'";
          $chasresult = pupe_query($query);
        }

        if (!isset($chasresult) or (isset($chasresult) and mysqli_num_rows($chasresult) != 1)) {

          $ytunnus     = $chastunn;
          $kutsuja     = "yllapito.php";
          $ulos2       = "";

          if ($ytunnus != "" and $ytunnus != "0") {
            require "asiakashaku.inc";
          }

          if ($ulos2 != "") {
            $alasveto[$i] = "<select name='t[$i]'><option value=''>".t("Valitse asiakas")."</option>".$ulos2."</select>";
            $virhe[$i]    = t("Valitse asiakas!");
          }
          elseif ($asiakasid > '') {
            $t[$i] = $asiakasid;
          }
          else {
            $virhe[$i] = t("Asiakas puuttuu tai sitä ei löydy!");
          }
        }
      }

      if (!isset($virhe[$i]) and $chastunn == '') {
        $virhe[$i] = t("Asiakas puuttuu tai sitä ei löydy!");
      }
    }
  }
}
