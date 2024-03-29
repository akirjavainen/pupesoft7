<?php

echo "<font class='message'>".t("Suorituksia kohdistetaan asiakkaaseen")."</font><br>\n";

$query  = "SELECT *
           FROM suoritus
           WHERE yhtio         = '$kukarow[yhtio]'
           and kohdpvm         = '0000-00-00'
           and asiakas_tunnus  = 0
           and summa          != 0";
$result = pupe_query($query);

while ($suoritus = mysqli_fetch_assoc($result)) {

  $ok = 0;

  // Kokeillaan ensin suoraan viitteellä
  if ($suoritus['viite'] != '') {
    $query  = "SELECT DISTINCT liitostunnus
               FROM lasku USE INDEX (yhtio_tila_mapvm)
               WHERE yhtio = '$kukarow[yhtio]'
               AND tila    = 'U'
               AND alatila = 'X'
               AND mapvm   = '0000-00-00'
               AND viite   = '$suoritus[viite]'";
    $laresult = pupe_query($query);

    // Viitteellä löytyi lasku!
    if (mysqli_num_rows($laresult) == 1) {
      $lasku = mysqli_fetch_assoc($laresult);

      //Etsitään vastaava asiakas
      $query = "SELECT nimi, konserniyhtio, tunnus
                FROM asiakas
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$lasku[liitostunnus]'";
      $asres = pupe_query($query);

      if (mysqli_num_rows($asres) == 1) {
        $asiakas = mysqli_fetch_assoc($asres);
        $ok = 1;

        echo "<font class='message'>Kohdistettiin: $suoritus[nimi_maksaja] --> $asiakas[nimi] viitteen perusteella</font><br>\n";

        if ($asiakas['konserniyhtio'] != '') {
          $query   = "UPDATE tiliointi
                      SET tilino = '$yhtiorow[konsernimyyntisaamiset]'
                      WHERE yhtio  = '$kukarow[yhtio]'
                      AND tunnus   = '$suoritus[ltunnus]'
                      AND korjattu = ''";
          $result2 = pupe_query($query);
        }

        $query = "UPDATE suoritus
                  set asiakas_tunnus = '$asiakas[tunnus]'
                  where tunnus = '$suoritus[tunnus]'
                  AND yhtio    = '$kukarow[yhtio]'";
        $result2 = pupe_query($query);
      }
    }
  }

  // Kokeillaan kohdistaa nimellä
  if ($ok == 0) {

    $unimi = poista_osakeyhtio_lyhenne($suoritus['nimi_maksaja']);

    $old   = array("[", "{", "\\", "|", "]", "}");
    $new   = array("Ä", "ä", "Ö", "ö", "Å", "å");
    $unimi = str_replace($old, $new, $unimi);

    $asiakasokmaksaja = FALSE;

    // Kokeillaan eka suoraan suorituksen maksajalla, 12 merkkia, aktiiviset asiakkaat
    $query = "SELECT nimi, konserniyhtio, tunnus
              FROM asiakas
              WHERE yhtio  = '$kukarow[yhtio]'
              and laji    != 'R'
              and laji    != 'P'
              and left(nimi, 12) = '{$suoritus['nimi_maksaja']}'";
    $asres = pupe_query($query);

    if (mysqli_num_rows($asres) == 1) {
      $asiakas = mysqli_fetch_assoc($asres);
      $asiakasokmaksaja = TRUE;
    }

    // Kokeillaan eka suoraan suorituksen maksajalla, 12 merkkia, kaikki asiakkaat
    if (!$asiakasokmaksaja) {
      $query = "SELECT nimi, konserniyhtio, tunnus
                FROM asiakas
                WHERE yhtio  = '$kukarow[yhtio]'
                and laji    != 'R'
                and left(nimi, 12) = '{$suoritus['nimi_maksaja']}'";
      $asres = pupe_query($query);

      if (mysqli_num_rows($asres) == 1) {
        $asiakas = mysqli_fetch_assoc($asres);
        $asiakasokmaksaja = TRUE;
      }
    }

    // Kokeillaan eka suoraan suorituksen maksajalla, 12 merkkia, aktiiviset asiakkaat
    if (!$asiakasokmaksaja) {
      $query = "SELECT nimi, konserniyhtio, tunnus
                FROM asiakas
                WHERE yhtio  = '$kukarow[yhtio]'
                and laji    != 'R'
                and laji    != 'P'
                and MATCH (nimi) AGAINST ('\"$unimi\"' IN BOOLEAN MODE)";
      $asres = pupe_query($query);

      if (mysqli_num_rows($asres) == 1) {
        $asiakas = mysqli_fetch_assoc($asres);
        $asiakasokmaksaja = TRUE;
      }
    }

    // Kokeillaan eka suoraan suorituksen maksajalla, 12 merkkia, kaikki asiakkaat
    if (!$asiakasokmaksaja) {
      $query = "SELECT nimi, konserniyhtio, tunnus
                FROM asiakas
                WHERE yhtio  = '$kukarow[yhtio]'
                and laji    != 'R'
                and MATCH (nimi) AGAINST ('\"$unimi\"' IN BOOLEAN MODE)";
      $asres = pupe_query($query);

      if (mysqli_num_rows($asres) == 1) {
        $asiakas = mysqli_fetch_assoc($asres);
        $asiakasokmaksaja = TRUE;
      }
    }

    if ($asiakasokmaksaja) {
      echo "<font class='message'>Kohdistettiin: $suoritus[nimi_maksaja] --> $asiakas[nimi] nimen perusteella</font><br>\n";

      if ($asiakas['konserniyhtio'] != '') {
        $query   = "UPDATE tiliointi
                    SET tilino = '$yhtiorow[konsernimyyntisaamiset]'
                    WHERE yhtio  = '$kukarow[yhtio]'
                    AND tunnus   = '$suoritus[ltunnus]'
                    AND korjattu = ''";
        $result2 = pupe_query($query);
      }

      $query = "UPDATE suoritus
                SET asiakas_tunnus = '$asiakas[tunnus]'
                WHERE tunnus = '$suoritus[tunnus]'
                AND yhtio    = '$kukarow[yhtio]'";
      $result2 = pupe_query($query);
    }
  }
}

echo "<font class='message'>".t("Suoritukset kohdistettu")."</font><br>\n<br>\n";
