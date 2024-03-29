<?php

/* Pupesoft -> DPD DELISprint datasiirto
 * rahtikirja_dpd_siirto.inc v0.8a 18.03.2008
 * Originaali: rahtikirja_dpd.inc
 * Kustomointi: Mikko Nousiainen mikko@datamonitor.fi
 *************************************************************/

//jos on laitettu kollikenttään 0 niin ei yritetä luoda siirtotiedostoa
if ($kollityht > 0) {

  // Onko varastokohtainen delisprinttiserveri?
  if (isset($varasto) and $varasto > 0) {
    $dpd_ds_host = (isset($dpd_delisprint_host[$varasto]) and $dpd_delisprint_host[$varasto] != "") ? $dpd_delisprint_host[$varasto] : $dpd_delisprint_host;
    $dpd_ds_user = (isset($dpd_delisprint_user[$varasto]) and $dpd_delisprint_user[$varasto] != "") ? $dpd_delisprint_user[$varasto] : $dpd_delisprint_user;
    $dpd_ds_pass = (isset($dpd_delisprint_pass[$varasto]) and $dpd_delisprint_pass[$varasto] != "") ? $dpd_delisprint_pass[$varasto] : $dpd_delisprint_pass;
    $dpd_ds_path = (isset($dpd_delisprint_path[$varasto]) and $dpd_delisprint_path[$varasto] != "") ? $dpd_delisprint_path[$varasto] : $dpd_delisprint_path;
  }
  else {
    $dpd_ds_host = $dpd_delisprint_host;
    $dpd_ds_user = $dpd_delisprint_user;
    $dpd_ds_pass = $dpd_delisprint_pass;
    $dpd_ds_path = $dpd_delisprint_path;
  }

  if (($dpd_ds_host != "" and $dpd_ds_user != "" and $dpd_ds_pass != "" and $dpd_ds_path != "") or $dpd_ds_path != "") {

    if ($toitarow['logy_rahtikirjanumerot'] != "" and mb_strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE and (!isset($muutos) or $muutos != 'yes')) {

      $logyq = "SELECT min(rahtikirjanro) rahtikirjanro
                FROM rahtikirjanumero
                WHERE yhtio      = '$kukarow[yhtio]'
                and kaytettyaika = '0000-00-00 00:00:00'";
      $logyres = pupe_query($logyq);
      $logyrow = mysqli_fetch_assoc($logyres);

      $rahtikirjanro = $logyrow["rahtikirjanro"];

      if ($rahtikirjanro != "") {
        $logyq = "UPDATE rahtikirjanumero
                  SET kayttaja = '$kukarow[kuka]',
                  kaytettyaika      = now()
                  WHERE yhtio       = '$kukarow[yhtio]'
                  and rahtikirjanro = '$logyrow[rahtikirjanro]'";
        $logyres = pupe_query($logyq);
      }
    }

    if ($rahtikirjanro == "" and mb_strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE and (!isset($muutos) or $muutos != 'yes')) {
      $rahtikirjanro = $lotsikot[0];
    }
    elseif ($rahtikirjanro == "") {
      $rahtikirjanro = $rivi["rahtikirjanro"];
    }

    if ($phpnimi == "rahtikirja_custom.php") {
      $laskurow = $osoitelappurow;
      $rakir_row = $osoitelappurow;
    }

    //$apu_kollityht = $kollityht;
    if ($kollityht > 20) {
      die("Liikaa kolleja! (max 20 kpl)"); // kolleja ei saa olla yli 20 per toimitusrivi...
    }

    //  Luodaan meidän tiedostorakenne parcelOrder arrayhin ja pukataan se tonne aineistoon sitten laakista
    $parcelOrder=array();
    $parcelOrder["rahtikirjanro"] = $rahtikirjanro;

    $tilausnumerot = $tilausnumerot2 = "";

    // Laitetaan max 30 merkkiä tilausnumeroita välilyönnillä eroteltuna
    foreach ($lotsikot as $tilnum) {
      if ((mb_strlen($tilausnumerot) + mb_strlen($tilnum) + 1) <= 30) {
        $tilausnumerot = "{$tilausnumerot} {$tilnum}";
      }
      elseif ((mb_strlen($tilausnumerot2) + mb_strlen($tilnum) + 1) <= 30) {
        $tilausnumerot2 = "{$tilausnumerot2} {$tilnum}";
      }
      else {
        break;
      }
    }

    $parcelOrder["tilausnumero"] = trim($tilausnumerot);
    $parcelOrder["tilausnumero2"] = trim($tilausnumerot2);

    //  Haetaan otsikkotiedot pohjalle
    $query = "SELECT *
              FROM lasku
              WHERE yhtio='$kukarow[yhtio]' and tunnus in ($otunnukset)
              ORDER BY tunnus limit 1";
    $laskures = pupe_query($query);
    $laskurow = mysqli_fetch_array($laskures);

    for ($i=0;$i<mysqli_num_fields($laskures);$i++) {
      $r = mysqli_field_name($laskures, $i);
      $v = $laskurow[$i];

      //  Korvaavuksia ja näppäilyä
      if ($r=="asiakkaan_viite" and $v == "") {
        $v = $laskurow["viite"];
      }

      //  Poistetaan aina kaikki turhat merkit ja välilyönnit
      $parcelOrder[$r] = trim(str_replace(array("\r\n", "\r", "\n"), " ", $v));
    }

    //  Haetaan asiakastiedot
    $query = "SELECT *
              FROM asiakas
              WHERE yhtio='$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
    $asres = pupe_query($query);
    $asrow = mysqli_fetch_array($asres);

    for ($i=0;$i<mysqli_num_fields($asres);$i++) {
      $r = mysqli_field_name($asres, $i);
      $v = $asrow[$i];

      //  Korvaavuksia ja näppäilyä

      $parcelOrder["asiakas_".$r] = $v;
    }

    $query = "SELECT *
              FROM asiakkaan_avainsanat
              WHERE yhtio      = '{$kukarow['yhtio']}'
              AND liitostunnus = '{$asrow['tunnus']}'
              AND LOWER(laji) = 'dpd'";
    $as_avainsana_res = pupe_query($query);
    $as_avainsana_row = mysqli_fetch_assoc($as_avainsana_res);

    /*
      Arvotaan lähetystapa
    */

    // jos maksuehto on jv, laitetaan NP,NN,CD
    if ($rakir_row['jv'] != "") {
      $parcelOrder["lahetystapa"] = "NP,NN,CD";
    }
    else {
      if (mb_strtolower($toitarow['lajittelupiste']) == 'sameday') {
        //$parcelOrder["lahetystapa"] = "NP,SD"; Toistaiseksi tuo SD pois DPD:n pyynnöstä "sameday" tapauksessa.
        $parcelOrder["lahetystapa"] = "NP";
      }
      elseif (mb_strtolower($toitarow['lajittelupiste']) == 'regular' and $as_avainsana_row['avainsana'] != '') {
        $parcelOrder["lahetystapa"] = "NP,{$as_avainsana_row['avainsana']}";
      }
      else {
        $parcelOrder["lahetystapa"] = "NP";
      }
    }

    $koko_summa = 0;

    //  Luotamme vahvasti siihen, että meillä on rakir_res tallella.. Täältä tulee jotain hyödyllistä kumminkin..
    for ($i=0;$i<mysqli_num_fields($rakir_res);$i++) {

      $r = mysqli_field_name($rakir_res, $i);
      $v = $rakir_row[$r];

      if ($r == "jv" and $v != "") {
        $parcelOrder["jv_summa"]     = $yhteensa;
        $parcelOrder["jv_valkoodi"]   = $yhtiorow["valkoodi"];
        $parcelOrder["jv_pankkitili"]   = $yhtiorow["pankkitili1"];
        $parcelOrder["jv_viite"]     = $viite;
      }

      $parcelOrder[$r] = $v;
    }

    if (isset($parcelOrder["jv_summa"]) and trim($parcelOrder["jv_summa"]) != "") {
      $parcelOrder["kateinen"] = t("Käteinen");
    }
    else {
      $parcelOrder["kateinen"] = "";
      $parcelOrder["jv_summa"] = "";
      $parcelOrder["jv_valkoodi"] = "";
    }

    /*
    Jos meillä oli joku yhteyshenkilö haetaan sen tiedot myös! (Falceback asiakas)
    */
    $query = "SELECT nimi, titteli, email, puh, gsm, fax
              FROM laskun_lisatiedot
              JOIN yhteyshenkilo ON yhteyshenkilo.yhtio=laskun_lisatiedot.yhtio and yhteyshenkilo.tunnus=laskun_lisatiedot.yhteyshenkilo_tekninen and yhteyshenkilo.tyyppi = 'A'
              WHERE laskun_lisatiedot.yhtio = '$kukarow[yhtio]'
              and otunnus                   IN ($otunnukset)
              and yhteyshenkilo_tekninen    > 0";
    $yhres = pupe_query($query);

    if (mysqli_num_rows($yhres) == 0) {
      $query = "SELECT nimi, titteli, email, puh, gsm, fax
                FROM laskun_lisatiedot
                JOIN yhteyshenkilo ON yhteyshenkilo.yhtio=laskun_lisatiedot.yhtio and yhteyshenkilo.tunnus=laskun_lisatiedot.yhteyshenkilo_kaupallinen and yhteyshenkilo.tyyppi = 'A'
                WHERE laskun_lisatiedot.yhtio = '$kukarow[yhtio]'
                and otunnus                   IN ($otunnukset)
                and yhteyshenkilo_tekninen    > 0";
      $yhres = pupe_query($query);

      if (mysqli_num_rows($yhres) == 0) {
        $query = "SELECT '2' O, '' nimi, '' titteli, email, puhelin puh, gsm, fax
                  FROM asiakas
                  WHERE yhtio='$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
        $yhres = pupe_query($query);
      }
    }

    if (mysqli_num_rows($yhres) > 0) {
      $yhrow = mysqli_fetch_array($yhres);
      for ($i=0;$i<mysqli_num_fields($yhres);$i++) {
        $r = mysqli_field_name($yhres, $i);
        $v = $yhrow[$i];

        //  Korvaavuksia ja näppäilyä
        $parcelOrder["yhteyshenkilo_".$r] = $v;
      }
    }

    $parcelOrder["kollit"] = $kollityht;

    //  Luodaan aineostoformaatti
    if (!is_array($delisprintFileFormat)) {

      //######### Uusi muokattu formaatti ##########
      // Reference
      // Reference 2
      // Viite
      // Vastaanottajan yhteyshenkilö
      // Vastaanottajan nimi
      // Vastaanottajan lähiosoite
      // Vastaanottajan lähiosoite 2
      // Vastaanottajan postinumero
      // Vastaanottajan postitoimipaikka
      // Vastaanottajan maa
      // Vastaanottajan puhelinnumero
      // Pakkausten määrä
      // Lähetystapa
      // Loppusumma jos jälkivaatimus, muuten ei mitään
      // Valuutta jos jälkivaatimus, muuten ei mitään
      // Jos maksuehto on jälkivaatimus, laitetaan t("Käteinen")
      // Rahtikirjanro
      $delisprintFileFormat = array(
        "tilausnumero",
        "tilausnumero2",
        "viite",
        "yhteyshenkilo_nimi",
        "toim_nimi",
        "toim_osoite",
        "toim_postino",
        "toim_postitp",
        "toim_maa",
        "yhteyshenkilo_puh",
        "kollit",
        "lahetystapa",
        "jv_summa",
        "jv_valkoodi",
        "kateinen",
        "rahtikirjanro",
      );

    }

    unset($aineisto);

    foreach ($delisprintFileFormat as $p) {
      $aineisto .= $parcelOrder[$p].";";

      // tehdään ylimääräinen ; koska formaatissa on lähiosoite 2 mitä ei ole pupessa
      if ($p == "toim_osoite") {
        $aineisto .= ";";
      }
    }

    // Muunnetaan merkistö Windows ANSI
    iconv("UTF-8", "Windows-1252", $aineisto);

    if (mb_substr($dpd_ds_path, -1) != '/') {
      $dpd_ds_path .= '/';
    }

    // Jos siirretään FTP:llä niin $dpd_ds_path on remote pathi ja jos ei siirretä niin $dpd_ds_path on local pathi
    if ($dpd_ds_host != "" and $dpd_ds_user != "" and $dpd_ds_pass != "" and $dpd_ds_path != "") {

      $filenimi = "/tmp/dpdimport-".md5(uniqid(rand(), true)).".dat";

      if (file_put_contents($filenimi, $aineisto) === FALSE) {
        echo "<br><font class='error'>".t("tiedoston kirjoitus EPÄONNISTUI")."</font><br>";
      }

      // tarvitaan  $ftphost $ftpuser $ftppass $ftppath $ftpfile
      // palautetaan $palautus ja $syy
      $ftphost = $dpd_ds_host;
      $ftpuser = $dpd_ds_user;
      $ftppass = $dpd_ds_pass;
      $ftppath = (trim($toitarow['lajittelupiste']) != '') ? $dpd_ds_path . trim($toitarow['lajittelupiste']) . "/" : $dpd_ds_path;
      $ftpfile = realpath($filenimi);

      require "inc/ftp-send.inc";
    }
    else {
      //kirjoitetaan faili levylle..
      $filenimi = $dpd_ds_path."dpdimport-".md5(uniqid(rand(), true)).".dat";

      if (file_put_contents($filenimi, $aineisto) === FALSE) {
        echo "<br><font class='error'>".t("tiedoston kirjoitus EPÄONNISTUI")."</font><br>";
      }
    }
  }
  else {
    echo "<br><font class='error'>".t("DPD aineiston luotiin tarvittavia parametrejä puuttuu!")."</font><br>";
  }

}
