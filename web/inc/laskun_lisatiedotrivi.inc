<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely

if (mysqli_field_name($result, $i) == "laskutus_nimi" or
  mysqli_field_name($result, $i) == "laskutus_nimitark" or
  mysqli_field_name($result, $i) == "laskutus_osoite" or
  mysqli_field_name($result, $i) == "laskutus_osoitetark" or
  mysqli_field_name($result, $i) == "laskutus_postino" or
  mysqli_field_name($result, $i) == "laskutus_postitp") {

  $tyyppi = 1;
}
elseif (mysqli_field_name($result, $i) == "laskutus_maa") {

  $query = "SELECT distinct koodi, nimi
            FROM maat
            WHERE nimi != ''
            ORDER BY koodi";
  $vresult = pupe_query($query);
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

  $ulos .= "<option value = '' ></option>";

  while ($vrow = mysqli_fetch_array($vresult)) {
    $sel="";
    if (mb_strtoupper($trow[$i]) == mb_strtoupper($vrow[0])) {
      $sel = "selected";
    }
    elseif ($trow[$i] == "" and mb_strtoupper($vrow[0]) == mb_strtoupper($yhtiorow["maa"])) {
      $sel = "selected";
    }
    $ulos .= "<option value = '".mb_strtoupper($vrow[0])."' $sel>".t($vrow[1])."</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}
else {
  $tyyppi = 0;
}
