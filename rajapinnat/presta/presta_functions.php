<?php

function presta_hae_asiakkaat($asiakaskasittely) {
  if ($asiakaskasittely == 'asiakkaittain') {
    $asiakkaat = presta_hae_asiakkaat_asiakkaittain();
  }
  elseif ($asiakaskasittely == 'yhteyshenkiloittain') {
    $asiakkaat = presta_hae_asiakkaat_yhteyshenkiloittain();
  }
  else {
    presta_echo("Virheellinen asiakaskäsittely.");

    $asiakkaat = array();
  }

  return $asiakkaat;
}

function presta_hae_asiakkaat_asiakkaittain() {
  global $kukarow, $yhtiorow, $ajetaanko_kaikki;

  $asiakkaat = array();

  $datetime_checkpoint = presta_export_checkpoint('PSTS_ASIAKAS');

  if ($ajetaanko_kaikki == "NO") {
    presta_echo("Haetaan asiakkaat, joita muutettu {$datetime_checkpoint} jälkeen.");

    $muutoslisa = " AND (yhteyshenkilo.muutospvm >= '{$datetime_checkpoint}'
      OR asiakas.muutospvm >= '{$datetime_checkpoint}') ";
  }
  else {
    presta_echo("Haetaan kaikki asiakkaat.");

    $muutoslisa = "";
  }

  $query = "SELECT
            asiakas.kuljetusohje,
            asiakas.nimi as asiakas_nimi,
            asiakas.nimitark as asiakas_nimitark,
            asiakas.tunnus as asiakas_tunnus,
            asiakas.ytunnus,
            avainsana.selitetark_5,
            yhteyshenkilo.email,
            yhteyshenkilo.gsm,
            yhteyshenkilo.maa,
            yhteyshenkilo.nimi,
            yhteyshenkilo.osoite,
            yhteyshenkilo.postino,
            yhteyshenkilo.postitp,
            yhteyshenkilo.puh,
            yhteyshenkilo.tunnus as yhteyshenkilo_tunnus,
            yhteyshenkilo.ulkoinen_asiakasnumero,
            yhteyshenkilo.verkkokauppa_nakyvyys,
            yhteyshenkilo.verkkokauppa_salasana,
            yhteyshenkilo.yhtio
            FROM yhteyshenkilo
            INNER JOIN asiakas
            ON (asiakas.yhtio = yhteyshenkilo.yhtio
              AND asiakas.tunnus      = yhteyshenkilo.liitostunnus )
            LEFT JOIN avainsana
            ON (avainsana.yhtio = asiakas.yhtio
              AND avainsana.selite    = asiakas.ryhma
              AND avainsana.laji      = 'ASIAKASRYHMA')
            WHERE yhteyshenkilo.yhtio = '{$kukarow['yhtio']}'
            AND yhteyshenkilo.rooli   = 'Presta'
            {$muutoslisa}";
  $result = pupe_query($query);

  while ($asiakas = mysqli_fetch_assoc($result)) {
    $osoitteet = array();
    $osoitteet[] = array(
      "asiakas_id"       => $asiakas['asiakas_tunnus'],
      "asiakas_nimi"     => $asiakas['asiakas_nimi'],
      "gsm"              => $asiakas['gsm'],
      "maa"              => $asiakas['maa'],
      "nimi"             => $asiakas['nimi'],
      "osoite"           => $asiakas['osoite'],
      "postino"          => $asiakas['postino'],
      "postitp"          => $asiakas['postitp'],
      "puh"              => $asiakas['puh'],
      "ytunnus"          => $asiakas['ytunnus'],
      "asiakas_nimitark" => $asiakas['asiakas_nimitark'],
    );

    $asiakkaat[] = array(
      "email"                   => $asiakas['email'],
      "kuljetusohje"            => $asiakas['kuljetusohje'],
      "nimi"                    => $asiakas['nimi'],
      "osoitteet"               => $osoitteet,
      "presta_customergroup_id" => $asiakas['selitetark_5'],
      "tunnus"                  => $asiakas['yhteyshenkilo_tunnus'],
      "ulkoinen_asiakasnumero"  => $asiakas['ulkoinen_asiakasnumero'],
      "verkkokauppa_nakyvyys"   => $asiakas['verkkokauppa_nakyvyys'],
      "verkkokauppa_salasana"   => $asiakas['verkkokauppa_salasana'],
      "yhtio"                   => $asiakas['yhtio'],
    );
  }

  return $asiakkaat;
}

function presta_hae_asiakkaat_yhteyshenkiloittain() {
  global $kukarow, $yhtiorow;

  $asiakkaat = array();

  // Haetaan kaikki yhteyshenkilöt ja niiden asiakkaat
  $query = "SELECT yhteyshenkilo.email,
            max(avainsana.selitetark_5) as selitetark_5,
            max(yhteyshenkilo.gsm) as gsm,
            max(yhteyshenkilo.nimi) as nimi,
            max(yhteyshenkilo.puh) as puh,
            max(yhteyshenkilo.tunnus) as tunnus,
            max(yhteyshenkilo.ulkoinen_asiakasnumero) as ulkoinen_asiakasnumero,
            max(yhteyshenkilo.verkkokauppa_nakyvyys) as verkkokauppa_nakyvyys,
            max(yhteyshenkilo.verkkokauppa_salasana) as verkkokauppa_salasana,
            max(yhteyshenkilo.yhtio) as yhtio,
            max(asiakas.kuljetusohje) as kuljetusohje,
            group_concat(yhteyshenkilo.liitostunnus) as asiakkaat
            FROM yhteyshenkilo
            INNER JOIN asiakas ON (asiakas.yhtio = yhteyshenkilo.yhtio
              AND asiakas.tunnus = yhteyshenkilo.liitostunnus)
            LEFT JOIN avainsana ON (avainsana.yhtio = asiakas.yhtio
              AND avainsana.selite = asiakas.ryhma
              AND avainsana.laji = 'ASIAKASRYHMA')
            WHERE yhteyshenkilo.yhtio = '{$kukarow['yhtio']}'
            AND yhteyshenkilo.rooli = 'Presta'
            AND yhteyshenkilo.email != ''
            AND yhteyshenkilo.nimi != ''
            GROUP BY yhteyshenkilo.email";
  $result = pupe_query($query);

  while ($yhteyshenkilo = mysqli_fetch_assoc($result)) {
    $asiakas_tunnukset = $yhteyshenkilo['asiakkaat'];
    $osoitteet = array();

    // haetaan kaikki osoitteet asiakkailta
    $query = "SELECT distinct
              asiakas.tunnus,
              asiakas.ytunnus,
              asiakas.nimi,
              asiakas.nimitark,
              asiakas.osoite,
              asiakas.postino,
              asiakas.postitp,
              asiakas.maa
              FROM asiakas
              WHERE asiakas.yhtio = '{$kukarow['yhtio']}'
              AND asiakas.tunnus in ($asiakas_tunnukset)
              AND asiakas.osoite != ''";
    $osoite_result = pupe_query($query);

    while ($osoite = mysqli_fetch_assoc($osoite_result)) {
      $osoitteet[] = array(
        "asiakas_id"       => $osoite['tunnus'],
        "asiakas_nimi"     => $osoite['nimi'],
        "gsm"              => $yhteyshenkilo['gsm'],
        "maa"              => $osoite['maa'],
        "nimi"             => $yhteyshenkilo['nimi'],
        "osoite"           => $osoite['osoite'],
        "postino"          => $osoite['postino'],
        "postitp"          => $osoite['postitp'],
        "puh"              => $yhteyshenkilo['puh'],
        "ytunnus"          => $osoite['ytunnus'],
        "asiakas_nimitark" => $osoite['nimitark'],
      );
    }

    // haetaan kaikki toimitusosoitteet asiakkailta
    $query = "SELECT distinct
              asiakas.tunnus,
              asiakas.ytunnus,
              asiakas.nimi,
              asiakas.nimitark,
              asiakas.toim_osoite,
              asiakas.toim_postino,
              asiakas.toim_postitp,
              asiakas.toim_maa
              FROM asiakas
              WHERE asiakas.yhtio = '{$kukarow['yhtio']}'
              AND asiakas.tunnus in ($asiakas_tunnukset)
              AND asiakas.toim_osoite != ''";
    $osoite_result = pupe_query($query);

    while ($osoite = mysqli_fetch_assoc($osoite_result)) {
      $osoitteet[] = array(
        "asiakas_id"       => $osoite['tunnus'],
        "asiakas_nimi"     => $osoite['nimi'],
        "gsm"              => $yhteyshenkilo['gsm'],
        "maa"              => $osoite['toim_maa'],
        "nimi"             => $yhteyshenkilo['nimi'],
        "osoite"           => $osoite['toim_osoite'],
        "postino"          => $osoite['toim_postino'],
        "postitp"          => $osoite['toim_postitp'],
        "puh"              => $yhteyshenkilo['puh'],
        "ytunnus"          => $osoite['ytunnus'],
        "asiakas_nimitark" => $osoite['nimitark'],
      );
    }

    // lisätään asiakas array
    $asiakkaat[] = array(
      "email"                   => $yhteyshenkilo['email'],
      "kuljetusohje"            => $yhteyshenkilo['kuljetusohje'],
      "nimi"                    => $yhteyshenkilo['nimi'],
      "osoitteet"               => $osoitteet,
      "presta_customergroup_id" => $yhteyshenkilo['selitetark_5'],
      "tunnus"                  => $yhteyshenkilo['tunnus'],
      "ulkoinen_asiakasnumero"  => $yhteyshenkilo['ulkoinen_asiakasnumero'],
      "verkkokauppa_nakyvyys"   => $yhteyshenkilo['verkkokauppa_nakyvyys'],
      "verkkokauppa_salasana"   => $yhteyshenkilo['verkkokauppa_salasana'],
      "yhtio"                   => $yhteyshenkilo['yhtio'],
    );
  }

  return $asiakkaat;
}

function presta_hae_asiakas_tunnuksella($tunnus) {
  global $kukarow, $yhtiorow;

  if (empty($tunnus)) {
    return null;
  }

  $query = "SELECT asiakas.*
            FROM asiakas
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = {$tunnus}
            LIMIT 1";
  $result = pupe_query($query);

  if (mysqli_num_rows($result) != 1) {
    return null;
  }

  return mysqli_fetch_assoc($result);
}

function presta_hae_yhteyshenkilon_asiakas_ulkoisella_asiakasnumerolla($asiakasnumero) {
  global $kukarow, $yhtiorow;

  if (empty($asiakasnumero)) {
    return null;
  }

  $query = "SELECT asiakas.*
            FROM yhteyshenkilo
            INNER JOIN asiakas
            ON (asiakas.yhtio = yhteyshenkilo.yhtio
              AND asiakas.tunnus = yhteyshenkilo.liitostunnus)
            WHERE yhteyshenkilo.yhtio = '{$kukarow['yhtio']}'
            AND yhteyshenkilo.ulkoinen_asiakasnumero = {$asiakasnumero}
            LIMIT 1";
  $result = pupe_query($query);

  if (mysqli_num_rows($result) != 1) {
    return null;
  }

  return mysqli_fetch_assoc($result);
}

function presta_hae_asiakasryhmat() {
  global $kukarow, $yhtiorow;

  presta_echo("Haetaan kaikki asiakasryhmät.");

  // HUOM. pakko hakea aina kaikki, koska muuten ei osata deletoida poistettuja
  $query = "SELECT avainsana.*,
            avainsana.selitetark_5 AS presta_customergroup_id
            FROM avainsana
            WHERE avainsana.yhtio = '{$kukarow['yhtio']}'
            AND laji              = 'ASIAKASRYHMA'";
  $result = pupe_query($query);

  $ryhmat = array();

  while ($ryhma = mysqli_fetch_assoc($result)) {

    // Onko ryhmälle luotu kaikkiin tuotteisiin pureva alennus?
    $query = "SELECT alennus, alennuslaji, minkpl, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, tunnus, campaign_id
              FROM asiakasalennus USE INDEX (yhtio_asiakasryhma_ryhma)
              WHERE yhtio        = '{$kukarow['yhtio']}'
              and asiakas_ryhma  = '{$ryhma['selite']}'
              and asiakas_ryhma != ''
              and ryhma          = '**'
              and ytunnus        = ''
              and asiakas        = 0
              and (minkpl = 0 or (minkpl <= 1 and monikerta = '') or (mod(1, minkpl) = 0 and monikerta != ''))
              and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
              and alennus        >= 0
              and alennus        <= 100
              ORDER BY alennuslaji, minkpl desc, aika, alennus desc, tunnus desc
              LIMIT 1";
    $aleres = pupe_query($query);
    $alerow = mysqli_fetch_assoc($aleres);

    if (!empty($alerow["alennus"])) {
      // Ylikirjataan selitetark_2-johon voi myös käsin antaa vain Prestassa voimassa olevat alennukset
      $ryhma["selitetark_2"] = $alerow["alennus"];
    }

    $ryhmat[] = $ryhma;
  }

  return $ryhmat;
}

function presta_specific_prices(array $ajolista) {
  global $kukarow, $yhtiorow;

  presta_echo("Haetaan tuotteiden ".implode(', ', $ajolista).".");

  // Laitetaan hinnat ja alennukset samaan arrayseen, koska Prestassa niitä käsitellään samalla tavalla
  $specific_prices = array();

  // HUOM! yhteyshenkilo.liitostunnus = asiakashinta.asiakas tarkoittaa, että sama asiakashintarivi
  // voi tulla monta kertaa, koska asiakas has_many yhteyshenkilö.
  // Näin pitääkin, koska yhteyshenkilö on Prestassa asiakas.

  // HUOM! pakko hakea aina kaikki alennukset,
  // koska asiakkaalta poistetaan aina aluksi kaikki alennukset.

  // Query pitää olla sama kun presta_hae_tuotteet (ilman muutospäivää)
  $tuoterajaus = presta_tuoterajaus();

  $query = "SELECT tuote.tuoteno
            FROM tuote
            WHERE tuote.yhtio = '{$kukarow['yhtio']}'
            {$tuoterajaus}";
  $result = pupe_query($query);

  while ($tuote = mysqli_fetch_assoc($result)) {
    // Katsotaan tällä löytyykö tuotteelle hintoja
    $tuotehintoja = 0;

    // Asiakashinnat
    if (array_search('asiakashinnat', $ajolista) !== false) {
      $query = "SELECT
                asiakashinta.tuoteno,
                asiakashinta.alkupvm,
                asiakashinta.loppupvm,
                asiakashinta.minkpl,
                asiakashinta.hinta,
                asiakashinta.valkoodi,
                avainsana.selitetark_5 AS presta_customergroup_id,
                yhteyshenkilo.ulkoinen_asiakasnumero AS presta_customer_id,
                'asiakashinta' AS tyyppi
                FROM asiakashinta
                LEFT JOIN avainsana ON (avainsana.yhtio = asiakashinta.yhtio
                  AND avainsana.selite           = asiakashinta.asiakas_ryhma
                  AND avainsana.laji             = 'ASIAKASRYHMA')
                LEFT JOIN yhteyshenkilo ON (yhteyshenkilo.yhtio = asiakashinta.yhtio
                  AND yhteyshenkilo.liitostunnus = asiakashinta.asiakas)
                WHERE asiakashinta.yhtio         = '{$kukarow['yhtio']}'
                AND asiakashinta.tuoteno         = '{$tuote['tuoteno']}'
                AND if(asiakashinta.alkupvm  = '0000-00-00', '0001-01-01', asiakashinta.alkupvm)  <= current_date
                AND if(asiakashinta.loppupvm = '0000-00-00', '9999-12-31', asiakashinta.loppupvm) >= current_date
                AND asiakashinta.hinta           > 0";
      $asiakashintaresult = pupe_query($query);

      while ($asiakashinta = mysqli_fetch_assoc($asiakashintaresult)) {
        $specific_prices[] = $asiakashinta;
        $tuotehintoja += 1;
      }
    }

    // Asiakasalennukset
    if (array_search('asiakasalennukset', $ajolista) !== false) {
      $query = "SELECT
                asiakasalennus.tuoteno,
                asiakasalennus.alkupvm,
                asiakasalennus.loppupvm,
                asiakasalennus.minkpl,
                asiakasalennus.alennus,
                avainsana.selitetark_5 AS presta_customergroup_id,
                yhteyshenkilo.ulkoinen_asiakasnumero AS presta_customer_id,
                'asiakasalennus' AS tyyppi
                FROM asiakasalennus
                LEFT JOIN avainsana ON (avainsana.yhtio = asiakasalennus.yhtio
                  AND avainsana.selite           = asiakasalennus.asiakas_ryhma
                  AND avainsana.laji             = 'ASIAKASRYHMA')
                LEFT JOIN yhteyshenkilo ON (yhteyshenkilo.yhtio = asiakasalennus.yhtio
                  AND yhteyshenkilo.liitostunnus = asiakasalennus.asiakas)
                WHERE asiakasalennus.yhtio       = '{$kukarow['yhtio']}'
                AND asiakasalennus.tuoteno       = '{$tuote['tuoteno']}'
                AND if(asiakasalennus.alkupvm  = '0000-00-00', '0001-01-01', asiakasalennus.alkupvm)  <= current_date
                AND if(asiakasalennus.loppupvm = '0000-00-00', '9999-12-31', asiakasalennus.loppupvm) >= current_date
                AND asiakasalennus.alennus       > 0";
      $asiakasalennusresult = pupe_query($query);

      while ($asiakasalennus = mysqli_fetch_assoc($asiakasalennusresult)) {
        $specific_prices[] = $asiakasalennus;
        $tuotehintoja += 1;
      }
    }

    // Hinnastohinnat
    if (array_search('hinnastohinnat', $ajolista) !== false) {
      // Haetaan aluksi kaikki mahdolliset tuoteno/valuutta/maa kombot
      // Lajit:
      // '' Bruttohinta Myyntihinta
      // 'N' N-Nettohinta Myyntihinta
      // 'E' E-Nettohinta Myyntihinta
      // 'K' Informatiivinen hinta
      $query = "SELECT distinct hinnasto.tuoteno, hinnasto.valkoodi, hinnasto.maa
                FROM hinnasto
                WHERE hinnasto.yhtio = '{$kukarow['yhtio']}'
                AND hinnasto.tuoteno = '{$tuote['tuoteno']}'
                AND hinnasto.laji    in ('', 'N', 'E', 'K')
                AND hinnasto.hinta   > 0";
      $hintavalresult = pupe_query($query);

      while ($hintavalrow = mysqli_fetch_assoc($hintavalresult)) {
        // katotaan onko tuotteelle voimassa hinnastohintoja
        $query = "SELECT hinnasto.tuoteno,
                  hinnasto.alkupvm,
                  hinnasto.loppupvm,
                  hinnasto.minkpl,
                  hinnasto.hinta,
                  hinnasto.valkoodi,
                  hinnasto.maa,
                  if(hinnasto.laji = 'K', 'informatiivinen_hinta', 'hinnastohinta') AS tyyppi
                  FROM hinnasto
                  WHERE hinnasto.yhtio  = '{$kukarow['yhtio']}'
                  AND hinnasto.tuoteno  = '{$hintavalrow['tuoteno']}'
                  AND hinnasto.hinta    > 0
                  AND hinnasto.valkoodi = '{$hintavalrow['valkoodi']}'
                  AND hinnasto.maa      = '{$hintavalrow['maa']}'
                  AND hinnasto.laji     in ('', 'N', 'E', 'K')
                  AND if(hinnasto.alkupvm  = '0000-00-00', '0001-01-01', hinnasto.alkupvm)  <= current_date
                  AND if(hinnasto.loppupvm = '0000-00-00', '9999-12-31', hinnasto.loppupvm) >= current_date";
        $hinnastoresult = pupe_query($query);

        while ($hinnasto = mysqli_fetch_assoc($hinnastoresult)) {
          $specific_prices[] = $hinnasto;
          $tuotehintoja += 1;
        }
      }
    }
  }

  // TODO karsea kovakoodaus. pitää keksiä tähän dynaamisempi vaihtoehto.
  if ($kukarow['yhtio'] == 'audio') {
    // Kaikille tuotteille halutaan tuotteen myyntihinta Prestan Specific Price -listaan
    // Prestan asiakasryhmälle 3
    $query = "SELECT
              tuote.tuoteno,
              '0000-00-00' as alkupvm,
              '0000-00-00' as loppupvm,
              '' as minkpl,
              tuote.myyntihinta as hinta,
              '{$yhtiorow['valkoodi']}' as valkoodi,
              '3' AS presta_customergroup_id,
              '' AS presta_customer_id,
              'customhinta' AS tyyppi
              FROM tuote
              WHERE tuote.yhtio     = '{$kukarow['yhtio']}'
              AND tuote.myyntihinta > 0
              {$tuoterajaus}";
    $result = pupe_query($query);

    while ($asiakashinta = mysqli_fetch_assoc($result)) {
      $specific_prices[] = $asiakashinta;
      $tuotehintoja += 1;
    }
  }

  // Jos tuotteelle ei ole yhtään hintoja, lisätään se tyhjänä.
  // Silloin saadaan poistettua tältä tuotteelta alet Prestasta
  if ($tuotehintoja == 0) {
    $specific_prices[] = array(
      "alkupvm"                 => "",
      "hinta"                   => "",
      "loppupvm"                => "",
      "minkpl"                  => "",
      "presta_customer_id"      => "",
      "presta_customergroup_id" => "",
      "tuoteno"                 => $tuote['tuoteno'],
      "tyyppi"                  => "",
      "valkoodi"                => "",
    );
  }

  // sortataan array tuotejärjestykseen, silloin tuote ei ole ikinä kauaa ilman alennuksia
  // rajapinta dellaa aina aluksi tuotteen alennukset, sen jälkeen lisää kaikki takaisin
  sort_array_of_arrays($specific_prices, 'tuoteno');

  return $specific_prices;
}

function presta_hae_kategoriat() {
  global $kukarow, $yhtiorow;

  presta_echo("Haetaan kaikki tuotekategoriat.");

  // haetaan kaikki kategoriat ja niiden parent_id
  // HUOM! pakko hakea aina kaikki, että osataan poistaa poistetut/siirretyt
  $query = "SELECT node.nimi,
            node.koodi,
            node.tunnus AS node_tunnus,
            (SELECT parent.tunnus
             FROM dynaaminen_puu AS parent
             WHERE parent.yhtio = node.yhtio
             AND parent.laji    = node.laji
             AND parent.lft     < node.lft
             AND parent.rgt     > node.rgt
             ORDER by parent.lft DESC
             LIMIT 1) as parent_tunnus
            FROM dynaaminen_puu AS node
            WHERE node.yhtio    = '{$kukarow['yhtio']}'
            AND node.laji       = 'tuote'
            ORDER BY node.syvyys, node.lft";
  $result = pupe_query($query);

  $kategoriat = array();

  while ($kategoria = mysqli_fetch_assoc($result)) {
    // Haetaan kategorian käännökset
    $query = "SELECT kieli, tarkenne
              FROM dynaaminen_puu_avainsanat
              WHERE yhtio      = '{$kukarow['yhtio']}'
              AND liitostunnus = '{$kategoria['node_tunnus']}'
              AND laji         = 'tuote'
              AND avainsana    = 'nimi'";
    $tr_result = pupe_query($query);
    $kaannokset = array();

    while ($tr_row = mysqli_fetch_assoc($tr_result)) {
      $kaannokset[] = array(
        "kieli" => $tr_row['kieli'],
        "nimi"  => $tr_row['tarkenne']
      );
    }

    $kategoriat[] = array(
      "kaannokset"    => $kaannokset,
      "koodi"         => $kategoria['koodi'],
      "nimi"          => $kategoria['nimi'],
      "node_tunnus"   => $kategoria['node_tunnus'],
      "parent_tunnus" => $kategoria['parent_tunnus'],
    );
  }

  return $kategoriat;
}

function presta_hae_kaikki_tuotteet() {
  global $kukarow, $yhtiorow, $presta_varastot;

  presta_echo("Haetaan kaikki tuotteet ja varastosaldot.");

  $tuoterajaus = presta_tuoterajaus();

  if (!is_array($presta_varastot)) {
    die('Presta varastot ei ole array!');
  }

  // Haetaan kaikki siirrettävät tuotteet, tämä on poistettujen dellausta varten
  // query pitää olla sama kun presta_hae_tuotteet (ilman muutospäivää)
  $query = "SELECT tuote.tuoteno, tuote.ei_saldoa, tuote.status
            FROM tuote
            WHERE tuote.yhtio = '{$kukarow['yhtio']}'
            {$tuoterajaus}";
  $res = pupe_query($query);

  $tuotteet = array();

  while ($row = mysqli_fetch_array($res)) {
    $tuoteno = $row['tuoteno'];

    // Katsotaan onko tämä isätuote
    $query = "SELECT tunnus
              FROM tuoteperhe
              WHERE yhtio    = '{$kukarow['yhtio']}'
              AND isatuoteno = '{$tuoteno}'
              AND tyyppi     = 'P'
              LIMIT 1";
    $tr_result = pupe_query($query);

    if (mysqli_num_rows($tr_result) == 1) {
      // isätuote
      $isa_saldot = tuoteperhe_myytavissa($tuoteno, 'KAIKKI', '', $presta_varastot);
      $myytavissa = 0;

      foreach ($isa_saldot as $isa_varasto => $isa_saldo) {
        $myytavissa += $isa_saldo;
      }
    }
    elseif ($row['ei_saldoa'] != '') {
      // saldottomille tuoteteilla null, jotta presta tietää olla lisäämättä tätä saldoa
      $myytavissa = null;
    }
    else {
      // normituote
      list(, , $myytavissa) = saldo_myytavissa($tuoteno, '', $presta_varastot);
    }

    // lisätään saldon päivittämiseen tarvittavat tiedot
    $tuotteet[] = array(
      "saldo"   => $myytavissa,
      "status"  => $row['status'],
      "tuoteno" => $tuoteno,
    );
  }

  return $tuotteet;
}

function presta_hae_tuotteet() {
  global $kukarow, $yhtiorow, $ajetaanko_kaikki;

  $datetime_checkpoint = presta_export_checkpoint('PSTS_TUOTE');

  $tuoterajaus = presta_tuoterajaus();

  if ($ajetaanko_kaikki == "NO") {
    presta_echo("Haetaan tuotteet, joita on muokattu {$datetime_checkpoint} jälkeen.");

    $tuoterajaus .= " AND (tuote.muutospvm >= '{$datetime_checkpoint}'";
    $tuoterajaus .= " OR puun_alkio.muutospvm >= '{$datetime_checkpoint}'";
    $tuoterajaus .= " OR tuotteen_avainsanat.muutospvm >= '{$datetime_checkpoint}') ";
  }
  else {
    presta_echo("Haetaan kaikki tuotteet.");
  }

  // tuotteen "tilaustuote" -termi kaikilla kielillä
  $tilaustuote_kaannokset = presta_hae_tilaustuote();

  // Haetaan pupesta tuotteen tiedot
  $query = "SELECT distinct tuote.*
            FROM tuote
            LEFT JOIN puun_alkio ON (puun_alkio.yhtio = tuote.yhtio
              AND puun_alkio.laji             = 'tuote'
              AND puun_alkio.liitos           = tuote.tuoteno)
            LEFT JOIN tuotteen_avainsanat ON (tuotteen_avainsanat.yhtio = tuote.yhtio
              AND tuotteen_avainsanat.tuoteno = tuote.tuoteno
              AND tuotteen_avainsanat.laji    IN ('nimitys', 'kuvaus', 'lyhytkuvaus'))
            WHERE tuote.yhtio                 = '{$kukarow['yhtio']}'
            {$tuoterajaus}";
  $res = pupe_query($query);
  $dnstuote = array();

  // Pyöräytetään muuttuneet tuotteet läpi
  while ($row = mysqli_fetch_array($res)) {
    // Jos yhtiön hinnat eivät sisällä alv:tä
    if ($yhtiorow["alv_kasittely"] != "") {
      $myyntihinta = hintapyoristys($row["myyntihinta"] * (1 + ($row["alv"] / 100)));
      $myyntihinta_veroton = $row["myyntihinta"];

      $myymalahinta = hintapyoristys($row["myymalahinta"] * (1 + ($row["alv"] / 100)));
      $myymalahinta_veroton = $row["myymalahinta"];
    }
    else {
      $myyntihinta = $row["myyntihinta"];
      $myyntihinta_veroton = hintapyoristys($row["myyntihinta"] / (1 + ($row["alv"] / 100)));

      $myymalahinta = $row["myymalahinta"];
      $myymalahinta_veroton = hintapyoristys($row["myymalahinta"] / (1 + ($row["alv"] / 100)));
    }

    // erikoistapaukset, jos hintoja halutaan siirtää vasten pupen alv_käsittelyä
    $myyntihinta_verot_pois    = hintapyoristys($row["myyntihinta"]  / (1 + ($row["alv"] / 100)), 6);
    $myyntihinta_verot_mukaan  = hintapyoristys($row["myyntihinta"]  * (1 + ($row["alv"] / 100)), 6);
    $myymalahinta_verot_pois   = hintapyoristys($row["myymalahinta"] / (1 + ($row["alv"] / 100)), 6);
    $myymalahinta_verot_mukaan = hintapyoristys($row["myymalahinta"] * (1 + ($row["alv"] / 100)), 6);

    // Haetaan tuotteen kaikki käännökset
    $query = "SELECT kieli, laji, selite
              FROM tuotteen_avainsanat
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tuoteno = '{$row['tuoteno']}'
              AND laji    IN ('nimitys', 'kuvaus', 'lyhytkuvaus')";
    $tr_result = pupe_query($query);
    $tuotteen_kaannokset = array();

    while ($tr_row = mysqli_fetch_assoc($tr_result)) {
      $tuotteen_kaannokset[] = array(
        "kieli"  => $tr_row['kieli'],
        "kentta" => $tr_row['laji'],
        "teksti" => $tr_row['selite']
      );
    }

    // jos tämä on tilaustuote, liitetään käännökset
    if ($row['status'] == 'T') {
      $tuotteen_kaannokset = array_merge($tuotteen_kaannokset, $tilaustuote_kaannokset);
    }

    // Haetaan tuotteen lapsituotteet, jos tämä on isätuote
    $query = "SELECT tuoteno, kerroin
              FROM tuoteperhe
              WHERE yhtio    = '{$kukarow['yhtio']}'
              AND isatuoteno = '{$row['tuoteno']}'
              AND tyyppi     = 'P'";
    $tr_result = pupe_query($query);
    $tuotteen_lapsituotteet = array();

    // tämä on isätuote
    while ($tr_row = mysqli_fetch_assoc($tr_result)) {
      // prestassa ei lapsituotteelle voida määrittää muuta kuin tuotenumero ja kerroin
      $tuotteen_lapsituotteet[] = array(
        "kerroin" => $tr_row['kerroin'],
        "tuoteno" => $tr_row['tuoteno'],
      );
    }

    // Jos tuote kuuluu tuotepuuhun niin haetaan kategoria_idt
    $query = "SELECT puun_tunnus
              FROM puun_alkio
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND laji    = 'tuote'
              AND liitos  = '{$row['tuoteno']}'";
    $result_tp = pupe_query($query);
    $tuotepuun_tunnukset = array();

    while ($tuotepuurow = mysqli_fetch_assoc($result_tp)) {
      $tuotepuun_tunnukset[] = $tuotepuurow['puun_tunnus'];
    }

    $dnstuote[] = array(
      'alv'                       => $row["alv"],
      'ean'                       => $row["eankoodi"],
      'ei_saldoa'                 => $row["ei_saldoa"],
      'keraysvyohyke'             => $row["keraysvyohyke"],
      'kuluprosentti'             => $row['kuluprosentti'],
      'kuvaus'                    => $row["kuvaus"],
      'lyhytkuvaus'               => $row["lyhytkuvaus"],
      'mainosteksti'              => $row['mainosteksti'],
      'mallitarkenne'             => $row['mallitarkenne'],
      'myynti_era'                => $row['myynti_era'],
      'nakyvyys'                  => $row["nakyvyys"],
      'nimi'                      => $row["nimitys"],
      'osasto'                    => $row["osasto"],
      'status'                    => $row["status"],
      'tahtituote'                => $row['tahtituote'],
      'try'                       => $row["try"],
      'tunnus'                    => $row['tunnus'],
      'tuotekorkeus'              => $row['tuotekorkeus'],
      'tuoteleveys'               => $row['tuoteleveys'],
      'tuotemassa'                => $row['tuotemassa'],
      'tuotemerkki'               => $row["tuotemerkki"],
      'tuoteno'                   => $row["tuoteno"],
      'tuotesyvyys'               => $row['tuotesyvyys'],
      'valmistuslinja'            => $row['valmistuslinja'],
      'yksikko'                   => $row["yksikko"],
      'myymalahinta'              => $myymalahinta,
      'myymalahinta_verot_mukaan' => $myymalahinta_verot_mukaan,
      'myymalahinta_verot_pois'   => $myymalahinta_verot_pois,
      'myymalahinta_veroton'      => $myymalahinta_veroton,
      'myyntihinta'               => $myyntihinta,
      'myyntihinta_verot_mukaan'  => $myyntihinta_verot_mukaan,
      'myyntihinta_verot_pois'    => $myyntihinta_verot_pois,
      'myyntihinta_veroton'       => $myyntihinta_veroton,
      'tuotepuun_tunnukset'       => $tuotepuun_tunnukset,
      'tuotteen_kaannokset'       => $tuotteen_kaannokset,
      'tuotteen_lapsien_maara'    => count($tuotteen_lapsituotteet),
      'tuotteen_lapsituotteet'    => $tuotteen_lapsituotteet,
    );
  }

  // pitää sortata array siten, että isätuotteet ovat lopussa.
  // muuten lapsia ei välttämättä ole perustettu, kun yritetään rakentaa perhettä
  sort_array_of_arrays($dnstuote, 'tuotteen_lapsien_maara');

  return $dnstuote;
}

function presta_tuoterajaus() {
  $presta_tuotekasittely = $GLOBALS["presta_tuotekasittely"];

  $tuoterajaus = " AND tuote.tuoteno != ''
                   AND tuote.tuotetyyppi NOT in ('A','B') ";

  if ($presta_tuotekasittely == 2) {
    $tuoterajaus .= "";
  }
  else {
    $tuoterajaus .= " AND tuote.status != 'P'
                      AND tuote.nakyvyys != '' ";
  }

  return $tuoterajaus;
}

function presta_ajetaanko_sykronointi($ajo, $ajolista) {
  // jos ajo ei ole ajolistalla, ei ajeta
  if (array_search(mb_strtolower(trim($ajo)), $ajolista) === false) {
    return false;
  }

  // Sallitaan vain yksi instanssi tästä ajosta kerrallaan
  $lock_params = array(
    "lockfile" => "presta-{$ajo}-flock.lock",
    "locktime" => 5400,
    "return"   => true,
  );

  $status = pupesoft_flock($lock_params);

  if ($status === false) {
    presta_echo("{$ajo} -ajo on jo käynnissä, ei ajeta uudestaan.");
  }

  return $status;
}

function presta_export_checkpoint($checkpoint) {
  global $kukarow, $yhtiorow;

  // Haetaan timestamp avainsanoista
  $checkpoint_res = t_avainsana($checkpoint, 'fi');

  // otetaan viimeisen ajon timestamppi talteen ja päivitetään tämä hetki
  if (mysqli_num_rows($checkpoint_res) != 0) {
    $row = mysqli_fetch_assoc($checkpoint_res);
    $selite = $row['selite'];

    // Päivitetään timestamppi tähän hetkeen
    $query = "UPDATE avainsana SET
              selite      = now()
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND laji    = '{$checkpoint}'";
    pupe_query($query);
  }
  else {
    // timestamppia ei löydy, eli tämä on ensimmäinen ajo
    $selite = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1, 1970));

    // Päivitetään timestamppi talteen
    $query = "INSERT INTO avainsana SET
              kieli      = 'fi',
              laatija    = '{$kukarow['kuka']}',
              laji       = '{$checkpoint}',
              luontiaika = now(),
              muutospvm  = now(),
              muuttaja   = '{$kukarow['kuka']}',
              selite     = now(),
              yhtio      = '{$kukarow['yhtio']}'";
    pupe_query($query);
  }

  return $selite;
}

function presta_echo($string) {
  if ($GLOBALS['presta_debug'] !== true) {
    return;
  }

  echo date("d.m.Y @ G:i:s")." - {$string}\n";
}

function presta_image_exists($sku, $id) {
  $kukarow  = $GLOBALS["kukarow"];
  $yhtiorow = $GLOBALS["yhtiorow"];

  if (empty($kukarow) or empty($yhtiorow)) {
    die("ERROR!");
  }

  // Haetaan tuote
  $tuote = hae_tuote($sku);

  if (empty($tuote['tunnus'])) {
    return false;
  }

  $liitostunnus = $tuote['tunnus'];

  // katsotaan onko meillä jo tämä tuotekuva tallessa
  $query = "SELECT tunnus
            FROM liitetiedostot
            WHERE yhtio         = '{$kukarow['yhtio']}'
            AND liitos          = 'tuote'
            AND liitostunnus    = {$liitostunnus}
            AND kayttotarkoitus = 'TK'
            AND selite          = '{$id}'";
  $result = pupe_query($query);

  if (mysqli_num_rows($result) == 0) {
    return false;
  }

  return true;
}

function presta_tallenna_liite($params) {
  $kukarow  = $GLOBALS["kukarow"];
  $yhtiorow = $GLOBALS["yhtiorow"];

  $filename = $params['filename'];
  $image_id = $params['id'];
  $sku      = $params['sku'];

  if (empty($sku) or empty($filename) or empty($image_id) or empty($kukarow) or empty($yhtiorow)) {
    return false;
  }

  // Haetaan tuote
  $tuote = hae_tuote($sku);

  if (empty($tuote['tunnus'])) {
    return false;
  }

  $liitostunnus = $tuote['tunnus'];

  // params: filename, liitos, liitostunnus, selite, käyttötarkoitus
  tallenna_liite($filename, 'tuote', $liitostunnus, $image_id, 'TK');
}

function presta_poista_ylimaaraiset_kuvat($sku, array $all_ids) {
  $kukarow  = $GLOBALS["kukarow"];
  $yhtiorow = $GLOBALS["yhtiorow"];

  if (empty($kukarow) or empty($yhtiorow)) {
    die("ERROR!");
  }

  // Haetaan tuote
  $tuote = hae_tuote($sku);

  if (empty($tuote['tunnus'])) {
    return false;
  }

  $liitostunnus = $tuote['tunnus'];

  // meillä ei ole yhtään kuvaan prestassa, dellataan kaikki
  if (count($all_ids) == 0) {
    $selite_query = "AND selite != ''";
  }
  else {
    $presta_product_ids = implode(',', $all_ids);
    $selite_query = "AND selite not in ({$presta_product_ids})";
  }

  // dellataan kuvat
  $query = "DELETE
            FROM liitetiedostot
            WHERE yhtio         = '{$kukarow['yhtio']}'
            AND liitos          = 'tuote'
            AND liitostunnus    = {$liitostunnus}
            AND kayttotarkoitus = 'TK'
            {$selite_query}";
  $result = pupe_query($query);
  $count = mysqli_affected_rows($link);

  return $count;
}

function presta_hae_tilaustuote() {
  global $kukarow, $yhtiorow;

  if (empty($kukarow) or empty($yhtiorow)) {
    die("ERROR!");
  }

  $kielet = array('fi', 'se', 'no', 'en', 'de', 'dk', 'ru', 'ee');

  $query = "SELECT " . implode(',', $kielet) . "
            FROM sanakirja
            WHERE fi = 'Tilaustuote'
            LIMIT 1";
  $result = pupe_query($query);
  $sanakirjarow = mysqli_fetch_assoc($result);

  $tuotteen_statukset = array();
  foreach ($kielet as $kieli) {
    if ($sanakirjarow[$kieli] == "") continue;

    $tuotteen_statukset[] = array(
      "kieli"  => $kieli,
      "kentta" => "tilaustuote",
      "teksti" => $sanakirjarow[$kieli],
    );
  }

  return $tuotteen_statukset;
}
