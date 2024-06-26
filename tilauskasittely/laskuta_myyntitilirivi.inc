<?php

// Määritellään funktio, jolla kopsataan valitut rivit uudelle myyntitilaukselle.
require "tilauksesta_myyntitilaus.inc";

if ($tilatapa == "VALITSE") {
  $sela = "";
  $selb = "";

  if ($row["var"] == "A") {
    $sela = "SELECTED";
  }
  if ($row["var"] == "B") {
    $selb = "SELECTED";
  }

  $paikat = "<option value=''>".t("Jätä myyntitiliin");
  $paikat .= "<option value='A' $sela>".t("Laskuta asiakasta");
  $paikat .= "<option value='B' $selb>".t("Palauta omaan varastoon");

  $paikat = "<select Style='{font-size: 8pt; padding:0;}' name='myyntitilirivit' onchange='submit();'>".$paikat."</select>";

  echo "  <form method='post'>
      <input type='hidden' name='toim' value='$toim'>
      <input type='hidden' name='tilausnumero' value = '$tilausnumero'>
      <input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
      <input type='hidden' name='tila' value = 'MYYNTITILIRIVI'>";
  echo "<td $class align='left'>$paikat</td>";

  //formi suljetaan tilaus_myynti.php failissa

  $tilatapa  = "";
}

if ($tilatapa == "PAIVITA") {
  //Päivitetään isän perheid nollaksi jotta voidaan lisätä lisä lisävarusteita
  $query  = "SELECT *
             FROM tilausrivi
             WHERE yhtio = '$kukarow[yhtio]'
             AND otunnus = '$kukarow[kesken]'
             AND tunnus  = '$rivitunnus'";
  $qres = pupe_query($query);
  $qrow = mysqli_fetch_array($qres);

  if ($qrow["kpl"] != $kpl and (float) $kpl > 0) {
    $isakpl = $qrow["kpl"];

    if ($qrow["perheid"] != 0) {
      $query  = "SELECT *
                 FROM tilausrivi
                 WHERE yhtio = '$kukarow[yhtio]'
                 AND otunnus = '$kukarow[kesken]'
                 AND perheid = '$qrow[perheid]'
                 ORDER BY tunnus";
      $qres = pupe_query($query);
    }

    mysqli_data_seek($qres, 0);

    $uuden_perheen_perheid = 0;
    $kopsatut_rivit = array();

    while ($qrow = mysqli_fetch_array($qres)) {

      $uusikpl = round(($qrow["kpl"]/$isakpl)*$kpl, 2);
      $erotus  = $qrow["kpl"] - $uusikpl;

      if ($erotus > 0) {
        $query = "UPDATE tilausrivi SET
                  kpl         = '$uusikpl'
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND otunnus = '$kukarow[kesken]'
                  AND tunnus  = '$qrow[tunnus]'";
        $updres = pupe_query($query);

        $fields = mysqli_field_name($qres, 0);
        $values = "'".$qrow[0]."'";

        for ($i=1; $i < mysqli_num_fields($qres)-1; $i++) { // Ei monisteta tunnusta

          $fields .= ", ".mysqli_field_name($qres, $i);

          switch (mysqli_field_name($qres, $i)) {
          case 'laadittu':
            $values .= ", now()";
            break;
          case 'tunnus':
            $values .= ", ''";
            break;
          case 'laatija':
            $values .= ", '$kukarow[kuka]'";
            break;
          case 'kpl':
          case 'tilkpl':
            $values .= ", '$erotus'";
            break;
          case 'kate_korjattu':
            $values .= ", NULL";
            break;
          default:
            $values .= ", '".$qrow[$i]."'";
          }
        }

        $kysely  = "INSERT into tilausrivi ($fields) VALUES ($values)";

	// MUOKKAUS: mysqli_insert_id():
        $insres  = pupe_query($kysely, $GLOBALS["link"]);
        $utunnus = mysqli_insert_id($GLOBALS["link"]);

        if ($uuden_perheen_perheid == 0) {
          $uuden_perheen_perheid = $utunnus;
        }

        $kopsatut_rivit[] = $utunnus;
      }
    }
    if (count($kopsatut_rivit) > 1) {
      foreach ($kopsatut_rivit as $uusitunnu) {
        $query = "UPDATE tilausrivi SET
                  perheid     = '$uuden_perheen_perheid'
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND otunnus = '$kukarow[kesken]'
                  AND tunnus  = '$uusitunnu'";
        $updres = pupe_query($query);

      }
    }
  }

  $query = "UPDATE tilausrivi SET
            var         = '$myyntitilirivit'
            WHERE yhtio = '$kukarow[yhtio]'
            AND otunnus = '$kukarow[kesken]'
            AND (tunnus = '$rivitunnus' or perheid='$rivitunnus')";
  $updres = pupe_query($query);

  $tila     = "";
  $tapa     = "";
  $rivitunnus = "";
  $tilatapa  = "";
}

if ($tilatapa == "LASKUTA") {
  $query = "UPDATE tilausrivi
            SET varattu = kpl
            WHERE yhtio  = '$kukarow[yhtio]'
            AND otunnus  = '$kukarow[kesken]'
            AND var      = 'A'
            AND kpl     != 0";
  $result = pupe_query($query);

  $query  = "SELECT tunnus
             FROM varastopaikat
             WHERE yhtio        = '$kukarow[yhtio]'
             AND alkuhyllyalue  = '!!M'
             AND loppuhyllyalue = '!!M'";
  $result = pupe_query($query);
  $row = mysqli_fetch_array($result);

  $syot_varasto   = $row["tunnus"];
  $syot_var     = "H";
  $tilrivilisa  = " and tilausrivi.var = 'A' and tilausrivi.kpl != 0 ";

  $tilauksesta_myyntitilaus = tilauksesta_myyntitilaus($kukarow["kesken"], $tilrivilisa, $syot_varasto, $syot_var, "", "KOPIOIDAANPAIKAT");

  echo "$tilauksesta_myyntitilaus<br><br>";

  //Laitetaan myyntitilin kappalemääriä kuntoon jotta niitä ei voisi laskuttaa vahingossa monta kertaa
  $query = "UPDATE tilausrivi
            SET varattu = 0,
            kpl          = 0
            WHERE yhtio  = '$kukarow[yhtio]'
            AND otunnus  = '$kukarow[kesken]'
            AND var      = 'A'
            AND kpl     != 0";
  $result = pupe_query($query);

  $tila     = "";
  $tapa     = "";
  $rivitunnus = "";
  $tilatapa  = "";

  echo "<font class='message'>".t("Valitut myyntitilirivit siirretty laskutukseen")."!</font><br><br>";

  $tilatapa = "LEPAA";
}

if ($tilatapa == "PALAUTA") {

  // Tehdään siirrettävistä riveistä siirtolista
  $query = "SELECT tilausrivi.*, concat_ws('#!¡!#', hyllyalue, hyllynro, hyllyvali, hyllytaso) paikka
            FROM tilausrivi
            WHERE tilausrivi.yhtio  = '{$kukarow["yhtio"]}'
            AND tilausrivi.otunnus  = '{$kukarow["kesken"]}'
            AND tilausrivi.var      = 'B'
            AND tilausrivi.kpl     != 0";
  $palauta_result = pupe_query($query);

  if (mysqli_num_rows($palauta_result) > 0) {

    // Tehdään siirtolistaotsikko
    $query = "SELECT varasto, clearing
              FROM lasku
              WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
              AND lasku.tunnus  = '{$kukarow["kesken"]}'";
    $otsikko_result = pupe_query($query);
    $otsikko_row = mysqli_fetch_assoc($otsikko_result);

    // Lähettävä varasto otetaan myyntitilin vastaanottavasta varastosta
    $varasto = $otsikko_row["clearing"];
    // Vastaanottava varasto otetaan myyntitilin lähettävästä varastosta
    $clearing = $otsikko_row["varasto"];

    $toim = "SIIRTOLISTA";
    $jatka = "jatka";
    $kukarow["kesken"] = 0;
    $tilausnumero = 0;

    require "tilauskasittely/otsik_siirtolista.inc";

    $kukarow["kesken"] = $id;

    $query = "SELECT *
              FROM lasku
              WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
              AND lasku.tunnus  = '{$kukarow["kesken"]}'";
    $laskuresult = pupe_query($query);
    $laskurow = mysqli_fetch_assoc($laskuresult);

    // Loopataan läpi myyntitilin rivit..
    while ($palauta_row = mysqli_fetch_assoc($palauta_result)) {

      $query = "SELECT *
                FROM tuote
                WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
                AND tuote.tuoteno = '{$palauta_row["tuoteno"]}'";
      $tuoteresult = pupe_query($query);
      $trow = mysqli_fetch_assoc($tuoteresult);

      $tuoteno = $palauta_row["tuoteno"];
      $kpl = $palauta_row["kpl"];
      $var = "H"; // Varmuuden vuoksi väkisin hyväksytty rivi, ettei tule ongelmia
      $paikka = $palauta_row["paikka"];

      // Lisätään rivi
      // $laskurow      --> jossa on laskun kaikki tiedot
      // $trow        --> jossa on tuotteen kaikki tiedot
      // $tuoteno        --> jossa on tuotenumero
      // $kpl          --> jossa on kappalemäärä
      // $var          --> H,J,P varrit
      // $paikka        --> miltä varastopaikalta siirretään

      require "tilauskasittely/lisaarivi.inc";

      // Päivitetään uusi siirtolistan rivi samantien kerätyksi
      $query = "UPDATE tilausrivi SET
                keratty     = '{$kukarow["kuka"]}',
                kerattyaika = now()
                WHERE yhtio = '{$kukarow["yhtio"]}'
                AND tunnus  in (".implode(",", $lisatyt_rivit1).")";
      $update_result = pupe_query($query);

      // Päivitetään myyntitilin rivi käsittelyksi
      $query = "UPDATE tilausrivi SET
                varattu     = 0,
                kpl         = 0
                WHERE yhtio = '{$kukarow["yhtio"]}'
                AND tunnus  = {$palauta_row["tunnus"]}";
      $tilausrivi_result = pupe_query($query);
    }

    // Päivitetään uusi siirtolista odottamaan vastaanottokuittausta
    $query = "UPDATE lasku SET
              lahetepvm   = now(),
              alatila     = 'C'
              WHERE yhtio = '{$kukarow["yhtio"]}'
              AND tunnus  = '{$laskurow["tunnus"]}'";
    $update_result = pupe_query($query);
  }

  $tila     = "";
  $tapa     = "";
  $rivitunnus = "";
  $tilatapa = "LEPAA";

  unset($jatka);
  $toim = "MYYNTITILI";
  $kukarow["kesken"] = 0;
  $tilausnumero = 0;

  echo "<font class='message'>".t("Valitut rivit siirretään omaan varastoon")."!</font><br><br>";
}

if ($tilatapa == "LEPAA") {
  // poistetaan aktiiviset tilaukset jota tällä käyttäjällä oli
  $query = "UPDATE kuka SET
            kesken      = ''
            WHERE yhtio = '$kukarow[yhtio]'
            AND kuka    = '$kukarow[kuka]'";
  $result = pupe_query($query);

  $tee        = '';
  $tilausnumero    = '';
  $laskurow      = '';
  $kukarow['kesken']  = '';
}
