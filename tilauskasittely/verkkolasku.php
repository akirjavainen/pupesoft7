<?php

// verkkolasku.php
//
// tarvitaan $kukarow ja $yhtiorow
//
// laskutetaan kaikki/valitut toimitetut tilaukset
// lähetetään kaikki laskutetut tilaukset operaattorille tai tulostetaan ne paperille
//
// $laskutettavat   --> jos halutaan laskuttaa vain tietyt tilaukset niin silloin ne tulee muuttujassa
// $laskutakaikki   --> muutujan avulla voidaan ohittaa laskutusviikonpäivät
// $eiketjut        --> muuttujan avulla katotaan halutaanko laskuttaa vaan laskut joita *EI SAA* ketjuttaa
//
// jos ollaan saatu komentoriviltä parametrejä
// $yhtio ja $kieli --> komentoriviltä pitää tulla parametreinä
// $eilinen         --> optional parametri on jollon ajetaan laskutus eiliselle päivälle
//
// $silent muuttujalla voidaan hiljentää kaikki outputti

// jos chn = 999 se tarkoittaa että lasku on laskutuskiellossa eli ei saa käsitellä täällä!!!!!

//$silent = '';

// Tämä vaatii paljon muistia
ini_set("memory_limit", "5G");

// Kutsutaanko CLI:stä, jos setataan force_web saadaan aina "ei cli" -versio
$force_web = (isset($force_web) and $force_web === true);
$php_cli = ((php_sapi_name() == 'cli' or isset($editil_cli)) and $force_web === false);

if (!function_exists("tarkista_jaksotetut_laskutunnukset")) {
  // Ottaa sisään arrayn jossa keynä 'jaksotettu' ja valuena array laskutunnuksia
  // Tarkistaa löytyykö tarvittava määrä laskutunnuksia
  // palauttaa arrayn jossa kaikki ok tunnukset[0] ja virheelliset tunnukset[1]
  function tarkista_jaksotetut_laskutunnukset($jaksotetut_array) {
    global $kukarow;

    $hyvat = "";
    $vialliset = "";

    foreach ($jaksotetut_array as $key => $value) {

      $query = "SELECT count(*) yhteensa
                FROM lasku
                WHERE yhtio     = '{$kukarow['yhtio']}'
                AND tila       != 'D'
                AND jaksotettu != 0
                AND jaksotettu  = '{$key}'";
      $result = pupe_query($query);
      $row = mysqli_fetch_assoc($result);
      $tarkastettu_maara = $row['yhteensa'];

      if (count($value) != $tarkastettu_maara) {
        $vialliset .= implode(',', $value).",";
      }
      else {
        $hyvat .= implode(',', $value).",";
      }
    }

    if (!empty($vialliset)) $vialliset = substr($vialliset, 0, -1);

    return array($hyvat, $vialliset);
  }
}

if (!function_exists("vlas_dateconv")) {
  function vlas_dateconv($date) {
    //kääntää mysqln vvvv-kk-mm muodon muotoon vvvvkkmm
    return substr($date, 0, 4).substr($date, 5, 2).substr($date, 8, 2);
  }
}

if (!function_exists("spyconv")) {
  //tehdään viitteestä SPY standardia eli 20 merkkiä etunollilla
  function spyconv($spy) {
    return $spy = sprintf("%020.020s", $spy);
  }
}

if (!function_exists("laskunkieli")) {
  function laskunkieli($liitostunnus, $kieli) {
    global $kukarow, $yhtiorow;

    $asiakas_apu_query = "SELECT *
                          FROM asiakas
                          WHERE yhtio = '$kukarow[yhtio]'
                          AND tunnus  = '$liitostunnus'";
    $asiakas_apu_res = pupe_query($asiakas_apu_query);
    $asiakas_apu_row = mysqli_fetch_assoc($asiakas_apu_res);

    if (strtoupper(trim($asiakas_apu_row["kieli"])) == "SE") {
      $laskun_kieli = "SE";
    }
    elseif (strtoupper(trim($asiakas_apu_row["kieli"])) == "EE") {
      $laskun_kieli = "EE";
    }
    elseif (strtoupper(trim($asiakas_apu_row["kieli"])) == "FI") {
      $laskun_kieli = "FI";
    }
    else {
      $laskun_kieli = trim(strtoupper($yhtiorow["kieli"]));
    }

    if ($kieli != "") {
      $laskun_kieli = trim(strtoupper($kieli));
    }

    return $laskun_kieli;
  }
}

if (!function_exists("pp")) {
  //pilkut pisteiksi
  function pp($muuttuja, $round="", $rmax="", $rmin="") {
    if (strlen($round)>0) {
      if (strlen($rmax)>0 and $rmax<$round) {
        $round = $rmax;
      }
      if (strlen($rmin)>0 and $rmin>$round) {
        $round = $rmin;
      }

      return $muuttuja = number_format($muuttuja, $round, ",", "");
    }
    else {
      return $muuttuja = str_replace(".", ",", $muuttuja);
    }
  }
}

if ($php_cli) {

  if (empty($argv[1])) {
    echo "Anna yhtiö!!!\n";
    exit(1);
  }

  // otetaan includepath aina rootista
  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)).PATH_SEPARATOR."/usr/share/pear");
  error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
  ini_set("display_errors", 0);

  // otetaan tietokanta connect
  require "inc/connect.inc";
  require "inc/functions.inc";

  // Logitetaan ajo
  cron_log();

  $_yhtio   = pupesoft_cleanstring($argv[1]);
  $yhtiorow = hae_yhtion_parametrit($_yhtio);

  // Kukarow setataan esim editilaus_in.inc:ssä
  if (!isset($kukarow)) {
    $kukarow = hae_kukarow('admin', $yhtiorow['yhtio']);

    // Komentoriviltä ku ajetaan, niin ei haluta posteja admin-käyttäjälle
    $kukarow["eposti"] = "";
  }

  if (!is_array($kukarow)) {
    exit(1);
  }

  if (isset($argv[2])) {
    $kieli = pupesoft_cleanstring($argv[2]);
  }

  // Pupeasennuksen root
  $pupe_root_polku = dirname(dirname(__FILE__));

  $laskkk   = "";
  $laskpp   = "";
  $laskvv   = "";
  $eilinen  = "";
  $eiketjut = "";
  $viennit  = "";
  $nosoap = "";

  // Laskutetaanko myös vientitilauksia
  if (!empty($argv[3]) and substr($argv[3], 0, 7) == "vienti_") {
    $argv[3] = substr($argv[3], 7);
    $viennit = "KYLLA";
  }

  // jos komentorivin kolmas arg on "eilinen" niin edelliselle laskutus päivälle, ohitetaan laskutusviikonpäivät
  if ($argv[3] == "eilinen") {
    $laskkk  = date("m", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
    $laskpp  = date("d", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
    $laskvv  = date("Y", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
  }

  // jos komentorivin kolmas arg on "eilinen" niin edelliselle laskutus päivälle
  if ($argv[3] == "eilinen_eikaikki") {
    $laskkk  = date("m", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
    $laskpp  = date("d", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
    $laskvv  = date("Y", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
    $eilinen = "eilinen_eikaikki";
  }

  // jos komentorivin kolmas arg on "eiketjut"
  if ($argv[3] == "eiketjut") {
    $eiketjut = "KYLLA";
  }

  // jos komentorivin kolmas arg on "kaikki"
  if ($argv[3] == "kaikki") {
    $laskutakaikki = "ON";
  }

  // jos kuukausilaskutus on päällä (cron.monthly), niin ei välttämättä haluta ajaa päivälaskutusta
  // kukauden vikana päivänä, koska silloin asiakkaalle saattaa mennä kaksi laskua vikana päivänä jos
  // laskutusviikonpäivät osuu sillai kivasti
  if ($argv[3] == "skippaa_kuukauden_vikapaiva" and date("d") == date("t")) {
    echo "HUOM: Päivälaskutusta ei ajeta kuukauden vikana päivänä!<br>\n";
    exit;
  }

  $tee = "TARKISTA";
}
elseif (strpos($_SERVER['SCRIPT_NAME'], "verkkolasku.php") !== FALSE) {

  if (isset($_POST["tee"])) {
    if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto = 1;
    if (!isset($_POST["kaunisnimi"])) $_POST["kaunisnimi"] = ""; // MUOKKAUS: isset()
    if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
  }

  require "../inc/parametrit.inc";
}

// Timeout in 5h
ini_set("mysql.connect_timeout", 18000);
ini_set("max_execution_time", 18000);

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("$pupe_root_polku/dataout/".basename($filenimi));
  exit;
}
else {
  // Nollataan muuttujat
  $tulostettavat        = array();
  $tulostettavat_email  = array();
  $tulostettavat_ulkvar = array();
  $tulos_ulos           = "";

  $verkkolaskuputkeen_pupevoice = array();
  $verkkolaskuputkeen_finvoice  = array();
  $verkkolaskuputkeen_suora     = array();
  $verkkolaskuputkeen_elmaedi   = array();
  $verkkolaskuputkeen_apix      = array();

  if (!isset($silent)) {
    $silent = "";
  }
  if (!isset($tee)) {
    $tee = "";
  }
  if (!isset($kieli)) {
    $kieli = "";
  }
  if (!isset($velox_laskutus)) {
    $velox_laskutus = "";
  }

  if ($silent == "") {
    $tulos_ulos .= "<font class='head'>".t("Laskutusajo")."</font><hr>\n";
  }

  if ($tee == 'TARKISTA') {

    $poikkeava_pvm = "";

    //syötetty päivämäärä
    if ($laskkk != '' or $laskpp != '' or $laskvv != '') {

      // Korjataan vuosilukua
      if ($laskvv < 1000) $laskvv += 2000;

      // Etunollat mukaan
      $laskkk = sprintf('%02d', $laskkk);
      $laskpp = sprintf('%02d', $laskpp);

      // Katotaan ensin, että se on ollenkaan validi
      if (checkdate($laskkk, $laskpp, $laskvv)) {

        //vertaillaan tilikauteen
        list($vv1, $kk1, $pp1) = explode("-", $yhtiorow["myyntireskontrakausi_alku"]);
        list($vv2, $kk2, $pp2) = explode("-", $yhtiorow["myyntireskontrakausi_loppu"]);

        $tilialku  = (int) date('Ymd', mktime(0, 0, 0, $kk1, $pp1, $vv1));
        $tililoppu = (int) date('Ymd', mktime(0, 0, 0, $kk2, $pp2, $vv2));
        $syotetty  = (int) date('Ymd', mktime(0, 0, 0, $laskkk, $laskpp, $laskvv));
        $tanaan    = (int) date('Ymd');

        if ($syotetty < $tilialku or $syotetty > $tililoppu) {
          $tulos_ulos .= "<br>\n".t("VIRHE: Syötetty päivämäärä ei sisälly kuluvaan tilikauteen!")."<br>\n<br>\n";
          $tee = "";
        }
        else {

          if ($syotetty > $tanaan and $yhtiorow['laskutus_tulevaisuuteen'] != 'S') {
            //tulevaisuudessa ei voida laskuttaa
            $tulos_ulos .= "<br>\n".t("VIRHE: Syötetty päivämäärä on tulevaisuudessa, ei voida laskuttaa!")."<br>\n<br>\n";
            $tee = "";
          }
          else {
            //homma on ok
            $poikkeava_pvm = $syotetty;

            //ohitetaan myös laskutusviikonpäivät jos poikkeava päivämäärä on syötetty
            if (!isset($eilinen) or $eilinen != "eilinen_eikaikki") {
              $laskutakaikki = "ON";
            }

            $tee = "LASKUTA";
          }
        }
      }
      else {
        $tulos_ulos .= "<br>\n".t("VIRHE: Syötetty päivämäärä on virheellinen, tarkista se!")."<br>\n<br>\n";
        $tee = "";
      }
    }
    else {
      //poikkeavaa päivämäärää ei ole, eli laskutetaan
      $tee = "LASKUTA";
    }
  }

  if ($tee == "LASKUTA") {
    //Tiedostojen polut ja nimet
    //keksitään uudelle failille joku varmasti uniikki nimi:
    $nimixml = "$pupe_root_polku/dataout/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(), true)).".xml";

    //  Itellan iPost vaatii siirtoon vähän oman nimen..
    if ($yhtiorow["verkkolasku_lah"] == "iPost") {
      $nimifinvoice = "$pupe_root_polku/dataout/TRANSFER_IPOST-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(), true))."_finvoice.xml";
    }
    elseif ($yhtiorow["verkkolasku_lah"] == "apix") {
      $nimifinvoice = "/tmp/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(), true))."_finvoice.xml";
    }
    else {
      $nimifinvoice = "$pupe_root_polku/dataout/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(), true))."_finvoice.xml";
    }

    $nimisisainenfinvoice = "$pupe_root_polku/dataout/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(), true))."_sisainenfinvoice.xml";

    $nimiedi = "$pupe_root_polku/dataout/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(), true)).".edi";

    //Pupevoice xml-dataa
    if (!$tootxml = fopen($nimixml, "w")) die("Filen $nimixml luonti epäonnistui!");

    //Finvoice xml-dataa
    if (!$tootfinvoice = fopen($nimifinvoice, "w")) die("Filen $nimifinvoice luonti epäonnistui!");

    //Elma-EDI-inhouse dataa (EIH-1.4.0)
    if (!$tootedi = fopen($nimiedi, "w")) die("Filen $nimiedi luonti epäonnistui!");

    //Sisäinenfinvoice xml-dataa
    if (!$tootsisainenfinvoice = fopen($nimisisainenfinvoice, "w")) die("Filen $nimisisainenfinvoice luonti epäonnistui!");

    // lock tables
    $query = "LOCK TABLES
              asiakas READ,
              asiakasalennus READ,
              asiakasalennus as asale1 READ,
              asiakasalennus as asale2 READ,
              asiakasalennus as asale3 READ,
              asiakasalennus as asale4 READ,
              asiakashinta READ,
              asiakashinta as ashin1 READ,
              asiakashinta as ashin2 READ,
              asiakaskommentti READ,
              avainsana as a READ,
              avainsana as avainsana_kieli READ,
              avainsana as b READ,
              avainsana READ,
              directdebit READ,
              dynaaminen_puu AS node READ,
              dynaaminen_puu AS parent READ,
              etaisyydet READ,
              factoring READ,
              inventointilistarivi READ,
              hinnasto READ,
              kalenteri WRITE,
              kassalipas READ,
              kirjoittimet READ,
              kuka WRITE,
              lasku AS lx_otsikko WRITE,
              lasku AS ux_otsikko WRITE,
              lasku WRITE,
              laskun_lisatiedot WRITE,
              liitetiedostot READ,
              maat READ,
              maksuehto READ,
              maksupositio READ,
              pakkaus READ,
              pankkiyhteystiedot READ,
              panttitili WRITE,
              perusalennus READ,
              puun_alkio READ,
              rahtikirjat READ,
              rahtimaksut READ,
              sanakirja WRITE,
              sarjanumeroseuranta WRITE,
              sarjanumeroseuranta_arvomuutos READ,
              tapahtuma WRITE,
              tilausrivi as t READ,
              tilausrivi as t2 WRITE,
              tilausrivi as t3 READ,
              tilausrivi WRITE,
              tilausrivin_lisatiedot as tl2 WRITE,
              tilausrivin_lisatiedot as tlt2 WRITE,
              tilausrivin_lisatiedot as tlt3 WRITE,
              tilausrivin_lisatiedot t_lisa READ,
              tilausrivin_lisatiedot WRITE,
              tili READ,
              tiliointi WRITE,
              kustannuspaikka as kustp READ,
              kustannuspaikka as kohde READ,
              kustannuspaikka as projekti READ,
              toimitustapa READ,
              tullinimike READ,
              tuote READ,
              tuotepaikat WRITE,
              tuoteperhe READ,
              tuotteen_alv READ,
              tuotteen_avainsanat READ,
              tuotteen_toimittajat READ,
              tyomaarays READ,
              varastopaikat READ,
              yhteyshenkilo as kk READ,
              yhteyshenkilo as kt READ,
              yhtio READ,
              yhtion_parametrit READ,
              yhtion_toimipaikat READ,
              tilausrivin_lisatiedot AS tl READ,
              valuu READ,
              valuu_historia READ,
              varastopaikat AS v_lahdevarasto READ,
              varastopaikat AS v_kohdevarasto READ,
              korvaavat_kiellot READ,
              oikeu READ,
              toimi READ,
              yhtion_toimipaikat_parametrit READ,
              varaston_hyllypaikat READ";
    $locre = pupe_query($query);

    //Haetaan tarvittavat funktiot aineistojen tekoa varten
    require "verkkolasku_elmaedi.inc";

    if ($yhtiorow["finvoice_versio"] == "3") {
      require "verkkolasku_finvoice_301.inc";
    }
    else if ($yhtiorow["finvoice_versio"] == "2") {
      require "verkkolasku_finvoice_201.inc";
    }
    else {
      require "verkkolasku_finvoice.inc";
    }

    require "verkkolasku_pupevoice.inc";

    // haetaan kaikki tilaukset jotka on toimitettu ja kuuluu laskuttaa tänään (tätä resulttia käytetään alhaalla lisää)
    $lasklisa = "";
    $lasklisa_eikateiset = "";

    // tarkistetaan tässä tuleeko laskutusviikonpäivät ohittaa
    // ohitetaan jos ruksi on ruksattu tai poikkeava laskutuspäivämäärä on syötetty
    if (!isset($laskutakaikki) or $laskutakaikki == "") {

      // Mikä viikonpäivä tänään on 1-7.. 1=sunnuntai, 2=maanantai, jne...
      if (isset($eilinen) and $eilinen == "eilinen_eikaikki") {
        $today = date("w", mktime(0, 0, 0, $laskkk, $laskpp, $laskvv)) +1;
        $vkopva_curdate = "'$laskvv-$laskkk-$laskpp'";
      }
      else {
        $today = date("w") + 1;
        $vkopva_curdate = "curdate()";
      }

      // Kuukauden eka päivä
      $eka_pv = laskutuspaiva("eka");

      // Kuukauden keskimmäinen päivä
      $keski_pv = laskutuspaiva("keski");

      // Kuukauden viimeinen päivä
      $vika_pv = laskutuspaiva("vika");

      $lasklisa .= " and (lasku.laskutusvkopv = 0 or
                 (lasku.laskutusvkopv = $today) or
                 (lasku.laskutusvkopv = -1 and {$vkopva_curdate} = '$vika_pv') or
                 (lasku.laskutusvkopv = -2 and {$vkopva_curdate} = '$eka_pv') or
                 (lasku.laskutusvkopv = -3 and {$vkopva_curdate} = '$keski_pv') or
                 (lasku.laskutusvkopv = -4 and {$vkopva_curdate} in ('$keski_pv','$vika_pv')) or
                 (lasku.laskutusvkopv = -5 and {$vkopva_curdate} in ('$eka_pv','$keski_pv'))) ";
    }

    // katotaan halutaanko laskuttaa vaan laskut joita *EI SAA* ketjuttaa
    if (isset($eiketjut) and $eiketjut == "KYLLA") {
      $lasklisa .= " and lasku.ketjutus != '' ";
    }

    if (isset($laskutettavat) and $laskutettavat != "") {
      // Laskutetaan vain tietyt tilausket
      $lasklisa .= " and lasku.tunnus in ($laskutettavat) ";
    }
    elseif (php_sapi_name() == 'cli') {
      // Komentoriviltä ei ikinä laskuteta käteismyyntejä ($php_cli ei kelpaa, koska $editil_cli virittää sen myös)
      $lasklisa_eikateiset = " JOIN maksuehto ON (lasku.yhtio=maksuehto.yhtio and lasku.maksuehto=maksuehto.tunnus and maksuehto.kateinen='')";
    }

    // Päivitetään laskutettavat vientitilaukset toimitetuiksi
    if (php_sapi_name() == 'cli' and $viennit == "KYLLA") {
      $query = "UPDATE lasku
                JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and lasku.tunnus = tilausrivi.otunnus and tilausrivi.tyyppi='L')
                JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno)
                {$lasklisa_eikateiset}
                SET lasku.alatila = 'D'
                WHERE lasku.yhtio = '$kukarow[yhtio]'
                and lasku.tila = 'L'
                and lasku.chn != '999'
                and (tilausrivi.keratty != '' or tuote.ei_saldoa!='')
                and tilausrivi.varattu != 0
                and lasku.alatila = 'E'
                and lasku.vienti != ''
                {$lasklisa}";
      pupe_query($query);
    }

    $tulos_ulos_maksusoppari = "";
    $tulos_ulos_sarjanumerot = "";

    // alustetaan muuttujia
    $laskutus_esto_saldot = array();

    $query_ale_lisa = generoi_alekentta('M');

    // saldovirhe_esto_laskutus-parametri 'H', jolla voidaan estää tilauksen laskutus, jos tilauksen yhdeltäkin tuotteelta saldo menee miinukselle
    // kehahinvirhe_esto_laskutus-parametri 'N', Estetaan laskutus mikali keskihankintahinta on 0.00 tai tuotteen kate on negatiivinen
    // Eutukäteeen lmaksettu verkkokauppatilaus ($editil_cli) laskutetaan vaikka saldo ei ihan riittäisikään
    // Mikäli halutaan laskuttaa tulevaisuuteen niin kaikki tilauksen tuotteet täytyy olla saldottomia
    if (empty($editil_cli) and ($yhtiorow['saldovirhe_esto_laskutus'] == 'H' or $yhtiorow['kehahinvirhe_esto_laskutus'] == 'N' or $yhtiorow['laskutus_tulevaisuuteen'] == 'S')) {

      $query = "SELECT
                tilausrivi.tuoteno,
                if(tuote.epakurantti100pvm='0000-00-00', if(tuote.epakurantti75pvm='0000-00-00', if(tuote.epakurantti50pvm='0000-00-00', if(tuote.epakurantti25pvm='0000-00-00', tuote.kehahin, tuote.kehahin*0.75), tuote.kehahin*0.5), tuote.kehahin*0.25), 0.01) kehahin,
                sum(tilausrivi.varattu) varattu,
                round(min(tilausrivi.hinta / if ('{$yhtiorow["alv_kasittely"]}' = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * {$query_ale_lisa}), $yhtiorow[hintapyoristys]) min_kplhinta,
                group_concat(distinct lasku.tunnus) tunnukset
                FROM lasku
                {$lasklisa_eikateiset}
                JOIN tilausrivi on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi = 'L' and tilausrivi.var not in ('P','J','O','S'))
                JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.ei_saldoa = '')
                WHERE lasku.yhtio  = '$kukarow[yhtio]'
                and lasku.tila     = 'L'
                and lasku.alatila  = 'D'
                and lasku.viite    = ''
                and lasku.chn     != '999'
                {$lasklisa}
                GROUP BY 1, 2";
      $lasku_chk_res = pupe_query($query);

      while ($lasku_chk_row = mysqli_fetch_assoc($lasku_chk_res)) {

        // Mikäli halutaan laskuttaa tulevaisuuteen niin kaikki tilauksen tuotteet täytyy olla saldottomia
        if ($syotetty > $tanaan and $yhtiorow['laskutus_tulevaisuuteen'] == 'S') {
          $lasklisa .= " and lasku.tunnus not in ({$lasku_chk_row['tunnukset']}) ";
          $tulos_ulos .= "<br>\n".t("Tuotevirheet").":<br>\n".t("Tilausta")." {$lasku_chk_row['tunnukset']} ".t("ei voida laskuttaa, koska tilauksien kaikki tuotteet eivät olleet saldottomia")."!<br>\n";
        }

        // Mikäli laskutuksessa tuotteen varastosaldo vähenee negatiiviseksi, hylätään KAIKKI tilaukset, joilla on kyseistä tuotetta
        if (empty($editil_cli) and $yhtiorow['saldovirhe_esto_laskutus'] == 'H') {
          $query = "SELECT sum(saldo) saldo
                    FROM tuotepaikat
                    WHERE yhtio = '$kukarow[yhtio]'
                    AND tuoteno = '$lasku_chk_row[tuoteno]'";
          $saldo_chk_res = pupe_query($query);
          $saldo_chk_row = mysqli_fetch_assoc($saldo_chk_res);

          if ($saldo_chk_row["saldo"] - $lasku_chk_row["varattu"] < 0) {
            $lasklisa .= " and lasku.tunnus not in ($lasku_chk_row[tunnukset]) ";
            $tulos_ulos .= "<br>\n".t("Saldovirheet").":<br>\n".t("Tilausta")." $lasku_chk_row[tunnukset] ".t("ei voida laskuttaa, koska tuotteen")." $lasku_chk_row[tuoteno] ".t("saldo ei riitä")."!<br>\n";
          }
        }

        // Estetään laskutus mikäli keskihankintahinta on nolla tai rivin kate on negatiivinen
        if ($yhtiorow['kehahinvirhe_esto_laskutus'] == 'N') {

          if ($lasku_chk_row["kehahin"] <= 0) {
            $lasklisa .= " and lasku.tunnus not in ($lasku_chk_row[tunnukset]) ";
            $tulos_ulos .= "<br>\n".t("Hintavirhe").":<br>\n".t("Tilausta")." $lasku_chk_row[tunnukset] ".t("ei voida laskuttaa, koska tuotteen keskihankintahinta on nolla")."<br>\n";
          }

          if (($lasku_chk_row["min_kplhinta"] - $lasku_chk_row["kehahin"]) < 0) {
            $lasklisa .= " and lasku.tunnus not in ($lasku_chk_row[tunnukset]) ";
            $tulos_ulos .= "<br>\n".t("Hintavirhe").":<br>\n".t("Tilausta")." $lasku_chk_row[tunnukset] ".t("ei voida laskuttaa, koska tuotteen kate on negatiivinen")."<br>\n";
          }
        }
      }
    }

    //haetaan kaikki laskutettavat tilaukset ja tehdään maksuehtosplittaukset ja muita tarkistuksia jos niitä on
    $query = "SELECT lasku.*
              FROM lasku
              {$lasklisa_eikateiset}
              WHERE lasku.yhtio  = '$kukarow[yhtio]'
              and lasku.tila     = 'L'
              and lasku.alatila  = 'D'
              and lasku.viite    = ''
              and lasku.chn     != '999'
              $lasklisa
              ORDER BY lasku.tunnus";
    $res = pupe_query($query);

    while ($laskurow = mysqli_fetch_assoc($res)) {

      // Tsekataan maskuehto
      $query = "SELECT tunnus
                FROM maksuehto
                WHERE yhtio  = '$kukarow[yhtio]'
                and tunnus   = '$laskurow[maksuehto]'
                and kaytossa = ''";
      $matsek = pupe_query($query);

      if (mysqli_num_rows($matsek) == 0) {
        // Oho ei löytnyt, katotaan onko asiakkaalla oletus kunnossa?
        $query = "SELECT asiakas.maksuehto
                  FROM asiakas
                  JOIN maksuehto ON asiakas.yhtio=maksuehto.yhtio and asiakas.maksuehto=maksuehto.tunnus and maksuehto.kaytossa=''
                  WHERE asiakas.yhtio = '$kukarow[yhtio]'
                  AND asiakas.tunnus  = '$laskurow[liitostunnus]'";
        $matsek = pupe_query($query);

        if (mysqli_num_rows($matsek) == 1) {
          $marow = mysqli_fetch_assoc($matsek);

          $query = "UPDATE lasku
                    SET maksuehto = {$marow["maksuehto"]}
                    WHERE tunnus = '$laskurow[tunnus]'";
          $updres = pupe_query($query);
        }
        else {
          // Jos tilauksella oli huono maksuehto, niin ei laskuteta
          $lasklisa .= " and lasku.tunnus != '$laskurow[tunnus]' ";

          $tulos_ulos .= "<br>\n".t("Maksuehtovirhe").":<br>\n".t("Tilausta")." $laskurow[tunnus] ".t("ei voida laskuttaa, koska maksuehto on virheellinen")."!<br>\n";
        }
      }

      // SALLITTAAN FIFO PERIAATTELLA SALDOJA
      if (empty($editil_cli) and ($yhtiorow['saldovirhe_esto_laskutus'] == 'K' or $yhtiorow['saldovirhe_esto_laskutus'] == 'V')) {

        // haetaan tilausriveiltä tuotenumero ja summataan varatut kappaleet
        $query = "SELECT tilausrivi.tuoteno, sum(tilausrivi.varattu) varattu
                  FROM tilausrivi
                  JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.ei_saldoa = '')
                  WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                  and tilausrivi.otunnus = '$laskurow[tunnus]'
                  and tilausrivi.tyyppi  = 'L'
                  and tilausrivi.var     not in ('P','J','O','S')
                  GROUP BY 1";
        $tuoteno_varattu_chk_res = pupe_query($query);

        while ($tuoteno_varattu_chk_row = mysqli_fetch_assoc($tuoteno_varattu_chk_res)) {

          if (!isset($laskutus_esto_saldot[$tuoteno_varattu_chk_row['tuoteno']])) {

            if ($yhtiorow['saldovirhe_esto_laskutus'] == 'V') {
              // haetaan saldo tuotepaikoilta vain laskulla olevasta varastosta
              $query = "SELECT sum(tuotepaikat.saldo) saldo
                        FROM tuotepaikat
                        WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
                        AND tuotepaikat.tuoteno = '$tuoteno_varattu_chk_row[tuoteno]'
                        AND tuotepaikat.varasto = '$laskurow[varasto]'";
            }
            else {
              // haetaan saldo tuotepaikoilta kaikista varastoista
              $query = "SELECT sum(tuotepaikat.saldo) saldo
                        FROM tuotepaikat
                        WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
                        AND tuotepaikat.tuoteno = '$tuoteno_varattu_chk_row[tuoteno]'";
            }
            $saldo_chk_res = pupe_query($query);
            $saldo_chk_row = mysqli_fetch_assoc($saldo_chk_res);

            $laskutus_esto_saldot[$tuoteno_varattu_chk_row['tuoteno']] = $saldo_chk_row['saldo'];
          }

          if ($laskutus_esto_saldot[$tuoteno_varattu_chk_row['tuoteno']] - $tuoteno_varattu_chk_row['varattu'] < 0) {

            $lasklisa .= " and lasku.tunnus != '$laskurow[tunnus]' ";
            $tulos_ulos .= "<br>\n".t("Saldovirheet").":<br>\n".t("Tilausta")." $laskurow[tunnus] ".t("ei voida laskuttaa, koska tuotteen")." $tuoteno_varattu_chk_row[tuoteno] ".t("saldo ei riitä")."!<br>\n";

            // skipataan seuraavaan laskuun
            continue 2;
          }

          $laskutus_esto_saldot[$tuoteno_varattu_chk_row['tuoteno']] -= $tuoteno_varattu_chk_row['varattu'];

        }
      }

      // Tsekataan ettei lipsahda JT-rivejä laskutukseen jos osaotoimitus on kielletty
      if ($yhtiorow["varaako_jt_saldoa"] != "") {
        $lisavarattu = " + tilausrivi.varattu";
      }
      else {
        $lisavarattu = "";
      }

      $query = "SELECT sum(if (tilausrivi.var = 'J' and tilausrivi.jt $lisavarattu > 0, 1, 0)) jteet
                FROM tilausrivi
                WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                and tilausrivi.otunnus = '$laskurow[tunnus]'
                and tilausrivi.tyyppi  = 'L'
                and tilausrivi.var     = 'J'";
      $sarjares1 = pupe_query($query);
      $srow1 = mysqli_fetch_assoc($sarjares1);

      if ($srow1["jteet"] > 0 and $laskurow["osatoimitus"] != '') {
        // Jos tilauksella oli yksikin jt-rivi ja osatoimitus on kielletty
        $lasklisa .= " and lasku.tunnus != '$laskurow[tunnus]' ";

        if ($silent == "" or $silent == "VIENTI") {
          $tulos_ulos .= "<br>\n".sprintf(t("Tilauksella %s oli JT-rivejä ja osatoimitusta ei tehdä, eli se jätettiin odottamaan JT-tuotteita."), $laskurow["tunnus"])."<br>\n";
        }
      }

      // Onko asiakkalla panttitili
      $query = "SELECT panttitili
                FROM asiakas
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$laskurow['liitostunnus']}'";
      $asiakas_panttitili_chk_res = pupe_query($query);
      $asiakas_panttitili_chk_row = mysqli_fetch_assoc($asiakas_panttitili_chk_res);

      // Tsekataan vähän alveja ja sarjanumerojuttuja
      $query = "SELECT tuote.sarjanumeroseuranta, tilausrivi.tunnus, tilausrivi.varattu, tilausrivi.tuoteno, tilausrivin_lisatiedot.osto_vai_hyvitys, tilausrivi.alv, tuote.kehahin, tuote.ei_saldoa, tuote.panttitili, tilausrivi.var2
                FROM tilausrivi use index (yhtio_otunnus)
                JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno
                LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
                WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                and tilausrivi.otunnus = '$laskurow[tunnus]'
                and tilausrivi.tyyppi  = 'L'
                and tilausrivi.var     not in ('P','J','O','S')";
      $sarjares1 = pupe_query($query);

      while ($srow1 = mysqli_fetch_assoc($sarjares1)) {

        // Tsekataan onko tuotetta ikinä ostettu jos kehahinarvio_ennen_ensituloa-parametri on päällä
        if ($yhtiorow["kehahinarvio_ennen_ensituloa"] != "" and $srow1["kehahin"] != 0 and $srow1["ei_saldoa"] == "") {

          if ($poikkeava_pvm != "") {
            $tapapvm = $laskvv."-".$laskkk."-".$laskpp." 23:59:59";
          }
          else {
            $tapapvm = date("Y-m-d H:i:s");
          }

          $query = "SELECT tunnus
                    FROM tapahtuma
                    WHERE yhtio  = '$kukarow[yhtio]'
                    and laji     in ('tulo', 'valmistus')
                    and laadittu < '$tapapvm'
                    and tuoteno  = '$srow1[tuoteno]'";
          $sarjares2 = pupe_query($query);

          if (mysqli_num_rows($sarjares2) == 0) {
            $lasklisa .= " and lasku.tunnus != '$laskurow[tunnus]' ";

            if ($silent == "" or $silent == "VIENTI") {
              $tulos_ulos .= "<br>\n"."<font class='error'>".t("Tilausta ei voida laskuttaa arvioidulla keskihankintahinnalla").": $laskurow[tunnus] $srow1[tuoteno]!!!</font><br>\n";
            }
          }
        }

        // Tsekataan alvit
        $query = "SELECT group_concat(distinct concat_ws(',', selite, selite+500, selite+600)) alvit
                  FROM avainsana
                  WHERE yhtio = '$kukarow[yhtio]'
                  and laji    in ('ALV','ALVULK')";
        $sarjares2 = pupe_query($query);
        $srow2 = mysqli_fetch_assoc($sarjares2);

        if (!in_array($srow1["alv"], explode(",", $srow2["alvit"]))) {
          $lasklisa .= " and lasku.tunnus != '$laskurow[tunnus]' ";

          if ($silent == "" or $silent == "VIENTI") {
            $tulos_ulos .= "<br>\n".t("Tilauksella virheellisiä verokantoja").": $laskurow[tunnus] $srow1[tuoteno] $srow1[alv]!!!<br>\n";
          }
        }

        if ($srow1["sarjanumeroseuranta"] != "") {

          if ($srow1["varattu"] < 0) {
            $tunken = "ostorivitunnus";
          }
          else {
            $tunken = "myyntirivitunnus";
          }

          if ($srow1["sarjanumeroseuranta"] == "S" or $srow1["sarjanumeroseuranta"] == "T" or $srow1["sarjanumeroseuranta"] == "V") {
            $query = "SELECT count(distinct sarjanumero) kpl
                      FROM sarjanumeroseuranta
                      WHERE yhtio = '$kukarow[yhtio]'
                      and tuoteno = '$srow1[tuoteno]'
                      and $tunken = '$srow1[tunnus]'";
            $sarjares2 = pupe_query($query);
            $srow2 = mysqli_fetch_assoc($sarjares2);

            if ($srow2["kpl"] != abs($srow1["varattu"])) {
              $lasklisa .= " and lasku.tunnus != '$laskurow[tunnus]' ";

              if ($silent == "" or $silent == "VIENTI") {
                $tulos_ulos_sarjanumerot .= t("Tilaukselta puuttuu sarjanumeroita, ei voida laskuttaa").": $laskurow[tunnus] $srow1[tuoteno] $laskurow[nimi]!!!<br>\n";
              }
            }
          }
          else {
            $query = "SELECT count(*) kpl
                      FROM sarjanumeroseuranta
                      WHERE yhtio = '$kukarow[yhtio]'
                      and tuoteno = '$srow1[tuoteno]'
                      and $tunken = '$srow1[tunnus]'";
            $sarjares2 = pupe_query($query);
            $srow2 = mysqli_fetch_assoc($sarjares2);

            if ($srow2["kpl"] != 1) {
              $lasklisa .= " and lasku.tunnus != '$laskurow[tunnus]' ";

              if ($silent == "" or $silent == "VIENTI") {
                $tulos_ulos_sarjanumerot .= t("Tilaukselta puuttuu eränumeroita, ei voida laskuttaa").": $laskurow[tunnus] $srow1[tuoteno] $laskurow[nimi]!!!<br>\n";
              }
            }
          }

          if ($srow1["sarjanumeroseuranta"] == "S" and $srow1["varattu"] < 0 and $srow1["osto_vai_hyvitys"] == "") {
            //Jos tuotteella on sarjanumero ja kyseessä on HYVITYSTÄ

            //Tähän hyvitysriviin liitetyt sarjanumerot
            $query = "SELECT sarjanumero, kaytetty, tunnus
                      FROM sarjanumeroseuranta
                      WHERE yhtio        = '$kukarow[yhtio]'
                      and ostorivitunnus = '$srow1[tunnus]'";
            $sarjares = pupe_query($query);

            while ($sarjarowx = mysqli_fetch_assoc($sarjares)) {

              // Haetaan hyvitettävien myyntirivien kautta alkuperäiset ostorivit
              $query  = "SELECT sarjanumeroseuranta.tunnus
                         FROM sarjanumeroseuranta
                         JOIN tilausrivi use index (PRIMARY) ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus
                         WHERE sarjanumeroseuranta.yhtio           = '$kukarow[yhtio]'
                         and sarjanumeroseuranta.tuoteno           = '$srow1[tuoteno]'
                         and sarjanumeroseuranta.sarjanumero       = '$sarjarowx[sarjanumero]'
                         and sarjanumeroseuranta.kaytetty          = '$sarjarowx[kaytetty]'
                         and sarjanumeroseuranta.myyntirivitunnus  > 0
                         and sarjanumeroseuranta.ostorivitunnus    > 0
                         and sarjanumeroseuranta.tunnus           != '$sarjarowx[tunnus]'
                         ORDER BY sarjanumeroseuranta.tunnus DESC
                         LIMIT 1";
              $sarjares12 = pupe_query($query);

              if (mysqli_num_rows($sarjares12) == 0) {
                // Jos sarjanumeroa ei ikinä olla ostettu, mutta se on myyty ja nyt halutaan perua kaupat
                $query  = "SELECT sarjanumeroseuranta.tunnus
                           FROM sarjanumeroseuranta
                           JOIN tilausrivi t2 use index (PRIMARY) ON t2.yhtio=sarjanumeroseuranta.yhtio and t2.tunnus=sarjanumeroseuranta.ostorivitunnus
                           JOIN tilausrivi t3 use index (PRIMARY) ON t3.yhtio=sarjanumeroseuranta.yhtio and t3.tunnus=sarjanumeroseuranta.myyntirivitunnus and t3.uusiotunnus>0
                           WHERE sarjanumeroseuranta.yhtio          = '$kukarow[yhtio]'
                           and sarjanumeroseuranta.tuoteno          = '$srow1[tuoteno]'
                           and sarjanumeroseuranta.sarjanumero      = '$sarjarowx[sarjanumero]'
                           and sarjanumeroseuranta.kaytetty         = '$sarjarowx[kaytetty]'
                           and sarjanumeroseuranta.myyntirivitunnus > 0
                           and sarjanumeroseuranta.ostorivitunnus   > 0";
                $sarjares12 = pupe_query($query);

                if (mysqli_num_rows($sarjares12) != 1) {
                  $lasklisa .= " and lasku.tunnus != '$laskurow[tunnus]' ";

                  if ($silent == "" or $silent == "VIENTI") {
                    $tulos_ulos_sarjanumerot .= t("Hyvitettävää riviä ei löydy, ei voida laskuttaa").": $laskurow[tunnus] $srow1[tuoteno] $sarjarowx[sarjanumero] $laskurow[nimi]!!!<br>\n";
                  }
                }
              }
            }
          }

          $query = "SELECT distinct kaytetty
                    FROM sarjanumeroseuranta
                    WHERE yhtio = '$kukarow[yhtio]'
                    and tuoteno = '$srow1[tuoteno]'
                    and $tunken = '$srow1[tunnus]'";
          $sarres = pupe_query($query);

          if (mysqli_num_rows($sarres) > 1) {
            $lasklisa .= " and lasku.tunnus != '$laskurow[tunnus]' ";

            if ($silent == "" or $silent == "VIENTI") {
              $tulos_ulos_sarjanumerot .= t("Riviin ei voi liittää sekä käytettyjä että uusia sarjanumeroita").": $laskurow[tunnus] $srow1[tuoteno] $laskurow[nimi]!!!<br>\n";
            }
          }

          // ollaan tekemässä myyntiä
          if ($tunken == "myyntirivitunnus") {
            $query = "SELECT sum(if (ifnull(tilausrivi.rivihinta, 0) = 0, 1, 0)) ei_ostohintaa
                      FROM sarjanumeroseuranta
                      LEFT JOIN tilausrivi use index (PRIMARY) ON (tilausrivi.yhtio = sarjanumeroseuranta.yhtio and tilausrivi.tunnus = sarjanumeroseuranta.ostorivitunnus)
                      LEFT JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio=tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus=tilausrivin_lisatiedot.tilausrivitunnus)
                      WHERE sarjanumeroseuranta.yhtio  = '$kukarow[yhtio]'
                      and sarjanumeroseuranta.tuoteno  = '$srow1[tuoteno]'
                      and sarjanumeroseuranta.$tunken  = '$srow1[tunnus]'
                      and sarjanumeroseuranta.kaytetty = 'K'";
            $sarres = pupe_query($query);
            $srow2 = mysqli_fetch_assoc($sarres);

            if (mysqli_num_rows($sarres) > 0 and $srow2["ei_ostohintaa"] > 0) {
              $lasklisa .= " and lasku.tunnus != '$laskurow[tunnus]' ";

              if ($silent == "" or $silent == "VIENTI") {
                $tulos_ulos_sarjanumerot .= t("Olet myymässä käytettyä venettä, jota ei ole vielä ostettu! Ei voida laskuttaa!").": $laskurow[tunnus] $srow1[tuoteno] $laskurow[nimi]!!!<br>\n";
              }
            }
          }
        }

        // jos tilausrivi ei ole cronin generoima (silloin var2-kenttään tallennetaan PANT-teksti)
        // cron-ohjelma on panttitili_cron.php
        // jos asiakkaalla on panttitili käytössä, katsotaan tilausrivien tuotteet läpi onko niissä panttitilillisiä tuotteita
        if ($asiakas_panttitili_chk_row['panttitili'] == "K" and $srow1['panttitili'] == 'K' and $srow1['var2'] != 'PANT' and $srow1['varattu'] < 0) {

          if ($laskurow['clearing'] == 'HYVITYS') {

            // jos tilauksella on panttituotteita ja ollaan tekemässä hyvitystä, pitää katsoa, että alkuperäisen veloituslaskun panttitili rivejä ei ole vielä käytetty
            $query = "SELECT otunnus, tuoteno, sum(kpl) kpl
                      FROM tilausrivi
                      WHERE yhtio     = '{$kukarow['yhtio']}'
                      AND tyyppi      = 'L'
                      and var         not in ('P','J','O','S')
                      AND tuoteno     = '{$srow1['tuoteno']}'
                      AND uusiotunnus = '{$laskurow['vanhatunnus']}'
                      AND kpl         > 0
                      GROUP BY 1, 2";
            $vanhatunnus_chk_res = pupe_query($query);

            while ($vanhatunnus_chk_row = mysqli_fetch_assoc($vanhatunnus_chk_res)) {

              $query = "SELECT sum(kpl) kpl
                        FROM panttitili
                        WHERE yhtio           = '{$kukarow['yhtio']}'
                        AND asiakas           = '{$laskurow['liitostunnus']}'
                        AND tuoteno           = '{$srow1['tuoteno']}'
                        AND myyntitilausnro   = '{$vanhatunnus_chk_row['otunnus']}'
                        AND status            = ''
                        AND kaytettypvm       = '0000-00-00'
                        AND kaytettytilausnro = 0";
              $pantti_chk_res = pupe_query($query);
              $pantti_chk_row = mysqli_fetch_assoc($pantti_chk_res);

              if ($vanhatunnus_chk_row['kpl'] != $pantti_chk_row['kpl']) {
                $lasklisa .= " and lasku.tunnus != '{$laskurow['tunnus']}' ";

                if ($silent == "" or $silent == "VIENTI") {
                  $tulos_ulos_sarjanumerot .= t("Hyvitettävän laskun pantit on jo käytetty")."!<br>\n";
                }
              }
            }
          }
        }
      }

      $query = "SELECT *
                FROM maksuehto
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$laskurow[maksuehto]'";
      $maresult = pupe_query($query);
      $maksuehtorow = mysqli_fetch_assoc($maresult);

      if ($maksuehtorow['jaksotettu'] != '') {
        $query = "UPDATE lasku SET alatila='J'
                  WHERE tunnus = '$laskurow[tunnus]'";
        $updres = pupe_query($query);

        if ($silent == "" or $silent == "VIENTI") {
          $tulos_ulos_maksusoppari .= t("Maksusopimustilaus siirretty odottamaan loppulaskutusta").": $laskurow[tunnus] $laskurow[nimi]<br>\n";
        }
      }
    }

    if (isset($tulos_ulos_sarjanumerot) and $tulos_ulos_sarjanumerot != '' and ($silent == "" or $silent == "VIENTI")) {
      $tulos_ulos .= "<br>\n".t("Sarjanumerovirheet").":<br>\n";
      $tulos_ulos .= $tulos_ulos_sarjanumerot;
    }

    if (isset($tulos_ulos_maksusoppari) and $tulos_ulos_maksusoppari != '' and ($silent == "" or $silent == "VIENTI")) {
      $tulos_ulos .= "<br>\n".t("Maksusopimustilaukset").":<br>\n";
      $tulos_ulos .= $tulos_ulos_maksusoppari;
    }

    if (isset($tulos_ulos_ehtosplit) and $tulos_ulos_ehtosplit != '' and $silent == "") {
      $tulos_ulos .= "<br>\n".t("Tilauksia joilla on moniehto-maksuehto").":<br>\n";
      $tulos_ulos .= $tulos_ulos_ehtosplit;
    }

    if ($php_cli and (float) $yhtiorow['koontilaskut_alarajasumma'] > 0) {

      // Tehdään ketjutus (group by PITÄÄ OLLA sama kuin alhaalla) rivi ~1243
      $query = "SELECT
                if (lasku.ketjutus = '', '', if (lasku.vanhatunnus > 0, lasku.vanhatunnus, lasku.tunnus)) ketjutuskentta,
                if ((((asiakas.koontilaskut_yhdistetaan = '' and ('{$yhtiorow['koontilaskut_yhdistetaan']}' = 'U' or '{$yhtiorow['koontilaskut_yhdistetaan']}' = 'V'))  or asiakas.koontilaskut_yhdistetaan = 'U') and lasku.tilaustyyppi in ('R','U')), 1, 0) reklamaatiot_lasku,
                group_concat(lasku.tunnus) tunnukset
                FROM lasku
                LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio and laskun_lisatiedot.otunnus = lasku.tunnus)
                LEFT JOIN asiakas ON asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus
                where lasku.yhtio  = '{$kukarow['yhtio']}'
                and lasku.tila     = 'L'
                and lasku.alatila  = 'D'
                and lasku.viite    = ''
                and lasku.chn     != '999'
                {$lasklisa}
                GROUP BY ketjutuskentta, reklamaatiot_lasku, lasku.ytunnus, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.maksuehto, lasku.erpcm, lasku.vienti, lasku.kolmikantakauppa,
                lasku.lisattava_era, lasku.vahennettava_era, lasku.maa_maara, lasku.kuljetusmuoto, lasku.kauppatapahtuman_luonne,
                lasku.sisamaan_kuljetus, lasku.aktiivinen_kuljetus, lasku.kontti, lasku.aktiivinen_kuljetus_kansallisuus,
                lasku.sisamaan_kuljetusmuoto, lasku.poistumistoimipaikka, lasku.poistumistoimipaikka_koodi, lasku.chn, lasku.maa, lasku.valkoodi, lasku.laskutyyppi,
                laskun_lisatiedot.laskutus_nimi, laskun_lisatiedot.laskutus_nimitark, laskun_lisatiedot.laskutus_osoite, laskun_lisatiedot.laskutus_postino, laskun_lisatiedot.laskutus_postitp, laskun_lisatiedot.laskutus_maa
                {$ketjutus_group}";
      $alaraja_res = pupe_query($query);

      while ($alaraja_row = mysqli_fetch_assoc($alaraja_res)) {
        $query = "SELECT ROUND(SUM(tilausrivi.hinta * IF('{$yhtiorow['alv_kasittely']}' != '' AND tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa
                  FROM tilausrivi
                  WHERE tilausrivi.yhtio  = '{$kukarow['yhtio']}'
                  AND tilausrivi.tyyppi   = 'L'
                  AND tilausrivi.otunnus  IN ({$alaraja_row['tunnukset']})
                  AND tilausrivi.varattu != 0
                  HAVING summa >= '{$yhtiorow['koontilaskut_alarajasumma']}'";
        $alarajasumma_chk_res = pupe_query($query);
        $alarajasumma_chk_row = mysqli_fetch_assoc($alarajasumma_chk_res);

        if (empty($alarajasumma_chk_row['summa'])) {
          $lasklisa .= " and lasku.tunnus NOT IN ({$alaraja_row['tunnukset']}) ";
        }
      }
    }

    //haetaan kaikki laskutettavat tilaukset uudestaan, nyt meillä on maksuehtosplittaukset tehty
    $query = "SELECT lasku.*
              FROM lasku
              {$lasklisa_eikateiset}
              WHERE lasku.yhtio  = '$kukarow[yhtio]'
              and lasku.tila     = 'L'
              and lasku.alatila  = 'D'
              and lasku.viite    = ''
              and lasku.chn     != '999'
              $lasklisa";
    $res = pupe_query($query);

    if (mysqli_num_rows($res) > 0) {

      $tunnukset = "";
      $jaksotetut_tunnukset = "";
      $jaksotetut_by_jaksotettu = array();

      // otetaan tunnukset talteen
      while ($row = mysqli_fetch_assoc($res)) {
        if ($row['jaksotettu'] > 0) {
          $jaksotetut_tunnukset .= "'$row[tunnus]',";
          $jaksotetut_by_jaksotettu[$row['jaksotettu']][] = $row['tunnus'];
        }
        else {
          $tunnukset .= "'$row[tunnus]',";
        }
      }

      // tarkistetaan jaksotetut tunnukset
      if (!empty($jaksotetut_tunnukset)) {
        list($ok_tunnukset, $puuttuvat_tunnukset) = tarkista_jaksotetut_laskutunnukset($jaksotetut_by_jaksotettu);

        // ohitetaan virheellisen jaksotettujen laskujen käsittely
        if (!empty($puuttuvat_tunnukset)) {
          $tulos_ulos .= "<br>\n".t("HUOM: Laskuta kaikki maksusopimustilauksen toimitukset kerralla. Valituista laskuista ei käsitelty seuraavia").": {$puuttuvat_tunnukset}<br>\n";
          $lasklisa .= " and lasku.tunnus NOT IN ({$puuttuvat_tunnukset}) ";
        }

        // lisätään oikeelliset jaksotetut laskut tunnuksiin
        if (!empty($ok_tunnukset)) {
          $tunnukset .= $ok_tunnukset;
        }
      }

      // vika pilkku pois
      $tunnukset = substr($tunnukset, 0, -1);

      if ($yhtiorow["koontilaskut_yhdistetaan"] == 'T' or $yhtiorow['koontilaskut_yhdistetaan'] == 'V') {
        $ketjutus_group = ", lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, lasku.toim_maa ";
      }
      else {
        $ketjutus_group = "";
      }

      // Lasketaan rahtikulut, jälkivaatimuskulut ja erilliskäsiteltäväkulut vain jos niitä ei olla laskettu jo tilausvaiheessa.
      if ($yhtiorow["rahti_hinnoittelu"] == "") {

        //rahtien, jälkivaatimuskulujen ja erilliskäsiteltäväkulujen muuttujia
        $rah       = 0;
        $jvhinta   = 0;
        $rah_hinta = 0;
        $ekhinta   = 0;

        // erilliskäsiteltäväkulut omalle riville ja tutkitaan tarvimmeko lisäillä EK-kuluja
        if ($silent == "") {
          $tulos_ulos .= "<br>\n".t("Erilliskäsiteltäväkulut").":<br>\n";
        }

        $query = "SELECT group_concat(distinct lasku.tunnus) tunnukset
                  FROM lasku
                  JOIN rahtikirjat ON (rahtikirjat.yhtio = lasku.yhtio AND rahtikirjat.otsikkonro = lasku.tunnus)
                  JOIN toimitustapa ON (toimitustapa.yhtio = lasku.yhtio AND toimitustapa.selite = lasku.toimitustapa)
                  WHERE lasku.yhtio = '{$kukarow['yhtio']}'
                  AND lasku.tunnus  in ({$tunnukset})
                  GROUP BY lasku.toimitustavan_lahto, lasku.toimitustapa, lasku.ytunnus, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp";
        $result = pupe_query($query);

        $yhdista = array();

        while ($row = mysqli_fetch_assoc($result)) {
          $yhdista[] = $row["tunnukset"];
        }

        if (count($yhdista) == 0 and $silent == "") {
          $tulos_ulos .= t("Ei erilliskäsittelyjä")."!<br>\n";
        }

        if ($silent == "") $tulos_ulos .= "<table>";

        foreach ($yhdista as $otsikot) {

          // lisätään näille tilauksille erilliskäsiteltäväkulut
          $virhe = 0;

          //haetaan vikan otsikon tiedot
          $query = "SELECT *
                    FROM lasku
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tunnus  in ({$otsikot})
                    ORDER BY tunnus DESC
                    LIMIT 1";
          $otsre = pupe_query($query);
          $laskurow = mysqli_fetch_assoc($otsre);

          if (mysqli_num_rows($otsre) != 1) $virhe++;

          if (mysqli_num_rows($otsre) == 1 and $virhe == 0) {

            // kirjoitetaan jv kulurivi ekalle otsikolle
            $query = "SELECT erilliskasiteltavakulu
                      FROM toimitustapa
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND selite  = '{$laskurow['toimitustapa']}'";
            $ekres = pupe_query($query);
            $ekrow = mysqli_fetch_assoc($ekres);

            if ($ekrow['erilliskasiteltavakulu'] != 0 and $yhtiorow["erilliskasiteltava_tuotenumero"] != "") {

              $query = "SELECT *
                        FROM tuote
                        WHERE yhtio = '{$kukarow['yhtio']}'
                        AND tuoteno = '{$yhtiorow['erilliskasiteltava_tuotenumero']}'";
              $rhire = pupe_query($query);

              // jos tuotenumero löytyy
              if (mysqli_num_rows($rhire) == 1) {
                $trow = mysqli_fetch_assoc($rhire);

                $laskun_kieli = laskunkieli($laskurow['liitostunnus'], $kieli);

                $hinta = $ekrow['erilliskasiteltavakulu']; // jv kulu
                $nimitys = t("Erilliskäsiteltäväkulu", $laskun_kieli);
                $kommentti = "";

                list($ekhinta, $alv) = alv($laskurow, $trow, $hinta, '', '');

                $query  = "INSERT INTO tilausrivi (hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti)
                           values ('{$ekhinta}', 'N', '1', '1', '{$laskurow['tunnus']}', '{$trow['tuoteno']}', '{$nimitys}', '{$kukarow['yhtio']}', 'L', '{$alv}', '{$kommentti}')";
                $addtil = pupe_query($query);

                if ($silent == "") {
                  $tulos_ulos .= "<tr><td>".t("Lisättiin erilliskäsiteltäväkulut")."</td><td>{$laskurow['tunnus']}</td><td>{$laskurow['toimitustapa']}</td><td>{$ekhinta}</td><td>{$yhtiorow['valkoodi']}</td></tr>\n";
                }
              }
            }
          }
          elseif (mysqli_num_rows($otsre) != 1 and $silent == "") {
            $tulos_ulos .= "<tr><td>".t("Erilliskäsiteltäväkulua ei löydy!")."</td><td>{$laskurow['tunnus']}</td><td>{$laskurow['toimitustapa']}</td></tr>\n";
          }
          elseif ($silent == "") {
            $tulos_ulos .= "<tr><td>".t("Erilliskäsiteltäväkulua ei osattu lisätä!")." {$virhe}</td><td>{$otsikot}</td><td>{$laskurow['toimitustapa']}</td></tr>\n";
          }
        }

        // haetaan laskutettavista tilauksista kaikki distinct toimitustavat per asiakas per päivä
        // jälkivaatimukset omalle riville ja tutkitaan tarvimmeko lisäillä JV-kuluja
        if ($silent == "") {
          $tulos_ulos .= "<br>\n".t("Jälkivaatimuskulut").":<br>\n";
        }

        $query = "SELECT group_concat(distinct lasku.tunnus) tunnukset
                  FROM lasku, rahtikirjat, maksuehto
                  WHERE lasku.yhtio    = '$kukarow[yhtio]'
                  AND lasku.tunnus     in ($tunnukset)
                  AND lasku.yhtio      = rahtikirjat.yhtio
                  AND lasku.tunnus     = rahtikirjat.otsikkonro
                  AND lasku.yhtio      = maksuehto.yhtio
                  AND lasku.maksuehto  = maksuehto.tunnus
                  AND maksuehto.jv    != ''
                  GROUP BY lasku.toimitustavan_lahto, lasku.toimitustapa, lasku.ytunnus, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp";
        $result = pupe_query($query);

        $yhdista = array();

        while ($row = mysqli_fetch_assoc($result)) {
          $yhdista[] = $row["tunnukset"];
        }

        if (count($yhdista) == 0 and $silent == "") {
          $tulos_ulos .= t("Ei jälkivaatimuksia")."!<br>\n";
        }

        if ($silent == "") $tulos_ulos .= "<table>";

        foreach ($yhdista as $otsikot) {

          // lisätään näille tilauksille jvkulut
          $virhe = 0;

          //haetaan vikan otsikon tiedot
          $query = "SELECT lasku.*, maksuehto.jv
                    FROM lasku, maksuehto
                    WHERE lasku.yhtio   = '$kukarow[yhtio]'
                    AND lasku.tunnus    in ($otsikot)
                    AND lasku.yhtio     = maksuehto.yhtio
                    AND lasku.maksuehto = maksuehto.tunnus
                    ORDER BY lasku.tunnus DESC
                    LIMIT 1";
          $otsre = pupe_query($query);
          $laskurow = mysqli_fetch_assoc($otsre);

          if (mysqli_num_rows($otsre) != 1) $virhe++;

          if (mysqli_num_rows($otsre) == 1 and $virhe == 0) {

            // kirjoitetaan jv kulurivi ekalle otsikolle
            $query = "SELECT jvkulu
                      FROM toimitustapa
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND selite  = '{$laskurow['toimitustapa']}'";
            $tjvres = pupe_query($query);
            $tjvrow = mysqli_fetch_assoc($tjvres);

            if ($yhtiorow["jalkivaatimus_tuotenumero"] == "") {
              $yhtiorow["jalkivaatimus_tuotenumero"] = $yhtiorow["rahti_tuotenumero"];
            }

            $query = "SELECT *
                      FROM tuote
                      WHERE yhtio = '$kukarow[yhtio]'
                      AND tuoteno = '$yhtiorow[jalkivaatimus_tuotenumero]'";
            $rhire = pupe_query($query);

            // jos tuotenumero löytyy
            if (mysqli_num_rows($rhire) == 1) {
              $trow = mysqli_fetch_assoc($rhire);

              $laskun_kieli = laskunkieli($laskurow['liitostunnus'], $kieli);

              $hinta = $tjvrow['jvkulu']; // jv kulu
              $nimitys = t("Jälkivaatimuskulu", $laskun_kieli);
              $kommentti = "";

              list($jvhinta, $alv) = alv($laskurow, $trow, $hinta, '', '');

              $query  = "INSERT INTO tilausrivi (hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti)
                         values ('$jvhinta', 'N', '1', '1', '$laskurow[tunnus]', '$trow[tuoteno]', '$nimitys', '$kukarow[yhtio]', 'L', '$alv', '$kommentti')";
              $addtil = pupe_query($query);

              if ($silent == "") {
                $tulos_ulos .= "<tr><td>".t("Lisättiin jv-kulut")."</td><td>$laskurow[tunnus]</td><td>$laskurow[toimitustapa]</td><td>$jvhinta</td><td>$yhtiorow[valkoodi]</td></tr>\n";
              }
            }
          }
          elseif (mysqli_num_rows($otsre) != 1 and $silent == "") {
            $tulos_ulos .= "<tr><td>".t("Jälkivaatimuskulua ei löydy!")."</td><td>$laskurow[tunnus]</td><td>$laskurow[toimitustapa]</td></tr>\n";
          }
          elseif ($silent == "") {
            $tulos_ulos .= "<tr><td>".t("Jälkivaatimuskulua ei osattu lisätä!")." $virhe</td><td>$otsikot</td><td>$laskurow[toimitustapa]</td></tr>\n";
          }
        }

        if ($silent == "") {
          $tulos_ulos .= "<br>\n".t("Rahtikulut").":<br>\n<table>";
        }

        // haetaan laskutettavista tilauksista per lähtö, ytunnus ja toimitusosite.
        // missä merahti (eli kohdistettu) = K (Käytetään lähettäjän rahtisopimusnumeroa)
        // jälkivaatimukset omalle riville
        $query   = "SELECT group_concat(distinct lasku.tunnus) tunnukset
                    FROM lasku, rahtikirjat, maksuehto
                    WHERE lasku.yhtio     = '$kukarow[yhtio]'
                    AND lasku.tunnus      in ($tunnukset)
                    AND lasku.rahtivapaa  = ''
                    AND lasku.kohdistettu = 'K'
                    AND lasku.yhtio       = rahtikirjat.yhtio
                    AND lasku.tunnus      = rahtikirjat.otsikkonro
                    AND lasku.yhtio       = maksuehto.yhtio
                    AND lasku.maksuehto   = maksuehto.tunnus
                    GROUP BY lasku.toimitustavan_lahto, lasku.toimitustapa, lasku.ytunnus, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, maksuehto.jv";
        $result  = pupe_query($query);

        $yhdista = array();

        while ($row = mysqli_fetch_assoc($result)) {
          $yhdista[] = $row["tunnukset"];
        }

        foreach ($yhdista as $otsikot) {

          // lisätään näille tilauksille rahtikulut
          $virhe = 0;

          //haetaan vikan otsikon tiedot
          $query = "SELECT lasku.*, maksuehto.jv
                    FROM lasku, maksuehto
                    WHERE lasku.yhtio   = '$kukarow[yhtio]'
                    AND lasku.tunnus    in ($otsikot)
                    AND lasku.yhtio     = maksuehto.yhtio
                    AND lasku.maksuehto = maksuehto.tunnus
                    ORDER BY lasku.tunnus DESC
                    LIMIT 1";
          $otsre = pupe_query($query);
          $laskurow = mysqli_fetch_assoc($otsre);

          if (mysqli_num_rows($otsre) != 1) $virhe++;

          //summataan kaikki painot yhteen
          $query = "SELECT sum(kilot) kilot FROM rahtikirjat WHERE yhtio='$kukarow[yhtio]' AND otsikkonro in ($otsikot)";
          $pakre = pupe_query($query);
          $pakka = mysqli_fetch_assoc($pakre);
          if (mysqli_num_rows($pakre)!=1) $virhe++;

          //haetaan vähän infoa rahtikirjoista
          $query = "SELECT DISTINCT date_format(tulostettu, '%d.%m.%Y') pvm, rahtikirjanro FROM rahtikirjat WHERE yhtio='$kukarow[yhtio]' AND otsikkonro in ($otsikot)";
          $rahre = pupe_query($query);
          if (mysqli_num_rows($rahre)==0) $virhe++;

          $rahtikirjanrot = "";
          while ($rahrow = mysqli_fetch_assoc($rahre)) {
            if ($rahrow["pvm"]!='') $pvm = $rahrow["pvm"]; // pitäs olla kyllä aina sama
            $rahtikirjanrot .= "$rahrow[rahtikirjanro] ";
          }

          //vika pilkku pois
          $rahtikirjanrot = substr($rahtikirjanrot, 0, -1);

          // haetaan rahdin hinta
          list($rah_hinta, $rah_ale, $rah_alv, $rah_netto) = hae_rahtimaksu($otsikot);

          $query = "SELECT *
                    FROM tuote
                    WHERE yhtio = '$kukarow[yhtio]'
                    AND tuoteno = '$yhtiorow[rahti_tuotenumero]'";
          $rhire = pupe_query($query);

          if ($rah_hinta > 0 and $virhe == 0 and mysqli_num_rows($rhire) == 1) {

            $laskun_kieli = laskunkieli($laskurow['liitostunnus'], $kieli);

            $trow      = mysqli_fetch_assoc($rhire);
            $otunnus   = $laskurow['tunnus'];
            $nimitys   = "$pvm $laskurow[toimitustapa]";
            $kommentti = t("Rahtikirja", $laskun_kieli).": $rahtikirjanrot";

            $ale_lisa_insert_query_1 = $ale_lisa_insert_query_2 = '';

            for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
              if (isset($rah_ale["ale{$alepostfix}"]) and $rah_ale["ale{$alepostfix}"] > 0) {
                $ale_lisa_insert_query_1 .= " ale{$alepostfix},";
                $ale_lisa_insert_query_2 .= " '".$rah_ale["ale{$alepostfix}"]."',";
              }
            }

            $query  = "INSERT INTO tilausrivi (laatija, laadittu, hinta, {$ale_lisa_insert_query_1} netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti, keratty, kerattyaika, toimitettu, toimitettuaika)
                       values ('automaatti', now(), '$rah_hinta', {$ale_lisa_insert_query_2} '$rah_netto', '1', '1', '$otunnus', '$trow[tuoteno]', '$nimitys', '$kukarow[yhtio]', 'L', '$rah_alv', '$kommentti', 'saldoton', now(), 'saldoton', now())";
            $addtil = pupe_query($query);

            if ($silent == "") {
              $tulos_ulos .= "<tr><td>".t("Lisättiin rahtikulut")."</td><td>$laskurow[tunnus]</td><td>$laskurow[toimitustapa]</td><td>$rah_hinta</td><td>$yhtiorow[valkoodi]</td><td>$pakka[kilot] kg</td></tr>\n";
            }

            $rah++;
          }
          elseif ($rah_hinta != 0 and $silent == "") {
            $tulos_ulos .= "<tr><td>".t("Rahtimaksua ei osattu lisätä!")." $virhe</td><td>$otsikot</td><td>$laskurow[toimitustapa]</td><td></td><td></td><td>$pakka[kilot] kg</td></tr>\n";
          }
        }

        if ($silent == "") {
          $tulos_ulos .= "</table>\n".sprintf(t("Lisättiin rahtikulu %s kpl rahtikirjaan"), $rah).".";
        }
      }
      elseif ($silent == "") {
        $tulos_ulos .= "<br>\n".t("Laskujen rahtikulut muodostuivat jo tilausvaiheessa").".<br>\n";
      }

      // katsotaan halutaanko laskuille lisätä lisäkulu prosentti
      if ($yhtiorow["laskutuslisa_tuotenumero"] != "" and ($yhtiorow["laskutuslisa"] > 0 or $yhtiorow["laskutuslisa_tyyppi"] == 'T' or $yhtiorow["laskutuslisa_tyyppi"] == 'U' or $yhtiorow["laskutuslisa_tyyppi"] == 'V') and $yhtiorow["laskutuslisa_tyyppi"] != "") {

        $laskutuslisa_tyyppi_ehto = "";

        //ei käteislaskuihin
        if ($yhtiorow["laskutuslisa_tyyppi"] == 'B' or $yhtiorow["laskutuslisa_tyyppi"] == 'K' or $yhtiorow["laskutuslisa_tyyppi"] == 'U') {
          $query = "SELECT tunnus
                    FROM maksuehto
                    WHERE yhtio   = '$kukarow[yhtio]'
                    and kateinen != ''";
          $limaresult = pupe_query($query);

          $lisakulu_maksuehto = array();

          while ($limaksuehtorow = mysqli_fetch_assoc($limaresult)) {
            $lisakulu_maksuehto[] = $limaksuehtorow["tunnus"];
          }

          if (count($lisakulu_maksuehto) > 0) {
            $laskutuslisa_tyyppi_ehto = " and lasku.maksuehto not in (".implode(',', $lisakulu_maksuehto).") ";
          }
        }
        elseif ($yhtiorow["laskutuslisa_tyyppi"] == 'C' or $yhtiorow["laskutuslisa_tyyppi"] == 'N' or $yhtiorow["laskutuslisa_tyyppi"] == 'V') {
          //ei noudolle
          $query = "SELECT selite
                    FROM toimitustapa
                    WHERE yhtio  = '$kukarow[yhtio]'
                    and nouto   != ''";
          $toimitusresult = pupe_query($query);

          $lisakulu_toimitustapa = array();

          while ($litoimitustaparow = mysqli_fetch_assoc($toimitusresult)) {
            $lisakulu_toimitustapa[] = "'".$litoimitustaparow["selite"]."'";
          }

          if (count($lisakulu_toimitustapa) > 0) {
            $laskutuslisa_tyyppi_ehto = " and lasku.toimitustapa not in (".implode(',', $lisakulu_toimitustapa).") ";
          }
        }

        // Tehdään ketjutus (group by PITÄÄ OLLA sama kuin alhaalla) rivi ~1243
        $query = "SELECT
                  if(lasku.ketjutus = '', '', if (lasku.vanhatunnus > 0, lasku.vanhatunnus, lasku.tunnus)) ketjutuskentta,
                  if ((((asiakas.koontilaskut_yhdistetaan = '' and ('{$yhtiorow['koontilaskut_yhdistetaan']}' = 'U' or '{$yhtiorow['koontilaskut_yhdistetaan']}' = 'V')) or asiakas.koontilaskut_yhdistetaan = 'U') and lasku.tilaustyyppi in ('R','U')), 1, 0) reklamaatiot_lasku,
                  group_concat(lasku.tunnus) tunnukset
                  FROM lasku
                  LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio and laskun_lisatiedot.otunnus = lasku.tunnus)
                  LEFT JOIN asiakas ON asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus
                  where lasku.yhtio = '$kukarow[yhtio]'
                  and lasku.tunnus  in ($tunnukset)
                  $laskutuslisa_tyyppi_ehto
                  GROUP BY ketjutuskentta, reklamaatiot_lasku, lasku.ytunnus, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.maksuehto, lasku.erpcm, lasku.vienti, lasku.kolmikantakauppa,
                  lasku.lisattava_era, lasku.vahennettava_era, lasku.maa_maara, lasku.kuljetusmuoto, lasku.kauppatapahtuman_luonne,
                  lasku.sisamaan_kuljetus, lasku.aktiivinen_kuljetus, lasku.kontti, lasku.aktiivinen_kuljetus_kansallisuus,
                  lasku.sisamaan_kuljetusmuoto, lasku.poistumistoimipaikka, lasku.poistumistoimipaikka_koodi, lasku.chn, lasku.maa, lasku.valkoodi, lasku.laskutyyppi,
                  laskun_lisatiedot.laskutus_nimi, laskun_lisatiedot.laskutus_nimitark, laskun_lisatiedot.laskutus_osoite, laskun_lisatiedot.laskutus_postino, laskun_lisatiedot.laskutus_postitp, laskun_lisatiedot.laskutus_maa
                  $ketjutus_group";
        $result = pupe_query($query);

        $yhdista = array();

        while ($row = mysqli_fetch_assoc($result)) {
          $yhdista[] = $row["tunnukset"];
        }

        // haetaan laskutuslisa_tuotenumero-tuotteen tiedot
        $query = "SELECT *
                  FROM tuote
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND tuoteno = '$yhtiorow[laskutuslisa_tuotenumero]'";
        $rhire = pupe_query($query);
        $trow  = mysqli_fetch_assoc($rhire);

        foreach ($yhdista as $otsikot) {
          // Tsekataan, että laskutuslisää ei ole jo lisätty käsin
          $query = "SELECT tunnus, hinta
                    FROM tilausrivi
                    WHERE yhtio = '$kukarow[yhtio]'
                    and otunnus in ($otsikot)
                    and tuoteno = '$trow[tuoteno]'
                    and tyyppi  = 'L'
                    and var     not in ('P','J','O','S')";
          $listilre = pupe_query($query);

          if (mysqli_num_rows($listilre) == 0) {
            //haetaan vikan otsikon tiedot
            $query = "SELECT lasku.*
                      FROM lasku
                      WHERE lasku.yhtio = '$kukarow[yhtio]'
                      AND lasku.tunnus  in ($otsikot)
                      ORDER BY lasku.tunnus DESC
                      LIMIT 1";
            $otsre = pupe_query($query);
            $laskurow = mysqli_fetch_assoc($otsre);

            $query = "SELECT *
                      FROM asiakas
                      WHERE yhtio = '$kukarow[yhtio]'
                      AND tunnus  = '$laskurow[liitostunnus]'";
            $aslisakulres = pupe_query($query);
            $aslisakulrow = mysqli_fetch_assoc($aslisakulres);

            if (mysqli_num_rows($otsre) == 1 and mysqli_num_rows($rhire) == 1 and $aslisakulrow['laskutuslisa'] == '') {

              $query_ale_lisa = generoi_alekentta('M');

              // Prosentuaalinen laskutuslisä
              // lasketaan laskun loppusumma (HUOM ei tarvitse huomioida veroa! Jos on verottomat hinnat niin lisäprossa lasketaan verottomasta summasta, jos on verolliset hinnat niin lasketaan verollisesta summasta)
              $query = "SELECT sum(tilausrivi.hinta * (tilausrivi.varattu + tilausrivi.jt) * {$query_ale_lisa}) laskun_loppusumma
                        FROM tilausrivi
                        JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
                        WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                        and tilausrivi.tyyppi  = 'L'
                        and tilausrivi.var     not in ('P','J','O','S')
                        and tilausrivi.otunnus in ($otsikot)";
              $listilre = pupe_query($query);
              $listilro = mysqli_fetch_assoc($listilre);

              // Jos tilauksen loppusumma on negatiivinen tai nolla, niin ei myöskään lisätä laskutuslisää
              if ($listilro["laskun_loppusumma"] <= 0) {
                continue;
              }

              if ($yhtiorow["laskutuslisa_tyyppi"] == 'L' or $yhtiorow["laskutuslisa_tyyppi"] == 'K' or $yhtiorow["laskutuslisa_tyyppi"] == 'N') {
                $hinta = $listilro["laskun_loppusumma"] * $yhtiorow["laskutuslisa"] / 100;
              }
              else {
                // Rahamääräinen laskutulisä
                // tapauksissa A,B,C
                $hinta = $yhtiorow["laskutuslisa"];
              }

              $hinta = laskuval($hinta, $laskurow["vienti_kurssi"]);
              $alemuuttuja = "";

              if ($yhtiorow["laskutuslisa_tyyppi"] == 'T' or $yhtiorow["laskutuslisa_tyyppi"] == 'U' or $yhtiorow["laskutuslisa_tyyppi"] == 'V') {
                list($lis_hinta, $lis_netto, $lis_ale, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $trow, '1', '', '', array());
                $netto = $lis_netto;

                for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                  if (isset($lis_ale["ale".$alepostfix])) {
                    $alemuuttuja .= "ale$alepostfix = '{$lis_ale["ale".$alepostfix]}',";
                  }
                }
              }
              else {
                list($lis_hinta, $lis_netto, $lis_ale, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $trow, '1', 'N', $hinta, array());
                $netto = 'N';
                $alemuuttuja = "ale1 = '0',";
              }

              list($lkhinta, $alv) = alv($laskurow, $trow, $lis_hinta, '', $alehinta_alv);

              $lkhinta = hintapyoristys($lkhinta);

              if ($lkhinta > 0) {

                // lisätään laskutuslisä
                $query = "INSERT into tilausrivi set
                          hyllyalue       = '',
                          hyllynro        = '',
                          hyllyvali       = '',
                          hyllytaso       = '',
                          tilaajanrivinro = '',
                          laatija         = '$kukarow[kuka]',
                          laadittu        = now(),
                          yhtio           = '$kukarow[yhtio]',
                          tuoteno         = '$trow[tuoteno]',
                          varattu         = '1',
                          yksikko         = '$trow[yksikko]',
                          kpl             = '0',
                          kpl2            = '0',
                          tilkpl          = '1',
                          jt              = '0',
                          {$alemuuttuja}
                          netto           = '{$netto}',
                          hinta           = '$lkhinta',
                          alv             = '$alv',
                          kerayspvm       = now(),
                          otunnus         = '$laskurow[tunnus]',
                          tyyppi          = 'L',
                          toimaika        = now(),
                          kommentti       = '',
                          var             = '',
                          try             = '$trow[try]',
                          osasto          = '$trow[osasto]',
                          perheid         = '',
                          perheid2        = '',
                          nimitys         = '$trow[nimitys]',
                          jaksotettu      = '',
                          keratty         = 'saldoton',
                          kerattyaika     = now(),
                          toimitettu      = 'saldoton',
                          toimitettuaika  = now()";

                // MUOKKAUS: mysqli_insert_id():
                $addtil = pupe_query($query, $GLOBALS["link"]);
                $lisatty_tun = mysqli_insert_id($GLOBALS["link"]);

                $query = "INSERT INTO tilausrivin_lisatiedot
                          SET yhtio           = '$kukarow[yhtio]',
                          positio            = '',
                          tilausrivilinkki   = '',
                          toimittajan_tunnus = '',
                          tilausrivitunnus   = '$lisatty_tun',
                          jarjestys          = '',
                          vanha_otunnus      = '$laskurow[tunnus]',
                          ei_nayteta         = '',
                          luontiaika         = now(),
                          laatija            = '$kukarow[kuka]'";
                $addtil = pupe_query($query);

                if ($silent == "") {
                  $tulos_ulos .= t("Lisättiin lisäkuluja")." $laskurow[tunnus]: $lkhinta $alemuuttuja $laskurow[valkoodi]<br>\n";
                }
              }
            }
          }
        }
      }

      // Onko toimitustapoja joilla on kuljetusvakuutus päällä
      $query = "SELECT group_concat(selite) toimitustavat
                FROM toimitustapa
                WHERE yhtio                 = '$kukarow[yhtio]'
                AND (kuljetusvakuutus_tuotenumero != '' or '$yhtiorow[kuljetusvakuutus_tuotenumero]' != '')
                AND (kuljetusvakuutus > 0 or '$yhtiorow[kuljetusvakuutus]' > 0 or kuljetusvakuutus_tyyppi = 'F' or '$yhtiorow[kuljetusvakuutus_tyyppi]' = 'F')
                AND kuljetusvakuutus_tyyppi not in ('','E')";
      $kulvare = pupe_query($query);
      $kulvaro = mysqli_fetch_assoc($kulvare);

      // katsotaan halutaanko tilauksille lisätä kuljetusvakuutus, joko yhtiön parametri päällä tai toimitustapojen takana päällä
      if ($kulvaro["toimitustavat"] != "" or ($yhtiorow["kuljetusvakuutus_tuotenumero"] != "" and ($yhtiorow["kuljetusvakuutus"] > 0 or $yhtiorow["kuljetusvakuutus_tyyppi"] == 'F') and $yhtiorow["kuljetusvakuutus_tyyppi"] != "")) {

        // Tehdään ketjutus (group by PITÄÄ OLLA sama kuin alhaalla) rivi ~1243
        $query = "SELECT
                  if (lasku.ketjutus = '', '', if (lasku.vanhatunnus > 0, lasku.vanhatunnus, lasku.tunnus)) ketjutuskentta,
                  if ((((asiakas.koontilaskut_yhdistetaan = '' and ('{$yhtiorow['koontilaskut_yhdistetaan']}' = 'U' or '{$yhtiorow['koontilaskut_yhdistetaan']}' = 'V'))  or asiakas.koontilaskut_yhdistetaan = 'U') and lasku.tilaustyyppi in ('R','U')), 1, 0) reklamaatiot_lasku,
                  group_concat(lasku.tunnus) tunnukset
                  FROM lasku
                  LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio and laskun_lisatiedot.otunnus = lasku.tunnus)
                  LEFT JOIN asiakas ON asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus
                  where lasku.yhtio = '{$kukarow['yhtio']}'
                  and lasku.tunnus  in ({$tunnukset})
                  GROUP BY ketjutuskentta, reklamaatiot_lasku, lasku.ytunnus, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.maksuehto, lasku.erpcm, lasku.vienti, lasku.kolmikantakauppa,
                  lasku.lisattava_era, lasku.vahennettava_era, lasku.maa_maara, lasku.kuljetusmuoto, lasku.kauppatapahtuman_luonne,
                  lasku.sisamaan_kuljetus, lasku.aktiivinen_kuljetus, lasku.kontti, lasku.aktiivinen_kuljetus_kansallisuus,
                  lasku.sisamaan_kuljetusmuoto, lasku.poistumistoimipaikka, lasku.poistumistoimipaikka_koodi, lasku.chn, lasku.maa, lasku.valkoodi, lasku.laskutyyppi,
                  laskun_lisatiedot.laskutus_nimi, laskun_lisatiedot.laskutus_nimitark, laskun_lisatiedot.laskutus_osoite, laskun_lisatiedot.laskutus_postino, laskun_lisatiedot.laskutus_postitp, laskun_lisatiedot.laskutus_maa
                  {$ketjutus_group}";
        $result = pupe_query($query);

        $yhdista = array();

        while ($row = mysqli_fetch_assoc($result)) {
          $yhdista[] = $row["tunnukset"];
        }

        foreach ($yhdista as $otsikot) {

          $query_ale_lisa = generoi_alekentta('M');

          if ($yhtiorow['kuljetusvakuutus_koonti'] == 'L') {
            $selectlisa_kuljetusvakuutus = "GROUP_CONCAT(DISTINCT lasku.tunnus) AS tunnus,";
            $groupbylisa_kuljetusvakuutus = "GROUP BY 1,2,3,4";
          }
          else {
            $selectlisa_kuljetusvakuutus = "lasku.tunnus,";
            $groupbylisa_kuljetusvakuutus = "GROUP BY 1,2,3,4,5";
          }

          // lasketaan tilauksen loppusumma (HUOM ei tarvitse huomioida veroa! Jos on verottomat hinnat niin lisäprossa lasketaan verottomasta summasta, jos on verolliset hinnat niin lasketaan verollisesta summasta)
          $query = "SELECT lasku.toimitustapa,
                    if (toimitustapa.kuljetusvakuutus_tyyppi != '', toimitustapa.kuljetusvakuutus_tyyppi, '$yhtiorow[kuljetusvakuutus_tyyppi]') kv_tyyppi,
                    if (toimitustapa.kuljetusvakuutus != '', toimitustapa.kuljetusvakuutus, '$yhtiorow[kuljetusvakuutus]') kv_kuljetusvakuutus,
                    if (toimitustapa.kuljetusvakuutus_tuotenumero != '', toimitustapa.kuljetusvakuutus_tuotenumero, '$yhtiorow[kuljetusvakuutus_tuotenumero]') kv_tuotenumero,
                    {$selectlisa_kuljetusvakuutus}
                    sum(tilausrivi.hinta * tilausrivi.varattu * {$query_ale_lisa}) laskun_loppusumma
                    FROM lasku
                    LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio and laskun_lisatiedot.otunnus = lasku.tunnus)
                    JOIN asiakas ON (lasku.yhtio = asiakas.yhtio and lasku.liitostunnus = asiakas.tunnus and asiakas.kuljetusvakuutus_tyyppi != 'E')
                    JOIN toimitustapa ON (toimitustapa.yhtio = lasku.yhtio and toimitustapa.selite = lasku.toimitustapa and toimitustapa.kuljetusvakuutus_tyyppi != 'E')
                    JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi = 'L' and tilausrivi.var not in ('P','J','O','S'))
                    JOIN tuote ON (tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno and tuote.ei_saldoa = '')
                    WHERE lasku.yhtio = '$kukarow[yhtio]'
                    AND lasku.tunnus  in ($otsikot)
                    {$groupbylisa_kuljetusvakuutus}
                    ORDER BY lasku.tunnus";
          $kvak_result = pupe_query($query);

          $kv_vakhinta = 0;
          $kv_vakalvi  = 0;
          $kv_tilaukset= "";
          $kv_vaktuote = $yhtiorow["kuljetusvakuutus_tuotenumero"];

          while ($row = mysqli_fetch_assoc($kvak_result)) {
            if ($row["kv_tuotenumero"] != "" and ($row["kv_kuljetusvakuutus"] > 0 or $row["kv_tyyppi"] == 'F') and $row["kv_tyyppi"] != "") {

              // haetaan kuljetusvakuutus_tuotenumero-tuotteen tiedot
              $query = "SELECT *
                        FROM tuote
                        WHERE yhtio = '$kukarow[yhtio]'
                        AND tuoteno = '$row[kv_tuotenumero]'";
              $rhire = pupe_query($query);

              if (mysqli_num_rows($rhire) == 1) {
                $trow = mysqli_fetch_assoc($rhire);

                if ($kv_vaktuote == "") $kv_vaktuote = $row["kv_tuotenumero"];

                // haetaan vikan otsikon tiedot
                $query = "SELECT lasku.*
                          FROM lasku
                          WHERE lasku.yhtio = '$kukarow[yhtio]'
                          AND lasku.tunnus  IN ({$row['tunnus']})
                          ORDER BY lasku.tunnus DESC
                          LIMIT 1";
                $otsre = pupe_query($query);
                $laskurow = mysqli_fetch_assoc($otsre);

                if ($row["kv_tyyppi"] == 'B' or $row["kv_tyyppi"] == 'G') {
                  // Prosentuaalinen kuljetusvakuutus
                  // tapauksissa B,G
                  $hinta = $row["laskun_loppusumma"] * $row["kv_kuljetusvakuutus"] / 100;
                }
                elseif ($row["kv_tyyppi"] == 'A') {
                  // Rahamääräinen kuljetusvakuutus
                  // tapauksissa A
                  $hinta = $row["kv_kuljetusvakuutus"];
                }
                else {
                  // Rahamääräinen kuljetusvakuutus, käytetään tuotteen myyntihintaa
                  // tapauksissa F
                  $hinta = "";
                }

                $hinta = laskuval($hinta, $laskurow["vienti_kurssi"]);
                $alemuuttuja = "";

                if ($row["kv_tyyppi"] == 'F' or $row["kv_tyyppi"] == 'G') {
                  list($lis_hinta, $lis_netto, $lis_ale, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $trow, '1', '', $hinta, array());
                  $netto = $lis_netto;

                  $kv_ale = 1;

                  for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                    if (isset($lis_ale["ale".$alepostfix])) {
                      $kv_ale *= (1 - $lis_ale["ale{$alepostfix}"] / 100);
                    }
                  }

                  $kv_ale = round($kv_ale, 4);

                  $lis_hinta = $lis_hinta * (1 - ($kv_ale/100));

                }
                else {
                  list($lis_hinta, $lis_netto, $lis_ale, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $trow, '1', 'N', $hinta, array());
                  $netto = 'N';
                  $alemuuttuja = "ale1 = '0',";
                }

                list($lkhinta, $alv) = alv($laskurow, $trow, $lis_hinta, '', $alehinta_alv);

                if ($lkhinta > 0) {
                  // Lasketaan hinnat yhteen. (HUOM: Menee metsään jos on useita eri kuljetusvakuutustuotteita eri alvikannoilla.)
                  $kv_vakhinta += hintapyoristys($lkhinta);

                  // Otetaan vikan tuotteen alvikanta ja mennään sillä
                  $kv_vakalvi = $alv;

                  $kv_tilaukset .= $row["tunnus"].", ";
                }
              }
            }
          }

          if ($kv_vakhinta > 0 and $kv_vaktuote != "") {

            // katotaan vielä vasta tässä onko kuljetusvakuutus jo lisätty (tässä vasta tiedetään faktavarmasti tuo tuotenumero)
            // jos on jo lisätty nii ei lisätä uudestaan
            $query = "SELECT tunnus
                      FROM tilausrivi
                      WHERE yhtio = '$kukarow[yhtio]'
                      AND otunnus in ($otsikot)
                      AND tyyppi  = 'L'
                      AND var     not in ('P','J','O','S')
                      AND tuoteno = '$kv_vaktuote'";
            $kvak_result = pupe_query($query);

            if (mysqli_num_rows($kvak_result) == 0) {

              $query = "SELECT *
                        FROM tuote
                        WHERE yhtio = '$kukarow[yhtio]'
                        AND tuoteno = '$kv_vaktuote'";
              $rhire = pupe_query($query);
              $trow = mysqli_fetch_assoc($rhire);

              $kv_komm = t("Kuljetusvakuutus muodostuu tilauksista", $kieli).": ".substr($kv_tilaukset, 0, -2);

              // laskurow-valuu tosta edellisesta while loopista. Siinä on vikan otsikon tiedot.
              // lisätään kuljetusvakuutus
              $query = "INSERT into tilausrivi set
                        hyllyalue       = '',
                        hyllynro        = '',
                        hyllyvali       = '',
                        hyllytaso       = '',
                        tilaajanrivinro = '',
                        laatija         = '$kukarow[kuka]',
                        laadittu        = now(),
                        yhtio           = '$kukarow[yhtio]',
                        tuoteno         = '$trow[tuoteno]',
                        varattu         = '1',
                        yksikko         = '$trow[yksikko]',
                        kpl             = '0',
                        kpl2            = '0',
                        tilkpl          = '1',
                        jt              = '0',
                        netto           = 'N',
                        hinta           = '$kv_vakhinta',
                        alv             = '$kv_vakalvi',
                        kerayspvm       = now(),
                        otunnus         = '$laskurow[tunnus]',
                        tyyppi          = 'L',
                        toimaika        = now(),
                        kommentti       = '$kv_komm',
                        var             = '',
                        try             = '$trow[try]',
                        osasto          = '$trow[osasto]',
                        perheid         = '',
                        perheid2        = '',
                        nimitys         = '$trow[nimitys]',
                        jaksotettu      = '',
                        keratty         = 'saldoton',
                        kerattyaika     = now(),
                        toimitettu      = 'saldoton',
                        toimitettuaika  = now()";
              
	      // MUOKKAUS: mysqli_insert_id():
              $addtil = pupe_query($query, $GLOBALS["link"]);
              $lisatty_tun = mysqli_insert_id($GLOBALS["link"]);

              $query = "INSERT INTO tilausrivin_lisatiedot
                        SET yhtio           = '$kukarow[yhtio]',
                        positio            = '',
                        tilausrivilinkki   = '',
                        toimittajan_tunnus = '',
                        tilausrivitunnus   = '$lisatty_tun',
                        jarjestys          = '',
                        vanha_otunnus      = '$laskurow[tunnus]',
                        ei_nayteta         = '',
                        luontiaika         = now(),
                        laatija            = '$kukarow[kuka]'";
              $addtil = pupe_query($query);

              if ($silent == "") {
                $tulos_ulos .= t("Lisättiin kuljetusvakuutusta")." $laskurow[tunnus]: $kv_vakhinta $laskurow[valkoodi]<br>\n";
              }
            }
          }
        }
      }

      //haetaan kaikki laskutettavat tilaukset uudestaan
      $query = "SELECT lasku.*
                FROM lasku
                {$lasklisa_eikateiset}
                WHERE lasku.yhtio  = '$kukarow[yhtio]'
                and lasku.tila     = 'L'
                and lasku.alatila  = 'D'
                and lasku.viite    = ''
                and lasku.chn     != '999'
                $lasklisa";
      $res = pupe_query($query);

      // laskutetaan kaikki tilaukset (siis tehään kaikki tarvittava matikka)
      // rullataan eka query alkuun
      if (mysqli_num_rows($res) != 0) {
        mysqli_data_seek($res, 0);
      }

      $laskutetttu = 0;

      if ($silent == "") {
        $tulos_ulos .= "<br><br>\n".t("Tilausten laskutus:")."<br>\n";
      }

      while ($row = mysqli_fetch_assoc($res)) {
        // laskutus tarttee kukarow[kesken]
        $kukarow['kesken']=$row['tunnus'];

        $_poikkeavalaskutuspvm = '';
        if ($poikkeava_pvm != '') $_poikkeavalaskutuspvm = $laskvv."-".$laskkk."-".$laskpp;

        tee_kirjanpidollinen_varastosiirto($row['tunnus'], $_poikkeavalaskutuspvm);

        require "laskutus.inc";
        $laskutetttu++;

        //otetaan laskutuksen viestit talteen
        if ($silent == "") {
          $tulos_ulos .= $tulos_ulos_laskutus;
        }
      }

      if ($silent == "") {
        $tulos_ulos .= t("Laskutettiin")." $laskutetttu ".t("tilausta").".";
      }

      //ketjutetaan laskut...
      $ketjut = array();

      //haetaan kaikki laskutusvalmiit tilaukset jotka saa ketjuttaa, viite pitää olla tyhjää muuten ei laskuteta
      $query  = "SELECT
                 if (lasku.ketjutus = '', '', if (lasku.vanhatunnus > 0, lasku.vanhatunnus, lasku.tunnus)) ketjutuskentta,
                 if ((((asiakas.koontilaskut_yhdistetaan = '' and ('{$yhtiorow['koontilaskut_yhdistetaan']}' = 'U' or '{$yhtiorow['koontilaskut_yhdistetaan']}' = 'V')) or asiakas.koontilaskut_yhdistetaan = 'U') and lasku.tilaustyyppi in ('R','U')), 1, 0) reklamaatiot_lasku,
                 lasku.ytunnus, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.maksuehto, lasku.erpcm, lasku.vienti, lasku.kolmikantakauppa,
                 lasku.lisattava_era, lasku.vahennettava_era, lasku.maa_maara, lasku.kuljetusmuoto, lasku.kauppatapahtuman_luonne,
                 lasku.sisamaan_kuljetus, lasku.aktiivinen_kuljetus, lasku.kontti, lasku.aktiivinen_kuljetus_kansallisuus,
                 lasku.sisamaan_kuljetusmuoto, lasku.poistumistoimipaikka, lasku.poistumistoimipaikka_koodi, lasku.chn, lasku.maa, lasku.valkoodi,
                 count(lasku.tunnus) yht,
                 group_concat(lasku.tunnus) tunnukset
                 FROM lasku
                 LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio and laskun_lisatiedot.otunnus = lasku.tunnus)
                 LEFT JOIN asiakas ON asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus
                 WHERE lasku.yhtio = '$kukarow[yhtio]'
                 and lasku.alatila = 'V'
                 and lasku.tila    = 'L'
                 and lasku.viite   = ''
                 $lasklisa
                 GROUP BY ketjutuskentta, reklamaatiot_lasku, lasku.ytunnus, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.maksuehto, lasku.erpcm, lasku.vienti, lasku.kolmikantakauppa,
                 lasku.lisattava_era, lasku.vahennettava_era, lasku.maa_maara, lasku.kuljetusmuoto, lasku.kauppatapahtuman_luonne,
                 lasku.sisamaan_kuljetus, lasku.aktiivinen_kuljetus, lasku.kontti, lasku.aktiivinen_kuljetus_kansallisuus,
                 lasku.sisamaan_kuljetusmuoto, lasku.poistumistoimipaikka, lasku.poistumistoimipaikka_koodi, lasku.chn, lasku.maa, lasku.valkoodi, lasku.laskutyyppi,
                 laskun_lisatiedot.laskutus_nimi, laskun_lisatiedot.laskutus_nimitark, laskun_lisatiedot.laskutus_osoite, laskun_lisatiedot.laskutus_postino, laskun_lisatiedot.laskutus_postitp, laskun_lisatiedot.laskutus_maa
                 $ketjutus_group
                 ORDER BY ketjutuskentta, reklamaatiot_lasku DESC, lasku.ytunnus, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.maksuehto, lasku.erpcm, lasku.vienti, lasku.kolmikantakauppa,
                 lasku.lisattava_era, lasku.vahennettava_era, lasku.maa_maara, lasku.kuljetusmuoto, lasku.kauppatapahtuman_luonne,
                 lasku.sisamaan_kuljetus, lasku.aktiivinen_kuljetus, lasku.kontti, lasku.aktiivinen_kuljetus_kansallisuus,
                 lasku.sisamaan_kuljetusmuoto, lasku.poistumistoimipaikka, lasku.poistumistoimipaikka_koodi, lasku.chn, lasku.maa, lasku.valkoodi,
                 laskun_lisatiedot.laskutus_nimi, laskun_lisatiedot.laskutus_nimitark, laskun_lisatiedot.laskutus_osoite, laskun_lisatiedot.laskutus_postino, laskun_lisatiedot.laskutus_postitp, laskun_lisatiedot.laskutus_maa";
      $result = pupe_query($query);

      if ($silent == "") {
        $tulos_ulos .= "<br><br>\n".t("Laskujen ketjutus:")."<br>\n<table>";
      }

      while ($row = mysqli_fetch_assoc($result)) {

        if ($silent == "") {
          $tulos_ulos .= "<tr><td>$row[ytunnus]</td><td>$row[nimi]<br>$row[nimitark]</td><td>$row[osoite]</td><td>$row[postino]</td>\n
                  <td>$row[postitp]</td><td>$row[maksuehto]</td><td>$row[erpcm]</td><td>Ketjutettu $row[yht] kpl</td></tr>\n";
        }

        $ketjut[]  = $row["tunnukset"];
      }

      if ($silent == "") {
        $tulos_ulos .= "</table><br>\n";
      }

      //laskuri
      $lask       = 0;
      $edilask    = 0;

      // jos on jotain laskutettavaa ...
      if (count($ketjut) != 0) {

        //Timestamppi EDI-failiin alkuu ja loppuun
        $timestamppi = gmdate("YmdHis");

        //nyt meillä on $ketjut arrayssa kaikki yhteenkuuluvat tunnukset suoraan mysql:n IN-syntaksin muodossa!! jee!!
        foreach ($ketjut as $tunnukset) {

          // generoidaan laskulle viite ja lasno
          $query = "SELECT max(laskunro) laskunro FROM lasku WHERE yhtio = '$kukarow[yhtio]' and tila = 'U'";
          $result= pupe_query($query);
          $lrow  = mysqli_fetch_assoc($result);

          $lasno = $lrow["laskunro"] + 1;

          if ($lasno < 100) {
            $lasno = 100;
          }

          // Tutkitaan onko ketju factorinkia
          // Ketju on groupattu maksuehdon mukaan, joten meillä ei ole kun yhden maksuehdon laskuja
          $query = "SELECT DISTINCT factoring.sopimusnumero, factoring.factoringyhtio, factoring.viitetyyppi
                    FROM lasku
                    JOIN maksuehto ON (maksuehto.yhtio = lasku.yhtio
                      and maksuehto.tunnus   = lasku.maksuehto
                      and maksuehto.factoring_id is not null)
                    JOIN factoring ON (factoring.yhtio = maksuehto.yhtio
                      and factoring.tunnus   = maksuehto.factoring_id
                      and factoring.valkoodi = lasku.valkoodi)
                    WHERE lasku.yhtio        = '$kukarow[yhtio]'
                    and lasku.tunnus         in ($tunnukset)";
          $fres = pupe_query($query);
          $frow = mysqli_fetch_assoc($fres);

          // Nordean viitenumero rakentuu hieman eri lailla ku normaalisti
          if ($frow["sopimusnumero"] > 0 and $frow["factoringyhtio"] == 'NORDEA' and $frow["viitetyyppi"] == '') {
            $viite = $frow["sopimusnumero"]."0".sprintf('%08d', $lasno);
          }
          elseif ($frow["sopimusnumero"] > 0 and $frow["factoringyhtio"] == 'COLLECTOR' and $frow["viitetyyppi"] == '') {
            $viite = $frow["sopimusnumero"]."0".sprintf('%08d', $lasno);
          }
          elseif ($frow["sopimusnumero"] > 0 and $frow["factoringyhtio"] == 'OKO' and $frow["viitetyyppi"] == '') {
            $viite = $frow["sopimusnumero"]."001".sprintf('%09d', $lasno);
          }
          elseif ($frow["sopimusnumero"] > 0 and $frow["factoringyhtio"] == 'SAMPO' and $frow["viitetyyppi"] == '') {
            $viite = $frow["sopimusnumero"]."1".sprintf('%09d', $lasno);
          }
          elseif ($frow["sopimusnumero"] > 0 and $frow["factoringyhtio"] == 'AKTIA' and $frow["viitetyyppi"] == '') {
            $viite = str_pad($frow["sopimusnumero"], 6, '0', STR_PAD_RIGHT).sprintf("%010d", $lasno);
          }
          else {
            $viite = $lasno;
          }

          // Tutkitaan käytetäänkö maksuehdon pankkiyhteystietoja
          $query  = "SELECT pankkiyhteystiedot.viite
                     FROM lasku
                     JOIN maksuehto ON lasku.yhtio=maksuehto.yhtio and lasku.maksuehto=maksuehto.tunnus
                     JOIN pankkiyhteystiedot ON maksuehto.yhtio=pankkiyhteystiedot.yhtio and maksuehto.pankkiyhteystiedot = pankkiyhteystiedot.tunnus and pankkiyhteystiedot.viite = 'SE'
                     WHERE lasku.yhtio = '$kukarow[yhtio]'
                     and lasku.tunnus  in ($tunnukset)";
          $pankres = pupe_query($query);

          $seviite = "";

          if (mysqli_num_rows($pankres) > 0) {
            $seviite = "SE";
          }

          //  Onko käsinsyötetty viite?
          $query = "SELECT kasinsyotetty_viite
                    FROM laskun_lisatiedot
                    WHERE yhtio              = '$kukarow[yhtio]'
                    AND otunnus              IN ($tunnukset)
                    AND kasinsyotetty_viite != ''";
          $tarkres = pupe_query($query);

          if (mysqli_num_rows($tarkres) == 1) {
            $tarkrow = mysqli_fetch_assoc($tarkres) or pupe_error($tarkres);
            $viite = $tarkrow["kasinsyotetty_viite"];

            if ($seviite != 'SE') {
              //  Jos viitenumero on väärin mennään oletuksilla!
              if (substr($viite, 0, 2) != "RF" and tarkista_viite($viite) === FALSE) {
                $viite = $lasno;
                $tulos_ulos .= "<font class='message'><br>\n".t("HUOM: laskun '%s' käsinsyotetty viitenumero '%s' on väärin! Laskulle annettii uusi viite '%s'", "", $lasno, $tarkrow["kasinsyotetty_viite"], $viite)."!</font><br>\n<br>\n";
                require 'inc/generoiviite.inc';
              }
              elseif (substr($viite, 0, 2) == "RF" and tarkista_rfviite($viite) === FALSE) {
                $viite = $lasno;
                $tulos_ulos .= "<font class='message'><br>\n".t("HUOM: laskun '%s' käsinsyotetty RF-viitenumero '%s' on väärin! Laskulle annettii uusi viite '%s'", "", $lasno, $tarkrow["kasinsyotetty_viite"], $viite)."!</font><br>\n<br>\n";
                require 'inc/generoiviite.inc';
              }
            }
          }
          else {
            if ($seviite == 'SE') {
              require 'inc/generoiviite_se.inc';
            }
            else {
              require 'inc/generoiviite.inc';
            }
          }

          // päivitetään ketjuun kuuluville laskuille sama laskunumero ja viite..
          $query  = "UPDATE lasku SET
                     laskunro    = '$lasno',
                     viite       = '$viite'
                     WHERE yhtio = '$kukarow[yhtio]'
                     AND tunnus  IN ($tunnukset)";
          $result = pupe_query($query);

          // tehdään U lasku ja tiliöinnit
          // tarvitaan $tunnukset mysql muodossa

          require "teeulasku.inc";

          // saadaan takaisin $laskurow
          $lasrow = $laskurow;

          // Luodaan tullausnumero jos sellainen tarvitaan
          // Jos on esim puhtaasti hyvitystä niin ei generoida tullausnumeroa
          if ($lasrow["vienti"] == 'K' and $lasrow["sisainen"] == "") {
            $query = "SELECT tilausrivi.yhtio
                      FROM tilausrivi
                      JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.ei_saldoa = '')
                      WHERE tilausrivi.uusiotunnus = '$lasrow[tunnus]'
                      and tilausrivi.kpl           > 0
                      and tilausrivi.yhtio         = '$kukarow[yhtio]'
                      and tilausrivi.tyyppi        = 'L'
                      and tilausrivi.var           not in ('P','J','O','S')";
            $cresult = pupe_query($query);

            $hyvitys = "";
            if (mysqli_num_rows($cresult) == 0) {
              //laskulla on vain hyvitysrivejä, tai ei yhtään riviä --> ei tullata!
              $hyvitys = "ON";
            }
            else {
              $hyvitys = "EI";
            }

            $tullausnumero = '';

            if ($hyvitys == 'EI') {
              //generoidaan tullausnumero.
              $p = date('d');
              $k = date('m');
              $v = date('Y');
              $pvm = $v."-".$k."-".$p;

              $query = "SELECT count(*)+1 tullausnumero
                        FROM lasku use index (yhtio_tila_tapvm)
                        WHERE vienti       = 'K'
                        and tila           = 'U'
                        and alatila        = 'X'
                        and tullausnumero != ''
                        and tapvm          = '$pvm'
                        and yhtio          = '$kukarow[yhtio]'";
              $result= pupe_query($query);
              $lrow  = mysqli_fetch_assoc($result);

              $pvanumero = date('z')+1;

              //tullausnumero muodossa Vuosi-Tullikamari-Päivännumero-Tullipääte-Juoksevanumeroperpäivä
              $tullausnumero = date('y') . "-". $yhtiorow["tullikamari"] ."-" . sprintf('%03d', $pvanumero) . "-" . $yhtiorow["tullipaate"] . "-" . sprintf('%03d', $lrow["tullausnumero"]);

              // päivitetään ketjuun kuuluville laskuille sama laskunumero ja viite..
              $query  = "UPDATE lasku set tullausnumero='$tullausnumero' WHERE vienti='K' and tila='U' and yhtio='$kukarow[yhtio]' and tunnus='$lasrow[tunnus]'";
              $result = pupe_query($query);

              $lasrow["tullausnumero"] = $tullausnumero;
            }
          }

          if ($silent == "") {
            $tulos_ulos .= $tulos_ulos_ulasku;
            $tulos_ulos .= $tulos_ulos_tiliointi;
          }

          // Haetaan maksuehdon tiedot
          $query  = "SELECT pankkiyhteystiedot.*, maksuehto.*
                     FROM maksuehto
                     LEFT JOIN pankkiyhteystiedot on (pankkiyhteystiedot.yhtio=maksuehto.yhtio and pankkiyhteystiedot.tunnus=maksuehto.pankkiyhteystiedot)
                     WHERE maksuehto.yhtio='$kukarow[yhtio]' and maksuehto.tunnus='$lasrow[maksuehto]'";
          $result = pupe_query($query);

          if (mysqli_num_rows($result) == 0) {
            $masrow = array();

            if ($lasrow["erpcm"] == "0000-00-00") {
              $tulos_ulos .= "<font class='message'><br>\n".t("Maksuehtoa")." $lasrow[maksuehto] ".t("ei löydy!")." Tunnus $lasrow[tunnus] ".t("Laskunumero")." $lasrow[laskunro] ".t("epäonnistui pahasti")."!</font><br>\n<br>\n";
            }
          }
          else {
            $masrow = mysqli_fetch_assoc($result);
          }

          //Haetaan factoringsopimuksen tiedot
          if (isset($masrow["factoring_id"])) {
            $query = "SELECT *
                      FROM factoring
                      WHERE yhtio  = '$kukarow[yhtio]'
                      and tunnus   = '$masrow[factoring_id]'
                      and valkoodi = '$lasrow[valkoodi]'";
            $fres = pupe_query($query);
            $frow = mysqli_fetch_assoc($fres);
          }
          else {
            unset($frow);
          }

          $pankkitiedot = array();

          //Laitetaan pankkiyhteystiedot kuntoon
          if (isset($masrow["factoring_id"])) {
            $pankkitiedot["pankkinimi1"]  = $frow["pankkinimi1"];
            $pankkitiedot["pankkitili1"]  = $frow["pankkitili1"];
            $pankkitiedot["pankkiiban1"]  = $frow["pankkiiban1"];
            $pankkitiedot["pankkiswift1"] = $frow["pankkiswift1"];
            $pankkitiedot["pankkinimi2"]  = $frow["pankkinimi2"];
            $pankkitiedot["pankkitili2"]  = $frow["pankkitili2"];
            $pankkitiedot["pankkiiban2"]  = $frow["pankkiiban2"];
            $pankkitiedot["pankkiswift2"] = $frow["pankkiswift2"];
            $pankkitiedot["pankkinimi3"]  = "";
            $pankkitiedot["pankkitili3"]  = "";
            $pankkitiedot["pankkiiban3"]  = "";
            $pankkitiedot["pankkiswift3"] = "";

          }
          elseif ($masrow["pankkinimi1"] != "") {
            $pankkitiedot["pankkinimi1"]  = $masrow["pankkinimi1"];
            $pankkitiedot["pankkitili1"]  = $masrow["pankkitili1"];
            $pankkitiedot["pankkiiban1"]  = $masrow["pankkiiban1"];
            $pankkitiedot["pankkiswift1"] = $masrow["pankkiswift1"];
            $pankkitiedot["pankkinimi2"]  = $masrow["pankkinimi2"];
            $pankkitiedot["pankkitili2"]  = $masrow["pankkitili2"];
            $pankkitiedot["pankkiiban2"]  = $masrow["pankkiiban2"];
            $pankkitiedot["pankkiswift2"] = $masrow["pankkiswift2"];
            $pankkitiedot["pankkinimi3"]  = $masrow["pankkinimi3"];
            $pankkitiedot["pankkitili3"]  = $masrow["pankkitili3"];
            $pankkitiedot["pankkiiban3"]  = $masrow["pankkiiban3"];
            $pankkitiedot["pankkiswift3"] = $masrow["pankkiswift3"];
          }
          else {
            $pankkitiedot["pankkinimi1"]  = $yhtiorow["pankkinimi1"];
            $pankkitiedot["pankkitili1"]  = $yhtiorow["pankkitili1"];
            $pankkitiedot["pankkiiban1"]  = $yhtiorow["pankkiiban1"];
            $pankkitiedot["pankkiswift1"] = $yhtiorow["pankkiswift1"];
            $pankkitiedot["pankkinimi2"]  = $yhtiorow["pankkinimi2"];
            $pankkitiedot["pankkitili2"]  = $yhtiorow["pankkitili2"];
            $pankkitiedot["pankkiiban2"]  = $yhtiorow["pankkiiban2"];
            $pankkitiedot["pankkiswift2"] = $yhtiorow["pankkiswift2"];
            $pankkitiedot["pankkinimi3"]  = $yhtiorow["pankkinimi3"];
            $pankkitiedot["pankkitili3"]  = $yhtiorow["pankkitili3"];
            $pankkitiedot["pankkiiban3"]  = $yhtiorow["pankkiiban3"];
            $pankkitiedot["pankkiswift3"] = $yhtiorow["pankkiswift3"];
          }

          $asiakas_apu_query = "SELECT *
                                FROM asiakas
                                WHERE yhtio = '$kukarow[yhtio]'
                                AND tunnus  = '$lasrow[liitostunnus]'";
          $asiakas_apu_res = pupe_query($asiakas_apu_query);

          if (mysqli_num_rows($asiakas_apu_res) == 1) {
            $asiakas_apu_row = mysqli_fetch_assoc($asiakas_apu_res);
          }
          else {
            $asiakas_apu_row = array();
          }

          $laskun_kieli = laskunkieli($lasrow['liitostunnus'], $kieli);

          if (verkkolaskuputkeen($lasrow, $masrow)) {

            // Nyt meillä on:
            // $lasrow array on U-laskun tiedot
            // $yhtiorow array on yhtion tiedot
            // $masrow array maksuehdon tiedot

            // Etsitään myyjän nimi
            $mquery  = "SELECT nimi, puhno, eposti
                        FROM kuka
                        WHERE tunnus = '$lasrow[myyja]'
                        and yhtio    = '$kukarow[yhtio]'";
            $myyresult = pupe_query($mquery);
            $myyrow = mysqli_fetch_assoc($myyresult);

            $lasrow['chn_orig'] = $lasrow['chn'];

            //HUOM: Tässä kaikki sallitut verkkopuolen chn:ät
            if (!in_array($lasrow['chn'], array("100", "010", "001", "020", "111", "112", "113"))) {
              //Paperi by default
              $lasrow['chn'] = "100";
            }

            if ($lasrow['chn'] == "020") {
              $lasrow['chn'] = "010";
            }

            if ($lasrow['arvo'] >= 0) {
              //Veloituslasku
              $tyyppi='380';
            }
            else {
              //Hyvityslasku
              $tyyppi='381';
            }

            // Laskukohtaiset kommentit kuntoon
            // Tämä merkki | eli pystyviiva on rivinvaihdon merkki laskun kommentissa elmalla
            $komm = "";

            // Onko käänteistä verotusta
            $alvquery = "SELECT tunnus
                         FROM tilausrivi
                         WHERE yhtio = '$kukarow[yhtio]'
                         and otunnus in ($tunnukset)
                         and tyyppi  = 'L'
                         and var     not in ('P','J','O','S')
                         and alv     >= 600";
            $alvresult = pupe_query($alvquery);

            if (mysqli_num_rows($alvresult) > 0) {
              $komm .= t_avainsana("KAANTALVVIESTI", $laskun_kieli, "", "", "", "selitetark");
            }

            if (trim($lasrow['tilausyhteyshenkilo']) != '') {
              $komm .= "\n".t("Tilaaja", $laskun_kieli).": ".$lasrow['tilausyhteyshenkilo'];
            }

            if (trim($lasrow['asiakkaan_tilausnumero']) != '') {
              $komm .= "\n".t("Tilauksenne", $laskun_kieli).": ".$lasrow['asiakkaan_tilausnumero'];
            }

            if (trim($lasrow['kohde']) != '') {
              $komm .= "\n".t("Kohde", $laskun_kieli).": ".$lasrow['kohde'];
            }

            if (trim($lasrow['sisviesti1']) != '') {
              $komm .= "\n".t("Kommentti", $laskun_kieli).": ".$lasrow['sisviesti1'];
            }

            if (trim($komm) != '') {
              $lasrow['sisviesti1'] = str_replace(array("\r\n", "\r", "\n"), "|", trim($komm));
            }

            // Hoidetaan pyöristys sekä valuuttakäsittely
            if ($lasrow["valkoodi"] != '' and trim(strtoupper($lasrow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
              $lasrow["kasumma"]   = $lasrow["kasumma_valuutassa"];
              $lasrow["summa"]     = $lasrow["summa_valuutassa"];
              $lasrow["arvo"]      = $lasrow["arvo_valuutassa"];
              $lasrow["pyoristys"] = $lasrow["pyoristys_valuutassa"];
            }

            if (strtoupper($laskun_kieli) != strtoupper($yhtiorow['kieli'])) {
              //Käännetään maksuehto
              $masrow["teksti"] = t_tunnus_avainsanat($masrow, "teksti", "MAKSUEHTOKV", $laskun_kieli);
            }

            $query = "SELECT
                      ifnull(min(date_format(if('$yhtiorow[tilausrivien_toimitettuaika]' = 'X', toimaika, if('$yhtiorow[tilausrivien_toimitettuaika]' = 'K' and keratty = 'saldoton', toimaika, toimitettuaika)), '%Y-%m-%d')), '0000-00-00') mint,
                      ifnull(max(date_format(if('$yhtiorow[tilausrivien_toimitettuaika]' = 'X', toimaika, if('$yhtiorow[tilausrivien_toimitettuaika]' = 'K' and keratty = 'saldoton', toimaika, toimitettuaika)), '%Y-%m-%d')), '0000-00-00') maxt
                      FROM tilausrivi
                      WHERE yhtio         = '$kukarow[yhtio]'
                      and otunnus         in ($tunnukset)
                      and toimitettuaika != '0000-00-00 00:00:00'
                      and tyyppi          = 'L'
                      and var             not in ('P','J','O','S')";
            $toimaikares = pupe_query($query);
            $toimaikarow = mysqli_fetch_assoc($toimaikares);

            if ($toimaikarow["mint"] == "0000-00-00") {
              $toimaikarow["mint"] = date("Y-m-d");
            }
            if ($toimaikarow["maxt"] == "0000-00-00") {
              $toimaikarow["maxt"] = date("Y-m-d");
            }

            // Laskun kaikki tilaukset
            $lasrow['tilausnumerot'] = $tunnukset;

	    if(!isset($verkkolasku_talenom_saanto)) {
              $verkkolasku_talenom_saanto = false;
            }

            //Kirjoitetaan failiin laskun otsikkotiedot
            if ($lasrow["chn"] == "111") {
              elmaedi_otsik($tootedi, $lasrow, $masrow, $tyyppi, $timestamppi, $toimaikarow);
            }
            elseif ($lasrow["chn"] == "112") {
              if($yhtiorow["finvoice_versio"] == "3") {
                finvoice_otsik($tootsisainenfinvoice, $lasrow, $kieli, $pankkitiedot, $masrow, $myyrow, $tyyppi, $toimaikarow, $tulos_ulos, $silent, "", $tunnukset, $asiakas_apu_row, $verkkolasku_talenom_saanto);
              } else {
                finvoice_otsik($tootsisainenfinvoice, $lasrow, $kieli, $pankkitiedot, $masrow, $myyrow, $tyyppi, $toimaikarow, $tulos_ulos, $silent, "", $verkkolasku_talenom_saanto);
              }
            }
            elseif (in_array($yhtiorow["verkkolasku_lah"], array("iPost", "finvoice", "maventa", "trustpoint", "ppg", "apix", "sepa", "talenom", "arvato"))) {
              if($yhtiorow["finvoice_versio"] == "3") {
                finvoice_otsik($tootfinvoice, $lasrow, $kieli, $pankkitiedot, $masrow, $myyrow, $tyyppi, $toimaikarow, $tulos_ulos, $silent, $nosoap, $tunnukset, $asiakas_apu_row, $verkkolasku_talenom_saanto);
              } else {
                finvoice_otsik($tootfinvoice, $lasrow, $kieli, $pankkitiedot, $masrow, $myyrow, $tyyppi, $toimaikarow, $tulos_ulos, $silent, $nosoap, $verkkolasku_talenom_saanto);
              }
              
            }
            else {
              pupevoice_otsik($tootxml, $lasrow, $laskun_kieli, $pankkitiedot, $masrow, $myyrow, $tyyppi, $toimaikarow);
            }

            // Tarvitaan rivien eri verokannat
            $alvquery = "SELECT distinct alv
                         FROM tilausrivi
                         WHERE yhtio = '$kukarow[yhtio]'
                         and otunnus in ($tunnukset)
                         and tyyppi  = 'L'
                         and var     not in ('P','J','O','S')
                         ORDER BY alv";
            $alvresult = pupe_query($alvquery);

            while ($alvrow1 = mysqli_fetch_assoc($alvresult)) {

              if ($alvrow1["alv"] >= 500) {
                $aquery = "SELECT '0' alv,
                           round(sum(tilausrivi.rivihinta/if (lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1)),2) rivihinta,
                           round(sum(0),2) alvrivihinta
                           FROM tilausrivi
                           JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
                           WHERE tilausrivi.uusiotunnus = '$lasrow[tunnus]'
                           and tilausrivi.yhtio         = '$kukarow[yhtio]'
                           and tilausrivi.alv           = '$alvrow1[alv]'
                           and tilausrivi.tyyppi        = 'L'
                           and tilausrivi.var           not in ('P','J','O','S')
                           GROUP BY alv";
              }
              else {
                $aquery = "SELECT tilausrivi.alv,
                           round(sum(tilausrivi.rivihinta/if (lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1)),2) rivihinta,
                           round(sum((tilausrivi.rivihinta/if (lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1))*(tilausrivi.alv/100)),2) alvrivihinta
                           FROM tilausrivi
                           JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
                           WHERE tilausrivi.uusiotunnus = '$lasrow[tunnus]'
                           and tilausrivi.yhtio         = '$kukarow[yhtio]'
                           and tilausrivi.alv           = '$alvrow1[alv]'
                           and tilausrivi.tyyppi        = 'L'
                           and tilausrivi.var           not in ('P','J','O','S')
                           GROUP BY alv";
              }
              $aresult = pupe_query($aquery);
              $alvrow = mysqli_fetch_assoc($aresult);

              // Kirjotetaan failiin arvierittelyt
              if ($lasrow["chn"] == "111") {
                elmaedi_alvierittely($tootedi, $alvrow);
              }
              elseif ($lasrow["chn"] == "112") {
                finvoice_alvierittely($tootsisainenfinvoice, $lasrow, $alvrow);
              }
              elseif (in_array($yhtiorow["verkkolasku_lah"], array("iPost", "finvoice", "maventa", "trustpoint", "ppg", "apix", "sepa", "talenom", "arvato"))) {
                finvoice_alvierittely($tootfinvoice, $lasrow, $alvrow);
              }
              else {
                pupevoice_alvierittely($tootxml, $alvrow);
              }
            }

            //Kirjoitetaan otsikkojen lopputiedot
            if ($lasrow["chn"] == "111") {
              elmaedi_otsikko_loput($tootedi, $lasrow);
            }
            elseif ($lasrow["chn"] == "112") {
              finvoice_otsikko_loput($tootsisainenfinvoice, $lasrow, $masrow, $pankkitiedot);
            }
            elseif (in_array($yhtiorow["verkkolasku_lah"], array("iPost", "finvoice", "maventa", "trustpoint", "ppg", "apix", "sepa", "talenom", "arvato"))) {
              finvoice_otsikko_loput($tootfinvoice, $lasrow, $masrow, $pankkitiedot);
            }

            // katotaan miten halutaan sortattavan
            // haetaan asiakkaan tietojen takaa sorttaustiedot
            $order_sorttaus = '';

            if (mysqli_num_rows($asiakas_apu_res) == 1) {
              $sorttauskentta = generoi_sorttauskentta($asiakas_apu_row["laskun_jarjestys"] != "" ? $asiakas_apu_row["laskun_jarjestys"] : $yhtiorow["laskun_jarjestys"]);
              $order_sorttaus = $asiakas_apu_row["laskun_jarjestys_suunta"] != "" ? $asiakas_apu_row["laskun_jarjestys_suunta"] : $yhtiorow["laskun_jarjestys_suunta"];
            }
            else {
              $sorttauskentta = generoi_sorttauskentta($yhtiorow["laskun_jarjestys"]);
              $order_sorttaus = $yhtiorow["laskun_jarjestys_suunta"];
            }

            // Asiakkaan / yhtiön laskutyyppi
            if ($lasrow['laskutyyppi'] == -9 or $lasrow['laskutyyppi'] == 0) {
              //jos laskulta löytyvät laskutyyppi on Oletus käytetään asiakkaan tai yhtiön oletus laskutyyppiä
              if (isset($asiakas_apu_row['laskutyyppi']) and $asiakas_apu_row['laskutyyppi'] != -9) {
                $laskutyyppi = $asiakas_apu_row['laskutyyppi'];
              }
              else {
                $laskutyyppi = $yhtiorow['laskutyyppi'];
              }
            }
            else {
              $laskutyyppi = $lasrow['laskutyyppi'];
            }

            if ($yhtiorow["laskun_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
            else $pjat_sortlisa = "";

            $query_ale_lisa = generoi_alekentta('M');
            $ale_query_select_lisa = generoi_alekentta_select('yhteen', 'M');

            // Haetaan laskun kaikki rivit
            $query = "SELECT
                      if ((tilausrivi.nimitys='Kuljetusvakuutus' OR '{$yhtiorow['yhdistetaan_identtiset_laskulla']}' = 'k'), tilausrivin_lisatiedot.vanha_otunnus, ifnull((SELECT vanha_otunnus from tilausrivin_lisatiedot t_lisa where t_lisa.yhtio=tilausrivi.yhtio and t_lisa.tilausrivitunnus=tilausrivi.perheid and t_lisa.omalle_tilaukselle != ''), tilausrivi.tunnus)) rivigroup,
                      tilausrivi.ale1,
                      tilausrivi.ale2,
                      tilausrivi.ale3,
                      $ale_query_select_lisa aleyhteensa,
                      tilausrivi.alv,
                      tuote.eankoodi,
                      tuote.ei_saldoa,
                      tilausrivi.erikoisale,
                      tilausrivi.nimitys,
                      tilausrivin_lisatiedot.osto_vai_hyvitys,
                      tuote.sarjanumeroseuranta,
                      tilausrivi.tuoteno,
                      tilausrivi.uusiotunnus,
                      tilausrivi.yksikko,
                      tilausrivi.hinta,
                      tilausrivi.netto,
                      tilausrivi.ale_peruste,
                      lasku.vienti_kurssi,
                      lasku.viesti laskuviesti,
                      lasku.asiakkaan_tilausnumero,
                      lasku.luontiaika tilauspaiva,
                      CONCAT(tuote.tullinimike1, IF(tuote.tullinimike2 NOT IN ('', '00', '0'), tuote.tullinimike2, '')) AS tullinimike,
                      if (tuote.tuotetyyppi = 'K','2 Työt','1 Muut') tuotetyyppi,
                      if (tilausrivi.var2 = 'EIOST', 'EIOST', '') var2,
                      if (tuote.myyntihinta_maara = 0, 1, tuote.myyntihinta_maara) myyntihinta_maara,
                      min(tilausrivi.hyllyalue) hyllyalue,
                      min(tilausrivi.hyllynro) hyllynro,
                      min(tilausrivi.keratty) keratty,
                      min(if (tilausrivi.toimaika = '0000-00-00', date_format(now(), '%Y-%m-%d'), tilausrivi.toimaika)) toimaika,
                      min(if (date_format(tilausrivi.toimitettuaika, '%Y-%m-%d') = '0000-00-00', date_format(now(), '%Y-%m-%d'), date_format(tilausrivi.toimitettuaika, '%Y-%m-%d'))) toimitettuaika,
                      min(tilausrivi.otunnus) otunnus,
                      min(tilausrivi.perheid) perheid,
                      min(tilausrivi.tunnus) tunnus,
                      min(tilausrivi.kommentti) kommentti,
                      min(tilausrivi.tilaajanrivinro) tilaajanrivinro,
                      min(tilausrivi.laadittu) laadittu,
                      min(tilausrivin_lisatiedot.tiliointirivitunnus) tiliointirivitunnus,
                      sum(tilausrivi.tilkpl) tilkpl,
                      sum(tilausrivi.kpl) kpl,
                      sum(tilausrivi.rivihinta) rivihinta,
                      sum(round(tilausrivi.hinta * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}, $yhtiorow[hintapyoristys])) rivihinta_verollinen,
                      sum((tilausrivi.hinta / lasku.vienti_kurssi) / if ('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}) rivihinta_valuutassa,
                      sum((tilausrivi.hinta / lasku.vienti_kurssi) * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}) rivihinta_valuutassa_verollinen,
                      group_concat(tilausrivi.tunnus) rivitunnukset,
                      group_concat(distinct tilausrivi.perheid) perheideet,
                      count(*) rivigroup_maara,
                      $sorttauskentta
                      FROM tilausrivi
                      JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
                      JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
                      LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
                      WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
                      and (tilausrivi.perheid = 0 or tilausrivi.perheid=tilausrivi.tunnus or tilausrivin_lisatiedot.ei_nayteta !='E' or tilausrivin_lisatiedot.ei_nayteta is null)
                      and tilausrivi.kpl     != 0
                      and tilausrivi.tyyppi   = 'L'
                      and tilausrivi.otunnus  in ($tunnukset)
                      GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25
                      ORDER BY tilausrivi.otunnus, if(tilausrivi.tuoteno in ('$yhtiorow[kuljetusvakuutus_tuotenumero]','$yhtiorow[laskutuslisa_tuotenumero]'), 2, 1), $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
            $tilres = pupe_query($query);

            $rivinumerot   = array(0 => 0);
            $rivilaskuri   = 1;
            $rivimaara     = mysqli_num_rows($tilres);
            $rivigrouppaus = FALSE;
            $tilrows       = array();

            while ($tilrow = mysqli_fetch_assoc($tilres)) {
              if ($yhtiorow["pura_osaluettelot"] != "") {
                // Korvataanko tilauksella oleva rivi osaluettelolla
                $tilrows = array_merge($tilrows, pura_osaluettelot($lasrow, $tilrow, $laskutyyppi));
              }
              else {
                $tilrows[] = $tilrow;
              }
            }

            foreach ($tilrows as $tilrow) {
              // Näytetään vain perheen isä ja summataan lasten hinnat isäriville
              if ($laskutyyppi == 2 or $laskutyyppi == 12) {
                if ($tilrow["perheid"] > 0) {
                  // kyseessä on isä
                  if ($tilrow["perheid"] == $tilrow["tunnus"]) {
                    // lasketaan isätuotteen riville lapsien hinnat yhteen
                    $query = "SELECT
                              sum(tilausrivi.rivihinta) rivihinta,
                              round(sum(tilausrivi.hinta
                                  * tilausrivi.kpl
                                  * {$query_ale_lisa})
                                / $tilrow[kpl], '$yhtiorow[hintapyoristys]') hinta,
                              sum(round(tilausrivi.hinta * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * tilausrivi.kpl * {$query_ale_lisa}, $yhtiorow[hintapyoristys])) rivihinta_verollinen,
                              sum((tilausrivi.hinta / lasku.vienti_kurssi) / if ('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * tilausrivi.kpl * {$query_ale_lisa}) rivihinta_valuutassa,
                              sum((tilausrivi.hinta / lasku.vienti_kurssi) * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * tilausrivi.kpl * {$query_ale_lisa}) rivihinta_valuutassa_verollinen
                              FROM tilausrivi
                              JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
                              WHERE tilausrivi.yhtio     = '$kukarow[yhtio]'
                              and tilausrivi.uusiotunnus = '$tilrow[uusiotunnus]'
                              and tilausrivi.perheid     in ($tilrow[perheideet])
                              and tilausrivi.perheid     > 0";
                    $riresult = pupe_query($query);
                    $perherow = mysqli_fetch_assoc($riresult);

                    $tilrow["hinta"] = $perherow["hinta"];
                    $tilrow["rivihinta"] = $perherow["rivihinta"];
                    $tilrow["rivihinta_verollinen"] = $perherow["rivihinta_verollinen"];
                    $tilrow["rivihinta_valuutassa"] = $perherow["rivihinta_valuutassa"];
                    $tilrow["rivihinta_valuutassa_verollinen"] = $perherow["rivihinta_valuutassa_verollinen"];

                    // Nollataan alet, koska hinta lasketaan rivihinnasta jossa alet on jo huomioitu
                    for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                      $tilrow["ale{$alepostfix}"] = "";
                    }

                    $tilrow["erikoisale"] = "";
                  }
                  else {
                    // lapsia ei lisätä
                    $lisataa = 1;
                  }
                }
              }

              if (strtolower($laskun_kieli) != strtolower($yhtiorow['kieli'])) {
                //Käännetään nimitys
                $tilrow['nimitys'] = t_tuotteen_avainsanat($tilrow, 'nimitys', $laskun_kieli);
              }

              // Rivin toimitusaika
              if ($yhtiorow["tilausrivien_toimitettuaika"] == 'K' and $tilrow["keratty"] == "saldoton") {
                $tilrow["toimitettuaika"] = $tilrow["toimaika"];
              }
              elseif ($yhtiorow["tilausrivien_toimitettuaika"] == 'X') {
                $tilrow["toimitettuaika"] = $tilrow["toimaika"];
              }
              else {
                $tilrow["toimitettuaika"] = $tilrow["toimitettuaika"];
              }

              if ($tilrow["rivigroup_maara"] > 1 and !$rivigrouppaus) {
                $rivigrouppaus = TRUE;
              }

              // Otetaan yhteensäkommentti pois jos summataan rivejä
              if ($rivigrouppaus) {
                // Trimmataan ja otetaan "yhteensäkommentti" pois
                $tilrow["kommentti"] = trim(poista_rivin_yhteensakommentti($tilrow["kommentti"]));
              }

              // Laitetaan alennukset kommenttiin, koska laskulla on vain yksi alekenttä
              if ($yhtiorow['myynnin_alekentat'] > 1 or $tilrow['erikoisale'] > 0) {

                $alekomm = "";

                for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                  if (trim($tilrow["ale{$alepostfix}"]) > 0) {
                    $alekomm .= t("Ale")."{$alepostfix} ".($tilrow["ale{$alepostfix}"]*1)." %|";
                  }
                }

                if ($tilrow['erikoisale'] > 0) {
                  $alekomm .= t("Erikoisale")." ".($tilrow["erikoisale"]*1)." %|";
                }

                $tilrow["kommentti"] = $alekomm.$tilrow["kommentti"];
              }

              // Käännetty arvonlisäverovelvollisuus ja käytetyn tavaran myynti
              if ($tilrow["alv"] >= 600) {
                $tilrow["alv"] = 0;
                $tilrow["kommentti"] .= " Ei lisättyä arvonlisäveroa, ostajan käännetty verovelvollisuus.";
              }
              elseif ($tilrow["alv"] >= 500) {
                $tilrow["alv"] = 0;
                $tilrow["kommentti"] .= " Ei sisällä vähennettävää veroa.";
              }

              //Hetaan sarjanumeron tiedot
              if ($tilrow["kpl"] > 0) {
                $sarjanutunnus = "myyntirivitunnus";
              }
              else {
                $sarjanutunnus = "ostorivitunnus";
              }

              $query = "SELECT *
                        FROM sarjanumeroseuranta
                        WHERE yhtio      = '$kukarow[yhtio]'
                        and tuoteno      = '$tilrow[tuoteno]'
                        and $sarjanutunnus in ($tilrow[rivitunnukset])
                        and sarjanumero != ''";
              $sarjares = pupe_query($query);

              if ($tilrow["kommentti"] != '' and mysqli_num_rows($sarjares) > 0) {
                $tilrow["kommentti"] .= " ";
              }
              while ($sarjarow = mysqli_fetch_assoc($sarjares)) {
                $tilrow["kommentti"] .= "S:nro: $sarjarow[sarjanumero] ";
              }

              if ($laskutyyppi == 7) {

                if ($tilrow["eankoodi"] != "") {
                  $tilrow["kommentti"] = "EAN: $tilrow[eankoodi]|$tilrow[kommentti]";
                }

                $query = "SELECT kommentti
                          FROM asiakaskommentti
                          WHERE yhtio = '{$kukarow['yhtio']}'
                          AND tuoteno = '{$tilrow['tuoteno']}'
                          AND ytunnus = '{$lasrow['ytunnus']}'
                          AND tyyppi  = ''
                          ORDER BY tunnus";
                $asiakaskommentti_res = pupe_query($query);

                if (mysqli_num_rows($asiakaskommentti_res) > 0) {
                  while ($asiakaskommentti_row = mysqli_fetch_assoc($asiakaskommentti_res)) {
                    $tilrow["kommentti"] .= "|".$asiakaskommentti_row['kommentti'];
                  }
                }
              }

              if ($lasrow["valkoodi"] != '' and trim(strtoupper($lasrow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
                // Veroton rivihinta valuutassa
                $tilrow["rivihinta"] = $tilrow["rivihinta_valuutassa"];

                // Verollinen rivihinta valuutassa
                $tilrow["rivihinta_verollinen"] = $tilrow["rivihinta_valuutassa_verollinen"];

                // Yksikköhinta valuutassa
                $tilrow["hinta"] = laskuval($tilrow["hinta"], $tilrow["vienti_kurssi"]);
              }

              // Yksikköhinta on laskulla aina veroton
              if ($yhtiorow["alv_kasittely"] == '') {
                // Tuotteiden myyntihinnat sisältävät arvonlisäveron
                $tilrow["hinta"] = $tilrow["hinta"] / (1 + $tilrow["alv"] / 100);
                $tilrow["hinta_verollinen"] = $tilrow["hinta"];
              }
              else {
                // Tuotteiden myyntihinnat ovat arvonlisäverottomia
                $tilrow["hinta"] = $tilrow["hinta"];
                $tilrow["hinta_verollinen"] = $tilrow["hinta"] * (1 + $tilrow["alv"] / 100);
              }

              // Veron määrä
              $vatamount = $tilrow['rivihinta'] * $tilrow['alv'] / 100;

              // Pyöristetään ja formatoidaan lopuksi
              $tilrow["hinta"] = hintapyoristys($tilrow["hinta"]);
              $tilrow["rivihinta"] = hintapyoristys($tilrow["rivihinta"]);
              $tilrow["rivihinta_verollinen"] = hintapyoristys($tilrow["rivihinta_verollinen"]);
              $vatamount = hintapyoristys($vatamount);

              $tilrow['kommentti'] = pupesoft_invoicestring(str_replace("\n", "|", $tilrow['kommentti']));
              $tilrow['nimitys']   = pupesoft_invoicestring($tilrow['nimitys']);

              // Otetaan seuraavan rivin otunnus
              if ($rivilaskuri < $rivimaara) {
                $tilrow_seuraava = mysqli_fetch_assoc($tilres);
                mysqli_data_seek($tilres, $rivilaskuri);

                if ($tilrow_seuraava['tuoteno'] == $yhtiorow["kuljetusvakuutus_tuotenumero"] or $tilrow_seuraava['tuoteno'] == $yhtiorow["laskutuslisa_tuotenumero"]) {
                  $tilrow['seuraava_otunnus'] = 0;
                }
                else {
                  $tilrow['seuraava_otunnus'] = $tilrow_seuraava["otunnus"];
                }
              }
              else {
                $tilrow['seuraava_otunnus'] = 0;
              }

              if ($lasrow["chn"] == "111") {

                if ((int) substr(sprintf("%06s", $tilrow["tilaajanrivinro"]), -6) > 0 and !in_array((int) substr(sprintf("%06s", $tilrow["tilaajanrivinro"]), -6), $rivinumerot)) {
                  $rivinumero = (int) substr(sprintf("%06s", $tilrow["tilaajanrivinro"]), -6);
                }
                else {
                  $rivinumero = (int) substr(sprintf("%06s", $tilrow["tunnus"]), -6);
                }

                elmaedi_rivi($tootedi, $tilrow, $rivinumero);
              }
              elseif ($lasrow["chn"] == "112") {
                if ($yhtiorow["finvoice_versio"] == "3") {
                  finvoice_rivi($tootsisainenfinvoice, $tilrow, $lasrow, $vatamount, $laskutyyppi, $rivilaskuri);
                } else {
                  finvoice_rivi($tootsisainenfinvoice, $tilrow, $lasrow, $vatamount, $laskutyyppi);
                }
              }
              elseif (in_array($yhtiorow["verkkolasku_lah"], array("iPost", "finvoice", "maventa", "trustpoint", "ppg", "apix", "sepa", "talenom", "arvato"))) {
                if ($yhtiorow["finvoice_versio"] == "3") {
                  finvoice_rivi($tootfinvoice, $tilrow, $lasrow, $vatamount, $laskutyyppi, $rivilaskuri);
                } else {
                  finvoice_rivi($tootfinvoice, $tilrow, $lasrow, $vatamount, $laskutyyppi);
                }
              }
              else {
                pupevoice_rivi($tootxml, $tilrow, $vatamount);
              }

              $rivilaskuri++;
            }

            //Lopetetaan lasku
            if ($lasrow["chn"] == "111") {
              elmaedi_lasku_loppu($tootedi, $lasrow);

              //Nämä menee verkkolaskuputkeen
              $verkkolaskuputkeen_elmaedi[$lasrow["laskunro"]] = $lasrow["nimi"];

              $edilask++;
            }
            elseif ($lasrow["chn"] == "112") {
              finvoice_lasku_loppu($tootsisainenfinvoice, $lasrow, $pankkitiedot, $masrow);

              //Nämä menee verkkolaskuputkeen
              $verkkolaskuputkeen_suora[$lasrow["laskunro"]] = $lasrow["nimi"];
            }
            elseif (in_array($yhtiorow["verkkolasku_lah"], array("iPost", "finvoice", "maventa", "trustpoint", "ppg", "apix", "sepa", "talenom", "arvato"))) {
              $liitteet  = hae_liitteet_verkkolaskuun($yhtiorow["verkkolasku_lah"], $laskutettavat);
              $liitteita = !empty($liitteet);

              finvoice_lasku_loppu($tootfinvoice, $lasrow, $pankkitiedot, $masrow, $liitteita);

              if ($yhtiorow["verkkolasku_lah"] == "apix") {
                //Nämä menee verkkolaskuputkeen
                $verkkolaskuputkeen_apix[$lasrow["laskunro"]] = $lasrow["nimi"];
              }
              else {
                //Nämä menee verkkolaskuputkeen
                $verkkolaskuputkeen_finvoice[$lasrow["laskunro"]] = $lasrow["nimi"];
              }
            }
            else {
              pupevoice_lasku_loppu($tootxml);

              //Nämä menee verkkolaskuputkeen
              $verkkolaskuputkeen_pupevoice[$lasrow["laskunro"]] = $lasrow["nimi"];
            }

            // Otetaan talteen jokainen laskunumero joka lähetetään jotta voidaan tulostaa paperilaskut
            $tulostettavat[] = $lasrow["tunnus"];
            $lask++;
          }
          elseif ($lasrow["sisainen"] != '') {
            if ($silent == "") $tulos_ulos .= "<br>\n".t("Tehtiin sisäinen lasku")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";

            // Sisäisiä laskuja ei normaaalisti tuloseta paitsi jos meillä on valittu_tulostin
            if ($valittu_tulostin != '') {
              $tulostettavat[] = $lasrow["tunnus"];
              $lask++;
            }
          }
          elseif ($masrow["kateinen"] != '') {

            // halutaan lähettää lasku suoraan asiakkaalle sähköpostilla.. mutta ei nollalaskua
            // ja nimenomaan etukäteen maksetuissa Magento-verkkokauppatilauksissa
            if ($lasrow["chn"] == "666" and $lasrow["summa"] != 0 and isset($verkkokauppa_email_kuitti) and $verkkokauppa_email_kuitti == 'JOO' and $lasrow["laatija"] == 'Magento') {
              $tulostettavat_email[] = $lasrow["tunnus"];
            }
            else {
              if ($silent == "") {
                $tulos_ulos .= "<br>\n".t("Käteislaskua ei lähetetty")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";
              }

              // Käteislaskuja ei lähetetä ulos mutta ne halutaan kuitenkin tulostaa itse
              $tulostettavat[] = $lasrow["tunnus"];
            }

            $lask++;
          }
          elseif ($lasrow["vienti"] != '' or $masrow["itsetulostus"] != '' or $lasrow["chn"] == "666" or $lasrow["chn"] == '667') {
            if ($silent == "" or $silent == "VIENTI") {
              if ($lasrow["chn"] == "666" and $lasrow["summa"] != 0) {
                $tulos_ulos .= "<br>\n".t("Tämä lasku lähetetään suoraan asiakkaan sähköpostiin")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";
              }
              elseif ($lasrow["chn"] == "667") {
                $tulos_ulos .= "<br>\n".t("Tehtiin sisäinen lasku")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";
              }
              else {
                $tulos_ulos .= "<br>\n".t("Tämä lasku tulostetaan omalle tulostimelle")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";
              }
            }

            // halutaan lähettää lasku suoraan asiakkaalle sähköpostilla.. mutta ei nollalaskua
            if ($lasrow["chn"] == "666" and $lasrow["summa"] != 0) {
              $tulostettavat_email[] = $lasrow["tunnus"];
            }

            // halutaan lähettää lasku ulkoiseen varastoon
            if ($lasrow["verkkotunnus"] == "VELOX" and $velox_laskutus == "KYLLA") {
              $tulostettavat_ulkvar[] = $lasrow["laskunro"];
            }

            // Halutaan tulostaa itse
            $tulostettavat[] = $lasrow["tunnus"];
            $lask++;
          }
          elseif ($silent == "") {
            $tulos_ulos .= "\n".t("Nollasummaista laskua ei lähetetty")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";
          }

          // päivitetään kaikki laskut lähetetyiksi...
          $tquery = "UPDATE lasku SET alatila='X' WHERE (tunnus in ($tunnukset) or tunnus='$lasrow[tunnus]') and yhtio='$kukarow[yhtio]'";
          $tresult = pupe_query($tquery);

        } // end foreach ketjut...

        if ($silent == "") {
          $tulos_ulos .= "<br><br>\n\n";
        }

        //Aineistojen lopputägit
        elmaedi_aineisto_loppu($tootedi, $timestamppi);
        pupevoice_aineisto_loppu($tootxml);
      }
    }
    else {
      $tulos_ulos .= "<br>\n".t("Yhtään laskutettavaa tilausta ei löytynyt")."!<br><br>\n";
    }

    // suljetaan faili
    fclose($tootxml);
    fclose($tootedi);
    fclose($tootfinvoice);
    fclose($tootsisainenfinvoice);

    //dellataan failit jos ne on tyhjiä
    if (filesize($nimixml) == 0) {
      unlink($nimixml);
    }
    elseif (PUPE_UNICODE) {
      // Muutetaan ISO-8859-15:ksi jos lasku on jossain toisessa merkistössä
      exec("recode --force UTF8..ISO-8859-15 '$nimixml'");
    }

    if (filesize($nimifinvoice) == 0) {
      unlink($nimifinvoice);
    }
    elseif (PUPE_UNICODE) {
      // Muutetaan ISO-8859-15:ksi jos lasku on jossain toisessa merkistössä
      exec("recode --force UTF8..ISO-8859-15 '$nimifinvoice'");
    }

    if (filesize($nimiedi) == 0) {
      unlink($nimiedi);
    }
    elseif (PUPE_UNICODE) {
      // Muutetaan ISO-8859-15:ksi jos lasku on jossain toisessa merkistössä
      exec("recode --force UTF8..ISO-8859-15 '$nimiedi'");
    }

    if (filesize($nimisisainenfinvoice) == 0) {
      unlink($nimisisainenfinvoice);
    }
    elseif (PUPE_UNICODE) {
      // Muutetaan ISO-8859-15:ksi jos lasku on jossain toisessa merkistössä
      exec("recode --force UTF8..ISO-8859-15 '$nimisisainenfinvoice'");
    }

    // poistetaan lukot
    $query = "UNLOCK TABLES";
    $locre = pupe_query($query);

    // jos laskutettiin jotain
    if (isset($lask) and $lask > 0) {

      if ($silent == "" or $silent == "VIENTI") {
        $tulos_ulos .= t("Luotiin")." $lask ".t("laskua").".<br>\n";
      }

      // jos verkkotunnus löytyy niin
      if ($yhtiorow['verkkotunnus_lah'] != '' and file_exists(realpath($nimixml))) {

        if ($silent == "") {
          $tulos_ulos .= "<br><br>\n".t("FTP-siirto pupevoice:")."<br>\n";
        }

        //siirretaan laskutiedosto operaattorille
        $ftphost = (isset($verkkohost_lah) and trim($verkkohost_lah) != '') ? $verkkohost_lah : "ftp.verkkolasku.net";
        $ftpuser = $yhtiorow['verkkotunnus_lah'];
        $ftppass = $yhtiorow['verkkosala_lah'];
        $ftppath = (isset($verkkopath_lah) and trim($verkkopath_lah) != '') ? $verkkopath_lah : "out/einvoice/data/";
        $ftpfile = realpath($nimixml);
        $ftpfail = "{$pupe_root_polku}/dataout/pupevoice_error/";

        $verkkolasmail  = t("Pvm").": ".date("Y-m-d H:i:s")."\n\n";
        $verkkolasmail .= t("Aineiston laskut").":\n";

        foreach ($verkkolaskuputkeen_pupevoice as $lasnoputk => $nimiputk) {
          $verkkolasmail .= "$lasnoputk - $nimiputk\n";
        }

        $verkkolasmail .= "\n\n";
        $verkkolasmail .= t("Aineisto liitteenä")."!\n\n\n\n";

        $_params = array(
          "to" => $yhtiorow["talhal_email"],
          "subject" => t("Pupevoice-aineiston siirto Itellaan"),
          "ctype" => "text",
          "body" => $verkkolasmail,
          "attachements" => array(
            array(
              "filename" => $ftpfile,
            ),
          ),
        );

        pupesoft_sahkoposti($_params);

        require "inc/ftp-send.inc";

        if ($silent == "") {
          $tulos_ulos .= $tulos_ulos_ftp;
        }
      }
      elseif ($yhtiorow["verkkolasku_lah"] == "apix" and file_exists(realpath($nimifinvoice))) {

        // Splitataan file ja lähetetään laskut sopivissa osissa
        $apix_laskuarray = explode("<SOAP-ENV:Envelope", file_get_contents($nimifinvoice));
        $apix_laskumaara = count($apix_laskuarray);

        if ($apix_laskumaara > 0) {
          require_once "tilauskasittely/tulosta_lasku.inc";

          for ($a = 1; $a < $apix_laskumaara; $a++) {
            preg_match("/\<InvoiceNumber\>(.*?)\<\/InvoiceNumber\>/i", $apix_laskuarray[$a], $invoice_number);

            $apix_finvoice = "<SOAP-ENV:Envelope".$apix_laskuarray[$a];

            // Laitetaan lasku lähetysjonoon
            $tulos_ulos .= apix_queue($apix_finvoice, $invoice_number[1], $kieli, $liitteet);
          }
        }
      }
      elseif ($yhtiorow["verkkolasku_lah"] == "maventa" and file_exists(realpath($nimifinvoice))) {
        // Splitataan file ja lähetetään YKSI lasku kerrallaan
        $maventa_laskuarray = explode("<SOAP-ENV:Envelope", file_get_contents($nimifinvoice));
        $maventa_laskumaara = count($maventa_laskuarray);

        if ($maventa_laskumaara > 0) {
          require_once "tilauskasittely/tulosta_lasku.inc";

          for ($a = 1; $a < $maventa_laskumaara; $a++) {
            preg_match("/\<InvoiceNumber\>(.*?)\<\/InvoiceNumber\>/i", $maventa_laskuarray[$a], $invoice_number);

            $status = maventa_invoice_put_file(NULL, NULL, $invoice_number[1], "<SOAP-ENV:Envelope".$maventa_laskuarray[$a], $kieli);

            $tulos_ulos .= "Maventa-lasku $invoice_number[1]: $status<br>\n";
          }
        }
      }
      elseif ($yhtiorow["verkkolasku_lah"] == "trustpoint" and file_exists(realpath($nimifinvoice))) {
        // Siirretään lähetysjonoon
        rename($nimifinvoice, "{$pupe_root_polku}/dataout/trustpoint_error/".basename($nimifinvoice));
        $tulos_ulos .= t("Lasku siirretty lähetysjonoon");
      }
      elseif ($yhtiorow["verkkolasku_lah"] == "ppg" and file_exists(realpath($nimifinvoice))) {
        // Splitataan file ja lähetetään YKSI lasku kerrallaan
        $ppg_laskuarray = explode("<SOAP-ENV:Envelope", file_get_contents($nimifinvoice));
        $ppg_laskumaara = count($ppg_laskuarray);

        if ($ppg_laskumaara > 0) {
          require_once "tilauskasittely/tulosta_lasku.inc";

          for ($a = 1; $a < $ppg_laskumaara; $a++) {
            preg_match("/\<InvoiceNumber\>(.*?)\<\/InvoiceNumber\>/i", $ppg_laskuarray[$a], $invoice_number);

            $status = ppg_queue($invoice_number[1], "<SOAP-ENV:Envelope".$ppg_laskuarray[$a], $kieli);

            $tulos_ulos .= "PPG-lasku $invoice_number[1]: $status<br>\n";
          }
        }
      }
      elseif ($yhtiorow["verkkolasku_lah"] == "arvato" and file_exists(realpath($nimifinvoice))) {
        // Splitataan file ja lähetetään YKSI lasku kerrallaan
        $arvato_laskuarray = explode("<SOAP-ENV:Envelope", file_get_contents($nimifinvoice));
        $arvato_laskumaara = count($arvato_laskuarray);

        if ($arvato_laskumaara > 0) {
          require_once "tilauskasittely/tulosta_lasku.inc";

          for ($a = 1; $a < $arvato_laskumaara; $a++) {
            preg_match("/\<InvoiceNumber\>(.*?)\<\/InvoiceNumber\>/i", $arvato_laskuarray[$a], $invoice_number);

            $status = arvato_queue($invoice_number[1], "<SOAP-ENV:Envelope".$arvato_laskuarray[$a], $kieli);

            $tulos_ulos .= "Arvato-lasku $invoice_number[1]: $status<br>\n";
          }
        }
      }
      elseif ($yhtiorow["verkkolasku_lah"] == "talenom" and file_exists(realpath($nimifinvoice))) {
        // Splitataan file ja lähetetään YKSI lasku kerrallaan
        $talenom_laskuarray = explode("<SOAP-ENV:Envelope", file_get_contents($nimifinvoice));
        $talenom_laskumaara = count($talenom_laskuarray);

        if ($talenom_laskumaara > 0) {
          for ($a = 1; $a < $talenom_laskumaara; $a++) {
            preg_match("/\<InvoiceNumber\>(.*?)\<\/InvoiceNumber\>/i", $talenom_laskuarray[$a], $invoice_number);

            $status = talenom_queue($invoice_number[1], "<SOAP-ENV:Envelope".$talenom_laskuarray[$a], $kieli);

            $tulos_ulos .= "Talenom-lasku $invoice_number[1]: $status<br>\n";
          }
        }
      }
      elseif (finvoice_pankki() && $yhtiorow["verkkolasku_lah"] == "sepa" and file_exists(realpath($nimifinvoice))) {
        rename($nimifinvoice, "{$pupe_root_polku}/dataout/" . finvoice_pankki() . "_error/" . basename($nimifinvoice));

        $tulos_ulos .= "SEPA-lasku siirretty lähetysjonoon";
      }
      elseif ($yhtiorow["verkkolasku_lah"] == "trustpoint" and !file_exists(realpath($nimifinvoice))) {
        // Tämä näytetään vain kun laskutetaan käsin ja lasku ei mene automaattiseen verkkolaskuputkeen
        if (strpos($_SERVER['SCRIPT_NAME'], "valitse_laskutettavat_tilaukset.php") !== FALSE) {
          js_openFormInNewWindow();

          foreach ($tulostettavat as $lasku) {

            $query = "SELECT laskunro
                      FROM lasku
                      WHERE yhtio = '$kukarow[yhtio]'
                      and tunnus  = '$lasku'";
            $laresult = pupe_query($query);
            $laskurow = mysqli_fetch_assoc($laresult);

            echo "<form id='finvoice_$lasku' name='finvoice_$lasku' method='post' action='{$palvelin2}tilauskasittely/uudelleenluo_laskuaineisto.php' class='multisubmit'>
                <input type='hidden' name='laskunumerot' value='$laskurow[laskunro]'>
                <input type='hidden' name='tee' value='NAYTATILAUS'>
                <input type='hidden' name='nayta_ja_tallenna' value='TRUE'>
                <input type='hidden' name='kaunisnimi' value='Finvoice_$laskurow[laskunro].xml'>
                <input type='submit' value='".t("Tallenna Finvoice").": $laskurow[laskunro]' onClick=\"js_openFormInNewWindow('finvoice_$lasku', 'samewindow'); return false;\"></form>";
          }
        }
      }
      elseif ($yhtiorow["verkkolasku_lah"] == "iPost" and file_exists(realpath($nimifinvoice))) {
        if ($silent == "" or $silent == "VIENTI") {
          $tulos_ulos .= "<br><br>\n".t("FTP-siirto iPost Finvoice:")."<br>\n";
        }

        //siirretaan laskutiedosto operaattorille
        $ftphost     = "ftp.itella.net";
        $ftpuser     = $yhtiorow['verkkotunnus_lah'];
        $ftppass     = $yhtiorow['verkkosala_lah'];
        $ftppath     = "out/finvoice/data/";
        $ftpfile     = realpath($nimifinvoice);
        $renameftpfile   = str_replace("TRANSFER_IPOST", "DELIVERED_IPOST", basename($nimifinvoice));
        $ftpfail     = "{$pupe_root_polku}/dataout/ipost_error/";

        // Tehdään maili, että siirretään laskut operaattorille
        $bound = uniqid(time()."_") ;

        $verkkolasmail  = t("Pvm").": ".date("Y-m-d H:i:s")."\n\n";
        $verkkolasmail .= t("Aineiston laskut").":\n";

        foreach ($verkkolaskuputkeen_finvoice as $lasnoputk => $nimiputk) {
          $verkkolasmail .= "$lasnoputk - $nimiputk\n";
        }

        $verkkolasmail .= "\n\n";
        $verkkolasmail .= t("Aineisto liitteenä")."!\n\n\n\n";

        $_params = array(
          "to" => $yhtiorow["talhal_email"],
          "subject" => t("iPost Finvoice-aineiston siirto Itellaan"),
          "ctype" => "text",
          "body" => $verkkolasmail,
          "attachements" => array(
            array(
              "filename" => $ftpfile,
            ),
          ),
        );

        pupesoft_sahkoposti($_params);

        require "inc/ftp-send.inc";

        if ($silent == "" or $silent == "VIENTI") {
          $tulos_ulos .= $tulos_ulos_ftp;
        }
      }
      elseif ($yhtiorow["verkkolasku_lah"] == "finvoice" and file_exists(realpath($nimifinvoice))) {
        if (isset($verkkolaskut_out)) {
          if (is_writable($verkkolaskut_out)) {
            copy(realpath($nimifinvoice), $verkkolaskut_out."/".basename($nimifinvoice));
          }
          else {
            $tulos_ulos .= "<br><br>\n".t("Tiedoston kopiointi epäonnistui")."!<br>\n";
          }
        }
      }

      if ($edilask > 0 and $edi_ftphost != '' and file_exists(realpath($nimiedi))) {
        if ($silent == "") {
          $tulos_ulos .= "<br><br>\n".t("FTP-siirto Elma EDI-inhouse:")."<br>\n";
        }

        //siirretaan laskutiedosto operaattorille, EDI-inhouse muoto
        $ftphost = $edi_ftphost;
        $ftpuser = $edi_ftpuser;
        $ftppass = $edi_ftppass;
        $ftppath = $edi_ftppath;
        $ftpfile = realpath($nimiedi);
        $ftpfail = "{$pupe_root_polku}/dataout/elmaedi_error/";

        $verkkolasmail  = t("Pvm").": ".date("Y-m-d H:i:s")."\n\n";
        $verkkolasmail .= t("Aineiston laskut").":\n";

        foreach ($verkkolaskuputkeen_elmaedi as $lasnoputk => $nimiputk) {
          $verkkolasmail .= "$lasnoputk - $nimiputk\n";
        }

        $verkkolasmail .= "\n\n";
        $verkkolasmail .= t("Aineisto liitteenä")."!\n\n\n\n";

        $_params = array(
          "to" => $yhtiorow["talhal_email"],
          "subject" => t("EDI-inhouse-aineiston siirto Itellaan"),
          "ctype" => "text",
          "body" => $verkkolasmail,
          "attachements" => array(
            array(
              "filename" => $ftpfile,
            ),
          ),
        );

        pupesoft_sahkoposti($_params);

        require "inc/ftp-send.inc";

        if ($silent == "") {
          $tulos_ulos .= $tulos_ulos_ftp;
        }
      }

      if (isset($sisainenfoinvoice_ftphost) and $sisainenfoinvoice_ftphost != '' and file_exists(realpath($nimisisainenfinvoice))) {
        if ($silent == "") {
          $tulos_ulos .= "<br><br>\n".t("FTP-siirto Pupesoft-Finvoice:")."<br>\n";
        }

        //siirretaan laskutiedosto operaattorille, SisäinenFinvoice muoto
        $ftphost = $sisainenfoinvoice_ftphost;
        $ftpuser = $sisainenfoinvoice_ftpuser;
        $ftppass = $sisainenfoinvoice_ftppass;
        $ftppath = $sisainenfoinvoice_ftppath;
        $ftpfile = realpath($nimisisainenfinvoice);
        $ftpfail = "{$pupe_root_polku}/dataout/sisainenfinvoice_error/";

        $verkkolasmail  = t("Pvm").": ".date("Y-m-d H:i:s")."\n\n";
        $verkkolasmail .= t("Aineiston laskut").":\n";

        foreach ($verkkolaskuputkeen_suora as $lasnoputk => $nimiputk) {
          $verkkolasmail .= "$lasnoputk - $nimiputk\n";
        }

        $verkkolasmail .= "\n\n";
        $verkkolasmail .= t("Aineisto liitteenä")."!\n\n\n\n";

        $_params = array(
          "to" => $yhtiorow["talhal_email"],
          "subject" => t("Pupesoft-Finvoice-aineiston siirto eteenpäin"),
          "ctype" => "text",
          "body" => $verkkolasmail,
          "attachements" => "",
        );

        pupesoft_sahkoposti($_params);

        require "inc/ftp-send.inc";

        if ($silent == "") {
          $tulos_ulos .= $tulos_ulos_ftp;
        }
      }

      if ($yhtiorow['lasku_tulostin'] == -88 or (isset($valittu_tulostin) and $valittu_tulostin == "-88")) {
        // Tämä näytetään vain kun laskutetaan käsin.
        if (strpos($_SERVER['SCRIPT_NAME'], "valitse_laskutettavat_tilaukset.php") !== FALSE or strpos($_SERVER['SCRIPT_NAME'], "tilaus_myynti.php") !== FALSE) {
          js_openFormInNewWindow();

          foreach ($tulostettavat as $lasku) {

            $query = "SELECT laskunro
                      FROM lasku
                      WHERE yhtio = '$kukarow[yhtio]'
                      and tunnus  = '$lasku'";
            $laresult = pupe_query($query);
            $laskurow = mysqli_fetch_assoc($laresult);

            echo "<br><form id='tulostakopioform_$lasku' name='tulostakopioform_$lasku' method='post' action='{$palvelin2}tilauskasittely/tulostakopio.php' autocomplete='off'>
                <input type='hidden' name='otunnus' value='$lasku'>
                <input type='hidden' name='toim' value='LASKU'>
                <input type='hidden' name='tee' value='NAYTATILAUS'>
                <input type='submit' value='".t("Näytä lasku").": $laskurow[laskunro]' onClick=\"js_openFormInNewWindow('tulostakopioform_$lasku', ''); return false;\"></form><br>";
          }
        }
      }
      elseif (($yhtiorow['lasku_tulostin'] > 0 or $yhtiorow['lasku_tulostin'] == -99) or (isset($valittu_tulostin) and $valittu_tulostin != "")) {
        // jos yhtiöllä on laskuprintteri on määritelty tai halutaan jostain muusta syystä tulostella laskuja paperille/sähköpostiin
        require_once "tilauskasittely/tulosta_lasku.inc";

        if ((!isset($valittu_tulostin) or $valittu_tulostin == "") and ($yhtiorow['lasku_tulostin'] > 0 or $yhtiorow['lasku_tulostin'] == -99)) {
          $valittu_tulostin = $yhtiorow['lasku_tulostin'];
        }

        if ($silent == "") $tulos_ulos .= "<br>\n".t("Tulostetaan paperilaskuja").":<br>\n";

        foreach ($tulostettavat as $lasku) {

          $vientierittelymail    = "";
          $vientierittelykomento = "";

          tulosta_lasku($lasku, $kieli, "VERKKOLASKU", "", $valittu_tulostin, $valittu_kopio_tulostin, $saatekirje);

          $query = "SELECT *
                    FROM lasku
                    WHERE yhtio = '$kukarow[yhtio]'
                    and tunnus  = '$lasku'";
          $laresult = pupe_query($query);
          $laskurow = mysqli_fetch_assoc($laresult);

          if ($silent == "") $tulos_ulos .= t("Tulostetaan lasku").": $laskurow[laskunro]<br>\n";

          if (($laskurow["vienti"] == "E" or $laskurow["vienti"] == "K") and $yhtiorow["vienti_erittelyn_tulostus"] != "E") {
            $uusiotunnus = $laskurow["tunnus"];

            require 'tulosta_vientierittely.inc';

            // keksitään uudelle failille joku varmasti uniikki nimi:
            list($usec, $sec) = explode(' ', microtime());
            mt_srand((float) $sec + ((float) $usec * 100000));
            $pdffilenimi = "/tmp/Vientierittely-".md5(uniqid(mt_rand(), true)).".pdf";

            //kirjoitetaan pdf faili levylle..
            $fh = fopen($pdffilenimi, "w");
            if (fwrite($fh, $Xpdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
            fclose($fh);

            if ($vientierittelykomento == "email" or $vientierittelymail != "") {
              // lähetetään meili
              if ($vientierittelymail != "") {
                $komento = $vientierittelymail;
              }
              else {
                $komento = "";
              }

              $kutsu = t("Lasku", $kieli)." $laskurow[laskunro] ".t("Vientierittely", $kieli);

              if ($yhtiorow["liitetiedostojen_nimeaminen"] == "N") {
                $kutsu .= ", ".trim($laskurow["nimi"]);
              }

              $liite              = $pdffilenimi;
              $sahkoposti_cc      = "";
              $content_subject    = "";
              $content_body       = "";
              include "inc/sahkoposti.inc"; // sanotaan include eikä require niin ei kuolla
            }
            elseif ($vientierittelykomento != '' and $vientierittelykomento != 'edi') {
              // itse print komento...
              $line = exec("$vientierittelykomento $pdffilenimi");
            }

            if ($silent == "") $tulos_ulos .= t("Vientierittely tulostuu")."...<br>\n";

            unset($Xpdf);
          }
        }
      }

      // lähetetään saähköpostilaskut
      if ($yhtiorow['lasku_tulostin'] != -99 and count($tulostettavat_email) > 0) {

        require_once "tilauskasittely/tulosta_lasku.inc";

        if ($silent == "" or $silent == "VIENTI") $tulos_ulos .= "<br>\n".t("Tulostetaan sähköpostilaskuja").":<br>\n";

        foreach ($tulostettavat_email as $lasku) {

          $vientierittelymail    = "";
          $vientierittelykomento = "";

          tulosta_lasku($lasku, $kieli, "VERKKOLASKU", "", -99, "", $saatekirje);

          $query = "SELECT *
                    FROM lasku
                    WHERE yhtio = '$kukarow[yhtio]'
                    and tunnus  = '$lasku'";
          $laresult = pupe_query($query);
          $laskurow = mysqli_fetch_assoc($laresult);

          if ($silent == "" or $silent == "VIENTI") $tulos_ulos .= t("Lähetetään lasku").": $laskurow[laskunro]<br>\n";

          if (($laskurow["vienti"] == "E" or $laskurow["vienti"] == "K") and $yhtiorow["vienti_erittelyn_tulostus"] != "E") {
            $uusiotunnus = $laskurow["tunnus"];

            require 'tulosta_vientierittely.inc';

            //keksitään uudelle failille joku varmasti uniikki nimi:
            list($usec, $sec) = explode(' ', microtime());
            mt_srand((float) $sec + ((float) $usec * 100000));
            $pdffilenimi = "/tmp/Vientierittely-".md5(uniqid(mt_rand(), true)).".pdf";

            //kirjoitetaan pdf faili levylle..
            $fh = fopen($pdffilenimi, "w");
            if (fwrite($fh, $Xpdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
            fclose($fh);

            if ($vientierittelykomento == "email" or $vientierittelymail != "") {
              // lähetetään meili
              if ($vientierittelymail != "") {
                $komento = $vientierittelymail;
              }
              else {
                $komento = "";
              }

              $kutsu = t("Lasku", $kieli)." $laskurow[laskunro] ".t("Vientierittely", $kieli);

              if ($yhtiorow["liitetiedostojen_nimeaminen"] == "N") {
                $kutsu .= ", ".trim($laskurow["nimi"]);
              }

              $liite              = $pdffilenimi;
              $sahkoposti_cc      = "";
              $content_subject    = "";
              $content_body       = "";
              include "inc/sahkoposti.inc"; // sanotaan include eikä require niin ei kuolla
            }

            if ($silent == "" or $silent == "VIENTI") $tulos_ulos .= t("Vientierittely lähetetään")."...<br>\n";

            unset($Xpdf);
          }
        }
      }

      // lähetetään saähköpostilaskut
      if (count($tulostettavat_ulkvar) > 0) {
        require_once "tilauskasittely/tulosta_lasku.inc";
        require_once "rajapinnat/logmaster/logmaster-functions.php";

        if ($silent == "" or $silent == "VIENTI") $tulos_ulos .= "<br>\n".t("Siirretään laskuja ulkoiseen varastoon").":<br>\n";

        foreach ($tulostettavat_ulkvar as $lasku) {
          $lasku_ulkvar_file = tulosta_lasku("LASKU:".$lasku, $kieli, "VERKKOLASKU_APIX", "", "", "", "");
          // nimetään lasku nätisti
          $nattinimi = "/tmp/Invoice_{$kukarow['yhtio']}_{$lasku}.pdf";
          rename($lasku_ulkvar_file, $nattinimi);

          $palautus = logmaster_send_file($nattinimi, TRUE);

          if ($palautus == "") {
            pupesoft_log('logmaster_outbound_delivery', "Siirretiin lasku {$nattinimi} onnistuneesti.");
          }
          else {
            pupesoft_log('logmaster_outbound_delivery', "Laskun {$nattinimi} siirto epäonnistui.");
          }
        }
      }
    }
    elseif ($silent == "") {
      $tulos_ulos .= t("Yhtään laskua ei siirretty/tulostettu!")."<br>\n";
    }

    // lähetetään meili vaan jos on jotain laskutettavaa ja ollaan tultu komentoriviltä
    if (isset($lask) and $lask > 0 and $php_cli) {

      $content  = "<html><body>\n";
      $content .= $tulos_ulos;
      $content .= "</body></html>\n";

      $_params = array(
        "to"      => $yhtiorow["talhal_email"],
        "subject" => "{$yhtiorow["nimi"]} - Laskutusajo",
        "ctype"   => "html",
        "body"    => $content,
      );

      pupesoft_sahkoposti($_params);
    }
  }

  if (!$php_cli) {
    echo "$tulos_ulos";

    // Annetaan mahdollisuus tallentaa finvoicetiedosto jos se on luotu..
    if (isset($nimifinvoice) and file_exists($nimifinvoice) and (strpos($_SERVER['SCRIPT_NAME'], "verkkolasku.php") !== FALSE or strpos($_SERVER['SCRIPT_NAME'], "valitse_laskutettavat_tilaukset.php") !== FALSE) and $yhtiorow["verkkolasku_lah"] == "finvoice") {
      echo "<br><table><tr><th>".t("Tallenna finvoice-aineisto").":</th>";
      echo "<form method='post' class='multisubmit'>";
      echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
      echo "<input type='hidden' name='kaunisnimi' value='".basename($nimifinvoice)."'>";
      echo "<input type='hidden' name='filenimi' value='".basename($nimifinvoice)."'>";
      echo "<td><input type='submit' value='".t("Tallenna")."'></td></tr></form></table>";
    }
  }

  if ($tee == '' and strpos($_SERVER['SCRIPT_NAME'], "verkkolasku.php") !== FALSE) {

    //päivämäärän tarkistus
    $tilalk = explode("-", $yhtiorow["myyntireskontrakausi_alku"]);
    $tillop = explode("-", $yhtiorow["myyntireskontrakausi_loppu"]);

    $tilalkpp = $tilalk[2];
    $tilalkkk = $tilalk[1]-1;
    $tilalkvv = $tilalk[0];

    $tilloppp = $tillop[2];
    $tillopkk = $tillop[1]-1;
    $tillopvv = $tillop[0];

    $tanaanpp = date("d");
    $tanaankk = date("m")-1;
    $tanaanvv = date("Y");

    echo "  <SCRIPT LANGUAGE=JAVASCRIPT>

          function verify(){
            var pp = document.lasku.laskpp;
            var kk = document.lasku.laskkk;
            var vv = document.lasku.laskvv;

            pp = Number(pp.value);
            kk = Number(kk.value)-1;
            vv = Number(vv.value);

            if (vv == 0 && pp == 0 && kk == -1) {
              var tanaanpp = $tanaanpp;
              var tanaankk = $tanaankk;
              var tanaanvv = $tanaanvv;

              var dateSyotetty = new Date(tanaanvv, tanaankk, tanaanpp);
            }
            else {
              if (vv > 0 && vv < 1000) {
                vv = vv+2000;
              }

              var dateSyotetty = new Date(vv,kk,pp);
            }

            var dateTallaHet = new Date();
            var ero = (dateTallaHet.getTime() - dateSyotetty.getTime()) / 86400000;

            var tilalkpp = $tilalkpp;
            var tilalkkk = $tilalkkk;
            var tilalkvv = $tilalkvv;
            var dateTiliAlku = new Date(tilalkvv,tilalkkk,tilalkpp);
            dateTiliAlku = dateTiliAlku.getTime();


            var tilloppp = $tilloppp;
            var tillopkk = $tillopkk;
            var tillopvv = $tillopvv;
            var dateTiliLoppu = new Date(tillopvv,tillopkk,tilloppp);
            dateTiliLoppu = dateTiliLoppu.getTime();

            dateSyotetty = dateSyotetty.getTime();

            if (dateSyotetty < dateTiliAlku || dateSyotetty > dateTiliLoppu) {
              var msg = '".t("VIRHE: Syötetty päivämäärä ei sisälly kuluvaan tilikauteen!")."';
              alert(msg);

              skippaa_tama_submitti = true;
              return false;
            }
            if (ero >= 2) {
              var msg = '".t("Oletko varma, että haluat päivätä laskun yli 2pv menneisyyteen?")."';

              if (confirm(msg)) {
                return true;
              }
              else {
                skippaa_tama_submitti = true;
                return false;
              }
            }
            if (ero < 0 && '{$yhtiorow['laskutus_tulevaisuuteen']}' != 'S') {
              var msg = '".t("VIRHE: Laskua ei voi päivätä tulevaisuuteen!")."';
              alert(msg);

              skippaa_tama_submitti = true;
              return false;
            }
          }
        </SCRIPT>";


    echo "<br>\n<table>";

    // Mikä viikonpäivä tänään on 1-7.. 1=sunnuntai, 2=maanantai, jne...
    $today = date("w") + 1;

    // Kuukauden eka päivä
    $eka_pv = laskutuspaiva("eka");

    // Kuukauden keskimmäinen päivä
    $keski_pv = laskutuspaiva("keski");

    // Kuukauden viimeinen päivä
    $vika_pv = laskutuspaiva("vika");

    $query = "SELECT
              sum(if (lasku.laskutusvkopv = '0', 1, 0)) normaali,
              sum(if (((lasku.laskutusvkopv = $today) or
                      (lasku.laskutusvkopv = -1 and curdate() = '$vika_pv') or
                      (lasku.laskutusvkopv = -2 and curdate() = '$eka_pv') or
                      (lasku.laskutusvkopv = -3 and curdate() = '$keski_pv') or
                      (lasku.laskutusvkopv = -4 and curdate() in ('$keski_pv','$vika_pv')) or
                      (lasku.laskutusvkopv = -5 and curdate() in ('$eka_pv','$keski_pv'))), 1, 0)) paiva,
              sum(if (maksuehto.factoring_id is not null, 1, 0)) factoroitavat,
              count(lasku.tunnus) kaikki
              from lasku
              LEFT JOIN maksuehto ON lasku.yhtio=maksuehto.yhtio and lasku.maksuehto=maksuehto.tunnus
              where lasku.yhtio  = '$kukarow[yhtio]'
              and lasku.tila     = 'L'
              and lasku.alatila  = 'D'
              and lasku.viite    = ''
              and lasku.chn     != '999'";
    $res = pupe_query($query);
    $row = mysqli_fetch_assoc($res);

    echo "<form method = 'post' name='lasku' onSubmit = 'return verify()'>
      <input type='hidden' name='tee' value='TARKISTA'>";

    echo "<tr><th>".t("Laskutettavia tilauksia joilla on laskutusviikonpäivä tänään").":</th><td colspan='3'>$row[paiva]</td></tr>\n";
    echo "<tr><th>".t("Laskutettavia tilauksia joiden laskutusviikonpäivä ei ole tänään").":</th><td colspan='3'>".($row["kaikki"]-$row["normaali"]-$row["paiva"])."</td></tr>\n";
    echo "<tr><th>".t("Laskutettavia tilauksia joilla EI ole laskutusviikonpäivää").":</th><td colspan='3'>$row[normaali]</td></tr>\n";
    echo "<tr><th>".t("Laskutettavia tilauksia jotka siirretään rahoitukseen").":</th><td colspan='3'>$row[factoroitavat]</td></tr>\n";
    echo "<tr><th>".t("Laskutettavia tilauksia kaikkiaan").":</th><td colspan='3'>$row[kaikki]</td></tr>\n";

    echo "<tr><th>".t("Syötä poikkeava laskutuspäivämäärä (pp-kk-vvvv)")."</th>
        <td><input type='text' name='laskpp' value='' size='3'></td>
        <td><input type='text' name='laskkk' value='' size='3'></td>
        <td><input type='text' name='laskvv' value='' size='5'></td></tr>\n";

    if ($yhtiorow["myyntilaskun_erapvmlaskenta"] == "K") {
      echo "<tr><th>".t("Laske eräpäivä").":</th>
          <td colspan='3'><select name='erpcmlaskenta'>";
      echo "<option value=''>".t("Eräpäivä lasketaan laskutuspäivästä")."</option>";
      echo "<option value='NOW'>".t("Eräpäivä lasketaan tästä hetkestä")."</option>";
      echo "</select></td></tr>\n";
    }

    echo "<tr><th>".t("Ohita laskujen laskutusviikonpäivät").":</th><td colspan='3'><input type='checkbox' name='laskutakaikki'></td></tr>\n";

    echo "<tr><th>".t("Laskuta vain tilaukset, lista pilkulla eroteltuna").":</th><td colspan='3'><textarea name='laskutettavat' rows='10' cols='60'></textarea></td></tr>";

    echo "</table>";

    echo "<br>\n<input type='submit' value='".t("Jatka")."'>";
    echo "</form>";
  }
}

if (!$php_cli and strpos($_SERVER['SCRIPT_NAME'], "verkkolasku.php") !== FALSE) {
  require "inc/footer.inc";
}
