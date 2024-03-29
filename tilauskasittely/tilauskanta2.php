<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require '../inc/parametrit.inc';

echo "<font class='head'>".t("Tilauskanta")."</font><hr>";

$nyviikko = date("W");

if ($tee == 'NAYTATILAUS') {

  echo "<font class='head'>".t("Tilaus")." $tunnus:</font><hr>";

  require "raportit/naytatilaus.inc";

  $tee = 'aja';
}

if ($tee == 'aja' and $atoimvko != '' and ($ltoimvko == '' or $ltoimvko >= $atoimvko)) {

  $DAY_ARRAY = array(1 => t("Ma"), t("Ti"), t("Ke"), t("To"), t("Pe"), t("La"), t("Su"));

  if ($atoimvko < $nyviikko) {
    $aaika = "+" . ((52 - $nyviikko) + $atoimvko); // MUOKKAUS: PHP7/8 yhteensopivuus
  }
  elseif ($atoimvko == $nyviikko) {
    $aaika = "this";
  }
  else {
    $aaika = "+" . ($atoimvko - $nyviikko); // MUOKKAUS: PHP7/8 yhteensopivuus
  }

  //echo "aaika $aaika<br>";

  $alkuaika = viikonpaiva("Monday", "$aaika weeks");
  //echo "päivämäärä $alkuaika<br>";

  if ($ltoimvko == '') {
    $loppuaika = viikonpaiva("Sunday", "$aaika weeks");
    //echo "päivämäärä $loppuaika<br>";
  }
  else {
    if ($ltoimvko < $nyviikko) {
      $laika = "+" . ((52 - $nyviikko) + $ltoimvko); // MUOKKAUS: PHP7/8 yhteensopivuus
    }
    elseif ($ltoimvko == $nyviikko) {
      $laika = "this";
    }
    else {
      $laika = "+" . ($ltoimvko - $nyviikko); // MUOKKAUS: PHP7/8 yhteensopivuus
    }

    $loppuaika = date("Y-m-d", strtotime("Sunday", strtotime("$laika weeks")));
    //echo "päivämäärä $loppuaika<br>";
  }

  $jarjestys = " lasku.toimaika, tuoteno ";

  if (mb_strlen($kojarj) > 0) {
    $jarjestys = $kojarj;
  }

  if ($vanhat == '1') {
    $alkuaika = "0000-00-00";
  }

  $query =  "SELECT if(lasku.toimvko>0,concat_ws('@@',lasku.toimvko,lasku.toimaika),lasku.toimaika) as 'Toimitusaika', tuoteno as 'Tuotenumero', if(jt>0,jt,varattu) as 'Määrä',
             concat(concat(nimi,'<br>'),if(nimitark!='',concat(nimitark,'<br>'),''),if(toim_nimi!='',if(toim_nimi!=nimi,concat(toim_nimi,'<br>'),''),''),if(toim_nimitark!='',if(toim_nimitark!=nimitark,concat(toim_nimitark,'<br>'),''),'')) as 'Nimi/Toim. nimi',
             lasku.tunnus as 'Tilausnro', lasku.tila, lasku.alatila, tilausrivi.tunnus
             FROM tilausrivi
             JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
             WHERE tilausrivi.yhtio        = '$kukarow[yhtio]'
             AND tilausrivi.tyyppi         = 'L'
             AND tilausrivi.laskutettuaika = '0000-00-00'
             AND tilausrivi.toimaika       >= '$alkuaika'
             AND tilausrivi.toimaika       <= '$loppuaika'
             AND tilausrivi.keratty        = ''
             AND (tilausrivi.varattu > 0 or tilausrivi.jt > 0)
             ORDER BY $jarjestys ";
  $result = pupe_query($query);

  echo "<table><tr>";

  $vanhaojarj = $kojarj;

  for ($i = 0; $i < mysqli_num_fields($result)-3; $i++) {
    $kojarj=$i+1; // sortti numeron mukaan niin ei tuu onglemia
    echo "<th align='left'><a href = '$PHP_SELF?tee=$tee&atoimvko=$atoimvko&ltoimvko=$ltoimvko&vanhat=$vanhat&kojarj=$kojarj'>".t(mysqli_field_name($result, $i))."</a></th>";
  }

  $kojarj = $vanhaojarj;

  echo "<th align='left'><a href = '$PHP_SELF?tee=$tee&atoimvko=$atoimvko&ltoimvko=$ltoimvko&vanhat=$vanhat&kojarj=6,7'>".t("Tyyppi")."</a></th>";
  echo "</tr>";

  $summat = 0;
  $arvot  = 0;

  $rivit = array();

  while ($prow = mysqli_fetch_array($result)) {
    if (mb_strpos($prow[$i], '@@') !== FALSE) {
      $pvmma = mb_substr($prow["Toimitusaika"], 3);
    }
    else {
      $pvmma = $prow["Toimitusaika"];
    }

    if (strtotime($pvmma) < strtotime("now")) {
      $pvmma = "";
    }

    list(, , $myyta) = saldo_myytavissa($prow["Tuotenumero"], "KAIKKI", '', '', '', '', '', '', '', $pvmma);

    if ($myyta < $prow["Määrä"]) {
      $rivit[] = $prow["tunnus"];
    }
  }

  if (is_resource($result) and mysqli_num_rows($result)) mysqli_data_seek($result, 0);

  while ($prow = mysqli_fetch_array($result)) {
    if (in_array($prow["tunnus"], $rivit)) {
      $ero="td";
      if ($tunnus==$prow['Tilausnro']) $ero="th";

      echo "<tr class='aktiivi'>";

      for ($i=0; $i < mysqli_num_fields($result)-3; $i++) {
        if (mysqli_field_name($result, $i) == 'Toimitusaika') {
          if (mb_strpos($prow[$i], '@@') !== FALSE) {
            $pvmma = mb_substr($prow[$i], 3);
            if (mb_substr($prow[$i], 0, 1) == '7') {
              echo "<$ero valign='top'>Vko ".date("W", strtotime($pvmma))."</$ero>";
            }
            else {
              echo "<$ero valign='top'>".$DAY_ARRAY[mb_substr($prow[$i], 0, 1)]." ".t("Vko")." ".date("W", strtotime($pvmma))."</$ero>";
            }
          }
          else {
            echo "<$ero valign='top'>".tv1dateconv($prow[$i], "pitka")."</$ero>";
          }

        }
        elseif (mysqli_field_name($result, $i) == 'Tuotenumero') {
          echo "<$ero valign='top'><a href='../tuote.php?tee=Z&tuoteno=".urlencode($prow[$i])."'>$prow[$i]</$ero>";
        }
        elseif (mysqli_field_name($result, $i) == 'Nimi/Toim. nimi' and mb_substr($prow[$i], -4) == '<br>') {
          echo "<$ero valign='top'>".mb_substr($prow[$i], 0, -4)."</$ero>";
        }
        elseif (mysqli_field_name($result, $i) == 'Tilausnro') {
          echo "<$ero valign='top'><a href = '$PHP_SELF?tee=NAYTATILAUS&tunnus=$prow[$i]&atoimvko=$atoimvko&ltoimvko=$ltoimvko&vanhat=$vanhat&kojarj=$kojarj'>$prow[$i]</a></$ero>";
        }
        else {
          echo "<$ero valign='top'>".str_replace(".", ",", $prow[$i])."</$ero>";
        }
      }

      $laskutyyppi=$prow["tila"];
      $alatila=$prow["alatila"];

      //tehdään selväkielinen tila/alatila
      require "inc/laskutyyppi.inc";

      $tarkenne = " ";

      echo "<$ero valign='top'>".t("$laskutyyppi")."$tarkenne".t("$alatila")."</$ero>";

      echo "</tr>";
    }
  }
  echo "</table><br><br><br><br><br>";
}

echo "<table><form name='uliuli' method='post'>";
echo "<input type='hidden' name='tee' value='aja'>";
echo "<input type='hidden' name='tunnus' value=''>";



if ($atoimvko == '') {
  $atoimvko = $nyviikko+1;
}

echo "<tr><td>".t("Viikko (Tai alkuviikko)").": </td><td><input type='text' name='atoimvko' value='$atoimvko' size='2'></td><td class='back'>(Nyt on viikko $nyviikko)</td></tr>";
echo "<tr><td>".t("Loppuviikko (Ei pakollinen)").": </td><td><input type='text' name='ltoimvko' value='$ltoimvko' size='2'></td></tr>";

$chekkis = "";

if ($vanhat == '1') {
  $chekkis = "CHECKED";
}

echo "<tr><td colspan='2'>Huomioi myös vanhemmat: <input type='checkbox' name='vanhat' value='1' $chekkis></td></tr>";

echo "</table>";

echo "<br><input type='submit' value='".t("Aja")."'>";
echo "</form>";

require "../inc/footer.inc";
