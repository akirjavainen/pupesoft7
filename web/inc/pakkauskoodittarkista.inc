<?php

if (!function_exists("pakkauskoodittarkista")) {
  function pakkauskoodittarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {

    if (mysqli_field_name($result, $i) == "pakkaus" or mysqli_field_name($result, $i) == "rahdinkuljettaja" or mysqli_field_name($result, $i) == "koodi") {
      if (trim($t[$i]) == '') {
        $virhe[$i] .= t("Tietue ei saa olla tyhjä")."!";
      }
      else {
        $t[$i] = trim(sanitize_string($t[$i]));
      }
    }
  }
}
