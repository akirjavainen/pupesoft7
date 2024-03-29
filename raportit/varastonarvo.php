<?php

// Tämä skripti käyttää slave-tietokantapalvelinta
$useslave = 1;

require "../inc/parametrit.inc";

if (!isset($merkki)) $merkki = "";
if (!isset($tryittain)) $tryittain = "";

echo "<font class='head'>", t("Varastonarvo"), "</font><hr>";

echo " <!-- Enabloidaan shiftillä checkboxien chekkaus //-->
    <script src='../inc/checkboxrange.js'></script>

    <script language='javascript' type='text/javascript'>
      $(document).ready(function(){
        $(\".shift\").shiftcheckbox();
      });
    </script>";

if ($piilotetut_varastot == 'on') {
  $piilotetut_select = "checked='checked'";
}
else {
  $piilotetut_select = "";
}
echo "<table>";
echo "<tr>";
echo "<th valign=top>".t('Näytä piilotetut varastot')."</th>";

echo "<td>";
echo "<form method='POST'>";
echo "<input type='checkbox' {$piilotetut_select} name='piilotetut_varastot' onclick='submit();' />";
echo "</form>";
echo "</td>";
echo "</tr>";
echo "</table>";
echo "<br />";

// piirrellään formi
echo "<form method='post'>";

$monivalintalaatikot = array("OSASTO", "TRY", "TUOTEMERKKI");
$noautosubmit = TRUE;

require "tilauskasittely/monivalintalaatikot.inc";

echo "<br><table>";

if ($tryittain != '') {
  $chk2 = 'checked';
}
else {
  $chk2 = '';
}

if ($merkki != '') {
  $chk = 'checked';
}
else {
  $chk = '';
}

echo "<tr>";
echo "<th>", t("Aja tuotemerkeittäin"), ":</th><td><input type='checkbox' name='merkki' $chk></td>";
echo "</tr>";

echo "<tr>";
echo "<th>", t("Tai tuoteryhmittäin"), ":</th><td><input type='checkbox' name='tryittain' $chk2></td>";
echo "</tr>";

$epakur_chk1 = "";
$epakur_chk2 = "";
$epakur_chk3 = "";

if ($epakur == 'kaikki') {
  $epakur_chk1 = ' selected';
}
elseif ($epakur == 'epakur') {
  $epakur_chk2 = ' selected';
}
elseif ($epakur == 'ei_epakur') {
  $epakur_chk3 = ' selected';
}

echo "<tr>";
echo "<th>", t("Näytettävät tuotteet"), ":</th><td>";
echo "<select name='epakur'>";
echo "<option value='kaikki'    $epakur_chk1>", t("Näytä kaikki tuotteet"), "</option>";
echo "<option value='epakur'    $epakur_chk2>", t("Näytä vain epäkurantit tuotteet"), "</option>";
echo "<option value='ei_epakur' $epakur_chk3>", t("Näytä varastonarvoon vaikuttavat tuotteet"), "</option>";
echo "</select>";
echo "</td></tr>";

echo "<tr><th valign=top>", t('Varastot'), "<br /><br /><span style='font-size: 0.8em;'>"
, t('Saat kaikki varastot jos et valitse yhtään')
, "</span></th>
    <td>";

if ($piilotetut_varastot != 'on') {
  $piilotetut_varastot_where = ' AND tyyppi != "P"';
}

$varastot = (isset($_POST['varastot']) and is_array($_POST['varastot'])) ? $_POST['varastot'] : array();

$query  = "SELECT tunnus, nimitys
           FROM varastopaikat
           WHERE yhtio = '$kukarow[yhtio]'
           $piilotetut_varastot_where
           ORDER BY tyyppi, nimitys";
$vares = pupe_query($query);

while ($varow = mysqli_fetch_array($vares)) {
  $sel = '';

  if (in_array($varow['tunnus'], $varastot)) {
    $sel = 'checked';
  }

  echo "<input type='checkbox' class='shift' name='varastot[]' value='{$varow['tunnus']}' $sel/>{$varow['nimitys']}<br />\n";
}

echo "</td></tr>";
echo "</table>";
echo "<br>";
echo "<input type='submit' name='ajetaan' value='", t("Laske varastonarvot"), "'>";
echo "</form><br><br>";

if (isset($ajetaan)) {

  $selectlisa    = "";
  $epakurlisa    = "";
  $varastojoini  = "";
  $varastorajaus = "";

  // $lisa -muuttuja tulee monivalintalaatikot-inc:stä

  $varastosumma    = 0;
  $bruttovarastosumma = 0;
  $groupby       = "";
  $orderby       = "";

  if ($merkki != '') {
    $selectlisa .= "tuote.tuotemerkki,";
    $groupby   .= "tuote.tuotemerkki,";
    $orderby   .= "tuote.tuotemerkki,";
  }

  if ($tryittain != '') {
    $selectlisa .= "tuote.try,";
    $groupby   .= "tuote.try,";
    $orderby   .= "tuote.try,";
  }

  if (isset($varastot) and count($varastot) > 0) {
    $selectlisa .= "varastopaikat.nimitys,";
    $groupby   .= "varastopaikat.nimitys,";
    $orderby   .= "varastopaikat.nimitys,";

    $varastojoini = "JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
                     AND varastopaikat.tunnus = tuotepaikat.varasto)";
    $varastorajaus = "AND tuotepaikat.varasto IN (".implode(",", $varastot).")";
  }

  if ($groupby != "") {
    $groupby = "GROUP BY ".mb_substr($groupby, 0, -1);
  }

  if ($orderby != "") {
    $orderby = "ORDER BY ".mb_substr($orderby, 0, -1);
  }

  // 12 kuukautta taakseppäin kuun eka päivä
  $kausi = date("Y-m-d", mktime(0, 0, 0, date("m")-12, 1, date("Y")));

  // epäkurantti
  if ($epakur == 'epakur') {
    $epakurlisa = " AND (epakurantti100pvm != '0000-00-00' or epakurantti75pvm != '0000-00-00' or epakurantti50pvm != '0000-00-00' or epakurantti25pvm != '0000-00-00') ";
  }

  if ($epakur == 'ei_epakur') {
    $epakurlisa = " AND epakurantti100pvm = '0000-00-00' ";
  }

  // Varaston arvo
  $query = "SELECT tuote.tuoteno,
            $selectlisa
            sum(
              if(  tuote.sarjanumeroseuranta = 'S',
                (  SELECT tuotepaikat.saldo*if(tuote.epakurantti75pvm='0000-00-00', if(tuote.epakurantti75pvm='0000-00-00', if(tuote.epakurantti50pvm='0000-00-00', if(tuote.epakurantti25pvm='0000-00-00', avg(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl), avg(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl)*0.75), avg(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl)*0.5), avg(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl)*0.25), 0)
                  FROM sarjanumeroseuranta
                  LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
                  LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
                  WHERE sarjanumeroseuranta.yhtio           = tuotepaikat.yhtio
                  and sarjanumeroseuranta.tuoteno           = tuotepaikat.tuoteno
                  and sarjanumeroseuranta.myyntirivitunnus != -1
                  and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
                  and tilausrivi_osto.laskutettuaika       != '0000-00-00'
                ),
                tuotepaikat.saldo*if(tuote.epakurantti100pvm='0000-00-00', if(tuote.epakurantti75pvm='0000-00-00', if(tuote.epakurantti50pvm='0000-00-00', if(tuote.epakurantti25pvm='0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0)
              )
            ) varasto,
            sum(
              if(  tuote.sarjanumeroseuranta = 'S',
                (  SELECT tuotepaikat.saldo*avg(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl)
                  FROM sarjanumeroseuranta
                  LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
                  LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
                  WHERE sarjanumeroseuranta.yhtio           = tuotepaikat.yhtio
                  and sarjanumeroseuranta.tuoteno           = tuotepaikat.tuoteno
                  and sarjanumeroseuranta.myyntirivitunnus != -1
                  and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
                  and tilausrivi_osto.laskutettuaika       != '0000-00-00'
                ),
                tuotepaikat.saldo*tuote.kehahin
              )
            ) bruttovarasto,
            group_concat(concat('\'',tuotepaikat.tuoteno,'\'')) tuotteet
            FROM tuotepaikat
            JOIN tuote ON (tuote.tuoteno = tuotepaikat.tuoteno and tuote.yhtio = tuotepaikat.yhtio and tuote.ei_saldoa = '' $epakurlisa $lisa)
            $varastojoini
            WHERE tuotepaikat.yhtio                         = '$kukarow[yhtio]'
            and tuotepaikat.saldo                           <> 0
            $varastorajaus
            $groupby
            $orderby";
  $result = pupe_query($query);

  echo "<table>";

  echo "<tr>";

  if ($merkki != '') {
    echo "<th>", t("Tuotemerkki"), "</th>";
  }

  if ($tryittain != '') {
    echo "<th>", t("Tuoteryhmä"), "</th>";
  }

  if (isset($varastot) and count($varastot) > 0) {
    echo "<th>", t("Varasto"), "</th>";
  }

  echo "<th>", t("Pvm"), "</th>";
  echo "<th>", t("Varastonarvo"), "</th>";
  echo "<th>", t("Bruttovarastonarvo"), "</th>";

  if ($merkki != '' or $tryittain != '') {
    echo "<th>", t("Varastonkierto"), "</th>";
  }

  echo "</tr>";

  $varvo = 0;

  while ($row = mysqli_fetch_assoc($result)) {
    // netto- ja bruttovarastoarvo
    $varvo  = hinta_kuluineen( $row["tuoteno"], $row["varasto"] );
    $bvarvo = hinta_kuluineen( $row["tuoteno"], $row["bruttovarasto"] );

    $varastosumma += hinta_kuluineen( $row["tuoteno"], $row["varasto"] );
    $bruttovarastosumma += hinta_kuluineen( $row["tuoteno"], $row["bruttovarasto"] );

    echo "<tr>";

    if ($merkki != '') {
      echo "<td>{$row["tuotemerkki"]}</td>";
    }

    if ($tryittain != '') {
      echo "<td>{$row["try"]}</td>";
    }

    if (isset($varastot) and count($varastot) > 0) {
      echo "<td>{$row["nimitys"]}</td>";
    }

    echo "<td>", date("d.m.Y"), "</td>";
    echo "<td align='right'>", sprintf("%.2f", $varvo), "</td>";
    echo "<td align='right'>", sprintf("%.2f", $bvarvo), "</td>";

    if ($merkki != '' or $tryittain != '') {

      // tuotteen määrä varastossa nyt
      $query = "SELECT sum(saldo) varasto
                FROM tuote
                JOIN tuotepaikat use index (tuote_index) on tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno
                WHERE tuote.yhtio   = '$kukarow[yhtio]'
                and tuote.tuoteno   in ($row[tuotteet])
                and tuote.ei_saldoa = ''";
      $vres = pupe_query($query);
      $vrow = mysqli_fetch_assoc($vres);

      // haetaan tuotteen myydyt kappaleet
      $query  = "SELECT
                 ifnull(sum(kpl), 0) kpl
                 FROM tuote
                 JOIN tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika) on tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tilausrivi.tyyppi in ('L','V') and tilausrivi.laskutettuaika >= date_sub(curdate(), INTERVAL 12 month)
                 WHERE tuote.yhtio   = '$kukarow[yhtio]'
                 and tuote.tuoteno   in ($row[tuotteet])
                 and tuote.ei_saldoa = ''";
      $xmyyres = pupe_query($query);
      $xmyyrow = mysqli_fetch_assoc($xmyyres);

      // lasketaan varaston kiertonopeus
      if ($vrow["varasto"] > 0) {
        $kierto = round($xmyyrow["kpl"] / $vrow["varasto"], 2);
      }
      else {
        $kierto = 0;
      }

      echo "<td align='right'>", str_replace(".", ",", sprintf("%.2f", $kierto)), "</td>";
    }

    echo "</tr>";

  }

  if ($varastosumma != 0) {
    echo "<tr>";

    $colspan = 1;

    if ($merkki != '') {
      $colspan++;
    }

    if ($tryittain != '') {
      $colspan++;
    }

    if (isset($varastot) and count($varastot) > 0) {
      $colspan++;
    }

    echo "<td class='tumma' colspan='$colspan'>", t("Yhteensä"), "</td>";
    echo "<td class='tumma' align='right'>", sprintf("%.2f", $varastosumma), "</td>";
    echo "<td class='tumma' align='right'>", sprintf("%.2f", $bruttovarastosumma), "</td>";
    echo "</tr>";
  }

  echo "</table>";
}

require "../inc/footer.inc";
