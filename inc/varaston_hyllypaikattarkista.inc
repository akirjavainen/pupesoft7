<?php

if (!function_exists("varaston_hyllypaikattarkista")) {
  function varaston_hyllypaikattarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow;

    static $chk_hyllyalue, $chk_hyllynro, $chk_hyllyvali, $chk_hyllytaso;

    if (mysqli_field_name($result, $i) != 'laatija' and
      mysqli_field_name($result, $i) != 'luontiaika' and
      mysqli_field_name($result, $i) != 'muutospvm' and
      mysqli_field_name($result, $i) != 'muuttaja' and
      mysqli_field_name($result, $i) != 'tunnus') {

      if (trim($t[$i]) != '') {
        $t[$i] = sanitize_string(trim($t[$i]));

        if (mysqli_field_name($result, $i) == "hyllyalue" or
          mysqli_field_name($result, $i) == "hyllynro" or
          mysqli_field_name($result, $i) == "hyllyvali" or
          mysqli_field_name($result, $i) == "hyllytaso") {

          ${'chk_'.mysqli_field_name($result, $i)} = $t[$i];
        }

        if (mysqli_field_name($result, $i) == "hyllytaso" and $chk_hyllyalue != '' and $chk_hyllynro != '' and $chk_hyllyvali != '' and $chk_hyllytaso != '') {

          $query = "SELECT tunnus
                    FROM varaston_hyllypaikat
                    WHERE yhtio    = '{$kukarow['yhtio']}'
                    AND hyllyalue  = '{$chk_hyllyalue}'
                    AND hyllynro   = '{$chk_hyllynro}'
                    AND hyllyvali  = '{$chk_hyllyvali}'
                    AND hyllytaso  = '{$chk_hyllytaso}'
                    and tunnus    != '{$tunnus}'";
          $chk_res = pupe_query($query);

          if (mysqli_num_rows($chk_res) != 0) {
            $virhe[$i] = t("Syötetty varastopaikka on jo tietokannassa")."!";
          }
        }
      }
      else {
        $virhe[$i] = ucwords(mysqli_field_name($result, $i)).' '.t("ei saa olla tyhjä")."!";
      }
    }
  }
}
