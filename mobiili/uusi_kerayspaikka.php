<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");

if (!isset($saapumisnro_haku)) $saapumisnro_haku = '';

if (isset($tullaan) and $tullaan == 'tuotteen_hyllypaikan_muutos') {

  if (empty($tuotepaikan_tunnus)) {
    echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=hyllysiirrot.php'>";
    exit();
  }

  $data = array(
    'tuotepaikan_tunnus' => $tuotepaikan_tunnus,
    'mista_koodi' => $mista_koodi,
    'minne_koodi' => $minne_koodi,
    'saapumisnro_haku' => $saapumisnro_haku
  );

  $url = http_build_query($data);
}
else {
  $alusta_tunnus = (int) $alusta_tunnus;
  $liitostunnus = (int) $liitostunnus;
  $tilausrivi = (int) $tilausrivi;

  $data = array(
    'alusta_tunnus' => $alusta_tunnus,
    'liitostunnus' => $liitostunnus,
    'tilausrivi' => $tilausrivi,
    'ostotilaus' => $ostotilaus,
    'saapuminen' => $saapuminen,
    'tilausten_lukumaara' => $tilausten_lukumaara,
    'manuaalisesti_syotetty_ostotilausnro' => $manuaalisesti_syotetty_ostotilausnro,
    'tuotenumero' => $tuotenumero,
    'ennaltakohdistettu' => $ennaltakohdistettu,
    'saapumisnro_haku' => $saapumisnro_haku,
  );

  $url = http_build_query($data);
}

// Virheet
$errors = array();
if (!isset($tuotepaikka)) $tuotepaikka = '';

// Suuntalavan kanssa
if (!empty($alusta_tunnus) and $yhtiorow['suuntalavat'] != "") {
  $res = suuntalavan_tuotteet(array($alusta_tunnus), $liitostunnus, "", "", "", $tilausrivi);
  $row = mysqli_fetch_assoc($res);
}
elseif (isset($tullaan) and $tullaan == 'tuotteen_hyllypaikan_muutos') {

  $query = "SELECT tuotepaikat.*, tuote.yksikko
            FROM tuotepaikat
            JOIN tuote ON (tuote.yhtio = tuotepaikat.yhtio AND tuote.tuoteno = tuotepaikat.tuoteno)
            WHERE tuotepaikat.yhtio = '{$kukarow['yhtio']}'
            AND tuotepaikat.tunnus  = '{$tuotepaikan_tunnus}'";
  $res = pupe_query($query);
  $row = mysqli_fetch_assoc($res);

}
// Ilman suuntalavaa
else {
  $query = "SELECT
            tilausrivi.*,
            tuotteen_toimittajat.toim_tuoteno
            FROM tilausrivi
            LEFT JOIN tuotteen_toimittajat on (tuotteen_toimittajat.tuoteno=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno)
            WHERE tilausrivi.tunnus = '{$tilausrivi}'
            AND tilausrivi.yhtio    = '{$kukarow['yhtio']}'";
  $row = mysqli_fetch_assoc(pupe_query($query));
}

$_varasto = kuuluukovarastoon($row['hyllyalue'], $row['hyllynro']);
$onko_varaston_hyllypaikat_kaytossa = onko_varaston_hyllypaikat_kaytossa($_varasto);

if (!isset($tullaan) or $tullaan != 'tuotteen_hyllypaikan_muutos') {
  // Tarkistetaan tuotteen saldo
  list($saldo['saldo'], $saldo['hyllyssa'], $saldo['myytavissa']) = saldo_myytavissa($row['tuoteno'], '', '', '0', $row['hyllyalue'], $row['hyllynro'], $row['hyllyvali'], $row['hyllytaso']);
  $saldo['myytavissa'] = ($saldo['myytavissa'] > 0) ? $saldo['myytavissa'] : 0;
}

if (isset($submit) and trim($submit) != '') {

  switch ($submit) {
  case 'submit':

    // Parsitaan uusi tuotepaikka
    // Jos tuotepaikka on luettu viivakoodina, muotoa (C21 045) tai (21C 03V)
    if (preg_match('/^([a-zåäö#0-9]{2,4} [a-zåäö#0-9]{2,4})/i', $tuotepaikka)) {

      // Pilkotaan viivakoodilla luettu tuotepaikka välilyönnistä
      list($alku, $loppu) = explode(' ', $tuotepaikka);

      // Mätsätään numerot ja kirjaimet erilleen
      preg_match_all('/([0-9]+)|([a-z]+)/i', $alku, $alku);
      preg_match_all('/([0-9]+)|([a-z]+)/i', $loppu, $loppu);

      // Hyllyn tiedot oikeisiin muuttujiin
      $hyllyalue = $alku[0][0];
      $hyllynro  = $alku[0][1];
      $hyllyvali = $loppu[0][0];
      $hyllytaso = $loppu[0][1];

      // Kaikkia tuotepaikkoja ei pystytä parsimaan
      if ($hyllyalue == '' or $hyllynro == '' or $hyllyvali == '' or $hyllytaso == '') {
        $errors[] = t("Tuotepaikan haussa virhe, yritä syöttää tuotepaikka käsin") . " ($tuotepaikka)";
      }
    }
    // Tuotepaikka syötetty manuaalisesti (C-21-04-5) tai (C 21 04 5)
    elseif (mb_strstr($tuotepaikka, '-') or mb_strstr($tuotepaikka, ' ')) {
      // Parsitaan tuotepaikka omiin muuttujiin (erotelto välilyönnillä)
      if (preg_match('/\w+\s\w+\s\w+\s\w+/i', $tuotepaikka)) {
        list($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso) = explode(' ', $tuotepaikka);
      }
      // (erotelto väliviivalla)
      elseif (preg_match('/\w+-\w+-\w+-\w+/i', $tuotepaikka)) {
        list($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso) = explode('-', $tuotepaikka);
      }

      // Ei saa olla tyhjiä kenttiä
      if ($hyllyalue == '' or $hyllynro == '' or $hyllyvali == '' or $hyllytaso == '') {
        $errors[] = t("Virheellinen tuotepaikka") . ". ($tuotepaikka)";
      }
    }
    else {
      $errors[] = t("Virheellinen tuotepaikka, yritä syöttää tuotepaikka käsin") . " ($tuotepaikka)";
    }

    // Tarkistetaan että tuotepaikka on olemassa
    if ($onko_varaston_hyllypaikat_kaytossa and count($errors) == 0 and !tarkista_varaston_hyllypaikka($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso)) {
      $errors[] = t("Varaston tuotepaikkaa ($hyllyalue-$hyllynro-$hyllyvali-$hyllytaso) ei ole perustettu").'.';
    }

    // Ei sarjanumerollisia tuotteita
    $query = "SELECT sarjanumeroseuranta
              FROM tuote
              WHERE yhtio='{$kukarow['yhtio']}'
              AND tuoteno='{$row['tuoteno']}'";
    $result = pupe_query($query);
    $tuote = mysqli_fetch_assoc($result);

    if (isset($siirra_saldot) and count($siirra_saldot) > 0) {
      if (count($siirra_saldot) == 1 and $siirra_saldot[0] == 'default') {
        $siirra_saldot = '';
      }
      else {
        $siirra_saldot = $siirra_saldot[1];
      }
    }
    else {
      $siirra_saldot = '';
    }

    if (isset($poista_vanha_tuotepaikka) and count($poista_vanha_tuotepaikka) > 0) {
      if (count($poista_vanha_tuotepaikka) == 1 and $poista_vanha_tuotepaikka[0] == 'default') {
        $poista_vanha_tuotepaikka = '';
      }
      else {
        $poista_vanha_tuotepaikka = $poista_vanha_tuotepaikka[1];
      }
    }
    else {
      $poista_vanha_tuotepaikka = '';
    }

    if ($tuote['sarjanumeroseuranta'] != '' and $siirra_saldot == 'on') {
      $errors[] = t("Saldojen siirto ei tue sarjanumerollisia tuotteita");
    }

    if (count($errors) == 0) {

      // Oletuspaikka checkboxi
      if (count($oletuspaikka) == 2) {
        $oletus = 'X';
      }
      else {
        $oletus = '';
      }

      $hylly = array(
        "hyllyalue" => $hyllyalue,
        "hyllynro"   => $hyllynro,
        "hyllyvali" => $hyllyvali,
        "hyllytaso" => $hyllytaso
      );

      // Tarkistetaan onko syötetty hyllypaikka jo tälle tuotteelle
      $tuotteen_oma_hyllypaikka = "SELECT * FROM tuotepaikat
                                   WHERE tuoteno = '$row[tuoteno]'
                                   AND yhtio     = '{$kukarow['yhtio']}'
                                   AND hyllyalue = '$hyllyalue'
                                   AND hyllynro  = '$hyllynro'
                                   AND hyllyvali = '$hyllyvali'
                                   AND hyllytaso = '$hyllytaso'";
      $oma_paikka = pupe_query($tuotteen_oma_hyllypaikka);

      // Jos syötettyä paikkaa ei ole tämän tuotteen, lisätään uusi tuotepaikka
      if (mysqli_num_rows($oma_paikka) == 0) {

        if (isset($tullaan) and $tullaan == 'tuotteen_hyllypaikan_muutos') {
          $_viesti = 'Hyllysiirroissa';
        }
        else {
          $_viesti = 'Saapumisessa';
        }

        lisaa_tuotepaikka($row['tuoteno'], $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso, $_viesti, "", $halytysraja, $tilausmaara);
      }
      else {
        // Nollataan poistettava kenttä varmuuden vuoksi
        $query = "UPDATE tuotepaikat SET
                  poistettava   = ''
                  WHERE tuoteno = '{$row['tuoteno']}'
                  AND yhtio     = '{$kukarow['yhtio']}'
                  AND hyllyalue = '$hyllyalue'
                  AND hyllynro  = '$hyllynro'
                  AND hyllyvali = '$hyllyvali'
                  AND hyllytaso = '$hyllytaso'";
        pupe_query($query);
      }

      // Päivitetään oletuspaikat jos tehdään tästä oletuspaikka
      if ($oletus == 'X') {
        // Asetetaan oletuspaikka uusiksi
        paivita_oletuspaikka($row['tuoteno'], $hylly, true);

        if ($poista_vanha_tuotepaikka == 'on') {

          // Lukitaan taulut saldojen siirtoa varten
          $query = "LOCK TABLE
                    tuotepaikat WRITE";
          $result = pupe_query($query);

          $query = "UPDATE tuotepaikat SET
                    muuttaja      = '{$kukarow['kuka']}',
                    muutospvm     = now(),
                    poistettava   = 'D'
                    WHERE tuoteno = '{$row['tuoteno']}'
                    AND yhtio     = '{$kukarow['yhtio']}'
                    AND hyllyalue = '{$row['hyllyalue']}'
                    AND hyllynro  = '{$row['hyllynro']}'
                    AND hyllyvali = '{$row['hyllyvali']}'
                    AND hyllytaso = '{$row['hyllytaso']}'";
          $result = pupe_query($query);

          // Unlock tables
          $query = "UNLOCK TABLES";
          $result = pupe_query($query);

        }

        // Siirretään saldot jos on siirrettävää
        if ($siirra_saldot == 'on' and isset($saldo) and $saldo['myytavissa'] > 0 and $tuote['sarjanumeroseuranta'] == '') {

          // Lukitaan taulut saldojen siirtoa varten
          $query = "LOCK TABLE
                    tuotepaikat WRITE,
                    tapahtuma WRITE,
                    tuote READ,
                    tilausrivi WRITE";
          $result = pupe_query($query);

          // Tarkistetaan löytyykö tuotepaikka MISTÄ saldot siirretään
          $query = "SELECT *
                    FROM tuotepaikat
                    WHERE yhtio='{$kukarow['yhtio']}'
                    AND tuoteno='{$row['tuoteno']}'
                    AND hyllyalue = '{$row['hyllyalue']}'
                    AND hyllynro  = '{$row['hyllynro']}'
                    AND hyllyvali = '{$row['hyllyvali']}'
                    AND hyllytaso = '{$row['hyllytaso']}'";
          $result = pupe_query($query);

          if (mysqli_num_rows($result) == 0) {
            echo "<font class='error'>". t("Tuotepaikkaa josta siirretään ei löydy") . "</font><br>";
            exit();
          }

          // Tarkistetaan löytyykö tuotepaikka MIHIN saldot siirretään
          $query = "SELECT *
                    FROM tuotepaikat
                    WHERE yhtio='{$kukarow['yhtio']}'
                    AND tuoteno='{$row['tuoteno']}'
                    AND hyllyalue = '{$hyllyalue}'
                    AND hyllynro  = '{$hyllynro}'
                    AND hyllyvali = '{$hyllyvali}'
                    AND hyllytaso = '{$hyllytaso}'";
          $result = pupe_query($query);

          if (mysqli_num_rows($result) == 0) {
            echo "<font class='error'>". t("Tuotepaikkaa johon siirretään ei löydy") . "</font><br>";
            exit();
          }

          // Siirretään saldot vanhasta oletuspaikasta uuteen oletuspaikkaan
          // Poistetaan VANHALTA tuotepaikalta siirrettävä määrä
          // Saldojen siirroissa vanha tuotepaikka merkataan aina poistettavaksi
          $query = "UPDATE tuotepaikat SET
                    saldo         = saldo - {$saldo['myytavissa']},
                    saldoaika     = now(),
                    muuttaja      = '{$kukarow['kuka']}',
                    muutospvm     = now(),
                    poistettava   = 'D'
                    WHERE tuoteno = '{$row['tuoteno']}'
                    AND yhtio     = '{$kukarow['yhtio']}'
                    AND hyllyalue = '{$row['hyllyalue']}'
                    AND hyllynro  = '{$row['hyllynro']}'
                    AND hyllyvali = '{$row['hyllyvali']}'
                    AND hyllytaso = '{$row['hyllytaso']}'";
          $result = pupe_query($query);

          // Lisätään UUTEEN tuotepaikkaan siirrettävä määrä
          $query = "UPDATE tuotepaikat SET
                    saldo         = saldo + {$saldo['myytavissa']},
                    saldoaika     = now(),
                    muuttaja      = '{$kukarow['kuka']}',
                    muutospvm     = now(),
                    poistettava   = ''
                    WHERE tuoteno = '{$row['tuoteno']}'
                    AND yhtio     = '{$kukarow['yhtio']}'
                    AND hyllyalue = '{$hyllyalue}'
                    AND hyllynro  = '{$hyllynro}'
                    AND hyllyvali = '{$hyllyvali}'
                    AND hyllytaso = '{$hyllytaso}'";
          $result = pupe_query($query);

          $mista = "{$row['hyllyalue']} {$row['hyllynro']} {$row['hyllyvali']} {$row['hyllytaso']}";
          $minne = "$hyllyalue $hyllynro $hyllyvali $hyllytaso";

          // Keskihankintahinta
          $kehahin_query = "SELECT tuote.sarjanumeroseuranta,
                            round(if (tuote.epakurantti100pvm = '0000-00-00',
                                if (tuote.epakurantti75pvm = '0000-00-00',
                                  if (tuote.epakurantti50pvm = '0000-00-00',
                                    if (tuote.epakurantti25pvm = '0000-00-00',
                                      tuote.kehahin,
                                    tuote.kehahin * 0.75),
                                  tuote.kehahin * 0.5),
                                tuote.kehahin * 0.25),
                              0),
                            6) kehahin
                            FROM tuote
                            WHERE yhtio = '{$kukarow['yhtio']}'
                            and tuoteno = '{$row['tuoteno']}'";
          $kehahin_result = pupe_query($kehahin_query);
          $kehahin_row = mysqli_fetch_array($kehahin_result);
          $keskihankintahinta = $kehahin_row['kehahin'];

          // Tapahtumat
          // insert into tapahtumat "vähennettiin"
          $tapahtuma_query = "INSERT INTO tapahtuma SET
                              yhtio      = '{$kukarow['yhtio']}',
                              tuoteno    = '{$row['tuoteno']}',
                              kpl        = {$saldo['myytavissa']} * -1,
                              hinta      = '$keskihankintahinta',
                              laji       = 'siirto',
                              hyllyalue  = '{$row['hyllyalue']}',
                              hyllynro   = '{$row['hyllynro']}',
                              hyllyvali  = '{$row['hyllyvali']}',
                              hyllytaso  = '{$row['hyllytaso']}',
                              rivitunnus = '0',
                              selite     = '".t("Paikasta")." $mista ".t("vähennettiin")." {$saldo['myytavissa']}',
                              laatija    = '$kukarow[kuka]',
                              laadittu   = now()";
          $result = pupe_query($tapahtuma_query);

          // insert into tapahtumat "lisättiin"
          $tapahtuma_query = "INSERT INTO tapahtuma SET
                              yhtio      = '{$kukarow['yhtio']}',
                              tuoteno    = '{$row['tuoteno']}',
                              kpl        = {$saldo['myytavissa']},
                              hinta      = '$keskihankintahinta',
                              laji       = 'siirto',
                              hyllyalue  = '$hyllyalue',
                              hyllynro   = '$hyllynro',
                              hyllyvali  = '$hyllyvali',
                              hyllytaso  = '$hyllytaso',
                              rivitunnus = '0',
                              selite     = '".t("Paikalle")." $minne ".t("lisättiin")." {$saldo['myytavissa']}',
                              laatija    = '$kukarow[kuka]',
                              laadittu   = now()";
          $result = pupe_query($tapahtuma_query);

          // Päivitetään vanhan tuotepaikan avoimet tulouttamattomat ostot uudelle paikalle
          $ostot_query = "UPDATE tilausrivi SET
                          hyllyalue     = '$hyllyalue',
                          hyllynro      = '$hyllynro',
                          hyllyvali     = '$hyllyvali',
                          hyllytaso     = '$hyllytaso'
                          WHERE yhtio   = '{$kukarow['yhtio']}'
                          AND tyyppi    = 'O'
                          AND varattu   > 0
                          AND tuoteno   = '{$row['tuoteno']}'
                          AND hyllyalue = '{$row['hyllyalue']}'
                          AND hyllynro  = '{$row['hyllynro']}'
                          AND hyllyvali = '{$row['hyllyvali']}'
                          AND hyllytaso = '{$row['hyllytaso']}'";
          $result = pupe_query($ostot_query);

          // Unlock tables
          $query = "UNLOCK TABLES";
          $result = pupe_query($query);
        }
      }

      if (!isset($tullaan) or $tullaan != 'tuotteen_hyllypaikan_muutos') {
        // Asetetaan tuotepaikka tilausriville
        $affected_rows = paivita_tilausrivin_hylly($tilausrivi, $hylly);
      }

      // Palataan edelliselle sivulle
      if (isset($hyllytys)) {
        echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=hyllytys.php?{$url}'>"; exit();
      }
      elseif (isset($tullaan) and $tullaan == 'tuotteen_hyllypaikan_muutos') {
        $minne_hyllypaikka = trim("{$hyllyalue} {$hyllynro} {$hyllyvali} {$hyllytaso}");
        echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=tuotteen_hyllypaikan_muutos.php?minne_hyllypaikka={$minne_hyllypaikka}&{$url}'>"; exit();
      }
      else {
        echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=vahvista_kerayspaikka.php?{$url}'>"; exit;
      }
    }

    break;
  }

}

$oletuspaikka_chk = "";

if (!isset($oletuspaikka) or count($oletuspaikka) == 2) $oletuspaikka_chk = "checked";

if (!isset($tullaan) or $tullaan != 'tuotteen_hyllypaikan_muutos') {

  $siirra_saldot_chk = "";
  if (!isset($siirra_saldot) or $siirra_saldot == 'on') $siirra_saldot_chk = "checked";

  $onko_suoratoimitus_res = onko_suoratoimitus($tilausrivi);

  if ($row_suoratoimitus = mysqli_fetch_assoc($onko_suoratoimitus_res)) {
    if ($row_suoratoimitus["suoraan_laskutukseen"] == "") $oletuspaikka_chk = '';
  }
}
elseif (isset($tullaan) and $tullaan == 'tuotteen_hyllypaikan_muutos') {
  $poista_vanha_tuotepaikka_chk = "";
  if (!isset($poista_vanha_tuotepaikka) or is_array($poista_vanha_tuotepaikka) or $poista_vanha_tuotepaikka == 'on') $poista_vanha_tuotepaikka_chk = "checked";
}

$paluu_url = "vahvista_kerayspaikka.php?{$url}";
if (isset($hyllytys)) {
  $paluu_url = "hyllytys.php?{$url}";
}
elseif (isset($tullaan) and $tullaan == 'tuotteen_hyllypaikan_muutos') {
  $paluu_url = "tuotteen_hyllypaikan_muutos.php?{$url}";
}

// View
echo "<div class='header'>";
echo "<button onclick='window.location.href=\"$paluu_url\"' class='button left'><img src='back2.png'></button>";
echo "<h1>", t("UUSI KERÄYSPAIKKA"), "</h1></div>";

// Virheet
if (isset($errors)) {
  echo "<span class='error'>";
  foreach ($errors as $virhe) {
    echo "{$virhe}<br>";
  }
  echo "</span>";
}

echo "<div class='main'>
<form name='uusipaikkaformi' method='post' action=''>
  <table>
    <tr>
      <th>", t("Tuote"), "</th>
      <td colspan='3'>{$row['tuoteno']}</td>
    </tr>";

if (!isset($tullaan) or $tullaan != 'tuotteen_hyllypaikan_muutos') {
  echo "  <tr>
        <th>", t("Toim. Tuotekoodi"), "</th>
        <td colspan='3'>{$row['toim_tuoteno']}</td>
      </tr>";
}

echo "  <tr>
      <th>", t("Keräyspaikka"), "</th>
      <td colspan='3'>{$row['hyllyalue']} {$row['hyllynro']} {$row['hyllyvali']} {$row['hyllytaso']}</td>
    </tr>
    <tr>
      <th>", t("Uusi tuotepaikka"), "</td>
      <td><input type='text' name='tuotepaikka' value='{$tuotepaikka}' /></td>
    </tr>
    <tr>
      <th>", t("Hälytysraja"), "</td>
      <td><input type='text' name='halytysraja' value='' /></th>
    </tr>
    <tr>
      <th>", t("Tilausmäärä"), "</td>
      <td><input type='text' name='tilausmaara' value='' /></th>
    </tr>";

/*
Jos yhtiöllä ei ole ns. oletuspaikka-käsitettä (varastoja on paljon ja ei ole "päävarastoa", joten ei tiedetä missä on oletuspaikka),
niin ei anneta siirtää saldoja tai vaihtaa oletuspaikkaa
*/
$toimipaikat_res = hae_yhtion_toimipaikat($kukarow['yhtio']);

if (mysqli_num_rows($toimipaikat_res) == 0 or (mysqli_num_rows($toimipaikat_res) != 0 and $kukarow['toimipaikka'] == 0)) {

  echo "  <tr>
        <td colspan='2'>", t("Tee tästä oletuspaikka"), "
        <input type='hidden' name='oletuspaikka[]' value='default' />
        <input type='checkbox' id='oletuspaikka' name='oletuspaikka[]' {$oletuspaikka_chk} /></td>
      </tr>";

  if (!isset($tullaan) or $tullaan != 'tuotteen_hyllypaikan_muutos') {
    echo "  <tr>
          <td colspan='2'>", t("Siirrä saldo"), " ({$saldo['myytavissa']})
          <input type='hidden' name='siirra_saldot[]' value='default' />
          <input type='checkbox' id='siirra_saldot' name='siirra_saldot[]' {$siirra_saldot_chk}/>
          </td>
        </tr>";
  }
  elseif (isset($tullaan) and $tullaan == 'tuotteen_hyllypaikan_muutos') {
    echo "  <tr>
          <td colspan='2'>", t("Poista vanha tuotepaikka"), "
          <input type='hidden' name='poista_vanha_tuotepaikka[]' value='default' />
          <input type='checkbox' id='poista_vanha_tuotepaikka' name='poista_vanha_tuotepaikka[]' {$poista_vanha_tuotepaikka_chk}/>
          </td>
        </tr>";
  }
}

echo "</table>";

if (!isset($tullaan) or $tullaan != 'tuotteen_hyllypaikan_muutos') {
  echo "<input type='hidden' name='alusta_tunnus' value='{$alusta_tunnus}' />";
  echo "<input type='hidden' name='liitostunnus' value='{$liitostunnus}' />";
  echo "<input type='hidden' name='tilausrivi' value='{$tilausrivi}' />";
}
elseif (isset($tullaan) and $tullaan == 'tuotteen_hyllypaikan_muutos') {
  echo "<input type='hidden' name='tuotepaikan_tunnus' value='{$tuotepaikan_tunnus}' />";
  echo "<input type='hidden' name='tullaan' value='{$tullaan}' />";
  echo "<input type='hidden' name='mista_koodi' value='{$mista_koodi}' />";
  echo "<input type='hidden' name='minne_koodi' value='{$minne_koodi}' />";
}

echo "</div>";

echo "<div class='controls'>
  <button name='submit' class='button' value='submit' onclick='submit();'>", t("Perusta"), "</button>
  </form>
</div>";

echo "
<script type='text/javascript'>

$(document).ready(function() {
  $('#oletuspaikka').on('change', function() {
    if ($('#oletuspaikka').is(':checked')) {
      if ($('#poista_vanha_tuotepaikka')) {
        // enabloidaan poista vanha tuotepaikka checkbox
        $('#poista_vanha_tuotepaikka').removeAttr('disabled');
      }
      else {
        // enabloidaan siirra saldot checkbox
        $('#siirra_saldot').removeAttr('disabled');
      }
    }
    else {
      if ($('#poista_vanha_tuotepaikka')) {
        // Tyhjennetään ja disabloidaan poista vanha tuotepaikka checkbox
        $('#poista_vanha_tuotepaikka').attr('disabled', 'disabled');
        $('#poista_vanha_tuotepaikka').removeAttr('checked');
      }
      else {
        // Tyhjennetään ja disabloidaan siirra saldot checkbox
        $('#siirra_saldot').attr('disabled', 'disabled');
        $('#siirra_saldot').removeAttr('checked');
      }
    }
  });
});

</script>";


require 'inc/footer.inc';
