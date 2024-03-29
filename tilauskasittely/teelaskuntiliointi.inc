<?php

// tarvitaan arrayt $kukarow, $yhtiorow, $mehtorow ja $lasku
$tulos_ulos_tiliointi = "";

if ($yhtiorow["tarkenteiden_prioriteetti"] == "T") {
  // Tässä tapauksessa toimipaikka ohittaa priossa muut vaikka vat_numero ois tyhjä
  $toimpriolisa = "";
}
else {
  $toimpriolisa = " ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ";
}

if ($yhtiorow["raaka_aine_tiliointi"] == "Y") {
  $raaka_aine_tiliointi_lisa = "if(tuote.tuotetyyppi = 'R', 'raaka-aine', " .
    "'normaalituote') tuotetyyppi,";
  $raaka_aine_tiliointi_group = ",15";
}
else {
  $raaka_aine_tiliointi_lisa = "";
  $raaka_aine_tiliointi_group = "";
}

// Tehtaan tiliointiaineisto suoraan sql:lla
$query = "SELECT tilausrivi.alv alv,
          if(ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ifnull(tuotteen_alv.tilino, '') != '',           tuotteen_alv.tilino,           if({$toimpriolisa} ifnull(yhtion_toimipaikat.tilino, '') != '',         yhtion_toimipaikat.tilino,           if(ifnull(tuote.tilino, '') != '',           tuote.tilino,           if(ifnull(asiakas.tilino, '') != '',           asiakas.tilino,         if(ifnull(yhtion_toimipaikat.tilino, '') != '',         yhtion_toimipaikat.tilino,           yhtio.myynti))))) tilino,
          if(ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ifnull(tuotteen_alv.tilino_eu, '') != '',         tuotteen_alv.tilino_eu,         if({$toimpriolisa} ifnull(yhtion_toimipaikat.tilino_eu, '') != '',       yhtion_toimipaikat.tilino_eu,         if(ifnull(tuote.tilino_eu, '') != '',         tuote.tilino_eu,         if(ifnull(asiakas.tilino_eu, '') != '',         asiakas.tilino_eu,         if(ifnull(yhtion_toimipaikat.tilino_eu, '') != '',         yhtion_toimipaikat.tilino_eu,         yhtio.myynti_eu))))) tilino_eu,
          if(ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ifnull(tuotteen_alv.tilino_ei_eu, '') != '',       tuotteen_alv.tilino_ei_eu,         if({$toimpriolisa} ifnull(yhtion_toimipaikat.tilino_ei_eu, '') != '',       yhtion_toimipaikat.tilino_ei_eu,       if(ifnull(tuote.tilino_ei_eu, '') != '',      tuote.tilino_ei_eu,       if(ifnull(asiakas.tilino_ei_eu, '') != '',         asiakas.tilino_ei_eu,       if(ifnull(yhtion_toimipaikat.tilino_ei_eu, '') != '',       yhtion_toimipaikat.tilino_ei_eu,       yhtio.myynti_ei_eu))))) tilino_ei_eu,
          if(ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ifnull(tuotteen_alv.tilino_kaanteinen, '') != '',    tuotteen_alv.tilino_kaanteinen,      if({$toimpriolisa} ifnull(yhtion_toimipaikat.tilino_kaanteinen, '') != '',    yhtion_toimipaikat.tilino_kaanteinen,    if(ifnull(tuote.tilino_kaanteinen, '') != '',    tuote.tilino_kaanteinen,    if(ifnull(asiakas.tilino_kaanteinen, '') != '',      asiakas.tilino_kaanteinen,    if(ifnull(yhtion_toimipaikat.tilino_kaanteinen, '') != '',    yhtion_toimipaikat.tilino_kaanteinen,    yhtio.myynti_kaanteinen))))) tilino_kaanteinen,
          if(ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ifnull(tuotteen_alv.tilino_triang, '') != '',      tuotteen_alv.tilino_triang,        if({$toimpriolisa} ifnull(yhtion_toimipaikat.tilino_triang, '') != '',      yhtion_toimipaikat.tilino_triang,      if(ifnull(tuote.tilino_triang, '') != '',      tuote.tilino_triang,      if(ifnull(asiakas.tilino_triang, '') != '',        asiakas.tilino_triang,      if(ifnull(yhtion_toimipaikat.tilino_triang, '') != '',      yhtion_toimipaikat.tilino_triang,      yhtio.tilino_triang))))) tilino_triang,
          if(ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ifnull(tuotteen_alv.kustp, 0) > 0,             tuotteen_alv.kustp,           if({$toimpriolisa} ifnull(yhtion_toimipaikat.kustp, 0) > 0,           yhtion_toimipaikat.kustp,           if(ifnull(tuote.kustp, 0) > 0,             tuote.kustp,           if(ifnull(asiakas.kustannuspaikka, 0) > 0,         asiakas.kustannuspaikka,     if(ifnull(yhtion_toimipaikat.kustp, 0) > 0,           yhtion_toimipaikat.kustp,           yhtio.myynti_kustp))))) kustp,
          if(ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ifnull(tuotteen_alv.kohde, 0) > 0,             tuotteen_alv.kohde,           if({$toimpriolisa} ifnull(yhtion_toimipaikat.kohde, 0) > 0,           yhtion_toimipaikat.kohde,           if(ifnull(tuote.kohde, 0) > 0,             tuote.kohde,           if(ifnull(asiakas.kohde, 0) > 0,             asiakas.kohde,           if(ifnull(yhtion_toimipaikat.kohde, 0) > 0,           yhtion_toimipaikat.kohde,           yhtio.myynti_kohde))))) kohde,
          if(ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ifnull(tuotteen_alv.projekti, 0) > 0,           tuotteen_alv.projekti,           if({$toimpriolisa} ifnull(yhtion_toimipaikat.projekti, 0) > 0,         yhtion_toimipaikat.projekti,         if(ifnull(tuote.projekti, 0) > 0,           tuote.projekti,         if(ifnull(asiakas.projekti, 0) > 0,           asiakas.projekti,         if(ifnull(yhtion_toimipaikat.projekti, 0) > 0,           yhtion_toimipaikat.projekti,         yhtio.myynti_projekti))))) projekti,
          if(ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ifnull(tuotteen_alv.tilino_marginaali, '') != '',    tuotteen_alv.tilino_marginaali,      if({$toimpriolisa} ifnull(yhtion_toimipaikat.tilino_marginaali, '') != '',   yhtion_toimipaikat.tilino_marginaali,     if(ifnull(tuote.tilino_marginaali, '') != '',     tuote.tilino_marginaali,     if(ifnull(asiakas.tilino_marginaali, '') != '',      asiakas.tilino_marginaali,    if(ifnull(yhtion_toimipaikat.tilino_marginaali, '') != '',    yhtion_toimipaikat.tilino_marginaali,    yhtio.myynti_marginaali))))) myynti_marginaali,
          if(ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ifnull(tuotteen_alv.tilino_osto_marginaali, '') != '',  tuotteen_alv.tilino_osto_marginaali,  if({$toimpriolisa} ifnull(yhtion_toimipaikat.tilino_osto_marginaali, '') != '',yhtion_toimipaikat.tilino_osto_marginaali,   if(ifnull(tuote.tilino_osto_marginaali, '') != '',   tuote.tilino_osto_marginaali,  if(ifnull(asiakas.tilino_osto_marginaali, '') != '',  asiakas.tilino_osto_marginaali,  if(ifnull(yhtion_toimipaikat.tilino_osto_marginaali, '') != '',  yhtion_toimipaikat.tilino_osto_marginaali,  yhtio.osto_marginaali))))) osto_marginaali,
          if(tilausrivi.alv >= 500 and tilausrivi.alv < 600 and tilausrivi.kpl < 0 and tilausrivin_lisatiedot.osto_vai_hyvitys = 'O', 'KYLLA', 'EI') marginaaliostot,
          if(tilausrivi.alv >= 500 and tilausrivi.alv < 600 and tilausrivi.kpl > 0 and tilausrivin_lisatiedot.osto_vai_hyvitys = 'H', 'KYLLA', 'EI') marginaalihyvitys,
          if(ifnull(yhtion_toimipaikat.toim_alv, '') != '', yhtion_toimipaikat.toim_alv, yhtio.alv) alvtilino,
          {$raaka_aine_tiliointi_lisa}
          sum(tilausrivi.rivihinta) summa,
          sum(tilausrivi.rivihinta_valuutassa) summa_valuutassa,
          sum(tilausrivi.kate) kate,
          sum(if(tuote.ei_saldoa = '', tilausrivi.rivihinta - tilausrivi.kate, 0)) varmuutos,
          GROUP_CONCAT(tilausrivi.tunnus SEPARATOR ',') tunnukset,
          GROUP_CONCAT(tilausrivin_lisatiedot.tunnus SEPARATOR ',') tilausrivin_lisatietotunnukset
          FROM tilausrivi
          JOIN lasku on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus)
          LEFT JOIN yhtio ON (yhtio.yhtio = lasku.yhtio)
          LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
          LEFT JOIN tuote ON (tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno)
          LEFT JOIN tuotteen_alv ON (tuotteen_alv.yhtio = tilausrivi.yhtio and tuotteen_alv.tuoteno = tilausrivi.tuoteno and tuotteen_alv.maa = '$lasku[maa]' and tuotteen_alv.maa != '')
          LEFT JOIN yhtion_toimipaikat ON (yhtion_toimipaikat.yhtio = tilausrivi.yhtio and yhtion_toimipaikat.tunnus = '$lasku[yhtio_toimipaikka]')
          LEFT JOIN asiakas ON (tilausrivi.yhtio = asiakas.yhtio and lasku.liitostunnus = asiakas.tunnus)
          WHERE tilausrivi.uusiotunnus = '$lasku[tunnus]'
          AND tilausrivi.tyyppi        = 'L'
          AND tilausrivi.yhtio         = '$kukarow[yhtio]'
          GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14{$raaka_aine_tiliointi_group}";
$xresult = pupe_query($query);

if (mysqli_num_rows($xresult) == 0) {
  $tulos_ulos_tiliointi .= "<font class='message'>".t("VIRHE: Laskulla ei ollut yhtään riviä").": $lasku[tunnus]</font><br><br>\r\n";
}
else {

  $varmuutos = 0;
  $vastasumma = 0;
  $vastasumma_valuutassa = 0;
  $maksimisumma = 0;
  $maksimisumma_kustp = 0;
  $maksimisumma_kohde = 0;
  $maksimisumma_projekti = 0;

  // Tehdaan myynnin kirjaukset
  while ($xrow = mysqli_fetch_assoc($xresult)) {

    $lukko = "";
    $tunnus = $lasku['tunnus'];
    $tapvm = $lasku['tapvm'];
    $loppusumma = $lasku['summa'];
    $loppusumma_valuutassa = $lasku['summa_valuutassa'];
    $tilino = 0;
    $tilino_eu = 0;
    $tilino_ei_eu = 0;
    $tilino_kaanteinen = 0;
    $tilino_triang = 0;
    $onkokaanteinen = ""; // apumuuttuja, että tiedetään onko käänteinen alvillisuus

    // kustannuspaikkaa, kohde ja projekti
    $kustp = $xrow["kustp"];
    $kohde = $xrow["kohde"];
    $projekti = $xrow["projekti"];

    // otetaan suurimman myynnin tiedot
    if ($yhtiorow["kirjanpidon_tarkenteet"] == "K" and (($lasku['summa'] > 0 and $xrow["summa"] > $maksimisumma) or ($lasku['summa'] < 0 and $xrow["summa"] < $maksimisumma))) {
      $maksimisumma = $xrow["summa"];

      $maksimisumma_kustp = $kustp;
      $maksimisumma_kohde = $kohde;
      $maksimisumma_projekti = $projekti;
    }

    // Valitaan myyntitili, jos rivillä on alv yli 500 niin kyseessä on marginaalimyyntiä
    if ($xrow["alv"] >= 500 and $xrow["alv"] < 600 and $xrow["marginaaliostot"] == "EI" and $xrow["marginaalihyvitys"] == "EI") {
      // Valitaan myyntitili, tässä on marginaalimyyntiä
      $selite = t("Marginaaliverolasku/Myynti").": ".$lasku['laskunro'].", ". $lasku['nimi']." ".$lasku['nimitark'];

      // tässä on kaikki marginaali-myyntitilausrivit
      $query = "SELECT *
                FROM tilausrivi
                WHERE yhtio = '$kukarow[yhtio]'
                AND tunnus  IN ($xrow[tunnukset])";
      $marginaali_myyntirivitres = pupe_query($query);

      // lasketaan kaikkien marginaalituotteiden ostohinnnat yhteen
      $marginaali_ostohinta = 0;

      while ($marginaali_myyntirivi = mysqli_fetch_assoc($marginaali_myyntirivitres)) {
        $marginaali_ostohinta += round(sarjanumeron_ostohinta("myyntirivitunnus", $marginaali_myyntirivi["tunnus"], "EIKULULASKUJA") * $marginaali_myyntirivi["kpl"], 6);
      }

      // lasketaan alv-laskennallisesti marginaalituotteille oikea "kate"
      $marginaali_kate = $xrow['summa'] - $marginaali_ostohinta;

      //Tilioidaan veroton osuus marginaalimyynnistä
      $tiliointisumma = $xrow['summa'] - $marginaali_kate;

      //Tähän ei osata parempaa valuuttasummaa keksiä, pitää kääntää kurssilla
      $tiliointisumma_valuutassa = round($tiliointisumma / $lasku['vienti_kurssi'], 2);

      list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($xrow["myynti_marginaali"], $kustp, $kohde, $projekti);

      // Tiliöidään marginaalimyyntitapahtuma
      $query = "INSERT into tiliointi set
                yhtio            = '$kukarow[yhtio]',
                ltunnus          = '$tunnus',
                tilino           = '$xrow[myynti_marginaali]',
                kustp            = '{$kustp_ins}',
                kohde            = '{$kohde_ins}',
                projekti         = '{$projekti_ins}',
                tapvm            = '$tapvm',
                summa            = $tiliointisumma * -1,
                summa_valuutassa = $tiliointisumma_valuutassa * -1,
                valkoodi         = '$lasku[valkoodi]',
                vero             = 0,
                selite           = '$selite',
                lukko            = '1',
                laatija          = '$kukarow[kuka]',
                laadittu         = now()";

      // MUOKKAUS: mysqli_insert_id():
      pupe_query($query, $GLOBALS["link"]);
      $margmyynti = mysqli_insert_id($GLOBALS["link"]);

      // Päivitetään tilausriville tieto myyntitiliöinnistä:
      if (!empty($xrow['tilausrivin_lisatietotunnukset'])) {
        $query = "UPDATE tilausrivin_lisatiedot
                  SET tiliointirivitunnus = '$margmyynti'
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  and tunnus in ({$xrow['tilausrivin_lisatietotunnukset']})
                  and tiliointirivitunnus = 0";
        pupe_query($query);
      }

      $vastasumma += $tiliointisumma;
      $vastasumma_valuutassa += $tiliointisumma_valuutassa;

      // Sitten pitää vielä tilioidä itse marginaali joka on normaalia verollista myyntiä
      $selite = t("Marginaaliverolasku/Marginaali", $yhtiorow["kieli"]).": ".$lasku['laskunro'].", ". $lasku['nimi']." ".$lasku['nimitark'];

      $tilino = (int) $xrow['tilino'];
      $tilino_eu = (int) $xrow['tilino_eu'];
      $tilino_ei_eu = (int) $xrow['tilino_ei_eu'];
      $tilino_kaanteinen = (int) $xrow['tilino_kaanteinen'];
      $tilino_triang = (int) $xrow['tilino_triang'];

      $tiliointisumma = $marginaali_kate;
      $tiliointisumma_valuutassa = round($marginaali_kate / $lasku['vienti_kurssi'], 2); //Myynnin marginaali valuutassa (paras arvaus on kääntää kurssilla)

      $lukko = "1";
      $xrow['alv']   = $xrow['alv'] - 500; //Poistetaan 500 yksikköä alvista

      $alv = round($tiliointisumma - ($tiliointisumma / (1 + $xrow['alv'] / 100)), 2);
      $alv_valuutassa = round($tiliointisumma_valuutassa - ($tiliointisumma_valuutassa / (1 + $xrow['alv'] / 100)), 2);

      $tiliointisumma -= $alv;
      $tiliointisumma_valuutassa -= $alv_valuutassa;
    }
    elseif ($xrow["alv"] >= 500 and $xrow["alv"] < 600 and ($xrow["marginaaliostot"] == "KYLLA" or $xrow["marginaalihyvitys"] == "KYLLA")) {
      // Valitaan ostotili, tässä on marginaaliostoa
      $selite = t("Marginaaliverolasku/Osto", $yhtiorow["kieli"]).": ".$lasku['laskunro'].", ". $lasku['nimi']." ".$lasku['nimitark'];

      $tilino = (int) $xrow['osto_marginaali'];
      $tilino_eu = (int) $xrow['osto_marginaali'];
      $tilino_ei_eu = (int) $xrow['osto_marginaali'];
      $tilino_kaanteinen = (int) $xrow['osto_marginaali'];
      $tilino_triang = (int) $xrow['osto_marginaali'];

      $tiliointisumma = $xrow['summa'];
      $tiliointisumma_valuutassa = $xrow['summa_valuutassa'];
      $xrow["varmuutos"]       = $xrow["summa"];
      $xrow["alv"]         = 0;
      $alv = 0;
      $alv_valuutassa = 0;
    }
    else {
      // Valitaan myyntitili, tässä on normaalimyyntiä
      $selite = t("Lasku", $yhtiorow["kieli"]).": ".$lasku['laskunro'].", ". $lasku['nimi']." ".$lasku['nimitark'];

      $tilino = (int) $xrow['tilino'];
      $tilino_eu = (int) $xrow['tilino_eu'];
      $tilino_ei_eu = (int) $xrow['tilino_ei_eu'];
      $tilino_kaanteinen = (int) $xrow['tilino_kaanteinen'];
      $tilino_triang = (int) $xrow['tilino_triang'];

      $tiliointisumma = $xrow['summa'];
      $tiliointisumma_valuutassa = $xrow['summa_valuutassa'];

      // Käänteinen verotus, poistetaan 600 alvista
      if ($xrow['alv'] >= 600) {
        $xrow['alv'] = 0;
        $onkokaanteinen = "kylla";
      }

      $alv = round($xrow['alv'] / 100 * $tiliointisumma, 2);
      $alv_valuutassa = round($xrow['alv'] / 100 * $tiliointisumma_valuutassa, 2);
    }

    // Valitaan myyntitili vienin mukaan
    if ($lasku['vienti'] == 'E') $tilino = $tilino_eu;
    if ($lasku['vienti'] == 'K') $tilino = $tilino_ei_eu;

    // Myydäänkö verovelvolliselta ulkomaan varastotoimipaikalta verottomana "kotimaanmyyntinä"?
    if ($lasku['vienti'] == '' and $lasku['alv'] == 0 and $lasku['yhtio_toimipaikka'] > 0) {
      $query = "SELECT tunnus
                FROM yhtion_toimipaikat
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus = $lasku[yhtio_toimipaikka]
                and vat_numero != ''
                and varastotoimipaikka != ''";
      $tptsekres = pupe_query($query);
      
      if (mysqli_num_rows($tptsekres)) {
        $tilino = $tilino_eu;
      }
    }

    // Valitaan myyntitili käänteisen verotuksen mukaan
    if ($onkokaanteinen == "kylla") {
      $tilino = $tilino_kaanteinen;
    }

    // Katsotaan onko kyseessä kolmikauppa
    if ($lasku['kolmikantakauppa'] != "" and $tilino_triang != 0) {
      $tilino = $tilino_triang;
    }

    // Tiliöidään myynti
    if ($tiliointisumma != 0) {

      $tiliointisumma = round($tiliointisumma, 2);
      $tiliointisumma_valuutassa = round($tiliointisumma_valuutassa, 2);

      list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($tilino, $kustp, $kohde, $projekti);

      $query = "INSERT into tiliointi set
                yhtio            = '$kukarow[yhtio]',
                ltunnus          = '$tunnus',
                tilino           = '$tilino',
                kustp            = '{$kustp_ins}',
                kohde            = '{$kohde_ins}',
                projekti         = '{$projekti_ins}',
                tapvm            = '$tapvm',
                summa            = $tiliointisumma * -1,
                summa_valuutassa = $tiliointisumma_valuutassa * -1,
                valkoodi         = '$lasku[valkoodi]',
                vero             = '$xrow[alv]',
                selite           = '$selite',
                lukko            = '$lukko',
                laatija          = '$kukarow[kuka]',
                laadittu         = now()";
      
      // MUOKKAUS: mysqli_insert_id():
      pupe_query($query, $GLOBALS["link"]);
      $isa = mysqli_insert_id($GLOBALS["link"]);

      // Päivitetään tilausriville tieto myyntitiliöinnistä:
      if (!empty($xrow['tilausrivin_lisatietotunnukset'])) {
        $query = "UPDATE tilausrivin_lisatiedot
                  SET tiliointirivitunnus = '$isa'
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  and tunnus in ({$xrow['tilausrivin_lisatietotunnukset']})
                  and tiliointirivitunnus = 0";
        pupe_query($query);
      }

      $lukko = "";

      // Tiliöidaan ALV, jos sitä on
      if ($xrow['alv'] > 0) {
        $query = "INSERT into tiliointi set
                  yhtio            = '$kukarow[yhtio]',
                  ltunnus          = '$tunnus',
                  tilino           = '$xrow[alvtilino]',
                  kustp            = 0,
                  kohde            = 0,
                  projekti         = 0,
                  tapvm            = '$tapvm',
                  summa            = $alv * -1,
                  summa_valuutassa = $alv_valuutassa * -1,
                  valkoodi         = '$lasku[valkoodi]',
                  vero             = 0,
                  selite           = '$selite',
                  lukko            = '1',
                  laatija          = '$kukarow[kuka]',
                  laadittu         = now(),
                  aputunnus        = '$isa'";
        pupe_query($query);
      }

      // Jos maksetaan käteisellä ja meillä on kassa-alennusta, niin kirjataan ne tässä (samalle kustannuspaikalle ku myynti)
      if ($mehtorow['kateinen'] != '' and $lasku['kasumma'] != 0) {

        // Kuinka paljon on tämän viennin osuus
        $kassaale = round($tiliointisumma*$mehtorow['kassa_alepros']/100, 2);
        $kassaale_valuutassa = round($tiliointisumma_valuutassa*$mehtorow['kassa_alepros']/100, 2);

        $query = "INSERT into tiliointi set
                  yhtio            = '$kukarow[yhtio]',
                  ltunnus          = '$tunnus',
                  tilino           = '$yhtiorow[myynninkassaale]',
                  kustp            = '{$kustp_ins}',
                  kohde            = '{$kohde_ins}',
                  projekti         = '{$projekti_ins}',
                  tapvm            = '$tapvm',
                  summa            = $kassaale,
                  summa_valuutassa = $kassaale_valuutassa,
                  valkoodi         = '$lasku[valkoodi]',
                  vero             = '$xrow[alv]',
                  selite           = '$selite',
                  lukko            = '$lukko',
                  laatija          = '$kukarow[kuka]',
                  laadittu         = now()";
        
        // MUOKKAUS: mysqli_insert_id():
        pupe_query($query, $GLOBALS["link"]);
        $isa = mysqli_insert_id($GLOBALS["link"]);

        if ($xrow["alv"] != 0) {

          //$alv:ssa on alennuksen alv:n maara
          $alv = round($kassaale - $kassaale / (1 + ($xrow["alv"] / 100)), 2);
          $alv_valuutassa = round($kassaale_valuutassa - $kassaale_valuutassa / (1 + ($xrow["alv"] / 100)), 2);

          //$summa on alviton alennus
          $summa -= $alv;
          $summa_valuutassa -= $alv_valuutassa;

          $query = "INSERT into tiliointi set
                    yhtio            = '$kukarow[yhtio]',
                    ltunnus          = '$lasku[tunnus]',
                    tilino           = '$xrow[alvtilino]',
                    kustp            = 0,
                    kohde            = 0,
                    projekti         = 0,
                    tapvm            = '$suoritus[maksupvm]',
                    summa            = $alv,
                    summa_valuutassa = $alv_valuutassa,
                    valkoodi         = '$tiliointirow[valkoodi]',
                    vero             = 0,
                    selite           = '$selite',
                    lukko            = '1',
                    laatija          = '$kukarow[kuka]',
                    laadittu         = now(),
                    aputunnus        = $isa";
          pupe_query($query);
        }
      }
    }

    //Lasketaan vastasummaa jotta voidaan tutkia pyöristyseroa
    $vastasumma += $tiliointisumma + $alv;
    $vastasumma_valuutassa += $tiliointisumma_valuutassa + $alv_valuutassa;

    //Lasketaan varastonmuutos
    $varmuutos = 0; // MUOKKAUS: poistettu kaytosta
    //$varmuutos = $xrow["varmuutos"];
    //$varmuutos_valuutassa = round($xrow["varmuutos"] / $lasku['vienti_kurssi'], 2); // ei osata paremmin, pitää kääntää kurssilla

    // Kirjataan varastonmuutos, jos sita on
    if ($varmuutos != 0) {

      if ($xrow["marginaaliostot"] == "KYLLA" or $xrow["marginaalihyvitys"] == "KYLLA") {
        $selite = t("Varastoontulo", $yhtiorow["kieli"]).": ".$lasku['laskunro'].", ". $lasku['nimi'];
      }
      else {
        $selite = t("Varastostamyynti", $yhtiorow["kieli"]).": ".$lasku['laskunro'].", ". $lasku['nimi'];
      }

      $vero = 0;
      $tiliointisumma = $varmuutos * -1;
      $tiliointisumma_valuutassa = $varmuutos_valuutassa * -1;

      // Tiliöidään ensisijaisesti varastonmuutos tilin oletuskustannuspaikalle
      list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yhtiorow["varastonmuutos"], $kustp, $kohde, $projekti);

      // Toissijaisesti kokeillaan vielä varasto-tilin oletuskustannuspaikkaa
      list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yhtiorow["varasto"], $kustp_ins, $kohde_ins, $projekti_ins);

      if ($yhtiorow["raaka_aine_tiliointi"] == "Y" and
        $xrow["tuotetyyppi"] == "raaka-aine") {
        $tilinumero = $yhtiorow["raaka_ainevarasto"];
        $muutostili = $yhtiorow["raaka_ainevarastonmuutos"];
      }
      else {
        $tilinumero = $yhtiorow["varasto"];
        $muutostili = $yhtiorow["varastonmuutos"];
      }

      //Kirjataan 'varasto'-tilille
      $query = "INSERT into tiliointi set
                yhtio            = '$kukarow[yhtio]',
                ltunnus          = '$tunnus',
                tilino           = '{$tilinumero}',
                kustp            = '{$kustp_ins}',
                kohde            = '{$kohde_ins}',
                projekti         = '{$projekti_ins}',
                tapvm            = '$tapvm',
                summa            = '$tiliointisumma',
                summa_valuutassa = '$tiliointisumma_valuutassa',
                valkoodi         = '$lasku[valkoodi]',
                vero             = '$vero',
                selite           = '$selite',
                lukko            = '',
                laatija          = '$kukarow[kuka]',
                laadittu         = now()";
      pupe_query($query);

      $tiliointisumma = $tiliointisumma * -1;
      $tiliointisumma_valuutassa = $tiliointisumma_valuutassa * -1;

      //Kirjataan 'varastonmuutos'-tilille
      $query = "INSERT into tiliointi set
                yhtio            = '$kukarow[yhtio]',
                ltunnus          = '$tunnus',
                tilino           = '{$muutostili}',
                kustp            = '{$kustp_ins}',
                kohde            = '{$kohde_ins}',
                projekti         = '{$projekti_ins}',
                tapvm            = '$tapvm',
                summa            = '$tiliointisumma',
                summa_valuutassa = '$tiliointisumma_valuutassa',
                valkoodi         = '$lasku[valkoodi]',
                vero             = '$vero',
                selite           = '$selite',
                lukko            = '',
                laatija          = '$kukarow[kuka]',
                laadittu         = now()";
      pupe_query($query);
    }
  }

  // Otetaan loppusumma myös apusumma muuttujaan
  $apusumma = $loppusumma;
  $apusumma_valuutassa = $loppusumma_valuutassa;

  // Kirjataan myyntisaamiset tai kassa
  if ($mehtorow['kateinen'] != '') {

    $query = "SELECT *
              FROM kassalipas
              WHERE yhtio = '$kukarow[yhtio]'
              and tunnus  = '$lasku[kassalipas]'";
    $kateisresult = pupe_query($query);
    $kateisrow = mysqli_fetch_assoc($kateisresult);

    $myysaatili = "";

    $myysaa_kateinen = array();
    $myysaa_kateinen['laskulle'] = $yhtiorow['myyntisaamiset'];

    if (mysqli_num_rows($kateisresult) == 1) {
      $myysaa_kateinen['kateinen']     = $kateisrow['kassa'];
      $myysaa_kateinen['pankkikortti']   = $kateisrow['pankkikortti'];
      $myysaa_kateinen['luottokortti']   = $kateisrow['luottokortti'];
    }
    else {
      $myysaa_kateinen['kateinen']     = $yhtiorow['kassa'];
      $myysaa_kateinen['pankkikortti']   = $yhtiorow['pankkikortti'];
      $myysaa_kateinen['luottokortti']   = $yhtiorow['luottokortti'];
    }

    if ($mehtorow['kateinen'] == "n") {
      if ($kateisrow["pankkikortti"] != "") {
        $myysaatili = $kateisrow["pankkikortti"];
      }
      else {
        $myysaatili = $yhtiorow['pankkikortti'];
      }
    }

    if ($mehtorow['kateinen'] == "o") {
      if ($kateisrow["luottokortti"] != "") {
        $myysaatili = $kateisrow["luottokortti"];
      }
      else {
        $myysaatili = $yhtiorow['luottokortti'];
      }
    }

    if ($myysaatili == "") {
      if ($kateisrow["kassa"] != "") {
        $myysaatili = $kateisrow["kassa"];
      }
      else {
        $myysaatili = $yhtiorow['kassa'];
      }
    }

    // Jos maksetaan käteisellä ja meillä on kassa-alennusta
    if ($lasku['kasumma'] != '') {
      // vähennetään loppusummasta kassa-ale
      $apusumma = $loppusumma - $lasku['kasumma'];
      $apusumma_valuutassa = $loppusumma_valuutassa - $lasku['kasumma_valuutassa'];
    }
  }
  elseif (isset($mehtorow["factoring_id"])) {
    $myysaatili = $yhtiorow['factoringsaamiset'];
  }
  elseif ($konsrow["konserniyhtio"] != "") {
    $myysaatili = $yhtiorow['konsernimyyntisaamiset'];
  }
  else {
    $myysaatili = $yhtiorow['myyntisaamiset'];
  }

  $selite = t("Lasku", $yhtiorow["kieli"]).": ".$lasku['laskunro'].", ". $lasku['nimi']." ".$lasku['nimitark'];

  if (isset($kateismaksu)) {

    $loppusumma = 0;
    $loppusumma_valuutassa = 0;

    foreach ($kateismaksu as $tili => $arvo) {
      $arvo = str_replace(",", ".", $arvo);

      if ((float) $arvo != 0) {

        // jos meillä on valuuttalasku nämä arvot ovat valuutassa
        if ($lasku["valkoodi"] != $yhtiorow["valkoodi"]) {
          $arvo_valuutassa = $arvo;
          $arvo = round($arvo * $lasku['vienti_kurssi'], 2);
        }
        else {
          $arvo_valuutassa = round($arvo / $lasku["vienti_kurssi"], 2);
        }

        list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($myysaa_kateinen[$tili], $maksimisumma_kustp, $maksimisumma_kohde, $maksimisumma_projekti);

        $query = "INSERT into tiliointi set
                  yhtio            = '$kukarow[yhtio]',
                  ltunnus          = '$tunnus',
                  tilino           = '$myysaa_kateinen[$tili]',
                  kustp            = '{$kustp_ins}',
                  kohde            = '{$kohde_ins}',
                  projekti         = '{$projekti_ins}',
                  tapvm            = '$tapvm',
                  summa            = '$arvo',
                  summa_valuutassa = '$arvo_valuutassa',
                  valkoodi         = '$lasku[valkoodi]',
                  vero             = 0,
                  selite           = '$selite',
                  lukko            = '1',
                  laatija          = '$kukarow[kuka]',
                  laadittu         = now()";
        pupe_query($query);

        $loppusumma += $arvo;
        $loppusumma_valuutassa += $arvo_valuutassa;

        // Päivitetään laskulle
        if ($tili == "laskulle" and $yhtiorow['sallitaanko_kateismyynti_laskulle'] != '') {
          // Päivitetään myyntilaskulle "popvm"-jotta me saadaan osittain käteisellä maksetut laskut
          // näkymään käteismyynnit-raportissa oikealla päivällä vaikka mapvm on nolla
          $query = "UPDATE lasku
                    SET saldo_maksettu = (summa - $arvo),
                    saldo_maksettu_valuutassa = (summa_valuutassa  - $arvo_valuutassa),
                    mapvm                     = 0,
                    popvm                     = '$tapvm 00:00:00'
                    WHERE tunnus              = '$lasku[tunnus]'
                    AND yhtio                 = '$kukarow[yhtio]'";
          pupe_query($query);
        }
      }
    }
  }
  else {
    list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($myysaatili, $maksimisumma_kustp, $maksimisumma_kohde, $maksimisumma_projekti);

    $query = "INSERT into tiliointi set
              yhtio            = '$kukarow[yhtio]',
              ltunnus          = '$tunnus',
              tilino           = '$myysaatili',
              kustp            = '{$kustp_ins}',
              kohde            = '{$kohde_ins}',
              projekti         = '{$projekti_ins}',
              tapvm            = '$tapvm',
              summa            = '$apusumma',
              summa_valuutassa = '$apusumma_valuutassa',
              valkoodi         = '$lasku[valkoodi]',
              vero             = 0,
              selite           = '$selite',
              lukko            = '1',
              laatija          = '$kukarow[kuka]',
              laadittu         = now()";
    pupe_query($query);
  }

  // Kirjataan pyoristykset, jos niita on
  $vastasumma = round($vastasumma, 2);
  $vastasumma_valuutassa = round($vastasumma_valuutassa, 2);

  list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yhtiorow["pyoristys"], $maksimisumma_kustp, $maksimisumma_kohde, $maksimisumma_projekti);

  if ($vastasumma != $loppusumma or ($lasku['valkoodi'] != $yhtiorow['valkoodi'] and $vastasumma_valuutassa != $loppusumma_valuutassa)) {
    $query = "INSERT into tiliointi set
              yhtio            = '$kukarow[yhtio]',
              ltunnus          = '$tunnus',
              tilino           = '$yhtiorow[pyoristys]',
              kustp            = '{$kustp_ins}',
              kohde            = '{$kohde_ins}',
              projekti         = '{$projekti_ins}',
              tapvm            = '$tapvm',
              summa            = $vastasumma - $loppusumma,
              summa_valuutassa = $vastasumma_valuutassa - $loppusumma_valuutassa,
              valkoodi         = '$lasku[valkoodi]',
              vero             = 0,
              selite           = '$selite',
              lukko            = '',
              laatija          = '$kukarow[kuka]',
              laadittu         = now()";
    pupe_query($query);
  }

  $vastasumma = 0;
  $varmuutos = 0;

} // yhtään tilausriviä ei löytyny else-haara

//echotaan rudulle jos kyseessä ei ole batch-ajo
if ($tulos_ulos == "" and $silent == "") {
  echo $tulos_ulos_tiliointi;
}
