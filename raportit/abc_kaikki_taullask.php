<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

echo "<font class='head'>".t("ABC-Analyysiä: ABC-pitkälistaus")."<hr></font>";

if ($toim == "kulutus") {
  $myykusana = t("Kulutus");
}
else {
  $myykusana = t("Myynti");
}

if ($asiakasanalyysi) {
  $astusana = t("Asiakas");
}
else {
  $astusana = t("Tuote");
}

if (trim($saapumispp) != '' and trim($saapumiskk) != '' and trim($saapumisvv) != '') {
  $saapumispp = $saapumispp;
  $saapumiskk = $saapumiskk;
  $saapumisvv  = $saapumisvv;
}
elseif (trim($saapumispvm) != '') {
  list($saapumisvv, $saapumiskk, $saapumispp) = explode('-', $saapumispvm);
}

// piirrellään formi
echo "<form method='post' autocomplete='OFF'>";
echo "<input type='hidden' name='aja' value='AJA'>";
echo "<input type='hidden' name='tee' value='PITKALISTA'>";
echo "<input type='hidden' name='toim' value='$toim'>";

// Monivalintalaatikot (osasto, try tuotemerkki...)
// Määritellään mitkä latikot halutaan mukaan
$abc_lisa  = "";
$ulisa = "";
$mulselprefix = "abc_aputaulu";

if ($asiakasanalyysi) {
  $monivalintalaatikot = array("ASIAKASOSASTO", "ASIAKASRYHMA");
}
else {
  $monivalintalaatikot = array("OSASTO", "TRY", "TUOTEMERKKI", "TUOTEMYYJA", "TUOTEOSTAJA");
}

require "tilauskasittely/monivalintalaatikot.inc";

echo "<br>";
echo "<table style='display:inline;'>";
echo "<tr>";
echo "<th>".t("Valitse luokka").":</th>";
echo "<td><select name='luokka'>";
echo "<option value=''>".t("Valitse luokka")."</option>";

$sel = array();
$sel[$luokka] = "selected";

$i=0;
foreach ($ryhmanimet as $nimi) {
  echo "<option value='$i' $sel[$i]>$nimi</option>";
  $i++;
}

echo "</select></td>";

if (!$asiakasanalyysi) {
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Syötä viimeinen saapumispäivä").":</th>";
  echo "  <td><input type='text' name='saapumispp' value='$saapumispp' size='2'>
      <input type='text' name='saapumiskk' value='$saapumiskk' size='2'>
      <input type='text' name='saapumisvv' value='$saapumisvv'size='4'></td></tr>";

  echo "<tr>";
  echo "<th>".t("Varastopaikoittain").":</th>";

  $sel = "";
  if ($paikoittain == 'JOO') {
    $sel = "CHECKED";
  }

  echo "<td><input type='checkbox' name='paikoittain' value='JOO' $sel></td>";
}
echo "<td class='back'><input type='submit' name='ajoon' value='".t("Aja raportti")."'></td>";
echo "</tr>";
echo "</form>";
echo "</table><br>";

if ($aja == "AJA" and isset($ajoon)) {

  include 'inc/pupeExcel.inc';

  $worksheet    = new pupeExcel();
  $format_bold = array("bold" => TRUE);
  $excelrivi    = 0;
  $excelsarake = 0;

  $worksheet->writeString($excelrivi, $excelsarake++, t("ABC"));
  $worksheet->writeString($excelrivi, $excelsarake++, t("Osaston luokka"));
  $worksheet->writeString($excelrivi, $excelsarake++, t("Ryhmän luokka"));
  $worksheet->writeString($excelrivi, $excelsarake++, $astusana);
  if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Toim_tuoteno"));
  $worksheet->writeString($excelrivi, $excelsarake++, t("Nimitys"));
  $worksheet->writeString($excelrivi, $excelsarake++, t("Osasto"));
  $worksheet->writeString($excelrivi, $excelsarake++, t("Ryhmä"));
  if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Merkki"));
  if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Malli"));
  if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Mallitarkenne"));
  if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Myyjä"));
  if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Ostaja"));
  if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Viim. saapumispvm"));
  if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Saldo"));
  if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Tulopvm"));
  $worksheet->writeString($excelrivi, $excelsarake++, $myykusana.t("tot"));
  $worksheet->writeString($excelrivi, $excelsarake++, t("Kate").t("tot"));
  $worksheet->writeString($excelrivi, $excelsarake++, t("Kate%"));
  $worksheet->writeString($excelrivi, $excelsarake++, t("Kateosuus"));
  if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Vararvo"));
  if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Kierto"));
  if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Kate")."% x ".t("Kierto"));
  $worksheet->writeString($excelrivi, $excelsarake++, $myykusana.t("määrä"));
  $worksheet->writeString($excelrivi, $excelsarake++, $myykusana.t("erä").t("määrä"));
  $worksheet->writeString($excelrivi, $excelsarake++, $myykusana.t("erä").$yhtiorow["valkoodi"]);
  $worksheet->writeString($excelrivi, $excelsarake++, $myykusana.t("rivit"));
  $worksheet->writeString($excelrivi, $excelsarake++, t("Puuterivit"));
  $worksheet->writeString($excelrivi, $excelsarake++, t("Palvelutaso"));
  if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Ostoerä").t("määrä"));
  if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Ostoerä").$yhtiorow["valkoodi"]);
  if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Ostorivit"));
  if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("KustannusMyynti"));
  if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("KustannusOsto"));
  if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("KustannusYht"));
  if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Kate-Kustannus"));
  if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Tuotepaikka"));
  $excelrivi++;

  if (count($haku) > 0) {
    foreach ($haku as $kentta => $arvo) {
      if (mb_strlen($arvo) > 0 and $kentta != 'kateosuus') {
        $abc_lisa  .= " and abc_aputaulu.$kentta like '%$arvo%'";
        $ulisa2 .= "&haku[$kentta]=$arvo";
      }
      if (mb_strlen($arvo) > 0 and $kentta == 'kateosuus') {
        $hav = "HAVING abc_aputaulu.kateosuus like '%$arvo%' ";
        $ulisa2 .= "&haku[$kentta]=$arvo";
      }
    }
  }

  $saapumispvmlisa = "";

  if (trim($saapumispp) != '' and trim($saapumiskk) != '' and trim($saapumisvv) != '') {
    $saapumispvm = "$saapumisvv-$saapumiskk-$saapumispp";
    $saapumispvmlisa = " and abc_aputaulu.saapumispvm <= '$saapumispvm' ";
  }

  $luokkalisa = "";

  if ($luokka != "") {
    $luokkalisa = " and luokka = '$luokka' ";
  }

  $query = "SELECT
            distinct luokka
            FROM abc_aputaulu
            WHERE yhtio = '$kukarow[yhtio]'
            and tyyppi  = '$abcchar'
            $luokkalisa
            ORDER BY luokka";
  $luokkares = pupe_query($query);

  // nämä määrittää kumpaan tauluun Joinataan, asiakas vai tuote
  $asiakas_join_array = array('AK', 'AM', 'AP', 'AR');
  $tuote_join_array = array('TK', 'TM', 'TP', 'TR', 'TV');

  if (in_array($abcchar, $asiakas_join_array)) {
    $analyysin_join = " JOIN asiakas on (abc_aputaulu.yhtio = asiakas.yhtio and abc_aputaulu.tuoteno = asiakas.tunnus) ";
  }
  elseif (in_array($abcchar, $tuote_join_array)) {
    $analyysin_join = " JOIN tuote USING (yhtio, tuoteno) ";
  }
  else {
    $analyysin_join = "";
  }

  while ($luokkarow = mysqli_fetch_assoc($luokkares)) {

    //kauden yhteismyynnit ja katteet
    $query = "SELECT
              sum(abc_aputaulu.summa) yhtmyynti,
              sum(abc_aputaulu.kate) yhtkate
              FROM abc_aputaulu
              {$analyysin_join}
              WHERE abc_aputaulu.yhtio = '{$kukarow["yhtio"]}'
              and abc_aputaulu.tyyppi  = '$abcchar'
              and abc_aputaulu.luokka  = '{$luokkarow["luokka"]}'
              $abc_lisa
              $lisa
              $saapumispvmlisa";
    $sumres = pupe_query($query);
    $sumrow = mysqli_fetch_assoc($sumres);

    $sumrow['yhtkate'] = (float) $sumrow['yhtkate'];
    $sumrow['yhtmyynti'] = (float) $sumrow['yhtmyynti'];

    $query = "SELECT abc_aputaulu.*,
              if ({$sumrow["yhtkate"]} = 0, 0, abc_aputaulu.kate / {$sumrow["yhtkate"]} * 100) kateosuus,
              abc_aputaulu.katepros * abc_aputaulu.varaston_kiertonop kate_kertaa_kierto,
              abc_aputaulu.kate - abc_aputaulu.kustannus_yht total
              FROM abc_aputaulu
              {$analyysin_join}
              WHERE abc_aputaulu.yhtio = '{$kukarow["yhtio"]}'
              and abc_aputaulu.tyyppi  = '$abcchar'
              and abc_aputaulu.luokka  = '{$luokkarow["luokka"]}'
              $saapumispvmlisa
              $abc_lisa
              $lisa
              $hav
              ORDER BY $abcwhat desc";
    $res = pupe_query($query);

    while ($row = mysqli_fetch_assoc($res)) {

      if (!$asiakasanalyysi) {
        $query = "SELECT group_concat(distinct toim_tuoteno) toim_tuoteno
                  FROM tuotteen_toimittajat
                  WHERE tuoteno = '$row[tuoteno]'
                  and yhtio     = '$kukarow[yhtio]'";
        $tuoresult = pupe_query($query);
        $tuorow = mysqli_fetch_assoc($tuoresult);

        $query = "SELECT distinct myyja, nimi
                  FROM kuka
                  WHERE yhtio='$kukarow[yhtio]'
                  AND myyja = '$row[myyjanro]'
                  AND myyja > 0
                  ORDER BY myyja";
        $myyjaresult = pupe_query($query);
        $myyjarow = mysqli_fetch_assoc($myyjaresult);

        $query = "SELECT distinct myyja, nimi
                  FROM kuka
                  WHERE yhtio='$kukarow[yhtio]'
                  AND myyja = '$row[ostajanro]'
                  AND myyja > 0
                  ORDER BY myyja";
        $ostajaresult = pupe_query($query);
        $ostajarow = mysqli_fetch_assoc($ostajaresult);
      }

      //haetaan varastopaikat ja saldot
      if ($asiakasanalyysi) {
        $query = "SELECT ytunnus, nimi
                  FROM asiakas
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$row[tuoteno]'";
      }
      elseif ($paikoittain == 'JOO') {
        $query = "SELECT concat_ws(' ', hyllyalue, hyllynro, hyllyvali, hyllytaso) paikka, saldo
                  from tuotepaikat
                  where tuoteno = '$row[tuoteno]'
                  and yhtio     = '$kukarow[yhtio]'";
      }
      else {
        $query = "SELECT sum(saldo) saldo
                  from tuotepaikat
                  where tuoteno = '$row[tuoteno]'
                  and yhtio     = '$kukarow[yhtio]'";

      }
      $paikresult = pupe_query($query);

      while ($paikrow = mysqli_fetch_assoc($paikresult)) {

        if ($asiakasanalyysi) {
          $row["tuoteno"] = $paikrow["ytunnus"];
          $row["nimitys"] = $paikrow["nimi"];
        }
        else {
          $row["nimitys"] = t_tuotteen_avainsanat($row, 'nimitys');
        }

        // Lisätään rivi exceltiedostoon
        $excelsarake = 0;

        $worksheet->writeString($excelrivi, $excelsarake++,  $ryhmanimet[$row["luokka"]]);
        $worksheet->writeString($excelrivi, $excelsarake++,  $ryhmanimet[$row["luokka_osasto"]]);
        $worksheet->writeString($excelrivi, $excelsarake++,  $ryhmanimet[$row["luokka_try"]]);
        $worksheet->writeString($excelrivi, $excelsarake++,  $row["tuoteno"]);
        if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++,  $tuorow["toim_tuoteno"]);
        $worksheet->writeString($excelrivi, $excelsarake++,  $row["nimitys"]);
        $worksheet->writeString($excelrivi, $excelsarake++,  $row["osasto"]);
        $worksheet->writeString($excelrivi, $excelsarake++,  $row["try"]);
        if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++,  $row["tuotemerkki"]);
        if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, $row["malli"]);
        if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, $row["mallitarkenne"]);
        if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, $myyjarow["nimi"]);
        if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, $ostajarow["nimi"]);
        if (!$asiakasanalyysi) $worksheet->write($excelrivi, $excelsarake++,  tv1dateconv($row["saapumispvm"]));
        if (!$asiakasanalyysi) $worksheet->writeNumber($excelrivi, $excelsarake++,  $row["saldo"]);
        if (!$asiakasanalyysi) $worksheet->write($excelrivi, $excelsarake++,  tv1dateconv($row["tulopvm"]));
        $worksheet->writeNumber($excelrivi, $excelsarake++,  sprintf('%.1f', $row["summa"]));
        $worksheet->writeNumber($excelrivi, $excelsarake++,  sprintf('%.1f', $row["kate"]));
        $worksheet->writeNumber($excelrivi, $excelsarake++, sprintf('%.1f', $row["katepros"]));
        $worksheet->writeNumber($excelrivi, $excelsarake++, sprintf('%.1f', $row["kateosuus"]));
        if (!$asiakasanalyysi) $worksheet->writeNumber($excelrivi, $excelsarake++, sprintf('%.1f', $row["vararvo"]));
        if (!$asiakasanalyysi) $worksheet->writeNumber($excelrivi, $excelsarake++, sprintf('%.1f', $row["varaston_kiertonop"]));
        if (!$asiakasanalyysi) $worksheet->writeNumber($excelrivi, $excelsarake++, sprintf('%.1f', $row["kate_kertaa_kierto"]));
        $worksheet->writeNumber($excelrivi, $excelsarake++, sprintf('%.1f', $row["kpl"]));
        $worksheet->writeNumber($excelrivi, $excelsarake++, sprintf('%.1f', $row["myyntierankpl"]));
        $worksheet->writeNumber($excelrivi, $excelsarake++, sprintf('%.1f', $row["myyntieranarvo"]));
        $worksheet->writeNumber($excelrivi, $excelsarake++, sprintf('%.0f', $row["rivia"]));
        $worksheet->writeNumber($excelrivi, $excelsarake++, sprintf('%.0f', $row["puuterivia"]));
        $worksheet->writeNumber($excelrivi, $excelsarake++, sprintf('%.1f', $row["palvelutaso"]));
        if (!$asiakasanalyysi) $worksheet->writeNumber($excelrivi, $excelsarake++, sprintf('%.1f', $row["ostoerankpl"]));
        if (!$asiakasanalyysi) $worksheet->writeNumber($excelrivi, $excelsarake++, sprintf('%.1f', $row["ostoeranarvo"]));
        if (!$asiakasanalyysi) $worksheet->writeNumber($excelrivi, $excelsarake++, sprintf('%.0f', $row["osto_rivia"]));
        if (!$asiakasanalyysi) $worksheet->writeNumber($excelrivi, $excelsarake++, sprintf('%.1f', $row["kustannus"]));
        if (!$asiakasanalyysi) $worksheet->writeNumber($excelrivi, $excelsarake++, sprintf('%.1f', $row["kustannus_osto"]));
        if (!$asiakasanalyysi) $worksheet->writeNumber($excelrivi, $excelsarake++, sprintf('%.1f', $row["kustannus_yht"]));
        if (!$asiakasanalyysi) $worksheet->writeNumber($excelrivi, $excelsarake++, sprintf('%.1f', $row["total"]));
        if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, $paikrow["paikka"]);
        $excelrivi++;
      }
    }
  }

  $excelnimi = $worksheet->close();

  echo "<br><br><table>";
  echo "<tr><th>".t("Tallenna Excel").":</th>";
  echo "<form method='post' class='multisubmit'>";
  echo "<input type='hidden' name='exceltee' value='lataa_tiedosto'>";
  echo "<input type='hidden' name='kaunisnimi' value='ABC_listaus.xlsx'>";
  echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
  echo "<input type='hidden' name='toim' value='$toim'>";
  echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
  echo "</table><br>";
}
