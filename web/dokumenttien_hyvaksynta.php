<?php

$pupe_DataTables = 'dokkarit';

require "inc/parametrit.inc";

echo "<font class='head'>".t('Hyväksyttävät dokumenttisi')."</font><hr><br>";

enable_ajax();

if (!isset($tee))            $tee = "";
if (!isset($tila))           $tila = "";
if (!isset($tunnus))         $tunnus = "";
if (!isset($nayta))          $nayta = "";
if (!isset($iframe))         $iframe = "";
if (!isset($iframe_id))      $iframe_id = "";

$dokkarityypit = "";

// Mitä dokkarityyppejä käyttäjälle saa näyttää
$query = "SELECT group_concat(hd.tunnus) tyypit
          FROM hyvaksyttavat_dokumenttityypit_kayttajat hdk
          JOIN hyvaksyttavat_dokumenttityypit hd ON (hdk.yhtio=hd.yhtio
            and hdk.doku_tyyppi_tunnus=hd.tunnus)
          WHERE hdk.yhtio = '$kukarow[yhtio]'
          AND hdk.kuka = '$kukarow[kuka]'";
$hvresult = pupe_query($query);
$hvrow = mysqli_fetch_assoc($hvresult);

if (empty($hvrow['tyypit'])) {
  echo "<font class='error'>".t("VIRHE: Et kuulu mihinkään dokumenttityyppiryhmään!")."</font>";
  exit;
}
else {
  $dokkarityypit = $hvrow['tyypit'];
}

# Halutaan nähdä hyvaksyttavat_dokumentin kuva oikealla puolella joten tehdään table
echo "<table><tr><td class='back ptop'>";

$onko_eka_hyvaksyja = FALSE;

if ((int) $tunnus != 0) {
  $tunnus = sanitize_string($tunnus);

  $query = "SELECT hd.hyvak1, hd.h1time, hd.hyvak2, hd.h2time
            FROM hyvaksyttavat_dokumentit hd
            JOIN hyvaksyttavat_dokumenttityypit hdt ON (hd.yhtio=hdt.yhtio and hd.tiedostotyyppi=hdt.tunnus and hdt.tunnus in ({$dokkarityypit}))
            WHERE hd.yhtio = '$kukarow[yhtio]'
            AND hd.tunnus  = '$tunnus'";
  $check_res = pupe_query($query);

  if (mysqli_num_rows($check_res) == 1) {
    $check_row = mysqli_fetch_assoc($check_res);

    if ($check_row['hyvak1'] == $kukarow['kuka'] and $check_row["h1time"] == "0000-00-00 00:00:00") {
      // jos ensimmäinen hyväksyjä on tää käyttäjä ja ei olla vielä hyväksytty
      $onko_eka_hyvaksyja = TRUE;
      $eka_hyvaksyja = $check_row['hyvak1'];
    }
  }
}

// Poistamme dokumentin
if ($tee == 'D' and $oikeurow['paivitys'] == '1') {
    $hyvaklisa = "hd.hyvaksyja_nyt = '$kukarow[kuka]'";

    $query = "SELECT hd.*
              FROM hyvaksyttavat_dokumentit hd
              JOIN hyvaksyttavat_dokumenttityypit hdt ON (hd.yhtio=hdt.yhtio and hd.tiedostotyyppi=hdt.tunnus and hdt.tunnus in ({$dokkarityypit}))
              WHERE $hyvaklisa
              and hd.yhtio  = '$kukarow[yhtio]'
              and hd.tunnus = '$tunnus'";
    $result = pupe_query($query);

  if (mysqli_num_rows($result) != 1) {
    echo "<font class = 'error'>".t('Dokumentti kateissa') . "$tunnus</font>";
    require "inc/footer.inc";
    exit;
  }

  $trow = mysqli_fetch_assoc($result);

  $komm = "(" . $kukarow['nimi'] . "@" . date('Y-m-d') .") ".t("Poisti dokumentin")."<br>" . $trow['selite'];

  // Merkataan dokumentti poistetuksi
  $query = "UPDATE hyvaksyttavat_dokumentit SET
            tila         = 'D',
            kommentit    = '$komm'
            WHERE tunnus = '$tunnus'
            and yhtio    = '$kukarow[yhtio]'";
  pupe_query($query);

  echo "<font class='error'>".sprintf(t('Poistit %s:n dokumentin tunnuksella %d.'), $trow['nimi'], $tunnus)."</font><br>";

  $tunnus = '';
  $tee = '';
}

if ($tee == "palauta") {

  $query = "SELECT hd.*
            FROM hyvaksyttavat_dokumentit hd
            JOIN hyvaksyttavat_dokumenttityypit hdt ON (hd.yhtio=hdt.yhtio and hd.tiedostotyyppi=hdt.tunnus and hdt.tunnus in ({$dokkarityypit}))
            WHERE hd.hyvaksyja_nyt = '$kukarow[kuka]'
            AND hd.yhtio           = '$kukarow[yhtio]'
            AND hd.tunnus          = '$tunnus'";
  $result = pupe_query($query);

  if (mysqli_num_rows($result) != 1) {
    echo "<font class = 'error'>".t('Dokumentti kateissa') . "$tunnus</font>";
    require "inc/footer.inc";
    exit;
  }

  $lrow = mysqli_fetch_assoc($result);

  if (trim($viesti) == "" or (int) $hyvaksyja == 0) {

    if ($formilta == "true") {
      if ((int) $hyvaksyja == 0) echo "<font class='error'>".t("Valitse kenelle dokumentti palautetaan.")."</font><br>";
      if (trim($viesti) == "") echo "<font class='error'>".t("Anna palautukseen syy.")."</font><br>";
    }

    $hyvaksyjat = "<table>
                  <tr>
                    <th>".t("Kenelle dokumentti palautetaan")."</th>
                  </tr>";

    $hyvaksyneet_hyvaksyjat = array();

    // Valitse kenelle palautetaan
    for ($i=1; $i<5; $i++) {
      $haika  = "h".$i."time";
      $haiku  = "hyvak".$i;

      if ($lrow[$haika] != "0000-00-00 00:00:00") {

        $query = "SELECT nimi
                  FROM kuka
                  WHERE yhtio = '$kukarow[yhtio]'
                  and kuka    = '{$lrow[$haiku]}'";
        $result = pupe_query($query);
        $krow = mysqli_fetch_assoc($result);

        $hyvaksyneet_hyvaksyjat[] = $i;

        if ($hyvaksyja == $i) {
          $sel = "checked";
        }
        else {
          $sel = "";
        }

        $hyvaksyjat .= "<tr><td><input type='radio' name='hyvaksyja' value='$i' $sel> $i. $krow[nimi]</td></tr>";
      }
    }

    $hyvaksyjat .= "</table><br>";

    // Pakataan array stringiin, että saadaan se nätisti läpi
    $hyvaksyneet_hyvaksyjat = implode(" ", $hyvaksyneet_hyvaksyjat);

    echo "  <form method='post'>
        <input type='hidden' name='lopetus' value='$lopetus'>
        <input type='hidden' name='tee' value='$tee'>
        <input type='hidden' name='formilta' value='true'>
        <input type='hidden' name='tunnus' value='$tunnus'>
        <input type='hidden' name='hyvaksyneet_hyvaksyjat' value='$hyvaksyneet_hyvaksyjat'>
        <br>
        $hyvaksyjat
        <br>
        <table>
          <tr>
            <th>".t("Anna palautuksen syy")."</th>
          </tr>
          <tr>
            <td><input type='text' name = 'viesti' value='$viesti' size = '50'></td>
          </tr>
        </table>
        <input type = 'submit' value = '".t("Palauta")."'>
        </form>
        <br><br>
        <form method='post'>
        <input type='hidden' name='tunnus' value='$tunnus'>
        <input type = 'submit' value = '".t("Palaa dokumenttien hyväksyntään")."'>
        </form>";

    require "inc/footer.inc";
    exit;
  }
  else {

    //  Palautetaan takaisin edelliselle hyväksyjälle
    if ((int) $hyvaksyja > 0) {
      $upd = "h{$hyvaksyja}time = '0000-00-00 00:00:00', hyvaksyja_nyt = '".$lrow["hyvak{$hyvaksyja}"]."'";
      $kukahyvak = $lrow["hyvak{$hyvaksyja}"];

      // Hajotetaan stringi takaisin arrayksi käsittelyä varten
      $hyvaksyneet_hyvaksyjat = explode (" ", $hyvaksyneet_hyvaksyjat);

      // Katsotaan ketkä ovat nykyisen hyväksyjän ja valitun hyväksyjän välissä hyväksyneet ja nollataan myös heidän hyväksymisensä, 
      // jotta saadaan dokumentti käymään hyväksymisketju tavalliseen tapaan läpi
      for ($i = 0; $i < count($hyvaksyneet_hyvaksyjat); $i = $i + 1) {
        if ((int)$hyvaksyneet_hyvaksyjat[$i] > $hyvaksyja) {
          $upd = $upd.", h{$hyvaksyneet_hyvaksyjat[$i]}time = '0000-00-00 00:00:00'";
        }
      }
    }
    else die("Hyväksyjä ei kelpaa");

    $query = "SELECT *
              FROM kuka
              WHERE yhtio = '$kukarow[yhtio]'
              and kuka    = '$kukahyvak'";
    $result = pupe_query($query);
    $krow = mysqli_fetch_assoc($result);

    $komm = "(" . $kukarow['nimi'] . "@" . date('Y-m-d') .") ".t("Palautti dokumentin hyväksyjälle").": $krow[nimi], $viesti<br>";

    $query = "UPDATE hyvaksyttavat_dokumentit
              SET $upd,
              kommentit = trim(concat('$komm', kommentit))
              WHERE tunnus = '$tunnus'
              AND yhtio    = '$kukarow[yhtio]'";
    $result = pupe_query($query);

    echo "<font class='message'>".t('Palauttettiin dokumentti käyttäjälle')." $krow[nimi]</font><br>";

    //  Lähetetään maili virheen merkiksi!
    mail($krow["eposti"], mb_encode_mimeheader("Hyväksymäsi dokumentti palautettiin", "UTF-8", "Q"), t("Hyväksymäsi dokumentti toimittajalta %s, %s %s palautettiin korjattavaksi.\n\nSyy: %s \n\nPalauttaja:", $krow["kieli"], $lrow["nimi"], $lrow["summa"], $lrow["valkoodi"], $viesti)." $kukarow[nimi], $yhtiorow[nimi]", "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "UTF-8", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
    $tunnus = "";
    $tee = "";
  }
}

if ($tee == 'V' and $komm == '') {
  echo "<font class='error'>".t('Anna kommentti')."!</font><br><br>";
  $tee = '';
}

// Dokumenttia kommentoidaan
if ($tee == 'V') {
  $query = "SELECT hd.*
            FROM hyvaksyttavat_dokumentit hd
            JOIN hyvaksyttavat_dokumenttityypit hdt ON (hd.yhtio=hdt.yhtio and hd.tiedostotyyppi=hdt.tunnus and hdt.tunnus in ({$dokkarityypit}))
            WHERE hd.hyvaksyja_nyt = '$kukarow[kuka]'
            and hd.yhtio           = '$kukarow[yhtio]'
            and hd.tunnus          = '$tunnus'";
  $result = pupe_query($query);

  if (mysqli_num_rows($result) != 1) {
    echo "<font class = 'error'>".t('Dokumentti kateissa') . "$tunnus</font>";
    require "inc/footer.inc";
    exit;
  }

  $trow = mysqli_fetch_assoc($result);

  $komm = "(" . $kukarow['nimi'] . "@" . date('Y-m-d') .") " . trim($komm) . "<br>" . $trow['kommentit'];

  $query = "UPDATE hyvaksyttavat_dokumentit
            SET kommentit = '$komm'
            WHERE yhtio = '$kukarow[yhtio]'
            and tunnus = '$tunnus'";
  $result = pupe_query($query);

  $tee = '';
}

// Hyväksyntälistaa muutetaan
if ($tee == 'L') {
  $query = "SELECT hd.*
            FROM hyvaksyttavat_dokumentit hd
            JOIN hyvaksyttavat_dokumenttityypit hdt ON (hd.yhtio=hdt.yhtio and hd.tiedostotyyppi=hdt.tunnus and hdt.tunnus in ({$dokkarityypit}))
            WHERE hd.tunnus      = '$tunnus'
            AND hd.yhtio         = '$kukarow[yhtio]'
            AND hd.hyvaksyja_nyt = '$kukarow[kuka]'";
  $result = pupe_query($query);

  if (mysqli_num_rows($result) != 1) {
    echo "<font class = 'error'>".t('Dokumentti kateissa') . "$tunnus</font>";
    require "inc/footer.inc";
    exit;
  }

  $dokumenttirow = mysqli_fetch_assoc($result);

  if ($eka_hyvaksyja != $dokumenttirow['hyvaksyja_nyt'] or $dokumenttirow['hyvaksynnanmuutos'] == '') {
    echo "<font class = 'error'>".t('Dokumentti on väärässä tilassa')."</font>";
    require "inc/footer.inc";
    exit;
  }

  // Katsotaan monennellako hyväksyjällä dokumentti on hyväksyttävänä
  // ja päivitetään hyvaksyja_nyt sen mukaan
  // Käytännössä dokumentti voi olla ekalla tai tokalla hyväksyjällä,
  // joten tämän takia ei muita tarvitse tarkistaa.
  if ($dokumenttirow['h1time'] == '0000-00-00 00:00:00') {
    $_hyvaksyja_nyt = $dokumenttirow["hyvak1"];
  }
  elseif ($dokumenttirow['h2time'] == '0000-00-00 00:00:00') {
    $_hyvaksyja_nyt = $hyvak[2];
  }

  $query = "UPDATE hyvaksyttavat_dokumentit SET
            hyvak2        = '$hyvak[2]',
            hyvak3        = '$hyvak[3]',
            hyvak4        = '$hyvak[4]',
            hyvak5        = '$hyvak[5]',
            hyvaksyja_nyt = '$_hyvaksyja_nyt'
            WHERE yhtio   = '$kukarow[yhtio]'
            AND tunnus    = '$tunnus'";
  $result = pupe_query($query);

  $tee = '';

  echo "<font class='message'>" . t("Hyväksyntäjärjestys").": $dokumenttirow[hyvak1]";
  if ($hyvak[2] != '') echo " -&gt; $hyvak[2]";
  if ($hyvak[3] != '') echo " -&gt; $hyvak[3]";
  if ($hyvak[4] != '') echo " -&gt; $hyvak[4]";
  if ($hyvak[5] != '') echo " -&gt; $hyvak[5]";
  echo "</font><br><br>";
}

// Tarkistetaan samalla, että dokumentti löytyy.
if ($tee == "H") {
  $query = "SELECT hd.*
            FROM hyvaksyttavat_dokumentit hd
            JOIN hyvaksyttavat_dokumenttityypit hdt ON (hd.yhtio=hdt.yhtio and hd.tiedostotyyppi=hdt.tunnus and hdt.tunnus in ({$dokkarityypit}))
            WHERE hd.yhtio       = '{$kukarow["yhtio"]}'
            AND hd.tunnus        = '{$tunnus}'
            AND hd.hyvaksyja_nyt = '{$kukarow["kuka"]}'";
  $result = pupe_query($query);

  if (mysqli_num_rows($result) != 1) {
    echo "<font class = 'error'>" . t('Dokumentti kateissa') . "$tunnus</font>";
    require "inc/footer.inc";
    exit;
  }

  $dokumenttirow = mysqli_fetch_assoc($result);
}

if ($tee == 'H') {
  // Dokumentti merkitään hyväksytyksi, tehdään timestamp ja päivitetään hyvaksyja_nyt

  // Kuka hyväksyi??
  if ($dokumenttirow['hyvaksyja_nyt'] == $dokumenttirow['hyvak1'] and $dokumenttirow["h1time"]=="0000-00-00 00:00:00") {
    $kentta = "h1time";
    $dokumenttirow['h1time'] = "99";
  }
  elseif ($dokumenttirow['hyvaksyja_nyt'] == $dokumenttirow['hyvak2'] and $dokumenttirow["h2time"]=="0000-00-00 00:00:00") {
    $kentta = "h2time";
    $dokumenttirow['h2time'] = "99";
  }
  elseif ($dokumenttirow['hyvaksyja_nyt'] == $dokumenttirow['hyvak3'] and $dokumenttirow["h3time"]=="0000-00-00 00:00:00") {
    $kentta = "h3time";
    $dokumenttirow['h3time'] = "99";
  }
  elseif ($dokumenttirow['hyvaksyja_nyt'] == $dokumenttirow['hyvak4'] and $dokumenttirow["h4time"]=="0000-00-00 00:00:00") {
    $kentta = "h4time";
    $dokumenttirow['h4time'] = "99";
  }
  elseif ($dokumenttirow['hyvaksyja_nyt'] == $dokumenttirow['hyvak5'] and $dokumenttirow["h5time"]=="0000-00-00 00:00:00") {
    $kentta = "h5time";
    $dokumenttirow['h5time'] = "99";
  }

  // Kuka hyväksyy seuraavaksi??
  if ($dokumenttirow['h5time'] == '0000-00-00 00:00:00' and mb_strlen(trim($dokumenttirow['hyvak5'])) != 0) {
    $hyvaksyja_nyt = $dokumenttirow['hyvak5'];
  }
  if ($dokumenttirow['h4time'] == '0000-00-00 00:00:00' and mb_strlen(trim($dokumenttirow['hyvak4'])) != 0) {
    $hyvaksyja_nyt = $dokumenttirow['hyvak4'];
  }
  if ($dokumenttirow['h3time'] == '0000-00-00 00:00:00' and mb_strlen(trim($dokumenttirow['hyvak3'])) != 0) {
    $hyvaksyja_nyt = $dokumenttirow['hyvak3'];
  }
  if ($dokumenttirow['h2time'] == '0000-00-00 00:00:00' and mb_strlen(trim($dokumenttirow['hyvak2'])) != 0) {
    $hyvaksyja_nyt = $dokumenttirow['hyvak2'];
  }
  if ($dokumenttirow['h1time'] == '0000-00-00 00:00:00' and mb_strlen(trim($dokumenttirow['hyvak1'])) != 0) {
    $hyvaksyja_nyt = $dokumenttirow['hyvak1'];
  }

  if (mb_strlen($hyvaksyja_nyt) == 0) {
    $tila = 'M';
    $viesti = t("Dokumentti on hyväskytty!");
  }
  else {
    $tila = "H";
    $viesti = t("Seuraava hyväksyjä on")." '" .$hyvaksyja_nyt ."'";
  }

  $query = "UPDATE hyvaksyttavat_dokumentit SET
            $kentta = now(),
            hyvaksyja_nyt = '$hyvaksyja_nyt',
            tila          = '$tila'
            WHERE yhtio = '$kukarow[yhtio]'
            and tunnus  = '$tunnus'";
  $result = pupe_query($query);

  echo "<br><font class='message'>'$dokumenttirow[hyvaksyja_nyt]' ".t("hyväksyi dokumentin")." $viesti</font><br><br>";

  $tunnus = '';
  $tee = '';
}

if (mb_strlen($tunnus) != 0) {

  // Dokumentti on valittu ja sitä tiliöidään
  $query = "SELECT hd.*,
            concat_ws('@', hd.laatija, hd.luontiaika) kuka
            FROM hyvaksyttavat_dokumentit hd
            JOIN hyvaksyttavat_dokumenttityypit hdt ON (hd.yhtio=hdt.yhtio and hd.tiedostotyyppi=hdt.tunnus and hdt.tunnus in ({$dokkarityypit}))
            WHERE hd.tunnus = '$tunnus'
            and hd.yhtio    = '$kukarow[yhtio]'";
  $result = pupe_query($query);

  if (mysqli_num_rows($result) == 0) {
    echo "<b>".t("Dokumentti katosi")."</b><br>";
    require "inc/footer.inc";
    exit;
  }

  $dokumenttirow = mysqli_fetch_assoc($result);

  echo "<table>";

  echo "<tr>";
  echo "<th>".t("Laatija/Laadittu")."</th>";
  echo "<th>".t("Kuvaus")."</th>";
  echo "</tr>";

  echo "<tr>";
  echo "<td>{$dokumenttirow['kuka']}</td>";
  echo "<td>{$dokumenttirow['nimi']}</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th colspan='2'>".t("Kuvaus")."</th>";
  echo "</tr>";

  echo "<tr>";
  echo "<td colspan='2'>{$dokumenttirow['kuvaus']}</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th colspan='2'>".t("Dokumentti")."</th>";
  echo "</tr>";

  $query = "SELECT tunnus, filename, selite
            from liitetiedostot
            where liitostunnus = '{$dokumenttirow['tunnus']}'
            and liitos = 'hyvaksyttavat_dokumentit'
            and yhtio = '{$kukarow['yhtio']}'
            ORDER BY tunnus";
  $res = pupe_query($query);

  while ($row = mysqli_fetch_assoc($res)) {
    echo "<tr>";
    echo "<td colspan='2'>";
    echo "<div id='div_$row[tunnus]' class='popup'>$row[filename]<br>$row[selite]</div>";
    echo js_openUrlNewWindow("{$palvelin2}view.php?id=$row[tunnus]", t('Liite').": $row[filename]", "class='tooltip' id='$row[tunnus]'")."&nbsp;&nbsp;";
    echo "</td>";
    echo "</tr>";
  }

  echo "</table>";
  echo "<br><table>";

  // Mahdollisuus antaa kommentti
  echo "  <form name='kommentti' method='post'>
      <input type='hidden' name='lopetus' value='$lopetus'>
      <input type='hidden' name='tee' value='V'>
      <input type='hidden' name = 'nayta' value='$nayta'>
      <input type='hidden' name='tunnus' value = '$tunnus'>
      <input type='hidden' name='iframe' value = '$iframe'>
      <input type='hidden' name='iframe_id' value = '$iframe_id'>";

  echo "  <tr>
      <th colspan='2'>".t("Lisää kommentti")."</th>
      </tr>";

  echo "  <tr>
      <td><input type='text' name='komm' value='' size='50'></td>
      <td><input type='submit' value='".t("Lisää kommentti")."'></td>
      </tr>";

  echo "</form>";

  echo "<tr>";
  echo "<th colspan='2'>".t("Kommentit")."</th>";
  echo "</tr>";

  echo "<tr>";
  echo "<td colspan='2'>{$dokumenttirow['kommentit']}</td>";
  echo "</tr>";

  echo "</table>";

  echo "<br><table>";
  echo "<tr>";

  // Jos dokumenttia hyvaksyy ensimmäinen henkilö ja dokumentilla on annettu mahdollisuus hyvksyntälistan muutokseen näytetään se!";
  if ($onko_eka_hyvaksyja and $dokumenttirow['hyvaksynnanmuutos'] != '') {

    echo "<td class='back' valign='top'><table>";

    $hyvak[1] = $dokumenttirow['hyvak1'];
    $hyvak[2] = $dokumenttirow['hyvak2'];
    $hyvak[3] = $dokumenttirow['hyvak3'];
    $hyvak[4] = $dokumenttirow['hyvak4'];
    $hyvak[5] = $dokumenttirow['hyvak5'];

    echo "<form name='uusi' method='post'>
        <input type='hidden' name='lopetus' value='$lopetus'>
        <input type='hidden' name='tee' value='L'>
        <input type='hidden' name='nayta' value='$nayta'>
        <input type='hidden' name='tunnus' value='$tunnus'>
        <input type='hidden' name='iframe' value = '$iframe'>
        <input type='hidden' name='iframe_id' value = '$iframe_id'>";

    echo "<tr><th colspan='2'>".t("Hyväksyjät")."</th></tr>";

    $query = "SELECT kuka, nimi
              FROM kuka
              WHERE yhtio   = '$kukarow[yhtio]'
              and hyvaksyja = 'o'
              and kuka      = '{$dokumenttirow['hyvak1']}'
              ORDER BY nimi";
    $vresult = pupe_query($query);
    $vrow = mysqli_fetch_assoc($vresult);

    echo "<tr><td>1. $vrow[nimi]</td><td>";

    $query = "SELECT DISTINCT kuka.kuka, kuka.nimi
              FROM kuka
              JOIN oikeu ON oikeu.yhtio = kuka.yhtio and oikeu.kuka = kuka.kuka and oikeu.nimi like '%dokumenttien_hyvaksynta.php'
              WHERE kuka.yhtio    = '$kukarow[yhtio]'
              AND kuka.aktiivinen = 1
              AND kuka.extranet   = ''
              ORDER BY kuka.nimi";
    $vresult = pupe_query($query);

    $ulos = '';

    echo "<tr><td>";

    // Täytetään 4 hyväksyntäkenttää (ensimmäinen on jo käytössä)
    for ($i = 2; $i < 6; $i++) {

      while ($vrow = mysqli_fetch_assoc($vresult)) {
        $sel = "";

        if ($hyvak[$i] == $vrow['kuka']) {
          $sel = "selected";
        }

        $ulos .= "<option value ='$vrow[kuka]' $sel>$vrow[nimi]</option>";
      }

      // Käydään sama data läpi uudestaan
      mysqli_data_seek($vresult, 0);

      echo "$i. <select name='hyvak[$i]'>
            <option value=''>".t("Ei kukaan")."</option>
            $ulos
            </select><br>";

      $ulos = "";
    }

    echo "</td>
        <td valign='top'><input type='submit' value='".t("Muuta lista")."'></td>
        </tr></form>";

    echo "</table></td><td width='30px' class='back'></td>";
  }
  else {

    echo "<td class='back' valign='top'><table>";
    echo "<tr><th>".t("Hyväksyjä")."</th><th>".t("Hyväksytty")."</th><th></th></tr>";

    for ($i = 1; $i < 6; $i++) {
      $hyind = "hyvak".$i;
      $htind = "h".$i."time";

      if ($dokumenttirow[$hyind] != '') {
        $query = "SELECT kuka, nimi
                  FROM kuka
                  WHERE yhtio = '$kukarow[yhtio]'
                  and kuka    = '$dokumenttirow[$hyind]'
                  ORDER BY nimi";
        $vresult = pupe_query($query);
        $vrow = mysqli_fetch_assoc($vresult);

        echo "<tr><td>$i. $vrow[nimi]</td><td>";

        if ($dokumenttirow[$htind] != '0000-00-00 00:00:00') {
          echo tv1dateconv($dokumenttirow[$htind], "P");
        }

        echo "</td><td>".t("Lukittu")."</td></tr>";
      }
    }

    echo "</table>";
    echo "<br><br><form method='post'>
        <input type='hidden' name = 'tunnus' value='$tunnus'>
        <input type='hidden' name = 'tee' value='H'>
        <input type='submit' value='".t("Hyväksy dokumentti")."'>
        </form>";

    if ($dokumenttirow["h1time"] != "0000-00-00 00:00:00") {
      echo "<form method='post'>
            <input type='hidden' name='tee' value='palauta'>
            <input type='hidden' name='tunnus' value='$tunnus'>
            <input type='submit' value='".t("Palauta dokumentti edelliselle hyväksyjälle")."'>
            </form><br>";
    }

    echo "</td><td width='30px' class='back'></td>";
  }

  echo "</tr></table>";

  // Dokumentin kuva oikealle puolelle
  echo "</td><td class='back ptop'>";

  echo "<table><tr>";

  //  Onko kuva tietokannassa?
  $query = "SELECT tunnus, filename, selite
            from liitetiedostot
            where liitostunnus = '{$dokumenttirow['tunnus']}'
            and liitos = 'hyvaksyttavat_dokumentit'
            and yhtio = '{$kukarow['yhtio']}'
            ORDER BY tunnus";
  $res = pupe_query($query);

  if (mysqli_num_rows($res) > 0) {
    echo "<form method='post'>
        <input type='hidden' name='lopetus' value='$lopetus'>
        <input type='hidden' name = 'tunnus' value='$tunnus'>
        <input type='hidden' name = 'iframe' value='yes'>
        <input type='hidden' name = 'nayta' value='$nayta'>
        <input type='hidden' name = 'tee' value = ''>
        <th>".t("Avaa dokumentti tähän ikkunaan")."</th>
        <td>";

    if (mysqli_num_rows($res) == 1) {
      $liiterow = mysqli_fetch_assoc($res);
      echo "<input type='hidden' name = 'iframe_id' value='{$liiterow['tunnus']}'>
          <input type='submit' value='".t("Avaa")."'>";
    }
    else {
      echo "<select name='iframe_id' onchange='submit();'>";
      echo "<option value=''>".t("Valitse dokumentti")."</option>";

      $liicoint = 1;
      while ($liiterow = mysqli_fetch_assoc($res)) {
        if ($iframe_id == $liiterow['tunnus']) $sel = "selected";
        else $sel = "";

        echo "<option value='{$liiterow['tunnus']}' $sel>".t("Liite")." $liicoint</option>";

        $liicoint++;
      }
      echo "</select>";
    }

    echo "</td></form>";
  }

  if ($iframe == 'yes') {
    echo "<td>
        <form method='post'>
          <input type='hidden' name = 'tunnus' value='$tunnus'>
        <input type='hidden' name='lopetus' value='$lopetus'>
          <input type='hidden' name = 'nayta' value='$nayta'>
          <input type='submit' value='".t("Sulje dokumentti")."'>
        </form></td>";
  }

  echo "</tr></table>";

  if ($iframe == 'yes' and $iframe_id != '') {
    echo "<iframe src='view.php?id=$iframe_id' name='alaikkuna' width='800px' height='1200px' align='bottom' scrolling='auto'></iframe>";
  }
}
else {
  // Tällä ollaan, jos olemme vasta valitsemassa dokumenttia
  $query = "SELECT hd.*, kuka.nimi laatija, hdt.tyyppi
            FROM hyvaksyttavat_dokumentit hd
            JOIN hyvaksyttavat_dokumenttityypit hdt ON (hd.yhtio=hdt.yhtio and hd.tiedostotyyppi=hdt.tunnus)
            LEFT JOIN kuka ON (kuka.yhtio = hd.yhtio and kuka.kuka = hd.laatija)
            WHERE hd.hyvaksyja_nyt = '$kukarow[kuka]'
            and hd.yhtio = '$kukarow[yhtio]'
            and hd.tila = 'H'
            ORDER BY hd.tunnus";
  $result = pupe_query($query);

  if (mysqli_num_rows($result) == 0) {
    echo "<b>".t("Sinulla ei ole hyväksymättömiä dokumentteja")."</b><br>";
    require "inc/footer.inc";
    exit;
  }

  $sarakkeet = 7;

  if ($oikeurow['paivitys'] == '1') {
    $sarakkeet++;
  }

  pupe_DataTables(array(array($pupe_DataTables, $sarakkeet, $sarakkeet)));

  echo "<table class='display dataTable' id='$pupe_DataTables'>";
  echo "<thead>";
  echo "<tr>";
  echo "<th>".t("Tyyppi")."</th>";
  echo "<th>".t("Nimi")."</th>";
  echo "<th>".t("Kuvaus")."</th>";
  echo "<th>".t("Kommentit")."</th>";
  echo "<th>".t("Laatija")."</th>";
  echo "<th>".t("Tiedosto")."</th>";
  echo "</tr>";
  echo "</thead>";

  echo "<tbody>";

  while ($trow = mysqli_fetch_assoc($result)) {
    echo "<tr class='aktiivi'>";
    echo "<td valign='top'>{$trow['tyyppi']}</td>";
    echo "<td valign='top'>{$trow['nimi']}</td>";
    echo "<td valign='top'>{$trow['kuvaus']}</td>";
    echo "<td valign='top'>{$trow['kommentit']}</td>";
    echo "<td valign='top'>{$trow['laatija']}</td>";

    $query = "SELECT tunnus, filename, selite
              from liitetiedostot
              where liitostunnus = '{$trow['tunnus']}'
              and liitos = 'hyvaksyttavat_dokumentit'
              and yhtio = '{$kukarow['yhtio']}'
              ORDER BY tunnus";
    $res = pupe_query($query);

    echo "<td>";

    while ($row = mysqli_fetch_assoc($res)) {
      echo "<div id='div_$row[tunnus]' class='popup'>$row[filename]<br>$row[selite]</div>";
      echo js_openUrlNewWindow("{$palvelin2}view.php?id=$row[tunnus]", t('Liite').": $row[filename]", "class='tooltip' id='$row[tunnus]'")."<br>";
    }

    echo "</td>";

    echo "<td class='back' valign='top'>
        <form method='post'>
        <input type='hidden' name='tunnus' value='$trow[tunnus]'>
        <input type='hidden' name='lopetus' value='$lopetus'>
        <input type='submit' value='".t("Valitse")."'>
        </form>
      </td>";

    if ($oikeurow['paivitys'] == '1') {
      echo "<td class='back' valign='top'>
          <form method='post' onSubmit='return verify()'>
          <input type='hidden' name='tee' value='D'>
          <input type='hidden' name='nayta' value='$nayta'>
          <input type='hidden' name='tunnus' value = '$trow[tunnus]'>
          <input type='submit' value='".t("Hylkää / Poista")."'>
          </form>
        </td>";
    }

    echo "</tr>";
  }

  echo "</tbody>";
  echo "</table>";

  echo "  <SCRIPT LANGUAGE=JAVASCRIPT>
      function verify() {
        msg = '".t("Haluatko todella poistaa tämän dokumentin?")."';

        if (confirm(msg)) {
          return true;
        }
        else {
          skippaa_tama_submitti = true;
          return false;
        }
      }
      </SCRIPT>";
}

# Dokumentin kuva taulu loppuu
echo "</td></tr></table>";

require "inc/footer.inc";
