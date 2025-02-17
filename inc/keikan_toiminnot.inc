<?php

if (isset($_POST['ajax_toiminto'])) {
  $keikan_otunnus = (int) $_POST['keikan_otunnus'];

  // haetaan keikkaotsikon tiedot
  $query    = "SELECT * FROM lasku WHERE tunnus = '{$keikan_otunnus}' AND yhtio = '{$kukarow['yhtio']}'";
  $result   = pupe_query($query);
  $laskurow = mysqli_fetch_array($result);
}

function laske_hinta($laskurow, $tunnus) {
  global $kukarow, $yhtiorow;

  // katsotaan onko tälle keikalle jo liitetty vaihto-omaisuuslaskuja (kotimaa, eu tai ei-eu)
  $query = "SELECT sum(summa) summa, sum(arvo) arvo, sum(abs(summa)) abssumma, valkoodi, vienti
            FROM lasku
            WHERE yhtio     = '{$kukarow['yhtio']}'
            and tila        = 'K'
            and laskunro    = '{$laskurow['laskunro']}'
            and vanhatunnus <> 0
            and vienti      in ('C','F','I','J','K','L')
            GROUP BY valkoodi, vienti";
  $result = pupe_query($query);

  // jos on, haetaan liitettyjen laskujen
  if (mysqli_num_rows($result) == 1) {
    $kulurow = mysqli_fetch_array($result);
  }
  else {
    $kulurow = array("vienti" => "", "summa" => 0, "arvo" => 0, "valkoodi" => ""); // muuten tyhjää
  }

  // jos ollaan liitetty jo vaihto-omaisuuslasku, käytetään sen vientikenttää
  if ($kulurow["vienti"] != "") $laskurow["vienti"] = $kulurow["vienti"];

  $alvti = " if(tuotteen_toimittajat.osto_alv >= 0, tuotteen_toimittajat.osto_alv, tuote.alv) ";

  // jos kysessä on kotimainen vaihto-omaisuuslasku, pitää lisätä tuotteen hintaan alvi
  if ($laskurow['vienti'] == 'C' or $laskurow['vienti'] == 'J') {

    // Lasketaan verokanta
    if ($kulurow["arvo"] != 0) $simualv = round(100 * (($kulurow["summa"]/$kulurow["arvo"])-1), 2);
    else $simualv = 0;

    // Onko miellä oletuksista poikkeava alvi. Verokantoja ennen viimeisintä alv-muutosta.
    if (in_array($simualv, array(24, 23, 13, 9))) {
      $alvti = $simualv;
      $alvit = $simualv;
    }
    else {
      $alvit = "if(tuotteen_toimittajat.osto_alv >= 0, tuotteen_toimittajat.osto_alv, tuote.alv)";
    }

    if ($laskurow["maa"] != "" and $laskurow["maa"] != $yhtiorow["maa"]) {
      // tutkitaan ollaanko siellä alv-rekisteröity
      $alhqur = "SELECT *
                 FROM yhtion_toimipaikat
                 WHERE yhtio     = '{$kukarow['yhtio']}'
                 AND maa         = '{$laskurow['maa']}'
                 AND vat_numero != ''";
      $alhire = pupe_query($alhqur);

      // ollaan alv-rekisteröity
      if (mysqli_num_rows($alhire) == 1) {
        $alvit = "tuotteen_alv.alv";
        $alvti = " ifnull(tuotteen_alv.alv, 0) ";
      }
    }
  }
  else {
    $alvit = 0;
  }

  $query_ale_lisa = generoi_alekentta('O');

  $query = "SELECT
            round((tilausrivi.varattu+tilausrivi.kpl)*if (tuotteen_toimittajat.tuotekerroin<=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),4) ulkkpl,
            round((tilausrivi.varattu+tilausrivi.kpl) * (tilausrivi.hinta * {$query_ale_lisa}) * if (tuotteen_toimittajat.tuotekerroin<=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),'{$yhtiorow['hintapyoristys']}') rivihinta,
            round((tilausrivi.varattu+tilausrivi.kpl) * if (tuotteen_toimittajat.tuotekerroin<=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin) * tilausrivi.hinta * {$query_ale_lisa} *
                        (1+(if ((SELECT max(kaytetty) kaytetty
                            FROM sarjanumeroseuranta
                            WHERE sarjanumeroseuranta.yhtio=tilausrivi.yhtio
                            and sarjanumeroseuranta.tuoteno=tilausrivi.tuoteno
                            and ((tilausrivi.varattu+tilausrivi.kpl < 0 and sarjanumeroseuranta.myyntirivitunnus=tilausrivi.tunnus) or (tilausrivi.varattu+tilausrivi.kpl > 0 and sarjanumeroseuranta.ostorivitunnus=tilausrivi.tunnus))) = 'K', 0, {$alvit})/100)) ,'{$yhtiorow['hintapyoristys']}') alerivihinta
            FROM tilausrivi
            JOIN lasku ON tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and lasku.liitostunnus = '{$laskurow['liitostunnus']}' and lasku.tila in ('O','K','D')
            JOIN tuote use index (tuoteno_index) ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
            LEFT JOIN tuotteen_toimittajat use index (yhtio_tuoteno) ON tuotteen_toimittajat.yhtio=tuote.yhtio and tuotteen_toimittajat.tuoteno=tuote.tuoteno and tuotteen_toimittajat.liitostunnus=lasku.liitostunnus
            LEFT JOIN tuotteen_alv ON (tuotteen_alv.yhtio = tilausrivi.yhtio AND tuotteen_alv.tuoteno = tilausrivi.tuoteno AND tuotteen_alv.maa = '{$laskurow['maa']}')
            WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
            and tilausrivi.tunnus  = '{$tunnus}'
            and tilausrivi.tyyppi  = 'O'
            GROUP BY tilausrivi.tunnus";
  $hintojen_haku_res = pupe_query($query);
  $hintojen_haku_row = mysqli_fetch_assoc($hintojen_haku_res);

  return $hintojen_haku_row;
}

/* Kohdista tilausrivi
*/
function kohdista_rivi($laskurow, $valittutunnus, $valittuotunnus, $otunnus, $suuntalavan_tunnus, $suoratoimitusrivi = '') {
  global $kukarow, $yhtiorow;

  $suuntalava_querylisa = '';

  if ($yhtiorow['suuntalavat'] == 'S') {

    if (trim($suoratoimitusrivi) != "" and (trim($suuntalavan_tunnus) == "" or $suuntalavan_tunnus == 0)) {

      $query = "SELECT suuntalavat.*
                FROM suuntalavat
                JOIN suuntalavat_saapuminen ON (
                    suuntalavat.yhtio                      = suuntalavat_saapuminen.yhtio
                    AND suuntalavat_saapuminen.saapuminen  = '{$otunnus}'
                    AND suuntalavat_saapuminen.saapuminen != 0
                    AND suuntalavat.tunnus                 = suuntalavat_saapuminen.suuntalava)
                WHERE suuntalavat.yhtio                    = '{$kukarow['yhtio']}'
                AND suuntalavat.tila                       = ''
                AND suuntalavat.sscc                       = 'Suoratoimitus'";
      $chk_suuntalava_res = pupe_query($query);
      $chk_suuntalava_row = mysqli_fetch_assoc($chk_suuntalava_res);

      if (mysqli_num_rows($chk_suuntalava_res) == 0) {

        $suuntalavan_tunnus = "";

        // Tehdään uusi suuntalava
        $tee = "lisaa";
        // $otunnus = $keikan_tunnus;
        $sscc = "Suoratoimitus";
        $tyyppi = 0;
        $keraysvyohyke = "Suoratoimitus";
        $usea_keraysvyohyke = 'K';
        $kaytettavyys = "Y";
        $terminaalialue = "Suoratoimitus";
        $korkeus = 0;
        $paino = 0;
        $alkuhyllyalue = "";
        $alkuhyllynro = "";
        $alkuhyllyvali = "";
        $alkuhyllytaso = "";
        $loppuhyllyalue = "";
        $loppuhyllynro = "";
        $loppuhyllyvali = "";
        $loppuhyllytaso = "";
        $automaattinen_paivitys = "S";
        $suuntalavat_ei_kayttoliittymaa = "KYLLA";

        require "../tilauskasittely/suuntalavat.inc";

        $suuntalavan_tunnus = (int) $uusi_suuntalavan_tunnus;
      }
      else {
        $suuntalavan_tunnus = $chk_suuntalava_row['tunnus'];
      }
    }

    if ($suuntalavan_tunnus > 0) {
      $suuntalava_querylisa = ", suuntalava = $suuntalavan_tunnus ";
    }
    else {
      // toistaiseksi ei päivitetä suuntalavaa tilausriville kohdistuksen yhteydessä automaattisesti
      // $suuntalava = hae_suuntalava($otunnus, $valittutunnus);
      // $suuntalava_querylisa = ", suuntalava = $suuntalava ";
    }
  }

  if ($otunnus == 0) {
    $updatelisa = ", suuntalava = 0, kerattyaika = '0000-00-00 00:00:00', keratty = '', erikoisale_saapuminen = 0 ";
  }
  else {
    $updatelisa = "$suuntalava_querylisa , kerattyaika = now(), keratty = '{$kukarow['kuka']}', erikoisale_saapuminen = '{$laskurow['erikoisale_saapuminen']}' ";
  }

  // Haetaan rivit ennen päivitystä
  $vanhathinnat = array();

  $updquery = "SELECT tunnus
               FROM tilausrivi use index (yhtio_otunnus)
               WHERE yhtio = '{$kukarow['yhtio']}'
               AND otunnus IN ('{$valittuotunnus}','{$otunnus}')
               AND (tunnus = '{$valittutunnus}' OR perheid2 = '{$valittutunnus}')";
  $selresult = pupe_query($updquery);

  while ($updrow = mysqli_fetch_assoc($selresult)) {
    $vanhathinnat[$updrow["tunnus"]] = laske_hinta($laskurow, $updrow["tunnus"]);
  }

  // Viedään varastoon (varastoon=1) kun räplätään ruksia
  $query = "UPDATE tilausrivi use index (yhtio_otunnus) SET
            uusiotunnus = '{$otunnus}',
            varastoon   = 1
            {$updatelisa}
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND otunnus IN ('{$valittuotunnus}','{$otunnus}')
            AND (tunnus = '{$valittutunnus}' OR perheid2 = '{$valittutunnus}')";
  $result = pupe_query($query);

  if ($otunnus == 0 and $valittutunnus != '') {

    // Katsotaan löytyykö rivi asn_sanomat-tauluun kohdistettuna laskuun
    // Jos löytyy, poistetaan kohdistus
    $query = "SELECT *
              FROM asn_sanomat
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND laji    in ('tec','mav')
              AND MATCH (tilausrivi) AGAINST ('{$valittutunnus}' IN BOOLEAN MODE)";
    $chk_exist_res = pupe_query($query);

    if (mysqli_num_rows($chk_exist_res) > 0) {

      while ($chk_exist_row = mysqli_fetch_assoc($chk_exist_res)) {

        $arr = explode(",", $chk_exist_row['tilausrivi']);
        $arr = array_flip($arr);
        unset($arr[$valittutunnus]);
        $arr = array_flip($arr);

        $query = "UPDATE asn_sanomat SET
                  tilausrivi  = '".implode(",", $arr)."'
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = '{$chk_exist_row['tunnus']}'";
        pupe_query($query);
      }
    }
  }

  // Haetaan päivitetyt rivit
  $uudethinnat = array();
  mysqli_data_seek($selresult, 0);

  while ($updrow = mysqli_fetch_assoc($selresult)) {
    $uudethinnat[$updrow["tunnus"]] = laske_hinta($laskurow, $updrow["tunnus"]);
  }

  $data = array(
    'vanhat_hinnat' => $vanhathinnat,
    'uudet_hinnat' => $uudethinnat,
    'suuntalava' => 0,
    'suuntalava_sscc' => ''
  );

  // Tuotteet päivitetään kohdistuksissa automaattisesti oikeille suuntalavoille, mikäli sellainen on jo perustettu (suuntalavan määriteltyjen varastopaikkojen sekä keräysvyöhykkeen mukaan)
  if ($yhtiorow['suuntalavat'] == 'S' and $otunnus > 0) {
    if ($suuntalavan_tunnus > 0) {

      $otunnus = trim($otunnus);

      $data['suuntalava'] = $suuntalavan_tunnus;

      $query = "SELECT suuntalavat.sscc, IFNULL(suuntalavat_saapuminen.saapuminen, '') AS saapuminen
                FROM suuntalavat
                LEFT JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = '{$otunnus}')
                WHERE suuntalavat.yhtio = '{$kukarow['yhtio']}'
                AND suuntalavat.tunnus  = '{$suuntalavan_tunnus}'";
      $sscc_fetch_res = pupe_query($query);
      $sscc_fetch_row = mysqli_fetch_assoc($sscc_fetch_res);

      $data['suuntalava_sscc'] = $sscc_fetch_row['sscc'];

      if ((int) $suuntalavan_tunnus != 0 and $sscc_fetch_row['saapuminen'] == '') {
        $query = "INSERT INTO suuntalavat_saapuminen SET
                  yhtio      = '{$kukarow['yhtio']}',
                  suuntalava = '{$suuntalavan_tunnus}',
                  saapuminen = '{$otunnus}',
                  laatija    = '{$kukarow['kuka']}',
                  luontiaika = now(),
                  muutospvm  = now(),
                  muuttaja   = '{$kukarow['kuka']}'";
        $insert_res = pupe_query($query);
      }
    }
    else {
      // toistaiseksi ei setata suuntalavaa dropdowniin kohdistuksen yhteydessä automaattisesti
      // $data['suuntalava'] = $suuntalava;
      $data['suuntalava'] = 0;
    }
  }

  return $data;
}

if ($_POST['ajax_toiminto'] == 'suuntalava') {
  $tunnus = sanitize_string($_POST['tunnus']);
  $suuntalava = sanitize_string($_POST['suuntalava']);

  $query = "SELECT tila
            FROM suuntalavat
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = '{$suuntalava}'";
  $tila_chk_res = pupe_query($query);
  $tila_chk_row = mysqli_fetch_assoc($tila_chk_res);

  if ($tila_chk_row['tila'] == 'S') {
    echo json_encode('error');
  }
  else {
    $query = "UPDATE tilausrivi SET
              suuntalava  = '{$suuntalava}'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$tunnus}'";
    $update_res = pupe_query($query);

    $query = "SELECT uusiotunnus
              FROM tilausrivi
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$tunnus}'";
    $otunnus_fetch_res = pupe_query($query);
    $otunnus_fetch_row = mysqli_fetch_assoc($otunnus_fetch_res);

    $uusiotunnus = $otunnus_fetch_row['uusiotunnus'];

    if ($suuntalava != 0 and $uusiotunnus != 0) {
      $query = "SELECT saapuminen
                FROM suuntalavat_saapuminen
                WHERE yhtio    = '{$kukarow['yhtio']}'
                AND suuntalava = '{$suuntalava}'
                AND saapuminen = '{$uusiotunnus}'";
      $fetch_res = pupe_query($query);

      if (mysqli_num_rows($fetch_res) == 0) {
        $query = "INSERT INTO suuntalavat_saapuminen SET
                  yhtio      = '{$kukarow['yhtio']}',
                  suuntalava = '{$suuntalava}',
                  saapuminen = '{$uusiotunnus}',
                  laatija    = '{$kukarow['kuka']}',
                  luontiaika = now(),
                  muutospvm  = now(),
                  muuttaja   = '{$kukarow['kuka']}'";
        $insert_res = pupe_query($query);
      }
    }

    echo json_encode('ok');
  }
}
elseif ($_POST['ajax_toiminto'] == 'rivi_toimitettuaika') {
  $tunnus = sanitize_string($_POST['tunnus']);

  if ($rivi_taika_vv < 2000) $rivi_taika_vv += 2000;

  $query = "UPDATE tilausrivin_lisatiedot SET
            suoratoimitettuaika  = '$rivi_taika_vv-$rivi_taika_kk-$rivi_taika_pp'
            WHERE yhtio          = '{$kukarow['yhtio']}'
            AND tilausrivitunnus = '{$tunnus}'";
  $update_res = pupe_query($query);

  echo json_encode('ok');

}
elseif ($_POST['ajax_toiminto'] == 'tarkista_saapuminen') {
  // Tarkistetaan onko saapuminen suljettu
  // saapumisen tunnus
  $otunnus = (int) $_POST['otunnus'];

  if ($otunnus == 0) {
    echo json_encode('error');
  }

  $query = "SELECT tunnus
            FROM lasku
            WHERE yhtio     = '{$kukarow['yhtio']}'
            AND tila        = 'K'
            AND alatila     = ''
            AND vanhatunnus = 0
            AND tunnus      = '{$otunnus}'";
  $res = pupe_query($query);

  echo mysqli_num_rows($res) == 0 ? json_encode('suljettu') : json_encode('ok');
}
elseif ($_POST['ajax_toiminto'] == 'kohdista_rivi') {

  // Valittu tilausrivi ja ostotilaus
  list($valittutunnus, $valittuotunnus) = explode("###", $_POST['valittu']);

  // saapuminen
  $otunnus = (int) $_POST['otunnus'];
  $keikan_otunnus = (int) $_POST['keikan_otunnus'];

  $suuntalavan_tunnus = $_POST['suuntalavan_tunnus'];
  $suoratoimitusrivi  = $_POST['suoratoimitusrivi'];
  $toimittajaid = (int) $_POST['toimittajaid'];

  $data = kohdista_rivi($laskurow, $valittutunnus, $valittuotunnus, $otunnus, $suuntalavan_tunnus, $suoratoimitusrivi);

  if (empty($otunnus)) {
    $otunnus = $keikan_otunnus;
  }

  $kulurow = saapumisen_laskut($laskurow);
  list(
    $hintarow,
    $valitutrivit,
    $alvit,
    $alvti
  ) = paivita_saapumisen_summa($laskurow, $kulurow, $toimittajaid, $otunnus);

  $data['laskunsumma'] = $hintarow['hinta'];

  echo json_encode($data);
}
elseif ($_POST['ajax_toiminto'] == 'rivi_ale') {
  $ale        = str_replace(",", ".", $_POST['ale']);
  $tunnus     = $_POST['tunnus'];
  $otunnus     = $_POST['otunnus'];
  $monesko_kentta = $_POST['monesko_kentta'];

  if ($monesko_kentta == "E") {
    $aleupd = " erikoisale = '{$ale}' ";
  }
  else {
    $aleupd = " ale{$monesko_kentta} = '{$ale}' ";
  }

  $query  = "UPDATE tilausrivi SET
             $aleupd
             WHERE yhtio = '{$kukarow['yhtio']}'
             AND tunnus  = '{$tunnus}'";
  $result = pupe_query($query);

  $hinnat = laske_hinta($laskurow, $tunnus);

  $data = array('rivihinta' => (float) $hinnat['rivihinta'], 'alerivihinta' => (float) $hinnat['alerivihinta']);

  echo json_encode($data);
}
elseif ($_POST['ajax_toiminto'] == 'rivi_hinta') {

  // otetaan muuttujiin talteen tarpeellisia tietoja
  $hinta    = str_replace(",", ".", $_POST['hinta']);
  $tunnus    = $_POST['tunnus'];
  $otunnus   = $_POST['otunnus'];

  // päivitetään haluttu hinta tilausriville
  $query  = "UPDATE tilausrivi set hinta='{$hinta}' where yhtio='{$kukarow['yhtio']}' and tunnus='{$tunnus}' and hinta<>'{$hinta}'";
  $result = pupe_query($query);

  $hinnat = laske_hinta($laskurow, $tunnus);

  $data = array('hinta' => $hinta, 'rivihinta' => (float) $hinnat['rivihinta'], 'alerivihinta' => (float) $hinnat['alerivihinta']);

  echo json_encode($data);
}
elseif ($_POST['ajax_toiminto'] == 'myyntihinta' and tarkista_oikeus('yllapito.php', 'tuote', 1) and $yhtiorow['myyntihinta_paivitys_saapuminen'] == 'K') {
  $myyntihinta = (float) str_replace(",", ".", $_POST['myyntihinta']);
  $tunnus = (int) $_POST['tunnus'];

  $query = "SELECT tuoteno
            FROM tilausrivi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = '{$tunnus}'";
  $tuoteno_chk_res = pupe_query($query);

  if (mysqli_num_rows($tuoteno_chk_res) == 1) {
    $tuoteno_chk_row = mysqli_fetch_assoc($tuoteno_chk_res);

    $query = "UPDATE tuote SET
              myyntihinta = '{$myyntihinta}'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tuoteno = '{$tuoteno_chk_row['tuoteno']}'";
    pupe_query($query);

    echo json_encode(array('ok' => true));
  }
  else {
    echo json_encode(array('ok' => false));
  }
}
elseif ($_POST['ajax_toiminto'] == 'rivi_kpl') {
  $kpl    = str_replace(",", ".", $_POST['kpl']);
  $tunnus  = $_POST['tunnus'];
  $otunnus = $_POST['otunnus'];

  // Toiminta funktioitu
  $kaikki_ok = paivita_tilausrivin_kpl($tunnus, $kpl);

  $hinnat = laske_hinta($laskurow, $tunnus);

  $data = array('rivihinta' => (float) $hinnat['rivihinta'], 'alerivihinta' => (float) $hinnat['alerivihinta'], 'ulkkpl' => (float) $hinnat['ulkkpl']);

  echo json_encode($data);
}
elseif ($_POST['ajax_toiminto'] == 'rivi_kpl_tuoteperhe_lapsi') {
  $tunnus  = $_POST['tunnus'];
  $otunnus = $_POST['otunnus'];
  $uuden_rivin_kpl = $_POST['uuden_rivin_kpl'];

  // Toiminta funktioitu
  // päivitetään lapset
  $kaikki_ok = paivita_tilausrivin_kpl($tunnus, $uuden_rivin_kpl, $otunnus, TRUE);

  echo json_encode(array('paivitetaanko' => $kaikki_ok));

}
elseif ($_POST['ajax_toiminto'] == 'rivi_kpl_splittaus') {
  $tunnus      = $_POST['tunnus'];
  $otunnus      = $_POST['otunnus'];
  $uuden_rivin_kpl = $_POST['uuden_rivin_kpl'];
  $vanhan_rivin_kpl = $_POST['vanhan_rivin_kpl'];

  $varastoonvaiei = (isset($_POST['varastoon'])) ? $_POST['varastoon'] : FALSE;

  $uuden_rivin_id = splittaa_tilausrivi($tunnus, $uuden_rivin_kpl, $varastoonvaiei, FALSE, FALSE, $vanhan_rivin_kpl);
  $hinnat = laske_hinta($laskurow, $uuden_rivin_id);

  $rivi['rivihinta'] = $hinnat['rivihinta'];
  $rivi['alerivihinta'] = $hinnat['alerivihinta'];
  $rivi['tunnus'] = $uuden_rivin_id;
  $rivi['varattu'] = $uuden_rivin_kpl;
  $rivi['toimaika'] = tv1dateconv($rivi['toimaika']);
  $rivi['yksikko'] = t_avainsana("Y", "", "and avainsana.selite='$rivi[yksikko]'", "", "", "selite");

  echo json_encode($rivi);
}
elseif ($_POST['ajax_toiminto'] == 'hae_saldo_myytavissa') {
  $_tuoteno = $_POST['id'];
  $_varasto = (int) $_POST['varasto'];

  list ($saldo, $hyllyssa, $myytavissa, $bool) = saldo_myytavissa($_tuoteno, "", $_varasto);

  echo json_encode(array(
      'saldo' => $saldo,
      'hyllyssa' => $hyllyssa,
      'myytavissa' => $myytavissa,
    ));
}
elseif ($_POST['ajax_toiminto'] == 'lisaa_suuntalava') {
  $otunnus      = $_POST['otunnus'];
  $suuntalavan_tunnus = 0;
  $tee = "massakausi";
  $suuntalavat_ei_kayttoliittymaa = "KYLLA";

  require "../tilauskasittely/suuntalavat.inc";

  $sscc_gen = uusi_sscc_nro();

  $params = array(
    "sscc" => $sscc_gen,
    "usea_keraysvyohyke" => "K",
    "keraysvyohyke" => $_POST['keraysvyohyke'],
    "kaytettavyys" => $_POST['kaytettavyys'],
    "terminaalialue" => utf8_decode($_POST['terminaalialue']),
    "automaattinen_paivitys" => "S",
    "valittutunnus" => $_POST['tunnus'],
    "tyyppi" => $_POST['tyyppi'],
    "korkeus" => 0,
    "paino" => 0,
    "alkuhyllyalue" => "",
    "alkuhyllynro" => "",
    "alkuhyllyvali" => "",
    "alkuhyllytaso" => "",
    "loppuhyllyalue" => "",
    "loppuhyllynro" => "",
    "loppuhyllyvali" => "",
    "loppuhyllytaso" => ""
  );

  $suuntalavan_tunnus = lisaa_suuntalava($otunnus, $params);

  // Koska kyseessä on kopioitu tilausrivi, varmistetaan vielä että juuri luotu suuntalava setataan uudelle riville
  $query = "UPDATE tilausrivi
            SET tilausrivi.suuntalava = '{$suuntalavan_tunnus}'
            WHERE tilausrivi.yhtio     = '{$kukarow['yhtio']}'
            AND tilausrivi.uusiotunnus = '{$otunnus}'
            AND tilausrivi.tunnus      = '{$_POST['tunnus']}'
            AND tilausrivi.kpl         = 0
            AND tilausrivi.tyyppi      = 'O'";

  $tilausrivi_res = pupe_query($query);

  $kirjoitin = (int) $_POST['kirjoitin'];

  if ($kirjoitin != 0) {

    $tee = "tulosta_sscc";
    $sscc_kopio = $sscc_gen;
    $kappalemaara = (int) $_POST['kappalemaara'];

    // Tarvitaan otunnus, kirjoitin, tee=tulosta_sscc, kappalemaara, suuntalavan tunnus, sscc_kopio != '', $suuntalavat_ei_kayttoliittymaa
    require "../tilauskasittely/tulosta_sscc.inc";
  }

  // Siirtovalmiiksi vasta tässä vaiheessa, että saadaan tulostettua oikealla sscc:llä tarra
  // Suuntalava siirtovalmiiksi - tarvitaan $suuntalavat_ei_kayttoliittymaa != '', $tee = 'siirtovalmis', $suuntalavan_tunnus

  $tee = 'siirtovalmis';
  require "../tilauskasittely/suuntalavat.inc";

  echo json_encode(array('suuntalavan_tunnus' => $suuntalavan_tunnus));
}

if (isset($_POST['ajax_toiminto'])) exit;
