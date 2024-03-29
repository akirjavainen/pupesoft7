<?php

//kaipaa sisään
//$varasto = varaston tunnus josta myydään
//$tilaus  = tilausnumero/tilausnumerot jota optimoidaan
//palauttaa $kirjoitin jossa on parhaan kirjoittimen tunnnus


//hateaan varaston tulostusalueet
$query = "SELECT *
          FROM varaston_tulostimet
          WHERE varasto = '$varasto'
          and yhtio     = '$kukarow[yhtio]'
          ORDER BY prioriteetti, alkuhyllyalue";
$turesult = pupe_query($query);

if (mysqli_num_rows($turesult) > 0) {

  $pri     = 0;
  $lis     = "";
  $kirjoitin  = "";

  if (!isset($printterinro)) {
    $printterinro = 0;
  }

  while ($turow = mysqli_fetch_array($turesult)) {
    $lis .= "  sum(if(
          concat(rpad(upper('$turow[alkuhyllyalue]')  ,5,'0'),lpad(upper('$turow[alkuhyllynro]')  ,5,'0')) <= concat(rpad(upper(tilausrivi.hyllyalue) ,5,'0'),lpad(upper(tilausrivi.hyllynro) ,5,'0')) and
          concat(rpad(upper('$turow[loppuhyllyalue]') ,5,'0'),lpad(upper('$turow[loppuhyllynro]') ,5,'0')) >= concat(rpad(upper(tilausrivi.hyllyalue) ,5,'0'),lpad(upper(tilausrivi.hyllynro) ,5,'0')),1,0)) '".$turow["printteri{$printterinro}"]."',";
    $pri++;
  }

  $lis = mb_substr($lis, 0, -1);

  if ($yhtiorow['pakkaamolokerot'] != '') {
    $kirjoitintilrivilisa = " AND var not in ('P','J','O','S') ";
  }

  if ($lis != '') {
    $query = "SELECT $lis
              FROM tilausrivi
              WHERE otunnus in ($tilaus)
              and yhtio     = '$kukarow[yhtio]'
              $kirjoitintilrivilisa";
    $tiresult = pupe_query($query);
    $tirow = mysqli_fetch_array($tiresult);

    for ($ir = 0; $ir<$pri; $ir++) {
      if ($tirow[$ir] > 0) {
        $kirjoitin = mysqli_field_name($tiresult, $ir);
        break;
      }
    }
  }
}
