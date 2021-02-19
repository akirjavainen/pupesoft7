<?php
// Tarvitsemme
// $laskurow jossa on laskun tiedot
// $kutsuja jossa on skripti josta kutsutaan

$kerattavia   = 0;
$ei_kerattavia   = 0;
$tilaus_valmis_message = "";

// Pamautetaan toimitusvahvistus asiakkaalle jos mennään haaraan jossa ei toimiteta mitään
$toimitusvahvitus_laheta = FALSE;

// etukäteen maksettu lasku, pitää huomioida myös kpl
if (($laskurow["tila"] == "L" or $laskurow["tila"] == "N") and $laskurow["mapvm"] != '0000-00-00' and $laskurow["mapvm"] != '') {
  $lis_plus_kpl = " + tilausrivi.kpl";
}
else {
  $lis_plus_kpl = "";
}

if ($yhtiorow["varaako_jt_saldoa"] != "") {
  $lisavarattu = " + tilausrivi.varattu $lis_plus_kpl";
}
else {
  $lisavarattu = "";
}

$rajattavat_tuotteet = "'{$yhtiorow['rahti_tuotenumero']}'";
$rajattavat_tuotteet = lisaa_vaihtoehtoinen_rahti_merkkijonoon($rajattavat_tuotteet);

$query = "SELECT count(*) riveja,
          sum(if (tilausrivi.var = 'P' and tilausrivi.tilkpl $lis_plus_kpl > 0, 1, 0)) puutteet,
          sum(if (tilausrivi.var = 'J' and tilausrivi.jt $lisavarattu > 0, 1, 0)) jteet,
          sum(if (tilausrivi.var not in ('P','J','O','S') and tilausrivi.varattu $lis_plus_kpl < 0, 1, 0)) hyvitykset,
          sum(if (tilausrivi.var not in ('P','J','O','S') and (('$yhtiorow[kerataanko_saldottomat]' = '' and tuote.ei_saldoa = '') or '$yhtiorow[kerataanko_saldottomat]' = 'S') and tilausrivi.varattu $lis_plus_kpl > 0, 1, 0)) normaalit_kerataan,
          sum(if (tilausrivi.var not in ('P','J','O','S') and '$yhtiorow[kerataanko_saldottomat]' = '' and tuote.ei_saldoa != '' and tilausrivi.varattu $lis_plus_kpl > 0, 1, 0)) saldottomat,
          sum(if (tilausrivi.var not in ('P','J','O','S') and '{$yhtiorow['saldottomat_rahtikirjansyottoon']}' != '' and tuote.ei_saldoa != '' and tuote.tuotetyyppi = '' and tilausrivi.varattu {$lis_plus_kpl} > 0, 1, 0)) saldottomat_rahtikirjansyottoon,
          sum(if (tilausrivi.toimitettu != '' and tuote.ei_saldoa = '' and (tilausrivi.varattu $lis_plus_kpl > 0 and tilausrivi.var not in ('P','J','O','S')), 1, 0)) toimitetut
          FROM tilausrivi
          LEFT JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
          WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
          and tilausrivi.otunnus  = '$laskurow[tunnus]'
          and tilausrivi.tyyppi   = 'L'
          and tilausrivi.tuoteno NOT IN ({$rajattavat_tuotteet})";
$tresult = pupe_query($query);
$rivirow = mysqli_fetch_array($tresult);

// Kerättävät ja ei kerättävä rivit
$kerattavia = $rivirow["normaalit_kerataan"];
$ei_kerattavia = $rivirow["saldottomat"] + $rivirow["hyvitykset"];

// Toimitettuja rivejä ei kerätä ikinä
$ei_kerattavia += $rivirow["toimitetut"];
$kerattavia -= $rivirow["toimitetut"];

/*
puute_jt_kerataanko
''  Puuterivejä ja JT-rivejä EI kerätä
'P' Puuterivit kerätään
'J' JT-rivit kerätään
'Q' Puuterivit ja JT-rivit kerätään

kerataanko_jos_vain_puute_jt
''  Tilausta EI kerätä jos listalla on vain Puuterivejä JA/TAI JT-rivejä
'P' Tilaus kerätään jos listalla on vain Puuterivejä
'J' Tilaus kerätään jos listalla on vain JT-rivejä
'Q' Tilaus kerätään jos listalla on vain Puuterivejä JA/TAI JT-rivejä
*/

// ei lasketa kerättäviä/eikerättäviä määriä jos ollaan kerää.phpssa, koska silloin kaikki on varmasti kerättäviä jos ne siellä näky
if (($yhtiorow["puute_jt_kerataanko"] == "P" or $yhtiorow["puute_jt_kerataanko"] == "Q") and $kutsuja != 'keraa.php') {
  // Jos yhtio haluaa kerätä Puuterivit
  $kerattavia += $rivirow["puutteet"];
}
else {
  $ei_kerattavia += $rivirow["puutteet"];
}

if (($yhtiorow["puute_jt_kerataanko"] == "J" or $yhtiorow["puute_jt_kerataanko"] == "Q") and $kutsuja != 'keraa.php') {
  // Jos yhtiö haluaa kerätä JT-rivit
  $kerattavia += $rivirow["jteet"];
}
else {
  $ei_kerattavia += $rivirow["jteet"];
}

// jos yhtiö haluaa kerätä puuterivejä niin katotaan onko meillä pelkästään tilauksella jt/puuterivejä (HB tilaukset kerätään silti!)
if ($yhtiorow["puute_jt_kerataanko"] != "" and $kutsuja != 'keraa.php') {
  if ($rivirow["puutteet"] == $kerattavia and ($yhtiorow["puute_jt_kerataanko"] == "P" or $yhtiorow["puute_jt_kerataanko"] == "Q") and (($yhtiorow["kerataanko_jos_vain_puute_jt"] == "" and $laskurow["alatila"] != 'FF') or $yhtiorow["kerataanko_jos_vain_puute_jt"] == "J")) {
    // Meillä on pelkästään puutteita kerättävänä ja niitä ei tässä tapauksessa kerätä
    $ei_kerattavia += $rivirow["puutteet"];
    $kerattavia     -= $rivirow["puutteet"];
  }
  elseif ($rivirow["jteet"] == $kerattavia and ($yhtiorow["puute_jt_kerataanko"] == "J" or $yhtiorow["puute_jt_kerataanko"] == "Q") and ($yhtiorow["kerataanko_jos_vain_puute_jt"] == "" or $yhtiorow["kerataanko_jos_vain_puute_jt"] == "P")) {
    // Meillä on pelkästään JT-rivejä kerättävänä ja niitä ei tässä tapauksessa kerätä
    $ei_kerattavia += $rivirow["jteet"];
    $kerattavia     -= $rivirow["jteet"];
  }
  elseif ($rivirow["jteet"] + $rivirow["puutteet"] == $kerattavia and $yhtiorow["puute_jt_kerataanko"] == "Q" and $yhtiorow["kerataanko_jos_vain_puute_jt"] == "") {
    // Meillä on pelkästään JT-rivejä + puutteita kerättävänä ja niitä ei tässä tapauksessa kerätä
    $ei_kerattavia += $rivirow["jteet"] + $rivirow["puutteet"];
    $kerattavia     -= $rivirow["jteet"] + $rivirow["puutteet"];
  }
}

$tilaus_toimitetuksi = false;

//Siirtolistat kerätään keräyserien kautta ja siihen linkattu myyntitilaus halutaan laittaa toimitetuksi kun siirtolista on vastaanotettu kohdevarastossa
if ($yhtiorow['kerayserat'] == 'K' and $yhtiorow['siirtolistan_tulostustapa'] == 'U' and $laskurow['tila'] == 'N' and $laskurow['alatila'] == '') {
  $query = "SELECT *
            FROM tilausrivi AS t
            JOIN tilausrivin_lisatiedot AS tt
            ON ( tt.yhtio = t.yhtio
              AND tt.tilausrivilinkki = t.tunnus )
            JOIN tilausrivi AS t2
            ON ( t2.yhtio = tt.yhtio
              AND t2.tunnus           = tt.tilausrivitunnus
              AND t2.tyyppi           = 'G' )
            WHERE t.yhtio             = '{$kukarow['yhtio']}'
            AND t.otunnus             = {$laskurow['tunnus']}
            AND t.tyyppi              = 'L'";
  $result = pupe_query($query);

  if (mysqli_num_rows($result) > 0) {
    $tilaus_toimitetuksi = true;
  }
}

/*
echo "puute_jt_kerataanko: $yhtiorow[puute_jt_kerataanko]<br>";
echo "kerataanko_jos_vain_puute_jt: $yhtiorow[kerataanko_jos_vain_puute_jt]<br>";
echo "R: $rivirow[riveja]<br>";
echo "P: $rivirow[puutteet]<br>";
echo "J: $rivirow[jteet]<br>";
echo "H: $rivirow[hyvitykset]<br>";
echo "N: $rivirow[normaalit_kerataan]<br>";
echo "S: $rivirow[saldottomat]<br>";
echo "T: $rivirow[toimitetut]<br>";
echo "KER: $kerattavia<br>";
echo "EIKER: $ei_kerattavia<br>";
// */

// Tutkitaan mitä tehdään tilaukselle
if ($rivirow["riveja"] == 0 or ($kerattavia == 0 and $ei_kerattavia == 0 and $kutsuja == 'keraa.php')) {
  // Tilauksella ei ole mitään laskutettavaa eikä kerättävää
  $query  = "UPDATE tilausrivi
             SET tyyppi='D'
             WHERE otunnus = '$laskurow[tunnus]' and yhtio = '$kukarow[yhtio]'";
  $result = pupe_query($query);

  $komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mitätöi ohjelmassa tilaus-valmis-valitsetila.php (1)")."<br>";

  $query  = "UPDATE lasku
             SET tila='D', alatila='$laskurow[tila]', comments = '$komm'
             where tunnus='$laskurow[tunnus]'
             and yhtio='$kukarow[yhtio]'";
  $result = pupe_query($query);

  $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilaus %s mitätöitiin koska tilauksella ei ollut yhtään kerättävää/laskutettavaa riviä."), $laskurow["tunnus"])."</font><br><br>\n";
  $toimitusvahvitus_laheta = TRUE;
}
elseif ($rivirow["jteet"] > 0 and $laskurow["osatoimitus"] != '') {
  // Jos tilauksella oli yksikin jt-rivi ja osatoimitus on kielletty
  $query  = "UPDATE lasku
             SET tila='N', alatila='U'
             where tunnus='$laskurow[tunnus]'
             and yhtio='$kukarow[yhtio]'";
  $result = pupe_query($query);

  $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s oli JT-rivejä ja osatoimitusta ei tehdä, eli se jätettiin odottamaan JT-tuotteita."), $laskurow["tunnus"])."</font><br><br>\n";
}
elseif ($kerattavia == 0 or $tilaus_toimitetuksi) {
  // Rahtikulurivien rajaus
  $rajattavat_tuotteet = "'{$yhtiorow['rahti_tuotenumero']}'";
  $rajattavat_tuotteet = lisaa_vaihtoehtoinen_rahti_merkkijonoon($rajattavat_tuotteet);
  // Tilauskella on pelkästään puuterivejä joita ei kerätä
  // merkataan otsikko mitätöidyksi, mutta jätetään PUUTE rivit analyysejä varten...
  if ($rivirow["riveja"] == $rivirow["puutteet"]) {
    // Jos tilauksella oli vain puuterivejä niin poistetaan rahtikulurivi jos sellanen on
    $query  = "DELETE FROM tilausrivi
               WHERE otunnus = '$laskurow[tunnus]'
               AND yhtio     = '$kukarow[yhtio]'
               AND tuoteno IN ({$rajattavat_tuotteet})";
    $result = pupe_query($query);

    $komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mitätöi ohjelmassa tilaus-valmis-valitsetila.php (2)")."<br>";

    $query  = "UPDATE lasku
               SET tila='D', alatila='$laskurow[tila]', comments = '$komm'
               WHERE tunnus = '$laskurow[tunnus]'
               AND yhtio    = '$kukarow[yhtio]'";
    $result = pupe_query($query);

    $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s  oli pelkästään Puuterivejä. Otsikko mitätöitiin."), $laskurow["tunnus"])."</font><br><br>\n";
    $toimitusvahvitus_laheta = TRUE;
  }
  elseif ($rivirow["riveja"] == $rivirow["jteet"]) {

    if ($yhtiorow["rahti_hinnoittelu"] != 'K') {
      // Jos tilauksella oli vain jt-rivejä niin poistetaan rahtikulurivi jos sellanen on
      $query  = "DELETE FROM tilausrivi
                 WHERE otunnus = '$laskurow[tunnus]'
                 AND yhtio     = '$kukarow[yhtio]'
                 AND tuoteno IN ({$rajattavat_tuotteet})";
      $result = pupe_query($query);
    }

    $query  = "UPDATE lasku
               SET tila='N', alatila='T'
               WHERE tunnus='$laskurow[tunnus]'
               AND yhtio='$kukarow[yhtio]'";
    $result = pupe_query($query);

    $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s oli pelkästään JT-rivejä ja se jätettiin odottamaan JT-tuotteita."), $laskurow["tunnus"])."</font><br><br>\n";
  }
  elseif ($rivirow["riveja"] == $rivirow["jteet"] + $rivirow["puutteet"]) {
    // Tilauskella on pelkästään jt-rivejä ja puutteita joita ei kerätä

    if ($yhtiorow["rahti_hinnoittelu"] != 'K') {
      // Jos tilauksella oli vain jt/puute-rivejä niin poistetaan rahtikulurivi jos sellanen on
      $query  = "DELETE FROM tilausrivi
                 WHERE otunnus = '$laskurow[tunnus]'
                 AND yhtio     = '$kukarow[yhtio]'
                 AND tuoteno IN ({$rajattavat_tuotteet})";
      $result = pupe_query($query);
    }

    // Päivitetään tilaus tilaan 'odottaa jt-rivejä'
    $query  = "UPDATE lasku
               SET tila='N', alatila='T'
               WHERE tunnus = '$laskurow[tunnus]'
               AND yhtio    = '$kukarow[yhtio]'";
    $result = pupe_query($query);

    $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s oli pelkästään JT-rivejä ja Puuterivejä ja se jätettiin odottamaan JT-tuotteita."), $laskurow["tunnus"])."</font><br><br>\n";
  }
  elseif ($rivirow["riveja"] == $rivirow["saldottomat_rahtikirjansyottoon"]) {
    // merkataan rivit kerätyiksi, mutta ei toimitetuiksi ja tilaukselle alatila "kerätty"
    $query  = "UPDATE tilausrivi SET
               kerattyaika = now(),
               keratty = '{$kukarow['kuka']}'
               WHERE otunnus   = '{$laskurow['tunnus']}'
               and yhtio       = '{$kukarow['yhtio']}'
               and var         NOT IN ('P','J','O','S')
               and keratty     = ''
               and toimitettu  = ''
               and tyyppi     != 'D'";
    $result = pupe_query($query);

    $query  = "UPDATE lasku SET
               tila         = 'L',
               alatila      = 'C',
               lahetepvm    = now()
               WHERE tunnus = '{$laskurow['tunnus']}'
               AND yhtio    = '{$kukarow['yhtio']}'";
    $result = pupe_query($query);

    $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s ei ollut yhtään kerättävää riviä. Tilaus siirretty rahtikirjansyöttöön."), $laskurow["tunnus"])."</font><br><br>\n";
  }
  elseif ($tilaus_toimitetuksi or $ei_kerattavia > 0) {
    if ($kateinen == 'X' or $itsetulostus == "X" or $laskurow["vienti"] != '') {
      // jos on laskuettavia rivejä ja jos kyseessä on käteistä, itsetulostus tai vientiä
      // merkataan rivit kerätyiksi, mutta ei toimitetuiksi ja laskulle alatila alatila C/B
      $query  = "UPDATE tilausrivi
                 SET kerattyaika = now(), keratty = '$kukarow[kuka]'
                 WHERE otunnus   = '$laskurow[tunnus]'
                 and yhtio       = '$kukarow[yhtio]'
                 and var         not in ('P','J','O','S')
                 and keratty     = ''
                 and toimitettu  = ''
                 and tyyppi     != 'D'";
      $result = pupe_query($query);

      // Jos on vientiä ja vain saldottomia niin mennään suoraan toimitetuksi
      if ($laskurow["vienti"] != '' and $rivirow["riveja"] == $rivirow["saldottomat"]) {

        // Jos meillä on maksupositioita laskulla, tulee se siirtää alatilaan J
        if ($laskurow['jaksotettu'] != 0) {
          $alat = "J";
        }
        else {
          $alat = "D";
        }

        $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s ei ollut yhtään kerättävää riviä, se merkattiin toimitetuksi."), $laskurow["tunnus"])."</font><br><br>\n";

        $query  = "UPDATE tilausrivi
                   SET toimitettuaika = now(), toimitettu = '$kukarow[kuka]'
                   WHERE otunnus   = '$laskurow[tunnus]'
                   and yhtio       = '$kukarow[yhtio]'
                   and var         not in ('P','J','O','S')
                   and keratty    != ''
                   and toimitettu  = ''
                   and tyyppi     != 'D'";
        $result = pupe_query($query);
      }
      elseif ($laskurow["vienti"] != '') {
        $alat = "B";
        $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s ei ollut yhtään kerättävää riviä, se merkattiin toimitetuksi. Viennin lisätiedot syötettävä."), $laskurow["tunnus"])."</font><br><br>\n";

        $query  = "UPDATE tilausrivi
                   SET toimitettuaika = now(), toimitettu = '$kukarow[kuka]'
                   WHERE otunnus   = '$laskurow[tunnus]'
                   and yhtio       = '$kukarow[yhtio]'
                   and var         not in ('P','J','O','S')
                   and keratty    != ''
                   and toimitettu  = ''
                   and tyyppi     != 'D'";
        $result = pupe_query($query);
      }
      else {
        $alat = "C";
        $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s ei ollut yhtään kerättävää riviä, se merkattiin kerätyksi."), $laskurow["tunnus"])."</font><br><br>\n";
      }

      $query  = "UPDATE lasku SET
                 tila         = 'L',
                 alatila      = '$alat',
                 lahetepvm    = now()
                 WHERE tunnus = '$laskurow[tunnus]'
                 AND yhtio    = '$kukarow[yhtio]'";
      $result = pupe_query($query);
    }
    else {
      // Jos on laskuettavia rivejä ja jos kyseessä ei ole käteinen, merkataan tilaukset kerätyksi ja toimitetuksi ja alatila 'D'
      $query  = "UPDATE tilausrivi
                 SET kerattyaika = now(), keratty = '$kukarow[kuka]'
                 WHERE otunnus  = '$laskurow[tunnus]'
                 and yhtio      = '$kukarow[yhtio]'
                 and var        not in ('P','J','O','S')
                 and keratty    = ''
                 and toimitettu = ''";
      $result = pupe_query($query);

      $query  = "UPDATE tilausrivi
                 SET toimitettuaika = now(), toimitettu = '$kukarow[kuka]'
                 WHERE otunnus   = '$laskurow[tunnus]'
                 and yhtio       = '$kukarow[yhtio]'
                 and var         not in ('P','J','O','S')
                 and keratty    != ''
                 and toimitettu  = ''
                 and tyyppi     != 'D'";
      $result = pupe_query($query);

      // Jos meillä on maksupositioita laskulla, tulee se siirtää alatilaan J
      if ((int)$laskurow['jaksotettu'] != 0) {
        $tilaus_valmis_alataila = 'D'; // MUOKKAUS: J -> D
      }
      else {
        $tilaus_valmis_alataila = 'D';
      }

      $query  = "UPDATE lasku SET
                 tila         = 'L',
                 alatila      = '$tilaus_valmis_alataila',
                 lahetepvm    = now()
                 WHERE tunnus = '$laskurow[tunnus]'
                 AND yhtio    = '$kukarow[yhtio]'";
      $result = pupe_query($query);

      $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s ei ollut yhtään kerättävää riviä, se merkattiin toimitetuksi."), $laskurow["tunnus"])."</font><br><br>\n";
      $toimitusvahvitus_laheta = TRUE;
      echo "<meta http-equiv=\"refresh\" content=\"1;URL=valitse_laskutettavat_tilaukset.php?indexvas=1\">\n";
    }
  }
  else {
    $tilaus_valmis_message .= "<font class='message'>".sprintf(t("VIRHE: Tilausta %s ei voitu merkata valmiiksi. Aktivoi tilaus ja korjaa virheet"), $laskurow["tunnus"])."! (1)</font><br><br>\n";
  }
}
elseif ($kerattavia > 0 and $kutsuja != 'keraa.php' and !$tilaus_toimitetuksi) {
  // Tässä meillä ON jotain kerättävää
  // laitetaan lähete tulostusjonoon
  $tila = 'N';
  $alatila = 'A';

  if ($yhtiorow['lahetteen_tulostustapa'] == 'I') {
    $tila = 'L';
    $alatila = 'D';
  }

  // Jos yhtiön parametreissä on keräyserät A tarkistetaan asiakkaan takaa
  if ($yhtiorow['kerayserat'] == 'A') {
    // Haetaan kerayseratieto asiakkaan takaa
    $query = "SELECT kerayserat
              FROM asiakas
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$laskurow['liitostunnus']}'";
    $asrow = mysqli_fetch_assoc(pupe_query($query));
  }

  // Poistetaan vanhat keräyserät kun palautetaan tilaus takaisin tulostusjonoon,
  // jottei keräyseriä luotaisi joka kerta vaan lisää ja lisää,
  // kun palautetaan tilaus takaisin tulostusjonoon
  if ($yhtiorow['kerayserat'] == 'K'
    or $yhtiorow['kerayserat'] == 'P'
    or ($yhtiorow['kerayserat'] == 'A' and $asrow['kerayserat'] == 'A')) {

    $query = "DELETE FROM kerayserat
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND otunnus = '{$laskurow['tunnus']}'";
    pupe_query($query);
  }

  $query  = "UPDATE lasku SET
             tila        = '{$tila}',
             alatila     = '{$alatila}'
             WHERE yhtio = '$kukarow[yhtio]'
             and tunnus  = '$laskurow[tunnus]'
             and tila    in ('N', 'L')
             and alatila in ('', 'A', 'F', 'FF', 'U', 'G')";
  $result = pupe_query($query);

  ///* Tähän mennään jos lähete tosiaan tulostetaan *///
  if (in_array($yhtiorow["lahetteen_tulostustapa"], array("A","B","X"))) {
    $tilausnumeroita = $laskurow["tunnus"];
    require "tilaus-valmis-tulostus.inc";

    // jos halutaan MYÖS lähete samantien
    if ($yhtiorow["lahetteen_tulostustapa"] == "B") {
      $params = array(
        'laskurow'          => $laskurow,
        'sellahetetyyppi'       => "",
        'extranet_tilausvahvistus'   => "",
        'naytetaanko_rivihinta'    => "",
        'tee'            => "",
        'toim'            => $toim,
        'komento'           => $komento,
        'lahetekpl'          => "",
        'kieli'           => ""
      );

      pupesoft_tulosta_lahete($params);
    }
  }
  ///* Tähän mennään jos lähete vain siirretään tulostusjonoon *///
  elseif (mysqli_affected_rows($link) > 0) {
    $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilaus %s siirretty tulostusjonoon"), $laskurow["tunnus"])."!</font><br><br>\n";
  }
}
elseif ($kutsuja != 'keraa.php' and !$tilaus_toimitetuksi) {
  $tilaus_valmis_message .= "<font class='message'>".sprintf(t("VIRHE: Tilausta %s ei voitu merkata valmiiksi. Aktivoi tilaus ja korjaa virheet"), $laskurow["tunnus"])."! (2)</font><br><br>\n";
}

if ($toimitusvahvitus_laheta) {
  $otunnukset = $laskurow["tunnus"];

  // Lähetetään toimitusvahvistus
  pupesoft_toimitusvahvistus($otunnukset);
}

// haetaan toimitustavan tiedot
$query = "SELECT *,
          if(virallinen_selite != '', virallinen_selite, selite) as selite_text
          FROM toimitustapa
          WHERE yhtio = '$kukarow[yhtio]'
          AND selite  = '$laskurow[toimitustapa]'";
$toitares = pupe_query($query);
$toitarow = mysqli_fetch_assoc($toitares);

// Jos Magento on käytössä, halutaan toimitusvahvistus, tämä on noutotilaus ja tullut Magentosta
$_magento_tv       = ($laskurow['laatija'] == 'Magento' and $toitarow['nouto'] != '' and !empty($laskurow["asiakkaan_tilausnumero"]));
$_magento_kaytossa = (!empty($magento_api_tt_url) and !empty($magento_api_tt_usr) and !empty($magento_api_tt_pas));

if ($_magento_kaytossa and $_magento_tv and $kutsuja == 'keraa.php') {
  $magento_api_met = $toitarow['selite_text'];
  $magento_api_rak = '';
  $magento_api_ord = $laskurow["asiakkaan_tilausnumero"];
  $magento_api_noutokuittaus = "JOO";

  require "magento_toimita_tilaus.php";
}

if (!isset($silent) or $silent == "") {
  echo $tilaus_valmis_message;
}
