<?php

require "inc/parametrit.inc";

/*
 * Lisää tuotteen ketjuun
 */

function lisaa_tuote($tuoteno = '', $vastaava = '', $ketju_id = '') {
  global $kukarow;

  // Tarkistetaan että tuote on olemassa
  $tuote = hae_tuote($vastaava);

  if ($tuote) {

    // Jos ketju on setattu, lisätään tuote haluttuun ketjuun
    if ($ketju_id != '') {

      // Tarkistetaan että tuote ei ole missään ketjussa päätuotteena
      $paatuote = false;
      if (onko_paatuote($vastaava)) {
        $paatuote = true;
        echo "<font class='error'>".t("Lisäys ei onnistu! Tuote")." $vastaava ".t("on päätuotteena jossain toisessa ketjussa")."!</font><br><br>";
      }

      // Tarkistetaan että tuote ei jo ole kyseisessä ketjussa
      $query = "SELECT * FROM vastaavat WHERE yhtio='{$kukarow['yhtio']}' AND tuoteno='{$vastaava}' AND id='{$ketju_id}'";
      $result = pupe_query($query);

      // Tuote on jo ketjussa
      if (mysqli_num_rows($result) > 0) {
        echo "<font class='error'>".t("Lisäys ei onnistu! Tuote")." $vastaava ".t("on jo ketjussa")." $ketju_id!</font><br><br>";
      }
      // Lisätään tuote haluttuun ketjuun
      elseif ($paatuote == false) {
        $query  = "INSERT INTO vastaavat (id, tuoteno, yhtio, laatija, luontiaika, muutospvm, muuttaja)
                   VALUES ('$ketju_id', '{$tuote['tuoteno']}', '$kukarow[yhtio]', '$kukarow[kuka]', now(), now(), '$kukarow[kuka]')";
        $result = pupe_query($query);
      }
    }
    //
    elseif ($tuoteno != '' and $ketju_id == '') {

      // Etsitään isä tuotetta (haettu tuoteno)
      $query  = "SELECT * FROM vastaavat WHERE tuoteno = '$tuoteno' AND yhtio = '$kukarow[yhtio]'";
      $result = pupe_query($query);

      if (mysqli_num_rows($result) != 0) {
        //jos on, otetaan ID luku talteen...
        $row    = mysqli_fetch_array($result);
        $fid    = $row['id'];
      }

      // Etsitään lisättävää tuotetta
      // Vastaavissa tarkistetaan vain että tuote ei ole päätuotteena missään toisessa ketjussa.
      // Jos isätuotetta ei löytynyt eikä lisättävä tuote ole missään ketjussa päätuotteena,
      // voidaan tuotteista tehdä uusi ketju
      $query  = "SELECT * FROM vastaavat WHERE tuoteno = '$vastaava' AND yhtio = '$kukarow[yhtio]' AND jarjestys='1'";
      $result = pupe_query($query);

      if (mysqli_num_rows($result) != 0) {
        //vastaava on jo lisätty.. otetaan senki id..
        $row    = mysqli_fetch_array($result);
        $cid    = $row['id'];
      }

      //jos kumpaakaan ei löytynyt...
      if (($cid == "") and ($fid == "")) {
        //silloin tämä on eka vastaava.. etsitään sopiva ID.
        $query  = "SELECT max(id) FROM vastaavat";
        $result = pupe_query($query);
        $row    = mysqli_fetch_array($result);
        $id     = $row[0]+1;

        //lisätään "isä tuote"...
        $query  = "INSERT INTO vastaavat (id, tuoteno, yhtio, laatija, luontiaika, muutospvm, muuttaja)
                   VALUES ('$id', '$tuoteno', '$kukarow[yhtio]', '$kukarow[kuka]', now(), now(), '$kukarow[kuka]')";
        $result = pupe_query($query);

        // lisätään vastaava tuote...
        $query  = "INSERT INTO vastaavat (id, tuoteno, yhtio, laatija, luontiaika, muutospvm, muuttaja)
                   VALUES ('$id', '$vastaava', '$kukarow[yhtio]', '$kukarow[kuka]', now(), now(), '$kukarow[kuka]')";
        $result = pupe_query($query);
      }

      //lapsi on löytynyt, isää ei
      if (($cid != "") and ($fid == "")) {
        //lisätään "isä tuote"...
        $query  = "INSERT INTO vastaavat (id, tuoteno, yhtio, laatija, luontiaika, muutospvm, muuttaja)
                   VALUES ('$cid', '$tuoteno', '$kukarow[yhtio]', '$kukarow[kuka]', now(), now(), '$kukarow[kuka]')";
        $result = pupe_query($query);
      }

      //isä on löytynyt, lapsi ei
      if (($fid != "") and ($cid == "")) {
        // Siirretään ketjun muita eteenpäin jarjestys + 1
        $query = "UPDATE vastaavat SET jarjestys=jarjestys+1
                  WHERE jarjestys!=0 AND id='$fid' AND yhtio='{$kukarow['yhtio']}'";
        $result = pupe_query($query);

        // Lisätään uusi aina päätuotteeksi jarjestys=1
        //lisätään vastaava päätuotteeksi
        $query  = "INSERT INTO vastaavat (id, tuoteno, yhtio, jarjestys, laatija, luontiaika, muutospvm, muuttaja)
                   VALUES ('$fid', '$vastaava', '$kukarow[yhtio]', '1', '$kukarow[kuka]', now(), now(), '$kukarow[kuka]')";
        $result = pupe_query($query);
      }

      //kummatkin löytyivät.. ja ne korvaa toisensa
      if ($fid != "" and $cid != "" and $fid == $cid) {
        echo "<font class='error'>".t("Tuotteet")." $vastaava <> $tuoteno ".t("ovat jo vastaavia")."!</font><br><br>";

      }
      elseif ($fid != "" and $cid != "" ) {
        echo "<font class='error'>".t("Tuotteet")." $vastaava, $tuoteno ".t("kuuluvat jo eri vastaavuusketjuihin")."!</font><br><br>";
      }
    }
    else {
      echo "<font class='error'>".t("Odottamaton virhe")."!</font><br><br>";
    }
  }
}

/**
 * Hakee tuotteen tiedot
 */


function hae_tuote($tuoteno) {
  global $kukarow;

  if (empty($tuoteno)) exit("ei voida hakea olematonta tuotetta!");

  // Haetaan tuotteen tiedot
  $query = "SELECT * FROM tuote WHERE yhtio='{$kukarow['yhtio']}' AND tuoteno='{$tuoteno}'";
  $result = pupe_query($query);
  $tuote = mysqli_fetch_assoc($result);

  return $tuote;
}


/**
 * Hakee vastaavat tuoteketjun
 */
function hae_vastaavat_ketjut($tuoteno) {
  global $kukarow;

  // Haetaan ketjut johon tuote kuuluu
  $query = "SELECT id FROM vastaavat WHERE yhtio='{$kukarow['yhtio']}' AND tuoteno='{$tuoteno}'";
  $result = pupe_query($query);

  // Haetaan ketjujen tuotteet
  $ketjut = array();
  while ($ketju = mysqli_fetch_array($result)) {
    $ketju_query = "SELECT * FROM vastaavat WHERE yhtio='{$kukarow['yhtio']}' AND id='{$ketju['id']}'
                    ORDER BY if(jarjestys=0, 9999, jarjestys), tuoteno";
    $ketju_result = pupe_query($ketju_query);

    $tuotteet = array();
    while ($tuote = mysqli_fetch_assoc($ketju_result)) {
      $tuotteet[] = $tuote;
    }

    $ketjut[$ketju['id']] = $tuotteet;
  }

  return $ketjut;
}


/**
 * Tarkistaa onko tuote minkään ketjun päätuotteena
 */
function onko_paatuote($tuoteno) {

  // Haetaan kaikki tuotteen ketjut
  $ketjut = hae_vastaavat_ketjut($tuoteno);

  // Loopataan ketjut läpi ja tarkistetaan päätuote
  foreach ($ketjut as $id => $ketju) {
    if (mb_strtoupper($tuoteno) == mb_strtoupper($ketju[0]['tuoteno'])) {
      return true;
    }
  }

  return false;
}

echo "<font class='head'>".t("Vastaavien ylläpito")."</font><hr>";

echo "<form method='get' name='etsituote' autocomplete='off'>
    <input type='hidden' value='$lopetus' name='lopetus'>
      ".t("Etsi tuotetta")." <input type='text' name='tuoteno'>
      <input type='submit' class='hae_btn' value='".t("Hae")."'>
      </form><br><br>";

if (!isset($tee)) $tee = '';

// Poistetaan vastaava ketjusta
if ($tee == 'del') {
  $query = "DELETE FROM vastaavat WHERE yhtio='{$kukarow['yhtio']}' AND tunnus='{$tunnus}'";
  $result = pupe_query($query);
}

if ($tee == 'vaihtoehtoinen') {
  //haetaan tuotteen id.. käyttäjästävällistä..
  $query  = "SELECT * FROM vastaavat WHERE tunnus = '$tunnus' AND yhtio = '$kukarow[yhtio]'";
  $result = pupe_query($query);
  $row    = mysqli_fetch_array($result);
  $id     = $row['id'];

  // Jos setattu vaihetoehtoiseksi?
  if ($vaihtoehtoinen == 'true' and $row['vaihtoehtoinen'] != 'K') {

    // Tuote voi olla useammassa ketjussa vain vaihtoehtoinen tai vastaava. Ei molempia.
    $query = "UPDATE vastaavat SET vaihtoehtoinen='K' WHERE yhtio='{$kukarow['yhtio']}' AND tuoteno='{$_tuoteno}'";
    $result = pupe_query($query);
  }
  elseif ($vaihtoehtoinen != true and $row['vaihtoehtoinen'] == 'K') {
    $query = "UPDATE vastaavat SET vaihtoehtoinen='' WHERE yhtio='{$kukarow['yhtio']}' AND tuoteno='{$_tuoteno}'";
    $result = pupe_query($query);
  }
}

if ($tee == 'muutaprio') {
  //haetaan tuotteen id.. käyttäjästävällistä..
  $query  = "SELECT * FROM vastaavat WHERE tunnus = '$tunnus' AND yhtio = '$kukarow[yhtio]'";
  $result = pupe_query($query);
  $row    = mysqli_fetch_array($result);
  $id     = $row['id'];

  // Tarkistetaan onko 'seuraava' ketjun päätuote päätuotteena jossakin toisessa ketjussa?
  if (onko_paatuote($row['tuoteno'])) {
    echo "<font class='error'>".t("Huom! Muutit päätuotetta tai tuote on päätuotteena jossakin toisessa ketjussa")."!</font><br><br>";
  }

  // Siirretään ketjun muita eteenpäin, jarjestys + 1
  if ($prio != 0 and $prio != $row['jarjestys'] and is_numeric($prio)) {
    $query = "UPDATE vastaavat
              SET jarjestys=jarjestys+1,
                  muuttaja='{$kukarow['kuka']}',
                  muutospvm=now()
              WHERE jarjestys!=0
                AND id='$id'
                AND yhtio='{$kukarow['yhtio']}'
                AND tunnus!=$tunnus
                AND jarjestys >= $prio";
    $result = pupe_query($query);
  }

  // muutetaan prioriteetti
  $query  = "UPDATE vastaavat SET
             jarjestys    = '$prio',
             muutospvm    = now(),
             muuttaja     = '$kukarow[kuka]'
             WHERE tunnus = '$tunnus' AND yhtio = '$kukarow[yhtio]'";
  $result = pupe_query($query);

  // Tiivistetään vastaavat ketjusta välit pois
  tiivista_vastaavat_tuoteketju($id);
}

if ($tee == 'add') {

  // tutkitaan onko lisättävä tuote oikea tuote...
  if (!hae_tuote($vastaava)) {
    echo "<font class='error'>".t("Lisäys ei onnistu! Tuotetta")." $vastaava ".t("ei löydy")."!</font><br><br>";
  }
  // Lisätään haluttuun ketjuun
  elseif (!empty($ketju_id)) {
    lisaa_tuote($tuoteno, $vastaava, $ketju_id);
  }
  else {
    lisaa_tuote($tuoteno, $vastaava);
  }
}

if ($tuoteno != '') {

  echo "<font class='head'>".t("Tuotenumero").": $tuoteno</font><hr>";

  $tuote = hae_tuote($tuoteno);

  // Jos tuote on olemassa
  if (!$tuote) {
    echo "<br><font class='error'>".t("Tuotenumeroa")." $tuoteno ".t("ei ole perustettu")."!</font><br>";
  }
  else {
    $ketjut = hae_vastaavat_ketjut($tuoteno);
    // Jos ketjuja ei löytynyt
    if (!$ketjut) {
      echo "<br><font class='message'>".t("Tuotteella ei ole vastaavia tuotteita")."!</font>";

      echo "<form method='post' autocomplete='off'>
          <input type='hidden' value='$lopetus' name='lopetus'>
                    <input type='hidden' name='tuoteno' value='$tuoteno'>
                    <input type='hidden' name='tee' value='add'>
                    <hr>";

      echo t("Lisää vastaava tuote").": ";

      echo "<input type='text' name='vastaava'>
                    <input type='submit' value='".t("Lisää")."'>
                    </form>";
    }
    // Loopataan ketjut läpi
    else {
      foreach ($ketjut as $id => $tuotteet) {
        echo "<br><table>";
        echo "<tr><th colspan=3>Ketju $id</th></tr>";
        echo "<tr>";
        echo "<th>".t("Vastaavia tuotteita")."</th>";
        echo "<th>".t("Järjestys")."</th>";
        echo "<th>".t("Vaihtoehtoinen")."</th>";
        echo "<th class='back'></th>";
        echo "</tr>";

        // Loopataan ketjun tuotteet läpi
        foreach ($tuotteet as $tuote) {

          // Tsekataan että ketjun tuotteet ovat olemassa
          $error = '';
          if (!hae_tuote($tuote['tuoteno'])) {
            $error = "<font class='error'>(".t("Tuote ei enää rekisterissä")."!)</font>";
          }

          echo "<tr><td>$tuote[tuoteno] $error</td>";
          echo "<td><form method='post' autocomplete='off'>
              <input type='hidden' value='$lopetus' name='lopetus'>
                          <input size='5' type='text' name='prio' value='$tuote[jarjestys]'>
                          <input type='hidden' name='tunnus' value='$tuote[tunnus]'>
                          <input type='hidden' name='tee' value='muutaprio'>
                          </form>
              </td>";

          $checked = ($tuote['vaihtoehtoinen'] == 'K') ? 'checked' : '';

          echo "<td><form method='post'>
            <input type='hidden' value='$lopetus' name='lopetus'>
                        <input type='hidden' name='tunnus' value='$tuote[tunnus]'>
                        <input type='hidden' name='_tuoteno' value='$tuote[tuoteno]'>
                        <input type='hidden' name='tee' value='vaihtoehtoinen'>
                        <input type='checkbox' name='vaihtoehtoinen' value='true' onclick='submit()' $checked>
                        </form></td>";

          echo "<td class='back'>
              <form method='post'>
              <input type='hidden' value='$lopetus' name='lopetus'>
              <input type='hidden' name='tunnus' value='$tuote[tunnus]'>
              <input type='hidden' name='tee' value='del'>
              <input type='submit' value='".t("Poista")."'>
              </form>
              </td>
              </tr>";
        }

        echo "</table>";

        echo "<form method='post' autocomplete='off'>
            <input type='hidden' value='$lopetus' name='lopetus'>
                      <input type='hidden' name='tuoteno' value='$tuoteno'>
                      <input type='hidden' name='ketju_id' value='$id'>
                      <input type='hidden' name='tee' value='add'>
                      <hr>";
        echo t("Lisää vastaava tuote").": ";

        echo "<input type='text' name='vastaava'>
                      <input type='submit' value='".t("Lisää")."'>
                      </form><br>";
      }
    }
  }
}

$formi = 'etsituote';
$kentta = 'tuoteno';

require "inc/footer.inc";
