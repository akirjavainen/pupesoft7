<?php

if (!function_exists("yhtiotarkista")) {
  function yhtiotarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set, $ulosarray;

    if ((mysqli_field_name($result, $i) == "kotipaikka") or
      (mysqli_field_name($result, $i) == "nimi") or
      (mysqli_field_name($result, $i) == "kieli") or
      (mysqli_field_name($result, $i) == "ovttunnus") or
      (mysqli_field_name($result, $i) == "pankkinimi1") or
      (mysqli_field_name($result, $i) == "tilikausi_alku") or
      (mysqli_field_name($result, $i) == "tilikausi_loppu") or
      (mysqli_field_name($result, $i) == "ostoreskontrakausi_alku") or
      (mysqli_field_name($result, $i) == "ostoreskontrakausi_loppu") or
      (mysqli_field_name($result, $i) == "myyntireskontrakausi_alku") or
      (mysqli_field_name($result, $i) == "myyntireskontrakausi_loppu")) {

      if (trim($t[$i]) == '') {
        $virhe[$i] = t("Tieto puuttuu!");
      }
    }

    if (((mysqli_field_name($result, $i) == "kassa") or
        (mysqli_field_name($result, $i) == "pankkikortti") or
        (mysqli_field_name($result, $i) == "luottokortti") or
        (mysqli_field_name($result, $i) == "myynti") or
        (mysqli_field_name($result, $i) == "myynti_eu") or
        (mysqli_field_name($result, $i) == "myynti_ei_eu") or
        (mysqli_field_name($result, $i) == "myynti_marginaali") or
        (mysqli_field_name($result, $i) == "myynti_kaanteinen") or
        (mysqli_field_name($result, $i) == "osto_marginaali") or
        (mysqli_field_name($result, $i) == "myyntisaamiset") or
        (mysqli_field_name($result, $i) == "luottotappiot") or
        (mysqli_field_name($result, $i) == "ostovelat") or
        (mysqli_field_name($result, $i) == "valuuttaero") or
        (mysqli_field_name($result, $i) == "myynninvaluuttaero") or
        (mysqli_field_name($result, $i) == "matkalla_olevat") or
        (mysqli_field_name($result, $i) == "kassaale") or
        (mysqli_field_name($result, $i) == "myynninkassaale") or
        (mysqli_field_name($result, $i) == "muutkulut") or
        (mysqli_field_name($result, $i) == "pyoristys") or
        (mysqli_field_name($result, $i) == "varasto") or
        (mysqli_field_name($result, $i) == "varastonmuutos") or
        (mysqli_field_name($result, $i) == "alv") or
        (mysqli_field_name($result, $i) == "siirtosaamiset") or
        (mysqli_field_name($result, $i) == "siirtovelka") or
        (mysqli_field_name($result, $i) == "factoringsaamiset") or
        (mysqli_field_name($result, $i) == "selvittelytili") or
        (mysqli_field_name($result, $i) == "raaka_ainevarasto") or
        (mysqli_field_name($result, $i) == "raaka_ainevarastonmuutos") or
        (mysqli_field_name($result, $i) == "varastonmuutos_valmistuksesta") or
        (mysqli_field_name($result, $i) == "varastonmuutos_inventointi") or
        (mysqli_field_name($result, $i) == "varastonmuutos_epakurantti") or
        (mysqli_field_name($result, $i) == "konsernisaamiset") or
        (mysqli_field_name($result, $i) == "konsernivelat") or
        (mysqli_field_name($result, $i) == "konserniostovelat") or
        (mysqli_field_name($result, $i) == "konsernimyyntisaamiset")) and trim($t[$i]) == '') {

      $virhe[$i] = t("Tili puuttuu!");
    }

    if (((mysqli_field_name($result, $i) == "kassa") or
        (mysqli_field_name($result, $i) == "pankkikortti") or
        (mysqli_field_name($result, $i) == "luottokortti") or
        (mysqli_field_name($result, $i) == "kassaerotus") or
        (mysqli_field_name($result, $i) == "kateistilitys") or
        (mysqli_field_name($result, $i) == "myynti") or
        (mysqli_field_name($result, $i) == "myynti_eu") or
        (mysqli_field_name($result, $i) == "myynti_ei_eu") or
        (mysqli_field_name($result, $i) == "myynti_marginaali") or
        (mysqli_field_name($result, $i) == "myynti_kaanteinen") or
        (mysqli_field_name($result, $i) == "osto_marginaali") or
        (mysqli_field_name($result, $i) == "myyntisaamiset") or
        (mysqli_field_name($result, $i) == "luottotappiot") or
        (mysqli_field_name($result, $i) == "ostovelat") or
        (mysqli_field_name($result, $i) == "valuuttaero") or
        (mysqli_field_name($result, $i) == "myynninvaluuttaero") or
        (mysqli_field_name($result, $i) == "matkalla_olevat") or
        (mysqli_field_name($result, $i) == "kassaale") or
        (mysqli_field_name($result, $i) == "myynninkassaale") or
        (mysqli_field_name($result, $i) == "muutkulut") or
        (mysqli_field_name($result, $i) == "pyoristys") or
        (mysqli_field_name($result, $i) == "varasto") or
        (mysqli_field_name($result, $i) == "varastonmuutos") or
        (mysqli_field_name($result, $i) == "alv") or
        (mysqli_field_name($result, $i) == "siirtosaamiset") or
        (mysqli_field_name($result, $i) == "siirtovelka") or
        (mysqli_field_name($result, $i) == "factoringsaamiset") or
        (mysqli_field_name($result, $i) == "selvittelytili") or
        (mysqli_field_name($result, $i) == "raaka_ainevarasto") or
        (mysqli_field_name($result, $i) == "raaka_ainevarastonmuutos") or
        (mysqli_field_name($result, $i) == "varastonmuutos_valmistuksesta") or
        (mysqli_field_name($result, $i) == "varastonmuutos_inventointi") or
        (mysqli_field_name($result, $i) == "varastonmuutos_epakurantti") or
        (mysqli_field_name($result, $i) == "konsernisaamiset") or
        (mysqli_field_name($result, $i) == "konsernivelat") or
        (mysqli_field_name($result, $i) == "konserniostovelat") or
        (mysqli_field_name($result, $i) == "konsernimyyntisaamiset")) and trim($t[$i]) != '') {

      $query = "SELECT nimi
                FROM tili
                WHERE yhtio='$kukarow[yhtio]' and tilino = '$t[$i]'";
      $sresult = pupe_query($query);

      if (mysqli_num_rows($sresult) != 1) {
        //Olisiko siellä nimi
        if ($t[$i] != '') {
          $query = "SELECT nimi, tilino
                    FROM tili
                    WHERE yhtio = '$kukarow[yhtio]' and nimi like '%$t[$i]%'
                    ORDER BY tilino";
          $sresult = pupe_query($query);

          if (mysqli_num_rows($sresult) > 0) {
            $ulosarray[$i] = "<td><select name='t[$i]'><option value = '' $sel>".t("Ei valintaa")."</option>";

            while ($vrow=mysqli_fetch_array($sresult)) {
              $sel="";
              if ($trow[$i] == $vrow[0]) {
                $sel = "selected";
              }
              $ulosarray[$i] .= "<option value = '$vrow[tilino]' $sel>$vrow[tilino] $vrow[nimi]</option>";
            }
            $ulosarray[$i] .= "</select></td>";
            $virhe[$i] = t("Valitse tili");
          }
        }
        if ($virhe[$i] == '') $virhe[$i] = t("Tiliä ei löydy");
      }
    }

    if (mysqli_field_name($result, $i) == "ean" and mb_strlen($t[$i]) > 9) {
      $virhe[$i] = t("Ean pitää olla max 9 merkkiä pitkä!");
    }

    if (mysqli_field_name($result, $i) == "ytunnus" and mb_strtoupper($yhtiorow["maa"]) == "FI") {
      $ytunnus = trim($t[$i]);
      require "tarkistaytunnus.inc";

      if ($ok==0) {
        $virhe[$i] = t("Virheellinen y-tunnus!")." $t[$i] -> $ytunnus";
      }
      else {
        $t[$i] = $ytunnus;
      }
    }

    if (mysqli_field_name($result, $i) == "pankkiiban1" or mysqli_field_name($result, $i) == "pankkiiban2" or mysqli_field_name($result, $i) == "pankkiiban3") {
      if (trim($t[$i]) != "") {

        $pankkitili = tarkista_iban($t[$i]);

        if ($pankkitili == '') {
          $virhe[$i] = t("Pankkitili on virheellinen");
        }
      }
    }

    if (mysqli_field_name($result, $i) == "pankkitili1" or mysqli_field_name($result, $i) == "pankkitili2" or mysqli_field_name($result, $i) == "pankkitili3") {

      if (mb_strtoupper($yhtiorow['maa']) == "FI" and trim($t[$i]) != "") {

        $pankkitili = $t[$i];

        require 'pankkitilinoikeellisuus.php';

        if ($pankkitili == '') {
          $pankkitili = tarkista_iban($t[$i]);
          if ($pankkitili == '') {
            $virhe[$i] = t("Pankkitili on virheellinen");
          }
        }
      }
    }
  }
}
