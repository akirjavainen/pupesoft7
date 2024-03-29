<?php

//## !!! HUOMHUOM !!! ###
//## Tämä ominaisuus on deprikoitu, käytä functions.inc:ssä määriteltyä pupesoft_sahkoposti -funktiota sähköpostin lähettämiseen!! ###

// Tämä ohjelma haluaa $liite muuttujan jossa on tiedostonnimi joka liitetään
// Ja $kutsu jossa on tiedoston kuvaus
// $content_body on viestin body
// $subject tai $content_subject on poikkeava subject

$varaposti      = '';
$varaposti_trig = FALSE;
if (!isset($sahkoposti_cc)) $sahkoposti_cc = "";
if (!isset($content_body))  $content_body  = "";

if (!isset($subject) or trim($subject) == '') {
  $subject = "$yhtiorow[nimi] - $kutsu";
}

if (isset($content_subject) and trim($content_subject) != "") {
  $subject = $content_subject;
}

if (isset($komento['Ostotilaus']) and mb_substr($komento['Ostotilaus'], 0, 15) == 'toimittajaemail') {
  $varaposti_trig    = TRUE;
  $varaposti         = $kukarow['eposti'];
  $kukarow['eposti'] = mb_substr($komento['Ostotilaus'], 15);
}

if (mb_substr($komento, 0, 12) == 'asiakasemail') {
  $varaposti_trig    = TRUE;
  $varaposti         = $kukarow['eposti'];
  $kukarow['eposti'] = mb_substr($komento, 12);
}

// Käydään läpi liitteet
if (!is_array($liite)) {
  $liite = array(0 => $liite);
  $avaimeksi = 0;
}
else {
  list($avaimeksi) = array_keys($liite);
}

if (!is_array($kutsu)) {
  $kutsu = array($avaimeksi => $kutsu);
}

$mail_liitteet = array();

foreach ($liite as $key => $value) {

  $path_parts = pathinfo($liite[$key]);

  $mail_liitteet[] = array(
    "filename"    => $liite[$key],
    "newfilename" => $kutsu[$key].".{$path_parts["extension"]}",
  );
}

if (isset($lisaliitteet)) {
  foreach ($lisaliitteet as $path => $nimi) {
    $mail_liitteet[] = array("filename" => $path, "newfilename" => $nimi);
  }
}

$content_body_ctype = "TEXT";

if (preg_match("/<[^<]+>/", $content_body)) {
  $content_body_ctype = "HTML";
}

$parametri = array(
  "to"           => $kukarow["eposti"],
  "cc"           => $sahkoposti_cc,
  "subject"      => $subject,
  "ctype"        => $content_body_ctype,
  "body"         => $content_body,
  "attachements" => $mail_liitteet,
);

$boob = pupesoft_sahkoposti($parametri);
if ($boob === FALSE) echo t("Sähköpostin lähetys epäonnistui").": $kukarow[eposti]<br>";

//Nää muuttujat voi olla aika isoja joten unsetataan ne
unset($content_body);
unset($content_subject);
unset($ctype);
if (mb_strpos($_SERVER['SCRIPT_NAME'], "tulostakopio.php") === FALSE) unset($komento);
unset($kutsu);
unset($liite);
unset($sahkoposti_cc);
unset($sisalto);
unset($subject);

if ($varaposti_trig) {
  $kukarow['eposti'] = $varaposti;
}
