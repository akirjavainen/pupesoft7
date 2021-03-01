<?php

require "inc/parametrit.inc";

// Tarkistetaan oikeus
$tuoteylloik = tarkista_oikeus("yllapito.php", "tuote%", "X", TRUE);

if (empty($tuoteylloik)) {
  echo "<font class='error'>".t("VIRHE: Sinulla ei ole oikeutta perustaa uusia tuotteita")."!</font><br><br>";
  $tee = "XXXVIRHEXXX";
}
else {
  $pertuotetoim = $tuoteylloik["alanimi"];
}

if ($tee != 'PERUSTA') {

  if ($livesearch_tee == "TUOTEHAKU") {
    livesearch_tuotehaku();
    exit;
  }

  // Enaboidaan ajax kikkare
  enable_ajax();

  echo "<font class='head'>".t("Kopioi tuote")."</font><hr>";
}

if ($tee == 'PERUSTA') {
  //  Trimmataan tyhjät merkit
  $uustuoteno = trim($uustuoteno);

  if ($uustuoteno == '') {
    $tee = 'AVALITTU';
    $varaosavirhe = t("VIRHE: Uusi tuotenumero ei saa olla tyhjä")."!";
  }
}

if ($tee == 'PERUSTA') {

  if (mb_strpos($tuoteno, '####') !== FALSE) {
    $hakyhtio  = mb_substr($tuoteno, mb_strpos($tuoteno, '####')+4);
    $tuoteno   = mb_substr($tuoteno, 0, mb_strpos($tuoteno, '####'));
  }
  else {
    $hakyhtio = $kukarow["yhtio"];
  }

  $query = "SELECT tunnus
            FROM tuote
            WHERE yhtio = '$kukarow[yhtio]'
            and tuoteno = '$uustuoteno'";
  $result = pupe_query($query);

  if (mysqli_num_rows($result) != 0 ) {
    $tee = 'HAKU';
    $varaosavirhe = t("VIRHE: Uudella tuotenumerolla")." $uustuoteno ".t("löytyy jo tuote, ei voida perustaa")."!";
  }
  else {
    $query = "SELECT *
              FROM tuote
              WHERE yhtio = '$hakyhtio'
              and tuoteno = '$tuoteno'";
    $stresult = pupe_query($query);

    if (mysqli_num_rows($stresult) == 0) {
      $tee = 'HAKU';
      $varaosavirhe = t("VIRHE: Vanha tuote")." $tuoteno ".t("on kadonnut, ei uskalleta tehdä mitään")."!";
    }
    else {
      $otsikkorivi = mysqli_fetch_array($stresult);

      // tuotepaikat perustetan tuotetarkista.incissä, ei tehdä niitä tässä

      // tehdään vanhasta tuotteesta 1:1 kopio...
      $query = "INSERT into tuote set ";

      for ($i = 0; $i < mysqli_num_fields($stresult); $i++) {

        if (mysqli_field_name($stresult, $i) == 'yhtio') {
          $query .= "yhtio='$kukarow[yhtio]',";
        }
        // tuotenumeroksi tietenkin uustuoteno
        elseif (mysqli_field_name($stresult, $i) == 'tuoteno') {
          $query .= "tuoteno='$uustuoteno',";
        }
        // laatijaksi klikkaaja
        elseif (mysqli_field_name($stresult, $i) == 'laatija') {
          $query .= "laatija='$kukarow[kuka]',";
        }
        // muuttajaksi klikkaaja
        elseif (mysqli_field_name($stresult, $i) == 'muuttaja') {
          $query .= "muuttaja='$kukarow[kuka]',";
        }
        // luontiaika
        elseif (mysqli_field_name($stresult, $i) == 'luontiaika' or mysqli_field_name($stresult, $i) == 'muutospvm') {
          $query .= mysqli_field_name($stresult, $i)."=now(),";
        }
        // nämä kentät tyhjennetään
        elseif (mysqli_field_name($stresult, $i) == 'kehahin' or
          mysqli_field_name($stresult, $i) == 'vihahin' or
          mysqli_field_name($stresult, $i) == 'vihapvm' or
          mysqli_field_name($stresult, $i) == 'epakurantti25pvm' or
          mysqli_field_name($stresult, $i) == 'epakurantti50pvm' or
          mysqli_field_name($stresult, $i) == 'epakurantti75pvm' or
          mysqli_field_name($stresult, $i) == 'epakurantti100pvm' or
          mysqli_field_name($stresult, $i) == 'eankoodi') {
          $query .= mysqli_field_name($stresult, $i)."='',";
        }
        elseif (mysqli_field_name($stresult, $i) == "ostoehdotus") {
          if (!empty($ostoehdotus_avainsanoista)) {
            if (count($ostoehdotus_avainsanoista) == 1 and $ostoehdotus_avainsanoista[0] == 'default') {
              $query .= mysqli_field_name($stresult, $i)."='E',";
            }
            else {
              $query .= mysqli_field_name($stresult, $i)."='',";
            }
          }
          else {
            $query .= mysqli_field_name($stresult, $i)."='".$otsikkorivi[$i]."',";
          }
        }
        // ja kaikki muut paitsi tunnus sellaisenaan
        elseif (mysqli_field_name($stresult, $i) != 'tunnus') {
          $query .= mysqli_field_name($stresult, $i)."='".$otsikkorivi[$i]."',";
        }
      }
      $query = mb_substr($query, 0, -1);

      // MUOKKAUS: mysqli_insert_id():
      $stresult = pupe_query($query, $GLOBALS["link"]);
      $tuote_id = mysqli_insert_id($GLOBALS["link"]);

      //  Tämä funktio tekee myös oikeustarkistukset!
      synkronoi($kukarow["yhtio"], "tuote", $tuote_id, "", "");

      if (!empty($kopioi_tt)) {
        $query = "SELECT *
                  FROM tuotteen_toimittajat
                  WHERE yhtio = '$hakyhtio'
                  and tuoteno = '$tuoteno'";
        $stresult = pupe_query($query);

        if (mysqli_num_rows($stresult) != 0 ) {
          while ($otsikkorivi = mysqli_fetch_array($stresult)) {

            $query_fields = "";

            for ($i=0; $i<mysqli_num_fields($stresult); $i++) {

              if (mysqli_field_name($stresult, $i) == 'yhtio') {
                $query_fields .= "yhtio='$kukarow[yhtio]',";
              }
              // tuotenumeroksi tietenkin uustuoteno
              elseif (mysqli_field_name($stresult, $i) == 'tuoteno') {
                $query_fields .= "tuoteno='$uustuoteno',";
              }
              // laatijaksi klikkaaja
              elseif (mysqli_field_name($stresult, $i) == 'laatija') {
                $query_fields .= "laatija='$kukarow[kuka]',";
              }
              // muuttajaksi klikkaaja
              elseif (mysqli_field_name($stresult, $i) == 'muuttaja') {
                $query_fields .= "muuttaja='$kukarow[kuka]',";
              }
              // luontiaika
              elseif (mysqli_field_name($stresult, $i) == 'luontiaika' or mysqli_field_name($stresult, $i) == 'muutospvm') {
                $query_fields .= mysqli_field_name($stresult, $i)."=now(),";
              }
              // ja kaikki muut paitsi tunnus sellaisenaan
              elseif (mysqli_field_name($stresult, $i) != 'tunnus') {
                $query_fields .= mysqli_field_name($stresult, $i)."='".$otsikkorivi[$i]."',";
              }
            }

            // Tehdään vanhoista tuotteen_toimittajista 1:1 kopio...
            $query  = "INSERT into tuotteen_toimittajat set ";
            $query .= mb_substr($query_fields, 0, -1);
            $query .= " ON DUPLICATE KEY UPDATE ";
            $query .= mb_substr($query_fields, 0, -1);

            // MUOKKAUS: mysqli_insert_id():
            $astresult = pupe_query($query, $GLOBALS["link"]);
            $id2 = mysqli_insert_id($GLOBALS["link"]);

            synkronoi($kukarow["yhtio"], "tuotteen_toimittajat", $id2, "", "");
          }
        }
      }

      // kopioidaan dynaamisen puun tiedot uudelle tuotteelle
      $query = "SELECT *
                FROM puun_alkio
                WHERE yhtio = '$hakyhtio'
                and laji    = 'tuote'
                and liitos  = '$tuoteno'";
      $stresult = pupe_query($query);

      if (mysqli_num_rows($stresult) != 0) {

        while ($otsikkorivi = mysqli_fetch_array($stresult)) {

          $query_fields = "";

          for ($i = 0; $i < mysqli_num_fields($stresult); $i++) {

            if (mysqli_field_name($stresult, $i) == 'yhtio') {
              $query_fields .= "yhtio='$kukarow[yhtio]',";
            }
            // liitokseksi tietenkin uustuoteno
            elseif (mysqli_field_name($stresult, $i) == 'liitos') {
              $query_fields .= "liitos='$uustuoteno',";
            }
            // laatijaksi klikkaaja
            elseif (mysqli_field_name($stresult, $i) == 'laatija') {
              $query_fields .= "laatija='$kukarow[kuka]',";
            }
            // muuttajaksi klikkaaja
            elseif (mysqli_field_name($stresult, $i) == 'muuttaja') {
              $query_fields .= "muuttaja='$kukarow[kuka]',";
            }
            // luontiaika
            elseif (mysqli_field_name($stresult, $i) == 'luontiaika' or mysqli_field_name($stresult, $i) == 'muutospvm') {
              $query_fields .= mysqli_field_name($stresult, $i)."=now(),";
            }
            // ja kaikki muut paitsi tunnus sellaisenaan
            elseif (mysqli_field_name($stresult, $i) != 'tunnus') {
              $query_fields .= mysqli_field_name($stresult, $i)."='".$otsikkorivi[$i]."',";
            }
          }

          // Tehdään vanhasta alkiosta kopio...
          $query = "INSERT into puun_alkio set ";
          $query .= mb_substr($query_fields, 0, -1);
          $query .= " ON DUPLICATE KEY UPDATE ";
          $query .= mb_substr($query_fields, 0, -1);

	  
	  // MUOKKAUS: mysqli_insert_id():
          $puunalkio_result = pupe_query($query, $GLOBALS["link"]);
          $id2 = mysqli_insert_id($GLOBALS["link"]);

          synkronoi($kukarow["yhtio"], "puun_alkio", $id2, "", "");
        }
      }

      //  Lähetetään mailia tästä eteenpäin jos meillä on vastaanottajia
      if ($yhtiorow["tuotekopio_email"] != "") {
        $header  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "UTF-8", "Q")." <$yhtiorow[postittaja_email]>\n";
        $header .= "MIME-Version: 1.0\n" ;

        $query = "SELECT *
                  FROM yhtio
                  WHERE yhtio = '$hakyhtio'";
        $yres = pupe_query($query);
        $yrow = mysqli_fetch_array($yres);

        $content = $kukarow["nimi"]." ".t("kopioi yhtiön")." $yrow[nimi] ".t("tuotteen")." '$tuoteno' ".t("yhtiön")." $yhtiorow[nimi] ".t("tuotteeksi")." '$uustuoteno'\n\n";

        mail($yhtiorow["tuotekopio_email"], mb_encode_mimeheader(t("Tuotteita kopioitu"), "UTF-8", "Q"), $content, $header, "-f $yhtiorow[postittaja_email]");
      }

      $toim   = $pertuotetoim;
      $tunnus = $tuote_id;
      $tee   = '';

      require "yllapito.php";
      exit;
    }
  }
}

if ($tee == 'HAKU') {

  $query = "SELECT tunnus
            FROM tuote
            WHERE yhtio = '$kukarow[yhtio]'
            and tuoteno = '$tuoteno'";
  $result = pupe_query($query);

  if (mysqli_num_rows($result) == 1) {
    $tee = 'AVALITTU';
  }
  else {
    $konsernihaku = "KYLLA";
    $kaikkituhaku = "KYLLA";

    if (mb_strpos($tuoteno, '*') === FALSE) {
      $tuoteno = $tuoteno."*";
    }

    require "inc/tuotehaku.inc";

    //on vaan löytynyt 1 muuten tulis virhettä ja ulosta
    if ($tee == 'HAKU' and $ulos == '' and $varaosavirhe == '' and $tuoteno != '') {
      $tee = 'AVALITTU';
    }
  }
}

if ($tee == 'AVALITTU' and $tuoteno != '') {
  $formi  = 'performi';
  $kentta = 'uustuoteno';

  echo "<form method='post' name='{$formi}' autocomplete='off'>";
  echo "<table>";
  echo "<tr><th>".t("Kopioitava tuote")."</th></tr>";

  if (mb_strpos($tuoteno, '####') !== FALSE) {
    $tu = mb_substr($tuoteno, mb_strpos($tuoteno, '####')+4)." - ".mb_substr($tuoteno, 0, mb_strpos($tuoteno, '####'));
  }
  else {
    $tu = $tuoteno;
  }

  echo "<tr><td>$tu</td>";

  $tresult_tt = t_avainsana("KOPIOITUOTE", "", "and selite = 'TT'");
  $trow_tt = mysqli_fetch_assoc($tresult_tt);
  $chk = $trow_tt['selitetark'] == 'K' ? "checked='true'" : "";
  $chk = mysqli_num_rows($tresult_tt) != 0 ? $chk : "checked='true'";

  echo "<tr>";
  echo "<th>".t("Anna uusi tuotenumero")."<br>".t("joka perustetaan")."</th>";
  echo "<td class='back'>";
  echo "<input type='checkbox' name='kopioi_tt' {$chk} /> ", t("Kopioi tuotteen toimittajat");

  echo "<br />";

  $tresult_ostoehdotus = t_avainsana("KOPIOITUOTE", "", "and selite = 'OSTOEHDOTUS'");

  if (mysqli_num_rows($tresult_ostoehdotus) != 0) {
    $trow_ostoehdotus = mysqli_fetch_assoc($tresult_ostoehdotus);
    $chk = $trow_ostoehdotus['selitetark'] == 'K' ? "checked='true'" : "";

    echo "<input type='hidden' name='ostoehdotus_avainsanoista[]' value='default' />";
    echo "<input type='checkbox' name='ostoehdotus_avainsanoista[]' value='K' {$chk} /> ";
    echo t("Ehdotetaan ostoehdotusohjelmissa tilattavaksi");
  }

  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<input type='hidden' name='tee' value='PERUSTA'>";
  echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
  echo "<td><input type='text' name='uustuoteno' size='22' maxlength='30' value=''></td>";
  echo "<td class='back'><input type='submit' value='".t("Kopioi")."'></td>";
  echo "<td class='back'><font class='error'>$varaosavirhe</font></td>";
  echo "</tr></table></form>";

  echo "<form action = 'kopioituote.php' method = 'post'>
    <input type='hidden' name='toim' value='$toim'>
    <input type='hidden' name='lopetus' value='$lopetus'>";
  echo "<br><input type='submit' value='".t("Tee uusi haku")."'>";
  echo "</form>";
}

if (($tee == 'HAKU' or $tee == "Y") and $ulos != '') {
  echo "<form method='post' autocomplete='off'>";
  echo "<input type='hidden' name='tee' value='AVALITTU'>";
  echo "<table><tr>";
  echo "<th>".t("Valitse listasta").":</th></tr>";
  echo "<tr><td>$ulos</td>";
  echo "<td class='back'><input type='submit' value='".t("Valitse")."'></td>";
  echo "<td class='back'><font class='error'>$varaosavirhe</font></td>";
  echo "</tr></table>";
  echo "</form>";

  $varaosavirhe = "";
}

if ($tee == '' or $tee == "Y") {
  $formi  = 'formi';
  $kentta = 'tuoteno';

  echo "<table><tr>";
  echo "<th>".t("Anna tuotenumero josta")."<br>".t("haluat kopioda tiedot")."</th>";

  echo "<tr><form method='post' name='$formi' autocomplete='off'>";
  echo "<input type='hidden' name='tee' value='HAKU'>";
  echo "<td>".livesearch_kentta("formi", "TUOTEHAKU", "tuoteno", 210)."</td>";
  echo "<td class='back'><input type='submit' value='".t("Jatka")."'></td>";
  echo "<td class='back'><font class='error'>$varaosavirhe</font></td>";
  echo "</form></tr></table>";
}

require "inc/footer.inc";
