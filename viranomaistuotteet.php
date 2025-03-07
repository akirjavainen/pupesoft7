<?php

require "inc/parametrit.inc";

enable_ajax();

if ($livesearch_tee == "TILIHAKU") {
  livesearch_tilihaku();
  exit;
}

echo "<font class='head'>".t("Viranomaistuotteiden päivitys")."</font><hr>";

flush();

if ($tee == 'PERUSTA') {

  $yc = 0;
  $ic = 0;
  $uc = 0;

  for ($riviindex = 0; $riviindex < count($maa); $riviindex++) {

    $paivaraha        = (float) $hinta[$riviindex];
    $tilino           = (int) $tilille[$riviindex];

    $maa_koodi        = trim($maa[$riviindex]);
    $maa_nimi         = trim(preg_replace("/[^a-z\,\.\-\(\) åäöüÅÄÖ]/i", "", trim($maannimi[$riviindex])));
    $vuosi            = date('y', mktime(0, 0, 0, 1, 6, $annettuvuosi));
    $lisaa_nimi       = trim($erikoisehto[$riviindex]);

    $tuotenimitys     = "Ulkomaanpäiväraha $annettuvuosi $maa_nimi";
    $tuotenimitys_osa = "Ulkomaanosapäiväraha $annettuvuosi $maa_nimi";

    if ($maa_koodi != '' and $lisaa_nimi == '') {
      $tuoteno = "PR-$maa_koodi-$vuosi";
    }
    elseif ($maa_koodi != '' and $lisaa_nimi == 'K') {
      $tuoteno = "PR-$maa_koodi-$maa_nimi-$vuosi";
    }
    else {
      $tuoteno = "PR-$maa_nimi-$vuosi";
    }

    $query  = "INSERT INTO tuote SET
               tuoteno       = '$tuoteno',
               nimitys       = '$tuotenimitys',
               malli         = '$tuotenimitys_osa',
               alv           = '0',
               kommentoitava = '',
               kuvaus        = '50',
               myyntihinta   = '$paivaraha',
               myymalahinta  = $paivaraha / 2,
               tuotetyyppi   = 'A',
               status        = 'A',
               tilino        = '$tilino',
               vienti        = '$maa[$riviindex]',
               yhtio         = '$kukarow[yhtio]',
               laatija       = '$kukarow[kuka]',
               luontiaika    = now()
               ON DUPLICATE KEY UPDATE
               nimitys       = '$tuotenimitys',
               malli         = '$tuotenimitys_osa',
               alv           = '0',
               kommentoitava = '',
               kuvaus        = '50',
               myyntihinta   = '$paivaraha',
               myymalahinta  = $paivaraha / 2,
               tuotetyyppi   = 'A',
               status        = 'A',
               tilino        = '$tilino',
               vienti        = '$maa[$riviindex]',
               muuttaja      = '$kukarow[kuka]',
               muutospvm     = now()";
    $result = pupe_query($query);
  }

  echo "<br>".t("Ukomaanpäivärahat lisätty kantaan")."<br><br><br>";
  unset($tee);
}

if ($tee == 'POISTA') {
  $annettuvuosipoista = date("y");

  $query = "UPDATE tuote
            SET status = 'P'
            WHERE yhtio = '$kukarow[yhtio]'
            AND ((tuotetyyppi = 'A' and tuoteno like 'PR-%')
              OR (tuotetyyppi = 'B' and tuoteno like 'KM-%'))
            AND right(tuoteno, 2) > 0
            AND right(tuoteno, 2) < $annettuvuosipoista";
  $result = pupe_query($query);

  echo "<br>".t("Vanhat päivärahat poistettu käytöstä")."<br><br><br>";
  unset($tee);
}

if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE and isset($annettuvuosi) and $annettuvuosi != 0 and isset($tilinumero) and trim($tilinumero) != '' and $tee == 'LUO') {

  $path_parts = pathinfo($_FILES['userfile']['name']);
  $ext = mb_strtoupper($path_parts['extension']);

  if ($ext != "XLS") {
    die ("<font class='error'><br>".t("Ainoastaan .xls tiedostot sallittuja")."!</font>");
  }

  if ($_FILES['userfile']['size'] == 0) {
    die ("<font class='error'><br>".t("Tiedosto on tyhjä")."!</font>");
  }

  require_once 'excel_reader/reader.php';

  // ExcelFile
  $data = new Spreadsheet_Excel_Reader();

  // Set output Encoding.
  $data->setOutputEncoding('CP1251');
  $data->setRowColOffset(0);
  $data->read($_FILES['userfile']['tmp_name']);

  echo "<font class='message'>".t("Tarkastetaan lähetetty tiedosto")."...<br><br></font>";
  echo "<form method='post'>";

  // luetaan eka rivi tiedostosta..
  $headers = array();

  for ($excej = 0; $excej < $data->sheets[0]['numCols']; $excej++) {
    $headers[] = mb_strtoupper(trim($data->sheets[0]['cells'][0][$excej]));
  }

  // Poistetaan tyhjät headerit oikealta
  for ($excej = 0; $excej = (count($headers)-1); $excej--) {
    if ($headers[$excej] != "") {
      break;
    }
    else {
      unset($headers[$excej]);
    }
  }

  // Luetaan tiedosto loppuun ja tehdään taulukohtainen array koko datasta
  for ($excei = 1; $excei < $data->sheets[0]['numRows']; $excei++) {
    for ($excej = 0; $excej < count($headers); $excej++) {
      $taulunrivit[$taulut[$excej]][$excei-1][] = trim($data->sheets[0]['cells'][$excei][$excej]);
    }
  }

  foreach ($taulunrivit as $taulu => $rivit) {

    echo "<table>";
    echo "<tr>";
    foreach ($taulunotsikot[$taulu] as $key => $column) {
      echo "<th>$column</th>";
    }
    echo "<th colspan='5'>".t("Tuotteet")."</th>";
    echo "</tr>";

    for ($eriviindex = 0; $eriviindex < count($rivit); $eriviindex++) {
      echo "<tr>";
      foreach ($rivit[$eriviindex] as $pyll => $eriv) {
        if ($pyll == 0) {
          $query = "  SELECT koodi from maat where nimi like '%$eriv%' limit 1";
          $res = pupe_query($query);
          $row = mysqli_fetch_assoc($res);
          $calc = mysqli_num_rows($res);

          echo "<td>";

          $query2 = "  SELECT distinct koodi, nimi from maat having nimi !='' order by koodi,nimi ";
          $res2 = pupe_query($query2);

          echo "<select name='maa[$eriviindex]' >";
          echo "<option value = ''>".t("VIRHE: Maatunnusta ei löytynyt")."!</option>";

          while ($vrow = mysqli_fetch_assoc($res2)) {
            $sel="";
            if (mb_strtoupper($vrow['koodi']) == mb_strtoupper($row['koodi'])) {
              $sel = "selected";
            }
            echo "<option value = '$vrow[koodi]' $sel>$vrow[nimi]</option>";
          }

          echo "</select></td>";

          echo "<td><input type='checkbox' name='erikoisehto[$eriviindex]' value='K'> ".t("Lisää maan nimi tuotenumeroon");
          echo "<input type='hidden' name='maannimi[$eriviindex]' value='$eriv'></td>";
          echo "<td>".t("Ulkomaanpäiväraha")." $annettuvuosi $eriv</td>";
        }
        else {
          echo "<td><input type='hidden' name='hinta[$eriviindex]' value='$eriv' />$eriv</td>";
        }
      }

      echo "<td><input type='text' name='tilille[$eriviindex]' value='$tilinumero' />";
      echo "</td>";
      echo "</tr>";
    }
    echo "</table><br>";
  }

  echo "<table>";
  echo "<tr colspan='3'>";
  echo "<td class='back'><input type='submit' name='perusta' value='".t("Perusta ulkomaanpäivärahat")."' />";
  echo "<input type='hidden' name='tee' value='PERUSTA' >";
  echo "<input type='hidden' name='annettuvuosi' value='$annettuvuosi' >";
  echo"</td></tr></table>";
  echo "<br><br>";
  echo "</form>";
}

if ($tee == 'LUO' and (trim($tilinumero) == '' or trim($annettuvuosi) == '')) {
  echo "<font class='error'>".t("VIRHE: Joko tiedosto puuttui, tilinumero puuttui tai vuosi puuttui")."!</font>";
  unset($tee);
}

if ($tee == "synkronoi") {

  $query = "SELECT tunnus, tilino
            FROM tili
            WHERE yhtio  = '{$kukarow['yhtio']}'
            and tilino   = '{$ulkomaantilinumero}'
            and tilino  != ''";
  $tilires = pupe_query($query);

  if (mysqli_num_rows($tilires) == 0) {
    echo "<font class='error'>".t("VIRHE: Ulkomaanpäivärahojen tilinumero puuttuu")."!</font><br>";
    $tee = '';
  }

  $query = "SELECT tunnus, tilino
            FROM tili
            WHERE yhtio  = '{$kukarow['yhtio']}'
            and tilino   = '{$kotimaantilinumero}'
            and tilino  != ''";
  $tilires = pupe_query($query);

  if (mysqli_num_rows($tilires) == 0) {
    echo "<font class='error'>".t("VIRHE: Kotimaanpäivärahojen tilinumero puuttuu")."!</font><br>";
    $tee = '';
  }
}

if ($tee == "synkronoi") {

  echo t("Lisätään uudet viranomaistuotteet tietokantaan")."...<br>";

  $ch  = curl_init();
  curl_setopt($ch, CURLOPT_URL, "http://pupeapi.sprintit.fi/referenssiviranomaistuotteet.sql");
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HEADER, FALSE);
  $nimikeet = curl_exec($ch);

  // Käännetään aliakset UTF-8 muotoon, jos Pupe on UTF-8:ssa
  if (PUPE_UNICODE) {
    // Tässä on "//NO_MB_OVERLOAD"-kommentti
    // jotta UTF8-konversio ei osu tähän riviin
    $nimikeet = mb_convert_encoding($nimikeet, 'UTF-8', mb_list_encodings()); //NO_MB_OVERLOAD
  }

  $nimikeet = explode("\n", trim($nimikeet));

  // Eka rivi roskikseen
  array_shift($nimikeet);

  foreach ($nimikeet as $rivi) {
    list($tuoteno, $nimitys, $alv, $kommentoitava, $kuvaus, $myyntihinta, $tuotetyyppi, $vienti, $malli, $myymalahinta) = explode("\t", trim($rivi));

    if (mb_strpos($nimitys, "Ulkomaanpäiväraha") !== FALSE) {
      $tilino = $ulkomaantilinumero;
    }
    else {
      $tilino = $kotimaantilinumero;
    }

    $query  = "INSERT INTO tuote SET
               tuoteno       = '$tuoteno',
               nimitys       = '$nimitys',
               alv           = '$alv',
               kommentoitava = '$kommentoitava',
               kuvaus        = '$kuvaus',
               myyntihinta   = '$myyntihinta',
               tuotetyyppi   = '$tuotetyyppi',
               status        = 'A',
               tilino        = '$tilino',
               vienti        = '$vienti',
               malli         = '$malli',
               myymalahinta  = '$myymalahinta',
               yhtio         = '$kukarow[yhtio]',
               laatija       = '$kukarow[kuka]',
               luontiaika    = now()
               ON DUPLICATE KEY UPDATE
               nimitys       = '$nimitys',
               alv           = '$alv',
               kommentoitava = '$kommentoitava',
               kuvaus        = '$kuvaus',
               myyntihinta   = '$myyntihinta',
               tuotetyyppi   = '$tuotetyyppi',
               status        = 'A',
               tilino        = '$tilino',
               vienti        = '$vienti',
               malli         = '$malli',
               myymalahinta  = '$myymalahinta',
               muuttaja      = '$kukarow[kuka]',
               muutospvm     = now()";
    $result = pupe_query($query);
  }

  fclose($file);
}

if ($tee == "synkronoi" or $tee == "synkronoimaat") {

  echo t("Päivitetään maat tietokantaan")."...<br>";

  $ch  = curl_init();
  curl_setopt($ch, CURLOPT_URL, "http://pupeapi.sprintit.fi/referenssimaat.sql");
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HEADER, FALSE);
  $nimikeet = curl_exec($ch);

  // Käännetään aliakset UTF-8 muotoon, jos Pupe on UTF-8:ssa
  if (PUPE_UNICODE) {
    // Tässä on "//NO_MB_OVERLOAD"-kommentti
    // jotta UTF8-konversio ei osu tähän riviin
    $nimikeet = mb_convert_encoding($nimikeet, 'UTF-8', mb_list_encodings()); //NO_MB_OVERLOAD
  }

  $nimikeet = explode("\n", trim($nimikeet));

  // Eka rivi roskikseen
  array_shift($nimikeet);

  foreach ($nimikeet as $rivi) {
    list($koodi, $nimi, $eu, $ryhma_tunnus, $iso3, $iso_name) = explode("\t", trim($rivi));

    $query  = "INSERT INTO maat SET
               koodi        = '$koodi',
               iso3         = '$iso3',
               nimi         = '$nimi',
               name         = '$iso_name',
               eu           = '$eu',
               ryhma_tunnus = '$ryhma_tunnus'
               ON DUPLICATE KEY UPDATE
               koodi        = '$koodi',
               iso3         = '$iso3',
               nimi         = '$nimi',
               name         = '$iso_name',
               eu           = '$eu',
               ryhma_tunnus = '$ryhma_tunnus'";
    $result = pupe_query($query);
  }

  if (isset($file)) fclose($file); // MUOKKAUS: isset()

  echo t("Päivitys referenssistä valmis")."...<br>";
  unset($tee);
}

if ($tee == '') {
  echo "<br><form method='post' name='sendfile' enctype='multipart/form-data'>";

  echo t("Lue ulkomaanpäivärahat tiedostosta").":<br><br>";
  echo "<table>";
  echo "<tr><th>".t("Valitse tiedosto").":</th>";
  echo "<td><input name='userfile' type='file'></td>";
  echo "<td class='back'><input type='submit' value='".t("Jatka")."'></td>";

  echo "<tr><th>".t("Tili (Kirjanpito)")."</th>";
  echo "<td width='200' valign='top'>".livesearch_kentta("sendfile", "TILIHAKU", "tilinumero", 170, $tilinumero, "EISUBMIT")." $tilinimi\n";
  echo "<input type='hidden' name='tee' value='LUO'></td>";
  echo "</tr>";
  echo "<tr><th>".t("Anna vuosi")."</th><td><input type='text' name='annettuvuosi' value='".date('Y')."' size='4'></td>";
  echo "</table>";
  echo "</form><br><br>";

  echo t("Poista vanhat päivärahat sekä KM- alkuiset muut kulut")." (PR-*".(date("y")-1)." KM-*".(date("y")-1)."):<br><br>";
  echo "<form method='post'>";
  echo "<table>";
  echo "<tr><th>".t("Poista edellisten vuosien päivärahat ja muut kulut käytöstä")."</th>";
  echo "<td><input type='submit' value='".t("Poista")."'></td>";
  echo "<input type='hidden' name='tee' value='POISTA'><input type='hidden' name='annettuvuosipoista' value='".date('y')."'><tr>";
  echo "</table>";
  echo "</form><br><br>";

  echo t("Päivitä järjestelmän päivärahat").":<br><br>";
  echo "<form method='post'>";
  echo "<table>";
  echo "<tr><th>".t("Tili (Kirjanpito)")." ".t("Kotimaanpäivärahat")."</th><td width='200' valign='top'>".livesearch_kentta("sendfile", "TILIHAKU", "kotimaantilinumero", 170, $kotimaantilinumero, "EISUBMIT")."</td></tr>";
  echo "<tr><th>".t("Tili (Kirjanpito)")." ".t("Ulkomaanpäivärahat")."</th><td width='200' valign='top'>".livesearch_kentta("sendfile", "TILIHAKU", "ulkomaantilinumero", 170, $ulkomaantilinumero, "EISUBMIT")."</td></tr>";
  echo "<tr><th>".t("Nouda uusimmat päivärahat")."</th>";
  echo "<td><input type='submit' value='".t("Nouda")."'></td>";
  echo "<input type='hidden' name='tee' value='synkronoi'><tr>";
  echo "</table>";
  echo "</form>";

  echo "<br><br><br>";
  echo t("Päivitä järjestelmän maat").":<br><br>";
  echo "<form method='post'>";
  echo "<input type='submit' value='".t("Päivitä maat referenssistä")."'>";
  echo "<input type='hidden' name='tee' value='synkronoimaat'><tr>";
  echo "</form>";

}

require "inc/footer.inc";
