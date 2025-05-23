<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

//yhtiötä ei näytetä
if (mysqli_field_name($result, $i) == "yhtio") {
  $tyyppi = 0;
}

if (mysqli_field_name($result, $i) == "laji") {

  $sel = array($trow[$i] => "SELECTED");

  $tem_laji = $trow[$i];

  $ulos = "<td><select name='$nimi' onchange='submit();'>";
  $ulos .= "<option value=''>".t("Valitse")."</option>";
  $ulos .= "<option value='unifaun_lisapalvelu' $sel[unifaun_lisapalvelu]>".t("Unifaun lisäpalvelu")."</option>";
  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysqli_field_name($result, $i) == "liitostunnus") {
  if ($lukitse_avaimeen != "") {
    $ulos = "<td><input type='hidden' name='$nimi' value='$lukitse_avaimeen'>$lukitse_avaimeen</td>";
    $jatko = 0;
  }
  elseif (isset($alasveto[$i])) {
    $ulos = "<td>".$alasveto[$i]."</td>";
    $jatko = 0;
  }
}

if (mysqli_field_name($result, $i) == "selite") {

  $tem_selite = $trow[$i];

  if ($tem_laji == 'unifaun_lisapalvelu') {

    $sel = array($trow[$i] => "SELECTED");
    //$tem_selite = $trow[$i];
    $ulos = "<td><select name='$nimi' onchange='submit();'>";
    $ulos .= "<option value=''>".t("Valitse")."</option>";
    $ulos .= "<option value='enot' $sel[enot]>".t("Unifaun ennakkoilmoitus")."</option>";
    $ulos .= "</select></td>";
    $jatko = 0;
  }
  else {
    $ulos = "<td><textarea rows=10 cols=50 name='$nimi'>{$trow[$i]}</textarea></td>";
    $jatko = 0;
  }
}

if (mysqli_field_name($result, $i) == "selitetark") {

  if ($tem_selite == 'enot') {
    $sel = array($trow[$i] => "SELECTED");
    $ulos = "<td><select name='$nimi' onchange='submit();'>";
    $ulos .= "<option value='e'>".t("Ei päällä")."</option>";
    $ulos .= "<option value='k' $sel[k]>".t("Päällä")."</option>";
    $ulos .= "</select></td>";
    $jatko = 0;
  }
  else {
    $ulos = "<td><textarea rows=10 cols=50 name='$nimi'>{$trow[$i]}</textarea></td>";
  }
}

if (mysqli_field_name($result, $i) == "kieli") {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

  foreach ($GLOBALS["sanakirja_kielet"] as $sanakirja_kieli => $sanakirja_kieli_nimi) {
    $sel = "";
    if ($trow[$i] == $sanakirja_kieli) {
      $sel = "selected";
    }
    elseif ($trow[$i] == "" and $sanakirja_kieli == $yhtiorow["kieli"]) {
      $sel = "selected";
    }

    $ulos .= "<option value='$sanakirja_kieli' $sel>".t($sanakirja_kieli_nimi)."</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}
