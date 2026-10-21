<?php

// MUOKKAUS: JWIO, Ikkunalaskurin ja Ovilaskurin "Lisää ja jatka" -painike.
// ----------------------------------------------------------------------------------------
// Lisää rivin Pupesoftissa auki olevalle tilaukselle tai tarjoukselle ilman, että laskurin
// (/hinnasto/) ikkuna suljetaan, jolloin seuraavan ikkunan tai oven voi syöttää heti.
//
// Rivi lisätään samalla lisaa_rivi()-funktiolla (inc/functions.inc -> lisaarivi.inc) kuin
// tilaus_myynti.php:n rivinsyötössä Lisää-napilla, joten hinta, alv, varaukset ja
// rivinumerot menevät samoin. Mallina rajapinnat/pupenext.php.
//
// Kutsu: POST, JSON-runko, otsake "X-Requested-With: jwio-hinnasto":
//   pupe_order  tilauksen tunnus (lasku.tunnus)
//   toim        tilaus_myynti.php:n toim, jolla tilaus on auki (null: päätellään tilasta)
//   tuoteno, kpl, hinta (veroton), nimitys, kommentti, rivinumero (tilaajan rivinumero)
//
// Käyttäjä tunnistetaan Pupesoftin istuntokeksistä (pupesoft_session) kuten
// parametrit.inc:ssä. Lisäksi:
//   - tilauksen pitää olla käyttäjällä auki (kuka.kesken), eli juuri se tilaus, jolta
//     laskuri avattiin, eikä mikään muu
//   - käyttäjällä pitää olla tilaus_myynti.php-oikeus samalla toim-arvolla
//   - otsaketta X-Requested-With ei voi asettaa toiselta sivustolta lähetetty lomake
//
// Vastaus: JSON { ok, virhe, rivitunnus, rivit, viestit }. viestit ovat lisaarivi.inc:n
// tulostamat ilmoitukset tekstinä (esim. saldo ei riitä), myös onnistuneesta lisäyksestä.
// ----------------------------------------------------------------------------------------

// Kaikki mitä includet tai lisaarivi.inc tulostavat, otetaan talteen eikä päästetä
// JSON-vastauksen sekaan:
ob_start();

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

if (!function_exists("jwio_vastaa")) {
  function jwio_vastaa($vastaus, $http_status = 200) {
    while (ob_get_level() > 0) {
      ob_end_clean();
    }

    http_response_code($http_status);
    echo json_encode($vastaus);
    exit;
  }
}

if (!function_exists("jwio_virhe")) {
  function jwio_virhe($viesti, $http_status = 400) {
    jwio_vastaa(array("ok" => false, "virhe" => $viesti), $http_status);
  }
}

if (!function_exists("jwio_puhdista")) {
  // Lomakkeen kentille tehdään sama kuin parametrit.inc:n pupe_register_globals()
  // UTF-8-tilassa, jolloin teksti päätyy riville täsmälleen kuten Lisää-napilla:
  function jwio_puhdista($teksti) {
    $src = array("\"", "'", "<", ">", "\\", ";");
    $rpl = array("″", "′", "", "", "", "");

    return trim(str_replace($src, $rpl, (string) $teksti));
  }
}

if (!function_exists("jwio_numero")) {
  function jwio_numero($arvo) {
    return (float) str_replace(",", ".", trim((string) $arvo));
  }
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
  jwio_virhe("Virheellinen pyyntö.", 405);
}

if (!isset($_SERVER["HTTP_X_REQUESTED_WITH"]) or $_SERVER["HTTP_X_REQUESTED_WITH"] != "jwio-hinnasto") {
  jwio_virhe("Virheellinen pyyntö.", 403);
}

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
  jwio_virhe("Virheellinen pyyntö.");
}

// otetaan includepath aina rootista
$pupe_root_polku = dirname(dirname(__FILE__));

ini_set("include_path",
  ini_get("include_path") . PATH_SEPARATOR .
  $pupe_root_polku        . PATH_SEPARATOR .
  "/usr/share/pear"       . PATH_SEPARATOR .
  "/usr/share/php/"       . PATH_SEPARATOR
);

require "inc/connect.inc";
require "inc/functions.inc";

if (!PUPE_UNICODE) {
  jwio_virhe("Pupesoft ei ole UTF-8-tilassa, rivin lisäys laskurista ei ole käytössä.", 500);
}

// --- Käyttäjä ---
$session = isset($_COOKIE["pupesoft_session"]) ? (string) $_COOKIE["pupesoft_session"] : "";

if (mb_strlen($session) != 25) {
  jwio_virhe("Et ole kirjautunut Pupesoftiin. Kirjaudu ja yritä uudelleen.", 401);
}

$query = "SELECT *
          FROM kuka
          WHERE session = '" . mysqli_real_escape_string($GLOBALS["link"],$session) . "'";
$result = pupe_query($query);

if (mysqli_num_rows($result) != 1) {
  jwio_virhe("Pupesoftin istunto on vanhentunut. Kirjaudu uudelleen ja yritä uudelleen.", 401);
}

$kukarow = mysqli_fetch_assoc($result);

if ($kukarow["extranet"] != "") {
  jwio_virhe("Toiminto ei ole käytössä extranetissä.", 403);
}

$yhtiorow = hae_yhtion_parametrit($kukarow["yhtio"]);

// --- Tilaus ---
$tilausnumero = (int) ($data["pupe_order"] ?? 0);

if ($tilausnumero <= 0) {
  jwio_virhe("Tilausnumero puuttuu.");
}

// Sama kuin tilaus_myynti.php: rivejä lisätään vain tilaukselle, joka on käyttäjällä auki:
if ((int) $kukarow["kesken"] != $tilausnumero) {
  jwio_virhe("Tilaus $tilausnumero ei ole sinulla auki Pupesoftissa. Avaa tilaus Pupesoftissa ja yritä uudelleen.", 409);
}

// Samat tilat kuin tilaus_myynti.php:ssä tilausta avattaessa:
$query = "SELECT laskun_lisatiedot.*, lasku.*
          FROM lasku
          LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio and laskun_lisatiedot.otunnus = lasku.tunnus)
          WHERE lasku.tunnus = '$tilausnumero'
          AND lasku.yhtio    = '{$kukarow['yhtio']}'
          AND lasku.tila     in ('G','S','C','T','V','N','E','L','0','R')
          AND (lasku.alatila != 'X' or lasku.tila = '0')";
$result = pupe_query($query);

if (mysqli_num_rows($result) != 1) {
  jwio_virhe("Tilausta $tilausnumero ei löydy tai se on mitätöity/laskutettu.", 404);
}

$laskurow = mysqli_fetch_assoc($result);

// --- Oikeus ---
// Tyhjä toim on myös toim, joten se päätellään tilauksen tilasta vain, jos laskuri ei
// tiennyt sitä (null, Pupesoftin ikkuna oli jo suljettu):
if (isset($data["toim"]) and is_string($data["toim"])) {
  $toim = preg_replace("/[^A-Z0-9_]/", "", mb_strtoupper($data["toim"]));
}
else {
  $toim = ($laskurow["tila"] == "T") ? "TARJOUS" : "RIVISYOTTO";
}

if (!tarkista_oikeus("tilaus_myynti.php", $toim)) {
  jwio_virhe("Sinulla ei ole oikeutta lisätä rivejä tälle tilaukselle (tilaus_myynti.php, $toim).", 403);
}

// --- Tuote ---
$tuoteno = jwio_puhdista($data["tuoteno"] ?? "");

if ($tuoteno == "") {
  jwio_virhe("Tuotenumero puuttuu.");
}

$query = "SELECT *
          FROM tuote
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND tuoteno = '" . mysqli_real_escape_string($GLOBALS["link"],$tuoteno) . "'";
$result = pupe_query($query);

if (mysqli_num_rows($result) != 1) {
  jwio_virhe("Tuotetta $tuoteno ei löydy Pupesoftista.", 404);
}

$trow = mysqli_fetch_assoc($result);

// --- Rivin tiedot ---
$kpl = jwio_numero($data["kpl"] ?? "");

if ($kpl <= 0) {
  jwio_virhe("Kappalemäärä puuttuu.");
}

$hinta = round(jwio_numero($data["hinta"] ?? ""), 6);
$rivinumero = (int) ($data["rivinumero"] ?? 0);

// lisaa_rivi() pitää tyhjää ja "0":aa samana eli käyttää silloin asiakkaan alennusta.
// Rivinsyöttölomake lähettää ale1 = 0, eli laskurin hinta ilman alennusta, joten tässä
// nolla, joka ei ole tyhjä:
$parametrit = array(
  "laskurow"     => $laskurow,
  "trow"         => $trow,
  "tuoteno"      => $trow["tuoteno"],
  "kpl"          => $kpl,
  "hinta"        => ($hinta > 0) ? (string) $hinta : "",
  "ale1"         => "0.00",
  "tuotenimitys" => jwio_puhdista($data["nimitys"] ?? ""),
  "kommentti"    => jwio_puhdista($data["kommentti"] ?? ""),
  "rivinumero"   => ($rivinumero > 0) ? $rivinumero : 0,
  "toim"         => $toim,
);

// Muut alekentät tyhjinä, kuten lomakkeelta:
for ($alepostfix = 2; $alepostfix <= $yhtiorow["myynnin_alekentat"]; $alepostfix++) {
  $parametrit["ale" . $alepostfix] = "";
}

$kukarow["kesken"] = $laskurow["tunnus"];

list($lisatyt_rivit1, $lisatyt_rivit2) = lisaa_rivi($parametrit);

// Mitä lisaarivi.inc ilmoitti, tekstinä:
$viestit = trim(preg_replace("/\s+/u", " ", html_entity_decode(strip_tags(
  str_ireplace(array("<br>", "<br/>", "<br />"), "\n", ob_get_contents())
), ENT_QUOTES, "UTF-8")));

$rivit = array_values(array_filter(array_merge(
  is_array($lisatyt_rivit1) ? $lisatyt_rivit1 : array(),
  is_array($lisatyt_rivit2) ? $lisatyt_rivit2 : array()
)));

if (count($rivit) == 0) {
  jwio_vastaa(array(
    "ok"      => false,
    "virhe"   => ($viestit != "") ? $viestit : "Pupesoft ei lisännyt riviä.",
    "viestit" => $viestit,
  ), 422);
}

jwio_vastaa(array(
  "ok"         => true,
  "rivitunnus" => (int) $rivit[0],
  "rivit"      => array_map("intval", $rivit),
  "viestit"    => $viestit,
));
