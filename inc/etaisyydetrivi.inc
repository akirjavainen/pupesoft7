<?php

$ulos = "";
$jatko = 1;   // oletetaan normaali käsittely
$tyyppi = 1;   // oletetaan rivin näkyvyys
if ($i == 0) {   //yhtiötä ei näytetä
  $tyyppi = 0;
}

if (mysqli_field_name($result, $i) == "varasto_postino") {
  $query = "SELECT DISTINCT postino, postitp
            FROM varastopaikat
            WHERE yhtio='$kukarow[yhtio]' AND postino != '' AND tyyppi != 'P'
            ORDER BY postino";
  $varastoresult = pupe_query($query);
  $ulos = "<td><select name='$nimi'>";
  $ulos .= "<option value = '' >".t("Ei varastoa")."</option>";

  while ($varastorow = mysqli_fetch_array($varastoresult)) {
    $sel = '';
    if ($trow[$i] == $varastorow[0]) {
      $sel = "selected";
    }
    $ulos .= "<option value='$varastorow[postino]' $sel>{$varastorow["postino"]} {$varastorow["postitp"]}</option>";
  }
  $ulos .= "</select></td>";
  $jatko = 0;
}
