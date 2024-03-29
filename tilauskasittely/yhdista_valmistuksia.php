<?php
require "../inc/parametrit.inc";

js_popup();

echo "<font class='head'>".t("Yhdistä valmistuksia").":</font><hr>";

if ($tee == 'NAYTATILAUS') {
  require "raportit/naytatilaus.inc";
  echo "<hr>";
  $tee = "VALITSE";
}

if ($ohitus == "OHITA") {
  $valmistettavat = implode(",", $valmistettavat);
  $tee = "VALITSE";
}

if ($tee=='YHDISTA') {
  //Käydään läpi rivit


  if (count($valmistettavat) > 1 or ($tilaukseen > 0 and count($valmistettavat) > 0)) {

    $query = "SELECT *
              FROM  lasku
              WHERE yhtio = '$kukarow[yhtio]'
              and  tunnus = (SELECT otunnus from tilausrivi WHERE yhtio = '$kukarow[yhtio]' and tunnus=$valmistettavat[0])";
    $result = pupe_query($query);
    $laskurow    = mysqli_fetch_array($result);

    if ($tilaukseen > 0) {

      //  Tarkastetaan että tunnus on oikein
      $query = "SELECT tunnus
                FROM  lasku
                WHERE yhtio = '$kukarow[yhtio]'
                and  tunnus = '$tilaukseen'
                and tila    = 'V'";
      $result = pupe_query($query);
      if (mysqli_num_rows($result) == 0) {
        die("<font class='error'>VIRHE!!! Tilaus ei ole valmistus!</font>");
      }

      $otunnus = $tilaukseen;


      echo "<font class='message'>".t("Liitetään rivit valmistukseen")." $otunnus</font><br>";

      $query = "UPDATE lasku SET
                comments    = '$comments',
                viesti      = '$viesti'
                WHERE yhtio = '$kukarow[yhtio]'
                and  tunnus = '$otunnus'";
      $result = pupe_query($query);
    }
    else {
      $query = "INSERT into
                lasku SET
                clearing          = '',
                nimi              = 'Valmistusajo',
                toimaika          = now(),
                kerayspvm         = now(),
                comments          = '$comments',
                viesti            = '$viesti',
                yhtio             = '$kukarow[yhtio]',
                varasto           = '$laskurow[varasto]',
                hyvaksynnanmuutos = '',
                liitostunnus      = '9999999999',
                tilaustyyppi      = 'W',
                tila              = 'V',
                alatila           = 'J',
                ytunnus           = 'Valmistusajo',
                laatija           = '$kukarow[kuka]',
                luontiaika        = NOW()";

      // MUOKKAUS: mysqli_insert_id():
      $result = pupe_query($query, $GLOBALS["link"]);
      $otunnus = mysqli_insert_id($GLOBALS["link"]);

      echo "<font class='message'>".t("Luotiin uusi otsikko")." $otunnus</font><br>";

    }


    foreach ($valmistettavat as $rivitunnus) {
      //Otetaan alkuperäisen otsikon numero talteen
      $query = "SELECT otunnus
                FROM tilausrivi
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$rivitunnus'";
      $result = pupe_query($query);
      $otsikrow = mysqli_fetch_array($result);

      //  Tallennetaan vanha otunnus
      $query = "UPDATE tilausrivin_lisatiedot
                SET vanha_otunnus = (SELECT otunnus from tilausrivi WHERE yhtio = '$kukarow[yhtio]' and tunnus=$rivitunnus)
                WHERE yhtio          = '$kukarow[yhtio]'
                and tilausrivitunnus = '$rivitunnus'";
      $result = pupe_query($query);

      //Siirretään rivi uudelle otsikolle
      $query = "UPDATE tilausrivi
                SET otunnus = '$otunnus'
                WHERE yhtio     = '$kukarow[yhtio]'
                and perheid     = '$rivitunnus'
                and tyyppi      in ('V','W','M')
                and uusiotunnus = 0";
      $result = pupe_query($query);

      //Tsekataan onko alkuperäisotsikolla vielä rivejä
      $query = "SELECT count(*) jaljella
                FROM tilausrivi
                WHERE yhtio = '$kukarow[yhtio]'
                and otunnus = '$otsikrow[otunnus]'";
      $result = pupe_query($query);
      $rivirow = mysqli_fetch_array($result);

      if ($rivirow["jaljella"] == 0) {
        //päivitetään alkuperäisen otsikon alatila
        $query = "UPDATE lasku
                  SET alatila = 'Y'
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$otsikrow[otunnus]'";
        $result = pupe_query($query);
      }
    }


    if ($yhtiorow["valmistusten_yhdistaminen"] == "P") {
      //  Testataan saataisiinko jotain perheitä yhdistettyä
      $query = "SELECT group_concat(tunnus) tunnukset, count(*) rivei
                FROM tilausrivi
                WHERE yhtio = '$kukarow[yhtio]' and otunnus='$otunnus' and tyyppi IN ('W','M') and perheid = tunnus
                GROUP BY tuoteno
                HAVING rivei > 1";
      $result = pupe_query($query);
      if (mysqli_num_rows($result)>0) {
        while ($row = mysqli_fetch_array($result)) {

          //  suoritetan vertailu by tuoteperhe
          $query = "SELECT tuoteno, perheid,
                    group_concat(
                      concat(
                        tuoteno, 'x',
                        (SELECT round((isa.kpl+isa.jt+isa.varattu)/((tilausrivi.kpl+tilausrivi.jt+tilausrivi.varattu)/10), 1) FROM tilausrivi isa WHERE isa.yhtio = tilausrivi.yhtio and isa.tunnus=tilausrivi.perheid)
                      ) ORDER BY tuoteno SEPARATOR '|'
                    ) stringi
                    FROM tilausrivi
                    WHERE yhtio = '$kukarow[yhtio]' and otunnus='$otunnus' and perheid IN ($row[tunnukset])
                    GROUP BY perheid
                    ORDER BY stringi";
          $sresult = pupe_query($query);

          $edstringi = $edperheid = "";
          $yhdistettavat = array();
          $yhdista = array();
          while ($srow = mysqli_fetch_array($sresult)) {

            //  Tänne mennään jos vaihdetaan summausta ja meillä on jotain yhdistettävää
            if ($edstringi != "" and $edstringi != $srow["stringi"] and count($yhdista) > 1) {
              $yhdistettavat[] = implode(',', $yhdista);
              $yhdista = array();
            }

            //  Jos meillä on sama stringi kuin edellinen voidaan yhdistää
            if ($edstringi == $srow["stringi"]) {
              if (count($yhdista) == 0) {
                $yhdista[] = $edperheid;
              }
              $yhdista[] = $srow["perheid"];

              echo "<font class='info'>".t("Yhdistetään tuote")." $srow[tuoteno]!</font><br>";
            }

            $edstringi = $srow["stringi"];
            $edperheid = $srow["perheid"];
          }

          if (count($yhdista) > 1) {
            $yhdistettavat[] = implode(',', $yhdista);
            $yhdista = array();
          }

          //  Ou jea! Miellä on sopivat reseptit summataanpas nmä nyt sitten yhteen!
          if (count($yhdistettavat) > 0) {
            foreach ($yhdistettavat as $tunnukset) {

              $pilkunpaikka = mb_strpos($tunnukset, ",");
              $ekaperhe = mb_substr($tunnukset, 0, $pilkunpaikka);
              $loput = mb_substr($tunnukset, ($pilkunpaikka+1));
              //  tiedot varmasti ok?
              if ($ekaperhe > 0 and $loput != "") {

                //  Päivitetään summa ekaan tietueeseen toinen tuhotaan! HUOM: tämä ei tajua mitään varastopaikoista, jos meillä on sama tuote 2 kertaa on myös suuri ongelma!
                $query = "SELECT tuoteno, sum(kpl) kpl, sum(varattu) varattu, sum(jt) jt, sum(tilkpl) tilkpl, group_concat(if(kommentti='', NULL, kommentti)) kommentti
                          FROM tilausrivi
                          WHERE yhtio = '$kukarow[yhtio]' and otunnus = '$otunnus' and perheid IN ($tunnukset)
                          GROUP BY tuoteno";
                $sresult = pupe_query($query);
                while ($srow = mysqli_fetch_array($sresult)) {

                  //  Päivitetään ekan perheen tiedot
                  $query = "UPDATE tilausrivi SET
                            kpl         = '".round($srow['kpl'], 2)."',
                            tilkpl      = '".round($srow['tilkpl'], 2 )."',
                            varattu     = '".round($srow['varattu'], 2 )."',
                            jt          = '".round($srow['jt'], 2 )."',
                            kommentti   = '$srow[kommentti]'
                            WHERE yhtio = '$kukarow[yhtio]' and otunnus = '$otunnus' and perheid = '$ekaperhe' and tuoteno = '$srow[tuoteno]'";
                  $updres = pupe_query($query);

                  //  Merkataan loput poistetuiksi
                  $query = "UPDATE tilausrivi SET
                            tyyppi      = 'D',
                            kommentti   = 'Tuote yhdistettiin perheeseen $ekaperhe'
                            WHERE yhtio = '$kukarow[yhtio]' and otunnus = '$otunnus' and perheid IN ($loput) and tuoteno = '$srow[tuoteno]'";
                  $updres = pupe_query($query);

                  //echo "<font class='info'>".t("Yhdistettiin")." $srow[tuoteno] !</font><br>";
                }
              }
              else {
                echo "<font class='error'>".t("VIRHE: Rivejä ei osattu summata!")."</font><br>";
              }
            }
          }
        }
      }
    }

    echo "<font class='message'>".t("Siirrettin rivit uudelle otsikolle")."!</font><br><br>";
    $tee = "";
  }
  else {
    echo "<font class='error'>", t("Valitse ainakin 2 riviä jotka aiot yhdistää")."</font><br><br>";
    $tee = "VALITSE";
    $valmistettavat = implode(",", $valmistettavat);
  }
}

if ($tee == "VALITSE") {

  echo "<table>";

  $query = "SELECT
            GROUP_CONCAT(DISTINCT lasku.tunnus SEPARATOR ', ') 'Tilaus',
            GROUP_CONCAT(DISTINCT lasku.nimi SEPARATOR ', ') 'Asiakas/Nimi',
            GROUP_CONCAT(DISTINCT lasku.ytunnus SEPARATOR ', ') 'Ytunnus',
            GROUP_CONCAT(DISTINCT lasku.viesti SEPARATOR ', ') 'viestit',
            GROUP_CONCAT(DISTINCT lasku.comments SEPARATOR ', ') 'comments'
            FROM tilausrivi, lasku
            LEFT JOIN kuka ON lasku.myyja = kuka.tunnus and lasku.yhtio=kuka.yhtio
            WHERE tilausrivi.yhtio     = '$kukarow[yhtio]'
            and  tilausrivi.tunnus     in ($valmistettavat)
            and lasku.tunnus=tilausrivi.otunnus
            and lasku.yhtio=tilausrivi.yhtio
            and tilausrivi.uusiotunnus = 0";
  $result = pupe_query($query);
  $row    = mysqli_fetch_array($result);

  for ($i=0; $i < mysqli_num_fields($result); $i++) {
    echo "<tr><th align='left'>" . t(mysqli_field_name($result, $i)) ."</th><td>$row[$i]</td></tr>";
  }
  echo "</table><br>";

  $query = "SELECT tilausrivi.otunnus, tilausrivi.nimitys, tilausrivi.kommentti, tilausrivi.tuoteno, tilkpl tilattu, if(tyyppi!='L', varattu, 0) valmistetaan, if(tyyppi='L' or tyyppi='D', varattu, 0) valmistettu,
            toimaika, kerayspvm, tilausrivi.tunnus tunnus, tilausrivi.perheid, tilausrivi.tyyppi, tilausrivi.toimitettuaika
            FROM tilausrivi, tuote
            WHERE
            tilausrivi.otunnus         in ($row[Tilaus])
            and tilausrivi.perheid     in ($valmistettavat)
            and tilausrivi.yhtio='$kukarow[yhtio]'
            and tuote.yhtio=tilausrivi.yhtio
            and tuote.tuoteno=tilausrivi.tuoteno
            and tyyppi                 IN ('W','M')
            and tilausrivi.uusiotunnus = 0
            ORDER BY perheid";
  $presult = pupe_query($query);
  $riveja = mysqli_num_rows($presult);

  echo "<table><tr>";
  echo "<th>".t("Valmistus")."</a></th>";
  echo "<th>".t("Nimitys")."/".t("Kommentti")."</a></th>";
  echo "<th>".t("Valmiste")."</a></th>";
  echo "<th>".t("Valmistetaan")."</a></th>";
  echo "<th>".t("Keräysaika")."</a></th>";
  echo "<th>".t("Valmistusaika")."</a></th>";
  echo "<th>".t("Yhdistä")."</a></th>";
  echo "</tr>";

  $rivkpl = mysqli_num_rows($presult);

  $vanhaid = "KALA";

  echo "  <form method='post' id = 'formi' autocomplete='off'>";
  echo "  <input type='hidden' name='tee' value='YHDISTA'>
      <input type='hidden' name='toim'  value='$toim'>";

  while ($prow = mysqli_fetch_array($presult)) {
    $linkki = "";
    $query = "SELECT fakta2
              FROM tuoteperhe
              WHERE yhtio    = '$kukarow[yhtio]'
              and tyyppi     = 'R'
              and isatuoteno = '$prow[tuoteno]'
              and trim(fakta2) != ''
              ORDER BY LENGTH(fakta2) desc
              LIMIT 1";
    $faktares = pupe_query($query);

    if (mysqli_num_rows($faktares) > 0) {
      $faktarow = mysqli_fetch_array($faktares);
      $id = uniqid();
      echo "<div id='div_$id' class='popup' style='width: 400px'>
          <font class='head'>Tuotteen yhdistettävyys</font><br>
          $faktarow[fakta2]<br></div>";
      $linkki = "<div style='text-align: right; float:right;' class='tooltip' id='$id'>&nbsp;&nbsp;<img src='../pics/lullacons/info.png' height='13'></div>";
    }

    $kommentti = "";
    if ($prow["kommentti"] != "") {
      $kommentti = "<br><font class='info'>$prow[kommentti]</font>";
    }

    echo "<tr>";
    echo "<td>$prow[otunnus]</td>";
    echo "<td align='left'>".t_tuotteen_avainsanat($prow, 'nimitys')."$kommentti</td>";
    echo "<td><a href='../tuote.php?tee=Z&tuoteno=".urlencode($prow[$i])."'>$prow[tuoteno]</a>$linkki</td>";
    echo "<td align='right'>$prow[tilattu]</td>";
    echo "<td align='right'>$prow[kerayspvm]</td>";
    echo "<td align='right'>$prow[toimaika]</td>";
    echo "<td align='center'><input type='checkbox' name='valmistettavat[]' value='$prow[tunnus]' checked></td>";
    echo "</tr>";
  }

  echo "</table><br><br>";

  $query = "SELECT *
            FROM  lasku
            WHERE yhtio = '$kukarow[yhtio]'
            and tila    = 'V'
            and  tunnus = '$tilaukseen'";
  $result = pupe_query($query);
  $laskurow = mysqli_fetch_array($result);

  echo "
    <table>
      <tr>
        <th>".t("Valmistus").":</th>
        <td>$laskurow[nimi]</td>
      </tr>
      <tr>
        <th>".t("Viite").":</th>
        <td><input type='text' size='53' name='viesti' value='$laskurow[viesti]'></td>
      </tr>
      <tr>
        <th>".t("Kommentit").":</th>
        <td><textarea name='comments' rows='2' cols='60'>$laskurow[comments]</textarea></td>
        </tr>
    </table>
    <br>";

  $query = "SELECT tunnus, viesti
            FROM lasku
            WHERE yhtio = '$kukarow[yhtio]' and tila = 'V' and alatila IN ('', 'J') and ytunnus = 'Valmistusajo'";
  $result = pupe_query($query);

  echo "<table><tr><th>".t("Yhdistä valitut valmistukseen").":</th>
      <td><input type='hidden' id='ohitus' name='ohitus' value=''><select name='tilaukseen' onchange = \"document.getElementById('ohitus').value='OHITA'; submit();\"><option value = ''>".t("Tee uusi valmistusajo")."</option>'";

  if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_array($result)) {
      if ($tilaukseen == $row["tunnus"]) {
        $sel = "SELECTED";
      }
      else {
        $sel = "";
      }
      echo "<option value='$row[tunnus]' $sel>$row[tunnus] - $row[viesti]</option>";
    }
  }

  echo "</select>
      <td class='back'><input type='submit' value='".t("Yhdistä")."'></td>
    </tr></table></form>";
}

// meillä ei ole valittua tilausta
if ($tee == "") {
  $formi  = "find";
  $kentta  = "etsi";

  // tehdään etsi valinta
  echo "<br><form name='find' method='post'>";
  echo "<input type='hidden' name='toim'  value='$toim'>";

  echo "<table>";
  echo "<tr>
    <th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
    <td><input type='text' name='ppa' value='$ppa' size='3'></td>
    <td><input type='text' name='kka' value='$kka' size='3'></td>
    <td><input type='text' name='vva' value='$vva' size='5'></td>
    </tr>\n
    <tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
    <td><input type='text' name='ppl' value='$ppl' size='3'></td>
    <td><input type='text' name='kkl' value='$kkl' size='3'></td>
    <td><input type='text' name='vvl' value='$vvl' size='5'></td>
    </tr>\n";

  if ($pervalmiste == "OK") {
    $chk = "CHECKED";
  }
  else {
    $chk = "";
  }

  echo "<tr>
    <th>".t("Ryhmittele per valmiste").":</th>";
  echo "<td colspan='3'><input type='checkbox' name='pervalmiste' value='OK' $chk onchange='submit();'></td>";


  echo "<tr>
    <th>".t("Etsi valmistetta/raaka-ainetta").":</th>";
  echo "<td colspan='3'><input type='text' name='etsi' value='$etsi'></td>";
  echo "<td><input type='submit' class='hae_btn' value='".t("Etsi")."'></td></form>";
  echo "</table><br><br>";

  $haku='';

  // Haetaan jotain tuotetta
  $query = "SELECT group_concat(distinct perheid separator ',') haku
            FROM tilausrivi
            JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tila = 'V' and lasku.alatila = 'J'
            JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus and tilausrivin_lisatiedot.vanha_otunnus = 0
            WHERE tilausrivi.yhtio      = '$kukarow[yhtio]'
            and tilausrivi.toimitettu   = ''
            and tilausrivi.varattu     != 0
            and tilausrivi.uusiotunnus  = 0
            and tilausrivi.tuoteno='$etsi'
            and tilausrivi.tyyppi       in ('W','V','M')";
  $tilre = pupe_query($query);
  $tilro = mysqli_fetch_array($tilre);

  if ($tilro["haku"] != '') {
    $haku = " and tilausrivi.tunnus in ($tilro[haku])";
  }


  if (mb_strlen($ojarj) > 0) {
    $jarjestys = " ORDER BY ".$ojarj;
  }
  else {
    $jarjestys = " ORDER BY tuoteno, lasku.tunnus, varattu";
  }

  if (checkdate((int) $kka, (int) $ppa, (int) $vva)) {
    $alku = " and tilausrivi.toimaika>='$vva-$kka-$ppa'";
  }

  if (checkdate((int) $kkl, (int) $ppl, (int) $vvl)) {
    $loppu = " and tilausrivi.toimaika<='$vvl-$kkl-$ppl'";
  }

  if ($haku == "" and $pervalmiste == "OK") {
    $query     = "SELECT
                  GROUP_CONCAT(DISTINCT lasku.tunnus ORDER BY lasku.tunnus SEPARATOR '<br>') tunnus,
                  GROUP_CONCAT(DISTINCT lasku.nimi SEPARATOR '<br>') nimi,
                  GROUP_CONCAT(DISTINCT lasku.ytunnus SEPARATOR '<br>') ytunnus,
                  tilausrivi.tuoteno,
                  sum(tilausrivi.varattu) varattu,
                  GROUP_CONCAT(DISTINCT tilausrivi.kerayspvm ORDER BY tilausrivi.kerayspvm SEPARATOR '<br>') kerayspvm,
                  GROUP_CONCAT(DISTINCT tilausrivi.toimaika ORDER BY tilausrivi.toimaika SEPARATOR '<br>') toimaika,
                  GROUP_CONCAT(DISTINCT tilausrivi.tunnus SEPARATOR ',') valmistettavat,
                  lasku.varasto";
    $grouppi  = " GROUP BY lasku.varasto, tilausrivi.tuoteno";
  }
  elseif ($haku != "") {
    $query     = "SELECT
                  GROUP_CONCAT(DISTINCT lasku.tunnus ORDER BY lasku.tunnus SEPARATOR '<br>') tunnus,
                  GROUP_CONCAT(DISTINCT lasku.nimi SEPARATOR '<br>') nimi,
                  GROUP_CONCAT(DISTINCT lasku.ytunnus SEPARATOR '<br>') ytunnus,
                  GROUP_CONCAT(DISTINCT tilausrivi.tuoteno ORDER BY tilausrivi.tuoteno SEPARATOR '<br>') tuoteno,
                  sum(tilausrivi.varattu) varattu,
                  GROUP_CONCAT(DISTINCT tilausrivi.kerayspvm ORDER BY tilausrivi.kerayspvm SEPARATOR '<br>') kerayspvm,
                  GROUP_CONCAT(DISTINCT tilausrivi.toimaika ORDER BY tilausrivi.toimaika SEPARATOR '<br>') toimaika,
                  GROUP_CONCAT(DISTINCT tilausrivi.tunnus SEPARATOR ',') valmistettavat,
                  lasku.varasto";
    $grouppi  = " GROUP BY lasku.varasto";
  }
  else {
    $query     = "SELECT
                  lasku.tunnus,
                  lasku.nimi,
                  lasku.ytunnus,
                  tilausrivi.tuoteno,
                  tilausrivi.varattu,
                  tilausrivi.kerayspvm,
                  tilausrivi.toimaika,
                  lasku.varasto,
                  tilausrivi.tunnus valmistettavat";
    $grouppi  = " ";
  }

  $query .= "  FROM tilausrivi
        JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and lasku.tila = 'V' and lasku.alatila = 'J'
        JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus and tilausrivin_lisatiedot.vanha_otunnus = 0
        WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
        and tilausrivi.toimitettu = ''
        and tilausrivi.varattu != 0
        and tilausrivi.uusiotunnus = 0
        and tilausrivi.tyyppi IN ('W','M')
        $alku
        $loppu
        $haku
        $grouppi
        $jarjestys";
  $tilre = pupe_query($query);

  if (mysqli_num_rows($tilre) > 0) {
    echo "<table>";
    echo "<tr>";
    echo "<th align='left'><a href = '$PHP_SELF?ojarj=1&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&etsi=$etsi'>".t("Valmistus")."</a></th>";
    echo "<th align='left'><a href = '$PHP_SELF?ojarj=2&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&etsi=$etsi'>".t("Asiakas/Varasto")."</a></th>";
    echo "<th align='left'><a href = '$PHP_SELF?ojarj=3&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&etsi=$etsi'>".t("Ytunnus")."</a></th>";
    echo "<th align='left'><a href = '$PHP_SELF?ojarj=4&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&etsi=$etsi'>".t("Valmiste")."</a></th>";
    echo "<th align='left'><a href = '$PHP_SELF?ojarj=5&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&etsi=$etsi'>".t("Määrä")."</a></th>";
    echo "<th align='left'><a href = '$PHP_SELF?ojarj=6&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&etsi=$etsi'>".t("Keräysaika")."</a></th>";
    echo "<th align='left'><a href = '$PHP_SELF?ojarj=7&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&etsi=$etsi'>".t("Valmistusaika")."</a></th>";
    echo "</tr>";

    while ($tilrow = mysqli_fetch_array($tilre)) {

      echo "  <tr>
          <td valign='top'>$tilrow[tunnus]</td>
          <td valign='top'>$tilrow[nimi] $tilrow[nimitark]</td>
          <td valign='top'>$tilrow[ytunnus]</td>
          <td valign='top'>$tilrow[tuoteno]</td>
          <td valign='top'>$tilrow[varattu]</td>
          <td valign='top'>$tilrow[kerayspvm]</td>
          <td valign='top'>$tilrow[toimaika]</td>";

      if ($haku != "") {
        echo "  <td class = 'back'>
              <form method='post'><td class='back'>
              <input type='hidden' name='tee' value='VALITSE'>
              <input type='hidden' name='valmistettavat' value='$tilrow[valmistettavat]'>
              <input type='submit' value='".t("Valitse")."'>
              </form>
            </td>";
      }
      echo "</tr>";
    }
    echo "</table>";
  }
  else {
    echo "<font class='message'>".t("Yhtään valmistettavaa tilausta/tuotetta ei löytynyt")."...</font>";
  }
}

require "../inc/footer.inc";
