<?php

// DataTables päälle
$pupe_DataTables = "maksalaskuja";

require "inc/parametrit.inc";

js_popup();

if (!isset($kaikki))    $kaikki = "";
if (!isset($summa))      $summa = "";
if (!isset($tapa))      $tapa = "";
if (!isset($tili))      $tili = "";
if (!isset($tunnus))    $tunnus = "";
if (!isset($poikkeus))    $poikkeus = "";
if (!isset($kaale))      $kaale = "";
if (!isset($nimihaku))    $nimihaku = "";
if (!isset($eipankkiin))  $eipankkiin = "";
if (!isset($valuu))      $valuu = "";
if (!isset($erapvm))    $erapvm = "";
if (!isset($tee))      $tee = "";

echo "<font class='head'>".t("Laskujen maksatus")."</font><hr>";

$oletusmaksupaiva_kasittely = "if(kapvm >= curdate() and kapvm < erpcm, kapvm, if(erpcm >= curdate(), erpcm, curdate()))";
// Näytetäänkö kustannuspaikka maksatuksessa
$kpquery = "SELECT selite
          FROM avainsana
          WHERE yhtio  = '$kukarow[yhtio]'
          AND laji     = 'MAKSUKP'";
$avainsana_result = pupe_query($kpquery);
$avainsana_row = mysqli_fetch_assoc($avainsana_result);

if (mysqli_num_rows($avainsana_result) == 0 or $avainsana_row["selite"] == 'Ei') {
  $kpmaksatuksessa = false;
}
else {
  $kpmaksatuksessa = true;
}

// Katsotaan montako päivää 'yli' halutaan automaattisesti käyttää kassa-alea
if ($yhtiorow["ostoreskontra_kassaalekasittely"] > 0) {
  $oletusmaksupaiva_kasittely = "if(kapvm >= curdate() and kapvm < erpcm, kapvm, ";

  for ($i = 1; $i <= $yhtiorow["ostoreskontra_kassaalekasittely"]; $i++) {
    $oletusmaksupaiva_kasittely .= "if(adddate(kapvm,$i) >= curdate() and adddate(kapvm,$i) < erpcm, adddate(kapvm,$i),";
  }

  $oletusmaksupaiva_kasittely .= " if(erpcm >= curdate(), erpcm, curdate())";

  for ($i = 1; $i <= $yhtiorow["ostoreskontra_kassaalekasittely"]; $i++) {
    $oletusmaksupaiva_kasittely .= ")";
  }

  $oletusmaksupaiva_kasittely .= ")";
}

if (count($_POST) == 0) {
  // Tarkistetaan laskujen oletusmaksupvm, eli poistetaan vanhentuneet kassa-alet. tehdään tämä aina kun aloitetaan maksatus
  $query = "UPDATE lasku use index (yhtio_tila_mapvm)
            SET olmapvm = $oletusmaksupaiva_kasittely
            WHERE yhtio = '$kukarow[yhtio]'
            and tila    in ('H', 'M')
            and mapvm   = '0000-00-00'";
  $result = pupe_query($query);
}

// MUOKKAUS: Lisatty laskujen merkitseminen maksetuksi myos ilman pankkiyhteytta:
if (isset($_GET["maksettu"])) {
  $maksettu_tunnus = (int)$_GET["maksettu"];

  if ($maksettu_tunnus > 0) {
    $query = "UPDATE lasku SET mapvm=NOW() WHERE tunnus=$maksettu_tunnus;";
    pupe_query($query);
  }
}

// Päivitetään oletustili
if ($tee == 'O') {
  $query = "UPDATE kuka SET
            oletustili  = '$oltili'
            WHERE yhtio = '$kukarow[yhtio]'
            AND kuka    = '$kukarow[kuka]'";
  $result = pupe_query($query);
  $tee = "V"; // näytetään käyttäjälle valikko
}

// Etsitään aluksi yrityksen oletustili
$query = "SELECT kuka.oletustili, yriti.bic
          FROM kuka
          JOIN yriti ON (yriti.yhtio = kuka.yhtio and yriti.tunnus = kuka.oletustili and yriti.kaytossa = '')
          WHERE kuka.yhtio = '$kukarow[yhtio]' and
          kuka.kuka        = '$kukarow[kuka]'";
$result = pupe_query($query);
$oltilrow = mysqli_fetch_assoc($result);

if (mysqli_num_rows($result) == 0 or mb_strlen($oltilrow["oletustili"]) == 0) {
  echo "<br/><font class='error'>".t("Maksutiliä ei ole valittu")."!</font><br><br>";
  $tee = 'W';
}

// Poistamme käyttyjän oletustilin
if ($tee == 'X') {
  $query = "UPDATE kuka set
            oletustili = ''
            WHERE kuka ='$kukarow[kuka]' and yhtio = '$kukarow[yhtio]'";
  $result = pupe_query($query);
  $tee = 'W';
}

// Poimitaan lasku ja halutaan muuttaa oletusmaksupäivää, päivitetään se kantaan
if ($tee == 'H' and ($poikkeus == 'on' or ($kaale == "" and $poikkeus == ""))) {

  // jos ollaan ruksattu maksa heti, niin tallennetaan maksupäiväksi tämä päivä
  $oletusmaksupaiva = "curdate()";

  // jos ei olla ruksattu maksa heti eikä haluta käyttää kassa-alea, niin tallennetaan maksupäiväksi eräpäivä
  if ($kaale == "" and $poikkeus == "") {
    $oletusmaksupaiva = "erpcm";
  }

  // tehdään päivitys vain maksuvalmiille laskuille, joiden eräpäivä on tulevaisuudessa
  $query = "UPDATE lasku set
            olmapvm     = $oletusmaksupaiva
            WHERE yhtio = '$kukarow[yhtio]'
            AND tunnus  = '$tunnus'
            AND tila    = 'M'
            AND erpcm   > curdate() ";
  $result = pupe_query($query);
}

// Jos ei saada poimia kun yhden pankin maksuja aineistoon
if ($tee == 'H' and ($yhtiorow['pankkitiedostot'] == 'F' or $yhtiorow['pankkitiedostot'] == 'E')) {
  $query = "SELECT lasku.tunnus
            FROM lasku
            WHERE lasku.yhtio     = '{$kukarow["yhtio"]}'
            and lasku.maksu_tili != '{$oltilrow["oletustili"]}'
            and lasku.tila        = 'P'
            and lasku.maksaja     = '{$kukarow["kuka"]}'";
  $result = pupe_query($query);

  if (mysqli_num_rows($result) != 0) {
    echo "<font class='error'>".t("Sinulla on maksuja poimittuna jo toisesta pankista. Siirrä maksuaineisto pankkiin ennenkuin lisäät toisen pankin maksuja aineistoon").".</font><br><br>";
    $tee = "V";
  }
}

// Lasku merkitään maksettavaksi ja vähennetään limiittiä tai tehdään vain tarkistukset päittäinvientiin.
if ($tee == 'H' or $tee == 'G') {

  $tili = $oltilrow["oletustili"];

  // maksetaan kassa-alennuksella
  if ($kaale == 'on') {
    $maksettava = "(lasku.summa - lasku.kasumma)";
    $alatila = ", alatila = 'K'";
  }
  else {
    $maksettava = "lasku.summa";
    $alatila = '';
  }

  $query = "SELECT valuu.kurssi, round($maksettava * valuu.kurssi,2) summa,
            $maksettava summa_valuutassa,
            maksuaika, olmapvm, maa, kapvm, erpcm,
            ultilno, swift, pankki1, pankki2, pankki3, pankki4, sisviesti1, valkoodi
            FROM lasku
            JOIN valuu ON (valuu.yhtio = lasku.yhtio and valuu.nimi = lasku.valkoodi)
            WHERE lasku.yhtio = '$kukarow[yhtio]'
            and lasku.tunnus  = '$tunnus'";
  $result = pupe_query($query);

  if (mysqli_num_rows($result) != 1) {
    echo "<b>".t("Haulla ei löytynyt yhtä laskua")."</b>";
    require "inc/footer.inc";
    exit;
  }

  $trow = mysqli_fetch_assoc($result);

  // Tukitaan mahdollinen aikaikkuna
  if ($trow["maksuaika"] == "0000-00-00 00:00:00") {
    $trow["maksuaika"] = "";
  }

  // Se oli jo maksettu
  if (mb_strlen($trow["maksuaika"]) > 0) {
    echo "<font class='error'>".t("Laskun ehti jo joku muu maksaa! Ohitetaan")."! $trow[maksuaika]</font><br>";
    require "inc/footer.inc";
    exit;
  }

  // virhetilanne, että kapvm on suurempi kuin ercpm!
  if ($trow['kapvm'] > $trow['erpcm']) {
    $trow['kapvm'] = $trow['erpcm'];
  }

  // Hyvityslasku --> vastaava määrä rahaa on oltava veloituspuolella
  if ($trow['summa'] < 0 and $eipankkiin == '') {

    // Huomaa samat kyselyt (wheret) tee == G haarassa...
    if (mb_strtoupper($trow['maa']) == 'FI') {
      $query = "SELECT sum(if(summa < 0, 1, 0)) maara, sum(if(alatila = 'K' and summa > 0, summa - kasumma, summa)) summa
                FROM lasku
                WHERE yhtio    = '$kukarow[yhtio]'
                and tila       = 'P'
                and olmapvm    = '$trow[olmapvm]'
                and maksaja    = '$kukarow[kuka]'
                and maksu_tili = '$tili'
                and maa        = 'FI'
                and ultilno    = '$trow[ultilno]'
                and swift      = '$trow[swift]'";
    }
    else {
      $query = "SELECT sum(if(summa < 0, 1, 0)) maara, sum(if(alatila='K' and summa > 0, summa - kasumma, summa)) summa
                FROM lasku
                WHERE yhtio     = '$kukarow[yhtio]'
                and tila        = 'P'
                and olmapvm     = '$trow[olmapvm]'
                and maksaja     = '$kukarow[kuka]'
                and maksu_tili  = '$tili'
                and maa        != 'FI'
                and valkoodi    = '$trow[valkoodi]'
                and ultilno     = '$trow[ultilno]'
                and swift       = '$trow[swift]'
                and pankki1     = '$trow[pankki1]'
                and pankki2     = '$trow[pankki2]'
                and pankki3     = '$trow[pankki3]'
                and pankki4     = '$trow[pankki4]'
                and sisviesti1  = '$trow[sisviesti1]'";
    }

    $result = pupe_query($query);

    if (mysqli_num_rows($result) != 1) {
      echo "<b>" . t("Hyvityshaulla ei löytynyt mitään") . "</b>$query";
      require "inc/footer.inc";
      exit;
    }

    $veloitusrow = mysqli_fetch_assoc($result);
    $hyvityslaskuja = $veloitusrow['maara'];

    if ($hyvityslaskuja >= 9 and $oltilrow['bic'] == 'NDEAFIHH') {
      echo "<font class='error'>".t("Poimittu aineisto voi sisältää vain 9 hyvityslaskua yhdelle toimittajalle")."</font><br><br>";
      $tee = 'S';
    }
    elseif (abs($veloitusrow['summa'] + $trow['summa_valuutassa']) < 0.01) {

      // Ei ole valittu mitä tehdään
      if (!isset($valinta) or $valinta == '') {
        echo "<font class='message'>" . t("Hyvityslasku ja veloituslasku(t) näyttävät menevän päittäin") . "<br>" . t("Haluatko, että ne suoritetaan heti ja jätetään lähettämättä pankkiin") . "?</font><br><br>";
        echo "<form action = 'maksa.php' method='post'>
        <input type='hidden' name = 'tee' value='G'>
        <input type='hidden' name = 'valuu' value='$valuu'>
        <input type='hidden' name = 'erapvm' value='$erapvm'>
        <input type='hidden' name = 'kaikki' value='$kaikki'>
        <input type='hidden' name = 'tapa' value='$tapa'>
        <input type='hidden' name = 'tunnus' value='$tunnus'>
        <input type='hidden' name = 'kaale' value='$kaale'>
        <input type='hidden' name = 'poikkeus' value='$poikkeus'>
        <input type='radio'  name = 'valinta' value='K' checked> " . t("Kyllä") . "
        <input type='radio'  name = 'valinta' value='E'> " . t("Ei") . "
        <input type='submit' name = 'valitse' value='" . t("Valitse") . "'>";

        require "inc/footer.inc";
        exit;
      }

      if (isset($valinta) and $valinta == 'E') {
        echo "<font class='error'>" . t("Valitut veloitukset ja hyvitykset menevät tasan päittäin (summa 0,-). Pankkiin ei kuitenkaan voi lähettää nolla-summaisia maksuja. Jos haluat lähettää nämä päittäin menevät veloitukset ja hyvitykset pankkiin, pitää sinun valita lisää veloituksia. Yhteissumman pitää olla suurempi kuin 0.") . "</font><br><br>";
        $tee = 'S';
      }
    }
    elseif ($veloitusrow['summa'] + $trow['summa'] < 0.01) {
      echo "<font class='error'>" . t("Hyvityslaskua vastaavaa määrää veloituksia ei ole valittuna.") . "<br>" . t("Valitse samalle asiakkaalle lisää veloituksia, jos haluat valita tämän hyvityslaskun maksatukseen") . "</font><br><br>";
      $tee = 'S';
    }
  }
}

// Suoritetaan päittäin
if ($tee == 'G') {

  $query = "UPDATE lasku set
            maksaja      = '$kukarow[kuka]',
            maksuaika    = now(),
            mapvm        = now(),
            maksu_kurssi = '$trow[kurssi]',
            maksu_tili   = '$tili',
            tila         = 'P',
            olmapvm      = '$trow[olmapvm]'
            WHERE tunnus = '$tunnus'
            and yhtio    = '$kukarow[yhtio]'";
  $xresult = pupe_query($query);

  if (mb_strtoupper($trow['maa']) == 'FI') {
    $query = "SELECT ytunnus, nimi, postitp,
              summa   'maksusumma',
              kasumma 'maksukasumma',
              summa   'maksusumma_valuutassa',
              kasumma 'maksukasumma_valuutassa',
              round(summa * vienti_kurssi, 2)   'vietysumma',
              round(kasumma * vienti_kurssi, 2) 'vietykasumma',
              summa   'vietysumma_valuutassa',
              kasumma 'kasumma_valuutassa',
              concat(summa, ' ', valkoodi) 'summa',
              ebid, tunnus, alatila, vienti_kurssi, tapvm, valkoodi
              FROM lasku
              WHERE yhtio    = '$kukarow[yhtio]'
              and tila       = 'P'
              and olmapvm    = '$trow[olmapvm]'
              and maksaja    = '$kukarow[kuka]'
              and maksu_tili = '$tili'
              and maa        = 'FI'
              and ultilno    = '$trow[ultilno]'
              and swift      = '$trow[swift]'
              ORDER BY tapvm desc";
  }
  else {
    $query = "SELECT ytunnus, nimi, postitp,
              summa   'maksusumma',
              kasumma 'maksukasumma',
              summa   'maksusumma_valuutassa',
              kasumma 'maksukasumma_valuutassa',
              round(summa * vienti_kurssi, 2)   'vietysumma',
              round(kasumma * vienti_kurssi, 2) 'vietykasumma',
              summa   'vietysumma_valuutassa',
              kasumma 'kasumma_valuutassa',
              concat(summa, ' ', valkoodi) 'summa',
              ebid, tunnus, alatila, vienti_kurssi, tapvm, valkoodi
              FROM lasku
              WHERE yhtio     = '$kukarow[yhtio]'
              and tila        = 'P'
              and olmapvm     = '$trow[olmapvm]'
              and maksaja     = '$kukarow[kuka]'
              and maksu_tili  = '$tili'
              and maa        != 'FI'
              and valkoodi    = '$trow[valkoodi]'
              and ultilno     = '$trow[ultilno]'
              and swift       = '$trow[swift]'
              and pankki1     = '$trow[pankki1]'
              and pankki2     = '$trow[pankki2]'
              and pankki3     = '$trow[pankki3]'
              and pankki4     = '$trow[pankki4]'
              and sisviesti1  = '$trow[sisviesti1]'
              ORDER BY tapvm desc";
  }

  $result = pupe_query($query);

  if (mysqli_num_rows($result) < 2) {
    echo "<font class='error'>".t("Laskuja katosi")."</font><br>";
    require "inc/footer.inc";
    exit;
  }

  // Otetaan max tapvm ja valuuttajutut talteen
  $max_tapvm_laskurow = mysqli_fetch_assoc($result);

  if ($max_tapvm_laskurow['valkoodi'] != $yhtiorow["valkoodi"]) {
    $kurssi = 0;

    // Haetaan kurssi
    $query = "SELECT kurssi
              FROM valuu_historia
              WHERE kotivaluutta = '$yhtiorow[valkoodi]'
              and valuutta       = '$max_tapvm_laskurow[valkoodi]'
              AND kurssipvm      >= '$max_tapvm_laskurow[tapvm]'
              AND kurssi         > 0
              ORDER BY kurssipvm
              LIMIT 1";
    $valuures = pupe_query($query);

    if (mysqli_num_rows($valuures) == 1) {
      $valuurow = mysqli_fetch_assoc($valuures);
      $kurssi = $valuurow["kurssi"];
    }

    // Haetaan kurssi
    $query = "SELECT kurssi
              FROM valuu
              WHERE yhtio = '$kukarow[yhtio]'
              AND nimi    = '$max_tapvm_laskurow[valkoodi]'
              AND kurssi  > 0";
    $valuures = pupe_query($query);

    if (mysqli_num_rows($valuures) == 1) {
      $valuurow = mysqli_fetch_assoc($valuures);
      $kurssi = $valuurow["kurssi"];
    }

    if ($kurssi == 0) {
      echo "<font class='error'>$query".t("Ei löydetty sopivaa kurssia!")."</font><br>";
      require "inc/footer.inc";
      exit;
    }
  }
  else {
    $kurssi = 1;
  }

  mysqli_data_seek($result, 0);

  while ($laskurow = mysqli_fetch_assoc($result)) {

    $laskurow["maksusumma"]   = round($laskurow["maksusumma"] * $kurssi, 2);
    $laskurow["maksukasumma"] = round($laskurow["maksukasumma"] * $kurssi, 2);

    // Kassa-ale
    if ($laskurow['alatila'] != 'K') $laskurow['maksukasumma'] = 0;

    // Luodaan tiliöinnit
    $selite = "Suoritettu päittäin $laskurow[nimi]";

    $query = "SELECT tilino, kustp, kohde, projekti
              FROM tiliointi
              WHERE ltunnus = '$laskurow[tunnus]'
              and yhtio     = '$kukarow[yhtio]'
              and tapvm     = '$laskurow[tapvm]'
              and tilino    in ('$yhtiorow[ostovelat]', '$yhtiorow[konserniostovelat]')
              and korjattu  = ''";
    $ostvresult = pupe_query($query);

    if (mysqli_num_rows($ostvresult) == 1) {
      $ostovelkarow = mysqli_fetch_assoc($ostvresult);

      // Ostovelat
      $query = "INSERT into tiliointi set
                yhtio            = '$kukarow[yhtio]',
                ltunnus          = '$laskurow[tunnus]',
                tilino           = '$ostovelkarow[tilino]',
                kustp            = '$ostovelkarow[kustp]',
                kohde            = '$ostovelkarow[kohde]',
                projekti         = '$ostovelkarow[projekti]',
                tapvm            = '$trow[olmapvm]',
                summa            = '$laskurow[vietysumma]',
                summa_valuutassa = '$laskurow[vietysumma_valuutassa]',
                vero             = 0,
                selite           = '$selite',
                lukko            = '',
                laatija          = '$kukarow[kuka]',
                laadittu         = now()";
      $xresult = pupe_query($query);

      list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yhtiorow["selvittelytili"]);

      // Rahatili = selvittely
      $query = "INSERT into tiliointi set
                yhtio            = '$kukarow[yhtio]',
                ltunnus          = '$laskurow[tunnus]',
                tilino           = '$yhtiorow[selvittelytili]',
                kustp            = '{$kustp_ins}',
                kohde            = '{$kohde_ins}',
                projekti         = '{$projekti_ins}',
                tapvm            = '$trow[olmapvm]',
                summa            = -1 * ($laskurow[maksusumma] - $laskurow[maksukasumma]),
                summa_valuutassa = -1 * ($laskurow[maksusumma_valuutassa] - $laskurow[maksukasumma_valuutassa]),
                vero             = 0,
                selite           = '$selite',
                lukko            = '',
                laatija          = '$kukarow[kuka]',
                laadittu         = now()";
      $xresult = pupe_query($query);

      if ($laskurow['maksukasumma'] != 0) {
        // Kassa-alessa on huomioitava alv, joka voi olla useita vientejä
        $totkasumma = 0;

        // Etsitään kulutiliöinnit
        $query = "SELECT tiliointi.*
                  FROM tiliointi
                  JOIN tili ON (tiliointi.yhtio = tili.yhtio and tiliointi.tilino = tili.tilino)
                  LEFT JOIN taso ON (tili.yhtio = taso.yhtio and tili.ulkoinen_taso = taso.taso and taso.tyyppi = 'U')
                  WHERE tiliointi.ltunnus = '$laskurow[tunnus]'
                  AND tiliointi.yhtio     = '$kukarow[yhtio]'
                  AND tiliointi.tapvm     = '$laskurow[tapvm]'
                  AND tiliointi.tilino    not in ('$yhtiorow[ostovelat]', '$yhtiorow[alv]', '$yhtiorow[konserniostovelat]', '$yhtiorow[matkalla_olevat]', '$yhtiorow[varasto]', '$yhtiorow[varastonmuutos]', '$yhtiorow[raaka_ainevarasto]', '$yhtiorow[raaka_ainevarastonmuutos]', '$yhtiorow[varastonmuutos_inventointi]', '$yhtiorow[varastonmuutos_epakurantti]')
                  AND tiliointi.korjattu  = ''
                  AND (taso.kayttotarkoitus is null or taso.kayttotarkoitus  in ('','O'))";
        $yresult = pupe_query($query);

        while ($tiliointirow = mysqli_fetch_assoc($yresult)) {
          // Kuinka paljon on tämän viennin osuus
          $summa = round($tiliointirow['summa'] * (1+$tiliointirow['vero']/100) / $laskurow['vietysumma'] * $laskurow['maksukasumma'], 2);
          $summa_valuutassa = round($tiliointirow['summa_valuutassa'] * (1+$tiliointirow['vero']/100) / $laskurow['vietysumma_valuutassa'] * $laskurow['maksukasumma_valuutassa'], 2);

          if ($tiliointirow['vero'] != 0) { // Netotetaan alvi
            $alv = round($summa - $summa / (1 + ($tiliointirow['vero'] / 100)), 2);
            $alv_valuutassa = round($summa_valuutassa - $summa_valuutassa / (1 + ($tiliointirow['vero'] / 100)), 2);

            $summa -= $alv;
            $summa_valuutassa -= $alv_valuutassa;
          }

          $totkasumma += $summa + $alv;
          $totkasumma_valuutassa += $summa_valuutassa + $alv_valuutassa;

          // Kassa-ale
          $query = "INSERT into tiliointi set
                    yhtio            = '$kukarow[yhtio]',
                    ltunnus          = '$laskurow[tunnus]',
                    tilino           = '$yhtiorow[kassaale]',
                    kustp            = '$tiliointirow[kustp]',
                    kohde            = '$tiliointirow[kohde]',
                    projekti         = '$tiliointirow[projekti]',
                    tapvm            = '$trow[olmapvm]',
                    summa            = $summa * -1,
                    summa_valuutassa = $summa_valuutassa * -1,
                    vero             = '$tiliointirow[vero]',
                    selite           = '$selite',
                    lukko            = '',
                    laatija          = '$kukarow[kuka]',
                    laadittu         = now()";

          // MUOKKAUS: mysqli_insert_id():
          $xresult = pupe_query($query, $GLOBALS["link"]);
          $isa = mysqli_insert_id($GLOBALS["link"]); // Näin löydämme tähän liittyvät alvit....

          if ($tiliointirow['vero'] != 0) {
            // Kassa-alen alv
            $query = "INSERT into tiliointi set
                      yhtio            = '$kukarow[yhtio]',
                      ltunnus          = '$laskurow[tunnus]',
                      tilino           = '$yhtiorow[alv]',
                      kustp            = 0,
                      kohde            = 0,
                      projekti         = 0,
                      tapvm            = '$trow[olmapvm]',
                      summa            = $alv * -1,
                      summa_valuutassa = $alv_valuutassa * -1,
                      vero             = 0,
                      selite           = '$selite',
                      lukko            = '1',
                      laatija          = '$kukarow[kuka]',
                      laadittu         = now(),
                      aputunnus        = $isa";
            $xresult = pupe_query($query);
          }
        }

        // Hoidetaan mahdolliset pyöristykset
        $heitto = round($totkasumma - $laskurow["maksukasumma"], 2);
        $heitto_valuutassa = round($totkasumma_valuutassa - $laskurow["maksukasumma_valuutassa"], 2);

        if (abs($heitto) >= 0.01) {
          $query = "UPDATE tiliointi SET
                    summa            = summa + $heitto,
                    summa_valuutassa = summa_valuutassa + $heitto_valuutassa
                    WHERE tunnus     = '$isa'
                    and yhtio        = '$kukarow[yhtio]'";
          $xresult = pupe_query($query);
        }
      }

      if ($laskurow['valkoodi'] != $yhtiorow["valkoodi"]) {

        $vesumma = round($laskurow["maksusumma"] - $laskurow["maksukasumma"] - $laskurow['vietysumma'], 2);

        if (abs(round($vesumma, 2)) > 0) {

          $totvesumma = 0;

          // Etsitään kulutiliöinnit
          $query = "SELECT tiliointi.*
                    FROM tiliointi
                    JOIN tili ON (tiliointi.yhtio = tili.yhtio and tiliointi.tilino = tili.tilino)
                    LEFT JOIN taso ON (tili.yhtio = taso.yhtio and tili.ulkoinen_taso = taso.taso and taso.tyyppi = 'U')
                    WHERE tiliointi.ltunnus = '$laskurow[tunnus]'
                    AND tiliointi.yhtio     = '$kukarow[yhtio]'
                    AND tiliointi.tapvm     = '$laskurow[tapvm]'
                    AND tiliointi.tilino    not in ('$yhtiorow[kassaale]', '$yhtiorow[ostovelat]', '$yhtiorow[alv]', '$yhtiorow[konserniostovelat]', '$yhtiorow[matkalla_olevat]', '$yhtiorow[varasto]', '$yhtiorow[varastonmuutos]', '$yhtiorow[raaka_ainevarasto]', '$yhtiorow[raaka_ainevarastonmuutos]', '$yhtiorow[varastonmuutos_inventointi]', '$yhtiorow[varastonmuutos_epakurantti]')
                    AND tiliointi.korjattu  = ''
                    AND (taso.kayttotarkoitus is null or taso.kayttotarkoitus  in ('','O'))";
          $yresult = pupe_query($query);

          while ($tiliointirow = mysqli_fetch_assoc($yresult)) {
            // Kuinka paljon on tämän viennin osuus
            $summa = round($tiliointirow['summa'] * (1+$tiliointirow['vero']/100) / $laskurow['vietysumma'] * $vesumma, 2);

            if (round($summa, 2) != 0) {
              // Valuuttaero
              $query = "INSERT into tiliointi set
                        yhtio    = '$kukarow[yhtio]',
                        ltunnus  = '$laskurow[tunnus]',
                        tilino   = '$yhtiorow[valuuttaero]',
                        kustp    = '$tiliointirow[kustp]',
                        kohde    = '$tiliointirow[kohde]',
                        projekti = '$tiliointirow[projekti]',
                        tapvm    = '$trow[olmapvm]',
                        summa    = $summa,
                        vero     = 0,
                        selite   = '$selite',
                        lukko    = '',
                        laatija  = '$kukarow[kuka]',
                        laadittu = now()";
		    
	      // MUOKKAUS: mysqli_insert_id():
              $xresult = pupe_query($query, $GLOBALS["link"]);
              $isa = mysqli_insert_id($GLOBALS["link"]);

              $totvesumma += $summa;
            }
          }

          // Hoidetaan mahdolliset pyöristykset
          if ($totvesumma != $vesumma) {
            echo "<font class='message'>".t("Joudun pyöristämään valuuttaeroa")."</font><br>";

            $query = "UPDATE tiliointi
                      SET summa = summa - $totvesumma + $vesumma
                      WHERE tunnus = '$isa' and yhtio='$kukarow[yhtio]'";
            $xresult = pupe_query($query);
          }
        }
      }

      $query = "UPDATE lasku set
                maksaja      = '$kukarow[kuka]',
                maksuaika    = now(),
                mapvm        = now(),
                maksu_kurssi = '$kurssi',
                maksu_tili   = '$tili',
                tila         = 'Y',
                olmapvm      = '$trow[olmapvm]'
                WHERE tunnus = '$laskurow[tunnus]'
                and yhtio    = '$kukarow[yhtio]'";
      $xresult = pupe_query($query);

    }
    else {
      echo "<font class='error'>".t("VIRHE: Ostovelkatilin määrittely epäonnistui")."!<br>".t("Summaa ei kirjattu")."!</font><br>";
    }
  }

  echo t("Laskut merkitty suoritetuksi!")."<br><br>";
  $tee = 'S';
}

// Poimitaan lasku
if ($tee == 'H') {

  if ($eipankkiin == 'on') {
    $tila   = 'Q';
    $poppari = ", maksaja = 'Ohitettu', popvm = '".date("Y-m-d")." 12:00:00' ";
  }
  else {
    $tila   = 'P';
    $poppari = ", maksaja = '$kukarow[kuka]' ";
  }

  $query = "UPDATE lasku set
            maksuaika    = now(),
            maksu_kurssi = '$trow[kurssi]',
            maksu_tili   = '$tili',
            tila         = '$tila'
            $poppari
            $alatila
            WHERE yhtio  = '$kukarow[yhtio]'
            AND tunnus   = '$tunnus'
            AND tila     = 'M'";
  $result = pupe_query($query);

  // Jotain meni pieleen
  if (mysqli_affected_rows($link) != 1) {
    echo "System error Debug --> $query<br>";
    require "inc/footer.inc";
    exit;
  }

  $query = "UPDATE yriti set
            maksulimitti = maksulimitti - $trow[summa]
            WHERE tunnus = '$oltilrow[oletustili]'
            and yhtio    = '$kukarow[yhtio]'";
  $result = pupe_query($query);
  $tee = 'S';
}

// Perutaan maksuun meno
if ($tee == 'DP') {
  $query = "SELECT *, if(alatila='K' and summa > 0, summa - kasumma, summa) usumma
            FROM lasku
            WHERE yhtio = '$kukarow[yhtio]'
            AND tunnus  = '$lasku'";
  $result = pupe_query($query);

  if (mysqli_num_rows($result) != 1) {
    echo "<font class='error'>".t('Lasku katosi tai se ehdittiin juuri siirtää pankkiin')."</font><br>";
  }
  else {
    $trow = mysqli_fetch_assoc($result);

    // Hyvityslasku --> vastaava määrä rahaa on oltava veloituspuolella
    if ($trow['usumma'] > 0) {
      if (mb_strtoupper($trow['maa']) == 'FI') {
        $query = "SELECT sum(if(alatila='K' and summa > 0, summa - kasumma, summa)) summa
                  FROM lasku
                  WHERE yhtio     = '$kukarow[yhtio]'
                  and tila        = 'P'
                  and olmapvm     = '$trow[olmapvm]'
                  and maksu_tili  = '$trow[maksu_tili]'
                  and maa         = 'fi'
                  and ultilno     = '$trow[ultilno]'
                  and swift       = '$trow[swift]'
                  and maksaja     = '$kukarow[kuka]'
                  and tunnus     != '$lasku'";
      }
      else {
        $query = "SELECT sum(if(alatila='K' and summa > 0, summa - kasumma, summa)) summa
                  FROM lasku
                  WHERE yhtio     = '$kukarow[yhtio]'
                  and tila        = 'P'
                  and olmapvm     = '$trow[olmapvm]'
                  and maksu_tili  = '$trow[maksu_tili]'
                  and maa         <> 'fi'
                  and valkoodi    = '$trow[valkoodi]'
                  and ultilno     = '$trow[ultilno]'
                  and swift       = '$trow[swift]'
                  and pankki1     = '$trow[pankki1]'
                  and pankki2     = '$trow[pankki2]'
                  and pankki3     = '$trow[pankki3]'
                  and pankki4     = '$trow[pankki4]'
                  and sisviesti1  = '$trow[sisviesti1]'
                  and maksaja     = '$kukarow[kuka]'
                  and tunnus     != '$lasku'";
      }

      $result = pupe_query($query);
      if (mysqli_num_rows($result) != 1) {
        echo "<b>".t("Hyvityshaulla ei löytynyt mitään")."</b>$query";
        require "inc/footer.inc";
        exit;
      }

      $veloitusrow=mysqli_fetch_assoc($result);

      if ($veloitusrow['summa'] < 0) {
        echo "<font class='error'>".t("Jos poistat tämän laskun maksatuksesta, on asiakkaalle valittu liikaa hyvityksiä.")." ($veloitusrow[summa])</font><br><br>";
        $tee = 'DM';
      }
    }

    if ($tee == 'DP') {
      $query = "UPDATE lasku set
                maksaja      = '',
                maksuaika    = '0000-00-00',
                maksu_kurssi = '0',
                maksu_tili   = '',
                tila         = 'M',
                alatila      = '',
                olmapvm      = $oletusmaksupaiva_kasittely
                WHERE tunnus = '$lasku'
                and yhtio    = '$kukarow[yhtio]'
                and tila     = 'P'";
      $updresult = pupe_query($query);

      if (mysqli_affected_rows($link) != 1) { // Jotain meni pieleen
        echo "System error Debug --> $query<br>";

        require "inc/footer.inc";
        exit;
      }

      $query = "UPDATE yriti set
                maksulimitti = maksulimitti + $trow[usumma]
                WHERE tunnus = '$trow[maksu_tili]'
                and yhtio    = '$kukarow[yhtio]'";
      $updresult = pupe_query($query);
      $tee = 'DM';
    }
  }
}

// Maksetaan nipussa
if ($tee == "NK" or $tee == "NT" or $tee == "NV") {

  if ($oltilrow["oletustili"] == 0) {
    echo "<br/><font class='error'>", t("Maksutili on kateissa"), "! ", t("Systeemivirhe"), "!</font><br/><br/>";
    require "inc/footer.inc";
    exit;
  }

  $lisa = "";

  if ($tee == "NT") {
    $lisa = " and lasku.olmapvm = curdate() ";
  }
  elseif ($tee == 'NK') {
    $lisa = " and lasku.olmapvm <= curdate() ";
  }
  else {

    if ($valuu != '') {
      $lisa .= " and valkoodi = '$valuu'";
    }

    if ($erapvm != '') {
      if ($kaikki == 'on') {
        $lisa .= " and olmapvm <= '$erapvm'";
      }
      else {
        $lisa .= " and olmapvm = '$erapvm'";
      }
    }

    if ($nimihaku != '') {
      $lisa .= " and lasku.nimi like '%$nimihaku%'";
    }
  }

  $query = "SELECT valuu.kurssi, round(if(date_add(kapvm, interval $yhtiorow[ostoreskontra_kassaalekasittely] day) >= curdate(), summa-kasumma, summa) * valuu.kurssi,2) summa, lasku.nimi, lasku.tunnus, lasku.liitostunnus
            FROM lasku
            JOIN valuu ON (valuu.yhtio = lasku.yhtio AND valuu.nimi = lasku.valkoodi)
            JOIN toimi ON (toimi.yhtio = lasku.yhtio AND toimi.tunnus = lasku.liitostunnus AND toimi.maksukielto = '')
            WHERE lasku.yhtio = '$kukarow[yhtio]'
            and lasku.summa   > 0
            and lasku.tila    = 'M'
            $lisa
            ORDER BY lasku.olmapvm, lasku.erpcm, lasku.summa desc";
  $result = pupe_query($query);

  while ($tiliointirow = mysqli_fetch_assoc($result)) {

    $query = "SELECT maksulimitti, tilinylitys
              FROM yriti
              WHERE yhtio  = '$kukarow[yhtio]'
              and tunnus   = '$oltilrow[oletustili]'
              and kaytossa = ''";
    $yrires = pupe_query($query);

    if (mysqli_num_rows($yrires) != 1) {
      echo "<br/><font class='error'>", t("Maksutili katosi"), "! ", t("Systeemivirhe"), "!</font><br/><br/>";

      require "inc/footer.inc";
      exit;
    }

    $mayritirow = mysqli_fetch_assoc($yrires);

    if ($mayritirow['tilinylitys'] == "" and $mayritirow['maksulimitti'] < $tiliointirow['summa']) {
      echo "<br><font class='error'>".t("Maksutilin limitti ylittyi! Laskujen maksu keskeytettiin")."</font><br><br>";
      break;
    }
    elseif ($mayritirow['tilinylitys'] != "" and $mayritirow['maksulimitti'] < $tiliointirow['summa']) {
      echo "<br><font class='error'>".t("HUOM: Maksutilin limitti ylittyi! (Tilinylitys sallittu)")."</font><br><br>";
    }

    $query = "UPDATE lasku set
              maksaja      = '$kukarow[kuka]',
              maksuaika    = now(),
              maksu_kurssi = '$tiliointirow[kurssi]',
              maksu_tili   = '$oltilrow[oletustili]',
              tila         = 'P',
              alatila      = if(date_add(kapvm, interval $yhtiorow[ostoreskontra_kassaalekasittely] day) >= curdate(), 'K', '')
              WHERE tunnus = '$tiliointirow[tunnus]'
              and yhtio    = '$kukarow[yhtio]'
              and tila     = 'M'";
    $updresult = pupe_query($query);

    if (mysqli_affected_rows($link) != 1) { // Jotain meni pieleen
      echo "System error Debug --> $query<br>";

      require "inc/footer.inc";
      exit;
    }

    $query = "UPDATE yriti set
              maksulimitti = maksulimitti - $tiliointirow[summa]
              WHERE tunnus = '$oltilrow[oletustili]'
              and yhtio    = '$kukarow[yhtio]'";
    $updresult = pupe_query($query);
  }

  $tee = 'DM';
}

// Poistetaan lasku
if ($tee == 'D' and $oikeurow['paivitys'] == '1') {

  $query = "SELECT *
            FROM lasku
            WHERE yhtio = '$kukarow[yhtio]'
            and tunnus  = '$tunnus'
            and h1time  = '0000-00-00 00:00:00'
            and h2time  = '0000-00-00 00:00:00'
            and h3time  = '0000-00-00 00:00:00'
            and h4time  = '0000-00-00 00:00:00'
            and h5time  = '0000-00-00 00:00:00'";
  $result = pupe_query($query);

  if (mysqli_num_rows($result) != 1) {
    echo t('lasku kateissa') . "$tunnus</font>";
    require "inc/footer.inc";
    exit;
  }

  $trow = mysqli_fetch_assoc($result);

  $komm = "(" . $kukarow['nimi'] . "@" . date('Y-m-d') .") ".t("Poisti laskun")."<br>" . $trow['comments'];

  // Ylikirjoitetaan tiliöinnit
  $query = "UPDATE tiliointi SET
            korjattu           = '$kukarow[kuka]',
            korjausaika        = now()
            WHERE ltunnus      = '$tunnus' and
            yhtio              = '$kukarow[yhtio]' and
            tiliointi.korjattu = ''";
  $result = pupe_query($query);

  // Merkataan lasku poistetuksi
  $query = "UPDATE lasku SET
            alatila      = 'H',
            tila         = 'D',
            comments     = '$komm'
            WHERE tunnus = '$tunnus' and
            yhtio        = '$kukarow[yhtio]'";
  $result = pupe_query($query);

  echo "<font class='error'>".sprintf(t('Poistit %s:n laskun tunnuksella %d.'), $trow['nimi'], $tunnus)."</font><br><br>";

  $tunnus = '';
  $tee   = 'S';
}

// Ei oletustiliä, joten annetaan käyttäjän valita
if ($tee == 'W') {

  echo "<font class='message'>".t("Valitse maksutili")."</font><hr>";

  $query = "SELECT tunnus, concat(nimi, ' (', tilino, ')') tili, maksulimitti, tilinylitys, valkoodi
            FROM yriti
            WHERE yhtio   = '$kukarow[yhtio]'
            and (maksulimitti > 0 or tilinylitys != '')
            and factoring = ''
            and kaytossa  = ''";
  $result = pupe_query($query);

  if (mysqli_num_rows($result) == 0) {
    echo "<font class='head'>".t("Sinulla ei ole yhtään tiliä, jolla olisi limiittiä")."!<p>".t("Käy päivittämässä limiitit yrityksen pankkitileille")."!</font><hr>";

    require "inc/footer.inc";
    exit;
  }

  echo "<form action='maksa.php' method='post'>";
  echo "<input type='hidden' name='tee' value='O'>";
  echo "<table>";

  echo "<tr>";
  echo "<th>".t("Tili")."</th>";
  echo "<th colspan='3'>".t("Maksulimitti")."</th>";
  echo "</tr>";

  while ($yritirow = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>$yritirow[tili]</td>";

    echo "<td align='right'>$yritirow[maksulimitti]";
    if ($yritirow["tilinylitys"] != "") echo " ".t("Tilinylitys sallittu")."</font>";
    echo "</td>";

    echo "<td>$yritirow[valkoodi]</td>";
    echo "<td><input type='radio' name='oltili' value='$yritirow[tunnus]'></td>";
    echo "</tr>";
  }

  echo "</table>";
  echo "<br>";
  echo "<input type='submit' value='".t("Valitse maksutili")."'>";
  echo "</form>";

  $tee = "";
}
else {
  // eli näytetään tili jolta maksetaan ja sen saldo

  $query = "SELECT yriti.tunnus, yriti.nimi, yriti.maksulimitti, yriti.tilinylitys, yriti.valkoodi, round(yriti.maksulimitti * valuu.kurssi, 2) maksulimitti_koti
            FROM yriti
            JOIN valuu ON (valuu.yhtio = yriti.yhtio and valuu.nimi = yriti.valkoodi)
            WHERE yriti.yhtio   = '$kukarow[yhtio]'
            and yriti.tunnus    = '$oltilrow[oletustili]'
            and yriti.factoring = ''
            and yriti.kaytossa  = ''";
  $result = pupe_query($query);
  $yritirow = mysqli_fetch_assoc($result);

  if (mysqli_num_rows($result) != 1) {
    echo t("Etsin tiliä")." '$oltilrow[oletustili]', ".t("mutta sitä ei löytynyt")."!";
    require "inc/footer.inc";
    exit;
  }

  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Tili")."</th>";
  echo "<th>".t("Maksulimitti")."</th>";
  echo "<th></th>";
  echo "<th>".t("Kaikki erääntyneet")."</th>";
  echo "<th>".t("Tänään erääntyvät")."</th>";
  echo "</tr>";

  $maksulimiittikoti = "";
  if ($yritirow["maksulimitti_koti"] != 0 and $yritirow["maksulimitti_koti"] != $yritirow["maksulimitti"]) {
    $maksulimiittikoti = "<br>$yritirow[maksulimitti_koti] $yhtiorow[valkoodi]";
  }

  echo "<tr>";
  echo "<td class='ptop'>$yritirow[nimi]</td>";

  $fontlisa1 = "";
  $fontlisa2 = "";

  if ($yritirow["maksulimitti"] <= 0) {
    $fontlisa1 = "<font class='error'>";
    $fontlisa2 = "</font>";
  }

  echo "<td class='ptop' align='right'>$fontlisa1$yritirow[maksulimitti] $yritirow[valkoodi]$maksulimiittikoti";
  if ($yritirow["tilinylitys"] != "") echo " (".t("Tilinylitys sallittu").")";
  echo "$fontlisa2</td>";
  echo "<td class='ptop'>";
  echo "<form action = 'maksa.php' method='post'>";
  echo "<input type='hidden' name = 'tee' value='X'>";
  echo "<input type='submit' value='".t("Vaihda maksutiliä")."'>";
  echo "</form>";
  echo "</td>";

  // Lisätään tähän vielä mahdollisuus maksaa kaikki erääntyneet laskut tai tänään erääntyvät
  $query = "SELECT ifnull(sum(round(if(date_add(kapvm, interval $yhtiorow[ostoreskontra_kassaalekasittely] day) >= curdate(), summa-kasumma, summa) * kurssi, 2)), 0) kaikkien_eraantyneiden_laskujen_summa,
            ifnull(sum(round(if(olmapvm = curdate(), if(date_add(kapvm, interval $yhtiorow[ostoreskontra_kassaalekasittely] day) >= curdate(), summa-kasumma, summa), 0) * kurssi, 2)), 0) tanaan_eraantyvien_laskujen_summa,
            ifnull(count(*), 0) laskuja_yhteensa_kpl,
            ifnull(sum(if(olmapvm = curdate(), 1, 0)), 0) tanaan_eraantyvia_laskuja_yhteensa_kpl
            FROM lasku
            JOIN valuu ON (valuu.yhtio = lasku.yhtio and valuu.nimi = lasku.valkoodi)
            WHERE lasku.yhtio = '$yhtiorow[yhtio]'
            and summa         > 0
            and tila          = 'M'
            and olmapvm       <= curdate()";
  $result = pupe_query($query);
  $sumrow = mysqli_fetch_assoc($result);

  echo "<td class='ptop' align='right'>$sumrow[kaikkien_eraantyneiden_laskujen_summa] $yhtiorow[valkoodi] ($sumrow[laskuja_yhteensa_kpl])";

  if ($sumrow['kaikkien_eraantyneiden_laskujen_summa'] > 0 and ($yritirow["tilinylitys"] != "" or $yritirow['maksulimitti'] >= $sumrow["kaikkien_eraantyneiden_laskujen_summa"])) {
    echo "<form action = 'maksa.php' method='post'>
      <input type='hidden' name = 'tee' value='NK'>
      <input type='hidden' name = 'tili' value='$oltilrow[oletustili]'>
      <input type='submit' value='".t('Poimi kaikki erääntyneet')."'>
      </form>";
  }

  echo "</td>";
  echo "<td class='ptop' align='right'>$sumrow[tanaan_eraantyvien_laskujen_summa] $yhtiorow[valkoodi] ($sumrow[tanaan_eraantyvia_laskuja_yhteensa_kpl])";

  if ($sumrow['tanaan_eraantyvien_laskujen_summa'] > 0 and ($yritirow["tilinylitys"] != "" or $yritirow['maksulimitti'] >= $sumrow["tanaan_eraantyvien_laskujen_summa"])) {
    echo "<form action = 'maksa.php' method='post'>
      <input type='hidden' name = 'tee' value='NT'>
      <input type='hidden' name = 'tili' value='$oltilrow[oletustili]'>
      <input type='submit' value='".t('Poimi kaikki tänään erääntyvät')."'>
      </form>";
  }
  echo "</td>";
  echo "</tr></table>";

  if ($tee == '') {
    $tee = "V";
  }
}

// Näytetään kaikki omat maksatukseen valitut
if ($tee == 'DM') {
  $query = "SELECT lasku.nimi, lasku.kapvm, lasku.erpcm, lasku.valkoodi,
            lasku.summa - lasku.kasumma kasumma,
            lasku.summa,
            round((lasku.summa - lasku.kasumma) * valuu.kurssi,2) ykasumma,
            round(lasku.summa * valuu.kurssi,2) ysumma,
            lasku.ebid, lasku.tunnus, lasku.olmapvm,
            lasku.ultilno,
            lasku.swift,
            if(alatila = 'K' and summa > 0, summa - kasumma, summa) maksettava_summa,
            if(alatila = 'K' and summa > 0, round(lasku.summa * valuu.kurssi,2) - kasumma, round(lasku.summa * valuu.kurssi,2)) maksettava_ysumma,
            h1time,
            h2time,
            h3time,
            h4time,
            h5time,
            if(alatila='k','*','') kale,
            lasku.tunnus peru,
            yriti.tilino,
            yriti.nimi tilinimi,
            lasku.liitostunnus, lasku.ytunnus, lasku.ovttunnus, lasku.viite, lasku.viesti, lasku.vanhatunnus, lasku.arvo, if(lasku.laskunro = 0, '', lasku.laskunro) laskunro
            FROM lasku, valuu, yriti
            WHERE lasku.yhtio    = '$kukarow[yhtio]'
            and valuu.yhtio      = lasku.yhtio
            and valuu.yhtio      = yriti.yhtio
            and yriti.kaytossa   = ''
            and lasku.maksu_tili = yriti.tunnus
            and lasku.tila       = 'P'
            and lasku.valkoodi   = valuu.nimi
            and lasku.maksaja    = '$kukarow[kuka]'
            ORDER BY lasku.olmapvm, lasku.erpcm, ykasumma desc";
  $result = pupe_query($query);

  echo "<br><font class='message'>".t("Maksuaineistoon poimitut laskut")."</font><hr>";

  if (mysqli_num_rows($result) == 0) {
    echo "<font class='error'>".t("Ei yhtään poimittua laskua")."!</font><br>";
  }
  else {

    pupe_DataTables(array(array($pupe_DataTables, 9, 10)));

    // Näytetään valitut laskut
    echo "<table class='display dataTable' id='$pupe_DataTables'>";

    echo "<thead>
        <tr>
        <th class='ptop'>".t("Nimi")."</th>
        <th class='ptop'>".t("Tilinumero")."</th>
        <th class='ptop'>".t("Eräpvm")." / ".t("Maksupvm")."</th>
        <th class='ptop' nowrap>".t("Kassa-ale")."</th>
        <th class='ptop'>".t("Summa")."</th>
        <th class='ptop'>".t("Laskunro")."</th>
        <th class='ptop'>".t("Maksutili")."</th>
        <th class='ptop'>".t("Viite")." / ".t("Viesti")."</th>
        <th class='ptop'>".t("Ebid")."</th>
        <th style='display:none;'></th>
        </tr>
        <tr>
        <td><input type='text' class='search_field' name='search_nimi'></td>
        <td><input type='text' class='search_field' name='search_tilinumero'></td>
        <td><input type='text' class='search_field' name='search_erpcm'></td>
        <td><input type='text' class='search_field' name='search_kassaale'></td>
        <td><input type='text' class='search_field' name='search_summa'></td>
        <td><input type='text' class='search_field' name='search_laskunro'></td>
        <td><input type='text' class='search_field' name='search_maksutili'></td>
        <td><input type='text' class='search_field' name='search_viite'></td>
        <td></td>
        <td class='back'></td>
        </tr>
      </thead>";

    echo "<tbody>";

    $summa = 0;
    $valsumma = array();

    while ($trow=mysqli_fetch_assoc($result)) {
      echo "<tr class='aktiivi'>";

      $query = "SELECT count(*) maara,
                group_concat(concat(lasku.summa, ' ', lasku.valkoodi) separator '<br>') summa
                FROM lasku use index (yhtio_tila_summa)
                WHERE yhtio = '$kukarow[yhtio]'
                and tila    = 'M'
                and summa   < 0
                and ultilno = '$trow[ultilno]'
                and swift   = '$trow[swift]'
                AND ytunnus = '{$trow["ytunnus"]}'";
      $hyvitysresult = pupe_query($query);
      $hyvitysrow = mysqli_fetch_assoc($hyvitysresult);

      echo "<td class='ptop'>";
      echo "<a name='$trow[tunnus]' href='muutosite.php?tee=E&tunnus=$trow[tunnus]&lopetus=$PHP_SELF////tee=DM//valuu=$valuu//erapvm=$erapvm//nimihaku=$nimihaku///$tunnus'>$trow[nimi]</a>";

      // jos toimittajalle on maksuvalmiita hyvityksiä, niin tehdään alertti!
      if ($hyvitysrow["maara"] > 0 and $trow["summa"] > 0) {
        echo "<div id='div_$trow[tunnus]' class='popup'>";
        printf(t("Toimittajalle on %s maksuvalmista hyvitystä!"), $hyvitysrow["maara"]);
        echo "<br>";
        echo "$hyvitysrow[summa]";
        echo "</div>";
        echo " <a class='tooltip' id='$trow[tunnus]'><img src='$palvelin2/pics/lullacons/info.png'></a>";
      }

      echo "</td>";

      echo "<td class='ptop'>$trow[ultilno]<br>$trow[swift]</td>";

      echo "<td class='ptop'>".pupe_DataTablesEchoSort($trow['erpcm']).tv1dateconv($trow['erpcm'])."<br>".tv1dateconv($trow['olmapvm'])."</td>";

      if ($trow['kapvm'] != '0000-00-00') {
        echo "<td class='ptop' align='right' nowrap>".pupe_DataTablesEchoSort($trow['kapvm']);

        if ($trow["kale"] != "") {
          echo t("Käytetään")."<br>";
        }

        echo tv1dateconv($trow["kapvm"])."<br>";
        echo "$trow[ykasumma] $yhtiorow[valkoodi]<br>";

        if (mb_strtoupper($trow["valkoodi"]) != mb_strtoupper($yhtiorow["valkoodi"])) {
          echo "$trow[summa] $trow[valkoodi]";
        }

        echo "</td>";
      }
      else {
        echo "<td class='ptop' align='right' nowrap></td>";
      }

      echo "<td class='ptop' align='right' nowrap>$trow[ysumma] $yhtiorow[valkoodi]<br>";

      $summa += $trow["maksettava_ysumma"];

      if (!isset($valsumma[$trow["valkoodi"]])) $valsumma[$trow["valkoodi"]] = $trow["maksettava_summa"];
      else $valsumma[$trow["valkoodi"]] += $trow["maksettava_summa"];

      if (mb_strtoupper($trow["valkoodi"]) != mb_strtoupper($yhtiorow["valkoodi"])) {
        echo "$trow[summa] $trow[valkoodi]";
      }

      echo "</td>";
      echo "<td class='ptop'>$trow[laskunro]</td>";
      echo "<td class='ptop'>$trow[tilinimi]<br>$trow[tilino]</td>";
      echo "<td class='ptop'>$trow[viite] $trow[viesti]";

      if ($trow["vanhatunnus"] != 0) {
        $query = "SELECT summa, valkoodi
                  from lasku
                  where yhtio     = '$kukarow[yhtio]'
                  and tila        in ('H','Y','M','P','Q')
                  and vanhatunnus = '$trow[vanhatunnus]'";
        $jaetutres = pupe_query($query);

        echo "<div id='div_$trow[tunnus]' class='popup'>";
        printf(t("Lasku on jaettu %s osaan!"), mysqli_num_rows($jaetutres));
        echo "<br>".t("Alkuperäinen summa")." $trow[arvo] $trow[valkoodi]<br>";
        $osa = 1;
        while ($jaetutrow = mysqli_fetch_assoc($jaetutres)) {
          echo "$osa: $jaetutrow[summa] $jaetutrow[valkoodi]<br>";
          $osa++;
        }
        echo "</div>";
        echo " <a class='tooltip' id='$trow[tunnus]'><img src='$palvelin2/pics/lullacons/alert.png'></a>";
      }

      echo "</td>";

      // tehdään lasku linkki
      echo "<td nowrap class='ptop'>";
      $lasku_urlit = ebid($trow['tunnus'], true);

      if (count($lasku_urlit) == 0) {
        echo t("Paperilasku");
      }

      if (is_array($lasku_urlit)) {
        foreach ($lasku_urlit as $lasku_url) {
          echo "<a href='$lasku_url' target='Attachment'>", t("Näytä liite"), "</a><br>";
        }
      }
      else {
        echo $lasku_urlit;
      }

      echo "</td>";

      echo "<td class='back ptop'>
          <form action = 'maksa.php' method='post'>
          <input type='hidden' name = 'tee' value='DP'>
          <input type='hidden' name = 'lasku' value='$trow[peru]'>
          <input type='submit' value='".t('Poista aineistosta')."'>
          </form></td>";

      echo "</tr>";
    }

    echo "</tbody>";

    echo "</table>";

    echo "<br><font class='message'>".t("Poimitut laskut yhteensä")."</font><hr>";

    echo "<table>";

    foreach ($valsumma as $val => $sum) {
      echo "<tr><th colspan='3'>$val ".t("laskut")." ".t("yhteensä")."</th><td class='ptop' align='right'>".sprintf('%.2f', $sum)." $val</td></tr>";
    }

    echo "<tr><th colspan='3'>".t("Kaikki")." ".t("laskut")." ".t("yhteensä")."</th><td class='ptop' align='right'>".sprintf('%.2f', $summa)." $yhtiorow[valkoodi]</td></tr>";

    echo "</table>";
  }

  $tee='V';
}

// Näytetään maksuvalmiit laskut
if ($tee == 'S' or $tee == 'V') { // MUOKKAUS: naytetaan oletuksena maksuvalmiit laskut, lisatty "$tee == 'S' or"

  $lisa = "";
  $kplisa = "";

  if ($valuu != '') {
    $lisa .= " and valkoodi = '$valuu'";
  }

  if ($erapvm != '') {
    if ($kaikki == 'on') {
      $lisa .= " and olmapvm <= '$erapvm'";
    }
    else {
      $lisa .= " and olmapvm = '$erapvm'";
    }
  }

  if ($nimihaku != '') {
    $lisa .= " and lasku.nimi like '%$nimihaku%'";
  }
  
  if ($kpmaksatuksessa == true) {
    $kplisa = ", (select kustannuspaikka.nimi from tiliointi 
left join kustannuspaikka on tiliointi.yhtio = kustannuspaikka.yhtio and kustannuspaikka.tyyppi = 'K' and kustannuspaikka.tunnus = tiliointi.kustp
where lasku.yhtio=tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus and tiliointi.kustp != 0 and tiliointi.korjattu = '' limit 1) kustnimi ";
  }  

  $query = "SELECT lasku.nimi, lasku.kapvm, lasku.erpcm, lasku.valkoodi,
            lasku.summa - lasku.kasumma kasumma,
            lasku.summa,
            round((lasku.summa - lasku.kasumma) * valuu.kurssi,2) ykasumma,
            round(lasku.summa * valuu.kurssi,2) ysumma,
            lasku.ebid, lasku.tunnus, lasku.olmapvm,
            lasku.ultilno,
            lasku.swift,
            h1time,
            h2time,
            h3time,
            h4time,
            h5time,
            lasku.liitostunnus, lasku.ytunnus, lasku.ovttunnus, lasku.viesti, lasku.comments, lasku.viite, lasku.vanhatunnus, lasku.arvo, lasku.maa, if(lasku.laskunro = 0, '', lasku.laskunro) laskunro
            $kplisa
            FROM lasku use index (yhtio_tila_mapvm)
            JOIN valuu ON lasku.yhtio=valuu.yhtio and lasku.valkoodi = valuu.nimi
            WHERE lasku.yhtio  = '$kukarow[yhtio]'
            and lasku.tila     = 'M'
            and lasku.valkoodi = valuu.nimi
            and lasku.mapvm    = '0000-00-00'
            $lisa
            ORDER BY lasku.olmapvm, lasku.erpcm, ykasumma desc";
  $result = pupe_query($query);

  if (mysqli_num_rows($result) == 0) {
    echo "<br><font class='error'>".t("Haulla ei löytynyt yhtään laskua")."</font><br>";
  }
  else {
    
    if ($kpmaksatuksessa == true) {
      pupe_DataTables(array(array($pupe_DataTables, 8, 12)));
    }
    else {
      pupe_DataTables(array(array($pupe_DataTables, 7, 11)));
    }
    
    // Näytetään valitut laskut
    echo "<br><font class='message'>".t("Maksuvalmiit laskut")."</font><hr>";

    echo "<table class='display dataTable' id='$pupe_DataTables'>";

    if ($kpmaksatuksessa == true) {
      echo "<thead>
          <tr>
          <th class='ptop'>".t("Nimi")."</th>
          <th class='ptop'>".t("Tilinumero")."</th>
          <th class='ptop'>".t("Eräpvm")."</th>
          <th class='ptop' nowrap>".t("Kassa-ale")."</th>
          <th class='ptop'>".t("Summa")."</th>
          <th class='ptop'>".t("Laskunro")."</th>
          <th class='ptop'>".t("Viite")." / ".t("Viesti")."</th>
          <th class='ptop'>".t("Kp")."</th>
          <th class='ptop'>".t("Ebid")."</th>
          <th class='ptop'>".t("Maksatus")."</th>
          <th class='ptop'>".t("Lisätieto")."</th>
          <th style='display:none;'></th>
          </tr>
          <tr>
          <td><input type='text' class='search_field' name='search_nimi'></td>
          <td><input type='text' class='search_field' name='search_tilinumero'></td>
          <td><input type='text' class='search_field' name='search_erpcm'></td>
          <td><input type='text' class='search_field' name='search_kassaale'></td>
          <td><input type='text' class='search_field' name='search_summa'></td>
          <td><input type='text' class='search_field' name='search_laskunro'></td>
          <td><input type='text' class='search_field' name='search_viite'></td>
          <td><input type='text' class='search_field' name='search_kustnimi'></td>
          <td></td>
          <td></td>
          <td></td>
          <td class='back'></td>
          </tr>
        </thead>";
      }
      else {
      echo "<thead>
          <tr>
          <th class='ptop'>".t("Nimi")."</th>
          <th class='ptop'>".t("Tilinumero")."</th>
          <th class='ptop'>".t("Eräpvm")."</th>
          <th class='ptop' nowrap>".t("Kassa-ale")."</th>
          <th class='ptop'>".t("Summa")."</th>
          <th class='ptop'>".t("Laskunro")."</th>
          <th class='ptop'>".t("Viite")." / ".t("Viesti")."</th>
          <th class='ptop'>".t("Ebid")."</th>
          <th class='ptop'>".t("Maksatus")."</th>
          <th class='ptop'>".t("Lisätieto")."</th>
          <th style='display:none;'></th>
          </tr>
          <tr>
          <td><input type='text' class='search_field' name='search_nimi'></td>
          <td><input type='text' class='search_field' name='search_tilinumero'></td>
          <td><input type='text' class='search_field' name='search_erpcm'></td>
          <td><input type='text' class='search_field' name='search_kassaale'></td>
          <td><input type='text' class='search_field' name='search_summa'></td>
          <td><input type='text' class='search_field' name='search_laskunro'></td>
          <td><input type='text' class='search_field' name='search_viite'></td>
          <td></td>
          <td></td>
          <td></td>
          <td class='back'></td>
          </tr>
        </thead>";
       } 
    $dataseek = 0;

    echo "<tbody>";

    while ($trow = mysqli_fetch_assoc($result)) {

      echo "<tr class='aktiivi'>";

      $query = "SELECT count(*) maara,
                group_concat(concat(lasku.summa, ' ', lasku.valkoodi) separator '<br>') summa
                FROM lasku use index (yhtio_tila_summa)
                WHERE yhtio = '$kukarow[yhtio]'
                and tila    = 'M'
                and summa   < 0
                and ultilno = '$trow[ultilno]'
                and swift   = '$trow[swift]'
                AND ytunnus = '{$trow["ytunnus"]}'";
      $hyvitysresult = pupe_query($query);
      $hyvitysrow = mysqli_fetch_assoc($hyvitysresult);

      echo "<td class='ptop'>";
      echo "<a name='$trow[tunnus]' href='muutosite.php?tee=E&tunnus=$trow[tunnus]&lopetus=$PHP_SELF////tee=S//valuu=$valuu//erapvm=$erapvm//nimihaku=$nimihaku///$tunnus'>$trow[nimi]</a>";

      // jos toimittajalle on maksuvalmiita hyvityksiä, niin tehdään alertti!
      if ($hyvitysrow["maara"] > 0 and $trow["summa"] > 0) {
        echo "<div id='div_$trow[tunnus]' class='popup'>";
        printf(t("Toimittajalle on %s maksuvalmista hyvitystä!"), $hyvitysrow["maara"]);
        echo "<br>";
        echo "$hyvitysrow[summa]";
        echo "</div>";
        echo " <a class='tooltip' id='$trow[tunnus]'><img src='$palvelin2/pics/lullacons/info.png'></a>";
      }

      echo "</td>";
      echo "<td class='ptop'>$trow[ultilno]<br>$trow[swift]</td>";

      echo "<td class='ptop'>".pupe_DataTablesEchoSort($trow['erpcm']);

      // eräpäivä punasella jos se on erääntynyt
      if ((int) str_replace("-", "", $trow['erpcm']) < (int) date("Ymd")) {
        echo "<font class='error'>".tv1dateconv($trow['erpcm'])."</font>";
      }
      else {
        echo tv1dateconv($trow['erpcm']);
      }

      echo "</td>";

      if ($trow['kapvm'] != '0000-00-00') {
        echo "<td class='ptop' align='right' nowrap>".pupe_DataTablesEchoSort($trow['kapvm']);
        echo tv1dateconv($trow['kapvm'])."<br>";
        echo "$trow[ykasumma] $yhtiorow[valkoodi]<br>";
        if (mb_strtoupper($trow["valkoodi"]) != mb_strtoupper($yhtiorow["valkoodi"])) {
          echo "$trow[summa] $trow[valkoodi]";
        }
        echo "</td>";
      }
      else {
        echo "<td class='ptop' align='right' nowrap></td>";
      }

      echo "<td class='ptop' align='right' nowrap>$trow[ysumma] $yhtiorow[valkoodi]<br>";

      $summa = (float)$summa + (float)$trow["ysumma"]; // MUOKKAUS: BUGIKORJAUS (string + string)

      if (mb_strtoupper($trow["valkoodi"]) != mb_strtoupper($yhtiorow["valkoodi"])) {
        echo "$trow[summa] $trow[valkoodi]";

        if (!isset($valsumma[$trow["valkoodi"]])) $valsumma[$trow["valkoodi"]] = $trow["summa"];
        else $valsumma[$trow["valkoodi"]] += (float)$trow["summa"];
      }
      else {
        if (!isset($valsumma[$trow["valkoodi"]])) $valsumma[$trow["valkoodi"]] = $trow["summa"];
        else $valsumma[$trow["valkoodi"]] += (float)$trow["summa"];
      }

      echo "</td>";

      echo "<td class='ptop'>$trow[laskunro]";
      
      // MUOKKAUS: Lisatty laskujen merkitseminen maksetuksi myos ilman pankkiyhteytta:
      echo "<br>";
      echo "<a href='?maksettu=" . $trow["tunnus"] . "'>";
      echo "<input type='submit' value='Merkitse maksetuksi'>";
      echo "</a></td>" . PHP_EOL;

      echo "<td class='ptop'>$trow[viite] $trow[viesti]";

      if ($trow["vanhatunnus"] != 0) {
        $query = "SELECT summa, valkoodi
                  from lasku
                  where yhtio     = '$kukarow[yhtio]'
                  and tila        in ('H','Y','M','P','Q')
                  and vanhatunnus = '$trow[vanhatunnus]'";
        $jaetutres = pupe_query($query);

        echo "<div id='div_$trow[tunnus]' class='popup'>";
        printf(t("Lasku on jaettu %s osaan!"), mysqli_num_rows($jaetutres));
        echo "<br>".t("Alkuperäinen summa")." $trow[arvo] $trow[valkoodi]<br>";

        $osa = 1;

        while ($jaetutrow = mysqli_fetch_assoc($jaetutres)) {
          echo "$osa: $jaetutrow[summa] $jaetutrow[valkoodi]<br>";
          $osa++;
        }
        echo "</div>";
        echo " <a class='tooltip' id='$trow[tunnus]'><img src='$palvelin2/pics/lullacons/alert.png'></a>";
      }

      echo "</td>";

      if ($kpmaksatuksessa == true) {
        echo "<td class='ptop'>$trow[kustnimi]</td>";
      }
      
      // tehdään lasku linkki
      echo "<td nowrap class='ptop'>";

      $lasku_urlit = ebid($trow['tunnus'], true);

      if (count($lasku_urlit) == 0) {
        echo t("Paperilasku");
      }
      elseif (is_array($lasku_urlit)) {
        foreach ($lasku_urlit as $filetype => $lasku_url) { // MUOKKAUS: lisatty filetype
          echo "<a href='$lasku_url' target='Attachment'>".t("Näytä liite")." ($filetype)</a><br>";
        }
      }

      echo "</td>";

      // Ok, mutta onko meillä varaa makssa kyseinen lasku???
      if ($yritirow["tilinylitys"] != "" or $trow["ysumma"] <= $yritirow["maksulimitti"]) {

        //Kikkaillaan jotta saadda seuraavan laskun tunnus
        if ($dataseek < mysqli_num_rows($result)-1) {
          $kikkarow = mysqli_fetch_assoc($result);
          mysqli_data_seek($result, $dataseek+1);
          $kikkalisa = "#$kikkarow[tunnus]";
        }
        else {
          $kikkalisa = "";
        }

        echo "<form action = 'maksa.php$kikkalisa' method='post'>";
        echo "<td class='ptop' nowrap>";

        $query = "SELECT maksukielto
                  FROM toimi
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND tunnus  = '$trow[liitostunnus]'";
        $maksukielto_res = pupe_query($query);
        $maksukielto_row = mysqli_fetch_assoc($maksukielto_res);

        if ($maksukielto_row['maksukielto'] == 'K') {
          echo "<font class='error'>", t("Maksukiellossa"), "</font>";
        }
        else {
          echo "  <input type='hidden' name = 'tee' value='H'>
              <input type='hidden' name = 'tunnus' value='$trow[tunnus]'>
              <input type='hidden' name = 'valuu' value='$valuu'>
              <input type='hidden' name = 'erapvm' value='$erapvm'>
              <input type='hidden' name = 'kaikki' value='$kaikki'>
              <input type='hidden' name = 'nimihaku' value='$nimihaku'>
              <input type='hidden' name = 'tapa' value='$tapa'>
              <input type='submit' value='".t("Poimi lasku")."'>";  
        }

        echo "</td>";
        echo "<td class='ptop' nowrap>";

        if ($trow["ysumma"] != $trow["ykasumma"] and $trow['ysumma'] > 0) {

          $ruksi='checked';

          if ($trow['kapvm'] < date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")-$yhtiorow["ostoreskontra_kassaalekasittely"], date("Y")))) {
            $ruksi = ''; // Ooh, maksamme myöhässä
          }

          echo "<input type='checkbox' name='kaale' $ruksi> ";
          echo t("Käytä kassa-ale");
          echo "<br>";
        }

        if ($trow['olmapvm'] >= date("Y-m-d")) {
          echo "<input type='checkbox' name='poikkeus'> ";
          echo t("Maksetaan heti");
          echo "<br>";
        }

        if ($trow['summa'] < 0) { //Hyvitykset voi hoitaa ilman pankkiinlähetystä
          echo "<input type='checkbox' name='eipankkiin'> ";
          echo t("Älä lähetä pankkiin");
          echo "<br>";
        }

        echo "</td>";
        echo "</form>";

        //Tutkitaan voidaanko lasku poistaa
        $query = "SELECT tunnus
                  from lasku use index (yhtio_vanhatunnus)
                  where yhtio     = '$kukarow[yhtio]'
                  and tila        = 'K'
                  and vanhatunnus = '$trow[tunnus]'";
        $delres2 = pupe_query($query);

        if (mysqli_num_rows($delres2) == 0 and $trow['h1time'] == '0000-00-00 00:00:00' and $trow['h2time'] == '0000-00-00 00:00:00' and $trow['h3time'] == '0000-00-00 00:00:00' and $trow['h4time'] == '0000-00-00 00:00:00' and $trow['h5time'] == '0000-00-00 00:00:00') {
          echo "  <td class='back'>
              <form action = 'maksa.php' method='post' onSubmit = 'return confirm(\"".t("Haluatko todella poistaa tämän laskun ja sen kaikki tiliöinnit? Tämä voi olla kirjanpitorikos!")."\");'>
              <input type='hidden' name = 'tee' value='D'>
              <input type='hidden' name = 'tunnus' value='$trow[tunnus]'>
              <input type='hidden' name = 'valuu' value='$valuu'>
              <input type='hidden' name = 'tapa' value='$tapa'>
              <input type='hidden' name = 'erapvm' value='$erapvm'>
              <input type='hidden' name = 'kaikki' value='$kaikki'>
              <input type='hidden' name = 'nimihaku' value='$nimihaku'>
              <input type='hidden' name = 'tapa' value='$tapa'>
              <input type='submit' value='".t("Poista lasku")."'>
              </form>
              </td>";
        }
        else {
          echo "<td class='back'></td>";
        }

      }
      else {
        // ei ollutkaan varaa!!
        echo "<td></td><td class='ptop'><font class='error'>".t("Tilin limitti ei riitä")."!</font></td><td class='back'></td>";
      }

      echo "</tr>";

      $dataseek++;
    }

    echo "</tbody>";
    echo "</table>";

    echo "<br><font class='message'>".t("Haetut yhteensä")."</font><hr>";

    echo "<table>";

    foreach ($valsumma as $val => $sum) {
      echo "<tr><th colspan='3'>$val ".t("laskut")." ".t("yhteensä")."</th><td class='ptop' align='right'>".sprintf('%.2f', $sum)." $val</td></tr>";
    }

    echo "<tr><th colspan='3'>".t("Kaikki")." ".t("laskut")." ".t("yhteensä")."</th><td class='ptop' align='right'>".sprintf('%.2f', $summa)." $yhtiorow[valkoodi]</td></tr>";

    echo "</table>";

    // jos limiitti riittää niin annetaan mahdollisuus poimia kaikki
    if ($yritirow["tilinylitys"] != "" or $yritirow['maksulimitti_koti'] >= $summa) {
      echo "<br><form method='post'>
        <input type='hidden' name = 'tili' value='$tili'>
        <input type='hidden' name = 'tee' value='NV'>
        <input type='hidden' name = 'kaikki' value='$kaikki'>
        <input type='hidden' name = 'valuu' value='$valuu'>
        <input type='hidden' name = 'erapvm' value='$erapvm'>
        <input type='hidden' name = 'nimihaku' value='$nimihaku'>
        <input type='submit' value='".t('Poimi kaikki haetut veloituslaskut')."'>
        </form><br>";
    }

  }
  $tee = "V";
}

// Tehdään hakukäyttöliittymä
if ($tee == 'V') {

  echo "<br><font class='message'>".t("Etsi maksuvalmiita laskuja")."</font><hr>";
  // Tällä ollaan, jos valitaan maksujen selailutapoja

  echo "  <form action = 'maksa.php' method='post'>
      <input type='hidden' name = 'tee' value='S'>";
  echo "  <table>";

  // Valuutoittain
  echo "<tr>";
  echo "<th>".t("Valuutta")."</th>";
  echo "<td>";

  $query = "SELECT valkoodi, count(*) maara
            FROM lasku
            WHERE yhtio = '$kukarow[yhtio]' and tila = 'M'
            GROUP BY valkoodi
            ORDER BY valkoodi";
  $result = pupe_query($query);

  echo "<select name='valuu'>";

  if (mysqli_num_rows($result) > 0) {
    $kaikaval = 0;
    while ($valuurow = mysqli_fetch_assoc($result)) {
      $kaikaval += $valuurow["maara"];
    }
  }

  echo "<option value=''>".t("Kaikki valuutat")." ($kaikaval)";

  if (mysqli_num_rows($result) > 0) {
    mysqli_data_seek($result, 0);

    while ($valuurow = mysqli_fetch_assoc($result)) {
      if ($valuurow["valkoodi"] == $valuu) {
        $sel = "SELECTED";
      }
      else {
        $sel = "";
      }

      echo "<option value='$valuurow[valkoodi]' $sel>$valuurow[valkoodi] ($valuurow[maara])";
    }
  }

  echo "</select></td>";
  echo "<td></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Eräpäivä")."</th>";

  $query = "SELECT olmapvm, count(*) maara
            FROM lasku
            WHERE yhtio = '$kukarow[yhtio]'
            AND tila    = 'M'
            GROUP BY olmapvm
            ORDER BY olmapvm";
  $result = pupe_query($query);

  echo "<td><select name='erapvm'>";

  if (mysqli_num_rows($result) > 0) {
    $kaikaval = 0;
    while ($laskurow = mysqli_fetch_assoc($result)) {
      $kaikaval += $laskurow["maara"];
    }
  }

  echo "<option value=''>".t("Kaikki eräpäivät")." ($kaikaval)";

  if (mysqli_num_rows($result) > 0) {
    mysqli_data_seek($result, 0);

    while ($laskurow = mysqli_fetch_assoc($result)) {
      if ($laskurow["olmapvm"] == $erapvm) {
        $sel = "SELECTED";
      }
      else {
        $sel = "";
      }

      echo "<option value = '$laskurow[olmapvm]' $sel>".tv1dateconv($laskurow["olmapvm"])." ($laskurow[maara])";
    }
  }

  echo "</select></td>";

  if ($kaikki != "") {
    $sel = "CHECKED";
  }
  else {
    $sel = "";
  }

  echo "<td>".t("Näytä myös vanhemmat")." <input type='Checkbox' name='kaikki' $sel></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Nimi")."</th><td><input type='text' name='nimihaku' size='15' value='$nimihaku'></td><td></td>";
  echo "<td class='back'><input type='submit' class='hae_btn' value='".t("Etsi")."'></td></tr>";
  echo "</table>";
  echo "</form>";

  echo "<br><font class='message'>".t("Poimitut laskut")."</font><hr>";

  $query = "SELECT lasku.tunnus
            FROM lasku, valuu, yriti
            WHERE lasku.yhtio    = '$kukarow[yhtio]'
            and valuu.yhtio      = lasku.yhtio
            and valuu.yhtio      = yriti.yhtio
            and lasku.maksu_tili = yriti.tunnus
            and yriti.kaytossa   = ''
            and lasku.tila       = 'P'
            and lasku.valkoodi   = valuu.nimi
            and lasku.maksaja    = '$kukarow[kuka]'";
  $result = pupe_query($query);

  echo "<table>";
  echo "  <tr><th>".t("Poimitut laskut")."</th>
      <td> ".mysqli_num_rows($result)." ".t("laskua poimittu")."</td>
      <td>
      <form action = 'maksa.php' method='post'>
      <input type='hidden' name = 'tee' value='DM'>
      <input type='submit' value='".t('Näytä jo poimitut laskut')."'>
      </form>
      </td></tr>";
  echo "</table>";

}

require "inc/footer.inc";
