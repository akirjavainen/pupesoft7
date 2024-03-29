<?php

// Nollataan keksit inventoinni alkuvalikossa
// jos $tee muuttujaa ei oo setattu getissä tai postissa
if (!isset($_GET['tee']) and !isset($_POST['tee'])) {
  setcookie("_tuotepaikka", null);
  setcookie("_varmistuskoodi", null);
}
// Talletetaan tuotepaikka ja varmistuskoodi keksiin, jolloin niitä ei tarvitse joka kerta syöttää.
// Molemmat pitää olla setattu että ne talletetaan!
elseif (!empty($_POST['tuotepaikka']) and !empty($_POST['varmistuskoodi'])) {
  setcookie("_tuotepaikka", $_POST['tuotepaikka']);
  setcookie("_varmistuskoodi", $_POST['varmistuskoodi']);
}

$_GET['ohje'] = 'off';
$_GET['no_css'] = 'yes';
$mobile = true;

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");

$errors = array();

// Haetaan tuotteita tai tuotepaikkaa
function hae($viivakoodi='', $tuoteno='', $tuotepaikka='') {
  global $kukarow;

  // Poistetaan tuotepaikasta välimerkit
  $hylly = preg_replace("/[^a-zA-ZåäöÅÄÖ0-9]/", "", $tuotepaikka);

  // Hakuehdot
  if ($tuoteno != '')    $params['tuoteno'] = "tuote.tuoteno = '{$tuoteno}'";

  if ($tuotepaikka != '') {
    $params['tuotepaikka'] = "hyllypaikka LIKE '{$hylly}%'";
  }

  // Viivakoodi case
  if ($viivakoodi != '') {
    $tuotenumerot = hae_viivakoodilla($viivakoodi);

    $param_viivakoodi = array();

    foreach ($tuotenumerot as $_tuoteno => $_arr) {
      array_push($param_viivakoodi, $_tuoteno);
    }

    $params['viivakoodi'] = "tuote.tuoteno in ('" . implode($param_viivakoodi, "','") . "')";
  }

  $osumat = array();

  if (!empty($params)) {

    $haku_ehto = implode($params, " AND ");

    $query = "SELECT
              tuote.tuoteno,
              inventointilistarivi.otunnus as inventointilista,
              inventointilistarivi.aika as inventointilista_aika,
              concat(  lpad(upper(tuotepaikat.hyllyalue), 5, '0'),
                  lpad(upper(tuotepaikat.hyllynro), 5, '0'),
                  lpad(upper(tuotepaikat.hyllyvali), 5, '0'),
                  lpad(upper(tuotepaikat.hyllytaso), 5, '0')) as sorttauskentta,
              concat_ws('-',tuotepaikat.hyllyalue, tuotepaikat.hyllynro,
                    tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) tuotepaikka
              FROM tuotepaikat
              JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
                AND varastopaikat.tunnus                   = tuotepaikat.varasto
                AND varastopaikat.toimipaikka              = '{$kukarow['toimipaikka']}'
                AND varastopaikat.tyyppi                   = ''
              )
              JOIN tuote on (tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno)
              LEFT JOIN inventointilistarivi ON (inventointilistarivi.yhtio = tuotepaikat.yhtio
                AND inventointilistarivi.tuotepaikkatunnus = tuotepaikat.tunnus
                AND inventointilistarivi.tila              = 'A')
              WHERE tuotepaikat.yhtio                      = '{$kukarow['yhtio']}'
              AND $haku_ehto
              LIMIT 200";
    $result = pupe_query($query);

    while ($row = mysqli_fetch_assoc($result)) {
      $osumat[] = $row;
    }
  }

  return $osumat;
}

/**
 * Tarkistaa varmistuskoodin syötetyn tuotepaikan ja koodin mukaan.
 *  jos varmistuskoodia ei annettu yritetään käyttää keksissä olevaa varmistuskoodia.
 */


function tarkista_varmistuskoodi($tuotepaikka, $varmistuskoodi = '', $haettu_tuotepaikalla = '') {
  // Muutetaan saatuo tuotepaikka arrayksi
  $hylly = explode('-', $tuotepaikka);

  // Jos haettu vajaalla tuotepaikalla, eli hyllyalue-hyllynro, niin tarkistetaan että jos
  // keksissä oleva tuotepaikka täsmää kyseistä aluetta. Jos täsmää niin varmistuskoodi tarkistetaan suoraan.
  $tuotealue = false;
  if (mb_stripos(str_replace('-', '', $_COOKIE['_tuotepaikka']), $haettu_tuotepaikalla) === 0) {
    $tuotealue = true;
  }

  // Jos varmistuskoodia ei saatu parametrissa, yritetään keksissä olevalla koodilla.
  if ($varmistuskoodi == '' and isset($_COOKIE['_varmistuskoodi']) and ($tuotepaikka == $_COOKIE['_tuotepaikka'] or $tuotealue == true)) {
    $varmistuskoodi = $_COOKIE['_varmistuskoodi'];
  }

  // Jos varmistuskoodi on edelleen tyhjä niin hylätään
  if ($varmistuskoodi == '') {
    return false;
  }
  // Verrataan varmistuskoodia hyllypaikan varmistuskoodiin
  else {
    $options = array('varmistuskoodi' => $varmistuskoodi);
    return tarkista_varaston_hyllypaikka($hylly[0], $hylly[1], $hylly[2], $hylly[3], $options);
  }
}

if (!isset($tee)) $tee = '';

// Alkuvalikko
if ($tee == '') {
  $title = t("Inventointi");
  include 'views/inventointi/index.php';
}

// Haku
if ($tee == 'haku') {

  $title = t("Vapaa Inventointi");

  // Haettu jollain
  if (isset($viivakoodi) or isset($tuoteno) or isset($tuotepaikka)) {
    if (!empty($tuotepaikka) and mb_strlen($tuotepaikka) < 2) {
      $errors[] = t("Tuotepaikka haun on oltava vähintään 2 merkkiä");
    }
    else {
      $tuotteet = hae($viivakoodi, $tuoteno, $tuotepaikka);
      if (count($tuotteet) == 0) $errors[] = "Ei löytynyt";
    }
  }

  $haku_tuotepaikalla = ($viivakoodi=='' and $tuoteno=='' and $tuotepaikka != '') ? $tuotepaikka : '';

  // Vain yksi osuma
  if (isset($tuotteet) and count($tuotteet) == 1) {
    // Suoraan varmistuskoodiin
    $url = http_build_query(array('tee' => 'laske',
        'tuotepaikka' => $tuotteet[0]['tuotepaikka'],
        'tuoteno' => $tuotteet[0]['tuoteno']));
    echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=inventointi.php?{$url}'>";
    exit();
  }

  // Löydetyt osumat
  if (isset($tuotteet) and count($tuotteet) > 0) {
    include 'views/inventointi/hakutulokset.php';
  }
  // Haku formi
  else {
    include 'views/inventointi/haku.php';
  }
}

// Inventointilistat
if ($tee == 'listat') {
  // Reservipaikka
  if (!isset($reservipaikka)) $reservipaikka = 'E';

  // Haetaan inventointilistat
  $query = "SELECT otunnus as lista
            FROM inventointilistarivi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tila    = 'A'
            GROUP BY 1
            ORDER BY 1";
  $result = pupe_query($query);

  $parametrit_tarkistettu = false;
  $listat = array();

  while ($row = mysqli_fetch_assoc($result)) {

    $query = "SELECT count(inventointilistarivi.tuoteno) as tuotteita,
              concat_ws('-', min(inventointilistarivi.hyllyalue), max(inventointilistarivi.hyllyalue)) as hyllyvali
              FROM inventointilistarivi
              WHERE inventointilistarivi.yhtio = '{$kukarow['yhtio']}'
              AND inventointilistarivi.otunnus = '{$row['lista']}'";
    $_count_res = pupe_query($query);
    $_count_row = mysqli_fetch_assoc($_count_res);

    $row['tuotteita'] = $_count_row['tuotteita'];
    $row['hyllyvali'] = $_count_row['hyllyvali'];

    $query = "SELECT inventointilistarivi.hyllyalue,
              inventointilistarivi.hyllynro,
              inventointilistarivi.hyllyvali,
              inventointilistarivi.hyllytaso
              FROM inventointilistarivi
              WHERE inventointilistarivi.yhtio = '{$kukarow['yhtio']}'
              AND inventointilistarivi.otunnus = '{$row['lista']}'";
    $_chk_res = pupe_query($query);

    while ($_chk_row = mysqli_fetch_assoc($_chk_res)) {

      if (!$parametrit_tarkistettu) {
        $parametrit_tarkistettu = true;
        $_varasto = kuuluukovarastoon($_chk_row['hyllyalue'], $_chk_row['hyllynro']);
        $onko_varaston_hyllypaikat_kaytossa = onko_varaston_hyllypaikat_kaytossa($_varasto);
      }

      if ($onko_varaston_hyllypaikat_kaytossa) {

        if (!empty($reservipaikka)) {
          $options = array('reservipaikka' => $reservipaikka);
        }
        else {
          $options = array();
        }

        $_chk = tarkista_varaston_hyllypaikka(
          $_chk_row['hyllyalue'],
          $_chk_row['hyllynro'],
          $_chk_row['hyllyvali'],
          $_chk_row['hyllytaso'],
          $options
        );

        if (!$_chk) {
          continue 2;
        }
      }
      else {
        break;
      }
    }

    $row['url'] = "?tee=laske&lista={$row['lista']}&reservipaikka={$reservipaikka}";
    $listat[] = $row;
  }

  if (count($listat) == 1) {
    // Jos löytyi vain yksi lista, inventoidaan suoraan sitä
    echo "<META HTTP-EQUIV='Refresh'CONTENT='0; URL=inventointi.php{$listat[0]['url']}'>";
  }
  elseif (count($listat) > 1) {
    $title = t('Useita listoja');
    include 'views/inventointi/listat.php';
  }
  else {
    $errors[] =  t("Inventoitavia eriä ei ole");
    $title = t("Inventointi");
    include 'views/inventointi/index.php';  }
}

// Lasketaan tuotteet
if ($tee == 'laske' or $tee == 'inventoi') {
  // Mitä parametrejä tarvitaan (tuoteno tai lista)
  assert(!empty($tuoteno) or !empty($lista));

  if (!isset($maara)) $maara = 0;
  if (!is_numeric($maara)) $errors[] = t("Määrän on oltava numero");

  // Inventoidaan listaa
  if (!empty($lista)) {

    // Hybridilistat, reservipaikka voi olla K tai E
    if (!isset($reservipaikka)) $reservipaikka = 'E';

    // Haetaan listan 'ensimmäinen' tuote
    $query = "SELECT
              tuote.nimitys,
              tuote.tuoteno,
              tuote.yksikko,
              tuotepaikat.saldo,
              tuotepaikat.hyllyalue,
              tuotepaikat.hyllynro,
              tuotepaikat.hyllyvali,
              tuotepaikat.hyllytaso,
              inventointilista.tunnus as inventointilista,
              inventointilista.naytamaara as inventointilista_naytamaara,
              tuotepaikat.tyyppi,
               concat_ws('-', tuotepaikat.hyllyalue, tuotepaikat.hyllynro,
                     tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) as tuotepaikka,
               concat(  lpad(upper(tuotepaikat.hyllyalue), 5, '0'),
                   lpad(upper(tuotepaikat.hyllynro), 5, '0'),
                   lpad(upper(tuotepaikat.hyllyvali), 5, '0'),
                   lpad(upper(tuotepaikat.hyllytaso), 5, '0')) as sorttauskentta
               FROM inventointilista
               JOIN inventointilistarivi ON (inventointilistarivi.yhtio = inventointilista.yhtio
                  AND inventointilistarivi.otunnus = inventointilista.tunnus)
               JOIN tuote on (tuote.yhtio=inventointilistarivi.yhtio and tuote.tuoteno=inventointilistarivi.tuoteno)
               JOIN tuotepaikat ON (tuotepaikat.yhtio = inventointilistarivi.yhtio
                AND tuotepaikat.tunnus             = inventointilistarivi.tuotepaikkatunnus)
               WHERE inventointilista.yhtio        = '{$kukarow['yhtio']}'
               AND inventointilista.tunnus         = '{$lista}'
               AND inventointilistarivi.tila       = 'A' # Inventoidut tuotteet on nollattu
               ORDER BY sorttauskentta, tuoteno
               LIMIT 1";
    $result = pupe_query($query);

    if (mysqli_num_rows($result) == 0) {
      include 'views/inventointi/erat_loppu.php';
      exit();
    }
  }
  // Inventoidaan haulla, tuote kerrallaan
  else {
    // Haetaan tuotteen ja tuotepaikan tiedot
    $query = "SELECT
              tuote.nimitys,
              tuote.tuoteno,
              tuote.yksikko,
              tuotepaikat.tyyppi,
              tuotepaikat.hyllyalue,
              tuotepaikat.hyllynro,
              concat_ws('-',tuotepaikat.hyllyalue, tuotepaikat.hyllynro,
                    tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) as tuotepaikka
              FROM tuotepaikat
              JOIN tuote on (tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno)
              WHERE tuotepaikat.yhtio = '{$kukarow['yhtio']}'
              AND tuote.tuoteno='{$tuoteno}'
              AND concat_ws('-',tuotepaikat.hyllyalue, tuotepaikat.hyllynro,
                    tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) = '{$tuotepaikka}'";
    $result = pupe_query($query);
  }
  $tuote = mysqli_fetch_assoc($result);

  $_varasto = kuuluukovarastoon($tuote['hyllyalue'], $tuote['hyllynro']);
  $onko_varaston_hyllypaikat_kaytossa = onko_varaston_hyllypaikat_kaytossa($_varasto);

  // Haetaan sscc jos tyyppi
  if ($tuote['tyyppi'] == 'S') {
    // etsitään suuntalavan sscc
    $suuntalava_query = "SELECT group_concat(sscc) as sscc
                         FROM tilausrivi
                         JOIN suuntalavat on (suuntalavat.yhtio=tilausrivi.yhtio AND suuntalavat.tunnus=tilausrivi.suuntalava)
                         WHERE tuoteno='{$tuote['tuoteno']}'
                           AND tilausrivi.tyyppi='O'
                           AND tilausrivi.suuntalava!=''
                           AND tilausrivi.yhtio='{$kukarow['yhtio']}'
                           AND concat_ws('-', hyllyalue, hyllynro,
                                 hyllyvali, hyllytaso) = '{$tuote['tuotepaikka']}'";
    $suuntalava_sscc = pupe_query($suuntalava_query);
    $suuntalava_sscc = mysqli_fetch_assoc($suuntalava_sscc);
    $sscc = $suuntalava_sscc['sscc'];
  }

  $query = "SELECT tunnus
            FROM tuotteen_toimittajat
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tuoteno = '{$tuote['tuoteno']}'
            ORDER BY if(jarjestys = 0, 9999, jarjestys), tunnus
            LIMIT 1";
  $paatoimittaja_result = pupe_query($query);
  $paatoimittaja_tunnus = mysqli_fetch_assoc($paatoimittaja_result);

  $pakkaukset = tuotteen_toimittajat_pakkauskoot($paatoimittaja_tunnus['tunnus']);

  $apulaskuri_url = '';
  // Jos pakkauksia ei löytynyt, ei näytetä apulaskuria
  if (count($pakkaukset)) {
    $apulaskuri_url = http_build_query(array('tee' => 'apulaskuri',
        'tuotepaikka' => $tuotepaikka,
        'tuoteno' => $tuote['tuoteno'],
        'lista' => $lista,
        'reservipaikka' => $reservipaikka));
  }

  // Jos varmistuskoodi kelpaa tai on keksissä tallessa
  if (!$onko_varaston_hyllypaikat_kaytossa or tarkista_varmistuskoodi($tuote['tuotepaikka'], $varmistuskoodi, $tuotepaikalla)) {
    $title = t("Laske määrä");
    $query = "SELECT *
              FROM avainsana
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND laji    = 'INVEN_LAJI'";
    $result = pupe_query($query);
    $inventointi_selitteet = array();
    while ($inventointi_selite = mysqli_fetch_assoc($result)) {
      $inventointi_selitteet[] = $inventointi_selite;
    }

    //Haetaan kerätty määrä
    $query = "SELECT ifnull(sum(if(keratty!='',tilausrivi.varattu,0)),0) keratty,  ifnull(sum(tilausrivi.varattu),0) ennpois
              FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
              WHERE yhtio    = '$kukarow[yhtio]'
              and tyyppi     in ('L','G','V')
              and tuoteno    = '$tuote[tuoteno]'
              and varattu    <> 0
              and laskutettu = ''
              and hyllyalue  = '$tuote[hyllyalue]'
              and hyllynro   = '$tuote[hyllynro]'
              and hyllyvali  = '$tuote[hyllyvali]'
              and hyllytaso  = '$tuote[hyllytaso]'";

    $hylresult = pupe_query($query);
    $hylrow = mysqli_fetch_assoc($hylresult);

    include 'views/inventointi/laske.php';

  }
  else {
    // Tarkistetaan että varaston_hyllypaikka on perustettu
    $hylly = explode('-', $tuotepaikka);
    if (isset($varmistuskoodi) and !tarkista_varaston_hyllypaikka($hylly[0], $hylly[1], $hylly[2], $hylly[3])) {
      $errors[] = "Tuotteen tietoja ei ole määritelty varaston hyllypaikoissa!";
    }
    // Tarkistetaan varmistuskoodi
    elseif (isset($varmistuskoodi)) $errors[] = "Virheellinen varmistuskoodi";

    $title = t("Varmistuskoodi");
    include 'views/inventointi/varmistuskoodi.php';
  }

  if ($tee == 'inventoi' and count($errors) == 0) {
    $tee = 'inventoidaan';
  }
}

if ($tee == 'apulaskuri') {
  // Pakkaus1
  $query = "SELECT
            if(myynti_era > 0, myynti_era, 1) as myynti_era,
            yksikko
            FROM tuote
            WHERE tuoteno='{$tuoteno}' AND yhtio='{$kukarow['yhtio']}'";
  $result = pupe_query($query);
  $p1 = mysqli_fetch_assoc($result);

  $query = "SELECT tunnus
            FROM tuotteen_toimittajat
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tuoteno = '{$tuoteno}'
            ORDER BY if(jarjestys = 0, 9999, jarjestys), tunnus
            LIMIT 1";
  $paatoimittaja_result = pupe_query($query);
  $paatoimittaja_tunnus = mysqli_fetch_assoc($paatoimittaja_result);

  $pakkaukset = tuotteen_toimittajat_pakkauskoot($paatoimittaja_tunnus['tunnus']);

  // pientä kaunistelua, ei turhia desimaaleja
  $p1['myynti_era'] = fmod($p1['myynti_era'], 1) ? $p1['myynti_era'] : round($p1['myynti_era']);

  // laitetaan vain kaksi ensimmäistä pakkauskokoa apulaskuriin
  $p2['myynti_era']   = $pakkaukset[0][0];
  $p2['yksikko']      = $pakkaukset[0][1];

  if (is_array($pakkaukset[1])) {
    $p3['myynti_era']   = $pakkaukset[1][0];
    $p3['yksikko']      = $pakkaukset[1][1];
  }
  else {
    $p3 = array();
  }

  $back = http_build_query(array('tee' => 'laske',
      'tuotepaikka' => $tuotepaikka,
      'tuoteno' => $tuoteno,
      'lista' => $lista,
      'reservipaikka' => $reservipaikka));

  $title = t("Apulaskuri");
  include 'views/inventointi/apulaskuri.php';
}

if ($tee == 'inventoidaan') {
  assert(!empty($tuoteno) or !empty($maara) or !empty($tuotepaikka));

  // Haetaan tuotteen tiedot
  $query = "SELECT tunnus,
            concat_ws('-',tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) tuotepaikka,
            hyllyalue,
            hyllynro,
            hyllyvali,
            hyllytaso
            FROM tuotepaikat
            WHERE tuoteno='{$tuoteno}'
            AND yhtio='{$kukarow['yhtio']}'
            AND concat_ws('-',tuotepaikat.hyllyalue, tuotepaikat.hyllynro,
                  tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) = '{$tuotepaikka}'
            ORDER BY tuotepaikka ";
  $result = pupe_query($query);
  $result = mysqli_fetch_assoc($result);

  // Jos tuotteen tiedot OK, voidaan inventoida
  if ($result) {
    $hylly = array($tuoteno, $result['hyllyalue'], $result['hyllynro'], $result['hyllyvali'], $result['hyllytaso']);
    $hash = implode('###', $hylly);

    $tuote = array($result['tunnus'] => $hash);
    $maara = array($result['tunnus'] => $maara);

    // inventointi
    $tee = 'VALMIS';
    $query = "SELECT *
              FROM avainsana
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$inventointi_seliteen_tunnus}'";
    $result = pupe_query($query);
    $row = mysqli_fetch_assoc($result);

    $inven_laji = $row['selite'];
    $lisaselite = t("Käsipääte").": " . $row['selitetark_4'];
    $mobiili = 'YES';

    require '../inventoi.php';

    // Jos inventoidaan listalta, palataan inventoimaan listan seuraava tuote.
    if ($lista != 0) {
      $paluu_url = http_build_query(array('tee' => 'laske', 'lista' => $lista, 'reservipaikka' => $reservipaikka));
    }
    // Jos inventoitu tuotepaikalla, palataan takaisin hakuun kyseisellä tuotepaikalla
    elseif (!empty($tuotepaikalla)) {
      $paluu_url = http_build_query(array('tee' => 'haku', 'viivakoodi' => '', 'tuoteno' => '', 'tuotepaikka' => $tuotepaikalla));
    }
    // Palataan alkuun
    else {
      $paluu_url = http_build_query(array('tee' => 'haku'));
    }
    echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=inventointi.php?".$paluu_url."'>";
  }
  else {
    $errors[] = t("Virhe inventoinnissa").".";
  }
  exit();
}

// Virheet
if (isset($errors)) {
  echo "<span class='error'>";
  foreach ($errors as $virhe) {
    echo "{$virhe}<br>";
  }
  echo "</span>";
}

require "inc/footer.inc";
