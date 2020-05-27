<?php

// Oletus selvittelytili
$tiliotesaantotilino = $yhtiorow['selvittelytili'];
$tiliointisaantorow['tilino'] = $yhtiorow['selvittelytili'];
$tiliointisaantorow['tilino2'] = 0;
$tiliointisaantorow['kustp'] = 0;

$loydettiinko_sopiva_tili = '';

// Jos pankkitilillä on oletus selvittelytili käytetään sitä
if ($yritirow["oletus_selvittelytili"] != "") {
  $tiliotesaantotilino = $yritirow["oletus_selvittelytili"];
  $tiliointisaantorow['tilino'] = $yritirow["oletus_selvittelytili"];
}

if (!empty($tiliotesaantotunnus)) {
  $query = "SELECT * 
            FROM tiliotesaanto
            WHERE yhtio = '$yritirow[yhtio]' 
            and tunnus = $tiliotesaantotunnus";
  $tiliotesaantoresult = pupe_query($query);
  
  if (mysqli_num_rows($tiliotesaantoresult) == 1) {
    $tiliointisaantorow = mysqli_fetch_array($tiliotesaantoresult);
    $tiliotesaantotilino = $tiliointisaantorow['tilino'];
  }
}
else {
  $query = "SELECT * 
            FROM tiliotesaanto
            WHERE yhtio = '$yritirow[yhtio]' and
            erittely    = '' and
            pankkitili  = '$pankkitilino' and
            koodi       = '$koodi' and
            koodiselite = '$koodiselite' and
            nimitieto   = '$maksaa' and
            locate(selite, '$vientiselite') > 0";
  $tiliotesaantoresult = pupe_query($query);

  // Jotain löytyy
  if (mysqli_num_rows($tiliotesaantoresult) > 0) {
    if (mysqli_num_rows($tiliotesaantoresult) == 1) {
      $tiliointisaantorow = mysqli_fetch_array($tiliotesaantoresult);
      $tiliotesaantotilino = $tiliointisaantorow['tilino'];
    }
    else {
      echo t("Monta sopivaa sääntöä, käytetään oletustiliä")."!";
    }
  }
  else {
    // Ei löytynyt höllätään
    $query = "SELECT * 
              FROM tiliotesaanto
              WHERE yhtio = '$yritirow[yhtio]' and
              erittely    = '' and
              pankkitili  = '$pankkitilino' and
              koodi       = '$koodi' and
              koodiselite = '$koodiselite' and
              nimitieto   = '$maksaa' and
              selite      = ''";
    $tiliotesaantoresult = pupe_query($query);

    // Jotain löytyy
    if (mysqli_num_rows($tiliotesaantoresult) > 0) {
      if (mysqli_num_rows($tiliotesaantoresult) == 1) {
        $tiliointisaantorow = mysqli_fetch_array($tiliotesaantoresult);
        $tiliotesaantotilino = $tiliointisaantorow['tilino'];
      }
      else {
        echo t("Monta sopivaa sääntöä, käytetään oletustiliä")."!";
      }
    }
    else {
      $query = "SELECT * 
                FROM tiliotesaanto
                WHERE yhtio = '$yritirow[yhtio]' and
                erittely    = '' and
                pankkitili  = '$pankkitilino' and
                koodi       = '$koodi' and
                koodiselite = '$koodiselite' and
                nimitieto   = '' and
                selite      = ''";
      $tiliotesaantoresult = pupe_query($query);

      // Jotain löytyy
      if (mysqli_num_rows($tiliotesaantoresult) > 0) {
        if (mysqli_num_rows($tiliotesaantoresult) == 1) {
          $tiliointisaantorow = mysqli_fetch_array($tiliotesaantoresult);
          $tiliotesaantotilino = $tiliointisaantorow['tilino'];
        }
        else {
          echo t("Monta sopivaa sääntöä, käytetään oletustiliä")."!";
        }
      }
      else {
        $query = "SELECT * 
                  FROM tiliotesaanto
                  WHERE yhtio = '$yritirow[yhtio]' and
                  erittely    = '' and
                  pankkitili  = '$pankkitilino' and
                  koodi       = '$koodi' and
                  koodiselite = '' and
                  nimitieto   = '' and
                  selite      = ''";
        $tiliotesaantoresult = pupe_query($query);

        // Jotain löytyy
        if (mysqli_num_rows($tiliotesaantoresult) > 0) {
          if (mysqli_num_rows($tiliotesaantoresult) == 1) {
            $tiliointisaantorow = mysqli_fetch_array($tiliotesaantoresult);
            $tiliotesaantotilino = $tiliointisaantorow['tilino'];
          }
          else {
            echo t("Monta sopivaa sääntöä, käytetään oletustiliä")."!";
          }
        }
        else {
          // kokeillaan vielä ekalla queryllä mutta ilman nimitietoa!
          $query = "SELECT * 
                    FROM tiliotesaanto
                    WHERE yhtio = '$yritirow[yhtio]' and
                    erittely    = '' and
                    pankkitili  = '$pankkitilino' and
                    koodi       = '$koodi' and
                    koodiselite = '$koodiselite' and
                    nimitieto   = '' and
                    locate(selite, '$vientiselite') > 0";
          $tiliotesaantoresult = pupe_query($query);

          // Jotain löytyy
          if (mysqli_num_rows($tiliotesaantoresult) > 0) {
            if (mysqli_num_rows($tiliotesaantoresult) == 1) {
              $tiliointisaantorow = mysqli_fetch_array($tiliotesaantoresult);
              $tiliotesaantotilino = $tiliointisaantorow['tilino'];
            }
            else {
              echo t("Monta sopivaa sääntöä, käytetään oletustiliä")."!";
            }
          }
        }
      }
    }
  }
}

if ($tiliotesaantotilino != $yhtiorow['selvittelytili']) {
  $loydettiinko_sopiva_tili = 'joo';
}
