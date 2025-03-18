<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if (mysqli_field_name($result, $i) == "tunnus" or
  mysqli_field_name($result, $i) == "yhtio") {
  $tyyppi = 0;
}

if (mysqli_field_type($result, $i) == "blob") {
  $ulos = "<td><textarea cols='50' rows='5' name='$nimi'>$trow[$i]</textarea></td>";
  $jatko = 0;
}

if (mysqli_field_name($result, $i) == "asiakas" and $lukitse_avaimeen != "") {
  // Jos tämä on liitetty tunnuksella:
  if ($trow[$i] > 0) {
    $ulos = "<input type='hidden' name='$nimi' value='$trow[$i]'>";
  }
  // Jos tää on uusi itemi
  elseif ((int) $tunnus == 0) {
    $ulos = "<input type='hidden' name='$nimi' value='$lukitse_avaimeen'>";
  }

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

    $ulos = "<td nowrap><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$ssize' maxlength='$maxsize'> <table><tr><td>$srow[nimi] $srow[nimitark]</td></tr><tr><td>$srow[toim_nimi] $srow[toim_nimitark]</td></tr></table></td>";
    $jatko = 0;
  }
  else {
    if ($trow[$i] == 0) $trow[$i] = "";

    // Ei maxlength:iä koska silloin hakutoiminto ei välttämättä toimi
    $ulos = "<td nowrap><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$size'></td>";
    $jatko = 0;
  }
}
if (mysqli_field_name($result, $i) == "tyyppi") {
  $preq = "SELECT avainsana.selite tyyppi, avainsana.selitetark selitetark
           FROM avainsana
           WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='kohde_tyyppi'";
  $preres = pupe_query($preq);

  if (mysqli_num_rows($preres) > 0) {
    $ulos = "<td><select id='tyyppi' name='$nimi'>";
    $ulos .= "<option value = ''>".t("Ei tyyppiä")."</option>";

    while ($srow = mysqli_fetch_array($preres)) {
      $sel = (mb_strtoupper($trow[$i]) == mb_strtoupper($srow["tyyppi"])) ? "selected" : "";
      $ulos .= "<option value='$srow[tyyppi]' $sel>$srow[selitetark]</option>";
    }

    $ulos .= "</select></td>\n";
    $jatko = 0;
  }
  else {
    $ulos = "";
    $tyyppi = 0;
    $jatko = 0;
  }
}
