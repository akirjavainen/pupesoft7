<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if ($i == 0) { //yhtiötä ei näytetä
  $tyyppi = 0;
}

if (mysqli_field_name($result, $i) == "tilikausi_alku" or
  mysqli_field_name($result, $i) == "tilikausi_loppu" or
  mysqli_field_name($result, $i) == "ostoreskontrakausi_alku" or
  mysqli_field_name($result, $i) == "ostoreskontrakausi_loppu" or
  mysqli_field_name($result, $i) == "myyntireskontrakausi_alku" or
  mysqli_field_name($result, $i) == "myyntireskontrakausi_loppu") {
  $tyyppi = 1.5;
}

if (mysqli_field_name($result, $i) == "kieli") {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

  foreach ($GLOBALS["sanakirja_kielet"] as $sanakirja_kieli => $sanakirja_kieli_nimi) {
    $sel = "";
    if ($trow[$i] == $sanakirja_kieli) {
      $sel = "selected";
    }

    $ulos .= "<option value='$sanakirja_kieli' $sel>".t($sanakirja_kieli_nimi)."</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysqli_field_name($result, $i) == "maa" or mysqli_field_name($result, $i) == "laskutus_maa") {
  $query = "SELECT distinct koodi, nimi
            FROM maat
            WHERE nimi != ''
            ORDER BY koodi";
  $vresult = pupe_query($query);
  $ulos = "<td><select name='$nimi'>";

  while ($vrow = mysqli_fetch_array($vresult)) {
    $sel="";
    if (mb_strtoupper($trow[$i]) == mb_strtoupper($vrow[0])) {
      $sel = "selected";
    }
    $ulos .= "<option value = '".mb_strtoupper($vrow[0])."' $sel>".t($vrow[1])."</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysqli_field_name($result, $i) == "valkoodi") { //valuutta
  $query = "SELECT nimi, tunnus
            FROM valuu
            WHERE yhtio = '$kukarow[yhtio]'
            ORDER BY jarjestys";
  $vresult = pupe_query($query);
  $ulos = "<td><select name='$nimi'>";

  while ($vrow=mysqli_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow['nimi']) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[nimi]' $sel>$vrow[nimi]";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if ((mysqli_field_name($result, $i) == "kassa") or
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
  (mysqli_field_name($result, $i) == "ostolasku_kotimaa_kulu") or
  (mysqli_field_name($result, $i) == "ostolasku_kotimaa_rahti") or
  (mysqli_field_name($result, $i) == "ostolasku_kotimaa_vaihto_omaisuus") or
  (mysqli_field_name($result, $i) == "ostolasku_kotimaa_raaka_aine") or
  (mysqli_field_name($result, $i) == "ostolasku_eu_kulu") or
  (mysqli_field_name($result, $i) == "ostolasku_eu_rahti") or
  (mysqli_field_name($result, $i) == "ostolasku_eu_vaihto_omaisuus") or
  (mysqli_field_name($result, $i) == "ostolasku_eu_raaka_aine") or
  (mysqli_field_name($result, $i) == "ostolasku_ei_eu_kulu") or
  (mysqli_field_name($result, $i) == "ostolasku_ei_eu_rahti") or
  (mysqli_field_name($result, $i) == "ostolasku_ei_eu_vaihto_omaisuus") or
  (mysqli_field_name($result, $i) == "ostolasku_ei_eu_raaka_aine") or
  (mysqli_field_name($result, $i) == "ennakkolaskun_asiakasvarasto") or
  (mysqli_field_name($result, $i) == "varastonmuutos_rahti") or
  (mysqli_field_name($result, $i) == "raaka_ainevarasto") or
  (mysqli_field_name($result, $i) == "raaka_ainevarastonmuutos") or
  (mysqli_field_name($result, $i) == "varastonmuutos_valmistuksesta") or
  (mysqli_field_name($result, $i) == "varastonmuutos_inventointi") or
  (mysqli_field_name($result, $i) == "varastonmuutos_epakurantti") or
  (mysqli_field_name($result, $i) == "konsernisaamiset") or
  (mysqli_field_name($result, $i) == "konsernivelat") or
  (mysqli_field_name($result, $i) == "konserniostovelat") or
  (mysqli_field_name($result, $i) == "konsernimyyntisaamiset") or
  (mysqli_field_name($result, $i) == "osto_rahti") or
  (mysqli_field_name($result, $i) == "osto_kulu") or
  (mysqli_field_name($result, $i) == "osto_rivi_kulu") or
  (mysqli_field_name($result, $i) == "tilino_triang")) {

  if ($ulosarray[$i] != '') {
    $ulos=$ulosarray[$i];
    $jatko = 0;
  }
  else {
    $ulos = "<td nowrap valign='top'>";

    if ($trow[$i] != "") {
      $ulos .= "<input type = 'hidden' name = '$nimi' value = '$trow[$i]'>$trow[$i] - ";
    }
    else {
      $ulos .= "<input type = 'text' name = '$nimi' value = '$trow[$i]' size='$size' maxlength='" . mysqli_field_len($result, $i) ."'>";
    }

    $query = "SELECT nimi
              FROM tili
              WHERE yhtio='$kukarow[yhtio]' and tilino = '$trow[$i]'";
    $vresult = pupe_query($query);

    if (mysqli_num_rows($vresult) == 1) {
      $vrow = mysqli_fetch_array($vresult);
      $ulos .= " $vrow[nimi]";
    }
    else {
      $query = "SELECT tilino, nimi
                FROM tili
                WHERE yhtio='$kukarow[yhtio]'";

      if (mysqli_field_name($result, $i) == "kassa")                 $query .= " and nimi regexp '(kassa|k.teis)' ";
      elseif (mysqli_field_name($result, $i) == "pankkikortti")           $query .= " and nimi regexp 'pankki.*kortti' ";
      elseif (mysqli_field_name($result, $i) == "luottokortti")           $query .= " and nimi regexp 'luotto.*kortti' ";
      elseif (mysqli_field_name($result, $i) == "kassaerotus")           $query .= " and nimi regexp '(kassa|k.teis)' and nimi regexp 'ero' ";
      elseif (mysqli_field_name($result, $i) == "kateistilitys")           $query .= " and nimi regexp '(kassa|k.teis)' and nimi regexp 'vara' ";
      elseif (mysqli_field_name($result, $i) == "myynti")               $query .= " and nimi regexp 'myynti' ";
      elseif (mysqli_field_name($result, $i) == "myynti_eu")             $query .= " and nimi regexp 'yhteis' and nimi regexp 'myyn' ";
      elseif (mysqli_field_name($result, $i) == "myynti_ei_eu")           $query .= " and nimi regexp 'yhteis' and nimi regexp 'myyn' ";
      elseif (mysqli_field_name($result, $i) == "myynti_marginaali")         $query .= " and nimi regexp '(k.ytet|taide)' and nimi regexp 'myyn' ";
      elseif (mysqli_field_name($result, $i) == "myynti_kaanteinen")         $query .= " and nimi regexp 'nteinen' and nimi regexp 'myyn' ";
      elseif (mysqli_field_name($result, $i) == "osto_marginaali")         $query .= " and nimi regexp '(k.ytet|taide)' and nimi regexp '(osto|hankin)' ";
      elseif (mysqli_field_name($result, $i) == "myyntisaamiset")           $query .= " and nimi regexp 'myyn' and nimi regexp 'saam' ";
      elseif (mysqli_field_name($result, $i) == "luottotappiot")           $query .= " and nimi regexp 'luotto' and nimi regexp 'tapp' ";
      elseif (mysqli_field_name($result, $i) == "ostovelat")             $query .= " and nimi regexp '(velka|velat)' and nimi regexp '(osto|hankin)' ";
      elseif (mysqli_field_name($result, $i) == "valuuttaero")           $query .= " and nimi regexp 'valuu' and nimi regexp 'ero' ";
      elseif (mysqli_field_name($result, $i) == "myynninvaluuttaero")         $query .= " and nimi regexp 'valuu' and nimi regexp 'ero' ";
      elseif (mysqli_field_name($result, $i) == "matkalla_olevat")         $query .= " and nimi regexp 'matkalla' ";
      elseif (mysqli_field_name($result, $i) == "kassaale")             $query .= " and nimi regexp '(kassa|k.teis)' and nimi regexp 'ale' ";
      elseif (mysqli_field_name($result, $i) == "myynninkassaale")         $query .= " and nimi regexp '(kassa|k.teis)' and nimi regexp 'ale' ";
      elseif (mysqli_field_name($result, $i) == "muutkulut")             $query .= " and nimi regexp 'muut.*kulut' ";
      elseif (mysqli_field_name($result, $i) == "pyoristys")             $query .= " and nimi regexp 'py.rist' ";
      elseif (mysqli_field_name($result, $i) == "varasto")             $query .= " and nimi regexp '(varasto|tavarat)' ";
      elseif (mysqli_field_name($result, $i) == "varastonmuutos")           $query .= " and nimi regexp 'varast.*muut' ";
      elseif (mysqli_field_name($result, $i) == "alv")               $query .= " and nimi regexp '(alv|arvonlis)' and nimi regexp '((velka|velat)|saam)' ";
      elseif (mysqli_field_name($result, $i) == "siirtosaamiset")           $query .= " and nimi regexp 'siirto' and nimi regexp 'saam' ";
      elseif (mysqli_field_name($result, $i) == "siirtovelka")           $query .= " and nimi regexp 'siirto' and nimi regexp '(velka|velat)' ";
      elseif (mysqli_field_name($result, $i) == "factoringsaamiset")         $query .= " and nimi regexp 'factoring' and nimi regexp 'saam' ";
      elseif (mysqli_field_name($result, $i) == "selvittelytili")           $query .= " and nimi regexp 'selvittely' ";
      elseif (mysqli_field_name($result, $i) == "raaka_ainevarasto")         $query .= " and nimi regexp 'raak.*aine' and nimi regexp '(varasto|tavarat)' ";
      elseif (mysqli_field_name($result, $i) == "raaka_ainevarastonmuutos")     $query .= " and nimi regexp 'raak.*aine' and nimi regexp 'muut' ";
      elseif (mysqli_field_name($result, $i) == "varastonmuutos_valmistuksesta")   $query .= " and nimi regexp 'kosto.*(velka|velat)' ";
      elseif (mysqli_field_name($result, $i) == "konsernisaamiset")         $query .= " and nimi regexp 'konserni.*saam' ";
      elseif (mysqli_field_name($result, $i) == "konsernivelat")           $query .= " and nimi regexp 'kons' and nimi regexp '(velka|velat)'  ";
      elseif (mysqli_field_name($result, $i) == "konserniostovelat")         $query .= " and nimi regexp 'kons' and nimi regexp '(osto|hankin)' and nimi regexp '(velka|velat)'  ";
      elseif (mysqli_field_name($result, $i) == "konsernimyyntisaamiset")       $query .= " and nimi regexp 'kons' and nimi regexp 'saam' ";
      elseif (mysqli_field_name($result, $i) == "ennakkolaskun_asiakasvarasto")       $query .= " and nimi regexp 'enna' and nimi regexp 'asiakas' ";
      elseif (mysqli_field_name($result, $i) == "varastonmuutos_rahti")       $query .= " and nimi regexp 'var' and nimi regexp 'muut' and nimi regexp 'rahti' ";
      else $query = "";

      if ($query != "") {

        $query .= " ORDER BY tilino";

        $vresult = pupe_query($query);

        if (mysqli_num_rows($vresult) > 0) {
          $ulos .= "<table style='display:inline;'>";

          while ($vrow = mysqli_fetch_array($vresult)) {
            $ulos .= "<tr><td>$vrow[tilino]</td><td>$vrow[nimi]</td></tr>";
          }

          $ulos .= "</table>";
        }
      }
    }

    $ulos .= "</td>";
    $jatko = 0;
  }
}

if (mysqli_field_name($result, $i) == "myynti_projekti" or mysqli_field_name($result, $i) == "myynti_kohde" or mysqli_field_name($result, $i) == "myynti_kustp") {

  if (mysqli_field_name($result, $i) == "myynti_projekti")  $kptyyppi = "P";
  if (mysqli_field_name($result, $i) == "myynti_kohde")  $kptyyppi = "O";
  if (mysqli_field_name($result, $i) == "myynti_kustp")  $kptyyppi = "K";

  $tpque = "SELECT tunnus, nimi, koodi
            from kustannuspaikka
            where yhtio   = '$kukarow[yhtio]'
            and tyyppi    = '$kptyyppi'
            and kaytossa != 'E'
            ORDER BY koodi+0, koodi, nimi";
  $vresult = pupe_query($tpque);

  $sel="";
  if ($trow[$i] == "") {
    $sel = "selected";
  }
  $ulos = "<td><select name='$nimi'><option value='' $sel>".t("Ei valintaa")."</option>";

  while ($vrow = mysqli_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow["tunnus"]) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[tunnus]' $sel>$vrow[koodi] $vrow[nimi]</option>";
  }
  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysqli_field_name($result, $i) == "tilikauden_tulos") {

  $tpque = "SELECT tunnus, tilino, nimi
            from tili
            where yhtio = '$kukarow[yhtio]'
            and LEFT(tilino, 1) = BINARY '2'";
  $vresult = pupe_query($tpque);

  $ulos = "<td><select name='$nimi'><option value=''>".t("Ei valintaa")."</option>";

  while ($vrow = mysqli_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow["tunnus"]) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[tunnus]' $sel>$vrow[tilino] $vrow[nimi]</option>";
  }
  $ulos .= "</select></td>";
  $jatko = 0;
}
