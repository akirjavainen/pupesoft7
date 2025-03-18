<?php

// Luetaan viitemaksuja tietokannasta...
// Automaattikohdistetaan viitemaksuja...
// Tehdään kohdistamattomista laskuja...

// tietuetunnus on ensimmäinen merkki
// 0 -> erätietue
// 3 -> viitesiirto
// 5 -> suoraveloitus
// 9 -> summatietue
// S -> factoringrivi, alatyyppi positiossa 88 (numerointi alkaa yhdestä)

$type = substr($tietue, 0, 1);
$factoring_oikaisutunnus = substr($tietue, 87, 1);

if ($type == '0') {
  // print "Tietuetunnus => $type\n";
  // print "Aineston luontipv => ".substr($tietue, 1, 6)."\n";
  // print "Aineston luontiaika => ".substr($tietue, 7, 4)."\n";
  // print "Rahalaitostunnus => ".substr($tietue, 11, 2)."\n";
  // print "Laskuttajan palvelutunnus => ".substr($tietue, 13, 9)."\n";
  // print "Rahayksikön koodi => ".substr($tietue, 22, 1)."\n";
  // print "Varalla => ".substr($tietue, 23, 67)."\n";
}
elseif ($type == '3' or ($type == 'S' and
    ($yhtiorow["viitemaksujen_oikaisut"] == 'K' or $factoring_oikaisutunnus == '0'))) {
  // viitesiirto
  // print "Oikaisutunnus => ".substr($tietue, 87, 1)."\n";
  // print "Välitystapa => ".substr($tietue, 88, 1)."\n";
  // print "Palautekoodi => ".substr($tietue, 89, 1)."\n";

  if ($type == 'S') {
    $account = $tiliotedatarow['tilino'];
  }
  else {
    $account = substr($tietue, 1, 14);
  }

  $payer_name = trim(substr($tietue, 63, 12));
  $reference = ltrim(substr($tietue, 43, 20), '0');
  $tietue = trim($tietue);
  $sum = substr($tietue, strrpos($tietue, " ") + 1); // MUOKKAUS: BUGIKORJAUS, haetaan summa strrpos-funktiolla, ei kiintealla (77/78) paikalla
  $sum = (float)preg_replace("/[^0-9]/", "", $sum); // MUOKKAUS: lisatty
  $sum = $sum / 1000; // XXX summa on sentteinä, etunollatäytöllä. MUOKKAUS: BUGIKORJAUS 100 -> 1000
  //if ($factoring_oikaisutunnus != '0') { // MUOKKAUS: BUGIKORJAUS (kommentoitu ulos)
  //  $sum = $sum * -1;
  //}
  $currency_code = substr($tietue, 75, 1);
  $entry_date = "20".dateconv(substr($tietue, 15, 6)); // VVKKPP
  $payment_date = "20".dateconv(substr($tietue, 21, 6)); // VVKKPP

  $query = "SELECT *
            FROM yriti
            WHERE tilino = '$account'
            AND kaytossa = ''";
  $result = pupe_query($query);
  $yritirow = mysqli_fetch_assoc($result);

  if ($yritirow["yhtio"] == "") {
    echo "<font class='error'>Tilille $account ei löytynyt yritystä.</font><br>\n";
    exit(); // MUOKKAUS: BUGIKORJAUS
  }
  else {
    // Setataan kukarow-yhtiö
    $kukarow["yhtio"] = $yritirow["yhtio"];
  }

  if ($yritirow["valkoodi"] != "") {
    $query = "SELECT *
              FROM valuu
              WHERE yhtio = '$yritirow[yhtio]'
              AND nimi    = '$yritirow[valkoodi]'";
    $valresult = pupe_query($query);

    if (mysqli_num_rows($valresult) != 1) {
      echo "<font class='error'> Valuuttaa '$yritirow[valkoodi]' ei löytynyt!</font><br>\n";
      exit(); // MUOKKAUS: BUGIKORJAUS
    }
    else {
      $valrow = mysqli_fetch_assoc($valresult);
      $valkoodi = $valrow["nimi"];
      $kurssi = $valrow["kurssi"];
    }
  }
  else {
    $query = "SELECT valkoodi FROM yhtio WHERE yhtio = '$yritirow[yhtio]'";
    $valresult = pupe_query($query);
    $valrow = mysqli_fetch_assoc($valresult);

    if ($valrow["valkoodi"] == '') {
      echo "<font class='error'> Yhtiöllä ei ole valuuttaa!</font><br>\n";
      exit(); // MUOKKAUS: BUGIKORJAUS
    }
    else {
      $valkoodi = $valrow["valkoodi"];
      $kurssi = 1;
    }
  }

  // Katotaan löytyykö viiteaineistosääntö
  $query = "SELECT tunnus
            FROM tiliotesaanto
            WHERE yhtio     = '$yritirow[yhtio]'
            and pankkitili  = 'x'
            and nimitieto   = '$payer_name'
            and nimitieto  != ''
            and selite      = '$reference'";
  $tiliotesaantoresult = pupe_query($query);

  if (!mysqli_num_rows($tiliotesaantoresult)) {
    $query = "SELECT tunnus
              FROM tiliotesaanto
              WHERE yhtio     = '$yritirow[yhtio]'
              and pankkitili  = 'x'
              and nimitieto   = '$payer_name'
              and nimitieto  != ''";
    $tiliotesaantoresult = pupe_query($query);
  }

  if (mysqli_num_rows($tiliotesaantoresult)) {
    $pvm = substr(str_replace("-", "", $payment_date), 2);
    $vientiselite = "$payer_name ".t("viitesuoritus");
    $tkesken = 1;
    $laskuid = 0;
    $tilinvaluutta = $valkoodi;
    $maara = $sum;
    $kohdm = $sum;
    $vastavienti = $sum * -1;
    $vastavienti_valuutassa = $sum * -1;
    $tiliotedataid = $tiliotedatarow["tunnus"];
    $ei_summata_selvittelyja = TRUE;

    $tiliointisaantorow = mysqli_fetch_array($tiliotesaantoresult);
    $tiliotesaantotunnus = $tiliointisaantorow['tunnus'];

    require "inc/teeselvittely.inc";

    unset($ei_summata_selvittelyja);
    unset($tiliotesaantotunnus);
  }
  else {
    // arkistointitunnus
    $query = "INSERT INTO suoritus SET
              yhtio        = '$yritirow[yhtio]',
              tilino       = '$account',
              nimi_maksaja = '$payer_name',
              viite        = '$reference',
              summa        = $sum,
              valkoodi     = '$valkoodi',
              kurssi       = '$kurssi',
              maksupvm     = '$payment_date',
              kirjpvm      = '$entry_date'";
   echo $query . PHP_EOL . PHP_EOL; 
    // MUOKKAUS: mysqli_insert_id():
    $result = pupe_query($query, $GLOBALS["link"]);
    $tiliointitunnus = mysqli_insert_id($GLOBALS["link"]);

    // päivitetään suorituksen tunnnus tiliotedataan ja tiliotedata käsitellyksi
    $query = "UPDATE tiliotedata SET
              tiliointitunnus = '$tiliointitunnus',
              kasitelty       = now()
              WHERE tunnus    = '$tiliotedatarow[tunnus]'";
    $dataupdres = pupe_query($query, $GLOBALS["link"]); // MUOKKAUS: BUGIKORJAUS
  }
}
elseif ($type != 'S' and $type != '9') {
  // Ei herjata summarivistä eikä oikaisurivistä, silloin kun sitä ei haluta käsitellä
  echo "<font class='error'>Tuntematon tietuetunnus $type</font><br>\n";
  $errcnt++;
}
