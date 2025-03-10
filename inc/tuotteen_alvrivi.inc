<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if (mysqli_field_name($result, $i) == "yhtio") {
  $tyyppi = 0;
}

if (mysqli_field_name($result, $i) == "tuoteno") {
 if ($lukitse_avaimeen != "") {
    $ulos = "<td><input type='hidden' name='$nimi' value='$lukitse_avaimeen'>$lukitse_avaimeen</td>";
    $jatko = 0;
  }
  $tuoteno = $trow[$i];
}

if (mysqli_field_name($result, $i) == "maa") {

  $maa=$trow[$i];

  $query = "SELECT distinct koodi, nimi
            FROM maat
            WHERE nimi != ''
            and koodi  != '{$yhtiorow["maakoodi"]}'
            ORDER BY koodi";
  $vresult = pupe_query($query);

  $ulos = "<td><select name='$nimi' onchange='submit();'>";

  $ulos .= "<option value = ''>".t("Valitse maa")."</option>";
  while ($vrow = mysqli_fetch_array($vresult)) {
    $sel = "";
    if ($trow[$i] == $vrow["koodi"]) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[koodi]' $sel>".t($vrow["nimi"])."</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysqli_field_name($result, $i) == "kustp") {
  $query = "SELECT tunnus, nimi
            FROM kustannuspaikka
            WHERE yhtio   = '$kukarow[yhtio]'
            and kaytossa != 'E'
            and tyyppi    = 'K'
            ORDER BY koodi+0, koodi, nimi";
  $vresult = pupe_query($query);
  $ulos = "<td><select name='$nimi'><option value=' '>".t("Ei kustannuspaikkaa")."</option>";

  while ($vrow=mysqli_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow[0]) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[0]' $sel>$vrow[1]</option>";
  }
  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysqli_field_name($result, $i) == "kohde") {
  $query = "SELECT tunnus, nimi
            FROM kustannuspaikka
            WHERE yhtio   = '$kukarow[yhtio]'
            and kaytossa != 'E'
            and tyyppi    = 'O'
            ORDER BY koodi+0, koodi, nimi";
  $vresult = pupe_query($query);
  $ulos = "<td><select name='$nimi'><option value=' '>".t("Ei kohdetta")."</option>";

  while ($vrow=mysqli_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow[0]) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[0]' $sel>$vrow[1]</option>";
  }
  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysqli_field_name($result, $i) == "projekti") {
  $query = "SELECT tunnus, nimi
            FROM kustannuspaikka
            WHERE yhtio   = '$kukarow[yhtio]'
            and kaytossa != 'E'
            and tyyppi    = 'P'
            ORDER BY koodi+0, koodi, nimi";
  $vresult = pupe_query($query);
  $ulos = "<td><select name='$nimi'><option value=' '>".t("Ei projektia")."</option>";

  while ($vrow=mysqli_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow[0]) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[0]' $sel>$vrow[1]</option>";
  }
  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysqli_field_name($result, $i) == "alv") {

  if ($maa!="") {
    //  Onko tämä viranomaistuote? Näille voidaan antaa alv vaikkei meillä ole toimipaikkaa ko. maassa
    $query = "SELECT tunnus
              FROM tuote
              WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno' and tuotetyyppi='B'";
    $vresult = pupe_query($query);
    if (mysqli_num_rows($vresult)==1) {
      $lisa="lista";
    }
    else {
      $lisa="";
    }

    $ulos = "<td>".alv_popup_oletus("t[$i]", $trow[$i], $maa, $lisa)."</td>";
  }
  else {
    $ulos = "<td>".t("Valitse ensin maa")."</td>";
  }

  $jatko = 0;
}
