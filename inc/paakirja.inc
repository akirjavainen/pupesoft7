<?php

// jos yritetäänä valita molempia niin ei anneta mitään!
if ($asia_ytunnus != "" and $toim_ytunnus != "") {
  $ytunnus = $toim_ytunnus = $asia_ytunnus = $asiakasid = $toimittajaid = $tee = "";
}

if ($ytunnus == "" and $asia_ytunnus == "" and $asiakasid != "") {
  $tee = $asiakasid = "";
}

if ($asia_ytunnus != '' or $asiakasid != '') {
  if ($asiakasid == "") $tee = "";
  if ($ytunnus == "") $ytunnus = $asia_ytunnus;
  require "inc/asiakashaku.inc";
  $asia_ytunnus = $ytunnus;
}

if ($ytunnus == "" and $toim_ytunnus == "" and $toimittajaid != "") {
  $tee = $toimittajaid = "";
}

if ($toim_ytunnus != '' or $toimittajaid != '') {
  if ($toimittajaid == "") $tee = "";
  if ($ytunnus == "") $ytunnus = $toim_ytunnus;
  require "inc/kevyt_toimittajahaku.inc";
  $toim_ytunnus = $ytunnus;
}

echo "<font class='head'>".t("Pää- ja päiväkirja")."</font><hr>
    <form name = 'valinta' action = 'raportit.php' method='post'>
    <input type = 'hidden' name = 'toim' value = '$toim'>
    <input type = 'hidden' name = 'lopetus' value = '$lopetus'>
    <table><tr>
    <th>".t("Tyyppi")."</th>
    <td colspan='3'>";

$sel = array();
if (isset($tee)) $sel[$tee] = "SELECTED";

echo "<select name='tee'>
    <option value = 'K' $sel[K]>".t("Pääkirja")."</option>
    <option value = 'P' $sel[P]>".t("Päiväkirja")."</option>
    </select></td></tr>";

echo "<tr><th>".t("Ajalta")."</th><td colspan='3'><select name='alvv'>";

if (!isset($alvv)) $alvv = date("Y");

$sel = array();
$sel[$alvv] = "SELECTED";

for ($i = date("Y") + 1; $i >= date("Y") - 6; $i--) {
  echo "<option value='$i' $sel[$i]>$i</option>";
}

echo "</select>";

$sel = array();
if (isset($alvk)) $sel[$alvk] = "SELECTED";

echo "<select name='alvk'>
  <option value = '0'>".t("koko kalenterivuosi")."</option>
  <option value = '01' $sel[01]>01</option>
  <option value = '02' $sel[02]>02</option>
  <option value = '03' $sel[03]>03</option>
  <option value = '04' $sel[04]>04</option>
  <option value = '05' $sel[05]>05</option>
  <option value = '06' $sel[06]>06</option>
  <option value = '07' $sel[07]>07</option>
  <option value = '08' $sel[08]>08</option>
  <option value = '09' $sel[09]>09</option>
  <option value = '10' $sel[10]>10</option>
  <option value = '11' $sel[11]>11</option>
  <option value = '12' $sel[12]>12</option>
  </select>";

$sel = array();
if (isset($alvp)) $sel[$alvp] = "SELECTED";

echo "<select name='alvp'>
    <option value = '0'>".t("koko kuukausi")."</option>
    <option value = '01' $sel[01]>01</option>
    <option value = '02' $sel[02]>02</option>
    <option value = '03' $sel[03]>03</option>
    <option value = '04' $sel[04]>04</option>
    <option value = '05' $sel[05]>05</option>
    <option value = '06' $sel[06]>06</option>
    <option value = '07' $sel[07]>07</option>
    <option value = '08' $sel[08]>08</option>
    <option value = '09' $sel[09]>09</option>
    <option value = '10' $sel[10]>10</option>
    <option value = '11' $sel[11]>11</option>
    <option value = '12' $sel[12]>12</option>
    <option value = '13' $sel[13]>13</option>
    <option value = '14' $sel[14]>14</option>
    <option value = '15' $sel[15]>15</option>
    <option value = '16' $sel[16]>16</option>
    <option value = '17' $sel[17]>17</option>
    <option value = '18' $sel[18]>18</option>
    <option value = '19' $sel[19]>19</option>
    <option value = '20' $sel[20]>20</option>
    <option value = '21' $sel[21]>21</option>
    <option value = '22' $sel[22]>22</option>
    <option value = '23' $sel[23]>23</option>
    <option value = '24' $sel[24]>24</option>
    <option value = '25' $sel[25]>25</option>
    <option value = '26' $sel[26]>26</option>
    <option value = '27' $sel[27]>27</option>
    <option value = '28' $sel[28]>28</option>
    <option value = '29' $sel[29]>29</option>
    <option value = '30' $sel[30]>30</option>
    <option value = '31' $sel[31]>31</option>
    </select>
    </td>
    <tr>
    <th>".t("Poikkeava loppuaika")."</th>
    <td colspan='3'>
    <select name='llvv'>
    <option value='0'></option>";

for ($i = date("Y")+1; $i >= date("Y")-6; $i--) {
  if ($i == $llvv) $sel = "selected";
  else $sel = "";
  echo "<option value='$i' $sel>$i</option>";
}

echo "</select>";

$sel = array();
if (isset($llvk)) $sel[$llvk] = "SELECTED";

echo "<select name='llvk'>
    <option value = '0'></option>
    <option value = '01' $sel[01]>01</option>
    <option value = '02' $sel[02]>02</option>
    <option value = '03' $sel[03]>03</option>
    <option value = '04' $sel[04]>04</option>
    <option value = '05' $sel[05]>05</option>
    <option value = '06' $sel[06]>06</option>
    <option value = '07' $sel[07]>07</option>
    <option value = '08' $sel[08]>08</option>
    <option value = '09' $sel[09]>09</option>
    <option value = '10' $sel[10]>10</option>
    <option value = '11' $sel[11]>11</option>
    <option value = '12' $sel[12]>12</option>
    </select>";

$sel = array();
if (isset($llvp)) $sel[$llvp] = "SELECTED";

echo "<select name='llvp'>
    <option value = '0'></option>
    <option value = '01' $sel[01]>01</option>
    <option value = '02' $sel[02]>02</option>
    <option value = '03' $sel[03]>03</option>
    <option value = '04' $sel[04]>04</option>
    <option value = '05' $sel[05]>05</option>
    <option value = '06' $sel[06]>06</option>
    <option value = '07' $sel[07]>07</option>
    <option value = '08' $sel[08]>08</option>
    <option value = '09' $sel[09]>09</option>
    <option value = '10' $sel[10]>10</option>
    <option value = '11' $sel[11]>11</option>
    <option value = '12' $sel[12]>12</option>
    <option value = '13' $sel[13]>13</option>
    <option value = '14' $sel[14]>14</option>
    <option value = '15' $sel[15]>15</option>
    <option value = '16' $sel[16]>16</option>
    <option value = '17' $sel[17]>17</option>
    <option value = '18' $sel[18]>18</option>
    <option value = '19' $sel[19]>19</option>
    <option value = '20' $sel[20]>20</option>
    <option value = '21' $sel[21]>21</option>
    <option value = '22' $sel[22]>22</option>
    <option value = '23' $sel[23]>23</option>
    <option value = '24' $sel[24]>24</option>
    <option value = '25' $sel[25]>25</option>
    <option value = '26' $sel[26]>26</option>
    <option value = '27' $sel[27]>27</option>
    <option value = '28' $sel[28]>28</option>
    <option value = '29' $sel[29]>29</option>
    <option value = '30' $sel[30]>30</option>
    <option value = '31' $sel[31]>31</option>
    </select></td>
    </tr>";

echo "<tr><th>".t("tai koko tilikausi")."</th>";

$query = "SELECT *
          FROM tilikaudet
          WHERE yhtio = '$kukarow[yhtio]'
          ORDER BY tilikausi_alku DESC";
$vresult = pupe_query($query);

echo "<td colspan='3'><select name='tkausi'><option value='0'>".t("Ei valintaa")."</option>";

while ($vrow = mysqli_fetch_array($vresult)) {
  $sel="";
  if ($tkausi == $vrow["tunnus"]) {
    $sel = "selected";
  }
  echo "<option value = '$vrow[tunnus]' $sel>".tv1dateconv($vrow["tilikausi_alku"])." - ".tv1dateconv($vrow["tilikausi_loppu"])."</option>";
}
echo "</select></td>";
echo "</tr>";

echo "<tr>
    <th>".t("Vain tili")."</th>";

echo "<td>";
echo livesearch_kentta("valinta", "TILIHAKU", "tili", 120, $tili, "EISUBMIT");
echo "</td><td> - </td><td>";
echo livesearch_kentta("valinta", "TILIHAKU", "tili2", 120, $tili2, "EISUBMIT");
echo "</td></tr>";

echo "<tr><th>".t("Vain asiakas")."</th><td colspan='3'><input type='text' name='asia_ytunnus' value='".tarkistahetu($asiakasrow['ytunnus'])."'> $asiakasrow[nimi] <input type='hidden' name='asiakasid' value='$asiakasid'></td></tr>";
echo "<tr><th>".t("Vain toimittaja")."</th><td colspan='3'><input type='text' name='toim_ytunnus' value='".tarkistahetu($toimittajarow['ytunnus'])."'> $toimittajarow[nimi] <input type='hidden' name='toimittajaid' value='$toimittajaid'></td></tr>";

echo "<tr><th>".t("Vain alv%")."</th>";

$vresult = t_avainsana("ALV");

echo "<td colspan='3'><select name='alv'><option value='tyhja'>".t("Ei valintaa");

while ($vrow = mysqli_fetch_array($vresult)) {
  $sel="";
  if ($vrow["selite"] == $alv) {
    $sel = "selected";
  }
  echo "<option value = '$vrow[selite]' $sel>$vrow[selite]%</option>";
}
echo "</select></td>";
echo "</tr>";

$sel="";
if ($kasin != "") {
  $sel = "CHECKED";
}

echo "<tr><th>".t("Vain käsin viedyt")."</th><td colspan='3'><input type='checkbox' name='kasin' $sel>";

$query = "SELECT kuka.kuka, kuka.nimi
          FROM kuka
          WHERE kuka.yhtio    = '{$kukarow["yhtio"]}'
          AND kuka.aktiivinen = 1
          and kuka.extranet   = ''
          ORDER BY kuka.nimi";
$vresult = pupe_query($query);

echo " <select name='laatija'><option value = '' >".t("Valitse laatija")."</option>";

if (!is_array($sel)) $sel = array(); // MUOKKAUS: isset()
if (isset($laatija)) $sel[$laatija] = "SELECTED";

while ($vrow = mysqli_fetch_assoc($vresult)) {
  echo "<option value = '{$vrow['kuka']}' {$sel[$vrow['kuka']]}>{$vrow['nimi']}</option>";
}

echo "<option value = 'automaatti' {$sel['automaatti']}>".t("Viiteaineistojen käsittely")."</option>";
echo "<option value = 'tiliote' {$sel['tiliote']}>".t("Tiliotteiden käsittely")."</option>";
echo "<option value = 'verkkolas' {$sel['verkkolas']}>".t("Verkkolaskujen vastaanotto")."</option>";
echo "<option value = 'crond' {$sel['crond']}>".t("Cron-ajo")."</option>";
echo "</select>";
echo "</td></tr>";

$sel="";
if ($excel != "") {
  $sel = "CHECKED";
}

echo "<tr><th>".t("Raportti vain Exceliin")."</th><td colspan='3'><input type='checkbox' name='excel' value = 'YES' $sel></td></tr>";

$alvsel = ($post_alv != "") ? "CHECKED" : "";

echo "<tr>";
echo "<th>";
echo t("Vain ALV-laskelman jälkeen tehdyt tiliöinnit");
echo "</th>";
echo "<td colspan='3'>";
echo "<input type = 'checkbox' name = 'post_alv' value = 'YES' {$alvsel}> ";
echo t("Toimii vain jos haetaan koko kuukaudelta!");
echo "</td>";
echo "</tr>";

echo "<tr><th valign='top'>".t("Konsernirajaus")."</th>";

$konsel = array();
$konsel[$konsernirajaus] = "SELECTED";

echo "<td colspan='3'><select name='konsernirajaus'>
    <option value=''>".t("Näytetään kaikki tiliöinnit")."</option>
    <option value='AT' $konsel[AT]>".t("Näytetään vain konserniasiakkaiden ja konsernitoimittajien tiliöinnit")."</option>
    <option value='T'  $konsel[T]>".t("Näytetään vain konsernitoimitajien tiliöinnit")."</option>
    <option value='A'  $konsel[A]>".t("Näytetään vain konserniasiakkaiden tiliöinnit")."</option>
    </select>
    </td></tr>";

echo "<tr><th valign='top'>".t("Maarajaus")."</th>";

if (!isset($maarajaus)) $maarajaus = '';

$query = "SELECT distinct koodi, nimi
          FROM maat
          WHERE nimi != ''
          ORDER BY koodi";
$vresult = pupe_query($query);

echo "<td colspan='3'><select name='maarajaus'><option value = '' >".t("Ei valintaa")."</option>";

while ($vrow=mysqli_fetch_array($vresult)) {
  $sel='';
  if (mb_strtoupper($maarajaus) == mb_strtoupper($vrow[0])) {
    $sel = "selected";
  }
  echo "<option value = '".mb_strtoupper($vrow[0])."' $sel>".t($vrow[1])."</option>";
}
echo "</select></td></tr>";

$sel='';
if (!isset($palvelut)) $palvelut = '';
if ($palvelut != "") {
  $sel = "CHECKED";
}

// Tämä vielä tekemättä
//echo "<tr><th valign='top'>".t("Palveluiden erittely")."</th><td colspan='3'><input type='checkbox' $palvelut>";

echo "</table><br>";

$monivalintalaatikot = array("KUSTP", "KOHDE", "PROJEKTI", "ASIAKASOSASTO", "ASIAKASRYHMA");
$noautosubmit = TRUE;

require "tilauskasittely/monivalintalaatikot.inc";

echo "<br><input type = 'submit' value = '".t("Näytä")."'></form><br><br>";

if ($tee == '') {
  $formi = 'valinta';
  $kentta = 'tili';
}

if ($tee != "") {

  // Tutkitaan ensiksi, mille tilikaudelle pyydettävä lista löytyy, jos lista on sopiva
  $blvk = 0;
  $blvp = 0;

  $alvk = (int) $alvk;
  $alvp = (int) $alvp;
  $alvv = (int) $alvv;
  $llvk = (int) $llvk;
  $llvp = (int) $llvp;
  $llvv = (int) $llvv;
  $tkausi = (int) $tkausi;

  $tili = empty($tili) ? '' : trim($tili);
  $tili2 = empty($tili2) ? '' : trim($tili2);
  $lisa = empty($lisa) ? '' : trim($lisa);
  $konsernirajaus = empty($konsernirajaus) ? '' : trim($konsernirajaus);

  $post_alv = empty($post_alv) ? '' : trim($post_alv);
  $post_alv_lisa = "";

  if ($post_alv != "" and $alvk < 1) {
    echo "<font class='error'>";
    echo t("ALV-Laskelman jälkeen tehtyjä tiliöintejä haettaessa pitää valita kuukausi.");
    echo "</font>";
    echo "<br><br>";
    $tee = "";
  }

  if ($post_alv != "" and $alvp > 0) {
    echo "<font class='error'>";
    echo t("ALV-Laskelman jälkeen tehtyjä tiliöintejä haettaessa pitää valita koko kuukausi.");
    echo "</font>";
    echo "<br><br>";
    $tee = "";
  }

  if ($post_alv != "" and $tkausi > 0) {
    echo "<font class='error'>";
    echo t("ALV-Laskelman jälkeen tehtyjä tiliöintejä haettaessa ei voi valita tilikautta.");
    echo "</font>";
    echo "<br><br>";
    $tee = "";
  }

  if ($post_alv != '' and $alvp == 0 and $alvk != 0) {
    $kuunvika = date("Y-m-t", strtotime("{$alvv}-{$alvk}-01"));

    $query = "SELECT luontiaika
              FROM lasku
              WHERE lasku.yhtio = '{$kukarow['yhtio']}'
              AND lasku.tapvm   = '{$kuunvika}'
              AND lasku.tila    = 'X'
              AND lasku.nimi    = 'ALVTOSITEMAKSUUN$kuunvika'";
    $result = pupe_query($query);

    if (mysqli_num_rows($result) > 0) {
      $luontiaikarow = mysqli_fetch_assoc($result);
      $laskelma_luotu = $luontiaikarow['luontiaika'];
      $post_alv_lisa = " and tiliointi.laadittu >= '$laskelma_luotu' and tiliointi.vero > 0";
    }
    else {
      echo "<font class='error'>";
      echo t("Valitun kuukauden ALV-laskelmaa ei ole lähetetty!");
      echo "</font>";
      echo "<br><br>";
      $tee = "";
    }
  }

  if ($llvv > 0) {
    if ($alvv == 0 or $alvk == 0 or $alvp == 0 or $llvv == 0 or $llvk == 0 or $llvp == 0) {
      echo "<font class='error'>".t("Haettaessa aikaväliltä pitää valita tarkka alku ja loppupäivä.")."</font><br><br>";
      $tee = "";
    }

    // Jos haetaan päivämäärillä niin nollataan tilikausi
    $tkausi = 0;

    $query = "SELECT *
              FROM tilikaudet
              WHERE yhtio         = '$kukarow[yhtio]'
              and tilikausi_alku  <= '$alvv-$alvk-$alvp'
              and tilikausi_loppu >= '$llvv-$llvk-$llvp'";
    $result = pupe_query($query);
    $tilikaudetrow = mysqli_fetch_array($result);
  }
  else {
    if ($tkausi == 0) {
      $blvk = $alvk;
      $blvp = $blvk;

      if ($blvk == 0) $blvk = 1;
      if ($blvp == 0) $blvp = 1;

      $query = "SELECT *
                FROM tilikaudet
                WHERE yhtio         = '$kukarow[yhtio]'
                and tilikausi_alku  <= '$alvv-$blvk-$blvp'
                and tilikausi_loppu >= '$alvv-$blvk-$blvp'";
      $result = pupe_query($query);
    }
    else {
      // Jos haetaan tilikaudella niin nollataan muut päivämäärät
      $alvv = 0;
      $alvk = 0;
      $alvp = 0;
      $llvv = 0;
      $llvk = 0;
      $llvp = 0;

      $query = "SELECT *
                FROM tilikaudet
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$tkausi'";
      $result = pupe_query($query);
    }

    if (mysqli_num_rows($result) != 1) {
      echo "<font class='error'>".t("Sopivaa yrityksen tilikautta ei löytynyt")."</font><br><br>";
      $tee = "";
    }

    $tilikaudetrow = mysqli_fetch_array($result);
  }
}

if ($tee != "") {
  $lisa1 = ''; // aineiston hakemiseen
  $lisa2 = ''; // alkusaldon laskemiseen

  // Tehdäänkö linkit päiväkirjaan
  $mutositelink = tarkista_oikeus("muutosite.php");

  $nimi = t("Pääkirja");
  $jarj = "tiliointi.tilino, tiliointi.tapvm, tiliointi.laadittu, tiliointi.summa";

  // Haluttiinkin päiväkirja
  if ($tee == 'P') {
    $nimi = t("Päiväkirja");
    $jarj = "tiliointi.tapvm, tiliointi.laadittu, tiliointi.tilino, tiliointi.summa";
  }

  if ($tkausi > 0) {
    $alkupvm = $tilikaudetrow["tilikausi_alku"];
    $loppupvm = $tilikaudetrow["tilikausi_loppu"];
  }
  elseif ($llvv > 0) {
    $alkupvm = date("Y-m-d", mktime(0, 0, 0, $alvk, $alvp, $alvv));
    $loppupvm = date("Y-m-d", mktime(0, 0, 0, $llvk, $llvp, $llvv));
  }
  elseif ($alvk == 0) {
    $alkupvm = date("Y-m-d", mktime(0, 0, 0, 1, 1, $alvv));
    $loppupvm = date("Y-m-d", mktime(0, 0, 0, 1, 0, $alvv + 1));
  }
  elseif ($alvp == 0) {
    $alkupvm = date("Y-m-d", mktime(0, 0, 0, $alvk, 1, $alvv));
    $loppupvm = date("Y-m-d", mktime(0, 0, 0, $alvk + 1, 0, $alvv));
  }
  else {
    $alkupvm = date("Y-m-d", mktime(0, 0, 0, $alvk, $alvp, $alvv));
    $loppupvm = $alkupvm;
  }

  if ($post_alv != "") {
    echo "<font class='message'>";
    echo t("Näytetään vain ALV-laskelman jälkeen tehdyt tiliöinnit");
    echo "</font>";
    echo "<br><br>";
  }

  $lisa1 = " and tiliointi.tapvm >= '$alkupvm' AND tiliointi.tapvm <= '$loppupvm'";
  $lisa2 = " and tiliointi.tapvm < '$alkupvm'";

  echo "<font class='head'>";
  echo "{$nimi} ";
  echo t("ajalta");
  echo " ";
  echo tv1dateconv($alkupvm);
  echo " - ";
  echo tv1dateconv($loppupvm);
  echo "</font>";
  echo "<hr>";

  if ($tili != "" and $tili2 != "") {
    $lisa1 .= " and tiliointi.tilino >= '$tili' and tiliointi.tilino <= '$tili2'";
  }
  elseif ($tili != "" and mb_strpos($tili, ",") !== false) {
    $lisa1 .= " and tiliointi.tilino in ('".str_replace(",", "','", $tili)."')";
  }
  elseif ($tili != "") {
    $lisa1 .= " and tiliointi.tilino = '$tili'";
  }

  if ($kasin != '') {
    $lisa1 .= " and lasku.tila = 'X'";
  }

  if ($asiakasid != "") {
    $lisa1 .= " and ((lasku.liitostunnus='{$asiakasid}' and lasku.tila in ('U','L')) or (tiliointi.liitos = 'A' and tiliointi.liitostunnus='{$asiakasid}' and lasku.tila = 'X')) ";
  }

  if ($toimittajaid != "") {
    $lisa1 .= " and ((lasku.liitostunnus='{$toimittajaid}' and lasku.tila in ('H','Y','M','P','Q')) or (tiliointi.liitos='T' and tiliointi.liitostunnus='{$toimittajaid}' and lasku.tila = 'X')) ";
  }

  if (!isset($alv)) {
    $alv = 'tyhja';
  }

  if ($alv != 'tyhja') {
    $lisa1 .= " and tiliointi.vero = '$alv' ";
  }

  if ($laatija != '') {
    $lisa1 .= " and tiliointi.laatija = '$laatija' ";
  }

  if ($maarajaus != '') {
    $lisa .= " and if(lasku.toim_maa != '', lasku.toim_maa, if(lasku.maa != '', lasku.maa, '$yhtiorow[maa]')) = '$maarajaus' ";
  }

  $asiakasjoini = "";
  $toimittajajoini = "";
  $konsernilisa = "";

  // Tarvitaanko asiakas joini
  $_monivalinnasta = (mb_strpos($lisa, "asiakas.") !== FALSE);
  $_konsernivalinnasta = ($konsernirajaus == "A" or $konsernirajaus == "AT");

  if ($_monivalinnasta or $_konsernivalinnasta) {
    $asiakasjoini = "LEFT JOIN asiakas ON (lasku.yhtio = asiakas.yhtio
    AND asiakas.tunnus = if(tiliointi.liitos = 'A', tiliointi.liitostunnus, lasku.liitostunnus))";
  }

  // Tarvitaanko toimittajajoini joini
  $_konsernivalinnasta = ($konsernirajaus == "T" or $konsernirajaus == "AT");

  if ($_konsernivalinnasta) {
    $toimittajajoini = " LEFT JOIN toimi ON (toimi.yhtio = lasku.yhtio
    AND toimi.tunnus = if(tiliointi.liitos = 'T', tiliointi.liitostunnus, lasku.liitostunnus))";
  }

  if ($konsernirajaus == "AT") {
    $konsernilisa = " and (asiakas.konserniyhtio != '' or toimi.konserniyhtio != '') ";
  }
  elseif ($konsernirajaus == "T") {
    $konsernilisa = " and toimi.konserniyhtio != '' ";
  }
  elseif ($konsernirajaus == "A") {
    $konsernilisa = " and asiakas.konserniyhtio != '' ";
  }

  // MUOKKAUS: lisatty lasku.tunnus kyselyyn:
  $query = "SELECT concat_ws(' ', tiliointi.tilino, tili.nimi) AS tili,
            tiliointi.tapvm,
            kustp.nimi AS kustp,
            kohde.nimi AS kohde,
            projekti.nimi AS projekti,
            concat_ws(' ', selite, lasku.nimi) AS selite,
            tiliointi.summa,
            tiliointi.vero,
            tiliointi.ltunnus,
            tiliointi.tilino AS xtilino,
            tiliointi.summa_valuutassa,
            tiliointi.valkoodi,
            tiliointi.tunnus AS rivitunnus,
            kustp.koodi AS kustp_koodi,
            kohde.koodi AS kohde_koodi,
            projekti.koodi AS projekti_koodi,
            tili.alv_taso,
            lasku.tapvm AS laskutapvm,
            lasku.mapvm AS laskumapvm,
            lasku.summa AS laskusumma,
            lasku.tunnus AS laskutunnus,
            lasku.tila
            FROM tiliointi
            LEFT JOIN tili ON (tili.tilino = tiliointi.tilino and tili.yhtio = tiliointi.yhtio)
            LEFT JOIN lasku ON (lasku.tunnus = tiliointi.ltunnus)
            LEFT JOIN kustannuspaikka kustp ON (tiliointi.yhtio = kustp.yhtio and tiliointi.kustp = kustp.tunnus)
            LEFT JOIN kustannuspaikka kohde ON (tiliointi.yhtio = kohde.yhtio and tiliointi.kohde = kohde.tunnus)
            LEFT JOIN kustannuspaikka projekti ON (tiliointi.yhtio = projekti.yhtio and tiliointi.projekti = projekti.tunnus)
            $asiakasjoini
            $toimittajajoini
            WHERE tiliointi.yhtio  = '{$kukarow["yhtio"]}'
            AND tiliointi.korjattu = ''
            $lisa1
            $lisa
            $post_alv_lisa
            $konsernilisa
            ORDER BY $jarj";
  $result = pupe_query($query);

  if (mysqli_num_rows($result) > 0) {

    if (!isset($worksheet)) {
      echo "<table><tr>";

      if ($tee == 'P') {
        echo "<th>".t("Pvm")."</th>";
        echo "<th>".t("Tili")."</th>";
      }
      else {
        echo "<th>".t("Tili")."</th>";
        echo "<th>".t("Pvm")."</th>";
      }

      echo "<th>".t("Kustp")."</th>";
      echo "<th>".t("Kohde")."</th>";
      echo "<th>".t("Projekti")."</th>";
      echo "<th>".t("Selite")."</th>";
      echo "<th>".t("Summa")."</th>";
      echo "<th>".t("Vero")."</th>";
    }
    else {
      echo t("Raportti tulostetaan exceliin")."...";
      flush();
    }

    if (isset($worksheet)) {
      if ($tee == 'P') {
        $worksheet->write($excelrivi, 0, ucfirst(t("Pvm")), $format_bold);
        $worksheet->write($excelrivi, 1, ucfirst(t("Tili")), $format_bold);
      }
      else {
        $worksheet->write($excelrivi, 0, ucfirst(t("Tili")), $format_bold);
        $worksheet->write($excelrivi, 1, ucfirst(t("Pvm")), $format_bold);
      }

      $worksheet->write($excelrivi, 2, ucfirst(t("Kustp")), $format_bold);
      $worksheet->write($excelrivi, 3, ucfirst(t("Kohde")), $format_bold);
      $worksheet->write($excelrivi, 4, ucfirst(t("Projekti")), $format_bold);
      $worksheet->write($excelrivi, 5, ucfirst(t("Selite")), $format_bold);
      $worksheet->write($excelrivi, 6, ucfirst(t("Summa")), $format_bold);
      $worksheet->write($excelrivi, 7, ucfirst(t("Vero")), $format_bold);
    }

    if (mb_strlen(trim($tili)) > 0 and isset($tilikaudetrow)) {
      if (!isset($worksheet)) echo "<th>".t("Kum. Summa")."</th>";
      if (isset($worksheet)) $worksheet->write($excelrivi, 8, t("Kum. Summa"), $format_bold);
    }

    if (isset($worksheet)) {
      $excelrivi++;
    }

    if (!isset($worksheet)) echo "</tr>";

    $summaplus = 0.0;
    $summamiinus = 0.0;
    $summa = 0.0;
    $edtili = "";
    $summa_valuutassa = array();

    while ($trow = mysqli_fetch_assoc($result)) {

      // exceliin jokaiselle riville....
      $tapvm_clean = $trow["tapvm"];
      $tili_clean = $trow["tili"];

      if (!isset($worksheet)) echo "<tr class='aktiivi'>";

      if ($tee == 'P' and $edtrow["tapvm"] == $trow["tapvm"]) {
        $trow["tapvm"] = "";
      }
      elseif ($tee != 'P' and $edtrow["tili"] == $trow["tili"]) {
        $trow["tili"] = "";
      }
      else {
        if ($eka != 0) {
          if (!isset($worksheet)) echo "<td></td><td></td><td></td><td></td><td></td><td></td><td valign='top' align = 'right' nowrap>".sprintf("%.2f", $summa)."</td><td align='right'>*</td>";

          if (mb_strlen(trim($tili)) > 0) {
            if (!isset($worksheet)) echo "<td></td>";
          }
          if (!isset($worksheet)) echo "</tr><tr class='aktiivi'>";

          $summa = 0;
        }
        else {
          $eka = 1;
        }

        $edtrow["tapvm"] = $trow["tapvm"];
        $edtrow["tili"] = $trow["tili"];
      }

      //jos tili on valittu lasketaan sen alkusaldo
      if (mb_strlen(trim($tili)) > 0 and $edtili != $trow["xtilino"] and isset($tilikaudetrow)) {
        $query = "SELECT sum(summa) saldo
                  FROM tiliointi
                  WHERE tiliointi.yhtio  = '$kukarow[yhtio]'
                  and tiliointi.korjattu = ''
                  and tiliointi.tapvm    >= '$tilikaudetrow[tilikausi_alku]'
                  and tiliointi.tilino   = '$trow[xtilino]'
                  $lisa2";
        $alkres = pupe_query($query);
        $alkurow = mysqli_fetch_array($alkres);
        $kumsumma = $alkurow["saldo"];

        // tilin alkusaldo
        if (!isset($worksheet)) {
          echo "<td valign='top' colspan='8'></td>";
          echo "<td valign='top' align='right' nowrap>".sprintf("%.2f", $kumsumma)."</td></tr>";
          echo "<tr class='aktiivi'>";
        }

        if (isset($worksheet)) {
          $worksheet->write($excelrivi, 8, $kumsumma);
          $excelrivi++;
        }
      }

      if ($tee == 'P') {
        if (!isset($worksheet)) {
          echo "<td valign='top'>".tv1dateconv($trow["tapvm"])."</td>";
          echo "<td valign='top'>$trow[tili]</td>";
        }

        if (isset($worksheet)) $worksheet->write($excelrivi, 0, $tapvm_clean);
        if (isset($worksheet)) $worksheet->write($excelrivi, 1, $tili_clean);

      }
      else {
        if (!isset($worksheet)) {
          echo "<td valign='top'>$trow[tili]</td>";
          echo "<td valign='top'>".tv1dateconv($trow["tapvm"])."</td>";
        }

        if (isset($worksheet)) $worksheet->write($excelrivi, 0, $tili_clean);
        if (isset($worksheet)) $worksheet->write($excelrivi, 1, $tapvm_clean);
      }

      if (!isset($worksheet)) {
        echo "<td valign='top'>$trow[kustp_koodi] $trow[kustp]</td>";
        echo "<td valign='top'>$trow[kohde_koodi] $trow[kohde]</td>";
        echo "<td valign='top'>$trow[projekti_koodi] $trow[projekti]</td>";
        echo "<td valign='top'>$trow[selite]</td>";
      }

      if (isset($worksheet)) $worksheet->write($excelrivi, 2, $trow["kustp_koodi"]." ".$trow["kustp"]);
      if (isset($worksheet)) $worksheet->write($excelrivi, 3, $trow["kohde_koodi"]." ".$trow["kohde"]);
      if (isset($worksheet)) $worksheet->write($excelrivi, 4, $trow["projekti_koodi"]." ".$trow["projekti"]);
      if (isset($worksheet)) $worksheet->write($excelrivi, 5, trim(strip_tags($trow["selite"])));

      if ($trow["summa"] < 0) {
        $class = "error";
      }
      else {
        $class = "ok";
      }

      if (!isset($worksheet)) {
        echo "<td valign='top' align='right'>";
        if ($mutositelink) echo "<a name='$trow[rivitunnus]' href = 'muutosite.php?tee=E&tunnus=$trow[ltunnus]&lopetus=$lopetus/SPLIT/$PHP_SELF////toim=$toim//tee=$tee//alvv=$alvv//alvk=$alvk//alvp=$alvp//llvv=$llvv//llvk=$llvk//llvp=$llvp//tkausi=$tkausi//tili=$tili//tili2=$tili2//asia_ytunnus=$asia_ytunnus//asiakasid=$asiakasid//toim_ytunnus=$toim_ytunnus//toimittajaid=$toimittajaid//alv=$alv//kasin=$kasin//excel=$excel//maarajaus=$maarajaus".str_replace("&", "//", $ulisa)."///$trow[rivitunnus]'>";
        echo "<font class='$class'>".sprintf("%.2f", $trow["summa"])."</font>";
        if ($mutositelink) echo "</a>";
        echo "</td>";
      }

      if (isset($worksheet)) $worksheet->writeNumber($excelrivi, 6, $trow["summa"]);

      if ($trow["summa"] < 0) $summamiinus += $trow["summa"];
      else $summaplus += $trow["summa"];

      $summa += $trow["summa"];

      if (!isset($worksheet)) echo "<td valign='top'>$trow[vero]</td>";

      if (isset($worksheet)) $worksheet->writeNumber($excelrivi, 7, $trow["vero"]);

      if (mb_strlen(trim($tili)) > 0) {
        //lasketaan ja echotaan kumulatiivinen summa
        $kumsumma += $trow["summa"];

        if (!isset($worksheet)) echo "<td align='right' valign='top'>".sprintf("%.2f", $kumsumma)."</td>";

        if (isset($worksheet)) $worksheet->writeNumber($excelrivi, 8, $kumsumma);
      }

      if ($trow["valkoodi"] == $yhtiorow["valkoodi"]) {
        $trow["valkoodi"] = "";
        $trow["summa_valuutassa"] = "";
      }
      elseif ($trow["summa_valuutassa"] == 0) {
        $trow["summa_valuutassa"] = "";
      }

      if (!isset($worksheet)) {
        echo "<td valign='top' align='right' class='back' nowrap>$trow[summa_valuutassa]</td>";
        echo "<td valign='top' align='right' class='back' nowrap>$trow[valkoodi]</td>";


        if ($trow["tila"] != "X" and $trow["xtilino"] == $yhtiorow['ostovelat'] or $trow["xtilino"] == $yhtiorow['konserniostovelat']) {

          $virhe = "";

	  if ($tapvm_clean != $trow["laskutapvm"] and $tapvm_clean != $trow["laskumapvm"]) {
            // MUOKKAUS: lisatty paivamaarat:
            $virhe  .= t("Ostovelkatiliöinnin päivämäärä virheellinen")."!<br>(lasku.tunnus: " . $trow["laskutunnus"] . ", tiliointi.tunnus: " . $trow["rivitunnus"] . ", tapvm: " . $tapvm_clean . ", tapvm: " .  $trow["laskutapvm"] . ", mapvm: " . $trow["laskumapvm"] . ")";
          }

          if ($tapvm_clean == $trow["laskumapvm"] and (($trow["valkoodi"] == "" and $trow["summa"] != $trow["laskusumma"]) or ($trow["valkoodi"] != "" and $trow["summa_valuutassa"] != $trow["laskusumma"]))) {
            // MUOKKAUS: lisatty lasku.tunnus ja summat:
            $virhe  .= t("Ostovelkatiliöinnin summa virheellinen")."!<br>(lasku.tunnus: " . $trow["laskutunnus"] . ", tiliointi.tunnus: " . $trow["rivitunnus"] . ", tiliointi.summa: " . $trow["summa"] . ", lasku.summa: " . $trow["laskusumma"] . ", tiliointi.summa_valuutassa: " . $trow["summa_valuutassa"] . ")";
          }

          if ($tapvm_clean == $trow["laskutapvm"] and (($trow["valkoodi"] == "" and $trow["summa"] != ($trow["laskusumma"]*-1)) or ($trow["valkoodi"] != "" and $trow["summa_valuutassa"] != ($trow["laskusumma"]*-1)))) {
            // MUOKKAUS: lisatty lasku.tunnus ja summat:
            $virhe  .= t("Ostovelkatiliöinnin summa virheellinen")."!<br>(lasku.tunnus: " . $trow["laskutunnus"] . ", tiliointi.tunnus: " . $trow["rivitunnus"] . ", tiliointi.summa: " . $trow["summa"] . ", lasku.summa: " . $trow["laskusumma"] . ", tiliointi.summa_valuutassa: " . $trow["summa_valuutassa"] . ")";
          }

          if ($virhe != "") echo "<td class='spec'><font class='error'>$virhe</font></td>";
        }

        if ($trow["alv_taso"] == "fi305" or $trow["alv_taso"] == "fi307") {
          // Onko tosite liitetty saapumiseen
          $query = "SELECT nimi, laskunro, mapvm from lasku where yhtio='$kukarow[yhtio]' and tila='K' and vanhatunnus='$trow[ltunnus]'";
          $keikres = pupe_query($query);

          if (mysqli_num_rows($keikres) > 0) {
            $keikrow = mysqli_fetch_assoc($keikres);


            $query = "SELECT nimi, laskunro, mapvm from lasku where yhtio='$kukarow[yhtio]' and tila='K' and laskunro = '$keikrow[laskunro]'and vanhatunnus='0'";
            $keikres = pupe_query($query);
            $keikrow2 = mysqli_fetch_assoc($keikres);

            echo "<td class='spec' nowrap>".t("Saapuminen").": $keikrow[laskunro]<br>".t("Pvm").": ".tv1dateconv($keikrow2["mapvm"])."</td>";
          }
        }

        echo "</tr>";
      }

      if (isset($worksheet)) $worksheet->write($excelrivi, 9, $trow["summa_valuutassa"]);
      if (isset($worksheet)) $worksheet->write($excelrivi, 10, $trow["valkoodi"]);
      if (isset($worksheet)) $excelrivi++;

      if ($trow['valkoodi'] != '' and $trow['summa_valuutassa'] != '') {
        $summa_valuutassa[$trow['valkoodi']] += $trow['summa_valuutassa'];
      }

      $edtili = $trow["xtilino"];
    }

    if (!isset($worksheet)) {
      echo "<tr class='aktiivi'><td></td><td></td><td></td><td></td><td></td><td></td><td align = 'right' nowrap>".sprintf("%.2f", $summa)."</td><td align='right'>*</td>";

      if (mb_strlen(trim($tili)) > 0) {
        echo "<td></td>";
      }

      echo "</tr>";

      echo "<tr class='aktiivi'><td></td><td></td><td></td><td></td><td></td><td align = 'right'>".t("Negatiiviset yhteensä")."</td>";
      echo "<td align = 'right' nowrap>".sprintf("%.2f", $summamiinus)."</td><td align='right'>*</td>";

      if (mb_strlen(trim($tili)) > 0) {
        echo "<td></td>";
      }

      echo "</tr>";

      echo "<tr class='aktiivi'><td></td><td></td><td></td><td></td><td></td><td align = 'right'>".t("Positiiviset yhteensä")."</td>";
      echo "<td align = 'right' nowrap>".sprintf("%.2f", $summaplus)."</td><td align='right'>*</td>";

      if (mb_strlen(trim($tili)) > 0) {
        echo "<td></td>";
      }

      echo "</tr>";

      if (count($summa_valuutassa) > 0) {
        foreach ($summa_valuutassa as $valuutta => $summ) {
          echo "<tr class='aktiivi'><td></td><td></td><td></td><td></td><td></td><td align = 'right'>".t("Yhteensä")." $valuutta</td>";
          echo "<td align = 'right' nowrap>".sprintf("%.2f", $summ)."</td><td align='right'>*</td>";

          if (mb_strlen(trim($tili)) > 0) {
            echo "<td></td>";
          }
          echo "</tr>";
        }
      }

      $summa = $summaplus + $summamiinus;
      echo "<tr class='aktiivi'><td></td><td></td><td></td><td></td><td></td><td align = 'right'>".t("Yhteensä")."</td>";
      echo "<td align = 'right' nowrap>".sprintf("%.2f", $summa)."</td><td align='right'>*</td>";

      if (mb_strlen(trim($tili)) > 0) {
        echo "<td></td>";
      }

      echo "</tr>";
      echo "</table><br><br>";
    }
  }
  else {
    echo t("Ei tapahtumia valitulla kaudella")."!";
  }
}
