<?php

require 'pdflib/phppdflib.class.php';

function hae_saldovahvistus_pdf($saldovahvistus, $boss = false) {
  global $kukarow, $yhtiorow, $pdf, $kala, $sivu, $norm, $pieni, $kieli, $bold, $lask, $rectparam, $sivu_numero_obj_ids;

  $sivu_numero_obj_ids = array();
  //PDF parametrit
  $pdf = new pdffile;
  $pdf->set_default('margin-top', 0);
  $pdf->set_default('margin-bottom', 0);
  $pdf->set_default('margin-left', 0);
  $pdf->set_default('margin-right', 0);
  $rectparam = array();
  $rectparam["width"] = 0.3;
  $lask = 1;
  $sivu = 1;

  $norm["height"] = 10;
  $norm["font"] = "Times-Roman";
  $pieni["height"] = 8;
  $pieni["font"] = "Times-Roman";
  $bold["height"] = 10;
  $bold["font"] = "Times-Bold";


  //Otetaan tässä asiakkaan kieli talteen
  $kieli = $saldovahvistus['asiakas']['kieli'];

  $firstpage = alku($saldovahvistus);

  $firstpage = rivit($firstpage, $saldovahvistus['laskut'], $saldovahvistus, $boss);

  $kala -= 20;
  $pdf->draw_text(250, $kala, t('Avoin saldo yhteensä', $kieli), $firstpage, $bold);
  $pdf->draw_text(452.5, $kala, $saldovahvistus['avoin_saldo_summa'], $firstpage, $bold);
  $pdf->draw_text(500, $kala, $saldovahvistus['valkoodi'], $firstpage, $bold);

  loppu($firstpage, $saldovahvistus);

  if (!empty($sivu_numero_obj_ids)) {
    foreach ($sivu_numero_obj_ids as $sivu_numero_obj_id) {
      $pdf->objects[$sivu_numero_obj_id]['text'] .= $pdf->currentPage['number'] + 1;
    }
  }

  //keksitään uudelle failille joku varmasti uniikki nimi:
  list($usec, $sec) = explode(' ', microtime());
  mt_srand((float)$sec + ((float)$usec * 100000));
  $pdffilenimi = "/tmp/saldovahvistus-".md5(uniqid(mt_rand(), true)).".pdf";

  //kirjoitetaan pdf faili levylle..
  $fh = fopen($pdffilenimi, "w");
  if (fwrite($fh, $pdf->generate()) === FALSE) {
    die("PDF kirjoitus epäonnistui $pdffilenimi");
  }
  fclose($fh);

  return $pdffilenimi;
}

function alku($saldovahvistus) {
  global $pdf, $yhtiorow, $kukarow, $kala, $sivu, $norm, $pieni, $kieli, $bold, $sivu_numero_obj_ids;

  $firstpage = $pdf->new_page("a4");

  tulosta_logo_pdf($pdf, $firstpage, "");

  $kala = 815;

  //Otsikko
  $pdf->draw_text(280, 815, t("Saldovahvistus", $kieli), $firstpage);
  $sivu_numero_obj_ids[] = $pdf->draw_text(430, $kala, t("Sivu", $kieli)." ".$sivu.' / ', $firstpage, $norm);

  $pdf->draw_text(30, $kala - 65, $saldovahvistus['asiakas']["nimi"], $firstpage, $bold);
  $pdf->draw_text(30, $kala - 75, $saldovahvistus['asiakas']["nimitark"], $firstpage, $bold);
  $pdf->draw_text(30, $kala - 85, $saldovahvistus['asiakas']["osoite"], $firstpage, $bold);
  $pdf->draw_text(30, $kala - 95, $saldovahvistus['asiakas']["postino"]." ".$saldovahvistus['asiakas']["postitp"], $firstpage, $bold);
  $pdf->draw_text(30, $kala - 105, $saldovahvistus['asiakas']["maa"], $firstpage, $bold);

  $kala = 710;

  if (!empty($saldovahvistus['paaasiakas'])) {

    $_chk = ($saldovahvistus['asiakas']['tunnus'] != $saldovahvistus['paaasiakas']['tunnus']);

    if ($_chk) {
      $kala -= 10;

      $pdf->draw_text(30, $kala - 10, $saldovahvistus['paaasiakas']["nimi"], $firstpage, $bold);
      $pdf->draw_text(30, $kala - 20, $saldovahvistus['paaasiakas']["nimitark"], $firstpage, $bold);
      $pdf->draw_text(30, $kala - 30, $saldovahvistus['paaasiakas']["osoite"], $firstpage, $bold);
      $pdf->draw_text(30, $kala - 40, $saldovahvistus['paaasiakas']["postino"]." ".$saldovahvistus['asiakas']["postitp"], $firstpage, $bold);
      $pdf->draw_text(30, $kala - 50, $saldovahvistus['paaasiakas']["maa"], $firstpage, $bold);

      $kala -= 10;
    }
  }

  $kala -= 60;

  $pdf->draw_text(380, 780, t("Päivämäärä", $kieli).': '.date('d.m.Y'), $firstpage, $norm);

  //Oikea sarake
  $pdf->draw_text(380, 760, t("Laatija", $kieli).':', $firstpage, $norm);
  $pdf->draw_text(440, 760, $kukarow["nimi"], $firstpage, $norm);

  $pdf->draw_text(380, 750, t("Puhelin", $kieli).':', $firstpage, $norm);
  $pdf->draw_text(440, 750, $kukarow["puhno"], $firstpage, $norm);

  $pdf->draw_text(380, 740, t("Fax", $kieli).':', $firstpage, $norm);
  $pdf->draw_text(440, 740, $yhtiorow['fax'], $firstpage, $norm);

  $pdf->draw_text(380, 730, t("Sähköposti", $kieli).':', $firstpage, $norm);
  $pdf->draw_text(440, 730, $kukarow["eposti"], $firstpage, $norm);

  if ($pdf->currentPage['number'] == 0) {
    $string = t('Ilmoitamme että avoin saldo ', $kieli)." ".date('d.m.Y', strtotime($saldovahvistus['tiliotepvm']))." ".t('on', $kieli).' '.$saldovahvistus['avoin_saldo_summa'].' '.$saldovahvistus['valkoodi'];
    $pdf->draw_text(30, $kala, $string, $firstpage, $norm);

    // tehdään riveistä max 90 merkkiä
    $viesti = wordwrap($saldovahvistus['saldovahvistus_viesti']['selitetark'], 120, "\n");

    $i = 0;
    $rivit = array();
    $rivit = explode("\n", $viesti);
    $kala -= 10;

    foreach ($rivit as $rivi) {
      // laitetaan
      $pdf->draw_text(30, $kala, $rivi, $firstpage, $norm);

      // seuraava rivi tulee 10 pistettä alemmas kuin tämä rivi
      $kala -= 10;
      $i++;
    }

    //Rivit alkaa täsä kohtaa
    $object_keys = array_keys($pdf->objects);
    $kala = ($pdf->objects[max($object_keys)]['bottom']) - 20;
  }
  else {
    $kala = 650;
  }

  return $firstpage;
}

function rivit($firstpage, $rows, $saldovahvistus, $boss = false) {
  global $pdf, $kala, $sivu, $lask, $norm, $pieni, $yhtiorow, $rectparam, $kieli;

  $vasen_kala = 30;

  if ($boss) {
    $pdf->draw_text($vasen_kala, $kala, t("Tilaaja", $kieli), $firstpage, $pieni);
    $vasen_kala += 190;
  }

  $pdf->draw_text($vasen_kala, $kala, t("Laskunro", $kieli), $firstpage, $pieni);
  $vasen_kala += 70;

  $pdf->draw_text($vasen_kala, $kala, t("Päivämäärä", $kieli), $firstpage, $pieni);
  $vasen_kala += 80;

  $pdf->draw_text($vasen_kala, $kala, t("Eräpäivä", $kieli), $firstpage, $pieni);

  if ($boss) {
    $vasen_kala += 110;
  }
  else {
    $vasen_kala += 300;
  }

  $oikpos = $pdf->mb_strlen(t("Avoinsumma", $kieli), $pieni);
  $pdf->draw_text($vasen_kala - $oikpos, $kala, t("Avoinsumma", $kieli), $firstpage, $pieni);

  $kala -= 15;

  $x[0] = 30;
  $x[1] = 565;
  $y[0] = $y[1] = $kala + 25;
  $pdf->draw_line($x, $y, $firstpage, $rectparam);

  foreach ($rows as $row) {
    $firstpage = rivi($firstpage, $row, $saldovahvistus, $boss);
  }

  $y[0] = $y[1] = $kala + 5;
  $pdf->draw_line($x, $y, $firstpage, $rectparam);

  return $firstpage;
}

function rivi($firstpage, $row, $saldovahvistus, $boss = false) {
  global $pdf, $kala, $sivu, $lask, $norm, $pieni, $yhtiorow;

  if ($lask == 37) {
    $sivu++;
    $firstpage = alku($saldovahvistus);
    $kala = 635;
    $lask = 1;
  }

  $vasen_kala = 30;

  if ($boss) {

    $query = "SELECT nimi
              FROM lasku
              WHERE yhtio  = '{$yhtiorow['yhtio']}'
              AND laskunro = '{$row['laskunro']}'
              AND tila     = 'U'
              AND alatila  = 'X'";
    $_nimi_res = pupe_query($query);
    $_nimi_row = mysqli_fetch_assoc($_nimi_res);

    list($ff_string, $ff_font) = pdf_fontfit($_nimi_row["nimi"], 190, $pdf, $norm);

    $pdf->draw_text($vasen_kala, $kala, $ff_string, $firstpage, $ff_font);
    $vasen_kala += 190;
  }

  $pdf->draw_text($vasen_kala, $kala, $row["laskunro"], $firstpage, $norm);
  $vasen_kala += 70;

  $pdf->draw_text($vasen_kala, $kala, tv1dateconv($row["tapvm"]), $firstpage, $norm);
  $vasen_kala += 80;

  $pdf->draw_text($vasen_kala, $kala, tv1dateconv($row["erpcm"]), $firstpage, $norm);

  if ($boss) {
    $vasen_kala += 110;
  }
  else {
    $vasen_kala += 300;
  }

  $oikpos = $pdf->mb_strlen($row["avoin_saldo"], $norm);
  $pdf->draw_text($vasen_kala - $oikpos, $kala, $row["avoin_saldo"], $firstpage, $norm);

  $kala = $kala - 13;
  $lask++;
  return $firstpage;
}

function loppu($firstpage, $saldovahvistus) {
  global $pdf, $yhtiorow, $kukarow, $sivu, $rectparam, $norm, $pieni, $kieli, $lask, $kala, $bold;

  if (($sivu > 1 and $lask > 28) or ($sivu == 1 and $lask > 19)) {
    $sivu++;
    $lask = 1;
    $firstpage = alku($saldovahvistus);
  }

  $kala = 270;

  //Pankkiyhteystiedot
  $pdf->draw_rectangle($kala, 30, ($kala - 45), 565, $firstpage, $rectparam);

  $pdf->draw_text(40, $kala - 8, t("Pankkiyhteys", $kieli), $firstpage, $pieni);
  $pdf->draw_text(40, $kala - 18, $yhtiorow["pankkinimi1"]." ".$yhtiorow["pankkitili1"], $firstpage, $norm);
  $pdf->draw_text(40, $kala - 28, $yhtiorow["pankkinimi2"]." ".$yhtiorow["pankkitili2"], $firstpage, $norm);
  $pdf->draw_text(40, $kala - 38, $yhtiorow["pankkinimi3"]." ".$yhtiorow["pankkitili3"], $firstpage, $norm);

  $pdf->draw_text(230, $kala - 18, (empty($yhtiorow["pankkiiban1"])) ? '' : 'IBAN: '.$yhtiorow["pankkiiban1"], $firstpage, $norm);
  $pdf->draw_text(230, $kala - 28, (empty($yhtiorow["pankkiiban2"])) ? '' : 'IBAN: '.$yhtiorow["pankkiiban1"], $firstpage, $norm);
  $pdf->draw_text(230, $kala - 38, (empty($yhtiorow["pankkiiban3"])) ? '' : 'IBAN: '.$yhtiorow["pankkiiban1"], $firstpage, $norm);

  $pdf->draw_text(430, $kala - 18, (empty($yhtiorow["pankkiswift1"])) ? '' : 'SWIFT: '.$yhtiorow["pankkiswift1"], $firstpage, $norm);
  $pdf->draw_text(430, $kala - 28, (empty($yhtiorow["pankkiswift2"])) ? '' : 'SWIFT: '.$yhtiorow["pankkiswift2"], $firstpage, $norm);
  $pdf->draw_text(430, $kala - 38, (empty($yhtiorow["pankkiswift3"])) ? '' : 'SWIFT: '.$yhtiorow["pankkiswift3"], $firstpage, $norm);

  $kala = 225;

  //Alimmat kolme laatikkoa, yhtiötietoja
  $pdf->draw_rectangle($kala, 30, ($kala - 50), 565, $firstpage, $rectparam);
  $pdf->draw_rectangle($kala, 207, ($kala - 50), 565, $firstpage, $rectparam);
  $pdf->draw_rectangle($kala, 394, ($kala - 50), 565, $firstpage, $rectparam);
  $pdf->draw_text(40, $kala - 13, $yhtiorow["nimi"], $firstpage, $pieni);
  $pdf->draw_text(40, $kala - 23, $yhtiorow["osoite"], $firstpage, $pieni);
  $pdf->draw_text(40, $kala - 33, $yhtiorow["postino"]."  ".$yhtiorow["postitp"], $firstpage, $pieni);
  $pdf->draw_text(40, $kala - 43, $yhtiorow["maa"], $firstpage, $pieni);

  $pdf->draw_text(217, $kala - 13, t("Puhelin", $kieli).":", $firstpage, $pieni);
  $pdf->draw_text(247, $kala - 13, $yhtiorow["puhelin"], $firstpage, $pieni);
  $pdf->draw_text(217, $kala - 23, t("Fax", $kieli).":", $firstpage, $pieni);
  $pdf->draw_text(247, $kala - 23, $yhtiorow["fax"], $firstpage, $pieni);
  $pdf->draw_text(217, $kala - 33, t("Email", $kieli).":", $firstpage, $pieni);
  $pdf->draw_text(247, $kala - 33, $yhtiorow["email"], $firstpage, $pieni);

  $pdf->draw_text(404, $kala - 13, t("Y-tunnus", $kieli).":", $firstpage, $pieni);
  $pdf->draw_text(444, $kala - 13, $yhtiorow["ytunnus"], $firstpage, $pieni);
  $pdf->draw_text(404, $kala - 23, t("Kotipaikka", $kieli).":", $firstpage, $pieni);
  $pdf->draw_text(444, $kala - 23, $yhtiorow["kotipaikka"], $firstpage, $pieni);
  $pdf->draw_text(404, $kala - 33, t("Enn.per.rek", $kieli), $firstpage, $pieni);
  $pdf->draw_text(404, $kala - 43, t("Alv.rek", $kieli), $firstpage, $pieni);


  $kala = 162;
  //Katkoviiva
  $y = array();
  $y[0] = $y[1] = $kala;
  $how_many_lines = 70;
  $page_width = $pdf->currentPage['width'];
  $margin_width = 30;
  $line_and_empty_space_width = ($page_width - ($margin_width * 2)) / $how_many_lines;
  for ($i = $margin_width; $i <= ($page_width - $margin_width); $i = $i + $line_and_empty_space_width) {
    $x[0] = $i;
    $line_width = $line_and_empty_space_width * 0.75;
    $x[1] = $i + $line_width;
    $pdf->draw_line($x, $y, $firstpage, $rectparam);
  }

  $pdf->draw_text(30, $kala - 20, t('Saldovahvistus', $kieli), $firstpage, $bold);

  $pdf->draw_text(30, $kala - 40, t("Todistamme että %s velka / ennakkomaksu %s %s on", $kieli, $saldovahvistus['asiakas']['nimi'], $yhtiorow['nimi'], date('d.m.Y', strtotime($saldovahvistus['tiliotepvm']))), $firstpage, $bold);

  $x[0] = 30;
  $x[1] = 230;
  $y[0] = $y[1] = $kala - 70;
  $pdf->draw_line($x, $y, $firstpage, $rectparam);
  $pdf->draw_text(235, $kala - 70, $saldovahvistus['valkoodi'], $firstpage, $bold);

  $pdf->draw_text(30, $kala - 90, t('Nimi', $kieli).':', $firstpage, $bold);
  $y[0] = $y[1] = $kala - 90;
  $x[0] = 100;
  $x[1] = 320;
  $pdf->draw_line($x, $y, $firstpage, $rectparam);

  $pdf->draw_text(30, $kala - 110, t('Allekirjoitus', $kieli).':', $firstpage, $bold);
  $y[0] = $y[1] = $kala - 110;
  $x[0] = 100;
  $x[1] = 320;
  $pdf->draw_line($x, $y, $firstpage, $rectparam);

  $pdf->draw_text(30, $kala - 130, t('Puhelin', $kieli).':', $firstpage, $bold);
  $y[0] = $y[1] = $kala - 130;
  $x[0] = 100;
  $x[1] = 320;
  $pdf->draw_line($x, $y, $firstpage, $rectparam);
}
