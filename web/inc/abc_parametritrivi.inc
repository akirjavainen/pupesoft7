<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if (mysqli_field_name($result, $i) == "yhtio") {
  $tyyppi = 0;
}

if (mysqli_field_name($result, $i) == "tyyppi") {
  $sel = array();
  $sel[$trow[$i]] = "selected";

  $ulos = "<td><select name='$nimi'>";
  $ulos .= "<option value='TM' $sel[TM]>".t("Myynnin mukaan")."</option>";
  $ulos .= "<option value='TK' $sel[TK]>".t("Katteen mukaan")."</option>";
  $ulos .= "<option value='TP' $sel[TP]>".t("Kappaleiden mukaan")."</option>";
  $ulos .= "<option value='TR' $sel[TR]>".t("Rivimäärän mukaan")."</option>";
  $ulos .= "<option value='AM' $sel[AM]>".t("Asiakkaat Myynnin mukaan")."</option>";
  $ulos .= "<option value='AK' $sel[AK]>".t("Asiakkaat Katteen mukaan")."</option>";
  $ulos .= "<option value='AP' $sel[AP]>".t("Asiakkaat Kappaleiden mukaan")."</option>";
  $ulos .= "<option value='AR' $sel[AR]>".t("Asiakkaat Rivimäärän mukaan")."</option>";
  $ulos .= "</select></td>";

  $jatko=0;
}
