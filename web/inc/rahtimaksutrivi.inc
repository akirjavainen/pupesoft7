<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if (mysqli_field_name($result, $i) == "tunnus" or
  mysqli_field_name($result, $i) == "yhtio") {
  $tyyppi = 0;
}

if (mysqli_field_name($result, $i) == "toimitustapa") {
  $query = "SELECT *
            from toimitustapa
            WHERE yhtio='$kukarow[yhtio]'";
  $sresult = pupe_query($query);

  $ulos = "<td><select name='$nimi'>";

  while ($srow = mysqli_fetch_array($sresult)) {
    $sel = '';
    if ($trow[$i] == $srow['selite']) {
      $sel = "selected";
    }

    $ulos .= "<option value='$srow[selite]' $sel>".t_tunnus_avainsanat($srow, "selite", "TOIMTAPAKV")."</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}
