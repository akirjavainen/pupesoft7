<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if (mysqli_field_name($result, $i) == "yhtio") {
  $tyyppi = 0;
}

if (mysqli_field_name($result, $i) == "kayttaja" or
  mysqli_field_name($result, $i) == "kaytettyaika") {
  $tyyppi = 2;
}
