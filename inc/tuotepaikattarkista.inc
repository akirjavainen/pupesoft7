<?php

if (!function_exists("tuotepaikattarkista")) {
  function tuotepaikattarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set;
    static $tmp_hyllyalue, $tmp_hyllynro, $tmp_hyllyvali, $tmp_hyllytaso, $tmp_tuotenoind;

    if (mysqli_field_name($result, $i) == "tuoteno") {
      $query = "SELECT *
                FROM tuote
                WHERE yhtio = '$kukarow[yhtio]'
                and tuoteno = '$t[$i]'";
      $chktuote = pupe_query($query);

      $tmp_tuotenoind = $i;

      if (mysqli_num_rows($chktuote) != 1) {
        $virhe[$i] = t("Tuotenumero ei löydy!");
      }
      else {
        $chktuoterow = mysqli_fetch_assoc($chktuote);

        if ($chktuoterow['ei_saldoa'] != "") {
          $virhe[$i] = t("Tuote on saldoton! Tuotepaikkaa ei voida lisätä.");
        }
      }
    }

    if (mysqli_field_name($result, $i) == "hyllyalue") {
      $tmp_hyllyalue = $t[$i];
    }

    if (mysqli_field_name($result, $i) == "hyllynro") {
      $tmp_hyllynro = $t[$i];
    }

    if (mysqli_field_name($result, $i) == "hyllyvali") {
      $tmp_hyllyvali = $t[$i];
    }

    if (mysqli_field_name($result, $i) == "hyllytaso") {
      $tmp_hyllytaso = $t[$i];
    }

    if (mysqli_field_name($result, $i) == "tunnus") {
      if (kuuluukovarastoon($tmp_hyllyalue, $tmp_hyllynro) == 0) {
        $virhe[$tmp_tuotenoind] = $virhe[$i] = t("VIRHE: Syötetty varastopaikka ei kuulu mihinkään varastoon");
      }
      else {

        if ($yhtiorow['kerayserat'] == 'K') {
          $tmp_hyllyalue = mb_strtoupper($tmp_hyllyalue);
          $tmp_hyllynro  = mb_strtoupper($tmp_hyllynro);
          $tmp_hyllyvali = mb_strtoupper($tmp_hyllyvali);
          $tmp_hyllytaso = mb_strtoupper($tmp_hyllytaso);

          if (!tarkista_varaston_hyllypaikka($tmp_hyllyalue, $tmp_hyllynro, $tmp_hyllyvali, $tmp_hyllytaso)) {
            $virhe[$tmp_tuotenoind] = $virhe[$i] = t("VIRHE: Syötettyä varastopaikkaa ei ole olemassa");
          }
        }
      }
    }
  }
}
