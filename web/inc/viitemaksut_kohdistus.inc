<?php

//#####################################
//
// automaattikohdistetaan viitemaksut
//
//#####################################

// Oletuksena asiakkaan maksama summa on oltava täsmälleen oikein
$Viitemaksujen_kohdistus_sallittu_heitto = 0.01;

if ((float)$yhtiorow["viitemaksujen_kohdistus_sallittu_heitto"] > 0 and (float)$yhtiorow["viitemaksujen_kohdistus_sallittu_heitto"] <= 1.00) {
  $Viitemaksujen_kohdistus_sallittu_heitto = (float)$yhtiorow["viitemaksujen_kohdistus_sallittu_heitto"];
}

$maksusopparit = array();

echo "<font class='message'>".t("Automaattikohdistetaan viitemaksuja").": $yhtiorow[nimi]</font><br>\n";

$query = "SELECT
          suoritus.tunnus suoritunnus,
          suoritus.yhtio suoriyhtio,
          suoritus.kirjpvm kirjpvm,
          suoritus.summa suoritettu,
          suoritus.kurssi suorituskurssi,
          suoritus.tilino stilino,
          suoritus.valkoodi suoritusvaluutta,
          lasku.summa-lasku.saldo_maksettu laskutettu,
          lasku.summa_valuutassa-lasku.saldo_maksettu_valuutassa laskutettu_valuutassa,
          lasku.tunnus laskutunnus,
          lasku.tapvm tapvm,
          lasku.summa laskusumma,
          lasku.summa_valuutassa laskusumma_valuutassa,
          lasku.kapvm kapvm,
          lasku.saldo_maksettu saldo_maksettu,
          lasku.saldo_maksettu_valuutassa saldo_maksettu_valuutassa,
          lasku.kasumma kasumma,
          lasku.kasumma_valuutassa kasumma_valuutassa,
          lasku.valkoodi laskuvaluutta,
          lasku.vienti_kurssi laskukurssi,
          lasku.jaksotettu,
          yhtio.myynninkassaale alennustili,
          yhtio.myyntisaamiset myyntisaamiset,
          yhtio.konsernimyyntisaamiset konsernimyyntisaamiset,
          yhtio.factoringsaamiset factoringsaamiset,
          yhtio.alv alvtili,
          yhtio.varasto varasto,
          yhtio.varastonmuutos varastomuu,
          yhtio.pyoristys pyoristys,
          yhtio.valkoodi yhtiovaluutta,
          yhtio.myynninvaluuttaero valuuttaerotili,
          yhtio.selvittelytili,
          yriti.factoring factoring,
          yriti.oletus_rahatili kassatili,
          yriti.oletus_selvittelytili,
          factoring.pankki_tili ftilino,
          (SELECT count(*) FROM tiliointi WHERE tiliointi.yhtio = suoritus.yhtio AND tiliointi.ltunnus = lasku.tunnus AND tiliointi.tilino in (yhtio.factoringsaamiset, yhtio.myyntisaamiset, yhtio.konsernimyyntisaamiset)) saamisetkpl
          FROM yhtio
          JOIN lasku use index (yhtio_tila_mapvm) ON (lasku.yhtio = yhtio.yhtio and lasku.tila = 'U' and lasku.alatila = 'X' and lasku.mapvm = '0000-00-00')
          JOIN yriti ON (yriti.yhtio = yhtio.yhtio)
          JOIN suoritus use index (yhtio_viite) ON (suoritus.yhtio = lasku.yhtio and suoritus.viite = lasku.viite and suoritus.valkoodi = lasku.valkoodi and suoritus.tilino = yriti.tilino and suoritus.kohdpvm = '0000-00-00' and suoritus.ltunnus = 0)
          JOIN maksuehto ON (maksuehto.yhtio = lasku.yhtio and maksuehto.tunnus = lasku.maksuehto)
          LEFT JOIN factoring ON (factoring.yhtio = lasku.yhtio and factoring.valkoodi = lasku.valkoodi and factoring.tunnus = maksuehto.factoring_id)
          WHERE yhtio.yhtio = '$kukarow[yhtio]'
          AND ((yriti.factoring!='' and maksuehto.factoring_id is not null) or (yriti.factoring='' and maksuehto.factoring_id is null))
          HAVING saamisetkpl > 0 and (
          (laskuvaluutta  = yhtiovaluutta and (((kapvm >= adddate(kirjpvm,-4) and abs(laskusumma-saldo_maksettu-kasumma-suoritettu)                              < $Viitemaksujen_kohdistus_sallittu_heitto)) or (abs(laskusumma-saldo_maksettu-suoritettu)                       < $Viitemaksujen_kohdistus_sallittu_heitto))) or
          (laskuvaluutta != yhtiovaluutta and (((kapvm >= adddate(kirjpvm,-4) and abs(laskusumma_valuutassa-saldo_maksettu_valuutassa-kasumma_valuutassa-suoritettu) < $Viitemaksujen_kohdistus_sallittu_heitto)) or (abs(laskusumma_valuutassa-saldo_maksettu_valuutassa-suoritettu) < $Viitemaksujen_kohdistus_sallittu_heitto))) or
          (laskuvaluutta  = yhtiovaluutta and (((kapvm >= adddate(kirjpvm,-4) and abs(laskusumma-saldo_maksettu-kasumma-suoritettu)                                  < $Viitemaksujen_kohdistus_sallittu_heitto)) or (abs(laskusumma-saldo_maksettu-suoritettu)                       < $Viitemaksujen_kohdistus_sallittu_heitto))) or
          (laskuvaluutta != yhtiovaluutta and (((kapvm >= adddate(kirjpvm,-4) and abs(laskusumma_valuutassa-saldo_maksettu_valuutassa-kasumma_valuutassa-suoritettu) < $Viitemaksujen_kohdistus_sallittu_heitto)) or (abs(laskusumma_valuutassa-saldo_maksettu_valuutassa-suoritettu) < $Viitemaksujen_kohdistus_sallittu_heitto))))";
$suorires = pupe_query($query);

while ($row = mysqli_fetch_assoc($suorires)) {

  // Setataan kukarow-yhtiö
  $kukarow["yhtio"] = $row["suoriyhtio"];

  if (mb_strtoupper($row["suoritusvaluutta"]) != mb_strtoupper($row['yhtiovaluutta'])) {
    $alennus = round(((float)$row["laskutettu_valuutassa"] - (float)$row["suoritettu"]) * (float)$row["suorituskurssi"], 2);
    $alennus_valuutassa = round((float)$row["laskutettu_valuutassa"] - (float)$row["suoritettu"], 2);
  }
  else {
    $alennus = round((float)$row["laskutettu"] - (float)$row["suoritettu"], 2);
    $alennus_valuutassa = round((float)$row["laskutettu"] - (float)$row["suoritettu"], 2);
  }

  // Katsotaan ensin, ettei tätä laskua ole jo suoritettu/maksettu
  // Eli keissi jossa asiakas maksaa saman laskun kahteen kertaan samassa viiteaineistossa
  $query = "SELECT tunnus, liitostunnus, maksuehto
            FROM lasku
            WHERE yhtio = '$kukarow[yhtio]'
            and tunnus  = '$row[laskutunnus]'
            and mapvm   = '0000-00-00'";
  $lasresult = pupe_query($query);

  // Katsotaan ensin, ettei tätä suoritusta ei oo vielä käytetty
  // Eli keissi jossa asiakkaalla on useampi lasku samalla summa/viitteellä auki reskontrassa
  $query = "SELECT tunnus
            FROM suoritus
            WHERE yhtio = '$kukarow[yhtio]'
            and tunnus  = '$row[suoritunnus]'
            and kohdpvm = '0000-00-00'
            and ltunnus = 0";
  $surresult = pupe_query($query);

  if (mysqli_num_rows($lasresult) == 1 and mysqli_num_rows($surresult) == 1 and ($row["ftilino"] == $row["stilino"] or $row["ftilino"] == "")) {
    $malaskurow = mysqli_fetch_assoc($lasresult);

    // Etsitään asiakas, jos se olisi konsernin jäsen
    $query = "SELECT konserniyhtio
              FROM asiakas
              WHERE yhtio        = '$kukarow[yhtio]'
              and tunnus         = '$malaskurow[liitostunnus]'
              and konserniyhtio != ''";
    $asresult = pupe_query($query);

    if (mysqli_num_rows($asresult) > 0) {
      $row["myyntisaamiset"] = $row["konsernimyyntisaamiset"];
    }

    // Onko tämä sittenkin factoringia
    if ($row["factoring"] != "") {
      $row["myyntisaamiset"] = $row["factoringsaamiset"];
    }

    // Haetaan myyntisaamistiliöinnin kustannuspaikka
    $query = "SELECT kustp, kohde, projekti
              FROM tiliointi
              WHERE yhtio  = '$kukarow[yhtio]'
              AND ltunnus  = '$row[laskutunnus]'
              AND tilino   = '$row[myyntisaamiset]'
              AND korjattu = ''";
    $asresult = pupe_query($query);
    $mskustprow = mysqli_fetch_assoc($asresult);

    // Tarkenteet kopsataan alkuperäiseltä tiliöinniltä, mutta jos alkuperäinen tiliöinti on ilman tarkenteita, niin mennään tilin defaulteilla
    list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($row["myyntisaamiset"], $mskustprow["kustp"], $mskustprow["kohde"], $mskustprow["projekti"]);

    // Myyntisaamiset
    $query = "INSERT INTO tiliointi SET
              yhtio            = '$kukarow[yhtio]',
              laatija          = 'automaatti',
              laadittu         = now(),
              tapvm            = '$row[kirjpvm]',
              ltunnus          = '$row[laskutunnus]',
              tilino           = '$row[myyntisaamiset]',
              kustp            = '{$kustp_ins}',
              kohde            = '{$kohde_ins}',
              projekti         = '{$projekti_ins}',
              summa            = -$row[laskutettu],
              summa_valuutassa = -$row[laskutettu_valuutassa],
              valkoodi         = '$row[laskuvaluutta]',
              selite           = 'Automaattikohdistettu asiakkaan suoritus'";
    pupe_query($query);

    if (mb_strtoupper($row["suoritusvaluutta"]) != mb_strtoupper($row['yhtiovaluutta'])) {
      $suoritettu_kassaan = round((float)$row["suoritettu"] * (float)$row["suorituskurssi"], 2);
      $suoritettu_kassaan_valuutassa = (float)$row["suoritettu"];
    }
    else {
      $suoritettu_kassaan = (float)$row["suoritettu"];
      $suoritettu_kassaan_valuutassa = (float)$row["suoritettu"];
    }

    list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($row["kassatili"]);

    // Kassatili
    $query = "INSERT INTO tiliointi SET
              yhtio            = '$kukarow[yhtio]',
              laatija          = 'automaatti',
              laadittu         = now(),
              tapvm            = '$row[kirjpvm]',
              ltunnus          = '$row[laskutunnus]',
              tilino           = '$row[kassatili]',
              kustp            = '{$kustp_ins}',
              kohde            = '{$kohde_ins}',
              projekti         = '{$projekti_ins}',
              summa            = $suoritettu_kassaan,
              summa_valuutassa = '$suoritettu_kassaan_valuutassa',
              valkoodi         = '$row[suoritusvaluutta]',
              selite           = 'Automaattikohdistettu asiakkaan suoritus'";
    pupe_query($query);

    // Mahdollinen kassa-alennus
    if ($alennus != 0) {

      // Kassa-alessa on huomioitava alv, joka voi olla useita vientejä
      $totkasumma = 0;
      $totkasumma_valuutassa = 0;

      // Etsitään myynti-tiliöinnit
      $query = "SELECT tiliointi.summa, tiliointi.vero, tiliointi.kustp, tiliointi.kohde, tiliointi.projekti, tiliointi.summa_valuutassa, tiliointi.valkoodi
                FROM tiliointi use index (tositerivit_index)
                JOIN tili ON (tiliointi.yhtio = tili.yhtio and tiliointi.tilino = tili.tilino)
                LEFT JOIN taso ON (tili.yhtio = taso.yhtio and tili.ulkoinen_taso = taso.taso and taso.tyyppi = 'U')
                WHERE tiliointi.yhtio  = '$kukarow[yhtio]'
                AND tiliointi.ltunnus  = '$row[laskutunnus]'
                AND tiliointi.tapvm    = '$row[tapvm]'
                AND abs(tiliointi.summa) <> 0
                AND tiliointi.tilino   not in ('$row[myyntisaamiset]','$row[konsernimyyntisaamiset]','$row[alvtili]','$row[varasto]','$row[varastomuu]','$row[pyoristys]','$row[alennustili]','$row[factoringsaamiset]')
                AND tiliointi.korjattu = ''
                AND (taso.kayttotarkoitus is null or taso.kayttotarkoitus  in ('','M'))";
      $tilres = pupe_query($query);

      if (mysqli_num_rows($tilres) == 0) {

        list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($row["alennustili"]);

        $query = "INSERT INTO tiliointi
                  SET yhtio      = '$kukarow[yhtio]',
                  laatija          = 'automaatti',
                  laadittu         = now(),
                  tapvm            = '$row[kirjpvm]',
                  ltunnus          = '$row[laskutunnus]',
                  tilino           = '$row[alennustili]',
                  kustp            = '{$kustp_ins}',
                  kohde            = '{$kohde_ins}',
                  projekti         = '{$projekti_ins}',
                  summa            = '$alennus',
                  summa_valuutassa = '$alennus_valuutassa',
                  valkoodi         = '$row[suoritusvaluutta]',
                  vero             = 0,
                  selite           = 'Automaattikohdistettu asiakkaan suoritus (alv ongelma)'";
        pupe_query($query);
      }
      else {

        $aputunnus = 0;

        while ($tiliointirow = mysqli_fetch_assoc($tilres)) {
          $alv       = 0;
          $alv_valuutassa = 0;

          $summa         = round((float)$tiliointirow["summa"] * -1 * (1+(float)$tiliointirow["vero"]/100) / (float)$row["laskusumma"] * $alennus, 2);
          $summa_valuutassa = round((float)$tiliointirow["summa_valuutassa"] * -1 * (1+(float)$tiliointirow["vero"]/100) / (float)$row["laskusumma_valuutassa"] * $alennus_valuutassa, 2);

          if ($tiliointirow["vero"] != 0) {
            // Netotetaan alvi
            // $alv:ssa on alennuksen alv:n maara
            $alv = round($summa - $summa / (1 + ((float)$tiliointirow["vero"] / 100)), 2);
            $alv_valuutassa = round($summa_valuutassa - $summa_valuutassa / (1 + ((float)$tiliointirow["vero"]/100)), 2);

            //$summa on alviton alennus
            $summa -= $alv;
            $summa_valuutassa -= $alv_valuutassa;
          }

          // Kuinka paljon olemme kumulatiivisesti tiliöineet
          $totkasumma += $summa + $alv;
          $totkasumma_valuutassa += $summa_valuutassa + $alv_valuutassa;

          // Tarkenteet kopsataan alkuperäiseltä tiliöinniltä, mutta jos alkuperäinen tiliöinti on ilman tarkenteita, niin mennään tilin defaulteilla
          list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($row["alennustili"], $tiliointirow["kustp"], $tiliointirow["kohde"], $tiliointirow["projekti"]);

          // Lisätään myynnin kassa-alennuskirjaus ilman veroa
          $query = "INSERT INTO tiliointi
                    SET yhtio      = '$kukarow[yhtio]',
                    laatija          = 'automaatti',
                    laadittu         = now(),
                    tilino           = '$row[alennustili]',
                    kustp            = '{$kustp_ins}',
                    kohde            = '{$kohde_ins}',
                    projekti         = '{$projekti_ins}',
                    tapvm            = '$row[kirjpvm]',
                    ltunnus          = '$row[laskutunnus]',
                    summa            = '$summa',
                    summa_valuutassa = '$summa_valuutassa',
                    vero             = '$tiliointirow[vero]',
                    valkoodi         = '$row[suoritusvaluutta]',
                    selite           = 'Automaattikohdistettu asiakkaan suoritus (Kassa-Alennus)'";
	  
	  // MUOKKAUS: mysqli_insert_id():
          pupe_query($query, $GLOBALS["link"]);
          $aputunnus = mysqli_insert_id($GLOBALS["link"]);

          // Etsitään korjattava vienti
          if ($alv != 0) {
            //Kirjataan myös kassa-alennuksen arvonlisäverot
            $query = "INSERT into tiliointi
                      SET yhtio       = '$kukarow[yhtio]',
                      ltunnus          = '$row[laskutunnus]',
                      tilino           = '$row[alvtili]',
                      kustp            = 0,
                      kohde            = 0,
                      projekti         = 0,
                      tapvm            = '$row[kirjpvm]',
                      summa            = '$alv',
                      summa_valuutassa = '$alv_valuutassa',
                      valkoodi         = '$row[suoritusvaluutta]',
                      vero             = 0,
                      selite           = 'Automaattikohdistettu asiakkaan suoritus (Kassa-alennuksen alv)',
                      lukko            = '1',
                      laatija          = 'automaatti',
                      laadittu         = now(),
                      aputunnus        = '$aputunnus'";
            pupe_query($query);
          }
        }

        //Hoidetaan mahdolliset pyöristykset
        $heitto = round($totkasumma - $alennus, 2);
        $heitto_valuutassa = round($totkasumma_valuutassa - $alennus_valuutassa, 2);

        if (abs($heitto) >= 0.01) {
          $query = "UPDATE tiliointi SET
                    summa            = summa - $heitto,
                    summa_valuutassa = summa_valuutassa - $heitto_valuutassa
                    WHERE tunnus     = '$aputunnus'
                    and yhtio        = '$kukarow[yhtio]'";
          pupe_query($query);

          $aputunnus = 0;
        }
      }
    }

    // tuliko valuuttaeroa?
    if (mb_strtoupper($row["suoritusvaluutta"]) != mb_strtoupper($row['yhtiovaluutta'])) {

      $valero = round((float)$row["laskutettu"] - $suoritettu_kassaan - $alennus, 2);

      if (round($valero, 2) != 0) {

        $totvesumma = 0;

        // Etsitään myynti-tiliöinnit
        $query = "SELECT tiliointi.summa, tiliointi.vero, tiliointi.kustp, tiliointi.kohde, tiliointi.projekti, tiliointi.summa_valuutassa, tiliointi.valkoodi
                  FROM tiliointi use index (tositerivit_index)
                  JOIN tili ON (tiliointi.yhtio = tili.yhtio and tiliointi.tilino = tili.tilino)
                  LEFT JOIN taso ON (tili.yhtio = taso.yhtio and tili.ulkoinen_taso = taso.taso and taso.tyyppi = 'U')
                  WHERE tiliointi.yhtio  = '$kukarow[yhtio]'
                  AND tiliointi.ltunnus  = '$row[laskutunnus]'
                  AND tiliointi.tapvm    = '$row[tapvm]'
                  AND abs(tiliointi.summa) <> 0
                  AND tiliointi.tilino   not in ('$row[myyntisaamiset]','$row[konsernimyyntisaamiset]','$row[alvtili]','$row[varasto]','$row[varastomuu]','$row[pyoristys]','$row[alennustili]','$row[factoringsaamiset]')
                  AND tiliointi.korjattu = ''
                  AND (taso.kayttotarkoitus is null or taso.kayttotarkoitus  in ('','M'))";
        $tilres = pupe_query($query);

        if (mysqli_num_rows($tilres) == 0) {

          list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($row["valuuttaerotili"]);

          $query = "INSERT INTO tiliointi SET
                    yhtio    = '$kukarow[yhtio]',
                    laatija  = 'automaatti',
                    laadittu = now(),
                    tapvm    = '$row[kirjpvm]',
                    ltunnus  = '$row[laskutunnus]',
                    tilino   = '$row[valuuttaerotili]',
                    kustp    = '{$kustp_ins}',
                    kohde    = '{$kohde_ins}',
                    projekti = '{$projekti_ins}',
                    summa    = $valero,
                    selite   = 'Automaattikohdistettu asiakkaan suoritus'";
          pupe_query($query);
        }
        else {
          while ($tiliointirow = mysqli_fetch_assoc($tilres)) {

            // Kuinka paljon on tämän viennin osuus
            $summa = round((float)$tiliointirow['summa'] * (1+(float)$tiliointirow['vero']/100) / (float)$row["laskutettu"] * $vesumma, 2);

            // Tarkenteet kopsataan alkuperäiseltä tiliöinniltä, mutta jos alkuperäinen tiliöinti on ilman tarkenteita, niin mennään tilin defaulteilla
            list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($row["valuuttaerotili"], $tiliointirow["kustp"], $tiliointirow["kohde"], $tiliointirow["projekti"]);

            $query = "INSERT INTO tiliointi SET
                      yhtio    = '$kukarow[yhtio]',
                      laatija  = 'automaatti',
                      laadittu = now(),
                      tapvm    = '$row[kirjpvm]',
                      ltunnus  = '$row[laskutunnus]',
                      tilino   = '$row[valuuttaerotili]',
                      kustp    = '{$kustp_ins}',
                      kohde    = '{$kohde_ins}',
                      projekti = '{$projekti_ins}',
                      summa    = $summa,
                      selite   = 'Automaattikohdistettu asiakkaan suoritus'";
	  
            // MUOKKAUS: mysqli_insert_id():
            pupe_query($query, $GLOBALS["link"]);
            $isa = mysqli_insert_id($GLOBALS["link"]);

            $totvesumma += $summa;
          }

          //Hoidetaan mahdolliset pyöristykset
          if ($totvesumma != $vesumma) {
            $query = "UPDATE tiliointi
                      SET summa = summa - $totvesumma + $vesumma
                      WHERE tunnus = '$isa'
                      and yhtio    = '$kukarow[yhtio]'";
            pupe_query($query);
          }
        }
      }
    }

    // Merkitään lasku maksetuksi
    $query = "UPDATE lasku
              SET mapvm = '$row[kirjpvm]',
              saldo_maksettu = 0,
              saldo_maksettu_valuutassa = 0
              WHERE yhtio = '$kukarow[yhtio]'
              and tunnus  = '$row[laskutunnus]'";
    pupe_query($query);

    // Vapautetaan holdissa olevat tilaukset, jos niillä on maksupositioita ja ennakkolaskut ovat maksettu
    // Holdissa olevat tilaukset ovat tilassa N B
    if ($yhtiorow['maksusopimus_toimitus'] == 'X' and (int)$row["jaksotettu"] < 0) {
      $query = "SELECT tunnus
                FROM lasku
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '".((int)$row['jaksotettu'] * -1)."'
                AND tila    = 'N'
                AND alatila = 'B'";
      $pos_chk_result = pupe_query($query);

      if (mysqli_num_rows($pos_chk_result)) {
        $maksusopparit[] = (int)$row['jaksotettu'] * -1;
      }
    }

    // Ja suoritus kirjatuksi
    $query = "UPDATE suoritus
              SET kohdpvm = '$row[kirjpvm]',
              ltunnus     = '$row[laskutunnus]'
              WHERE yhtio = '$kukarow[yhtio]'
              and tunnus  = '$row[suoritunnus]'";
    pupe_query($query);

    // ja kohdistusrelaatio
    $kohdistus_qry = "INSERT INTO suorituksen_kohdistus SET
                      yhtio          = '{$kukarow['yhtio']}',
                      suoritustunnus = '{$row['suoritunnus']}',
                      laskutunnus    = '{$row['laskutunnus']}',
                      kohdistuspvm   = NOW(),
                      kirjauspvm     = '{$row['tapvm']}'";
    pupe_query($kohdistus_qry);
  }
}

//####################################
//
// siirretään feilanneet laskuiksi...
//
//####################################

echo "<font class='message'>".t("Tehdään kohdistamattomista laskuja").": $yhtiorow[nimi]</font><br>\n";

$query = "SELECT
          suoritus.tunnus tunnus,
          suoritus.yhtio yhtio,
          suoritus.summa summa,
          suoritus.kirjpvm,
          suoritus.valkoodi suoritusvaluutta,
          suoritus.kurssi suorituskurssi,
          suoritus.nimi_maksaja,
          yhtio.myyntisaamiset,
          yhtio.valkoodi yhtiovaluutta,
          yhtio.factoringsaamiset,
          yhtio.selvittelytili,
          yriti.factoring,
          yriti.oletus_rahatili,
          yriti.oletus_selvittelytili
          FROM yhtio
          JOIN yriti ON (yriti.yhtio = yhtio.yhtio)
          JOIN suoritus ON (suoritus.yhtio = yhtio.yhtio AND suoritus.tilino = yriti.tilino AND suoritus.kohdpvm = '0000-00-00' AND suoritus.ltunnus = 0)
          WHERE yhtio.yhtio = '$kukarow[yhtio]'
          ORDER BY yhtio";
$suorires = pupe_query($query);

$laskut = array();

while ($row = mysqli_fetch_assoc($suorires)) {

  if (mb_strtoupper($row["suoritusvaluutta"]) != mb_strtoupper($row['yhtiovaluutta'])) {
    $summa = round((float)$row["summa"] * (float)$row["suorituskurssi"], 2);
    $summa_valuutassa = (float)$row["summa"];
  }
  else {
    $summa = (float)$row["summa"];
    $summa_valuutassa = (float)$row["summa"];
  }

  if (!isset($laskut[$row["yhtio"]])) {
    $query = "INSERT into lasku
              SET yhtio   = '$kukarow[yhtio]',
              tapvm      = now(),
              tila       = 'X',
              laatija    = 'viitesiirrot',
              luontiaika = now()";
	    
    // MUOKKAUS: mysqli_insert_id():
    pupe_query($query, $GLOBALS["link"]);
    $laskut[$row["yhtio"]] = mysqli_insert_id($GLOBALS["link"]);
  }

  if ($laskut[$row["yhtio"]] > 0) {

    // Onko tämä sittenkin factoringia
    if ($row["factoring"] != "") {
      $row["myyntisaamiset"] = $row["factoringsaamiset"];
    }

    // Jos meillä on oletus selvittelytili niin pistetään aina sinne tilille
    if (!empty($row["oletus_selvittelytili"]) and $row["oletus_selvittelytili"] != $row["selvittelytili"]) {
      $row["myyntisaamiset"] = $row["oletus_selvittelytili"];
    }

    $maksaja_espattu = sanitize_string($row["nimi_maksaja"]);

    list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($row["myyntisaamiset"]);

    // Myyntisaamiset tai selvittely
    $query = "INSERT INTO tiliointi SET
              yhtio            = '$kukarow[yhtio]',
              laatija          = 'automaattikohdistus',
              laadittu         = now(),
              tapvm            = '$row[kirjpvm]',
              ltunnus          = '{$laskut[$row["yhtio"]]}',
              tilino           = '$row[myyntisaamiset]',
              kustp            = '{$kustp_ins}',
              kohde            = '{$kohde_ins}',
              projekti         = '{$projekti_ins}',
              summa            = -$summa,
              summa_valuutassa = -$summa_valuutassa,
              valkoodi         = '$row[suoritusvaluutta]',
              selite           = '$maksaja_espattu maksoi viitteellä väärin',
              lukko            = '1'";
    
    // MUOKKAUS: mysqli_insert_id():
    pupe_query($query, $GLOBALS["link"]);
    $tlast_id = mysqli_insert_id($GLOBALS["link"]);

    list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($row["oletus_rahatili"]);

    // Kassatili
    $query = "INSERT INTO tiliointi SET
              yhtio            = '$kukarow[yhtio]',
              laatija          = 'automaattikohdistus',
              laadittu         = now(),
              tapvm            = '$row[kirjpvm]',
              ltunnus          = '{$laskut[$row["yhtio"]]}',
              tilino           = '$row[oletus_rahatili]',
              kustp            = '{$kustp_ins}',
              kohde            = '{$kohde_ins}',
              projekti         = '{$projekti_ins}',
              summa            = $summa,
              summa_valuutassa = $summa_valuutassa,
              valkoodi         = '$row[suoritusvaluutta]',
              selite           = '$maksaja_espattu maksoi viitteellä väärin',
              aputunnus        = '$tlast_id',
              lukko            = '1'";
    pupe_query($query);

    $query = "UPDATE suoritus
              SET ltunnus = '$tlast_id'
              WHERE yhtio = '$kukarow[yhtio]'
              and tunnus  = '$row[tunnus]'";
    pupe_query($query);
  }
  else {
    echo "<font class='error'>VIRHE: Tositteen otsikkoa ei saatu luotua!</font><br>\n";
  }
}

// Vapautetaan holdissa oleva tilaus, jos/kun ennakkolaskut on maksettu
if (count($maksusopparit)) {
  foreach ($maksusopparit as $soppari) {
    vapauta_maksusopimus($soppari);
  }
}
