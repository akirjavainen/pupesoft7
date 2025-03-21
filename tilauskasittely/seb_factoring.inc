<?php
//tarvitsemme $laskurow:n jossa on laskun tiedot
// ja $masrown jossa on maksuehdon tiedot

// jos $fakt_lahetys on tyhjä niin luodaan aineisto
// jos $fakt_lahetys ei ole tyhjä niin aineisto lähetetään

//softa palauttaa $sisalto-muutujan

$sebfact_ulos = "";

if ($fakt_lahetys == "") {
  //maksuehto tekstinä
  $maksuehto     = t_tunnus_avainsanat($masrow, "teksti", "MAKSUEHTOKV");
  $kateistyyppi  = $masrow["kateinen"];

  require_once 'tulosta_lasku_text.inc';

  // haetaan tilauksen kaikki rivit
  $query = "SELECT *, concat(lpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0')) sorttauskentta
            FROM tilausrivi
            WHERE uusiotunnus='$laskurow[tunnus]' and yhtio='$kukarow[yhtio]'
            ORDER BY otunnus, sorttauskentta, tuoteno";
  $ffaktresult = pupe_query($query);

  //kuollaan jos yhtään riviä ei löydy
  if (mysqli_num_rows($ffaktresult) == 0) {
    echo t("VIRHE: Laskurivejä ei löytynyt");
  }
  // aloitellaan laskun teko
  $firstpage = text_alku();

  while ($row = mysqli_fetch_array($ffaktresult)) {

    // Rivin toimitusaika
    if ($yhtiorow["tilausrivien_toimitettuaika"] == 'K' and $row["keratty"] == "saldoton") {
      $row["toimitettuaika"] = $row["toimaika"];
    }
    elseif ($yhtiorow["tilausrivien_toimitettuaika"] == 'X') {
      $row["toimitettuaika"] = $row["toimaika"];
    }
    else {
      $row["toimitettuaika"] = $row["toimitettuaika"];
    }

    text_rivi();
  }

  text_alvierittely();
  text_loppu();
}

if ($fakt_lahetys != "" and $sisalto != '') {
  //keksitään uudelle failille joku varmasti uniikki nimi:
  $pdffilenimi  = "Autofakt-".mb_substr(md5(uniqid(rand(), true)), 0, 10).".txt";

  //tässa on kaikki failit jotka tarvitaan
  $bound = uniqid(time()."_") ;

  $header  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "UTF-8", "Q")." <$yhtiorow[postittaja_email]>\r\n";
  $header .= "MIME-Version: 1.0\r\n" ;
  $header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\r\n" ;

  $content .= "--$bound\r\n";
  $content .= "Content-Type: text/plain; name=\"$pdffilenimi\"\r\n";
  $content .= "Content-Transfer-Encoding: base64\r\n";
  $content .= "Content-Description: $pdffilenimi\r\n";
  $content .= "Content-Disposition: attachment; filename=\"$pdffilenimi\"\r\n\r\n";

  $content .= chunk_split(base64_encode($sisalto));
  $content .= "\r\n" ;

  $subjekti = $frow["sopimusnumero"]." ".$pdffilenimi;

  if ($frow["email"] == '' or !mail($frow["email"], mb_encode_mimeheader($subjekti, "UTF-8", "Q"), $content, $header, "-f $yhtiorow[postittaja_email]")) {
    $sebfact_ulos .= sprintf(t("VIRHE: Laskutuksen factoring-sähköpostisiirtoa ei voitu suorittaa osoitteeseen: %s"), $frow["email"])."<br>\r\n";
  }
  else {
    $sebfact_ulos .= sprintf(t("Laskutuksen factoring-sähköpostisiirto lähetettiin osoitteeseen: %s"), $frow["email"])."<br><br>\r\n";
  }

  //lähetetään meili myös yhtiön adminille
  mail($yhtiorow["alert_email"], mb_encode_mimeheader($subjekti, "UTF-8", "Q"), $content, $header, "-f $yhtiorow[postittaja_email]");

  //echotaan rudulle jos kyseessä ei ole batch-ajo
  if ($tulos_ulos == "") {
    echo $sebfact_ulos;
  }
}
