<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if (mysqli_field_name($result, $i) == "tunnus" or
  mysqli_field_name($result, $i) == "yhtio") {
  $tyyppi = 0;
}

if (mysqli_field_type($result, $i) == "blob") {
  $ulos = "<td><textarea cols='50' rows='5' name='{$nimi}'>{$trow[$i]}</textarea></td>";
  $jatko = 0;
}
