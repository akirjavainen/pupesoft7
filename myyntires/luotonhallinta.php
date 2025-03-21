<?php

require "../inc/parametrit.inc";

echo "<font class='head'>".t("Luotonhallinta")."</font><hr>";

echo "<script>
    function toggleAll(toggleBox) {
      var currForm = toggleBox.form;
      var isChecked = toggleBox.checked;
      for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {
        if (currForm.elements[elementIdx].type == 'checkbox') {
          currForm.elements[elementIdx].checked = isChecked;
        }
      }
    }
    </script>";

if ($yhtiorow["myyntitilaus_saatavat"] == "Y") {
  // käsitellään luottorajoja per ytunnus
  $kasittely_periaate = "asiakas.ytunnus";
}
else {
  // käsitellään luottorajoja per asiakas
  $kasittely_periaate = "asiakas.tunnus";
}

$paivitys_oikeus = tarkista_oikeus("luotonhallinta.php", '', 1);

if (isset($edytunnus) and isset($ytunnus) and $edytunnus != $ytunnus) {
  unset($asiakasid);
}

if (isset($muutparametrit)) {
  $muut         = explode('#', $muutparametrit);
  $pvm_alku       = $muut[0];
  $pvm_loppu       = $muut[1];
  $minimi_myynti     = $muut[2];
  $luottorajauksia   = $muut[3];
  $luottovakuutettu   = $muut[4];
}

$asiakasrajaus = "";
$muutparametrit = "$pvm_alku#$pvm_loppu#$minimi_myynti#$luottorajauksia#$luottovakuutettu";

if ($ytunnus != '') {

  require "inc/asiakashaku.inc";

  if ($ytunnus == '') {
    echo "<br><br>";
    $tee = "";
  }
  else {
    if ($yhtiorow["myyntitilaus_saatavat"] == "Y") {
      $asiakasrajaus = " and asiakas.ytunnus = '$ytunnus' ";
    }
    else {
      $asiakasrajaus = " and asiakas.tunnus = '$asiakasid' ";
    }

    $luottorajauksia = "Z";
  }
}

if ($luottovakuutettu == "K") {
  $luottolisa = "K";
  $checked = "CHECKED";
}
else {
  $luottolisa = "";
  $checked = "";
}

$update_message = array();
$raja_select = array();

if (!isset($pvm_alku)) {
  $pvm_alku = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
}
if (!isset($pvm_loppu)) {
  $pvm_loppu = date("Y-m-d");
}
if (!isset($minimi_myynti)) {
  $minimi_myynti = 0;
}
if (isset($luottorajauksia)) {
  $raja_select[$luottorajauksia] = "selected";
}
if (!isset($muutosprosentti)) {
  $muutosprosentti = 10;
}
if ($luottorajauksia == "A" and (float) $muutosprosentti == 0) {
  echo "<font class='error'>".t("Virheellinen muutosprosentti")."</font>";
  $tee = "";
}

echo "<form name='haku' action='luotonhallinta.php' method='post' autocomplete='off'>";
echo "<input type='hidden' name='tee' value='1'>";
echo "<input type='hidden' name='lopetus' value='$lopetus'>";

$monivalintalaatikot = array("ASIAKASOSASTO", "ASIAKASRYHMA", "ASIAKASPIIRI");
require "tilauskasittely/monivalintalaatikot.inc";

echo "<br>";
echo "<table>";

echo "<tr>";
echo "<th>".t("Myynti")." ".t("Alkupvm")."</th>";
echo "<td><input type='date' required name='pvm_alku' value='$pvm_alku'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Myynti")." ".t("Loppupvm")."</th>";
echo "<td><input type='date' required name='pvm_loppu' value='$pvm_loppu'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Minimi myynti")." $yhtiorow[valkoodi]</th>";
echo "<td><input type='number' name='minimi_myynti' value='$minimi_myynti'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Luottoraja rajauksia")."</th>";
echo "<td><select name='luottorajauksia'>";
echo "<option value='A' $raja_select[A]>".t("Näytä asiakkaat, joiden luottoraja eroaa myynnistä yli muutosprosentin")."</option>";
echo "<option value='B' $raja_select[B]>".t("Näytä asiakkaat, jotka ovat ylittäneet luottorajan")."</option>";
echo "<option value='C' $raja_select[C]>".t("Näytä asiakkaat, joilla ei ole luottorajaa, mutta myyntiä")."</option>";
echo "<option value='D' $raja_select[D]>".t("Näytä asiakkaat, joilla on luottoraja, mutta ei myyntiä")."</option>";
echo "<option value='E' $raja_select[E]>".t("Näytä asiakkaat, jotka ovat myyntikiellossa")."</option>";
echo "<option value='F' $raja_select[F]>".t("Näytä asiakkaat, jotka ovat ylittäneet luottorajan, mutta eivät ole myyntikiellossa")."</option>";
echo "<option value='G' $raja_select[G]>".t("Näytä asiakkaat, joilla on avoimia maksukehotuksia, joita on kehotettu maksamaan ainakin kerran")."</option>";
echo "<option value='H' $raja_select[H]>".t("Näytä asiakkaat, joilla on avoimia maksukehotuksia, joita on kehotettu maksamaan ainakin kaksi kertaa")."</option>";
echo "<option value='I' $raja_select[I]>".t("Näytä asiakkaat, joilla on avoimia maksukehotuksia, joita on kehotettu maksamaan ainakin kolme kertaa")."</option>";
echo "<option value='Z' $raja_select[Z]>".t("Näytä kaikki asiakkaat")."</option>";
echo "</select></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Muutosprosentti")."</th>";
echo "<td><input type='number' name='muutosprosentti' value='$muutosprosentti'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Vain asiakas")."</th>";
echo "<td><input type='text' name='ytunnus' value='$ytunnus'><input type='hidden' name='asiakasid' value='$asiakasid'>";
if (isset($ytunnus) and $ytunnus != "") echo "<input type='hidden' name='edytunnus' value='$ytunnus'>";
echo "$asiakasrow[nimi]</td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Vain luottovakuutetut")."</th>";
echo "<td><input type='checkbox' name='luottovakuutettu' value='K' $checked></td>";
echo "</tr>";

echo "</table>";

echo "<br>";

echo "<input type='submit' value='".t("Näytä")."'>";
echo "</form>";

echo "<br />";
echo "<br />";

echo "<form method='post' name='sendfile' enctype='multipart/form-data'>";
echo "<input type='hidden' name='tee'    value = '3'>";

if ($paivitys_oikeus) {
  echo t("tai"), "...";
  echo "<br />";
  echo "<br />";

  echo "<font class='message'>", t("Päivitä asiakkaiden luottotietoja tiedostosta"), "</font><br />";
  echo "<font class='info'>", t("Otsikot: ytunnus, asiakasnro, luottoraja"), "</font><br />";
  echo "<table>";
  echo "<tr><th>", t("Valitse tiedosto"), "</th><td><input type='file' name='userfile' /></td><td class='back'><input type='submit' value='", t("Lähetä"), "' /></td></tr>";
  echo "</table>";
  echo "</form>";
}
echo "<br>";
echo "<br>";

if ($tee == "3" and $paivitys_oikeus) {

  if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {

    $kasiteltava_tiedoto_path = $_FILES['userfile']['tmp_name'];
    $path_parts = pathinfo($_FILES['userfile']['name']);
    $ext = mb_strtoupper($path_parts['extension']);

    $excelrivit = pupeFileReader($kasiteltava_tiedoto_path, $ext);

    echo "<font class='message'>".t("Luetaan lähetetty tiedosto")."...<br><br></font>";

    // Otetaan otsikot pois
    array_shift($excelrivit);

    $cnt = 0;

    foreach ($excelrivit as $rivinumero => $rivi) {

      // Tarkistetaan että tiedostossa ei ole ylimääräisiä rivejä
      if ($rivi[3] != "") {
        echo "<font class='error'>".t("Tiedostossa liikaa sarakkeita")." ! ".t("Tarkista tiedosto")."!</font><br />";
        exit;
      }

      $ytunnus  = sanitize_string(trim($rivi[0]));
      $asiakasnro  = trim($rivi[1]) != "" ? (int) trim($rivi[1]) : "";
      $luottoraja = (float) $rivi[2];

      if ($ytunnus == "" and $asiakasnro == "") continue;

      $ytunnuslisa = $ytunnus != "" ? "AND ytunnus = '{$ytunnus}'" : "";
      $asiakasnrolisa = $asiakasnro != "" ? "AND asiakasnro = '{$asiakasnro}'" : "";

      $query = "UPDATE asiakas SET
                luottoraja  = '{$luottoraja}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                {$ytunnuslisa}
                {$asiakasnrolisa}";
      pupe_query($query);

      $cnt += mysqli_affected_rows($link);
    }

    $plural = $cnt == 1 ? "rivi" : "riviä";
    $classi = $cnt > 0 ? "ok" : "error";

    echo "<font class='{$classi}'>", t("Päivitettiin %d %s", '', $cnt, $plural), ".</font><br />";
  }
}

// päivitetään asiakkaat
if ($tee == "2" and $paivitys_oikeus) {

  foreach ($luottoraja as $ytunnus => $summa) {

    $summa = trim(str_replace(",", ".", $summa));

    // Katsotaan onko tiedot muuttuneet
    if ($summa == $alkuperainen_luottoraja[$ytunnus] and $myyntikielto[$ytunnus] == $alkuperainen_myyntikielto[$ytunnus]) {
      continue;
    }

    // Katsotaan, että tiedot ovat oikein
    if (!is_numeric($summa)) {
      continue;
    }

    // Varmistetaan checkboxinkin value
    if ($myyntikielto[$ytunnus] != "K" and $myyntikielto[$ytunnus] != "") {
      continue;
    }

    $summa = (float) $summa;
    $ytunnus = sanitize_string($ytunnus);

    $query = "UPDATE asiakas SET
              myyntikielto = '$myyntikielto[$ytunnus]',
              luottoraja   = '$summa'
              WHERE yhtio  = '$kukarow[yhtio]'
              AND $kasittely_periaate = '$ytunnus'";
    $asiakasres = pupe_query($query);

    if (mysqli_affected_rows($link) != 0) {
      $update_message[$ytunnus] = t("Asiakastiedot päivitetty!");
    }
  }

  $tee = "1";

}

// näytetään asiakkaat
if ($tee == "1") {

  // haetaan kaikki yrityksen asiakkaat
  $query  = "SELECT $kasittely_periaate ytunnus,
             group_concat(distinct tunnus) liitostunnukset,
             group_concat(distinct nimi ORDER BY nimi SEPARATOR '<br>') nimi,
             group_concat(distinct toim_nimi ORDER BY nimi SEPARATOR '<br>') toim_nimi,
             min(luottoraja) luottoraja,
             min(myyntikielto) myyntikielto,
             min(ytunnus) tunniste
             FROM asiakas
             WHERE yhtio           = '$kukarow[yhtio]'
             AND laji             != 'P'
             AND luottovakuutettu  = '$luottolisa'
             $asiakasrajaus
             $lisa
             GROUP BY 1";
  $asiakasres = pupe_query($query);

  echo "<form name='paivitys' action='luotonhallinta.php' method='post' autocomplete='off'>";
  echo "<input type='hidden' name='tee' value='2'>";
  echo "<input type='hidden' name='pvm_alku' value='$pvm_alku'>";
  echo "<input type='hidden' name='pvm_loppu' value='$pvm_loppu'>";
  echo "<input type='hidden' name='minimi_myynti' value='$minimi_myynti'>";
  echo "<input type='hidden' name='luottorajauksia' value='$luottorajauksia'>";
  echo "<input type='hidden' name='muutosprosentti' value='$muutosprosentti'>";
  echo "<input type='hidden' name='ytunnus' value='$ytunnus'>";
  echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
  echo "<input type='hidden' name='lopetus' value='$lopetus'>";

  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Ytunnus")."</th>";
  echo "<th>".t("Laskutusnimi")."<br>".t("Toimitusnimi")."</th>";
  echo "<th>".t("Verollinen myynti")."<br>".t("Veroton myynti")."</th>";
  echo "<th>".t("Avoimet")."<br>".t("laskut")."</th>";
  echo "<th>".t("Avoimet")."<br>".t("tilaukset")."</th>";
  echo "<th>".t("Kaatotili")."</th>";
  echo "<th>".t("Luottotilanne nyt")."</th>";
  echo "<th>".t("Luottoraja")."<br>$yhtiorow[valkoodi]</th>";
  echo "<th>".t("Myyntikielto")."</th>";

  if ($luottorajauksia == 'G' or $luottorajauksia == 'H' or $luottorajauksia == 'I') {
    echo "<th>".t("Maksukehotuskertoja")."</th>";
    echo "<th>".t("Laskuja")."</th>";
  }

  echo "</tr>";

  $query_alennuksia = generoi_alekentta('M');

  while ($asiakasrow = mysqli_fetch_array($asiakasres)) {

    // haetaan asiakkaan myynnit halutulta ajalta
    $query = "SELECT ifnull(sum(summa), 0) summa, ifnull(sum(arvo), 0) arvo
              FROM lasku USE INDEX (yhtio_tila_liitostunnus_tapvm)
              WHERE lasku.yhtio      = '$kukarow[yhtio]'
              AND lasku.tila         = 'U'
              AND lasku.liitostunnus IN ($asiakasrow[liitostunnukset])
              AND lasku.tapvm        >= '$pvm_alku'
              AND lasku.tapvm        <= '$pvm_loppu'";
    $myyntires = pupe_query($query);
    $myyntirow = mysqli_fetch_array($myyntires);

    // Näytä asiakkaat, joiden luottorajaa tulisi tarkistaa (myynnin/luottorajan ero yli muutosprosentin)
    // MUOKKAUS: BUGIKORJAUS (jako nollalla):
    if ((float)$asiakasrow["luottoraja"] > 0 and $luottorajauksia == "A" and (abs(100 - ((float)$myyntirow["summa"] / (float)$asiakasrow["luottoraja"] * 100)) < $muutosprosentti or $asiakasrow["luottoraja"] == 0)) {
      continue;
    }

    // Näytä asiakkaat, jotka ovat ylittäneet luottorajan
    if ($luottorajauksia == "B" and ($myyntirow["summa"] < $asiakasrow["luottoraja"] or $asiakasrow["luottoraja"] == 0)) {
      continue;
    }

    // Näytä asiakkaat, joilla ei ole luottorajaa, mutta myyntiä
    if ($luottorajauksia == "C" and ($asiakasrow['luottoraja'] != 0 or $myyntirow["summa"] == 0)) {
      continue;
    }

    // Näytä asiakkaat, joilla on luottoraja, mutta ei myyntiä
    if ($luottorajauksia == "D" and ($asiakasrow['luottoraja'] == 0 or $myyntirow["summa"] != 0)) {
      continue;
    }

    // Näytä asiakkaat, jotka ovat myyntikiellossa
    if ($luottorajauksia == "E" and $asiakasrow['myyntikielto'] != "K") {
      continue;
    }

    // Näytä asiakkaat, jotka ovat ylittäneet luottorajan, mutta eivät ole myyntikiellossa
    if ($luottorajauksia == "F" and ($myyntirow["summa"] < $asiakasrow["luottoraja"] or $asiakasrow['myyntikielto'] == "K" or $asiakasrow["luottoraja"] == 0)) {
      continue;
    }

    if ($luottorajauksia == 'G' or $luottorajauksia == 'H' or $luottorajauksia == 'I') {

      if ($yhtiorow["myyntitilaus_saatavat"] == "Y") {
        // käsitellään luottorajoja per ytunnus
        $extraehto = " AND lasku.ytunnus = '$asiakasrow[ytunnus]'";
      }
      else {
        // käsitellään luottorajoja per asiakas
        $extraehto = " AND lasku.liitostunnus = '$asiakasrow[tunnus]'";
      }

      if ($luottorajauksia == 'G') {
        $having_ehto = " HAVING karhukerrat >= 1 ";
      }
      if ($luottorajauksia == 'H') {
        $having_ehto = " HAVING karhukerrat >= 2 ";
      }
      if ($luottorajauksia == 'I') {
        $having_ehto = " HAVING karhukerrat >= 3 ";
      }

      // haetaan uusin karhukierros/karhukerta
      $query = "SELECT count(karhu_lasku.ltunnus) as karhukerrat
                FROM lasku
                JOIN karhu_lasku ON (lasku.tunnus = karhu_lasku.ltunnus)
                JOIN karhukierros ON (karhukierros.tunnus = karhu_lasku.ktunnus and karhukierros.yhtio = lasku.yhtio and karhukierros.tyyppi = '')
                WHERE lasku.yhtio = '$kukarow[yhtio]'
                AND lasku.tila    = 'U'
                AND lasku.alatila = 'X'
                AND lasku.mapvm   = '0000-00-00'
                $extraehto
                GROUP BY karhu_lasku.ltunnus
                $having_ehto
                ORDER BY karhukerrat DESC";
      $laskures = pupe_query($query);

      if (mysqli_num_rows($laskures) > 0) {
        $karhuttu = mysqli_fetch_array($laskures);
        $ulostulo = $karhuttu[0];
        $kerrat = mysqli_num_rows($laskures);
      }
      else {
        continue;
      }
    }

    if ($asiakasrow["myyntikielto"] != "") {
      $chk = "CHECKED";
    }
    else {
      $chk = "";
    }

    // Avoimet laskut
    $query = "SELECT sum(lasku.summa - lasku.saldo_maksettu) laskuavoinsaldo
              FROM lasku use index (yhtio_tila_mapvm)
              WHERE lasku.yhtio      = '$kukarow[yhtio]'
              AND lasku.tila         = 'U'
              AND lasku.alatila      = 'X'
              AND lasku.mapvm        = '0000-00-00'
              AND lasku.liitostunnus IN ($asiakasrow[liitostunnukset])";
    $avoimetlaskutres = pupe_query($query);
    $avoimetlaskutrow = mysqli_fetch_assoc($avoimetlaskutres);

    // Kaatotili
    $query = "SELECT
              sum(round(summa*if(kurssi=0, 1, kurssi),2)) summa
              FROM suoritus
              WHERE yhtio        = '$kukarow[yhtio]'
              and ltunnus        > 0
              and kohdpvm        = '0000-00-00'
              and asiakas_tunnus in ($asiakasrow[liitostunnukset])";
    $kaatotilires = pupe_query($query);
    $kaatotilirow = mysqli_fetch_assoc($kaatotilires);

    // Avoimet tilaukset
    $query = "SELECT
              round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_alennuksia}),2) tilausavoinsaldo
              FROM lasku
              JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi IN ('L','W'))
              WHERE lasku.yhtio      = '$kukarow[yhtio]'
              AND ((lasku.tila in ('L', 'N') and lasku.alatila != 'X')      # Kaikki myyntitilaukset, paitsi laskutetut
                OR (lasku.tila = 'V' and lasku.alatila in ('','A','C','J','V'))  # Valmistukset
              )
              AND lasku.liitostunnus in ($asiakasrow[liitostunnukset])";
    $avoimettilauksetres = pupe_query($query);
    $avoimettilauksetrow = mysqli_fetch_assoc($avoimettilauksetres);

    echo "<tr class='aktiivi'>";
    echo "<td>$asiakasrow[tunniste]</td>";
    echo "<td>$asiakasrow[nimi]<br>$asiakasrow[toim_nimi]</td>";
    echo "<td align='right'>$myyntirow[summa]<br>$myyntirow[arvo]</td>";
    echo "<td align='right'>$avoimetlaskutrow[laskuavoinsaldo]</td>";
    echo "<td align='right'>$avoimettilauksetrow[tilausavoinsaldo]</td>";
    echo "<td align='right'>$kaatotilirow[summa]</td>";

    // Lasketaan luottotilanne nyt
    $luottotilanne_nyt = round($asiakasrow["luottoraja"] - $avoimetlaskutrow["laskuavoinsaldo"] + $kaatotilirow["summa"] - $avoimettilauksetrow["tilausavoinsaldo"], 2);

    echo "<td align='right'>$luottotilanne_nyt</td>";

    if ($paivitys_oikeus) {
      echo "<td align='right'><input style='text-align:right' type='text' name='luottoraja[$asiakasrow[ytunnus]]' value='$asiakasrow[luottoraja]' size='11'></td>";
      echo "<td align='right'><input type='checkbox' name='myyntikielto[$asiakasrow[ytunnus]]' value='K' $chk></td>";
    }
    else {
      echo "<td>$asiakasrow[luottoraja]</td>";
      echo "<td>$asiakasrow[myyntikielto]</td>";
    }

    if ($luottorajauksia == 'G' or $luottorajauksia == 'H' or $luottorajauksia == 'I') {
      echo "<td align='right'>1 - {$ulostulo}</td>";
      echo "<td align='right'>{$kerrat}</td>";
    }

    echo "<td class='back'><font class='error'>".$update_message[$asiakasrow["ytunnus"]]."</font></td>";
    echo "</tr>";

    // Välitetään alkuperäiset arvot, ettei päivitetä asiakasta suotta
    echo "<input type='hidden' name='alkuperainen_luottoraja[$asiakasrow[ytunnus]]' value='$asiakasrow[luottoraja]'>";
    echo "<input type='hidden' name='alkuperainen_myyntikielto[$asiakasrow[ytunnus]]' value='$asiakasrow[myyntikielto]'>";
  }

  if ($paivitys_oikeus) {
    echo "<tr><td class='back' colspan='9' align='right'>".t("Ruksaa kaikki")." &raquo; <input type='checkbox' name='ruksaakaikki' onclick='toggleAll(this)'></td></tr>";
  }

  echo "</table>";
  if ($paivitys_oikeus) {
    echo "<input type='submit' value='".t("Päivitä luottorajat")."'>";
  }
  echo "</form>";
}

require "inc/footer.inc";
