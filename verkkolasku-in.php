<?php

// Kutsutaanko CLI:stä
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
  $php_cli = TRUE;
}

date_default_timezone_set("Europe/Helsinki");

if ($php_cli) {
  // otetaan includepath aina rootista
  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");
  error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
  ini_set("display_errors", 0);

  // otetaan tietokantayhteys
  require "inc/connect.inc";
  require_once "inc/functions.inc";
}

$lock_params = array(
  "locktime" => 5400,
);

// Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
pupesoft_flock($lock_params);

// Otetaan defaultit, jos ei olla yliajettu salasanat.php:ssä
$verkkolaskut_in     = empty($verkkolaskut_in)     ? "/home/verkkolaskut"        : rtrim($verkkolaskut_in, "/");
$verkkolaskut_ok     = empty($verkkolaskut_ok)     ? "/home/verkkolaskut/ok"     : rtrim($verkkolaskut_ok, "/");
$verkkolaskut_orig   = empty($verkkolaskut_orig)   ? "/home/verkkolaskut/orig"   : rtrim($verkkolaskut_orig, "/");
$verkkolaskut_error  = empty($verkkolaskut_error)  ? "/home/verkkolaskut/error"  : rtrim($verkkolaskut_error, "/");
$verkkolaskut_reject = empty($verkkolaskut_reject) ? "/home/verkkolaskut/reject" : rtrim($verkkolaskut_reject, "/");

// VIRHE: verkkolasku-kansiot on väärin määritelty!
if (!is_dir($verkkolaskut_in) or !is_writable($verkkolaskut_in)) exit;
if (!is_dir($verkkolaskut_ok) or !is_writable($verkkolaskut_ok)) exit;
if (!is_dir($verkkolaskut_orig) or !is_writable($verkkolaskut_orig)) exit;
if (!is_dir($verkkolaskut_error) or !is_writable($verkkolaskut_error)) exit;
if (!is_dir($verkkolaskut_reject) or !is_writable($verkkolaskut_reject)) exit;

$laskut     = $verkkolaskut_in;
$oklaskut   = $verkkolaskut_ok;
$origlaskut = $verkkolaskut_orig;
$errlaskut  = $verkkolaskut_error;

if ($php_cli || mb_strpos($_SERVER['SCRIPT_NAME'], "pankkiyhteys.php") !== FALSE) {
  // Ei tehdä mitään. Tälläset iffit on sitä parasta koodia.
}
elseif (mb_strpos($_SERVER['SCRIPT_NAME'], "tiliote.php") !== FALSE and $verkkolaskut_in != "" and $verkkolaskut_ok != "" and $verkkolaskut_orig != "" and $verkkolaskut_error != "") {
  //Pupesoftista
  echo "Aloitetaan verkkolaskun sisäänluku...<br>\n<br>\n";

  // Kopsataan uploadatta faili verkkolaskudirikkaan
  $copy_boob = copy($filenimi, $laskut."/".$userfile);

  if ($copy_boob === FALSE) {
    echo "Kopiointi epäonnistui $filenimi $laskut/$userfile<br>\n";
    exit;
  }
}
else {
  echo "Näillä ehdoilla emme voi ajaa verkkolaskujen sisäänlukua!";
  exit;
}

require "inc/verkkolasku-in.inc"; // täällä on itse koodi
require "inc/verkkolasku-in-erittele-laskut.inc"; // täällä pilkotaan Finvoiceaineiston laskut omiksi tiedostoikseen

// Käsitellään ensin kaikki Finvoicet
if ($handle = opendir($laskut)) {
  while (($file = readdir($handle)) !== FALSE) {
    if (!is_file($laskut."/".$file)) {
      continue;
    }

    $nimi = $laskut."/".$file;

    // Muutetaan oikeaan merkistöön
    $encoding = exec("file -b --mime-encoding '$nimi'");

    if (!PUPE_UNICODE and $encoding != "" and mb_strtoupper($encoding) != 'ISO-8859-15') {
      exec("recode -f $encoding..ISO-8859-15 '$nimi'");
    }
    elseif (PUPE_UNICODE and $encoding != "" and mb_strtoupper($encoding) != 'UTF-8') {
      exec("recode -f $encoding..UTF8 '$nimi'");
    }

    $luotiinlaskuja = erittele_laskut($nimi);

    // Jos tiedostosta luotiin laskuja siirretään se tieltä pois
    if ($luotiinlaskuja > 0) {

      // Logitetaan ajo
      cron_log($origlaskut."/".$file);

      rename($laskut."/".$file, $origlaskut."/".$file);
    }
  }
}

if ($handle = opendir($laskut)) {
  while (($file = readdir($handle)) !== FALSE) {
    if (!is_file($laskut."/".$file)) {
      continue;
    }

    // $yhtiorow ja $xmlstr
    unset($yhtiorow);
    unset($xmlstr);

    $nimi = $laskut."/".$file;
    $laskuvirhe = verkkolasku_in($nimi, TRUE);

    if ($laskuvirhe == "") {
      echo "Verkkolasku vastaanotettu onnistuneesti!<br>\n<br>\n";
      rename($laskut."/".$file, $oklaskut."/".$file);
    }
    else {
      echo "<font class='error'>Verkkolaskun vastaanotossa virhe:</font><br>\n<pre>$laskuvirhe</pre><br>\n";

      $alku = $loppu = "";
      list($alku, $loppu) = explode("####", $laskuvirhe);

      if (trim((string)$loppu) == "ASN") { // MUOKKAUS: (string)
        // ei tehdä mitään vaan annetaan jäädä roikkumaan kansioon seuraavaan kierrokseen saakka, tai kunnes joku lukee postit.
      }
      else {
        rename($laskut."/".$file, $errlaskut."/".$file);
      }
    }
  }
}

if ($php_cli) {
  // laitetaan käyttöoikeudet kuntoon
  system("chown -R :http $verkkolaskut_in; chmod -R 770 $verkkolaskut_in;");
}

// siivotaan yli 90 päivää vanhat aineistot
system("find $verkkolaskut_in -type f -mtime +90 -delete");
