<?php

echo "<font class='head'>".t("Rakenteen muutos")."</font><hr>";

if ($tee == "lisaalisaa") {

  if ($tyyppi == "") {
    echo "<font class='error'>Tyyppi puuttuu!</font><br>";

    $tee = "lisaa";
  }
  if ($uusitaso == "") {
    echo "<font class='error'>Taso puuttuu!</font><br>";

    $tee = "lisaa";
  }
  if ($nimi == "") {
    echo "<font class='error'>Nimi puuttuu!</font><br>";

    $tee = "lisaa";
  }

  if (mb_substr($uusitaso, 0, mb_strlen($taso)) != $taso) {
    echo "<font class='error'>Uuden tason ".mb_strlen($taso)." ensimmäistä merkkiä on oltava: \"$taso\"!</font><br>";

    $tee = "lisaa";
  }

  if (mb_strlen($uusitaso) != mb_strlen($taso)+1) {
    echo "<font class='error'>Uuden tason pituus on oltava: ".(mb_strlen($taso)+1)."!</font><br>";

    $tee = "lisaa";
  }

  if ($kirjain != $tyyppi) {
    echo "<font class='error'>Uuden tason tyyppi on oltava: $kirjain!</font><br>";

    $tee = "lisaa";
  }

  if (mb_substr($taso, 0, 1) != mb_substr($uusitaso, 0, 1)) {

    echo "<font class='error'>Uuden tason ensimmäinen merkki on oltava: ".mb_substr($taso, 0, 1)."!</font><br>";

    $tee = "lisaa";
  }

  $query = "SELECT *
            FROM taso
            WHERE yhtio = '$kukarow[yhtio]'
            and taso    = BINARY '$uusitaso'";
  $vresult = pupe_query($query);

  if (mysqli_num_rows($vresult) > 0) {
    echo "<font class='error'>Taso $uusitaso löytyy jo järjestelmästä!</font><br>";

    $tee = "lisaa";
  }

  if ($tee == "lisaalisaa") {
    if ($sisennys == "K") {
      $query = "SELECT *
                FROM taso
                WHERE yhtio = '$kukarow[yhtio]'
                and taso    like BINARY '$taso%'
                and tyyppi  = '$kirjain'
                ORDER BY CHAR_LENGTH(taso) desc";
      $vresult = pupe_query($query);

      while ($vrow = mysqli_fetch_array($vresult)) {
        $paivtaso = $taso.mb_substr($uusitaso, -1).mb_substr($vrow["taso"], mb_strlen($taso));

        echo "Tasokoodi muuttuu: $vrow[taso] --> $paivtaso<br>";

        $query = "UPDATE taso
                  SET taso = '$paivtaso'
                  WHERE yhtio  = '$kukarow[yhtio]'
                  and taso     = BINARY '$vrow[taso]'
                  and taso    != BINARY '$taso'
                  and tyyppi   = '$kirjain'";
        $res = pupe_query($query);

        if ($kirjain == "S") {
          $query = "UPDATE tili
                    SET sisainen_taso = '$paivtaso'
                    WHERE yhtio       = '$kukarow[yhtio]'
                    and sisainen_taso = BINARY '$vrow[taso]'";
          $res = pupe_query($query);

          $query = "UPDATE budjetti
                    SET taso = '$paivtaso'
                    WHERE yhtio = '$kukarow[yhtio]'
                    and taso    = BINARY '$vrow[taso]'";
          $res = pupe_query($query);
        }
        else {
          $query = "UPDATE tili
                    SET ulkoinen_taso = '$paivtaso'
                    WHERE yhtio       = '$kukarow[yhtio]'
                    and ulkoinen_taso = BINARY '$vrow[taso]'";
          $res = pupe_query($query);
        }
      }
    }

    $query = "INSERT into taso
              SET taso     = '$uusitaso',
              tyyppi          = '$tyyppi',
              summattava_taso = '$summattava_taso',
              nimi            = '$nimi',
              laatija         = '$kukarow[kuka]',
              luontiaika      = now(),
              yhtio           = '$kukarow[yhtio]'";
    $res = pupe_query($query);

    echo "Taso $uusitaso lisätty tilikartan rakenteeseen!<br>";

    $tee = "";
  }
}

if ($tee == "lisaa") {

  echo "<br><br>";
  echo "Lisää taso väliin: $taso - $edtaso<br>";

  echo "<br>";
  echo "  <form method='post'>
      <input type = 'hidden' name = 'tasomuutos' value = 'TRUE'>
      <input type = 'hidden' name = 'tee' value = 'lisaalisaa'>
      <input type = 'hidden' name = 'taso' value = '$taso'>
      <input type = 'hidden' name = 'edtaso' value = '$edtaso'>
      <input type = 'hidden' name = 'kirjain' value = '$kirjain'>
      <input type = 'hidden' name = 'lopetus' value = '$lopetus'>
      <table>";

  $valittuu="";
  if ($tyyppi='U') $valittuu="selected";

  if (!isset($uusitaso)) {
    $uusitaso = $taso;
  }
  echo "
    <tr>
      <th align='left'>Sisennetäänkö ylemmät tasot</th>
      <td>
        <select name='sisennys'>
          <option value = 'E' >Ei</option>
          <option value = 'K' >Kyllä</option>
        </select>
      </td>
    </tr>

    <tr>
      <th align='left'>Tyyppi</th>
      <td>
        <select name='tyyppi'>
          <option value = 'S' >Sisäinen taso</option>
          <option value = 'U' $valittuu>Ulkoinen taso</option>
        </select>
      </td>
    </tr>

    <tr><th align='left'>Summattava_taso</th><td><input type = 'text' name = 'summattava_taso' value = '$summattava_taso' size='10'></td></tr>
    <tr><th align='left'>Taso</th><td><input type = 'text' name = 'uusitaso' value = '$uusitaso' size='10'></td></tr>
    <tr><th align='left'>nimi</th><td><input type = 'text' name = 'nimi' value = '$nimi' size='35'></td></tr><tr>";

  echo "</table><br>
        <input type = 'submit' value = '".t("Lisää")."'></form>";

}

if ($tee == "muutamuuta") {

  if (mb_strlen($uusitaso) < mb_strlen($taso)) {
    echo "<font class='error'>Uuden tason pituus on oltava vähintään: ".mb_strlen($taso)." merkkiä pitkä!</font><br>";

    $tee = "muuta";
  }

  if (mb_substr($taso, 0, 1) != mb_substr($uusitaso, 0, 1)) {

    echo "<font class='error'>Uuden tason ensimmäinen merkki on oltava: ".mb_substr($taso, 0, 1)."!</font><br>";

    $tee = "muuta";
  }

  $query = "SELECT *
            FROM taso
            WHERE yhtio = '$kukarowkarow[yhtio]'
            and taso    = BINARY '$uusitaso'
            and tyyppi  = '$kirjain'";
  $vresult = pupe_query($query);

  if (mysqli_num_rows($vresult) > 0) {
    echo "<font class='error'>Taso $uusitaso löytyy jo järjestelmästä!</font><br>";

    $tee = "muuta";
  }

  if ($tee == "muutamuuta") {

    echo t("Muutetaan tasot")."<br><br>";

    $query = "SELECT *
              FROM taso
              WHERE yhtio = '$kukarow[yhtio]'
              and taso    like BINARY '$taso%'
              and tyyppi  = '$kirjain'
              ORDER BY CHAR_LENGTH(taso) desc";
    $vresult = pupe_query($query);

    while ($vrow = mysqli_fetch_array($vresult)) {

      $uusitilitaso = $uusitaso.mb_substr($vrow["taso"], mb_strlen($uusitaso)-(mb_strlen($uusitaso)-mb_strlen($taso)));

      echo "$vrow[taso] --> $uusitilitaso<br>";

      $query = "UPDATE taso
                SET taso = '$uusitilitaso'
                WHERE yhtio = '$kukarow[yhtio]'
                and taso    = BINARY '$vrow[taso]'
                and tyyppi  = '$kirjain'";
      $res = pupe_query($query);

      if ($kirjain == "S") {
        $query = "UPDATE tili
                  SET sisainen_taso = '$uusitilitaso'
                  WHERE yhtio       = '$kukarow[yhtio]'
                  and sisainen_taso = BINARY '$vrow[taso]'";
        $res = pupe_query($query);

        $query = "UPDATE budjetti
                  SET taso = '$uusitilitaso'
                  WHERE yhtio = '$kukarow[yhtio]'
                  and taso    = BINARY '$vrow[taso]'";
        $res = pupe_query($query);
      }
      else {
        $query = "UPDATE tili
                  SET ulkoinen_taso = '$uusitilitaso'
                  WHERE yhtio       = '$kukarow[yhtio]'
                  and ulkoinen_taso = BINARY '$vrow[taso]'";
        $res = pupe_query($query);
      }
    }
  }

  flush();
  $tee = "";
}

if ($tee == "muuta") {

  echo "<br><br>";
  echo "Muuta tason: $taso koodia<br>";

  echo "<br>";
  echo "  <form method='post'>
      <input type = 'hidden' name = 'tasomuutos' value = 'TRUE'>
      <input type = 'hidden' name = 'tee' value = 'muutamuuta'>
      <input type = 'hidden' name = 'taso' value = '$taso'>
      <input type = 'hidden' name = 'lopetus' value = '$lopetus'>
      <input type = 'hidden' name = 'kirjain' value = '$kirjain'>
      <table>";

  echo "
    <tr><th align='left'>Uusi tasokoodi</th><td><input type = 'text' name = 'uusitaso' value = '$uusitaso' size='10'></td></tr>";

  echo "</table><br>
        <input type = 'submit' value = '".t("Muuta")."'></form>";

}

if ($tee == "tilitasotilitaso") {

  if ($kirjain == "S") {
    $query = "UPDATE tili
              SET sisainen_taso = '$uusitaso'
              WHERE yhtio = '$kukarow[yhtio]'
              and tilino  in ($tiliarray)";
    $res = pupe_query($query);
  }
  else {
    $query = "UPDATE tili
              SET ulkoinen_taso = '$uusitaso'
              WHERE yhtio = '$kukarow[yhtio]'
              and tilino  in ($tiliarray)";
    $res = pupe_query($query);
  }

  echo "Tilit $tiliarray siiretty tasoon $uusitaso!<br>";
  flush();
  $tee = "";
}

if ($tee == "tilitaso") {

  echo "<br><br>";
  echo "Anna tileille: ";
  echo implode(",", $tiliarray);
  echo " taso!<br>";

  echo "<br>";
  echo "  <form method='post'>
      <input type = 'hidden' name = 'tasomuutos' value = 'TRUE'>
      <input type = 'hidden' name = 'tee' value = 'tilitasotilitaso'>
      <input type = 'hidden' name = 'kirjain' value = '$kirjain'>
      <input type = 'hidden' name = 'lopetus' value = '$lopetus'>
      <input type = 'hidden' name = 'tiliarray' value = '".implode(",", $tiliarray)."'>
      <table>";

  echo "<tr><th align='left'>Uusi tasokoodi</th>";

  $query = "SELECT *
            from taso where yhtio = '$kukarow[yhtio]'
            and tyyppi = '$kirjain'
            and left(taso,1) = BINARY '$taso'
            order by taso";
  $tasoresu = pupe_query($query);

  echo "<td><select name='uusitaso'>\n";

  while ($tasorow = mysqli_fetch_array($tasoresu)) {
    echo "<option value = '$tasorow[taso]'>$tasorow[taso] - $tasorow[nimi]</option>\n";
  }

  echo "</select></td></tr>";

  echo "</table><br>
        <input type = 'submit' value = '".t("Muuta")."'></form>";

}

if ($tee == "") {
  if ($lopetus != '') {
    lopetus($lopetus, "META");
  }
}
