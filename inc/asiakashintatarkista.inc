<?php

if (!function_exists("asiakashintatarkista")) {
  function asiakashintatarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set, $alasveto;

    static $chytunnus, $chasiakas_ryhma, $chtuoteno, $chryhma, $chminkpl, $chmaxkpl, $chalkupvm, $chloppupvm, $chastunn, $chtuoteno_ind, $chsegmentti, $chpiiri, $chvalkoodi;

    if (mysqli_field_name($result, $i) == "hinta") {
      if (trim($t[$i]) == '') {
        $virhe[$i] = t("Tieto puuttuu!");
      }
    }

    if (mysqli_field_name($result, $i) == "valkoodi") {
      $chvalkoodi = trim($t[$i]);
    }

    if (mysqli_field_name($result, $i) == "ytunnus") {
      $chytunnus = trim($t[$i]);

      // Ei tsekata turhaan kun rivi dellataan luedatasta
      if ($chytunnus != '' and (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA")) {
        $query = "SELECT ytunnus
                  FROM asiakas
                  WHERE yhtio  = '$kukarow[yhtio]'
                  and laji    != 'P'
                  and ytunnus  = '$t[$i]'";
        $sresult = pupe_query($query);

        if (mysqli_num_rows($sresult) == 0) {
          $virhe[$i] = t("Ytunnuksella ei löydy asiakasta!");
        }
      }
    }

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

          if ($ulos2 != "" and $ytunnus == "") {
            $alasveto[$i] = "<select name='t[$i]'><option value=''>".t("Valitse asiakas")."</option>".$ulos2."</select>";
            $virhe[$i]    = t("Valitse asiakas!");
          }
          elseif ($ytunnus != "" and $asiakasid > 0) {
            $t[$i]  = $asiakasid;
            if (mb_strpos($_SERVER['SCRIPT_NAME'], "lue_data.php") === FALSE) $virhe[$i] = t("Asiakas löytyi!");
          }
          else {
            $virhe[$i] = t("Asiakas puuttuu tai sitä ei löydy!");
          }
        }
      }
    }

    if (mysqli_field_name($result, $i) == "asiakas_ryhma") {
      $chasiakas_ryhma = trim($t[$i]);

      // Ei tsekata turhaan kun rivi dellataan luedatasta
      if ($chasiakas_ryhma != '' and (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA")) {
        $sresult = t_avainsana("ASIAKASRYHMA", "", "and avainsana.selite = '$chasiakas_ryhma'");

        if (mysqli_num_rows($sresult) == 0) {
          $virhe[$i] = t("Asiakasryhmä puuttuu tai sitä ei löydy!");
        }
      }
    }

    if (mysqli_field_name($result, $i) == "tuoteno") {
      $chtuoteno = $t[$i];
      $chtuoteno_ind = $i;

      // Ei tsekata turhaan kun rivi dellataan luedatasta
      if ($chtuoteno != '' and (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA")) {
        $query = "SELECT tuoteno
                  FROM tuote
                  WHERE yhtio='$kukarow[yhtio]' and tuoteno = '$t[$i]'";
        $sresult = pupe_query($query);

        if (mysqli_num_rows($sresult) != 1) {

          if (mb_substr($t[$i], -1) != '*') $tuoteno = $t[$i]."*";
          else $tuoteno = $t[$i];

          $kutsuja     = "yllapito.php";
          $ulos      = "";

          require "inc/tuotehaku.inc";

          if ($ulos != "") {
            $alasveto[$i] = "<select name='t[$i]'>".$ulos."</select>";
          }

          $virhe[$i] = t("Tuotenumero puuttuu tai sitä ei löydy!");
        }
      }
    }

    if (mysqli_field_name($result, $i) == "ryhma") {
      $chryhma = $t[$i];

      // Ei tsekata turhaan kun rivi dellataan luedatasta
      if ($chryhma != '' and (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA")) {
        $query = "SELECT tunnus
                  FROM perusalennus
                  WHERE yhtio='$kukarow[yhtio]' and ryhma = '$t[$i]'";
        $sresult = pupe_query($query);

        if (mysqli_num_rows($sresult) != 1) {
          $virhe[$i] = t("Aleryhmä puuttuu tai sitä ei löydy!");
        }
      }
    }

    if (mysqli_field_name($result, $i) == "asiakas_segmentti") {

      $chsegmentti = (int) $t[$i];

      // Ei tsekata turhaan kun rivi dellataan luedatasta
      if ($chsegmentti > 0 and (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA")) {
        $preq = "SELECT * FROM dynaaminen_puu where yhtio='$kukarow[yhtio]' and laji='asiakas' and tunnus='$chsegmentti'";
        $preres = pupe_query($preq);

        if (mysqli_num_rows($preres) != 1) {
          $virhe[$i] = t("Asiakassegmentti puuttuu tai sitä ei löydy!");
        }
      }
    }

    if (mysqli_field_name($result, $i) == "piiri") {

      $chpiiri = $t[$i];

      // Ei tsekata turhaan kun rivi dellataan luedatasta
      if ($chpiiri != '' and (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA")) {
        $preq = "SELECT avainsana.selite, avainsana.selitetark
                 FROM avainsana
                 WHERE avainsana.selite='{$chpiiri}' AND avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='piiri'";
        $preres = pupe_query($preq);

        if (mysqli_num_rows($preres) != 1) {
          $virhe[$i] = t("Asiakaspiiri puuttuu tai sitä ei löydy! ");
        }
      }
    }

    if ((($chryhma == '' and $chtuoteno == '') or ($chryhma != '' and $chtuoteno != '')) and mysqli_field_name($result, $i) == 'ryhma') {
      $virhe[$i] = t("Sinun on annettava joko tuote tai ryhmä!");
    }

    if (mysqli_field_name($result, $i) == "minkpl") {
      $t[$i] = str_replace(",", ".", $t[$i]);
      $chminkpl = (int) $t[$i];
    }

    if (mysqli_field_name($result, $i) == "maxkpl") {
      $t[$i] = str_replace(",", ".", $t[$i]);
      $chmaxkpl = (int) $t[$i];
    }

    if (mysqli_field_name($result, $i) == "alkupvm") {
      $chalkupvm = $t[$i];

      if ($chalkupvm == '') {
        $chalkupvm = '0000-00-00';
      }

      // Ei tsekata turhaan kun rivi dellataan luedatasta
      if ($chalkupvm != '0000-00-00' and (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA")) {
        $pp = mb_substr($chalkupvm, 8, 2);
        $kk = mb_substr($chalkupvm, 5, 2);
        $vv = mb_substr($chalkupvm, 0, 4);

        if (!checkdate($kk, $pp, $vv)) {
          $virhe[$i] = t("Alkupäivämäärä virheellinen!");
        }
      }
    }

    if (mysqli_field_name($result, $i) == "loppupvm") {
      $chloppupvm = $t[$i];

      if ($chloppupvm == '') {
        $chloppupvm = '0000-00-00';
      }

      // Ei tsekata turhaan kun rivi dellataan luedatasta
      if ($chloppupvm != '0000-00-00' and (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA")) {
        $pp = mb_substr($chloppupvm, 8, 2);
        $kk = mb_substr($chloppupvm, 5, 2);
        $vv = mb_substr($chloppupvm, 0, 4);

        if (!checkdate($kk, $pp, $vv)) {
          $virhe[$i] = t("Loppupäivämäärä virheellinen!");
        }
      }
    }

    if (($chasiakas_ryhma != '' or $chytunnus != '' or $chpiiri != '' or $chsegmentti != '' or $chastunn != '') and ($chryhma != '' or $chtuoteno != '') and mysqli_field_name($result, $i) == 'tunnus' and (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA")) {

      $rajlask = 0;

      if ($chasiakas_ryhma != '') $rajlask++;
      if ($chytunnus != '') $rajlask++;
      if ($chpiiri != '') $rajlask++;
      if ($chsegmentti > 0) $rajlask++;
      if ($chastunn > 0) $rajlask++;

      if ($rajlask > 1 or $rajlask == 0) {
        $virhe[$i] = t("Valitse vain asiakas, ytunnus, asiakasryhmä, asiakassegmentti tai piiri!");
      }

      $and = '';

      if ($chasiakas_ryhma != '') $and .= " and asiakas_ryhma = '$chasiakas_ryhma'";
      if ($chytunnus != '')       $and .= " and ytunnus = '$chytunnus'";
      if ($chpiiri != '')         $and .= " and piiri = '$chpiiri'";
      if ($chsegmentti > 0)       $and .= " and asiakas_segmentti = '$chsegmentti'";
      if ($chastunn > 0)          $and .= " and asiakas = '$chastunn'";
      if ($chryhma != '')         $and .= " and ryhma = '$chryhma'";
      if ($chtuoteno != '')       $and .= " and tuoteno = '$chtuoteno'";
      if ($chvalkoodi != '')      $and .= " and valkoodi = '$chvalkoodi'";

      $chminkpl = (int) $chminkpl;
      $chmaxkpl = (int) $chmaxkpl;

      $aquery = "SELECT ytunnus
                 FROM asiakashinta
                 WHERE yhtio='$kukarow[yhtio]'
                 $and
                 and alkupvm   = '$chalkupvm'
                 and loppupvm  = '$chloppupvm'
                 and minkpl    = $chminkpl
                 and maxkpl    = $chmaxkpl
                 and tunnus   != '$trow[$i]'";
      $dsresult = pupe_query($aquery);

      if (mysqli_num_rows($dsresult) > 0) {
        // Pitää aina setata myös "tämän" kentän virhe, muuten luedata ei toimi
        $virhe[$chtuoteno_ind] = $virhe[$i] = t("VIRHE: Näillä tiedoilla on jo asiakashinta järjestelmässä!");
      }
    }
  }
}
