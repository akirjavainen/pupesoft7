<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if (mysqli_field_name($result, $i) == "tunnus" or
  mysqli_field_name($result, $i) == "yhtio") {
  $tyyppi = 0;
}

if (mysqli_field_name($result, $i) == "hyllyalue") {
  $ulos = "<td>";

  $ulos .= hyllyalue($nimi, $trow[$i]);

  $ulos .= "</td>";
  $jatko = 0;
}

if (mysqli_field_name($result, $i) == "reservipaikka") {

  $sel = array_fill_keys(array($trow[$i]), " selected") + array('E' => '', 'K' => '');

  $ulos = "<td><select name='{$nimi}'>";
  $ulos .= "<option value='E'{$sel['E']}>".t("Ei")."</option>";
  $ulos .= "<option value='K'{$sel['K']}>".t("Kyllä")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if (mysqli_field_name($result, $i) == "keraysvyohyke") {
  $ulos = "<td><select name='{$nimi}'>";
  $ulos .= "<option value=''>".t("Valitse")."</option>";

  $query = "SELECT tunnus, nimitys FROM keraysvyohyke WHERE yhtio = '{$kukarow['yhtio']}' AND nimitys != ''";
  $keraysvyohyke_result = pupe_query($query);

  while ($keraysvyohyke_row = mysqli_fetch_assoc($keraysvyohyke_result)) {

    $sel = $keraysvyohyke_row['tunnus'] == $trow[$i] ? ' selected' : '';

    $ulos .= "<option value='{$keraysvyohyke_row['tunnus']}'{$sel}>{$keraysvyohyke_row['nimitys']}</option>";
  }

  $ulos .= "</td>";
  $jatko = 0;
}
