<?php
if ($tee == "file") {
  if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {
    $timeparts = explode(" ", microtime());
    $starttime = $timeparts[1].mb_substr($timeparts[0], 1);

    $path_parts = pathinfo($_FILES['userfile']['name']);
    $name  = mb_strtoupper($path_parts['filename']);
    $ext  = mb_strtoupper($path_parts['extension']);

    if ($ext != "TXT" and $ext != "CSV" and $ext != "XLS" and $ext != "XLSX") {
      die ("<font class='error'><br>".t("Ainoastaan .txt, .csv, .xlsx tai .xls tiedostot sallittuja")."!</font>");
    }

    if ($_FILES['userfile']['size']==0) {
      die ("<font class='error'><br>".t("Tiedosto on tyhjä")."!</font>");
    }

    $excelrivi = pupeFileReader($_FILES['userfile']['tmp_name'], $ext, '');

    foreach ($excelrivi as $rivi) {

      $tila      = '';
      $valinta    = '';
      $varaosavirhe   = '';
      $tuoteerror   = 0;

      $tuoteno  = pupesoft_cleanstring($rivi[0]);
      $kpl      = (float) str_replace(",", ".", $rivi[1]);

      $toimaika   = pupesoft_cleanstring($rivi[2]);
      $toimaika = choose_correct_date($toimaika);

      if (trim($toimaika) == "") {
        $toimaika = $laskurow["toimaika"];
      }

      if (trim($rivi[3]) != '') {
        $hinta = (float) str_replace(",", ".", $rivi[3]);
      }
      else {
        $hinta = "";
      }

      if (trim($rivi[4]) != '') {
        $ale1 = (float) str_replace(",", ".", $rivi[4]);
      }
      else {
        $ale1 = "";
      }

      $paikka    = pupesoft_cleanstring($rivi[5]);

      if ($tuoteno != '' and $kpl != 0) {

        ///* Toimittajan tuotenumerospecial*///
        if (mb_substr($tuoteno, 0, 1) == '?') {
          $query = "SELECT *
                    FROM tuotteen_toimittajat
                    JOIN tuote USING (yhtio, tuoteno)
                    WHERE tuotteen_toimittajat.yhtio       = '{$kukarow['yhtio']}'
                    AND tuotteen_toimittajat.toim_tuoteno  = '".mb_substr($tuoteno, 1)."'
                    AND tuotteen_toimittajat.liitostunnus  = '{$laskurow['liitostunnus']}'
                    AND tuote.status                      != 'P'
                    ORDER BY IF(tuotteen_toimittajat.jarjestys=0, 99999999999, tuotteen_toimittajat.jarjestys), tuote.tuoteno
                    LIMIT 1";
        }
        elseif (mb_substr($tuoteno, 0, 1) != '?' and $tiedoston_tuoteno == 'toimittajan_tuoteno') {
          $query = "SELECT *
                    FROM tuotteen_toimittajat
                    JOIN tuote USING (yhtio, tuoteno)
                    WHERE tuotteen_toimittajat.yhtio       = '{$kukarow['yhtio']}'
                    AND tuotteen_toimittajat.toim_tuoteno  = '$tuoteno'
                    AND tuotteen_toimittajat.liitostunnus  = '{$laskurow['liitostunnus']}'
                    AND tuote.status                      != 'P'
                    ORDER BY IF(tuotteen_toimittajat.jarjestys=0, 99999999999, tuotteen_toimittajat.jarjestys), tuote.tuoteno
                    LIMIT 1";
        }
        else {
          $query = "SELECT *, tuote.tuoteno as tuoteno
                    FROM tuote
                    LEFT JOIN tuotepaikat ON tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.oletus!=''
                    WHERE tuote.yhtio='$kukarow[yhtio]'
                    and tuote.tuoteno='$tuoteno'";
        }
        $result = pupe_query($query);

        if (mysqli_num_rows($result) == 1) {

          $trow = mysqli_fetch_array($result);
          $tuoteno = $trow["tuoteno"];

          require 'lisaarivi.inc';
        }
        else {
          echo "<font class='message'>".t("Tuotenumeroa")." $tuoteno ".t("ei löydy")."!</font><br>";
        }
      }

      $tuoteno  = '';
      $kpl    = '';
      $hinta    = '';
      $ale1    = '';
      $alv    = '';
      $var    = '';
      $toimaika  = '';
      $kommentti  = '';
      $rivitunnus = '';

    }

    echo "<br><br>";
    $tee = "Y";
  }
  else {
    $tee = "Y";
  }
}

if ($tee == 'mikrotila') {
  echo "<font class='head'>".t("Ostotilaus").":</font><hr><br>";

  echo "<form method='post' name='sendfile' enctype='multipart/form-data'>
      <input type='hidden' name='toim'         value = '$toim'>
      <input type='hidden' name='lopetus'       value = '$lopetus'>
      <input type='hidden' name='tilausnumero'     value = '$tilausnumero'>
      <input type='hidden' name='toim_nimitykset'   value = '$toim_nimitykset'>
      <input type='hidden' name='naytetaankolukitut'   value = '$naytetaankolukitut'>
      <input type='hidden' name='tee'         value = 'file'>

      <font class='message'>".t("Tiedostomuoto").":</font><br><br>

      <table>
      <tr>
      <th>".t("Tuoteno")."</th>
      <td>
      <select name='tiedoston_tuoteno'>
      <option value='oma_tuoteno'>".t("Oma tuoteno")."</option>
      <option value='toimittajan_tuoteno'>".t("Toimittajan tuoteno")."</option>
      </select>
      </td>
      </tr>
      <tr><th colspan='7'>".t("Sarkaineroteltu tekstitiedosto tai Excel-tiedosto").".</th></tr>
      <tr><td>".t("Tuoteno")."</td><td>".t("Määrä")."</td><td>".t("Arvioitu toimituspäivä vvvv-kk-pp")."</td><td>".t("Hinta")."</td><td>".t("Ale")."</td><td>".t("Paikka (#!¡!#-eroteltu)")."</td></tr>
      </table>

      <br>
      <table>
      <tr>
      <th>".t("Valitse tiedosto").":</th>
      <td><input name='userfile' type='file'></td>
      <td class='back'><input type='submit' value='".t("Läheta")."'></td>
      </tr>
      </table>
      </form>";
}
