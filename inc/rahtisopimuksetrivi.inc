<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if (mysqli_field_name($result, $i) == "tunnus" or
  mysqli_field_name($result, $i) == "muutospvm" or
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
    if ($trow[$i] == $srow['selite'] or $srow['selite'] == $toimitustapa) {
      $sel = "selected";
    }
    $ulos .= "<option value='$srow[selite]' $sel>".t_tunnus_avainsanat($srow, "selite", "TOIMTAPAKV")."</option>";
  }
  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysqli_field_name($result, $i) == "ytunnus" and !isset($alasveto[$i]) and $ytunnus != "") {
  $ulos = "<td><input type = 'text' name = '$nimi' value = '$ytunnus' size='$size' maxlength='" . mysqli_field_len($result, $i) ."'>";
  $jatko = 0;
}

if (mysqli_field_name($result, $i) == "ytunnus" and $lukitse_avaimeen != "") {
  $tyyppi = 4;
}
elseif (mysqli_field_name($result, $i) == "ytunnus" and isset($alasveto[$i])) {
  $ulos = "<td>".$alasveto[$i]."</td>";
  $jatko = 0;
}

if (mysqli_field_name($result, $i) == "asiakas" and $lukitse_avaimeen != "") {
  $ulos = "<input type='hidden' name='$nimi' value='$lukitse_avaimeen'>";
  $tyyppi = 4;
  $jatko = 0;
}
elseif (mysqli_field_name($result, $i) == "asiakas" and isset($alasveto[$i])) {
  $ulos = "<td>".$alasveto[$i]."</td>";
  $jatko = 0;
}
elseif (mysqli_field_name($result, $i) == "asiakas") {
  if ((int) $trow[$i] > 0) {
    $query  = "SELECT *
               FROM asiakas
               WHERE yhtio='$kukarow[yhtio]' and tunnus='$trow[$i]'";
    $sresult = pupe_query($query);
    $srow = mysqli_fetch_array($sresult);
    $ssize = mb_strlen($trow[$i])+2;

    $ulos = "<td nowrap><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$ssize' maxlength='$maxsize'>";

    $ulos .= "<table><tr><td>$srow[nimi] $srow[nimitark] $srow[postitp]</td></tr><tr><td>$srow[toim_nimi] $srow[toim_nimitark] $srow[toim_postitp]</td></tr></table>";

    $ulos .= "</td>";

    $jatko = 0;
  }
  else {
    $ulos = "<td nowrap><input type = 'text' name = '$nimi' value = '' size='$size' maxlength='$maxsize'></td>";
    $jatko = 0;
  }
}
