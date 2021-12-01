<?php

// Kutsutaanko CLI:stÃ¤
if (php_sapi_name() != 'cli') {
  die ("TÃ¤tÃ¤ scriptiÃ¤ voi ajaa vain komentoriviltÃ¤!\n");
}

date_default_timezone_set('Europe/Helsinki');

if (trim($argv[1]) == '') {
  die ("Et antanut lÃ¤hettÃ¤vÃ¤Ã¤ yhtiÃ¶tÃ¤!\n");
}

if (trim($argv[2]) == '' or !is_numeric($argv[2])) {
  die ("Et antanut tilauksen tunnus!\n");
}

// lisÃ¤tÃ¤Ã¤n includepathiin pupe-root
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))));
ini_set("display_errors", 1);

error_reporting(E_ALL);

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi tÃ¤stÃ¤ skriptistÃ¤ kerrallaan
pupesoft_flock();

$yhtio = sanitize_string(trim($argv[1]));
$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow = hae_kukarow('admin', $yhtio);

if (empty($kukarow)) {
  exit("VIRHE: Admin kÃ¤yttÃ¤jÃ¤ ei lÃ¶ydy!\n");
}

luo_lasku_poikkeus_tilaukset($argv[2]);