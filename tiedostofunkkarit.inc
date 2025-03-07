<?php

function hae_toimittajat_selectiin() {
  global $kukarow;

  $result    = t_avainsana('TOIMIT_TKIRJAST');
  $tunnukset = array();

  while ($toimittaja = mysqli_fetch_assoc($result)) {
    array_push($tunnukset, $toimittaja["selite"]);
  }

  if (!empty($tunnukset)) {
    $tunnukset = implode(",", $tunnukset);
    $query     = "SELECT tunnus, nimi
                  FROM toimi
                        WHERE yhtio = '{$kukarow["yhtio"]}'
                        AND tunnus  IN ({$tunnukset})
                  ORDER BY toimi.nimi";

    $result = pupe_query($query);
    $toimittajat = array();

    while ($toimittaja = mysqli_fetch_assoc($result)) {
      array_push($toimittajat, $toimittaja);
    }

    return $toimittajat;
  }

  return false;
}

function tiedostotyypit($aihealue = "", $toim = "") {
  global $kukarow;

  if ($aihealue) {
    if ($toim == 'LUVAT_JA_LISENSSIT') {
      $avainsanat = t_avainsana("LITETY_LUVLIS", "", "AND selite = '{$aihealue}'");
    }
    else {
      $avainsanat = t_avainsana("LITETY_LAATUAS", "", "AND selite = '{$aihealue}'");
    }

    $tiedostotyypit = array();

    while ($tiedostotyyppi = mysqli_fetch_assoc($avainsanat)) {
      array_push($tiedostotyypit, $tiedostotyyppi);
    }

    return $tiedostotyypit;
  }
  else {
    $query = "SELECT selite
              FROM avainsana
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND laji    = 'LITETY_TKIRJAST'";

    $result = pupe_query($query);
    $tiedostotyypit = array();

    while ($tiedostotyyppi = mysqli_fetch_assoc($result)) {
      array_push($tiedostotyypit, mb_strtolower($tiedostotyyppi['selite']));
    }

    return $tiedostotyypit;
  }
}

function hae_tiedostot($params) {
  $tiedoston_tyyppi   = isset($params["tiedoston_tyyppi"]) ? $params["tiedoston_tyyppi"] : "";
  $valittu_toimittaja = isset($params["valittu_toimittaja"]) ? $params["valittu_toimittaja"] : "";
  $aihealue           = isset($params["aihealue"]) ? $params["aihealue"] : "";
  $yleiset_tiedostot  = isset($params["yleiset_tiedostot"]) ? $params["yleiset_tiedostot"] : false;

  global $kukarow;

  $tiedoston_tyyppi = mb_strtolower($tiedoston_tyyppi);

  if ($valittu_toimittaja) {
    $query = "SELECT liitetiedostot.tunnus,
              liitetiedostot.kayttotarkoitus,
              liitetiedostot.selite,
              tuotteen_toimittajat.tuoteno,
              tuote.tunnus AS tuote_tunnus
              FROM tuotteen_toimittajat
              INNER JOIN tuote ON (tuote.yhtio = tuotteen_toimittajat.yhtio
                AND tuotteen_toimittajat.tuoteno    = tuote.tuoteno)
              INNER JOIN liitetiedostot ON (liitetiedostot.yhtio = tuotteen_toimittajat.yhtio
                AND liitetiedostot.liitos           = 'tuote'
                AND liitetiedostot.liitostunnus     = tuote.tunnus
                AND liitetiedostot.kayttotarkoitus  = '{$tiedoston_tyyppi}')
              WHERE tuotteen_toimittajat.yhtio      = '{$kukarow['yhtio']}'
              AND tuotteen_toimittajat.liitostunnus = '{$valittu_toimittaja}'
              ORDER BY liitetiedostot.selite";
  }
  elseif (($aihealue and $tiedoston_tyyppi) or $yleiset_tiedostot) {

    $tiedoston_tyyppi2_lisa = "";

    // Tarkistetaan asiakkaan maa extranetissä
    if ($yleiset_tiedostot and !empty($kukarow['oletus_asiakas']) and $kukarow['extranet'] == 'X') {
      $asiakasrow = hae_asiakas($kukarow['oletus_asiakas']);
      $tiedoston_tyyppi = $asiakasrow['maa'];

      // Tiedoston_tyyppi * = kaikki, eli haetaan ne aina extranetissä
      $tiedoston_tyyppi2_lisa = " OR liitetiedostot.kayttotarkoitus = '{$aihealue} | *'";
    }

    $query = "SELECT liitetiedostot.tunnus,
              liitetiedostot.kayttotarkoitus,
              liitetiedostot.selite
              FROM liitetiedostot
              WHERE liitetiedostot.liitos        = 'muut_tiedostot'
              AND liitetiedostot.liitostunnus    = 0
              AND (
                liitetiedostot.kayttotarkoitus = '{$aihealue} | {$tiedoston_tyyppi}'
                {$tiedoston_tyyppi2_lisa}
              )
              AND liitetiedostot.yhtio           = '{$kukarow["yhtio"]}'
              ORDER BY liitetiedostot.selite";
  }
  elseif ($tiedoston_tyyppi == "extranet") {
    $query = "(SELECT
                liitetiedostot.tunnus,
                liitetiedostot.selite
              FROM liitetiedostot
              INNER JOIN asiakas
                ON liitetiedostot.yhtio = asiakas.yhtio
                AND liitetiedostot.liitos = 'asiakas'
                AND liitetiedostot.liitostunnus = asiakas.tunnus
              INNER JOIN kuka
                ON asiakas.yhtio = kuka.yhtio
                AND asiakas.tunnus = kuka.oletus_asiakas
              WHERE liitetiedostot.kayttotarkoitus = 'extranet'
                AND liitetiedostot.yhtio = '{$kukarow["yhtio"]}'
                AND kuka.tunnus = '{$kukarow["tunnus"]}')
              UNION
              (SELECT liitetiedostot.tunnus, liitetiedostot.selite
              FROM kuka
              JOIN asiakas ON (asiakas.yhtio = kuka.yhtio and asiakas.tunnus = kuka.oletus_asiakas)
              JOIN puun_alkio ON (puun_alkio.yhtio = asiakas.yhtio and puun_alkio.liitos = asiakas.tunnus)
              JOIN liitetiedostot ON (liitetiedostot.yhtio = puun_alkio.yhtio and liitetiedostot.liitostunnus = puun_alkio.puun_tunnus and liitetiedostot.liitos = 'dynaaminen_puu')
              WHERE kuka.yhtio = '{$kukarow["yhtio"]}' and kuka.tunnus = '{$kukarow["tunnus"]}')";
  }

  if (empty($query)) {
    return false;
  }

  $result = pupe_query($query);

  $tiedostot = array();

  while ($tiedosto = mysqli_fetch_assoc($result)) {
    array_push($tiedostot, $tiedosto);
  }

  return $tiedostot;
}

function hae_aihealueet($toim = '') {
  if ($toim == 'LUVAT_JA_LISENSSIT') {
    $avainsanat = t_avainsana("AIHEAL_LUVLIS");
  }
  else {
    $avainsanat = t_avainsana("AIHEAL_LAATUAS");
  }

  $aihealueet = array();

  while ($avainsana = mysqli_fetch_assoc($avainsanat)) {
    array_push($aihealueet, $avainsana);
  }

  return $aihealueet;
}
