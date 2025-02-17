<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

// MUOKKAUS: isset():
foreach (array("rajaus", "nimet", "rajattunakyma", "asale", "ashin", "aletaulu", "yhdistetty") as $v) {
	if (!isset(${$v})) ${$v} = NULL;
}

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

if (@include "../inc/parametrit.inc");
elseif (@include "parametrit.inc");
else exit;

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}

// MUOKKAUS: isset():
foreach (array("tee", "lopetus", "asiakasid", "ytunnus") as $var) {
  if (!isset(${$var})) ${$var} = null;
}

$alennuslaji = "";

if ($yhtiorow['myynnin_alekentat'] > 1) {
  $alennuslaji = "alennuslaji";
}

//  Haardkoodataan exranetrajaus vain alennukseen
if ($kukarow["extranet"] != "") {

  $rajaus = "ALENNUKSET";

  //Haetaan asiakkaan tunnuksella
  $query  = "SELECT *
             FROM asiakas
             WHERE yhtio='$kukarow[yhtio]' and tunnus='$kukarow[oletus_asiakas]'";
  $result = pupe_query($query);

  if (mysqli_num_rows($result) == 1) {
    $asiakasrow = mysqli_fetch_assoc($result);
    $ytunnus = $asiakasrow["ytunnus"];
    $asiakasid = $asiakasrow["tunnus"];
  }
  else {
    echo "<font class='error'>".t("VIRHE: Käyttäjätiedoissasi on virhe! Ota yhteys järjestelmän ylläpitäjään")."!</font><br><br>";
    exit;
  }
}

if ($tee == 'eposti') {

  if ($kukarow["extranet"] == "") {
    if ($komento == '') {
      $tulostimet[] = "Alennustaulukko";
      $toimas = $ytunnus;
      require "inc/valitse_tulostin.inc";
    }

    $ytunnus = $toimas;
  }
  else {
    $komento["Alennustaulukko"] = "email";
  }

  require 'pdflib/phppdflib.class.php';

  function alku() {
    global $yhtiorow, $firstpage, $pdf, $rectparam, $norm, $norm_bold, $pieni, $ytunnus, $asiakasid, $kukarow, $kala, $tid, $otsikkotid;

    static $sivu;
    $sivu++;

    if (!isset($pdf)) {

      //PDF parametrit
      $pdf = new pdffile;
      $pdf->enable('template');

      $pdf->set_default('margin-top',   0);
      $pdf->set_default('margin-bottom',   0);
      $pdf->set_default('margin-left',   0);
      $pdf->set_default('margin-right',   0);
      $rectparam["width"] = 0.3;

      $norm["height"] = 12;
      $norm["font"] = "Courier";

      $norm_bold["height"] = 12;
      $norm_bold["font"] = "Courier-Bold";

      $pieni["height"] = 8;
      $pieni["font"] = "Courier";

      $query =  "SELECT *
                 FROM asiakas
                 WHERE yhtio = '$kukarow[yhtio]'
                 and tunnus  = '$asiakasid'";
      $assresult = pupe_query($query);
      $assrow = mysqli_fetch_assoc($assresult);

      // Tehdään firstpage
      $firstpage = $pdf->new_page("a4");

      //  Tehdään headertemplate
      $tid=$pdf->template->create();
      $pdf->template->rectangle($tid, 20, 20,  0, 580, $rectparam);
      $pdf->template->text($tid, 30,  5, $yhtiorow["nimi"], $pieni);
      $pdf->template->text($tid, 170, 5, "$assrow[nimi] $assrow[nimitark] ($ytunnus) ".t("alennustaulukko"));
      $pdf->template->place($tid, $firstpage, 0, 800);

      //  Tehdään otsikkoheader
      $otsikkotid=$pdf->template->create();
      $pdf->template->text($otsikkotid, 30,  20, t("Osasto"), $norm_bold);
      $pdf->template->text($otsikkotid, 30,   0, t("Tuoteryhmä")."/".t("Tuotenumero"), $norm_bold);
      $pdf->template->text($otsikkotid, 330,  0, t("Aleryhmä"), $norm_bold);
      $pdf->template->text($otsikkotid, 450,  0, t("Alennus"), $norm_bold);
      if ($yhtiorow['myynnin_alekentat'] > 1) $pdf->template->text($otsikkotid, 520,  0, t("Alelaji"), $norm_bold);
      $pdf->template->place($otsikkotid, $firstpage, 0, 665, $norm_bold);
      $kala = 650;

      //  Asiakastiedot
      //$pdf->draw_rectangle(737, 20,  674, 300, $firstpage, $rectparam);
      $pdf->draw_text(50, 759, t("Osoite", $kieli),   $firstpage, $pieni);
      $pdf->draw_text(50, 747, $assrow["nimi"],     $firstpage, $norm);
      $pdf->draw_text(50, 737, $assrow["nimitark"],  $firstpage, $norm);
      $pdf->draw_text(50, 727, $assrow["osoite"],   $firstpage, $norm);
      $pdf->draw_text(50, 717, $assrow["postino"]." ".$assrow["postitp"], $firstpage, $norm);
      $pdf->draw_text(50, 707, $assrow["maa"],     $firstpage, $norm);
    }
    else {

      //  Liitetään vaan valmiit templatet uudelle sivulle
      $firstpage = $pdf->new_page("a4");
      $pdf->template->place($tid, $firstpage, 0, 800);
      $pdf->template->place($otsikkotid, $firstpage, 0, 760);
    }

    $pdf->draw_text(520, 805, t("Sivu").": $sivu", $firstpage, $norm);

  }

  function rivi($firstpage, $osasto, $try, $tuote, $ryhma, $ale, $alelaji) {
    global $pdf, $kala, $rectparam, $norm, $norm_bold, $pieni;

    static $edosasto;

    if ($kala < 80) {
      $firstpage = alku();
      $kala = 760;
    }

    //  Vaihdetaan osastoa
    if ($osasto != $edosasto) {
      $kala -= 15;
      $pdf->draw_text(30,  $kala, $osasto,               $firstpage, $norm_bold);
      $kala -= 25;
    }

    $edosasto = $osasto;

    if ($tuote == " - ") {
      $pdf->draw_text(30,  $kala, $try,                 $firstpage, $norm);
    }
    else {
      $pdf->draw_text(60, $kala, $tuote,                 $firstpage, $norm);
    }
    $pdf->draw_text(310, $kala, sprintf('%10s', $ryhma),   $firstpage, $norm);
    $pdf->draw_text(420, $kala, sprintf('%10s', sprintf('%.2d', $ale))."%",   $firstpage, $norm);
    if ($yhtiorow['myynnin_alekentat'] > 1 and trim($alelaji) != '') $pdf->draw_text(500, $kala, sprintf('%10s', t("Ale").$alelaji),   $firstpage, $norm);

    $kala -= 15;
  }

  // tehdään eka sivu
  alku();
}

echo "<font class='head'>".t("Asiakkaan perustiedot")."</font><hr><br><br>";

if ($kukarow["extranet"] == "" and $lopetus == "") {
  //  Jos tullaan muualta ei anneta valita uutta asiakasta
  echo "<form name=asiakas action='asiakasinfo.php' method='post' autocomplete='off'>";
  echo "<input type = 'hidden' name = 'lopetus' value = '$lopetus'>";
  echo "<table><tr>";
  echo "<th>".t("Anna asiakasnumero tai osa nimestä")."</th>";
  echo "<td><input type='text' name='ytunnus'></td>";
  echo "<td class='back'><input type='submit' class='hae_btn' value='".t("Hae")."'>";
  echo "</tr></table>";
  echo "</form><br><br>";
}

if ($kukarow["extranet"] == "" and $ytunnus != '') {
  require "inc/asiakashaku.inc";
}

// jos meillä on onnistuneesti valittu asiakas
if ($asiakasid > 0) {

  // KAUTTALASKUTUSKIKKARE
  if (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '' and ($GLOBALS['koti_yhtio'] != $kukarow['yhtio'] or $asiakasrow['osasto'] != '6')) {
    $GLOBALS['eta_yhtio'] = "";
  }

  $katteet_naytetaan = ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or $kukarow["naytetaan_katteet_tilauksella"] == "") ? true : false;

  if (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '' and $GLOBALS['koti_yhtio'] == $kukarow['yhtio']) {
    $asiakas_yhtio = $GLOBALS['eta_yhtio'];

    // Toisen firman asiakastiedot
    $query = "SELECT *
              FROM asiakas
              WHERE yhtio         = '{$asiakas_yhtio}'
              AND laji           != 'P'
              AND ytunnus         = '{$asiakasrow['ytunnus']}'
              AND toim_ovttunnus  = '{$asiakasrow['toim_ovttunnus']}'";
    $asiakas_tunnus_res = pupe_query($query);
    $asiakasrow = mysqli_fetch_assoc($asiakas_tunnus_res);
  }
  else {
    $asiakas_yhtio = $kukarow['yhtio'];
  }

  // haetaan asiakkaan segmentit
  $query = "SELECT group_concat(parent.tunnus) tunnukset
            FROM puun_alkio
            JOIN dynaaminen_puu AS node ON (node.yhtio = puun_alkio.yhtio AND node.tunnus = puun_alkio.puun_tunnus AND node.laji = puun_alkio.laji)
            JOIN dynaaminen_puu AS parent ON (parent.yhtio = node.yhtio AND parent.laji = node.laji AND parent.lft <= node.lft AND parent.rgt >= node.lft AND parent.lft > 1)
            WHERE puun_alkio.yhtio = '$kukarow[yhtio]'
            AND puun_alkio.laji    = 'asiakas'
            AND puun_alkio.liitos  = '$asiakasrow[tunnus]'";
  $almight = pupe_query($query);

  $alehi_assegmenttirow = mysqli_fetch_assoc($almight);

  if ($alehi_assegmenttirow["tunnukset"] == "") {
    $alehi_assegmenttirow["tunnukset"] = 0;
  }

  echo "<table><tr>";
  echo "<th class='ptop'>".t("Ytunnus")."</th>";
  echo "<th class='ptop'>".t("Asiakasnumero")."</th>";
  echo "<th class='ptop'>".t("Nimi")."<br>".t("Osoite")."</th>";
  echo "<th class='ptop'>".t("Toimitusasiakas")."<br>".t("Toimitusosoite")."</th>";
  echo "</tr><tr>";
  echo "<td class='ptop'>$asiakasrow[ytunnus]</td>";
  echo "<td class='ptop'>$asiakasrow[asiakasnro]</td>";
  echo "<td class='ptop'>$asiakasrow[nimi]<br>$asiakasrow[osoite]<br>$asiakasrow[postino] $asiakasrow[postitp]</td>";
  echo "<td class='ptop'>$asiakasrow[toim_nimi]<br>$asiakasrow[toim_osoite]<br>$asiakasrow[toim_postino] $asiakasrow[toim_postitp]</td>";
  echo "</tr></table><br><br>";

  $maxcol  = 12; // montako columnia näyttö on

  if ($lopetus == "" and $kukarow["extranet"] == "") {
    if ($rajaus=="MYYNTI") $sel["M"] = "checked";
    elseif ($rajaus=="ALENNUKSET") $sel["A"] = "checked";
    else $sel["K"] = "checked";

    echo "<form name=asiakas action='asiakasinfo.php' method='post' autocomplete='off'>";
    echo "<input type = 'hidden' name = 'ytunnus' value = '$ytunnus'>";
    echo "<input type = 'hidden' name = 'asiakasid' value = '$asiakasid'>";
    echo "<table><tr>";
    echo "<th>".t("Rajaa näkymää")."</th></tr>";
    echo "<tr><td><input type='radio' name='rajaus' value='' onclick='submit()' $sel[K]>".t("Kaikki")." <input type='radio' name='rajaus' value='MYYNTI' onclick='submit()' $sel[M]>".t("Myynti")." <input type='radio' name='rajaus' value='ALENNUKSET' onclick='submit()' $sel[A]>".t("Alennukset")."</td></tr>";
    echo "</table>";
    echo "</form>";
  }

  if (($rajaus == "" or $rajaus == "MYYNTI") and $asiakas_yhtio == $kukarow["yhtio"]) {
    // tehdään asiakkaan ostot kausittain, sekä pylväät niihin...
    echo "<br><font class='message'>".t("Myynti kausittain viimeiset 24 kk")." (".t("myynti");

    if ($katteet_naytetaan) echo "/".t("kate");
    if ($katteet_naytetaan) echo "/".t("kateprosentti");

    echo "/".t("tavoite")."/".t("asiakaskäynnit").")</font>";
    echo "<hr>";

    // 24 kk sitten
    $ayy = date("Y-m-01", mktime(0, 0, 0, date("m")-24, date("d"), date("Y")));

    $query  = "SELECT date_format(tapvm,'%Y/%m') kausi,
               round(sum(arvo),0) myynti,
               round(sum(kate),0) kate,
               round(sum(kate)/sum(arvo)*100,1) katepro
               FROM lasku use index (yhtio_tila_liitostunnus_tapvm)
               WHERE yhtio      = '$kukarow[yhtio]'
               AND liitostunnus = '$asiakasid'
               AND tila         = 'U'
               AND tapvm        >= '$ayy'
               GROUP BY 1";
    $result = pupe_query($query);

    // otetaan suurin myynti talteen
    $maxeur   = 0;
    $sumarray = array();

    while ($sumrow = mysqli_fetch_assoc($result)) {
      if ($sumrow['myynti'] > $maxeur) $maxeur = $sumrow['myynti'];
      if ($katteet_naytetaan and $sumrow['kate'] > $maxeur) $maxeur = $sumrow['kate'];

      $sumarray[$sumrow['kausi']] = $sumrow;
    }

    $kuukausi  = date("m");
    $vuosi    = date("Y");
    $maxkay    = 0;
    $kayarray  = array();
    $budarray  = array();

    for ($i = 1; $i <= 24; $i++) {

      $kuukausi = mb_str_pad((int) $kuukausi, 2, 0, STR_PAD_LEFT);


      $tapahaku = "'Asiakaskäynti'";

      foreach ($sanakirja_kielet as $kieli => $devnull) {
        $kaannettyna = t("Asiakaskäynti", $kieli);

        if ($kaannettyna != "Asiakaskäynti") {
          $tapahaku .= ",'$kaannettyna'";
        }
      }

      // Asiakaskäynnit
      $query = "SELECT count(*) kaynnit
                FROM kalenteri
                WHERE yhtio      = '$kukarow[yhtio]'
                AND liitostunnus = '{$asiakasid}'
                and tapa         IN ({$tapahaku})
                and tyyppi       IN ('kalenteri','memo')
                and ((left(pvmalku,7) = '$vuosi-$kuukausi') or (left(pvmalku,7) < '$vuosi-$kuukausi' and left(pvmloppu,7) >= '$vuosi-$kuukausi'))";
      $result = pupe_query($query);
      $askarow = mysqli_fetch_assoc($result);

      if ($askarow['kaynnit'] > $maxkay) $maxkay = $askarow['kaynnit'];

      $kayarray["$vuosi/$kuukausi"] = $askarow;

      // Haetaan asiakasbudjetti
      // Löytyykö kokonaisbudjetti
      $budj_q = "SELECT
                 sum(if(osasto = '' and try = '', summa, 0)) kokonaisbudjetti,
                 sum(if(osasto != '', summa, 0)) osastobudjetti,
                 sum(if(try != '', summa, 0)) trybudjetti
                 FROM budjetti_asiakas
                 WHERE yhtio          = '$kukarow[yhtio]'
                 and kausi            = '$vuosi$kuukausi'
                 and asiakkaan_tunnus = '$asiakasid'";
      $budj_r = pupe_query($budj_q);
      $budjro = mysqli_fetch_assoc($budj_r);

      $paras_budjetti_array = array($budjro["kokonaisbudjetti"], $budjro["osastobudjetti"], $budjro["trybudjetti"]);
      sort($paras_budjetti_array);

      $paras_budjetti = round(array_pop($paras_budjetti_array), 0);

      if ($paras_budjetti > $maxeur) $maxeur = $paras_budjetti;

      $budarray["$vuosi/$kuukausi"] = $paras_budjetti;

      if ($kuukausi == 01) {
        $kuukausi = 12;
        $vuosi--;
      }
      else {
        $kuukausi--;
      }
    }


    echo "<table>\n";

    $col    = 1;
    $kuukausi = date("m");
    $vuosi     = date("Y");

    for ($i = 1; $i <= 24; $i++) {

      $kuukausi = mb_str_pad((int) $kuukausi, 2, 0, STR_PAD_LEFT);

      // MUOKKAUS: isset():
      if (!isset($sumarray["$vuosi/$kuukausi"])) $sumarray["$vuosi/$kuukausi"] = 0;
      if (!isset($kayarray["$vuosi/$kuukausi"])) $kayarray["$vuosi/$kuukausi"] = 0;
      if (!isset($budarray["$vuosi/$kuukausi"])) $budarray["$vuosi/$kuukausi"] = 0;

      $sumrow   = $sumarray["$vuosi/$kuukausi"];
      $askarow  = $kayarray["$vuosi/$kuukausi"];
      $budjetti = $budarray["$vuosi/$kuukausi"];

      if ($col==1) echo "<tr>\n";

      // lasketaan pylväiden korkeus
      if ($maxeur > 0 and is_array($sumrow)) { // MUOKKAUS: is_array()
        $hmyynti  = round(50*$sumrow['myynti'] / $maxeur, 0);
        $hkate    = round(50*$sumrow['kate'] / $maxeur, 0);
        $hkatepro = round($sumrow['katepro'] / 2, 0);
        $hbudj    = round(50*$budjetti / $maxeur, 0);

        if ($hkatepro>60) $hkatepro = 60;
      }
      else {
        $hmyynti = $hkate = $hkatepro = $hbudj = 0;
      }

      if ($maxkay > 0) {
        $haskay  = round(50*$askarow['kaynnit']/$maxkay, 0);
      }
      else {
        $haskay = 0;
      }

      $budjetti = ($budjetti == 0) ? "" : $budjetti;
      $askarow["kaynnit"] = ($askarow["kaynnit"] == 0) ? "" : $askarow["kaynnit"];

      // MUOKKAUS: is_array():
      if (is_array($sumrow)) {
        $sumrow["katepro"]  = (is_array($sumrow) and $sumrow["katepro"] == 0) ? "" : round($sumrow["katepro"], 0)."%";
      }

      // MUOKKAUS: isset():
      if (!isset($sumrow["myynti"]) and is_array($sumrow)) $sumrow["myynti"] = 0;
      if (!isset($sumrow["kate"]) and is_array($sumrow)) $sumrow["kate"] = 0;

      $pylvaat = "<table style='padding:0px;margin:0px;'><tr>
      <td style='padding:0px;margin:0px;vertical-align:bottom;' class='back'><img src='{$palvelin2}pics/blue.png' height='$hmyynti' width='12' alt='".t("myynti")." $sumrow[myynti]'></td>";
      if ($katteet_naytetaan) $pylvaat .= "<td style='padding:0px;margin:0px;vertical-align:bottom;' class='back'><img src='{$palvelin2}pics/orange.png' height='$hkate' width='12' alt='".t("kate")." $sumrow[kate]'></td>";
      if ($katteet_naytetaan) $pylvaat .= "<td style='padding:0px;margin:0px;vertical-align:bottom;' class='back'><img src='{$palvelin2}pics/green.png' height='$hkatepro' width='12' alt='".t("katepro")." $sumrow[katepro]'></td>";
      $pylvaat .= "<td style='padding:0px;margin:0px;vertical-align:bottom;' class='back'><img src='{$palvelin2}pics/yellow.png' height='$hbudj' width='12' alt='".t("tavoite")." $budjetti'></td>
      <td style='padding:0px;margin:0px;vertical-align:bottom;' class='back'><img src='{$palvelin2}pics/red.png' height='$haskay' width='12' alt='".t("asiakaskäynnit")." $askarow[kaynnit]'></td>
      </tr></table>";

      echo "<td class='back' style='vertical-align: bottom;'>";
      echo "<table width='60'>";
      echo "<tr><td nowrap align='center' style='padding:0px;margin:0px;vertical-align:bottom;height:55px;' class='back'>$pylvaat</td></tr>";
      echo "<tr><th nowrap align='right'>$vuosi/$kuukausi<br></th></tr>";
      echo "<tr><td nowrap align='right'><font class='myynti'>$sumrow[myynti]<br></font></td></tr>";
      if ($katteet_naytetaan) echo "<tr><td nowrap align='right'><font class='kate'>$sumrow[kate]<br></font></td></tr>";
      if ($katteet_naytetaan) echo "<tr><td nowrap align='right'><font class='katepros'>$sumrow[katepro]<br></font></td></tr>";
      echo "<tr><td nowrap align='right'><font class='katepros'>$budjetti<br></font></td></tr>";
      echo "<tr><td nowrap align='right'><font class='katepros'>$askarow[kaynnit]<br></font></td></tr>";
      echo "</table>";
      echo "</td>\n";

      if ($col==$maxcol) {
        echo "</tr>\n";
        $col=0;
      }

      $col++;

      if ($kuukausi == 01) {
        $kuukausi = 12;
        $vuosi--;
      }
      else {
        $kuukausi--;
      }
    }

    // tehään validia htmllää ja täytetään tyhjät solut..
    $ero = $maxcol+1-$col;

    if ($ero<>$maxcol)
      echo "<td colspan='$ero' class='back'></td></tr>\n";

    echo "</table>";


    // tehdään asiakkaan ostot tuoteryhmittäin... vikat 12 kk
    echo "<br><font class='message'>".t("Myynti osastoittain tuoteryhmittäin viimeiset 12 kk")." (".t("myynti")."/".t("määrä");
    if ($katteet_naytetaan) echo "/".t("kate");
    if ($katteet_naytetaan) echo "/".t("kateprosentti");
    echo ")</font>";
    echo "<hr>";

    echo "<form method='post'>";
    echo "<input type = 'hidden' name = 'lopetus' value = '$lopetus'>";
    echo "<br>".t("Näytä/piilota osastojen ja tuoteryhmien nimet.");
    echo "<input type ='hidden' name='ytunnus' value='$ytunnus'>";

    if ($nimet == 'nayta') {
      $sel = "CHECKED";
    }
    else {
      $sel = "";
    }

    echo "<input type='checkbox' name='nimet' value='nayta' onClick='submit()' $sel>";
    echo "</form>";

    // alkukuukauden tiedot 12 kk sitten
    $ayy = date("Y-m-01", mktime(0, 0, 0, date("m")-12, date("d"), date("Y")));

    $query = "SELECT osasto, try,
              round(sum(rivihinta),0) myynti,
              round(sum(tilausrivi.kate),0) kate,
              round(sum(kpl),0) kpl,
              round(sum(tilausrivi.kate)/sum(rivihinta)*100,1) katepro
              FROM lasku use index (yhtio_tila_liitostunnus_tapvm), tilausrivi use index (uusiotunnus_index)
              WHERE lasku.yhtio          = '$kukarow[yhtio]'
              AND lasku.liitostunnus     = '$asiakasid'
              AND lasku.tila             = 'U'
              AND lasku.alatila          = 'X'
              AND lasku.tapvm            >= '$ayy'
              AND tilausrivi.yhtio       = lasku.yhtio
              AND tilausrivi.uusiotunnus = lasku.tunnus
              GROUP BY 1,2
              HAVING myynti <> 0 OR kate <> 0
              ORDER BY osasto+0, try+0";
    $result = pupe_query($query);

    $col=1;
    echo "<table>\n";

    if ($nimet == 'nayta') {
      $maxcol = $maxcol/2;
    }

    while ($sumrow = mysqli_fetch_assoc($result)) {

      if ($col==1) echo "<tr>\n";

      if ($sumrow['katepro']=='') $sumrow['katepro'] = '0.0';

      echo "<td valign='bottom' class='back'>";

      // tehdään avainsana query
      $avainresult = t_avainsana("TRY", $kukarow['kieli'], "and avainsana.selite ='$sumrow[try]'");
      $tryrow = mysqli_fetch_assoc($avainresult);

      // tehdään avainsana query
      $avainresult = t_avainsana("OSASTO", $kukarow['kieli'], "and avainsana.selite ='$sumrow[osasto]'");
      $osastorow = mysqli_fetch_assoc($avainresult);

      if ($nimet == 'nayta') {
        $ostry = $osastorow["selitetark"]."<br>".$tryrow["selitetark"];
      }
      else {
        $ostry = $sumrow["osasto"]."/".$sumrow["try"];
      }


      echo "<table width='100%'>";
      echo "<tr><th nowrap align='right'><a href='tuorymyynnit.php?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&try=$sumrow[try]&osasto=$sumrow[osasto]&lopetus=$lopetus'>$ostry</a></th></tr>";
      echo "<tr><td nowrap align='right'><font class='myynti'>$sumrow[myynti] $yhtiorow[valkoodi]</font></td></tr>";
      echo "<tr><td nowrap align='right'><font class='myynti'>$sumrow[kpl] ".t("kpl")."</font></td></tr>";
      if ($katteet_naytetaan) echo "<tr><td nowrap align='right'><font class='kate'>$sumrow[kate]   $yhtiorow[valkoodi]</font></td></tr>";
      if ($katteet_naytetaan) echo "<tr><td nowrap align='right'><font class='katepros'>$sumrow[katepro] %</font></td></tr>";
      echo "</table>";

      echo "</td>\n";

      if ($col==$maxcol) {
        echo "</tr>\n";
        $col=0;
      }

      $col++;
    }

    // tehään validia htmllää ja täytetään tyhjät solut..
    $ero = $maxcol+1-$col;

    if ($ero<>$maxcol)
      echo "<td colspan='$ero' class='back'></td></tr>\n";

    echo "</table><br>";

    $query = "SELECT yhtio
              FROM oikeu
              WHERE yhtio = '$kukarow[yhtio]'
              and kuka    = '$kukarow[kuka]'
              and nimi    = 'raportit/myyntiseuranta.php'
              and alanimi = ''";
    $result = pupe_query($query);

    if (mysqli_num_rows($result) > 0) {

      $otee = $tee;
      $oasiakasrow = $asiakasrow;
      $orajaus = $rajaus;

      $_POST["fuuli"]  = "on";
      $tee       = "go";
      $ajotapa     = "lasku";
      $ajotapanlisa   = "summattuna";
      $yhtiot[]    = $kukarow["yhtio"];
      $asiakasosasto   = "";
      $asiakasosasto2 = "";
      $asiakasryhma   = "";
      $asiakasryhma2   = "";
      $jarjestys[1]  = "on";
      $asiakasid    = $asiakasrow["tunnus"];
      $asiakas    = $ytunnus;
      $rajaus      = array();
      $toimittaja   = "";
      $kka       = date("m", mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
      $vva       = date("Y", mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
      $ppa       = date("d", mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
      $kkl       = date("m");
      $vvl       = date("Y");
      $ppl       = date("d");

      if (!$katteet_naytetaan) $piilota_nettokate = "ON";
      if (!$katteet_naytetaan) $piilota_kate = "ON";

      //  Huijataan myyntiseurantaa
      if ($lopetus == "") $lopetus = "block";

      require "myyntiseuranta.php";

      if ($lopetus == "block") $lopetus = "";

      //  Korjataan ytunnus ja tee
      $ytunnus   = $asiakas;
      $tee     = $otee;
      $asiakasrow = $oasiakasrow;
      $rajaus   = $orajaus;
    }
  }

  $yhdistetty_array = array();

  if ($rajaus == "" or $rajaus == "ALENNUKSET") {

    if ($rajaus == "") {
      echo "<a href='#' name='alennukset'></a>";
    }

    echo "<br><font class='message'>".t("Asiakkaan alennusryhmät, alennustaulukko ja alennushinnat")."</font><hr>";

    if ($kukarow["extranet"] == "") {
      $sela = $selb = "";
      if ($rajattunakyma != "JOO") {
        $sela = "checked";
      }
      else {
        $selb = "checked";
      }
      echo "<form method='post' action='$PHP_SELF#alennukset'>";
      echo "<input type='hidden' name='asiakasid' value = '$asiakasid'>";
      echo "<input type='hidden' name='ytunnus' value = '$ytunnus'>";
      echo "<input type='hidden' name='rajaus' value = '$rajaus'>";
      echo "<input type='hidden' name='asale' value = '$asale'>";
      echo "<input type='hidden' name='ashin' value = '$ashin'>";
      echo "<input type='hidden' name='aletaulu' value = '$aletaulu'>";
      echo "<input type='hidden' name='yhdistetty' value = '$yhdistetty'>";
      echo "<input type='radio' onclick='submit()' name='rajattunakyma' value='' $sela> ".t("Normaalinäkymä");
      echo "<input type='radio' onclick='submit()' name='rajattunakyma' value='JOO' $selb> ".t("Extranetnäkymä");
      echo "</form><br>";
    }

    echo "<br><a href='$PHP_SELF?tee=eposti&ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("Tulosta alennustaulukko")."</a><br><br>";

    if ($asale != '' or $aletaulu != '' or $ashin != '' or $yhdistetty != "" or $tee == "eposti") {

      if (@include 'inc/pupeExcel.inc');
      else include 'pupeExcel.inc';

      $worksheet   = new pupeExcel();
      $format_bold = array("bold" => TRUE);
      $excelrivi   = 0;

      $taulu  = "";

      if ($aletaulu != "" or $tee == "eposti") {
        $tuotejoin  = " JOIN tuote ON tuote.yhtio=perusalennus.yhtio and tuote.aleryhma=perusalennus.ryhma and tuote.hinnastoon != 'E' and tuote.tuotetyyppi NOT IN ('A', 'B') ";
        $tuotewhere = " and (tuote.status not in ('P','X') or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0) ";
        $tuotegroup = " GROUP BY tuote.try, tuote.osasto, tuote.aleryhma ";
        $tuotecols   = ", tuote.osasto, tuote.try";
        $order     = " osasto+0, try+0, alennusryhmä+0, tuoteno, alennuslaji, prio ";
      }
      else {
        $tuotejoin  = "";
        $tuotewhere  = "";
        $tuotegroup  = "";
        $tuotecols   = "";
        $order     = "alennusryhmä+0, alennuslaji, prio, asiakasryhmä";
      }

      $asrows = hae_asiakasalennukset($asiakasrow, $alehi_assegmenttirow, $asiakas_yhtio, $tuotecols, $tuotejoin, $tuotewhere, $tuotegroup, $order);

      if ($aletaulu != "" or $tee == "eposti") {
        $ulos  = "<table><caption><font class='message'>".t("Alennustaulukko")."<br>".t("osastoittain/tuoteryhmittäin")."</font></caption>";
        if ($kukarow["extranet"] != "" or $rajattunakyma == "JOO") {
          $otsik = array("osasto", "try", "alennusryhmä", "tuoteno", "alennus", "$alennuslaji", "alkupvm", "loppupvm");
          $otsik_spread = array("osasto", "osasto_nimi", "try",  "try_nimi", "alennusryhmä", "alennusryhmä_nimi",  "tuoteno", "tuoteno_nimi", "alennus", "$alennuslaji", "alkupvm", "loppupvm");
        }
        else {
          $otsik = array("osasto", "try", "alennusryhmä", "tuoteno", "asiakasryhmä", "alennus", "$alennuslaji", "alkupvm", "loppupvm", "tyyppi");
          $otsik_spread = array("osasto", "osasto_nimi", "try", "try_nimi", "alennusryhmä", "alennusryhmä_nimi", "tuoteno", "tuoteno_nimi", "asiakasryhmä", "asiakasryhmä_nimi", "alennus", "$alennuslaji", "alkupvm", "loppupvm");
        }
      }
      elseif ($yhdistetty != "") {
        $ulos  = "<table><caption><font class='message'>".t("Yhdistetty alennustaulukko")."</font></caption>";
        if ($kukarow["extranet"] != "" or $rajattunakyma == "JOO") {
          $otsik = array("alennusryhmä", "alennusryhmä_nimi",  "tuoteno", "tuoteno_nimi", "osasto", "try", "alennus", "$alennuslaji", "alkupvm", "loppupvm");
        }
        else {
          $otsik = array("alennusryhmä", "alennusryhmä_nimi",  "tuoteno", "tuoteno_nimi", "asiakasryhmä",  "asiakasryhmä_nimi", "osasto", "try", "alennus", "$alennuslaji", "alkupvm", "loppupvm", "tyyppi");
        }
      }
      else {
        $ulos  = "<table><caption><font class='message'>".t("Alennustaulukko")."</font></caption>";
        if ($kukarow["extranet"] != "" or $rajattunakyma == "JOO") {
          $otsik = array("alennusryhmä", "tuoteno", "alennus", "$alennuslaji", "alkupvm", "loppupvm");
          $otsik_spread = array("alennusryhmä", "alennusryhmä_nimi", "tuoteno", "tuoteno_nimi", "alennus", "$alennuslaji", "alkupvm", "loppupvm");
        }
        else {
          $otsik = array("alennusryhmä",  "tuoteno", "asiakasryhmä", "alennus", "$alennuslaji", "alkupvm", "loppupvm", "tyyppi");
          $otsik_spread = array("alennusryhmä", "alennusryhmä_nimi", "tuoteno", "tuoteno_nimi", "asiakasryhmä", "alennus", "$alennuslaji", "alkupvm", "loppupvm");
        }
      }

      // Otsikot
      if ($yhdistetty == "") {
        $ulos  .= "<tr>";
        foreach ($otsik as $o) {
          $ulos .= "<th>".t(ucfirst($o))."</th>";
        }
        $ulos  .= "</tr>";
      }

      if ($yhdistetty == "") {
        foreach ($otsik_spread as $key => $value) {
          $worksheet->writeString($excelrivi, $key, t(ucfirst($value)), $format_bold);
        }
        $excelrivi++;
      }

      unset($edryhma);
      unset($edryhma2);
      $osastot = array();
      $tryt    = array();

      //  Haetaan osastot ja avainsanat muistiin
      // tehdään avainsana query
      $tryres = t_avainsana("OSASTO");

      while ($tryrow = mysqli_fetch_assoc($tryres)) {
        $osastot[$tryrow["selite"]] = $tryrow["selitetark"];
      }

      // tehdään avainsana query
      $tryres = t_avainsana("TRY");

      while ($tryrow = mysqli_fetch_assoc($tryres)) {
        $tryt[$tryrow["selite"]] = $tryrow["selitetark"];
      }

      foreach ($asrows as $asrow) {

        $tyhja = 0;
        //  Onko perusalessa tyhjä ryhmä?
        if ($asrow["prio"] == 5) {
          $query = "SELECT tuote.tunnus
                    FROM tuote
                    WHERE tuote.yhtio      = '$asiakas_yhtio'
                    and tuote.aleryhma     = '$asrow[alennusryhmä]'
                    and tuote.hinnastoon  != 'E'
                    AND tuote.tuotetyyppi  NOT IN ('A', 'B')
                    and (tuote.status not in ('P','X') or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)
                    LIMIT 1";
          $testres = pupe_query($query);

          if (mysqli_num_rows($testres) == 0) {
            $tyhja = 1;
          }
        }

        //  Suodatetaan extranetkäyttäjilta muut aleprossat
        if ((((($kukarow["extranet"] != "" or $tee == "eposti"  or $yhdistetty != "" or $rajattunakyma == "JOO") and ($edtry != $asrow["try"] or $edryhma != $asrow["alennusryhmä"] or $edtuoteno != $asrow["tuoteno"])) or ($kukarow["extranet"] == "" and $tee != "eposti" and $yhdistetty == ""  and $rajattunakyma != "JOO"))) and $tyhja == 0) {

          $edryhma   = $asrow["alennusryhmä"];
          $edtry     = $asrow["try"];
          $edtuoteno   = $asrow["tuoteno"];

          if ($yhdistetty == "") {
            foreach ($otsik_spread as $key => $value) {
              if ($value == "osasto_nimi") {
                $worksheet->writeString($excelrivi, $key, $osastot[$asrow["osasto"]]);
              }
              elseif ($value == "try_nimi") {
                $worksheet->writeString($excelrivi, $key, $tryt[$asrow["try"]]);
              }
              elseif ($value == "alennus" or $value == "alennuslaji" or $value == "hinta") {
                $worksheet->writeNumber($excelrivi, $key, $asrow[$value]);
              }
              else {
                $worksheet->writeString($excelrivi, $key, $asrow[$value]);
              }
            }
            $excelrivi++;
          }

          $dada = array();
          if ($yhdistetty == "") $ulos .= "<tr>";

          foreach ($otsik as $o) {

            if ($yhdistetty != "") {
              $dada[$o] = $asrow[$o];
            }
            else {
              //  Kaunistetaan tulostusta..
              if ($asrow[$o."_nimi"] != "" and $asrow[$o] != $asrow[$o."_nimi"]) {
                $arvo = $asrow[$o]." - ".$asrow[$o."_nimi"];
              }
              elseif ($o == "osasto" and $osastot[$asrow[$o]]) {
                $arvo = $asrow[$o]." - ".$osastot[$asrow[$o]];
                $osasto = $arvo;
              }
              elseif ($o == "try" and $tryt[$asrow[$o]] != "") {
                $arvo = $asrow[$o]." - ".$tryt[$asrow[$o]];
                $try = $arvo;
              }
              else {
                $arvo = $asrow[$o];
              }

              $align = "";

              if ($o == "alennus") {
                $align = " align='right' ";
              }

              if ($o == "alkupvm" or $o == "loppupvm") {
                $arvo = tv1dateconv($arvo);
              }

              $ulos .= "<td $align>$arvo</td>";
            }
          }

          if ($yhdistetty == "") $ulos .= "</tr>";

          if ($yhdistetty != "") {
            $yhdistetty_array[] = $dada;
          }

          if ($tee == 'eposti') {
            rivi($firstpage, $osasto, $try, $asrow["tuoteno"]." - ".$asrow["tuoteno_nimi"], $asrow["alennusryhmä"], $asrow["alennus"], $asrow["alennuslaji"]);
          }
        }
      }

      $ulos .= "</table>";

      //  Liitetään ulostus oikeaan muuttujaan
      if ($aletaulu != "") {
        $aletaulu = $ulos;
        $asale     = "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&asale=kylla&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("Alennustaulukko")."</a>";
      }
      elseif ($asale != "") {
        $asale = $ulos;
        $aletaulu   = "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&aletaulu=kylla&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("Alennustaulukko")."<br>".t("osastoittain/tuoteryhmittäin")."</a>";
      }
      else {
        $aletaulu   = "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&aletaulu=kylla&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("Alennustaulukko")."<br>".t("osastoittain/tuoteryhmittäin")."</a>";
        $asale     = "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&asale=kylla&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("Alennustaulukko")."</a>";
        $ulos = "";
      }
    }
    else {
      $asale     = "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&asale=kylla&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("Alennustaulukko")."</a>";
      $aletaulu   = "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&aletaulu=kylla&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("Alennustaulukko")."<br>".t("osastoittain/tuoteryhmittäin")."</a>";
    }

    if ($ashin != "" or $yhdistetty != "") {
      // haetaan asiakashintoja
      $ashin  = "<table><caption><font class='message'>".t("Asiakashinnat")."</font></caption>";

      if ($yhdistetty != "") {
        if ($kukarow["extranet"] != "" or $rajattunakyma == "JOO") {
          $otsik = array("alennusryhmä", "alennusryhmä_nimi", "tuoteno", "tuoteno_nimi", "hinta", "alkupvm", "loppupvm");
        }
        else {
          $otsik = array("alennusryhmä", "alennusryhmä_nimi", "tuoteno", "tuoteno_nimi", "hinta", "alkupvm", "loppupvm", "tyyppi");
        }
      }
      else {
        if ($kukarow["extranet"] != "" or $rajattunakyma == "JOO") {
          $otsik = array("alennusryhmä", "tuoteno", "hinta", "alkupvm", "loppupvm");
          $otsik_spread = array("alennusryhmä", "alennusryhmä_nimi", "tuoteno", "tuoteno_nimi", "hinta", "alkupvm", "loppupvm");
        }
        else {
          $otsik = array("alennusryhmä", "tuoteno", "asiakasryhmä", "hinta", "alkupvm", "loppupvm", "tyyppi");
          $otsik_spread = array("alennusryhmä", "alennusryhmä_nimi", "tuoteno", "tuoteno_nimi", "hinta", "alkupvm", "loppupvm");
        }
      }

      // Otsikot
      if ($yhdistetty == "") {
        foreach ($otsik_spread as $key => $value) {
          $worksheet->writeString($excelrivi, $key, t(ucfirst($value)), $format_bold);
        }
        $excelrivi++;
      }

      if ($yhdistetty == "") {
        $ashin  .= "<tr>";
        foreach ($otsik as $o) {
          $ashin .= "<th>".t(ucfirst($o))."</th>";
        }
        $ashin  .= "</tr>";
      }

      $asrows = hae_asiakashinnat($asiakasrow, $alehi_assegmenttirow, $asiakas_yhtio);

      foreach ($asrows as $asrow) {

        //  Suodatetaan extranetkäyttäjilta muut hinnat
        if (($kukarow["extranet"] != "" or $tee == "eposti" or $yhdistetty != "" or $rajattunakyma == "JOO") or ($kukarow["extranet"] == "" and $tee != "eposti" and $yhdistetty == "" or $rajattunakyma != "JOO") ) {
          if ($edryhma != $asrow["alennusryhmä"] or $edtuoteno != $asrow["tuoteno"] or $edasryhma != $asrow["asiakasryhmä"]) {
            $edryhma   = $asrow["alennusryhmä"];
            $edtuoteno = $asrow["tuoteno"];
            $edasryhma = $asrow["asiakasryhmä"];

            if ($yhdistetty == "") {
              foreach ($otsik_spread as $key => $value) {
                if ($value == "alennus" or $value == "alennuslaji" or $value == "hinta") {
                  $worksheet->writeNumber($excelrivi, $key, $asrow[$value]);
                }
                else {
                  $worksheet->writeString($excelrivi, $key, $asrow[$value]);
                }
              }
              $excelrivi++;
            }

            $dada = array();
            if ($yhdistetty == "") $ashin .= "<tr>";

            foreach ($otsik as $o) {

              if ($yhdistetty != "") {
                $dada[$o] = $asrow[$o];
              }
              else {
                //  Kaunistetaan tulostusta..
                if ($asrow[$o."_nimi"] != "" and $asrow[$o] != $asrow[$o."_nimi"]) {
                  $arvo = $asrow[$o]." - ".$asrow[$o."_nimi"];
                }
                elseif ($o == "osasto" and $osastot[$asrow[$o]]) {
                  $arvo = $asrow[$o]." - ".$osastot[$asrow[$o]];
                  $osasto = $arvo;
                }
                elseif ($o == "try" and $tryt[$asrow[$o]] != "") {
                  $arvo = $asrow[$o]." - ".$tryt[$asrow[$o]];
                  $try = $arvo;
                }
                else {
                  $arvo = $asrow[$o];
                }

                $align = "";

                if ($o == "hinta") {
                  $align = " align='right' ";
                }

                if ($o == "alkupvm" or $o == "loppupvm") {
                  $arvo = tv1dateconv($arvo);
                }

                $ashin .= "<td $align>$arvo</td>";
              }
            }
            if ($yhdistetty == "") $ashin .= "</tr>";

            if ($yhdistetty != "") {
              $yhdistetty_array[] = $dada;
            }
          }
        }
      }

      $ashin .= "</table>";

      if ($yhdistetty != "") {
        $ashin = "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&ashin=kylla&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("Asiakashinnat")."</a>";
      }
    }
    else {
      $ashin = "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&ashin=kylla&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("Asiakashinnat")."</a>";
    }

    if ($yhdistetty != '') {

      // tehdään yhdistetty alennustaulukko...
      $yhdistetty  = "<table><caption><font class='message'>".t("Yhdistetty alennustaulukko")."</font></caption>";

      if ($kukarow["extranet"] != "" or $rajattunakyma == "JOO") {
        $otsik = array("alennusryhmä",  "tuoteno", "alennus", "$alennuslaji", "hinta", "alkupvm", "loppupvm");
        $otsik_spread = array("alennusryhmä", "alennusryhmä_nimi",  "tuoteno", "tuoteno_nimi", "alennus", "$alennuslaji", "hinta", "alkupvm", "loppupvm");
      }
      else {
        $otsik = array("alennusryhmä",  "tuoteno", "asiakasryhmä", "alennus", "$alennuslaji", "hinta", "alkupvm", "loppupvm", "tyyppi");
        $otsik_spread = array("alennusryhmä", "alennusryhmä_nimi",  "tuoteno", "tuoteno_nimi", "alennus", "$alennuslaji", "hinta", "alkupvm", "loppupvm");
      }

      // Otsikot
      foreach ($otsik_spread as $key => $value) {
        $worksheet->writeString($excelrivi, $key, t(ucfirst($value)), $format_bold);
      }
      $excelrivi++;

      $yhdistetty  .= "<tr>";
      foreach ($otsik as $o) {
        $yhdistetty .= "<th>".t(ucfirst($o))."</th>";
      }
      $yhdistetty  .= "</tr>";

      foreach ($yhdistetty_array as $key => $value) {

        foreach ($otsik_spread as $key => $xvalue) {
          if ($xvalue == "alennus" or $xvalue == "alennuslaji" or $xvalue == "hinta") {
            $worksheet->writeNumber($excelrivi, $key, $value[$xvalue]);
          }
          else {
            $worksheet->writeString($excelrivi, $key, $value[$xvalue]);
          }
        }
        $excelrivi++;

        $yhdistetty .= "<tr>";

        foreach ($otsik as $o) {
          //  Kaunistetaan tulostusta..
          if ($value[$o."_nimi"] != "" and $value[$o] != $value[$o."_nimi"]) {
            $arvo = $value[$o]." - ".$value[$o."_nimi"];
          }
          elseif ($o == "osasto" and $osastot[$value[$o]]) {
            $arvo = $value[$o]." - ".$osastot[$value[$o]];
            $osasto = $arvo;
          }
          elseif ($o == "try" and $tryt[$value[$o]] != "") {
            $arvo = $value[$o]." - ".$tryt[$value[$o]];
            $try = $arvo;
          }
          else {
            $arvo = $value[$o];
          }

          $align = "";

          if ($o == "alennus") {
            $align = " align='right' ";
          }

          if ($o == "alkupvm" or $o == "loppupvm") {
            $arvo = tv1dateconv($arvo);
          }

          $yhdistetty .= "<td $align>$arvo</td>";
        }

        $yhdistetty .= "</tr>";
      }
      $yhdistetty .= "</table>";
    }
    else {
      $yhdistetty = "<a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&rajaus=$rajaus&yhdistetty=kylla&rajattunakyma=$rajattunakyma&lopetus=$lopetus#alennukset'>".t("Yhdistetty alennustaulukko")."</a>";
    }

    // piirretään ryhmistä ja hinnoista taulukko..
    echo "<table><tr>
        <td class='ptop back'>$asale</td>
        <td class='back'></td>
        <td class='ptop back'>$aletaulu</td>
        <td class='back'></td>
        <td class='ptop back'>$ashin</td>
        <td class='back'></td>
        <td class='ptop back'>$yhdistetty</td>
      </tr></table><br>";

    if (isset($worksheet) and $excelrivi > 1) {
      $excelnimi = $worksheet->close();

      echo "<table>";
      echo "<tr><th>".t("Tallenna tulos").":</th>";
      echo "<form method='post' class='multisubmit'>";
      echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
      echo "<input type='hidden' name='kaunisnimi' value='Alennustaulukko.xlsx'>";
      echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
      echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
      echo "</table><br><br>";
    }

    if ($tee == 'eposti') {
      //keksitään uudelle failille joku varmasti uniikki nimi:
      list($usec, $sec) = explode(' ', microtime());
      mt_srand((float) $sec + ((float) $usec * 100000));
      $pdffilenimi = "/tmp/Aletaulukko-".md5(uniqid(mt_rand(), true)).".pdf";

      //kirjoitetaan pdf faili levylle..
      $fh = fopen($pdffilenimi, "w");
      if (fwrite($fh, $pdf->generate()) === FALSE) die("".t("PDF kirjoitus epäonnistui")." $pdffilenimi");
      fclose($fh);

      // itse print komento...
      if ($komento["Alennustaulukko"] == 'email' or $kukarow["extranet"] != "") {
        $liite = $pdffilenimi;
        $kutsu = "Alennustaulukko - ".trim($asiakasrow["nimi"]." ".$asiakasrow["nimitark"]);

        if ($kukarow["extranet"] != "") {
          require "sahkoposti.inc";
        }
        else {
          require "inc/sahkoposti.inc";
        }

        echo "<br><br>".t("Alennustaulukko lähetetään osoitteeseen")." $kukarow[eposti]...<br>";
      }
      elseif ($komento["Alennustaulukko"] != '' and $komento["Alennustaulukko"] != 'edi') {
        $line = exec("$komento[Alennustaulukko] $pdffilenimi");

        echo "<br><br>".t("Tulostetaan alennustaulukko $kukarow[eposti]")."...<br>";
      }
    }
  }
}

// kursorinohjausta
$formi  = "asiakas";
$kentta = "ytunnus";

if (@include "inc/footer.inc");
elseif (@include "footer.inc");
else exit;
