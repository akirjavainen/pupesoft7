<?php

// tarvitaan tilauksen tunnus muutujassa $tunnus
// printterin komento muuttujassa $oslapp
// $kukarow[yhtio] jostain saadaan yhtio
// $yhtiorow array josta saada lähettäjän tiedot

if ($phpnimi != "rahtikirja_custom.php") {
  //Tulostetaan standardi kolliosoitelappu
  $query = "SELECT lasku.*, if(yhteyshenkilo.nimi!='',concat_ws(' / ', yhteyshenkilo.nimi,if(yhteyshenkilo.gsm!='',yhteyshenkilo.gsm,if(yhteyshenkilo.puh!='',yhteyshenkilo.puh,NULL))),'') yhteyshenkilo
            FROM lasku
            LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and laskun_lisatiedot.otunnus=lasku.tunnus
            LEFT JOIN yhteyshenkilo ON yhteyshenkilo.yhtio=laskun_lisatiedot.yhtio and yhteyshenkilo.tunnus=laskun_lisatiedot.yhteyshenkilo_tekninen and yhteyshenkilo.tyyppi = 'A'
            WHERE lasku.yhtio = '$kukarow[yhtio]'
            and lasku.tunnus  = '$tunnus'";
  $tempr = pupe_query($query);
  $laskurow = mysqli_fetch_assoc($tempr);

  // Käytetäänkö VAK-tietokantaa
  if ($yhtiorow["vak_kasittely"] != "") {
    $vakselect = "concat_ws(' ', concat('UN',yk_nro), nimi_ja_kuvaus, lipukkeet, pakkausryhma)";
    $vakjoin   = "JOIN vak ON tuote.yhtio = vak.yhtio and tuote.vakkoodi = vak.tunnus";
  }
  else {
    $vakselect = "tuote.vakkoodi";
    $vakjoin   = "";
  }

  // Haetaan kaikki vakkoodit arrayseen
  $query = "SELECT group_concat(distinct $vakselect SEPARATOR ', ') vakkoodi
            FROM tilausrivi
            JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.vakkoodi not in ('','0'))
            $vakjoin
            where tilausrivi.otunnus = '$laskurow[tunnus]'
            and tilausrivi.yhtio     = '$kukarow[yhtio]'
            and tilausrivi.var       in ('','H')
            and tilausrivi.tyyppi    in ('L','G')
            ORDER BY tuote.vakkoodi";
  $vres = pupe_query($query);

  if (mysqli_num_rows($vres) > 0) {
    $vak = mysqli_fetch_assoc($vres);
    $vakit = $vak["vakkoodi"];
  }
}
else {
  $laskurow = $osoitelappurow;
}

if (isset($oslapp) and $oslapp == "-88") {
  js_openFormInNewWindow();

  echo "<br><form id='tulostakopioform_osoitelappu_{$laskurow['tunnus']}' name='tulostakopioform_osoitelappu_{$laskurow['tunnus']}' method='post' action='{$palvelin2}tilauskasittely/tulostakopio.php' autocomplete='off'>
        <input type='hidden' name='otunnus' value='{$laskurow['tunnus']}'>
        <input type='hidden' name='toim' value='OSOITELAPPU'>
        <input type='hidden' name='tee' value='NAYTATILAUS'>
        <input type='submit' value='".t("Osoitelappu", $kieli).": {$laskurow['tunnus']}' onClick=\"js_openFormInNewWindow('tulostakopioform_osoitelappu_{$laskurow['tunnus']}', ''); return false;\"></form><br>";
}
else {

  // haetaan printterin osoitetiedot
  $query = "SELECT *
            FROM kirjoittimet
            WHERE yhtio  = '{$kukarow['yhtio']}'
            AND TRIM(komento) LIKE CONCAT(TRIM('{$oslapp}'),'%')
            AND nimi    != ''";
  $pres  = pupe_query($query);

  if (mysqli_num_rows($pres) > 0) {
    $print = mysqli_fetch_assoc($pres);

    $yhtiorow["nimi"]    = $print["nimi"];
    $yhtiorow["osoite"]  = $print["osoite"];
    $yhtiorow["postino"] = $print["postino"];
    $yhtiorow["postitp"] = $print["postitp"];
    $yhtiorow["puhelin"] = $print["puhelin"];
  }

  if (empty($yhtiorow['ean'])) {
    $yhtiorow['ean'] = $yhtiorow["ytunnus"];
  }

  //PDF:n luonti ja defaultit
  require_once "pdflib/phppdflib.class.php";

  // jos php-gd on installoitu niin loidataab barcode library
  if (in_array("gd", get_loaded_extensions())) {
    if (@include_once "viivakoodi/Barcode.php");
    else include_once "Barcode.php";
  }

  //PDF parametrit
  $pdf = new pdffile;
  $pdf->set_default('margin-top',   0);
  $pdf->set_default('margin-bottom',   0);
  $pdf->set_default('margin-left',   0);
  $pdf->set_default('margin-right',   0);
  $rectparam["width"] = 0.3;

  //fontit
  $otsik["height"] = 9;
  $otsik["font"] = "Helvetica";

  $pien["height"] = 10;
  $pien["font"] = "Helvetica";

  $norm["height"] = 10;
  $norm["font"] = "Helvetica-Bold";

  $piennorm["height"] = 9;
  $piennorm["font"] = "Helvetica-Bold";

  $kirj["height"] = 12;
  $kirj["font"] = "Helvetica-Bold";

  $iso["height"] = 16;
  $iso["font"] = "Helvetica-Bold";

  $huge["height"] = 20;
  $huge["font"] = "Helvetica-Bold";

  $uniq_pakkaukset = array();
  $uniq_pakkaukset_sscc = array();

  if ($yhtiorow['kerayserat'] == 'P' or $yhtiorow['kerayserat'] == 'A') {

    $kaikki_ok = true;

    if ($yhtiorow['kerayserat'] == 'A') {
      $query = "SELECT kerayserat
                FROM asiakas
                WHERE yhtio    = '{$kukarow['yhtio']}'
                AND tunnus     = '{$laskurow['liitostunnus']}'
                AND kerayserat = 'A'";
      $asiakas_chk_res = pupe_query($query);

      if (mysqli_num_rows($asiakas_chk_res) == 0) {
        $kaikki_ok = false;
      }
    }

    if ($kaikki_ok) {
      $query = "SELECT DISTINCT pakkausnro, sscc, sscc_ulkoinen, pakkaus
                FROM kerayserat
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND otunnus = '{$laskurow['tunnus']}'
                ORDER BY pakkausnro";
      $pakkaus_chk_res = pupe_query($query);

      $pi = 1;

      while ($pakkaus_chk_row = mysqli_fetch_assoc($pakkaus_chk_res)) {
        $uniq_pakkaukset[$pi] = $pakkaus_chk_row['pakkausnro'];
        $uniq_pakkaukset_sscc[$pi] = $pakkaus_chk_row['sscc_ulkoinen'];
        $pi++;
      }

      $oslappkpl = count($uniq_pakkaukset) > 0 ? count($uniq_pakkaukset) : $oslappkpl;
    }
  }

  $kirjainlisa = '';

  for ($tulostuskpl = 1; $tulostuskpl <= $oslappkpl; $tulostuskpl++) {

    if (count($uniq_pakkaukset) == 0) {
      $uniq_merkki = $tulostuskpl;
    }
    else {
      $uniq_merkki = $uniq_pakkaukset[$tulostuskpl];
      $kirjainlisa = " ".chr(64+$uniq_merkki);
    }

    // tehdään pdf:n uusi sivu
    $firstpage = $pdf->new_page("a5");

    if (class_exists("Image_Barcode")) {
      $nimi = "/tmp/".md5(uniqid(rand(), true)).".jpg";

      imagejpeg(Image_Barcode::draw($laskurow['asiakkaan_tilausnumero'], 'code128', 'jpg', false, 3, 170), $nimi);

      $fh = fopen($nimi, "r");
      $data = fread($fh, filesize($nimi));
      fclose($fh);

      $image = $pdf->jfif_embed($data);

      $logoparam['scale'] = 102/282;
      $pdf->image_place($image, mm_pt(72), mm_pt(24), $firstpage, $logoparam);
      unlink($nimi);
    }

    $pdf->draw_text(mm_pt(22), mm_pt(65), t("Asiakastilaus", $kieli).":",  $firstpage, $norm);
    $pdf->draw_text(mm_pt(50), mm_pt(65), "$laskurow[asiakkaan_tilausnumero]{$kirjainlisa}",  $firstpage, $iso);

    $pdf->draw_rectangle(mm_pt(179), mm_pt(21.5), mm_pt(31), mm_pt(126.5), $firstpage, $rectparam);

    unset($data);
    if ( (int) $yhtiorow["lasku_logo"] > 0) {
      $liite = hae_liite($yhtiorow["lasku_logo"], "Yllapito", "array");
      $data = $liite["data"];
      $isizelogo[0] = $liite["image_width"];
      $isizelogo[1] = $liite["image_height"];
      unset($liite);
    }
    elseif (file_exists($yhtiorow["lasku_logo"])) {
      $filename = $yhtiorow["lasku_logo"];

      $fh = fopen($filename, "r");
      $data = fread($fh, filesize($filename));
      fclose($fh);

      $isizelogo = getimagesize($yhtiorow["lasku_logo"]);
    }

    if ($data) {
      $image = $pdf->jfif_embed($data);

      if (!$image) {
        echo t("Logokuvavirhe");
      }
      else {

        $logoparam = array();

        if ($isizelogo[0] > $isizelogo[1] and $isizelogo[1] * (200 / $isizelogo[0]) <= 40) {
          $logoparam['scale'] = 200 / $isizelogo[0];
        }
        else {
          $logoparam['scale'] = 40  / $isizelogo[1];
        }

        $placement = $pdf->image_place($image, 560-($logoparam['scale']*$isizelogo[1]), 410-($logoparam['scale']*$isizelogo[0]), $firstpage, $logoparam);
      }
    }

    $query = "SELECT toimitustapa.*,
              aktiivinen_kuljetus toimittajanumero
              FROM toimitustapa
              WHERE yhtio = '$kukarow[yhtio]'
              and selite  = '$laskurow[toimitustapa]'";
    $toitares = pupe_query($query);
    $toitarow = mysqli_fetch_assoc($toitares);

    $pdf->draw_text(mm_pt(22), mm_pt(175), t("Mistä", $kieli).":",              $firstpage, $otsik);

    // toimittajanumero
    $pdf->draw_text(mm_pt(56), mm_pt(173), $toitarow['toimittajanumero'],               $firstpage, $iso);

    $pdf->draw_text(mm_pt(22), mm_pt(170), $yhtiorow['nimi'],                $firstpage, $pien);
    if ($yhtiorow['nimitark'] != '') {
      $pdf->draw_text(mm_pt(22), mm_pt(165), $yhtiorow['nimitark'],            $firstpage, $pien);
      $pdf->draw_text(mm_pt(22), mm_pt(160), $yhtiorow['osoite']." ".$yhtiorow['postino']." ".mb_strtoupper($yhtiorow['postitp']), $firstpage, $pien);
    }
    else {
      $pdf->draw_text(mm_pt(22), mm_pt(165), $yhtiorow['osoite'].", ".$yhtiorow['postino']." ".mb_strtoupper($yhtiorow['postitp']), $firstpage, $pien);
    }

    $pdf->draw_text(mm_pt(100), mm_pt(175), t("Lähetyspvm", $kieli).":",      $firstpage, $otsik);
    $pdf->draw_text(mm_pt(100), mm_pt(170), date("d.m.Y") ,           $firstpage, $norm);

    $pdf->draw_text(mm_pt(22), mm_pt(157), t("Terminaalitoimitus", $kieli),  $firstpage, $norm);
    $pdf->draw_text(mm_pt(22), mm_pt(152), $toitarow['selite'],  $firstpage, $norm);

    $pdf->draw_text(mm_pt(100), mm_pt(140), t("Toimituspvm", $kieli).":",    $firstpage, $otsik);

    if ((int) str_replace("-", "", $laskurow['toimaika']) < date("Ymd")) {
      $_toimaika = date("d.m.Y");
    }
    else {
      $_toimaika = tv1dateconv($laskurow['toimaika']);
    }

    $pdf->draw_text(mm_pt(100), mm_pt(135), $_toimaika,                  $firstpage, $norm);

    $pdf->draw_text(mm_pt(22),  mm_pt(140), t("Minne", $kieli).":",   $firstpage, $otsik);

    $pdf->draw_text(mm_pt(22),  mm_pt(136), $laskurow['toim_nimi'],       $firstpage, $norm);
    $pdf->draw_text(mm_pt(22),  mm_pt(129), $laskurow['toim_nimitark'],   $firstpage, $huge);
    $pdf->draw_text(mm_pt(22),  mm_pt(125), $laskurow['toim_osoite'],     $firstpage, $norm);
    $pdf->draw_text(mm_pt(22),  mm_pt(121), $laskurow['toim_postino']." ".$laskurow['toim_postitp'],  $firstpage, $norm);
  }

  //keksitään uudelle failille joku varmasti uniikki nimi:
  $pdffilenimi = "/tmp/osoitelappu-hornbach-".md5(uniqid(rand(), true)).".pdf";

  //kirjoitetaan pdf faili levylle..
  $fh = fopen($pdffilenimi, "w");
  if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
  fclose($fh);

  if ($oslapp == 'email') {
    $liite = $pdffilenimi;
    $kutsu = "Osoitelappu";

    require "inc/sahkoposti.inc";
  }
  elseif ($tee == 'NAYTATILAUS') {
    //Työnnetään tuo pdf vaan putkeen!
    echo file_get_contents($pdffilenimi);
  }
  elseif ($oslapp != '' and $oslapp != 'edi') {
    $line = exec("$oslapp $pdffilenimi");
    echo t("Osoitelappu tulostuu")."...<br>";
  }
}

$pdffilenimi = '';
