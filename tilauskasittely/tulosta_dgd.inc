<?php

require_once 'Zend/Pdf.php';
require_once 'Zend/Barcode.php';

if (!function_exists('alku_dgd')) {
  function alku_dgd($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    foreach ($params as $key => $val) {
      ${$key} = $val;
    }

    // haetaan toimitustavan tiedot
    $query = "SELECT *
              FROM toimitustapa
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND selite  = '{$laskurow['toimitustapa']}'";
    $toitares = pupe_query($query);
    $toitarow = mysqli_fetch_assoc($toitares);

    //PDF parametrit
    if ($pdf === NULL) {
      $pdf = Zend_Pdf::load($GLOBALS["pupe_root_polku"]."/tilauskasittely/DGDpohja.pdf");
    }

    // Fontit
    $norm = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
    $bold = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);

    $pdf->pages[$sivu]->setFont($norm, 10);
    $pdf->pages[$sivu]->drawText($yhtiorow["nimi"], mm_pt(15), mm_pt(262), 'UTF-8');
    $pdf->pages[$sivu]->drawText($yhtiorow["osoite"], mm_pt(15), mm_pt(258), 'UTF-8');
    $pdf->pages[$sivu]->drawText($yhtiorow["postino"]." ".$yhtiorow["postitp"], mm_pt(15), mm_pt(254), 'UTF-8');
    $pdf->pages[$sivu]->drawText(maa($yhtiorow["maa"], $kieli), mm_pt(15), mm_pt(250), 'UTF-8');

    // Rakirno
    $query = "SELECT r2.rahtikirjanro, group_concat(DISTINCT r2.otsikkonro) otsikkonro
              FROM rahtikirjat r1
              JOIN rahtikirjat r2 ON r1.yhtio=r2.yhtio and r1.rahtikirjanro=r2.rahtikirjanro and r2.rahtikirjanro!=0 and r2.otsikkonro>0
              WHERE r1.otsikkonro = '$laskurow[tunnus]'
                    AND r1.yhtio  = '$kukarow[yhtio]'
              GROUP BY 1";
    $res = pupe_query($query);
    $rahtikirjanro = mysqli_fetch_assoc($res);

    if ($rahtikirjanro["rahtikirjanro"] != "") {
      $laskurow["rahtikirjanro"]   = $rahtikirjanro["rahtikirjanro"];
      $laskurow["otsikkonro"]   = $rahtikirjanro["otsikkonro"];
    }
    else {
      $laskurow["rahtikirjanro"]   = $laskurow["tunnus"];
      $laskurow["otsikkonro"]   = $laskurow["tunnus"];
    }

    $pdf->pages[$sivu]->drawText($laskurow["rahtikirjanro"], mm_pt(110), mm_pt(263), 'UTF-8');
    // jos tulostustapa on koonti ja toimitustavan takana on annettu osoitetiedot, käytetään niitä
    if (in_array($toitarow['tulostustapa'], array('K', 'L')) and !empty($toitarow['toim_nimi']) and !empty($toitarow['toim_postitp'])) {
      $pdf->pages[$sivu]->drawText($toitarow["toim_nimi"], mm_pt(15), mm_pt(242), 'UTF-8');
      $pdf->pages[$sivu]->drawText($toitarow["toim_osoite"], mm_pt(15), mm_pt(238), 'UTF-8');
      $pdf->pages[$sivu]->drawText($toitarow["toim_postino"]." ".$toitarow["toim_postitp"], mm_pt(15), mm_pt(234), 'UTF-8');
      $pdf->pages[$sivu]->drawText(maa($toitarow["toim_maa"], $kieli), mm_pt(15), mm_pt(230), 'UTF-8');
    }
    else {
      $pdf->pages[$sivu]->drawText($laskurow["toim_nimi"], mm_pt(15), mm_pt(242), 'UTF-8');
      $pdf->pages[$sivu]->drawText($laskurow["toim_osoite"], mm_pt(15), mm_pt(238), 'UTF-8');
      $pdf->pages[$sivu]->drawText($laskurow["toim_postino"]." ".$laskurow["toim_postitp"], mm_pt(15), mm_pt(234), 'UTF-8');
      $pdf->pages[$sivu]->drawText(maa($laskurow["toim_maa"], $kieli), mm_pt(15), mm_pt(230), 'UTF-8');
    }

    // Ruksit
    $pdf->pages[$sivu]->drawLine(mm_pt(15), mm_pt(202), mm_pt(55), mm_pt(197));
    $pdf->pages[$sivu]->drawLine(mm_pt(15), mm_pt(197), mm_pt(55), mm_pt(202));

    $pdf->pages[$sivu]->drawLine(mm_pt(58), mm_pt(202), mm_pt(101), mm_pt(197));
    $pdf->pages[$sivu]->drawLine(mm_pt(58), mm_pt(197), mm_pt(101), mm_pt(202));

    // 9 Additional handling information
    $pdf->pages[$sivu]->drawText(t("Hätäpuhelinnumero", $kieli)." {$yhtiorow["puhelin"]}", mm_pt(105), mm_pt(202), 'UTF-8');
    $pdf->pages[$sivu]->drawText($yhtiorow["nimi"], mm_pt(105), mm_pt(196), 'UTF-8');

    $pdf->pages[$sivu]->drawText($yhtiorow["nimi"], mm_pt(15), mm_pt(61), 'UTF-8');

    if ($kukarow["yhtio"] == "mast") {
      $nimirow = array("nimi" => "Virpi Kivelä");
    }
    else {
      $query = "SELECT nimi
                FROM kuka
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$laskurow[myyja]'";
      $nimires = pupe_query($query);
      $nimirow = mysqli_fetch_assoc($nimires);
    }

    $pdf->pages[$sivu]->drawText($nimirow["nimi"], mm_pt(15), mm_pt(50), 'UTF-8');
    $pdf->pages[$sivu]->drawText($yhtiorow["kotipaikka"]." / ".tv1dateconv(date("Y-m-d")), mm_pt(15), mm_pt(41), 'UTF-8');

    $pdf->pages[$sivu]->drawText($yhtiorow["nimi"], mm_pt(143), mm_pt(61), 'UTF-8');
    $pdf->pages[$sivu]->drawText($nimirow["nimi"], mm_pt(143), mm_pt(50), 'UTF-8');
    $pdf->pages[$sivu]->drawText($yhtiorow["kotipaikka"]." / ".tv1dateconv(date("Y-m-d")), mm_pt(143), mm_pt(41), 'UTF-8');

    // Viivoitin
    //$pdf->pages[$sivu]->setLineWidth(0.5);
    //$pdf->pages[$sivu]->setFont($norm, 5);

    //for ($i=5; $i < 295; $i++) {
    //  $pdf->pages[$sivu]->drawText($i, mm_pt(6), mm_pt($i));
    //  $pdf->pages[$sivu]->drawLine(mm_pt(10), mm_pt($i), mm_pt(13), mm_pt($i));
    //}

    // KUVAT:
    //$image = Zend_Pdf_Image::imageWithPath("/tmp/logo.jpg");
    //$pdf->pages[0]->drawImage($image, 20, 830, 150, 800);
    //$pdf->pages[0] = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
    //$pdf->save("/tmp/kakal.pdf");

    // VIIVAKOODIT:
    //Zend_Barcode::setBarcodeFont("/Library/Fonts/Arial Black.ttf");
    //$barcodeOptions = array('text' => 'ZEND-FRAMEWORK-1');
    //$rendererOptions = array('topOffset' => 50, 'leftOffset' => 50);
    //$pdfWithBarcode = Zend_Barcode::factory('code39', 'pdf', $barcodeOptions, $rendererOptions)->setResource($pdf)->draw();

    // Luodaan palautettavat
    $return = array();

    foreach ($params as $key => $null) {
      $return[$key] = ${$key};
    }

    return $return;
  }
}

if (!function_exists('uusi_sivu_dgd')) {
  function uusi_sivu_dgd($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    foreach ($params as $key => $val) {
      ${$key} = $val;
    }

    // Loadataan lisäsivun pohja omaan olioon
    $pdf2 = Zend_Pdf::load($GLOBALS["pupe_root_polku"]."/tilauskasittely/DGDpohja_lisasivu.pdf");

    // Kloonataan sivu jotta saadaan se liitettyä alkuperäiseen dokumenttiin
    $pdf->pages[] = clone $pdf2->pages[0];

    $pdf->pages[$sivu]->setFont($norm, 10);
    $pdf->pages[$sivu]->drawText($yhtiorow["nimi"], mm_pt(15), mm_pt(262), 'UTF-8');
    $pdf->pages[$sivu]->drawText($yhtiorow["osoite"], mm_pt(15), mm_pt(258), 'UTF-8');
    $pdf->pages[$sivu]->drawText($yhtiorow["postino"]." ".$yhtiorow["postitp"], mm_pt(15), mm_pt(254), 'UTF-8');
    $pdf->pages[$sivu]->drawText(maa($yhtiorow["maa"]), mm_pt(15), mm_pt(250), 'UTF-8');

    $pdf->pages[$sivu]->drawText($laskurow["rahtikirjanro"], mm_pt(110), mm_pt(263), 'UTF-8');

    // Luodaan palautettavat
    $return = array();

    foreach ($params as $key => $null) {
      $return[$key] = ${$key};
    }

    return $return;
  }
}

if (!function_exists('rivi_dgd')) {
  function rivi_dgd($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    foreach ($params as $key => $val) {
      ${$key} = $val;
    }

    $kala = 175;

    $pdf->pages[$sivu]->setFont($norm, 8);

    list($vakrakirtiedot, $vaktuotetiedot) = palauta_vak_tiedot($otunnukset, $kieli, "DGD", "imdg");

    $kollimaara_yht = 0;

    foreach ($vakrakirtiedot as $vakrakirrivi) {

      if (($sivu == 0 and $kala < 120) or ($sivu > 0 and $kala < 30)) {

        $pdf->pages[$sivu]->drawText("Continues....", mm_pt(45), mm_pt($kala), 'UTF-8');

        $sivu++;

        // Luodaan palautettavat
        $return = array();

        foreach ($params as $key => $null) {
          $return[$key] = ${$key};
        }

        $params = uusi_sivu_dgd($return);

        // Luodaan muuttujat
        foreach ($params as $key => $val) {
          ${$key} = $val;
        }

        $kala = 240;
        $pdf->pages[$sivu]->setFont($norm, 8);
      }

      list($kollimaara, $teksti, $kilot) = explode("###", $vakrakirrivi);

      $kollimaara_yht += $kollimaara;

      $pdf->pages[$sivu]->drawText($teksti, mm_pt(45), mm_pt($kala), 'UTF-8');
      $pdf->pages[$sivu]->drawText($kilot, mm_pt(145), mm_pt($kala), 'UTF-8');
      $kala-=5;
    }

    $pdf->pages[$sivu]->drawText(1, mm_pt(20), mm_pt(175), 'UTF-8');

    if ($kollimaara_yht > 1) {
      $pdf->pages[$sivu]->drawText(" -  ".$kollimaara_yht, mm_pt(22), mm_pt(175), 'UTF-8');
    }

    $kala-=2;

    $riveja = count($vaktuotetiedot);

    $total_brutto = str_replace('TOTAL GROSS MASS: ', '', $vaktuotetiedot[$riveja-2]);
    $total_brutto = str_replace(' kg', '', $total_brutto);

    $total_netto = str_replace('TOTAL NET MASS: ', '', $vaktuotetiedot[$riveja-1]);
    $total_netto = str_replace(' kg', '', $total_netto);

    $suhde = $total_netto / $total_brutto;

    foreach ($vaktuotetiedot as $vaktuoterivi) {

      list($vaktuoterivi, $vaktuotepaino) = explode("###", $vaktuoterivi);

      if ($kala < 120) {

        $pdf->pages[$sivu]->drawText("Continues....", mm_pt(45), mm_pt($kala), 'UTF-8');

        $sivu++;

        // Luodaan palautettavat
        $return = array();

        foreach ($params as $key => $null) {
          $return[$key] = ${$key};
        }

        $params = uusi_sivu_dgd($return);

        // Luodaan muuttujat
        foreach ($params as $key => $val) {
          ${$key} = $val;
        }

        $kala = 240;
        $pdf->pages[$sivu]->setFont($norm, 8);
      }

      $pdf->pages[$sivu]->drawText($vaktuoterivi, mm_pt(45), mm_pt($kala), 'UTF-8');

      if ($vaktuotepaino != "") {
        $bruttopaino = $vaktuotepaino / $suhde;
        $pdf->pages[$sivu]->drawText(round($bruttopaino, 2), mm_pt(145), mm_pt($kala), 'UTF-8');
        $pdf->pages[$sivu]->drawText(round($vaktuotepaino, 2), mm_pt(167), mm_pt($kala), 'UTF-8');
      }

      $kala-=4;
    }

    // Luodaan palautettavat
    $return = array();

    foreach ($params as $key => $null) {
      $return[$key] = ${$key};
    }

    return $return;
  }
}

if (!function_exists('loppu_dgd')) {
  function loppu_dgd($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    foreach ($params as $key => $val) {
      ${$key} = $val;
    }

    // Sivunumerot
    for ($pp=0; $pp<=$sivu; $pp++) {
      $csiv = $pp+1;
      $tsiv = $sivu+1;

      $pdf->pages[$pp]->setFont($norm, 10);
      $pdf->pages[$pp]->drawText("$csiv       $tsiv", mm_pt(114), mm_pt(259), 'UTF-8');
    }

    // Luodaan palautettavat
    $return = array();

    foreach ($params as $key => $null) {
      $return[$key] = ${$key};
    }

    return $return;
  }
}

if (!function_exists('print_pdf_dgd')) {
  function print_pdf_dgd($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    foreach ($params as $key => $val) {
      ${$key} = $val;
    }

    if (isset($komento) and $komento == "-88") {
      js_openFormInNewWindow();

      echo "<br><form id='tulostakopioform_dgd_{$laskurow['tunnus']}' name='tulostakopioform_dgd_{$laskurow['tunnus']}' method='post' action='{$palvelin2}tilauskasittely/tulostakopio.php' autocomplete='off'>
            <input type='hidden' name='otunnus' value='{$laskurow['tunnus']}'>
            <input type='hidden' name='toim' value='DGD'>
            <input type='hidden' name='tee' value='NAYTATILAUS'>
            <input type='submit' value='".t("DGD").": {$laskurow['tunnus']}' onClick=\"js_openFormInNewWindow('tulostakopioform_dgd_{$laskurow['tunnus']}', ''); return false;\"></form><br>";
    }
    elseif ($tee == 'NAYTATILAUS') {
      //Työnnetään tuo pdf vaan putkeen!
      echo $pdf->render();
    }
    else {

      //keksitään uudelle failille joku varmasti uniikki nimi:
      list($usec, $sec) = explode(' ', microtime());
      mt_srand((float) $sec + ((float) $usec * 100000));
      $pdffilenimi = "/tmp/DGD-".md5(uniqid(mt_rand(), true)).".pdf";

      $pdf->save($pdffilenimi);

      if ($komento == 'email') {
        $liite = $pdffilenimi;
        $content_body = "";

        echo t("DGD tulostuu")."...<br>";

        $kutsu = t("DGD %s", $kieli, $laskurow["tunnus"]);

        if (@include "inc/sahkoposti.inc");
        else include "sahkoposti.inc";
      }
      elseif ($komento != '' and $komento != 'edi') {
        echo t("DGD tulostuu")."...<br>";
        $line = exec("$komento $pdffilenimi", $output, $returnvalue);
      }
    }
  }
}
