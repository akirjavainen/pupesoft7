<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if ($i == 0) { //yhtiötä ei näytetä
  $tyyppi = 0;
}

if (mysqli_field_name($result, $i) == "toimipaikka") {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300)." onchange='submit();'>";

  $query = "SELECT *
            FROM yhtion_toimipaikat
            WHERE yhtio    = '{$kukarow['yhtio']}'
            AND vat_numero = ''
            ORDER BY nimi";
  $vares = pupe_query($query);

  while ($varow = mysqli_fetch_assoc($vares)) {
    $sel = '';
    if ($varow['tunnus'] == $trow[$i]) $sel = 'selected';
    $ulos .= "<option value='{$varow['tunnus']}' {$sel}>{$varow['ovtlisa']} {$varow['nimi']}</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysqli_field_name($result, $i) == "parametri") {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300)." onchange='submit();'>";

  $query  = "SHOW columns FROM yhtion_parametrit";
  $fields =  pupe_query($query);

  // ihan kaikkia parametrejä ei voi sallia toimipaikan taakse
  $poikkeukset = array(
    "myyntitilauksen_toimipaikka",
    "toimipaikkakasittely",
  );

  while ($apurow = mysqli_fetch_array($fields)) {

    $sel = "";

    if (in_array($apurow[0], $poikkeukset)) continue;

    if ($trow[$i] == $apurow[0]) {
      $sel     = "SELECTED";
      $parametri  = $trow[$i];
    }

    if (!in_array($apurow[0], array("tunnus", "yhtio", "laatija", "luontiaika", "muuttaja", "muutospvm"))) {
      $ulos .= "<option value='$apurow[0]' $sel>".t($apurow[0])."</option>";
    }
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysqli_field_name($result, $i) == "arvo") {
  $ytp_fieldname = $parametri;

  require "yhtion_parametritrivi.inc";
}
