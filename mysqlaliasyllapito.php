<?php

require "inc/parametrit.inc";

echo "<font class='head'>".t("Mysqlalias-avainsanojen ylläpito")."</font><hr>";

if ($upd == 1) {
  list($xtaulu, $xalias_set) = explode("###", $taulu);
  if (empty($xalias_set)) $xalias_set = "Default";

  // Luodaan puskuri, jotta saadaan taulukot kuntoon
  $query = "SELECT *
            FROM $xtaulu
            WHERE tunnus = '0'";
  $result = pupe_query($query);

  for ($i=1; $i < mysqli_num_fields($result)-1; $i++) {

    // Tarkistetaan saako käyttäjä päivittää tätä kenttää
    $al_nimi = mysqli_field_name($result, $i);

    $query = "DELETE
              FROM avainsana
              WHERE yhtio      = '$kukarow[yhtio]'
              and laji         = 'MYSQLALIAS'
              and selite       = '$xtaulu.$al_nimi'
              and selitetark_2 = '$xalias_set'";
    $al_res = pupe_query($query);

    if ($mysqlaliasbox[$al_nimi] != "" or trim($mysqlalias[$al_nimi]) != "" or trim($oletusarvo[$al_nimi]) != "" or trim($ohjeteksti[$al_nimi]) != "") {

      $pakollisuus = "";
      $nakyvyys = "";

      if ($mysqlaliaspakollisuus[$al_nimi] != "") {
        $pakollisuus = "PAKOLLINEN";
      }

      if ($mysqlaliasbox[$al_nimi] != "") {
        $nakyvyys = "X";
      }

      $xotsikko = str_replace("<br>", "(BR)", trim($mysqlalias[$al_nimi]));

      $query = "INSERT INTO avainsana
                SET yhtio     = '$kukarow[yhtio]',
                laji         = 'MYSQLALIAS',
                nakyvyys     = '$nakyvyys',
                selite       = '$xtaulu.$al_nimi',
                selitetark   = '$xotsikko',
                selitetark_2 = '$xalias_set',
                selitetark_3 = '$pakollisuus',
                selitetark_4 = '{$oletusarvo[$al_nimi]}',
                selitetark_5 = '{$ohjeteksti[$al_nimi]}',
                laatija      = '{$kukarow['kuka']}',
                luontiaika   = now()";
      pupe_query($query);
    }
  }
}

if ($delete == 1) {
  list($xtaulu, $xalias_set) = explode("###", $taulu);
  if (empty($xalias_set)) $xalias_set = "Default";

  $query = "DELETE FROM avainsana
            WHERE yhtio      = '$kukarow[yhtio]'
            and laji         = 'MYSQLALIAS'
            and selite       like '$xtaulu.%'
            and selitetark_2 = '$xalias_set'";
  $al_res1 = pupe_query($query);

  echo "<br><br>".t("Mysqlaliakset poistettu")."!<br><br><br>";

  $taulu = "";
}

if ($kopsaataulu != "" and $uusisetti != "") {

  list($kopsaataulu, $alias_set) = explode("###", $kopsaataulu);
  if (empty($alias_set)) $alias_set = "Default";

  $query = "SELECT *
            FROM avainsana
            WHERE yhtio      = '$kukarow[yhtio]'
            and laji         = 'MYSQLALIAS'
            and selite       like '$kopsaataulu.%'
            and selitetark_2 = '$alias_set'";
  $al_res1 = pupe_query($query);

  $query = "SELECT *
            FROM avainsana
            WHERE yhtio      = '$kukarow[yhtio]'
            and laji         = 'MYSQLALIAS'
            and selite       like '$kopsaataulu.%'
            and selitetark_2 = '$uusisetti'";
  $al_res2 = pupe_query($query);

  if (mysqli_num_rows($al_res1) > 0 and mysqli_num_rows($al_res2) == 0) {
    while ($al_row = mysqli_fetch_array($al_res1)) {
      $query = "INSERT INTO avainsana
                SET yhtio    = '$kukarow[yhtio]',
                laji         = 'MYSQLALIAS',
                nakyvyys     = '$al_row[nakyvyys]',
                selite       = '$al_row[selite]',
                selitetark   = '$al_row[selitetark]',
                selitetark_2 = '$uusisetti',
                selitetark_3 = '$al_row[selitetark_3]',
                selitetark_4 = '$al_row[selitetark_4]',
                selitetark_5 = '$al_row[selitetark_5]',
                laatija      = '{$kukarow['kuka']}',
                luontiaika   = now()";
      $al_res3 = pupe_query($query);
    }
  }
}

// Nyt selataan
$query  = "SHOW FULL TABLES FROM `$dbkanta` WHERE Table_Type = 'BASE TABLE'";
$tabresult = pupe_query($query);

$sel[$taulu] = "SELECTED";

echo "<form method = 'post'><table>";
echo "<tr><th>".t("Muokkaa aliasryhmää").":</th><td>";
echo "<select name = 'taulu'>";

while ($tables = mysqli_fetch_array($tabresult)) {

  if (!empty($pupenext_yllapitonakymat[$tables[0]]) or file_exists("inc/".$tables[0].".inc")) {

    $query = "SELECT distinct selitetark_2
              FROM avainsana
              WHERE yhtio       = '$kukarow[yhtio]'
              and laji          = 'MYSQLALIAS'
              and selite        like '".$tables[0].".%'
              and selitetark_2 != 'Default'";
    $al_res = pupe_query($query);

    echo "<option value='$tables[0]' ".$sel[$tables[0]].">$tables[0]</option>";

    if (mysqli_num_rows($al_res) > 0) {
      while ($alrow = mysqli_fetch_array($al_res)) {
        echo "<option value='$tables[0]###$alrow[selitetark_2]' ".$sel[$tables[0]."###".$alrow["selitetark_2"]].">$tables[0] - $alrow[selitetark_2]</option>";
      }
    }
  }
}

echo "</select></td><td class='back'>";
echo "<input type='submit' value='".t("Valitse")."'>";
echo "</td></tr></table></form><br><br>";

if ($taulu == "") {

  echo "<form method = 'post'><table>";
  echo "<tr><th>".t("Kopioi aliasryhmä").":</th><td>";
  echo "<select name = 'kopsaataulu'>";

  mysqli_data_seek($tabresult, 0);

  while ($tables = mysqli_fetch_array($tabresult)) {
    if (!empty($pupenext_yllapitonakymat[$tables[0]]) or file_exists("inc/".$tables[0].".inc")) {

      $query = "SELECT distinct selitetark_2
                FROM avainsana
                WHERE yhtio       = '$kukarow[yhtio]'
                and laji          = 'MYSQLALIAS'
                and selite        like '".$tables[0].".%'
                and selitetark_2 != 'Default'";
      $al_res = pupe_query($query);

      echo "<option value='$tables[0]' ".$sel[$tables[0]].">$tables[0]</option>";

      if (mysqli_num_rows($al_res) > 0) {
        while ($alrow = mysqli_fetch_array($al_res)) {
          echo "<option value='$tables[0]###$alrow[selitetark_2]' ".$sel[$tables[0]."###".$alrow["selitetark_2"]].">$tables[0] - $alrow[selitetark_2]</option>";
        }
      }
    }
  }

  echo "</select></td>";

  echo "<th>".t("Uuden aliasryhmän nimi").":</th><td><input type='text' size='30' name='uusisetti'>";
  echo "</select></td><td class='back'>";
  echo "<input type='submit' value='".t("Valitse")."'>";
  echo "</td></tr></table></form><br><br>";
}

// Nyt näytetään vanha tai tehdään uusi(=tyhjä)
if ($taulu != "") {

  $cleantaulu = $taulu;

  echo "<form method = 'post'>";
  echo "<input type = 'hidden' name = 'taulu' value = '$cleantaulu'>";
  echo "<input type = 'hidden' name = 'upd' value = '1'>";

  list($taulu, $alias_set) = explode("###", $taulu);

  // Kokeillaan geneeristä
  $query = "SELECT *
            FROM $taulu
            WHERE tunnus = 0";
  $result = pupe_query($query);
  $trow = mysqli_fetch_array($result);

  echo "<table><tr><td class='back' valign='top'>";
  echo "<table>";
  echo "<tr>";
  echo "<th>Sarakkeen nimi</th>";
  echo "<th>Näkyvyys</th>";
  echo "<th>Pakollisuus</th>";
  echo "<th>Seliteteksti</th>";
  echo "<th>Oletusarvo</th>";
  echo "<th>Ohjeteksti</th>";
  echo "<th>Esimerkkejä</th>";
  echo "</tr>";

  for ($i=0; $i < mysqli_num_fields($result) - 1; $i++) {

    $nimi = "t[$i]";

    if (isset($t[$i])) {
      $trow[$i] = $t[$i];
    }

    if (strlen($trow[$i]) > 35) {
      $size = strlen($trow[$i])+2;
    }
    elseif (mysqli_field_len($result, $i)>10) {
      $size = '35';
    }
    elseif (mysqli_field_len($result, $i)<5) {
      $size = '5';
    }
    else {
      $size = '10';
    }

    $maxsize = mysqli_field_len($result, $i); // Jotta tätä voidaan muuttaa

    if (empty($pupenext_yllapitonakymat[$taulu])) {
      require "inc/$taulu"."rivi.inc";

      // Tässä näytetään kaikki kentät
      $tyyppi = 1;
    }
    else {
      $jatko = 1; // oletetaan normaali käsittely
      $tyyppi = 1;
      $ulos = "";
    }

    // Yhtiöta ei näytetä
    if (mysqli_field_name($result, $i) == "yhtio") {
      $tyyppi = 0;
    }

    // Näitä kenttiä ei ikinä saa päivittää käyttöliittymästä
    if (mysqli_field_name($result, $i) == "laatija" or
      mysqli_field_name($result, $i) == "muutospvm" or
      mysqli_field_name($result, $i) == "muuttaja" or
      mysqli_field_name($result, $i) == "luontiaika") {
      $tyyppi = 2;
    }

    //Haetaan tietokantasarakkeen nimialias
    $al_nimi = mysqli_field_name($result, $i);
    if (empty($alias_set)) $alias_set = "Default";
    $otsikko = "";
    $box    = "";
    $pakollisuusbox = "";
    $oletusarvo = "";
    $ohjeteksti = "";

    $query = "SELECT *
              FROM avainsana
              WHERE yhtio      = '$kukarow[yhtio]'
              and laji         = 'MYSQLALIAS'
              and selite       = '$taulu.$al_nimi'
              and selitetark_2 = '$alias_set'";
    $al_res = pupe_query($query);

    if (mysqli_num_rows($al_res) > 0) {
      $al_row = mysqli_fetch_array($al_res);

      $otsikko = str_replace("<br>", "(BR)", $al_row["selitetark"]);

      $box = $al_row['nakyvyys'] != '' ? "CHECKED" : "";
      $pakollisuusbox = $al_row['selitetark_3'] == 'PAKOLLINEN' ? "CHECKED" : "";
      $oletusarvo = $al_row['selitetark_4'];
      $ohjeteksti = $al_row['selitetark_5'];
    }

    // $tyyppi --> 0 riviä ei näytetä ollenkaan
    // $tyyppi --> 1 rivi näytetään normaalisti
    // $tyyppi --> 1.5 rivi näytetään normaalisti ja se on päivämääräkenttä
    // $tyyppi --> 2 rivi näytetään, mutta sitä ei voida muokata, eikä sen arvoa pävitetä
    // $tyyppi --> 3 rivi näytetään, mutta sitä ei voida muokata, mutta sen arvo päivitetään
    // $tyyppi --> 4 riviä ei näytetä ollenkaan, mutta sen arvo päivitetään
    // $tyyppi --> 5 liitetiedosto

    if (($tyyppi > 0 and $tyyppi < 4) or $tyyppi == 5) {
      echo "<tr>";
      echo "<th align='left'>".mysqli_field_name($result, $i)."</th>";

      echo "<th><input type='checkbox' name='mysqlaliasbox[".mysqli_field_name($result, $i)."]' $box></th>";
      echo "<th><input type='checkbox' name='mysqlaliaspakollisuus[".mysqli_field_name($result, $i)."]' $pakollisuusbox></th>";

      echo "<th align='left'><input type='text' size='30' name='mysqlalias[".mysqli_field_name($result, $i)."]' value='$otsikko'></th>";

      echo "<th align='left'><input type='text' size='30' name='oletusarvo[".mysqli_field_name($result, $i)."]' value='$oletusarvo'></th>";
      echo "<th align='left'><textarea cols='30' rows='4' name='ohjeteksti[".mysqli_field_name($result, $i)."]'>{$ohjeteksti}</textarea></th>";
    }

    if ($jatko == 0) {
      echo $ulos;
    }
    elseif ($tyyppi == 1) {
      echo "<td><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$size' maxlength='$maxsize'></td>";
    }
    elseif ($tyyppi == 1.5) {
      $vva = substr($trow[$i], 0, 4);
      $kka = substr($trow[$i], 5, 2);
      $ppa = substr($trow[$i], 8, 2);

      echo "<td>
          <input type = 'text' name = 'tpp[$i]' value = '$ppa' size='3' maxlength='2'>
          <input type = 'text' name = 'tkk[$i]' value = '$kka' size='3' maxlength='2'>
          <input type = 'text' name = 'tvv[$i]' value = '$vva' size='5' maxlength='4'></td>";
    }
    elseif ($tyyppi == 2) {
      echo "<td>$trow[$i]</td>";
    }
    elseif ($tyyppi == 3) {
      echo "<td>$trow[$i]<input type = 'hidden' name = '$nimi' value = '$trow[$i]'></td>";
    }
    elseif ($tyyppi == 4) {
      echo "<input type = 'hidden' name = '$nimi' value = '$trow[$i]'>";
    }
    elseif ($tyyppi == 5) {
      echo "<td><input type = 'file' name = 'liite_$i'></td>";
    }

    if (isset($virhe[$i])) {
      echo "<td class='back'><font class='error'>$virhe[$i]</font></td>\n";
    }

    if (($tyyppi > 0 and $tyyppi < 4) or $tyyppi == 5) {
      echo "</tr>";
    }
  }
  echo "</table><br>";
  echo "<input type='submit' value='".t("Päivitä")."'>";
  echo "</form><br><br>";

  echo "  <SCRIPT LANGUAGE=JAVASCRIPT>
        function verify(){
          msg = '".t("HUOM: Oletko varma, että haluat poistaa aliakset?")."';

          if (confirm(msg)) {
            return true;
          }
          else {
            skippaa_tama_submitti = true;
            return false;
          }
        }
      </SCRIPT>";

  echo "<form method = 'post'>";
  echo "<input type = 'hidden' name = 'taulu' value = '$cleantaulu'>";
  echo "<input type = 'hidden' name = 'delete' value = '1'>";
  echo "<input type='submit' value='".t("Poista")."' onClick='return verify();'>";
  echo "</form>";

}

require "inc/footer.inc";
