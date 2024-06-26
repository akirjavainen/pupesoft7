<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

require "../inc/parametrit.inc";

if (isset($tee)) {
  if ($tee == "lataa_tiedosto") {
    readfile("/tmp/".$tmpfilenimi);
    exit;
  }
}
else {
  echo "<font class='head'>".t("Myynti asiakkaittain").":</font><hr>";

  //Käyttöliittymä
  if (!isset($kka))
    $kka = date("m", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
  if (!isset($vva))
    $vva = date("Y", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
  if (!isset($ppa))
    $ppa = date("d", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));

  if (!isset($kkl))
    $kkl = date("m");
  if (!isset($vvl))
    $vvl = date("Y");
  if (!isset($ppl))
    $ppl = date("d");

  $chk = "";
  $chk1 = "";

  if (trim($summaa) == 'summaa') {
    $chk = "CHECKED";
  }

  if (trim($summaa) == 'summaa_ytunnus') {
    $chk1 = "CHECKED";
  }

  echo "<form method='post'>";
  echo "<input type='hidden' name='matee' value='kaikki'>";

  echo "<table style='display:inline;padding-right:4px;'>";
  echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
        <td><input type='text' name='ppa' value='$ppa' size='3'></td>
        <td><input type='text' name='kka' value='$kka' size='3'></td>
        <td><input type='text' name='vva' value='$vva' size='5'></td>
        </tr><tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
        <td><input type='text' name='ppl' value='$ppl' size='3'></td>
        <td><input type='text' name='kkl' value='$kkl' size='3'></td>
        <td><input type='text' name='vvl' value='$vvl' size='5'></td></tr>";

  echo "<tr><th>".t("Summaa myynnit per asiakas").":</th><td colspan='3'><input type='radio' name='summaa' value='summaa' $chk></td></tr>";
  echo "<tr><th>".t("Summaa myynnit per ytunnus").":</th><td colspan='3'><input type='radio' name='summaa' value='summaa_ytunnus' $chk1></td></tr>";

  echo "</table>";

  // Monivalintalaatikot (osasto, try tuotemerkki...)
  // Määritellään mitkä latikot halutaan mukaan
  $monivalintalaatikot = array("OSASTO", "TRY");

  require "../tilauskasittely/monivalintalaatikot.inc";

  echo "<br><input type='submit' name='AJA' value='".t("Aja raportti")."'>";
  echo "</form><br><br>";

  if ($matee != '' and isset($AJA)) {

    include 'inc/pupeExcel.inc';

    $worksheet    = new pupeExcel();
    $format_bold = array("bold" => TRUE);
    $excelrivi    = 0;

    $worksheet->writeString($excelrivi, 0, t("Myynti asiakkaittain"));
    $excelrivi ++;

    $select = "lasku.liitostunnus, asiakas.piiri, tuote.aleryhma, max(lasku.ytunnus) ytunnus, max(lasku.nimi) nimi, max(lasku.nimitark) nimitark, ";
    $group  = "lasku.liitostunnus, asiakas.piiri, tuote.aleryhma";

    if ($summaa == 'summaa') {
      $select = "lasku.liitostunnus, max(asiakas.ytunnus) ytunnus, max(asiakas.nimi) nimi, max(asiakas.nimitark) nimitark, ";
      $group  = "lasku.liitostunnus";
    }

    if ($summaa == 'summaa_ytunnus') {
      $select = "lasku.ytunnus, max(asiakas.nimi) nimi, max(asiakas.nimitark) nimitark, ";
      $group  = "lasku.ytunnus";
    }

    // MUOKKAUS: lisatty asiakas.ryhma = '2' kyselyyn:
    $total_summa = 0; // MUOKKAUS: lisatty
    $query = "SELECT $select
              sum(tilausrivi.rivihinta) summa,
              sum(tilausrivi.kate) kate,
              sum(tilausrivi.kpl) kpl
              FROM lasku
              JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus and tilausrivi.tyyppi = 'L')
              JOIN asiakas ON (asiakas.yhtio = tilausrivi.yhtio and asiakas.tunnus = lasku.liitostunnus)
              JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno)
              WHERE lasku.yhtio = '$kukarow[yhtio]'
              and lasku.tila    = 'U'
              and lasku.alatila = 'X'
              and lasku.tapvm   >= '$vva-$kka-$ppa'
              and lasku.tapvm   <= '$vvl-$kkl-$ppl'
	      and asiakas.ryhma = '2'
              $lisa
              GROUP BY $group
              ORDER BY nimi, nimitark, ytunnus";
    $result = pupe_query($query);

    if (mysqli_num_rows($result) < 2000) {
      echo "<table>";
      echo "<tr>";
      echo "<th>".t("Ytunnus")."</th>";
      echo "<th>".t("Nimi")."</th>";
      echo "<th>".t("Nimitark")."</th>";
      if ($summaa == '') echo "<th>".t("Aleryhmä")."</th>";

      if ($summaa == '') {
        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          echo "<th>", t("Alennus"), "{$alepostfix}</th>";
        }
      }

      if ($summaa == '') echo "<th>".t("Piiri")."</th>";
      echo "<th>".t("Määrä")."</th>";
      echo "<th>".t("Summa")."</th>";

      // MUOKKAUS: kommentoitu ulos:
      //echo "<th>".t("Kate")."</th>";
      //echo "<th>".t("Katepros")."</th>";
    }
    else {
      echo "<br><font class='error'>".t("Hakutulos oli liian suuri")."!</font><br>";
      echo "<font class='error'>".t("Tallenna/avaa tulos excelissä")."!</font><br><br>";
    }

    $sarake = 0;
    $worksheet->write($excelrivi, $sarake++, t("Ytunnus"), $format_bold);
    $worksheet->write($excelrivi, $sarake++, t("Nimi"), $format_bold);
    $worksheet->write($excelrivi, $sarake++, t("Nimitark"), $format_bold);
    if ($summaa == '') $worksheet->write($excelrivi, $sarake++, t("Aleryhmä"), $format_bold);

    if ($summaa == '') {
      for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
        $worksheet->write($excelrivi, $sarake++, t("Alennus").$alepostfix, $format_bold);
      }
    }

    if ($summaa == '') $worksheet->write($excelrivi, $sarake++, t("Piiri"), $format_bold);
    $worksheet->write($excelrivi, $sarake++, t("Määrä"), $format_bold);
    $worksheet->write($excelrivi, $sarake++, t("Summa"), $format_bold);
    $worksheet->write($excelrivi, $sarake++, t("Kate"), $format_bold);
    $worksheet->write($excelrivi, $sarake++, t("Katepros"), $format_bold);

    $excelrivi++;

    while ($lrow = mysqli_fetch_array($result)) {
      if ($summaa == '') {

        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          ${'ale'.$alepostfix} = 0;
        }

        //haetaan tuoteryhmmän alennusryhmä
        $query = "SELECT alennus, alennuslaji
                  FROM asiakasalennus
                  WHERE yhtio='$kukarow[yhtio]' and ryhma = '$lrow[aleryhma]' and ytunnus = '$lrow[ytunnus]'";
        $hresult = pupe_query($query);

        if (mysqli_num_rows($hresult) != 0) {
          $hrow = mysqli_fetch_array($hresult);

          if ($hrow["alennus"] > 0) {
            ${'ale'.$hrow["alennuslaji"]} = $hrow[0];
          }
        }
        else {
          // Pudotaan perusalennukseen
          $query = "SELECT alennus
                    FROM perusalennus
                    WHERE yhtio='$kukarow[yhtio]' and ryhma = '$lrow[aleryhma]'";
          $hresult = pupe_query($query);

          if (mysqli_num_rows($hresult) != 0) {
            $hrow=mysqli_fetch_array($hresult);

            if ($hrow["alennus"] > 0) {
              $ale1 = $hrow[0];
            }

            for ($alepostfix = 2; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
              ${'ale'.$alepostfix} = '';
            }
          }
        }
      }

      $katepros=0;

      if ($lrow["summa"] != 0) {
        $katepros = round($lrow["kate"]/$lrow["summa"]*100, 2);
      }

      if (mysqli_num_rows($result) < 2000) {
        echo "<tr class='aktiivi'>";
        echo "<td>".$lrow["ytunnus"]."</td>";
        echo "<td>".$lrow["nimi"]."</td>";
        echo "<td>".$lrow["nimitark"]."</td>";
        if ($summaa == '') echo "<td>$lrow[aleryhma]</td>";

        if ($summaa == '') {
          for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
            echo "<td>", ${'ale'.$alepostfix}, "</td>";
          }
        }

        if ($summaa == '') echo "<td align='right'>".$lrow["piiri"]."</td>";
        echo "<td align='right'>".sprintf("%.2f", $lrow["kpl"])."</td>";
        echo "<td align='right'>".sprintf("%.2f", $lrow["summa"])."</td>";
	$total_summa += $lrow["summa"]; // MUOKKAUS: lisatty

	// MUOKKAUS: kommentoitu ulos:
        //echo "<td align='right'>".sprintf("%.2f", $lrow["kate"])."</td>";
        //echo "<td align='right'>".sprintf("%.2f", $katepros)."%</td>";
        echo "</tr>";
      }

      $sarake = 0;
      $worksheet->writeString($excelrivi, $sarake++, $lrow["ytunnus"]);
      $worksheet->write($excelrivi, $sarake++, $lrow["nimi"]);
      $worksheet->write($excelrivi, $sarake++, $lrow["nimitark"]);
      if ($summaa == '') $worksheet->write($excelrivi, $sarake++, $lrow["aleryhma"]);

      if ($summaa == '') {
        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          $worksheet->write($excelrivi, $sarake++, ${'ale'.$alepostfix});
        }
      }

      if ($summaa == '') $worksheet->write($excelrivi, $sarake++, $lrow["piiri"]);
      $worksheet->writeNumber($excelrivi, $sarake++, $lrow["kpl"]);
      $worksheet->writeNumber($excelrivi, $sarake++, $lrow["summa"]);
      $worksheet->writeNumber($excelrivi, $sarake++, $lrow["kate"]);
      $worksheet->writeNumber($excelrivi, $sarake++, $katepros);

      $excelrivi++;
    }

    if (mysqli_num_rows($result) < 2000) echo "</table>";

    $excelnimi = $worksheet->close();

    echo "<br><br><table>";
    echo "<tr><th>".t("Tallenna tulos").":</th>";
    echo "<form method='post' class='multisubmit'>";
    echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
    echo "<input type='hidden' name='kaunisnimi' value='Myyntiasiakkaittain.xlsx'>";
    echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
    echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
    echo "</table><br>";
  }

  echo "<br>Kokonaismyynti: ".round($total_summa, 2)." euroa (ALV 0 %)<br>\n"; // MUOKKAUS: lisatty
  require "inc/footer.inc";
}
