<?php
// Formaatti on:
// toimittajanumero
// tilausnumero
// tuotekoodi
// määrä
$query_ale_lisa = generoi_alekentta("O");

$tulosta_ostohinnalla_hae = t_avainsana("EDI_OSTOHINTA");
$tulosta_ostohinnalla = false;
while ($tulosta_ostohinnalla_tark = mysqli_fetch_assoc($tulosta_ostohinnalla_hae)) {
  if($tulosta_ostohinnalla_tark['selite'] == 1) {
    $tulosta_ostohinnalla = true;
    break;
  }
}

if($tulosta_ostohinnalla) {
  $query_ale_lisa_tulosta_ostohinnalla = generoi_alekentta("O", '', 'ei_erikoisale');
  $hinta_query = "
    tilausrivi.kommentti kommentti,
    tilausrivi.nimitys nimitys,
    ROUND(tilausrivi.hinta,3) hinta,
    round((tilausrivi.varattu + tilausrivi.jt)
    * tilausrivi.hinta
    * if (tuotteen_toimittajat.tuotekerroin = 0
      OR tuotteen_toimittajat.tuotekerroin IS NULL,
        1,
        tuotteen_toimittajat.tuotekerroin)
    * {$query_ale_lisa_tulosta_ostohinnalla}, '$yhtiorow[hintapyoristys]') rivihinta,
  ";
} else {
  $hinta_query = "
    ROUND(tilausrivi.hinta * IF(tuotteen_toimittajat.tuotekerroin = 0 OR tuotteen_toimittajat.tuotekerroin IS NULL, 1, tuotteen_toimittajat.tuotekerroin) * {$query_ale_lisa}, '$yhtiorow[hintapyoristys]') nettohinta,
  ";
}

$query = "SELECT tilausrivi.tunnus,
          tilausrivi.otunnus,
          ROUND(tilausrivi.varattu * IF(tuotteen_toimittajat.tuotekerroin = 0, 1, tuotteen_toimittajat.tuotekerroin), 2) AS varattu,
          tilausrivi.tuoteno,
          IFNULL(tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno) toim_tuoteno,
          $hinta_query
          tilausrivi.yksikko
          FROM tilausrivi
          JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno AND tuotteen_toimittajat.liitostunnus = '{$laskurow['liitostunnus']}')
          LEFT JOIN tuotteen_avainsanat AS ta ON (ta.yhtio = tilausrivi.yhtio AND ta.tuoteno = tilausrivi.tuoteno AND ta.laji = 'ei_edi_ostotilaukseen')
          WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
          AND tilausrivi.tyyppi  = 'O'
          AND tilausrivi.otunnus = '{$laskurow['tunnus']}'
          AND tilausrivi.varattu > 0
          AND ta.tunnus IS NULL
          ORDER BY tilausrivi.tunnus";
$result = pupe_query($query);

if (mysqli_num_rows($result) == 0) {
  echo "<font class='error'>".t("Lähetettäviä tilausrivejä ei löydy")."</font>";
}
else {

  require_once 'Spreadsheet/Excel/Writer.php';

  $excel_editilaus = $pupe_root_polku."/dataout/excel-order-$laskurow[tunnus].xls";

  $workbook = new Spreadsheet_Excel_Writer($excel_editilaus);

  $workbook->setVersion(8);

  $thispage = $workbook->addWorksheet($laskurow["tunnus"]);

  $boldi = $workbook->addFormat();
  $boldi->setBold();

  $thispage->setColumn(1, 2, 25);

  $excelrivi = 1;
  $thispage->writeString($excelrivi++, 1, trim($laskurow["toim_nimi"]." ".$laskurow["toim_nimitark"]), $boldi);
  $thispage->writeString($excelrivi++, 1, $laskurow["toim_osoite"], $boldi);
  $thispage->writeString($excelrivi++, 1, $laskurow["toim_maa"]."-".$laskurow["toim_postino"]." ".$laskurow["toim_postitp"], $boldi);

  $thispage->writeString($excelrivi, 1, "Order Number:", $boldi);
  $thispage->writeString($excelrivi++, 2, $laskurow["tunnus"]);
  $thispage->writeString($excelrivi, 1, "Date of order:", $boldi);
  $thispage->writeString($excelrivi++, 2, date("Y-m-d"));

  $excelrivi++;

  if($tulosta_ostohinnalla) { 
    $thispage->writeString($excelrivi, 0, "Line", $boldi);
    $thispage->writeString($excelrivi, 1, "Your product code", $boldi);
    $thispage->writeString($excelrivi, 2, "Our product code", $boldi);
    $thispage->writeString($excelrivi, 4, "Stock code", $boldi);
    $thispage->writeString($excelrivi, 5, "Name", $boldi);
    $thispage->writeString($excelrivi, 6, "Quantity", $boldi);
    $thispage->writeString($excelrivi, 7, "Price", $boldi);
    $thispage->writeString($excelrivi, 8, "Row price", $boldi);
  } else {
    $thispage->writeString($excelrivi, 0, "Line", $boldi);
    $thispage->writeString($excelrivi, 1, "Your product code", $boldi);
    $thispage->writeString($excelrivi, 2, "Our product code", $boldi);
    $thispage->writeString($excelrivi, 4, "Quantity", $boldi);
    $thispage->writeString($excelrivi, 5, "Price", $boldi);
  }

  $excelrivi++;

  $lask = 1;

  while ($tilausrivirow = mysqli_fetch_assoc($result)) {

    if($tulosta_ostohinnalla) {
      $thispage->writeString($excelrivi, 0, $lask);
      $thispage->writeString($excelrivi, 1, $tilausrivirow['toim_tuoteno']);
      $thispage->writeString($excelrivi, 2, $tilausrivirow['tuoteno']);
      $thispage->writeString($excelrivi, 4, $tilausrivirow['kommentti']);
      $thispage->writeString($excelrivi, 5, $tilausrivirow['nimitys']);
      $thispage->writeNumber($excelrivi, 6, $tilausrivirow['varattu']);
      $thispage->writeNumber($excelrivi, 7, $tilausrivirow['hinta']);
      $thispage->writeNumber($excelrivi, 8, $tilausrivirow['rivihinta']);
    } else {
      $thispage->writeString($excelrivi, 0, $lask);
      $thispage->writeString($excelrivi, 1, $tilausrivirow['toim_tuoteno']);
      $thispage->writeString($excelrivi, 2, $tilausrivirow['tuoteno']);
      $thispage->writeNumber($excelrivi, 4, $tilausrivirow['varattu']);
      $thispage->writeNumber($excelrivi, 5, $tilausrivirow['nettohinta']);
    }

    $excelrivi++;

    $lask++;
  }

  // We need to explicitly close the workbook
  $workbook->close();

  echo "<table>";
  echo "<tr><th>".t("Tallenna ostotilaus").":</th>";
  echo "<form method='post' action='{$palvelin2}tilauskasittely/tilaus_osto.php'>";
  echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
  echo "<input type='hidden' name='kaunisnimi' value='Order_{$laskurow["tunnus"]}.xls'>";
  echo "<input type='hidden' name='filenimi' value='$excel_editilaus'>";
  echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
  echo "</table><br>";
}

// Nollataan tarkoituksella lopetusmuuttuja
$lopetus = "";
