<?php

// tarvitaan $laskurow array
// tarvitaan $yhtiorow array

$query         = "SELECT * from asiakas where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
$tilvahresult  = pupe_query($query);
$tilvahasiakas = mysqli_fetch_array($tilvahresult);

$kieli = mb_strtolower($tilvahasiakas['kieli']);

if ($tilvahasiakas['fax'] == '') {

  $tilvah_nimi = $tilvahasiakas["nimi"];
  if ($tilvahasiakas["toim_nimi"] != "" and $tilvahasiakas["toim_nimi"] != $tilvahasiakas["nimi"]) {
    $tilvah_nimi .= " / ".$tilvahasiakas["toim_nimi"];
  }

  if (!isset($silent) or $silent == "") {
    echo "<font class='error'>".t("TILAUSVAHVISTUS: Asiakkaalta puuttuu faxinumero")."! $tilvah_nimi ($tilvahasiakas[ytunnus])</font><br><br>";
  }
}
else {

  if ($kukarow['extranet'] == '') {
    $tilvahvfilenimi = "../dataout/tilvafax-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(), true)).".txt";
  }
  else {
    $tilvahvfilenimi = "dataout/tilvafax-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(), true)).".txt";
  }

  if (!$toot = fopen($tilvahvfilenimi, "w")) die(t("TILAUSVAHVISTUS: Tiedoston luonti epäonnistui")."!");

  if ($yhtiorow["tilausvahvistus_lahetys"] == 1 and $laskurow["vanhatunnus"] > 0) {
    $query = "SELECT group_concat(tunnus) tunnukset
              FROM lasku use index (yhtio_vanhatunnus)
              WHERE yhtio     = '$kukarow[yhtio]'
              and vanhatunnus = '$laskurow[vanhatunnus]'
              and tila        IN ('N','L','A')";
    $abures = pupe_query($query);
    $aburow = mysqli_fetch_assoc($abures);

    if ($aburow["tunnukset"] != "") {
      $wherelisa = " tilausrivi.otunnus IN ($aburow[tunnukset])";
      $laskurow["nippu"] = $aburow["tunnukset"];
    }
    else {
      $wherelisa = "tilausrivi.otunnus = '$laskurow[tunnus]'";
      $laskurow["nippu"] = $laskurow["tunnus"];
    }
  }
  elseif ($laskurow["tunnusnippu"] > 0 and $laskurow["clearing"] != "JT-TILAUS") {
    $query = "SELECT group_concat(tunnus) tunnukset
              FROM lasku
              WHERE yhtio     = '$kukarow[yhtio]'
              and tunnusnippu = '$laskurow[tunnusnippu]'
              and tila        IN ('N','L','A')";
    $abures = pupe_query($query);
    $aburow = mysqli_fetch_assoc($abures);

    if ($aburow["tunnukset"] != "") {
      $wherelisa = " tilausrivi.otunnus IN ($aburow[tunnukset])";
      $laskurow["nippu"] = $aburow["tunnukset"];
    }
    else {
      $wherelisa = "tilausrivi.otunnus = '$laskurow[tunnus]'";
      $laskurow["nippu"] = $laskurow["tunnus"];
    }
  }
  else {
    $wherelisa = "tilausrivi.otunnus = '$laskurow[tunnus]'";
    $laskurow["nippu"] = $laskurow["tunnus"];
  }

  $ale_query_select_lisa = '';

  for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
    $ale_query_select_lisa .= " tilausrivi.ale{$alepostfix},";
  }

  if ($laskurow["tila"] == "L" or $laskurow["tila"] == "N") {
    $tyyppilisa = " and tilausrivi.tyyppi in ('L') ";
  }
  else {
    $tyyppilisa = " and tilausrivi.tyyppi in ('L','G','W','E','T') ";
  }

  if (($laskurow["tunnusnippu"] > 0 or $yhtiorow["tilausvahvistus_lahetys"] == 1) and $laskurow['vanhatunnus'] > 0) {
    $tulostettava_tunnus = $laskurow['vanhatunnus'];
  }
  else {
    $tulostettava_tunnus = $laskurow['tunnus'];
  }

  $query = "SELECT tilausrivi.tunnus, tuote.eankoodi, tuote.myyntihinta, tuote.aleryhma, tilausrivi.tuoteno,
            if(tilausrivi.var not in ('P','J','O','S'), tilausrivi.varattu, 0) varattu,
            if(tilausrivi.var = 'J', tilausrivi.jt+tilausrivi.varattu , 0) jt,
            tilausrivi.toimaika, tilausrivi.kommentti, tilausrivi.tilkpl, tilausrivi.alv,
            tilausrivi.nimitys, tilausrivi.hinta, {$ale_query_select_lisa} tilausrivi.kommentti, tilausrivi.var
            FROM tilausrivi
            LEFT JOIN tuote USING (yhtio, tuoteno)
            WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
            $tyyppilisa
            and $wherelisa
            and tilausrivi.tuoteno != '{$yhtiorow['ennakkomaksu_tuotenumero']}'
            ORDER BY tilausrivi.tunnus";
  $tilvahresult = pupe_query($query);

  $query = "SELECT nimi, eposti
            FROM kuka
            WHERE yhtio = '$kukarow[yhtio]'
            and tunnus  = '$laskurow[myyja]'";
  $kresult = pupe_query($query);
  $krow = mysqli_fetch_array($kresult);

  if (mysqli_num_rows($tilvahresult) > 0) {
    $ulos  = "FAX: ".mb_ereg_replace("[^0-9]", "", $tilvahasiakas['fax'])."\n";

    if ($laskurow["tilaustyyppi"] == 'E') {
      $ulos .= sprintf("%-50.s", $yhtiorow['nimi'])                .t("Tilausvahvistus Ennakkomyynti", $kieli)."\n";
    }
    else {
      $ulos .= sprintf("%-50.s", $yhtiorow['nimi'])                .t("Tilausvahvistus", $kieli)."\n";
    }

    $ulos .= sprintf("%-50.s", $yhtiorow['osoite'])                ."\n";
    $ulos .= sprintf("%-50.s", $yhtiorow['postino']." ".$yhtiorow['postitp'])  .tv1dateconv($laskurow['luontiaika'])."\n";
    $ulos .= "\r\n";
    $ulos .= sprintf("%-50.s", t("Tilaaja", $kieli).":")              .t("Toimitusosoite", $kieli).":\n";
    $ulos .= sprintf("%-50.s", $laskurow['nimi'])                .$laskurow['toim_nimi']."\n";
    $ulos .= sprintf("%-50.s", $laskurow['nimitark'])              .$laskurow['toim_nimitark']."\n";
    $ulos .= sprintf("%-50.s", $laskurow['osoite'])                .$laskurow['toim_osoite']."\n";
    $ulos .= sprintf("%-50.s", $laskurow['postino']." ".$laskurow['postitp'])  .$laskurow['toim_postino']." ".$laskurow['toim_postitp']."\n";
    $ulos .= "\r\n";

    $useita = "EI";

    if ($laskurow["nippu"] > 0) {
      $query = "SELECT distinct(toimaika)
                FROM tilausrivi
                WHERE yhtio   = '$kukarow[yhtio]'
                $tyyppilisa
                and otunnus   IN ($laskurow[nippu])
                and toimaika != '0000-00-00'";
      $toimaikares = pupe_query($query);

      if (mysqli_num_rows($toimaikares) > 1) {
        $useita = "JOO";
      }
    }

    if ($useita == "JOO") {
      $laskurow["toimaika"] = t("Useita", $kieli);
    }
    elseif ($laskurow["toimvko"] != '') {
      $DAY_ARRAY = array(1 => t("Ma", $kieli), t("Ti", $kieli), t("Ke", $kieli), t("To", $kieli), t("Pe", $kieli), t("La", $kieli), t("Su", $kieli));

      $taika = t("Vko", $kieli)." ".date("W", strtotime($laskurow["toimaika"]));

      if ($laskurow['toimvko'] != '7') {
        $taika .= "/" .$DAY_ARRAY[$laskurow["toimvko"]];
      }

      $laskurow["toimaika"] = $taika;
    }
    else {
      $laskurow["toimaika"] = tv1dateconv($laskurow["toimaika"]);
    }

    $ulos .= sprintf("%-50.s", t("Toimitustapa", $kieli).": ".t_tunnus_avainsanat($laskurow['toimitustapa'], "selite", "TOIMTAPAKV", $kieli)).t("Toimitusaika", $kieli).": ".$laskurow['toimaika']."\n";
    $ulos .= sprintf("%-50.s", t("Tilausnumero", $kieli).": ".$tulostettava_tunnus)      .t("Tilausviite", $kieli).": ".$laskurow['viesti']."\n";
    $ulos .= sprintf("%-50.s", t("Tilaaja", $kieli).": ".$laskurow['tilausyhteyshenkilo'])  .t("Tilaajan tilausnumero", $kieli).": ".$laskurow['asiakkaan_tilausnumero']."\n";
    $ulos .= sprintf("%-50.s", t("Kohde", $kieli).": ".$laskurow['kohde'])          .t("Myyjä", $kieli).": ".$krow['nimi']."\n";

    $toimehto = t_avainsana("TOIMEHTO", $kieli, " and concat(selite,' ',selitetark) = '{$laskurow["toimitusehto"]}'", "", "", "selitetark");
    if ($toimehto == "") $toimehto = $laskurow["toimitusehto"];
    else $toimehto = mb_substr($laskurow["toimitusehto"], 0, 3)." ".$toimehto;

    $ulos .= sprintf("%-50.s", t("Toimitusehto", $kieli).": ".$toimehto)."\n";

    if (($naytatvale != 3 or $naytatvale != 4) and $laskurow['erikoisale'] > 0) {
      $ulos .= sprintf("%-50.s", t("Erikoisalennus", $kieli).": ".$laskurow['erikoisale']."%\n");
    }

    if ($yhtiorow['alv_kasittely'] != '') {
      $ulos .= sprintf("%-50.s", t("Hinnat ovat verottomia.", $kieli)."\n");
    }
    else {
      $ulos .= sprintf("%-50.s", t("Hinnat sisältävät arvonlisäveron.", $kieli)."\n");
    }

    if ($laskurow['comments']!='')
      $ulos .= t("Kommentti", $kieli).": ".$laskurow['comments']."\n";
    $ulos .= "\n";

    $ulos .= t("Nro", $kieli)." ".t("Nimitys", $kieli)."                       ".t("Tuotenumero", $kieli)."              ".t("Kpl", $kieli);

    if ($naytatvale != 3) {
      $ulos .= "   ".t("Hinta", $kieli)." ".t("Val", $kieli);
      if ($naytatvale != 4) {
        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          $ulos .= " ".t("Ale{$alepostfix}", $kieli);
        }
      }
    }

    $ulos .= "\n";
    $ulos .= "---------------------------------------------------------------------------------\n";

    $rivino=0;

    while ($tvtilausrivirow = mysqli_fetch_array($tilvahresult)) {
      $rivino++;

      $tvtilausrivirow['nimitys'] = t_tuotteen_avainsanat($tvtilausrivirow, 'nimitys', $kieli);

      if (mb_strlen($tvtilausrivirow['nimitys']) > 29) {
        $nimitysloput = mb_substr($tvtilausrivirow['nimitys'], 29);
        $tvtilausrivirow['nimitys'] = mb_substr($tvtilausrivirow['nimitys'], 0, 29);
      }
      $ulos .= sprintf("%03.0f", $rivino)." ";
      $ulos .= sprintf("%-30.s", $tvtilausrivirow['nimitys']);
      $ulos .= sprintf("%-20.s", $tvtilausrivirow['tuoteno']);

      if ($laskurow["tilaustyyppi"] == 'E') {
        $ulos .= sprintf("%8.s"  , $tvtilausrivirow['tilkpl']);
      }
      elseif ($tvtilausrivirow['var']!= 'J') {
        $ulos .= sprintf("%8.s"  , $tvtilausrivirow['varattu']);
      }
      else {
        $ulos .= sprintf("%8.s"  , $tvtilausrivirow['jt']).t("Jälkitoimitukseen", $kieli);
      }

      if ($naytatvale==3) {
        // mennään ohi koska ei haluta näyttää hintoja
      }
      elseif ($naytatvale==2) {
        $ulos .= sprintf("%8.s"  , $tvtilausrivirow['myyntihinta'])." ";
      }
      else {
        $ulos .= sprintf("%8.s"  , $tvtilausrivirow['hinta'])." ";
      }

      // kolmosella ei haluta näyttää myöskään valkoodia
      if ($naytatvale != 3) {
        $ulos .= sprintf("%3.s"  , $laskurow['valkoodi'])." ";
      }

      if ($naytatvale == 3 or $naytatvale == 4) {
        // mennään ohi koska ei haluta näyttää alea
      }
      elseif ($naytatvale==2) {
        $ulos .= sprintf("%6.s"  , $tvtilausrivirow['aleryhma']);
      }
      else {
        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          $ulos .= sprintf("%5.s"  , $tvtilausrivirow["ale{$alepostfix}"])."%";
        }
      }
      $ulos .= "\n";

      if ($nimitysloput != "") {
        $ulos .= "    ".sprintf("%-77.77s", $nimitysloput)."\n";
      }

      if ($tvtilausrivirow['kommentti'] != "") {
        $ulos .= "     * ".sprintf("%-75.75s", $tvtilausrivirow['kommentti'])."\n";
      }

    }

  } // end rivejä löytyi

  fputs($toot, $ulos);
  fclose($toot);

  $ftphost = $faxhost;
  $ftpuser = $faxuser;
  $ftppass = $faxpass;
  $ftppath = $faxpath;
  $ftpfile = realpath($tilvahvfilenimi);

  if ($kukarow["extranet"] == '') {
    require "../inc/ftp-send.inc";
  }
  else {
    require "ftp-send.inc";
  }

} // end email löytyi

if (mb_strpos($_SERVER['SCRIPT_NAME'], "tulostakopio.php") === FALSE and $yhtiorow['tilausvahvistus_tallenna'] == 'K') {
  $tilausvahvistus_tallenna = "/tmp/Tilausvahvistus-".md5(uniqid(mt_rand(), true)).".txt";
  file_put_contents($tilausvahvistus_tallenna, $ulos);
}
else {
  $tilausvahvistus_tallenna = "";
}

$ulos = "";
