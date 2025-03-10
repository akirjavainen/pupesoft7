<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if (!isset($apu)) {
  $apu = array();
}

// yhtiötä ei näytetä
if (mysqli_field_name($result, $i) == "yhtio") {
  $tyyppi = 0;
}

if (mysqli_field_name($result, $i) == "liitostunnus") {
  $query = "SELECT *
            FROM toimitustapa
            WHERE yhtio = '$kukarow[yhtio]'
            ORDER BY jarjestys, selite";
  $vresult = pupe_query($query);
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

  while ($vrow=mysqli_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow['tunnus']) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[tunnus]' $sel>".t_tunnus_avainsanat($vrow, "selite", "TOIMTAPAKV")."</option>";
  }
  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysqli_field_name($result, $i) == "kerailyn_aloitusaika" or mysqli_field_name($result, $i) == "viimeinen_tilausaika" or mysqli_field_name($result, $i) == "lahdon_kellonaika") {
  $trow[$i] = mb_substr($trow[$i], 0, 5);
}

if (mysqli_field_name($result, $i) == "lahdon_viikonpvm") {

  if ($trow[$i] == 0) $sel0 = "SELECTED";
  if ($trow[$i] == 1) $sel1 = "SELECTED";
  if ($trow[$i] == 2) $sel2 = "SELECTED";
  if ($trow[$i] == 3) $sel3 = "SELECTED";
  if ($trow[$i] == 4) $sel4 = "SELECTED";
  if ($trow[$i] == 5) $sel5 = "SELECTED";
  if ($trow[$i] == 6) $sel7 = "SELECTED";

  $ulos = "<td><select name='$nimi'>";
  $ulos .= "<option value='' >".t("Ei valittua päivää")."</option>";
  $ulos .= "<option value='1' $sel1>".t("Maanantai")."</option>";
  $ulos .= "<option value='2' $sel2>".t("Tiistai")."</option>";
  $ulos .= "<option value='3' $sel3>".t("Keskiviikko")."</option>";
  $ulos .= "<option value='4' $sel4>".t("Torstai")."</option>";
  $ulos .= "<option value='5' $sel5>".t("Perjantai")."</option>";
  $ulos .= "<option value='6' $sel6>".t("Lauantai")."</option>";
  $ulos .= "<option value='0' $sel7>".t("Sunnuntai")."</option>";

  $ulos .= "</select></td>";

  $jatko = 0;
}

if (mysqli_field_name($result, $i) == "aktiivi") {

  $sel1 = $sel2 = "";

  if ($trow[$i] == "E") {
    $sel2 = "SELECTED";
  }
  else {
    $sel1 = "SELECTED";
  }

  $ulos = "<td><select name='$nimi'>";
  $ulos .= "<option value='' $sel1>".t("Käytössä")."</option>";
  $ulos .= "<option value='E' $sel2>".t("Ei käytössä")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if (mysqli_field_name($result, $i) == "terminaalialue") {

  $sresult = t_avainsana('TERMINAALIALUE', '', " and selite != '' ");

  $ulos = "<td><select name='$nimi'>";
  $ulos .= "<option value=''>".t("Ei terminaalialuetta")."</option>";

  while ($row = mysqli_fetch_array($sresult)) {
    $sel = "";
    if ($row["selite"] == $trow[$i]) $sel = 'selected';
    $ulos .= "<option value='$row[selite]' $sel>$row[selite]</option>";
  }

  $ulos .= "</select></td>";

  $jatko = 0;
}

if (mysqli_field_name($result, $i) == "asiakasluokka") {
  $vresult = t_avainsana("ASIAKASLUOKKA");

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300)."><option value=' '>".t("Ei luokkaa")."</option>";

  while ($row = mysqli_fetch_array($vresult)) {
    $sel = $row["selitetark_3"] == $trow[$i] ? " selected" : "";

    $ulos .= "<option value='{$row['selitetark_3']}'{$sel}>{$row['selite']} ({$row['selitetark_3']})";
    if (trim($row['selitetark']) != "") $ulos .= " - {$row['selitetark']}";
    $ulos .= "</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysqli_field_name($result, $i) == "alkupvm") {
  $tyyppi = 1.5;
}

if (mysqli_field_name($result, $i) == "varasto") {

  $query = "SELECT tunnus, nimitys
            FROM varastopaikat
            WHERE yhtio = '{$kukarow['yhtio']}' AND tyyppi != 'P'
            ORDER BY tyyppi, nimitys";
  $varastores = pupe_query($query);

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300)."><option value=''>".t("Valitse")."</option>";

  while ($row = mysqli_fetch_assoc($varastores)) {
    $sel = $row["tunnus"] == $trow[$i] ? " selected" : "";

    $ulos .= "<option value='{$row['tunnus']}'{$sel}>{$row['nimitys']}</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}
