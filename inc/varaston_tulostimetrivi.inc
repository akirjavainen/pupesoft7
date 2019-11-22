<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys


if (mysqli_field_name($result, $i) == "tunnus" or
  mysqli_field_name($result, $i) == "yhtio") {
  $tyyppi = 0;
}

if (mb_substr(mysqli_field_name($result, $i), 0, 9)=='varasto') {

  $sel = array();
  $sel[$trow[$i]] = "selected";

  $ulos = "<td><select name='$nimi'>";

  $query = "SELECT *
            FROM varastopaikat
            WHERE yhtio = '$kukarow[yhtio]' AND tyyppi != 'P'
            ORDER BY tyyppi, nimitys";
  $kires = pupe_query($query);

  while ($kirow=mysqli_fetch_array($kires)) {
    if ($kirow["tunnus"]==$row[$i]) $select='SELECTED';
    else $select = '';

    $ulos .= "<option value='$kirow[tunnus]' ".$sel[$kirow["tunnus"]].">$kirow[nimitys]</option>";
  }

  $ulos .= "</select></td>";

  $jatko=0;
}

if (mysqli_field_name($result, $i)=='pakkaamo') {

  $sel = array();
  $sel[$trow[$i]] = "selected";

  $ulos = "<td><select name='$nimi'>";

  $query = "select distinct nimi from pakkaamo where yhtio='$kukarow[yhtio]'";
  $kires = pupe_query($query);

  $ulos .= "<option value=''>".t("Ei pakkaamoa")."</option>";

  while ($kirow=mysqli_fetch_array($kires)) {
    if ($kirow["nimi"]==$row[$i]) $select='SELECTED';
    else $select = '';

    $ulos .= "<option value='$kirow[nimi]' ".$sel[$kirow["nimi"]].">$kirow[nimi]</option>";
  }

  $ulos .= "</select></td>";

  $jatko=0;
}


if (mb_substr(mysqli_field_name($result, $i), 0, 9) == 'printteri') {

  $sel = array();
  $sel[$trow[$i]] = "selected";

  $ulos = "<td><select name='$nimi'>";

  $query = "SELECT *
            from kirjoittimet
            where yhtio  = '$kukarow[yhtio]'
            AND komento != 'EDI'
            ORDER BY kirjoitin";
  $kires = pupe_query($query);

  $ulos .= "<option value=''>".t("Ei kirjoitinta")."</option>";

  while ($kirow=mysqli_fetch_array($kires)) {
    if ($kirow["tunnus"]==$row[$i]) $select='SELECTED';
    else $select = '';

    $ulos .= "<option value='$kirow[tunnus]' ".$sel[$kirow["tunnus"]].">$kirow[kirjoitin]</option>";
  }

  $ulos .= "</select></td>";

  $jatko=0;
}
