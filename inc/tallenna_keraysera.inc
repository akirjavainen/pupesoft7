<?php

if (!is_array($erat)) {
  echo "<br /><font class='error'>", t("VIRHE: Keräyserää ei enää löydy"), "!</font><br />";

  require 'inc/footer.inc';
  exit;
}

$otunnukset      = implode(",", $erat['tilaukset']);
$tilausrivit      = '';
$pakkaus_kirjaimet = array();
$lisatyt_tilausrivit = array();
$lisatyt_tilaukset = array();
$kerayseran_numero = 0;

foreach ($erat as $era_key => $era_arr) {
  if ($era_key == 'pakkaukset') {

    $query = "SELECT ifnull(max(nro), 0) AS nro
              FROM kerayserat
              WHERE yhtio = '{$kukarow['yhtio']}'";
    $nro_fetch_res = pupe_query($query);
    $nro_fetch_row = mysqli_fetch_assoc($nro_fetch_res);

    $kerayseran_numero = $nro_fetch_row['nro'] + 1;

    foreach ($era_arr as $pakkaus_nro => $rarr) {
      foreach ($rarr as $juokseva_nro => $pakkaus) {

        $pakkaus_kirjain = chr(64+$juokseva_nro);

        $rivit     = 0;
        $paino     = 0;
        $tilavuus   = 0;
        $kappaleet   = 0;

        $sscc = uusi_sscc_nro();

        foreach ($pakkaus as $tilriv => $kpl) {
          // varmistetaan, että ei keräytetä nollakappaleita
          if ((float) $kpl != 0) {
            $pakkaus_kirjaimet[$tilriv][$pakkaus_kirjain] = $kpl;
            $rivit += 1;
            $tilausrivit .= "$tilriv,";
            $kappaleet += $kpl;

            $query = "SELECT round((tuote.tuotemassa * (tilausrivi.kpl+tilausrivi.varattu)), 2) as paino, round(((tuote.tuoteleveys * tuote.tuotekorkeus * tuote.tuotesyvyys) * (tilausrivi.kpl+tilausrivi.varattu)), 4) as tilavuus, tilausrivi.otunnus
                      FROM tilausrivi
                      JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
                      WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                      AND tilausrivi.tunnus  = '{$tilriv}'";
            $paino_res = pupe_query($query);
            $paino_row = mysqli_fetch_assoc($paino_res);

            $paino += $paino_row['paino'];
            $tilavuus += $paino_row['tilavuus'];

            $query = "SELECT *
                      FROM lasku
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tunnus  = '{$paino_row['otunnus']}'";
            $res = pupe_query($query);
            $laskurow = mysqli_fetch_assoc($res);

            // jos ulkoinen järjestelmä
            // tila = 'K' tarkoittaa "Keräyksessä"-normivarastossa
            // tila = 'X' tarkoittaa "Keräyksessä"-kardexvarastossa
            $tilalisa = $erat['keraysvyohyketiedot']['ulkoinen_jarjestelma'] == "K" ? "X" : "K";

            $modulilisa = (isset($ohjelma_moduli) and $ohjelma_moduli != "") ? sanitize_string($ohjelma_moduli) : 'PUPESOFT';

            $query = "INSERT INTO kerayserat SET
                      yhtio          = '{$kukarow['yhtio']}',
                      nro            = '{$kerayseran_numero}',
                      keraysvyohyke  = '{$erat['keraysvyohyketiedot']['keraysvyohyke']}',
                      tila           = '{$tilalisa}',
                      sscc           = '{$sscc}',
                      otunnus        = '{$laskurow['tunnus']}',
                      tilausrivi     = '{$tilriv}',
                      pakkaus        = '{$pakkaus_nro}',
                      pakkausnro     = '{$juokseva_nro}',
                      kpl            = '{$kpl}',
                      laatija        = '{$kukarow['kuka']}',
                      luontiaika     = now(),
                      ohjelma_moduli = '$modulilisa'";
            $insert_res = pupe_query($query);
          }
        }
      }
    }

    // Tsekataanko pitääkö tilaukset/rivit splitata (eli keissi jossa koko tilaus ei ole mahtunut erään tai keissi jossa vain osa rivistä on mahtunut erään)
    $query = "SELECT tilausrivi.otunnus, tilausrivi.tunnus, sum(ifnull(kerayserat.kpl,0)) kerkpl
              FROM tilausrivi
              JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus and tilausrivin_lisatiedot.ohita_kerays = '')
              JOIN kerayserat on (kerayserat.yhtio = tilausrivi.yhtio and kerayserat.tilausrivi = tilausrivi.tunnus and kerayserat.nro = '{$kerayseran_numero}')
              WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
              AND tilausrivi.otunnus IN ($otunnukset)
              GROUP BY 1,2
              ORDER BY 1,2";
    $era_res = pupe_query($query);

    $splittaa_tilaus_tilausrivit = array();

    while ($era_row = mysqli_fetch_assoc($era_res)) {
      $lisatyt_tilaukset[$era_row['otunnus']] = $era_row['otunnus'];
      $lisatyt_tilausrivit[$era_row['otunnus']][$era_row['tunnus']] = $era_row['kerkpl'];

      $splittaa_tilaus_tilausrivit[$era_row['otunnus']][$era_row['tunnus']] = $era_row['kerkpl'];
    }

    splittaa_tilaus($splittaa_tilaus_tilausrivit);

    if (isset($kukarow['debugfile']) and $kukarow['debugfile'] != "") {
      file_put_contents($tee_keraysera_debugdir."/".basename($kukarow['debugfile']), $kukarow['debugstring'], FILE_APPEND);

      rename($tee_keraysera_debugdir."/".basename($kukarow['debugfile']), $tee_keraysera_debugdir."/Kerayseradebug-{$kerayseran_numero}.html");

      unset($kukarow['debugfile']);
      unset($kukarow['debugstring']);
    }
  }
}
