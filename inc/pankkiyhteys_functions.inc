<?php

define('SEPA_ENVIRONMENT', 'production');
define('SEPA_ADDRESS', 'https://sepa.ahkio.com');
define('SEPA_ADDRESS_CROSSKEY', 'https://pft.poppankki.fi/CorporateFileService-v1'); // MUOKKAUS: Lisatty
define('SEPA_WSDL_CROSSKEY', 'https://pft.poppankki.fi/CertificateService-v1'); // MUOKKAUS: Lisatty varmennepalvelun osoite
//define('SEPA_ADDRESS_CROSSKEY', 'https://popstagepft.crosskey.fi/CorporateFileService-v1'); // MUOKKAUS: Test
//define('SEPA_WSDL_CROSSKEY', 'https://popstagepft.crosskey.fi/CertificateService-v1'); // MUOKKAUS: Test

include("/var/www/robrichards/vendor/robrichards/wse-php/src/WSSESoap.php");

use RobRichards\XMLSecLibs\XMLSecurityKey;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\WsePhp\WSSESoap;

// Käyttöliittymän funktiot

/**
 * Tarkistaa sepa-palvelimelta tulleen vastauksen koodin ja palauttaa true tai false sen
 * perusteella. Echottaa myos mahdollisen virheilmoituksen
 *
 * @param unknown $vastaus
 *
 * @return bool
 */


function vastaus_kunnossa($koodi) {
  switch ($koodi) {
  case 200:
    return true;
  case 400:
    virhe("Palvelimelta saatiin virheilmoitus, tarkista, että antamasi tiedot ovat kunnossa");
    return false;
  case 500:
    virhe("Pankki ei vastaa kyselyyn, yritä myöhemmin uudestaan");
    return false;
  case 503:
    virhe("Pankki ei vastaa kyselyyn toivotulla tavalla, yritä myöhemmin uudestaan");
    return false;
  case 0:
    virhe("Sepa-palvelimeen ei jostain syystä saada yhteyttä, yritä myöhemmin uudestaan");
    return false;
  default:
    virhe("Tuntematon virhekoodi");
    return false;
  }
}


/**
 * Piirtaa taulun palvelimelta ladatuille tiedostoille ja nayttaa siina tiedoston viitteen ja
 * statuksen
 *
 * @param unknown $tiedostot
 */
function tiedostot_table($tiedostot, $filelist) {
  echo "<table>";

  echo "<thead>";
  echo "<tr>";
  echo "<th>" . t("Pankkitili") . "</th>";
  echo "<th>" . t("Aineiston päiväys") . "</th>";
  echo "<th>" . t("Haun status") . "</th>";
  echo "</tr>";
  echo "</thead>";

  echo "<tbody>";

  // Pankkitiedostot on laskevassa päivämääräjärjestyksessä
  // Reversataan array, että näytetään aineistot aina vanhimmasta uusimpaan,
  // koska ne ajetaan sisään myös vanhimmasta uusimpaan
  $tiedostot = array_reverse($tiedostot);

  foreach ($tiedostot as $tiedosto) {

    // Haetaan tiedoston tiedot filelistasta, että saadaan päivämäärä ja tilinumero
    $tiedot = search_array_key_for_value_recursive($filelist, "fileReference", $tiedosto["viite"]);
    $_date = date("d.m.Y h:i:s", strtotime($tiedot[0]["fileTimestamp"]));
    $_tili = $tiedot[0]["serviceId"];

    echo "<tr class='aktiivi'>";
    echo "<td>{$_tili}</td>";
    echo "<td>{$_date}</td>";
    echo "<td>{$tiedosto["status"]}</td>";
    echo "</tr>";
  }

  echo "</tbody>";
  echo "</table>";

  echo "<br/><br/>";
}

function toggle_all($toggler_id, $toggled_class) {
  echo "
  <script type='text/javascript' language='javascript'>
    $(function () {
      $('#{$toggler_id}').toggle(
        function() {
          $('.{$toggled_class}').prop('checked', true);
        },
        function() {
          $('.{$toggled_class}').prop('checked', false);
        }
    );
    });
  </script>
  ";
}

function filelist_table($array, $type) {
  global $kukarow, $yhtiorow;

  if (!is_array($array) or count($array) == 0) {
    echo "<font class='message>";
    echo t("Yhtään %s-aineistoa ei ole saavavilla!", $kukarow['kieli'], $type);
    echo "</font>";
    return;
  }

  // Tiedostolista tulee files -arrayssa
  $tiedostot = $array['files'];

  // Sortataan array päivämäärän mukaan. Pitää ensiksi tehdä sortattavasta keystä oma array.
  $sort_by = array();

  foreach ($tiedostot as $apusort_key => $apusort_row) {
    $sort_by[$apusort_key] = $apusort_row['fileTimestamp'];
  }

  // Sortataan by date desc
  array_multisort($sort_by, SORT_DESC, $tiedostot);

  echo "<table class='full-width'>";

  echo "<thead>";
  echo "<tr>";
  echo "<th>" . t("Pankkitili") . "</th>";
  echo "<th>" . t("Aineiston päiväys") . "</th>";
  echo "<th>" . t("Status") . "</th>";
  echo "<th>" . t("Valitse") . "</th>";
  echo "</tr>";
  echo "</thead>";

  echo "<tbody>";

  foreach ($tiedostot as $tiedosto) {
    $_viite = $tiedosto["fileReference"];
    $_date = date("d.m.Y h:i:s", strtotime($tiedosto["fileTimestamp"]));
    $_status = $tiedosto["status"];

    // Ruksataan, jos tiedosto on uusi ja tehdään selkokielisempi
    if (strtolower($_status) == 'new') {
      $_chk = "checked";
      $_status = t("Uusi");
    }
    else {
      $_chk = '';
      $_status = t("Haettu");
    }

    // Kaikki pankit ei osaa näyttää tilinumeroa, näytetään sillo filereference
    $_tilinumero = $tiedosto["serviceId"] != "" ? $tiedosto["serviceId"] : $tiedosto["fileReference"];

    echo "<tr class='aktiivi'>";
    echo "<td>{$_tilinumero}</td>";
    echo "<td>{$_date}</td>";
    echo "<td>{$_status}</td>";
    echo "<td>";
    echo "<input type='checkbox' {$_chk} value='{$_viite}' name='{$type}_references[]' class='{$type}_boxes'>";
    echo "</td>";
    echo "</tr>";
  }

  echo "<tr>";
  echo "<th colspan='3'>";
  echo t("Ruksaa kaikki");
  echo "</th>";
  echo "<td>";
  echo "<input type='checkbox' id='{$type}_toggler'>";
  echo "</td>";
  echo "</tr>";

  echo "</tbody>";
  echo "</table>";
}

function kasittele_pain_palaute($pain_data) {
  global $kukarow, $yhtiorow;

  // Otetaan sisään base64enkoodattu data
  // Palautetaan array, jossa "status" ja "selite"

  // Dekoodataan data
  $data = base64_decode($pain_data);

  // Jos data on tyhjää
  if ($pain_data == "" or $data == "") {
    return array(
      "status" => t("Virhe"),
      "selite" => t("Palute oli tyhjä!"),
    );
  }

  // Parsitaan XML
  $xml = simplexml_load_string($data);

  // Jos xml on virheellinen
  if ($xml === false) {
    return array(
      "status" => t("Virhe"),
      "selite" => t("Virheellinen XML palaute!"),
    );
  }

  // Statuksia:
  // Hyväksytty maksatukseen (Group status ACCP)
  // Hyväksytty tekninen tarkastus (Group status ACTC)
  // Cancellation is accepted (ACCR)
  // AcceptedSettlementInProcess (ACSP)
  // Settlement on the debtor's account has been completed (ACSC)
  // Rejected transaction (Transaction status RJCT)
  $_ok = array("ACCP", "ACTC", "ACSC", "ACCR", "ACSP");
  $_error = array("RJCT");

  // Status voi tulla näissä elementeissä
  $_status = array(
    (string) $xml->{"pain.002.001.02"}->OrgnlGrpInfAndSts->GrpSts,
    (string) $xml->{"pain.002.001.02"}->TxInfAndSts->TxSts,
  );

  // Virheviesti voi tulla näissä elementeissä
  $_message = array(
    (string) $xml->{"pain.002.001.02"}->OrgnlGrpInfAndSts->StsRsnInf->AddtlStsRsnInf,
    (string) $xml->{"pain.002.001.02"}->TxInfAndSts->StsRsnInf->AddtlStsRsnInf,
  );

  // Intesect palauttaa arrayn elementeistä jotka matchaa, eli
  // jos joku löytyneistä status elementeistä löytyy ok arraystä,
  // niin tiedetään, että aineisto oli ok.
  if (count(array_intersect($_status, $_ok)) > 0) {
    $status = t("Ok");
    $selite = t("Aineisto OK");
  }
  elseif (count(array_intersect($_status, $_error)) > 0) {
    // Reversesortataan arrayt niin tyhjät menee loppuun
    // ja meillä on ekassa elementissä aina oikea arvo
    rsort($_message);

    // Jos meillä oli joku virhekoodi, selitteeseen virheviesti
    $status = t("Virhe");
    $selite = $_message[0];
  }
  else {
    // Reversesortataan arrayt niin tyhjät menee loppuun
    // ja meillä on ekassa elementissä aina oikea arvo
    rsort($_status);

    // Jos ei tunnistettu statuskoodia, pistetään koko data selitteeseen
    $status = $_status[0];
    $selite = htmlentities($data);
  }

  return array(
    "status" => $status,
    "selite" => $selite,
  );
}


/**
 *
 * @param string  $viesti
 */
function virhe($viesti) {
  global $kukarow, $yhtiorow;

  echo "<font class='error'>";
  echo t($viesti);
  echo "</font>";
  echo "<br/>";
}


/**
 *
 * @param string  $viesti
 */
function ok($viesti) {
  global $kukarow, $yhtiorow;

  echo "<font class='ok'>";
  echo t($viesti);
  echo "</font>";
  echo "<br/>";
}


/**
 *
 * @param string  $viesti
 */
function viesti($viesti) {
  global $kukarow, $yhtiorow;

  echo "<font class='message'>";
  echo t($viesti);
  echo "</font>";
  echo "<br/>";
}

function sepa_pankkiyhteys_kunnossa() {
  global $yhtiorow, $kukarow, $sepa_pankkiyhteys_token;

  if (SEPA_PANKKIYHTEYS === false) {
    echo "<font class='error'>";
    echo t("SEPA-palvelua ei ole aktivoitu.");
    echo "<br>";

    if (empty($sepa_pankkiyhteys_token)) {
      echo "<br>";
      echo t("Sepa palvelun tunniste on asentamatta!");
    }

    if (!isset($_SERVER["HTTPS"]) or $_SERVER["HTTPS"] != 'on') {
      echo "<br>";
      echo t("Voit käyttää pankkiyhteyttä vain salatulla yhteydellä!");
    }

    if ($yhtiorow['pankkitiedostot'] != "F") {
      echo "<br>";
      echo t("Voit käyttää pankkiyhteyttä vain pankkitiedostot -parametrillä");
      echo ": \"";
      echo t("Päivät yhdistetään, yksi tiedosto per pankki");
      echo "\"!";
    }

    echo "</font>";

    exit;
  }
}
// Pankkiyhteys tietokantafunktiot

/**
 * Hakee pankkiyhteydet, joita ei ole viela luotu kyseiselle yhtiolle
 *
 * @return array
 */
function mahdolliset_pankkiyhteydet() {
  global $kukarow, $yhtiorow;

  $pankit = tuetut_pankit();

  $luodut_pankit = array();

  foreach (hae_pankkiyhteydet() as $pankkiyhteys) {
    array_push($luodut_pankit, $pankkiyhteys["pankki"]);
  }

  $mahdolliset_pankkiyhteydet = array();

  foreach ($pankit as $bic => $nimet) {
    if (!in_array($bic, $luodut_pankit)) {
      $mahdolliset_pankkiyhteydet[$bic] = $nimet["nimi"];
    }
  }

  return $mahdolliset_pankkiyhteydet;
}


// SEPA API fuktiot

function sepa_download_file_list($params) {
  global $kukarow, $yhtiorow, $sepa_pankkiyhteys_token;

  $tunnus       = parameter($params["pankkiyhteys_tunnus"]);
  $salasana     = parameter($params["pankkiyhteys_salasana"]);
  $file_type    = parameter($params["file_type"]);
  $status       = parameter($params["status"]);
  $target_id    = parameter($params["target_id"]);
  $pankkiyhteys = hae_pankkiyhteys_ja_pura_salaus($tunnus, $salasana);

  if (!$pankkiyhteys or empty($file_type) or empty($status)) {
    return false;
  }

  $bank = $pankkiyhteys['bank'];

  // Varmistetaan, että ei kutsuta väärällä filetypellä
  $file_type = sepa_file_type_check($bank, $file_type);

  // Nordealla, pitää hakea filetypen "target_id"
  // Haetaan vaan jos ei ole passattu parametrinä
  if ($bank == "nordea" and $target_id == "") {
    $params["file_type"] = $file_type;

    $target_id = sepa_target_id_for_file_type($params);

    if ($target_id === false) {
      return false;
    }
  }

  $parameters = array(
    "method"  => "POST",
    "data"    => array(
      "own_signing_certificate"     => $pankkiyhteys["signing_certificate"],
      "signing_private_key"         => $pankkiyhteys["signing_private_key"],
      "bank_encryption_certificate" => $pankkiyhteys["bank_encryption_certificate"],
      "encryption_private_key"      => $pankkiyhteys["encryption_private_key"],
      "customer_id"                 => $pankkiyhteys["customer_id"],
      "target_id"                   => $target_id,
      "file_type"                   => $file_type,
      "status"                      => $status,
      "environment"                 => SEPA_ENVIRONMENT,
    ),
    "url" => SEPA_ADDRESS . "/" . bank_provider($bank) . "/download_file_list", // ZEPA
    //"url" => SEPA_ADDRESS, // ZEPA
    //"url" => SEPA_ADDRESS . "/download_file_list", // ZEPA
    "headers" => array(
      "Content-Type: application/json",
      "Authorization: Token token={$sepa_pankkiyhteys_token}"
    )
  );

  list($code, $response) = pupesoft_rest($parameters);

  if (!vastaus_kunnossa($code)) {
    return false;
  }

  // Lisätään target id_responseen, niin ei tartte hakea uudestaan jos kutsutaan peräkkäin
  $response["target_id"] = $target_id;

  return $response;
}

// MUOKKAUS: Lisätty suora tuki POP Pankille:
// ---
function sepa_download_file_list_crosskey(array $params): array | bool {
  global $kukarow, $yhtiorow, $sepa_pankkiyhteys_token;

  ini_set('display_errors', '1');
  ini_set('display_startup_errors', '1');
  error_reporting(E_ALL & ~E_DEPRECATED);

  // Noudetaan pankkiyhteyteen vaaditut tiedot tietokannasta:
  $tunnus = parameter($params["pankkiyhteys_tunnus"]);
  $salasana = parameter($params["pankkiyhteys_salasana"]);
  $pankkiyhteys = hae_pankkiyhteys($tunnus);

  if (!$pankkiyhteys) {
  return false;
  }

  // Tarkistetaan salasana ja haetaan salaamaton private key:
  $_c = pura_salaus($pankkiyhteys["signing_certificate"], $salasana);
  $_pk = pura_salaus($pankkiyhteys["signing_private_key"], $salasana);
  $_ssl = pura_salaus($pankkiyhteys["encryption_private_key"], $salasana);
  $_ssl_cert = pura_salaus($pankkiyhteys["encryption_certificate"], $salasana);

  // Jos salauksen purkaminen ei onnistu, lopetetaan tähän:
  if (!openssl_pkey_get_private($_pk) || !openssl_pkey_get_private($_ssl)) {
    return false;
  }

  // 1. Luodaan POP Pankin vaatima ApplicationRequest-rakenne. Ei Id-attribuuttia juurella --
  // Crosskeyn oma esimerkkipyyntö ei sellaista käytä, ks. alla:
  $xmlPayload = '<?xml version="1.0" encoding="UTF-8"?>' .
  '<ApplicationRequest xmlns="http://bxd.fi/xmldata/">' .
    '<CustomerId>' . $pankkiyhteys['customer_id'] . '</CustomerId>' .
    '<Command>DownloadFileList</Command>' .
    '<Timestamp>' . gmdate("c") . '</Timestamp>' .
    '<Status>NEW</Status>' .
    '<Environment>' . strtoupper(SEPA_ENVIRONMENT) . '</Environment>' .
    '<SoftwareId>Pupesoft</SoftwareId>' .
    '<FileType>0130</FileType>' .
  '</ApplicationRequest>';

  $doc = new DOMDocument('1.0', 'UTF-8');
  $doc->preserveWhiteSpace = false;
  $doc->loadXML($xmlPayload);

  // 2. Alustetaan allekirjoitus. Nämä kolme (kanonisointi, digest, allekirjoitusalgoritmi)
  // on nyt asetettu vastaamaan tarkasti Crosskeyn omaa esimerkkipyyntöä
  // (dowloadFileList-application-request_1.xml): C14N WithComments, SHA-1 digest,
  // RSA-SHA1 allekirjoitus -- ei Exclusive C14N eikä SHA-256, joita täällä käytettiin ennen.
  $objDSig = new XMLSecurityDSig();
  $objDSig->setCanonicalMethod(XMLSecurityDSig::C14N_COMMENTS);

  // 2.1. $doc (ei $rootElement) addReference():n ensimmäisenä parametrina: xmlseclibs
  // kirjoittaa URI-attribuutin tyhjäksi (URI="") vain kun kohde on DOMDocument, ja force_uri
  // on tosi. $rootElement-kohteella se sen sijaan aina generoi ja liittää oman Id:n
  // (URI="#..."), mitä esimerkkipyynnössä ei ole -- sen <Reference URI=""> allekirjoittaa
  // koko dokumentin ilman erillistä Id:tä.
  $objDSig->addReference(
    $doc,
    XMLSecurityDSig::SHA1,
    [
      'http://www.w3.org/2000/09/xmldsig#enveloped-signature'
    ],
    [
      'force_uri' => true
    ]
  );

  // 3. Avaimen lataus:
  $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, ['type' => 'private']);
  $objKey->loadKey($_pk);

  // Allekirjoitus
  $objDSig->sign($objKey);

  // 4. Sertifikaatin lisäys (PEM-otsikoiden kera, add509Cert() siivoaa ne pois).
  // issuerSerial => true lisää X509IssuerSerial-lohkon, kuten esimerkkipyynnössä:
  $objDSig->add509Cert($_c, true, false, ['issuerSerial' => true]);

  // 5. Liitetään Signature loppuun:
  $objDSig->appendSignature($doc->documentElement);

  // Otetaan lopullinen string ulos ilman mitään käsittelyä sen jälkeen:
  $requestBody = $doc->saveXML();

  // 6. Allekirjoitettu raaka XML ($requestBody) muutetaan Base64-muotoon:
  $base64Payload = base64_encode(trim($requestBody));

  // 7. Luodaan uniikki RequestId (pankki käyttää tätä duplikaattitunnistukseen):
  $requestId = time() . rand(100, 999);
  $timestamp = date('Y-m-d\TH:i:s.vP'); // Muoto: 2026-06-30T14:15:00.000+03:00

  // 8. Rakennetaan virallinen Finanssialan WS-standardin mukainen kuori.
  // HUOM: Operaatio on 'downloadFileListin' (ei 'downloadFileList' -- ApplicationRequestin
  // <Command> on silti 'DownloadFileList', nämä kaksi ovat eri asioita). Tämä ja koko
  // WS-Security-osuus alla on nyt tarkistettu Crosskeyn omasta esimerkkipyynnöstä
  // (dowloadFileList-SOAP-request_1.xml): Header ei saa jäädä tyhjäksi
  // ('<soapenv:Header/>', kuten se oli täällä ennen) -- pankki vaatii wsse:Security-lohkon,
  // jossa on wsu:Timestamp ja erillinen ds:Signature Timestampin ja Bodyn ylle (eri
  // allekirjoitus kuin ApplicationRequestin oma, yllä).
  $soapEnvelopeXml = '<?xml version="1.0" encoding="UTF-8"?>' .
  '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cor="http://bxd.fi/CorporateFileService" xmlns:mod="http://model.bxd.fi">' .
    '<soapenv:Header/>' .
    '<soapenv:Body>' .
      '<cor:downloadFileListin>' .
         '<mod:RequestHeader>' .
           '<mod:SenderId>' . $pankkiyhteys['customer_id'] . '</mod:SenderId>' . // Pankin asiakastunnus
            '<mod:RequestId>' . $requestId . '</mod:RequestId>' .
            '<mod:Timestamp>' . $timestamp . '</mod:Timestamp>' .
            '<mod:Language>FI</mod:Language>' .
            '<mod:UserAgent>Pupesoft</mod:UserAgent>' .
            '<mod:ReceiverId>POPFFI2</mod:ReceiverId>' . // POP Pankin BIC, varmistettu oikeaksi
         '</mod:RequestHeader>' .
         '<mod:ApplicationRequest>' . $base64Payload . '</mod:ApplicationRequest>' .
      '</cor:downloadFileListin>' .
    '</soapenv:Body>' .
  '</soapenv:Envelope>';

  $soapDoc = new DOMDocument('1.0', 'UTF-8');
  $soapDoc->loadXML($soapEnvelopeXml);

  // 8.1. WSSESoap (jo ladattu tiedoston alussa, mutta ei aiemmin käytetty tässä
  // funktiossa) lisää wsse:Security-lohkon ja allekirjoittaa sen. BinarySecurityToken
  // lisätään vasta allekirjoituksen JÄLKEEN: signSoapDoc() allekirjoittaa kaikki sillä
  // hetkellä Security-lohkossa olevat solmut plus Bodyn, ja esimerkkipyynnössä on
  // allekirjoitettuna vain Timestamp ja Body -- ei BinarySecurityTokenia itseään.
  $wsse = new WSSESoap($soapDoc);
  $wsse->addTimestamp(3600);

  $objSoapKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, ['type' => 'private']);
  $objSoapKey->loadKey($_pk);
  $wsse->signSoapDoc($objSoapKey, ['algorithm' => XMLSecurityDSig::SHA1]);

  $soapToken = $wsse->addBinaryToken($_c);
  $wsse->attachTokentoSig($soapToken);

  $soapEnvelope = $wsse->saveXML();

  // 9. Palvelin-osoite (vaihda tarvittaessa testipalvelimen osoite tähän):
  $endpointUrl = SEPA_ADDRESS_CROSSKEY;

  // 10. Alustetaan cURL
  $ch = curl_init($endpointUrl);

  // 11. SOAPAction-header. Operaationimi 'downloadFileListin' (ks. yllä):
  $headers = [
    'Content-Type: text/xml; charset=utf-8',
    'Content-Length: ' . strlen($soapEnvelope),
    //'SOAPAction: ""'
    'SOAPAction: "http://bxd.fi/CorporateFileService/downloadFileListin"'
  ];

  // 12. cURL:
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $soapEnvelope);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);

  // Varmista mTLS-varmennekättely (POP Pankki vaatii tämän lähes poikkeuksetta).
  // Salauspari ($_ssl_cert/$_ssl) on se, jonka pankki vahvistaa yhteyden avauksessa:
  curl_setopt($ch, CURLOPT_SSLCERT_BLOB, $_ssl_cert);
  curl_setopt($ch, CURLOPT_SSLKEY_BLOB, $_ssl);

  // Suoritetaan pyyntö:
  $response = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $curlError = curl_errno($ch) ? curl_error($ch) : "";
  curl_close($ch);

  // 13. cURL-virhe:
  if ($curlError !== "") {
    echo "cURL virhe: " . $curlError . PHP_EOL;
    echo "cURL vastaus: " . $response . PHP_EOL;
    return false;
  }

  // HTTP 500 osoittautui sisältävän oikean ApplicationResponsen tälle pankille -- ks.
  // "Sopimus ei voimassa" -tapaus. HTTP-status ei siis ratkaise onnistumista, vaan se onko
  // SOAP-vastauksessa ApplicationResponse ylipäätään:
  $responseDoc = sepa_crosskey_extract_application_response($response);

  if ($responseDoc === null) {
    echo "Palvelinvirhe (HTTP-koodi: $httpCode)" . PHP_EOL;
    echo $response . PHP_EOL;
    return false;
  }

  $xpath = new DOMXPath($responseDoc);
  $xpath->registerNamespace('bxd', 'http://bxd.fi/xmldata/');

  $responseCode = (string)$xpath->evaluate('string(//bxd:ResponseCode)');
  $responseText = (string)$xpath->evaluate('string(//bxd:ResponseText)');

  echo "Pankki vastasi: $responseCode $responseText" . PHP_EOL;

  if ($responseCode !== '00') {
    return false;
  }

  // 14. Palautusmuoto sama kuin vanhalla, Ahkion kautta kulkevalla
  // sepa_download_file_list():lla -- ks. filelist_table() ja
  // sepa_lataa_kaikki_uudet_tiedostot(), jotka lukevat tätä samaa "files"/"fileReference"/
  // "fileTimestamp"/"status"-muotoa riippumatta siitä kumpi funktio sen tuotti.
  // serviceId jää tyhjäksi: FileDescriptorilla ei ole tälle vastinetta, ja kutsujat jo
  // varautuvat siihen olemalla tyhjä (fallback fileReferenceen):
  $files = array();

  foreach ($xpath->query('//bxd:FileDescriptors/bxd:FileDescriptor') as $node) {
    $files[] = array(
      "fileReference" => (string)$xpath->evaluate('string(bxd:FileReference)', $node),
      "fileTimestamp" => (string)$xpath->evaluate('string(bxd:FileTimestamp)', $node),
      "status" => (string)$xpath->evaluate('string(bxd:Status)', $node),
      "serviceId" => ""
    );
  }

  // 15. Valmis!
  return array(
    "files" => $files,
    "target_id" => ""
  );
}
// ---

// ----------------------------------------------------------------------------------------
// Etsii ApplicationResponsen Crosskeyn SOAP-vastauksesta ja palauttaa sen omana
// DOMDocumenttina, kummassa tahansa muodossa jonka Crosskeyn omat esimerkit näyttävät
// sille: itsenäisenä dokumenttina Base64-koodattuna elementin tekstinä (vahvistettu muoto
// molemmilla latausoperaatioilla), tai suoraan sisäkkäisinä oikeina lapsielementteinä
// (uploadFilen esimerkki -- ks. kommentti sepa_upload_file_crosskey():n kohdalla siitä
// miksi se esimerkki on epävarmempi). Etsitään pelkän paikallisen nimen perusteella,
// välittämättä siitä mitä etuliitettä tai oletusnimiavaruutta vastaus sattuu käyttämään,
// koska sekin on jo vaihdellut esimerkkien välillä.
//
// Palauttaa null, jos ApplicationResponse-nimistä elementtiä ei löydy, tai sen sisältö ei
// jäsenny kummassakaan muodossa -- kutsuja näyttää tällöin raa'an SOAP-vastauksen sellaisenaan:
function sepa_crosskey_extract_application_response(string $soapResponseXml): ?DOMDocument {
  $soapDoc = new DOMDocument();

  if ($soapResponseXml === "" || !@$soapDoc->loadXML($soapResponseXml)) {
    return null;
  }

  $xpath = new DOMXPath($soapDoc);
  $nodes = $xpath->query('//*[local-name()="ApplicationResponse"]');

  if ($nodes === false || $nodes->length === 0) {
    return null;
  }

  $node = $nodes->item(0);

  foreach ($node->childNodes as $child) {
    if ($child->nodeType === XML_ELEMENT_NODE) {
      // Jo oikeita lapsielementtejä: solmusta itsestään tulee uuden dokumentin juuri:
      $responseDoc = new DOMDocument();
      $responseDoc->appendChild($responseDoc->importNode($node, true));
      return $responseDoc;
    }
  }

  // Muuten: Base64-tekstiä, puretaan omaksi täydeksi dokumentiksi:
  $decoded = base64_decode(trim($node->textContent), true);

  if ($decoded === false) return null;

  $responseDoc = new DOMDocument();

  if (!@$responseDoc->loadXML($decoded)) return null;

  return $responseDoc;
}
// ----------------------------------------------------------------------------------------

// ----------------------------------------------------------------------------------------
// Lataa yhden tai useamman tiedoston Crosskeystä niiden fileReferencen perusteella (kuten
// sepa_download_file_list_crosskey() ne palauttaa). Yksi pyyntö per viite, samoin kuin
// vanha, Ahkion kautta kulkeva sepa_download_files() alempana tekee ja samasta syystä:
// Crosskeyn oma esimerkki (dowloadFile-application-request_1.xml) näyttää vain yhden
// <FileReference>:n kerrallaan, joten useamman pyytämistä samassa <FileReferences>-lohkossa
// -- minkä skeema kyllä sallisi -- ei ole tosiasiassa vahvistettu toimivaksi, ja yksi pyyntö
// per tiedosto poistaa arvailun tarpeen.
//
// Palauttaa saman muodon kuin sepa_download_files(): listan, jossa yksi rivi per viite,
// "file_type"/"viite"/"status" ("OK" tai "Virhe")/"data" (Base64, kuten vanhan funktion oma
// "data" -- ks. tiedostot_table() ja kutsupaikat pankkiyhteys_tilioteviite.inc:ssä, jotka
// tekevät sille itse base64_decode()n). false vain silloin kun pyyntöä ei voida edes
// yrittää (parametri puuttuu, väärä salasana, pankkiyhteyttä ei löydy); yhden viitteen
// epäonnistuminen omalla pankkikutsullaan näkyy sen omalla rivillä "status" => "Virhe", kuten
// vanhalla funktiolla, kaatamatta koko loppulistaa mukanaan.
//
// Itse pyynnön rakenne -- sekä ApplicationRequestin oma allekirjoitus että SOAP-kuoren
// WS-Security -- on kopioitu Crosskeyn omista esimerkeistä
// (dowloadFile-application-request_1.xml / dowloadFile-SOAP-request_1.xml), samaan tapaan
// kuin sepa_download_file_list_crosskey() yllä.
// Kaksi asiaa on tarkoituksella eri tavalla kuin kyseisessä funktiossa, ei vahingossa, koska
// esimerkit ovat niistä eri mieltä:
//   - ApplicationRequestin allekirjoitus käyttää tässä plain C14N:ää (ei WithComments),
//     kun downloadFileListin käyttää C14N WithCommentsia.
//   - SOAP-kuoressa ei ole lainkaan WS-Security Timestampia tässä -- vain Body
//     allekirjoitetaan, yksi Reference, ei "Timestamp + Body" kuten downloadFileListillä.
function sepa_download_file_crosskey(array $params): array | bool {
  global $kukarow, $yhtiorow, $sepa_pankkiyhteys_token;

  ini_set('display_errors', '1');
  ini_set('display_startup_errors', '1');
  error_reporting(E_ALL & ~E_DEPRECATED);

  $tunnus = parameter($params["pankkiyhteys_tunnus"]);
  $salasana = parameter($params["pankkiyhteys_salasana"]);
  $file_type = parameter($params["file_type"]);
  $start_date = parameter($params["start_date"] ?? "");
  $viitteet = $params["viitteet"] ?? array();

  if (!is_array($viitteet)) $viitteet = array($viitteet);
  $viitteet = array_values(array_unique(array_filter($viitteet, function ($v) {
    return !empty((string)$v);
  })));

  if (empty($file_type) || count($viitteet) === 0) {
    return false;
  }

  // Ei varmistettu esimerkillä -- standardi rajaa tällä minkä tiedostojen viitteeksi
  // FileReference voi ylipäätään täsmätä, mutta Crosskeyn oma esimerkki näyttää vain
  // yhden, jo tiedetyn päivämäärän. 30 päivää taaksepäin on riittävän väljä "juuri
  // tiedostolistalta saatu viite" -tapaukseen ilman että väärä oletus pääsisi haittaamaan:
  if (empty($start_date)) {
    $start_date = date("Y-m-d", strtotime("-30 days"));
  }

  $pankkiyhteys = hae_pankkiyhteys($tunnus);

  if (!$pankkiyhteys) {
    return false;
  }

  // Tarkistetaan salasana ja haetaan salaamaton private key:
  $_c = pura_salaus($pankkiyhteys["signing_certificate"], $salasana);
  $_pk = pura_salaus($pankkiyhteys["signing_private_key"], $salasana);
  $_ssl = pura_salaus($pankkiyhteys["encryption_private_key"], $salasana);
  $_ssl_cert = pura_salaus($pankkiyhteys["encryption_certificate"], $salasana);

  if (!openssl_pkey_get_private($_pk) || !openssl_pkey_get_private($_ssl)) {
    return false;
  }

  $tiedostot = array();

  foreach ($viitteet as $viite) {
    echo "Haetaan tiedosto $viite..." . PHP_EOL;

    // 1. ApplicationRequest:
    $xmlPayload = '<?xml version="1.0" encoding="UTF-8"?>' .
    '<ApplicationRequest xmlns="http://bxd.fi/xmldata/">' .
      '<CustomerId>' . $pankkiyhteys['customer_id'] . '</CustomerId>' .
      '<Command>DownloadFile</Command>' .
      '<Timestamp>' . gmdate("c") . '</Timestamp>' .
      '<StartDate>' . $start_date . '</StartDate>' .
      '<Environment>' . strtoupper(SEPA_ENVIRONMENT) . '</Environment>' .
      '<FileReferences>' .
        '<FileReference>' . htmlspecialchars((string)$viite, ENT_XML1, 'UTF-8') . '</FileReference>' .
      '</FileReferences>' .
      '<Compression>false</Compression>' .
      '<SoftwareId>Pupesoft</SoftwareId>' .
      '<FileType>' . $file_type . '</FileType>' .
    '</ApplicationRequest>';

    $doc = new DOMDocument('1.0', 'UTF-8');
    $doc->preserveWhiteSpace = false;
    $doc->loadXML($xmlPayload);

    // 2. Allekirjoitus. Plain C14N (ei WithComments), SHA-1 digest, RSA-SHA1 -- Crosskeyn
    // oman dowloadFile-application-request_1.xml-esimerkin mukaisesti:
    $objDSig = new XMLSecurityDSig();
    $objDSig->setCanonicalMethod(XMLSecurityDSig::C14N);

    // $doc (ei $doc->documentElement) addReference():n ensimmäisenä parametrina: xmlseclibs
    // kirjoittaa URI-attribuutin tyhjäksi (URI="") vain kun kohde on DOMDocument -- ks.
    // tarkempi selitys sepa_download_file_list_crosskey():n vastaavasta kohdasta:
    $objDSig->addReference(
      $doc,
      XMLSecurityDSig::SHA1,
      [
        'http://www.w3.org/2000/09/xmldsig#enveloped-signature'
      ],
      [
        'force_uri' => true
      ]
    );

    $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, ['type' => 'private']);
    $objKey->loadKey($_pk);
    $objDSig->sign($objKey);

    $objDSig->add509Cert($_c, true, false, ['issuerSerial' => true]);
    $objDSig->appendSignature($doc->documentElement);

    $requestBody = $doc->saveXML();
    $base64Payload = base64_encode(trim($requestBody));

    $requestId = time() . rand(100, 999);
    $timestamp = date('Y-m-d\TH:i:s.vP');

    // 3. SOAP-kuori. Operaatio on 'downloadFilein'. Ei WS-Security Timestampia lainkaan --
    // vain Body allekirjoitetaan, Crosskeyn oman dowloadFile-SOAP-request_1.xml-esimerkin
    // mukaisesti:
    $soapEnvelopeXml = '<?xml version="1.0" encoding="UTF-8"?>' .
    '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cor="http://bxd.fi/CorporateFileService" xmlns:mod="http://model.bxd.fi">' .
      '<soapenv:Header/>' .
      '<soapenv:Body>' .
        '<cor:downloadFilein>' .
           '<mod:RequestHeader>' .
             '<mod:SenderId>' . $pankkiyhteys['customer_id'] . '</mod:SenderId>' .
              '<mod:RequestId>' . $requestId . '</mod:RequestId>' .
              '<mod:Timestamp>' . $timestamp . '</mod:Timestamp>' .
              '<mod:Language>FI</mod:Language>' .
              '<mod:UserAgent>Pupesoft</mod:UserAgent>' .
              '<mod:ReceiverId>POPFFI2</mod:ReceiverId>' .
           '</mod:RequestHeader>' .
           '<mod:ApplicationRequest>' . $base64Payload . '</mod:ApplicationRequest>' .
        '</cor:downloadFilein>' .
      '</soapenv:Body>' .
    '</soapenv:Envelope>';

    $soapDoc = new DOMDocument('1.0', 'UTF-8');
    $soapDoc->loadXML($soapEnvelopeXml);

    // WSSESoap: ei addTimestamp()-kutsua tälle operaatiolle, ks. yllä:
    $wsse = new WSSESoap($soapDoc);

    $objSoapKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, ['type' => 'private']);
    $objSoapKey->loadKey($_pk);
    $wsse->signSoapDoc($objSoapKey, ['algorithm' => XMLSecurityDSig::SHA1]);

    $soapToken = $wsse->addBinaryToken($_c);
    $wsse->attachTokentoSig($soapToken);

    $soapEnvelope = $wsse->saveXML();

    $endpointUrl = SEPA_ADDRESS_CROSSKEY;
    $ch = curl_init($endpointUrl);

    $headers = [
      'Content-Type: text/xml; charset=utf-8',
      'Content-Length: ' . strlen($soapEnvelope),
      'SOAPAction: "http://bxd.fi/CorporateFileService/downloadFilein"'
    ];

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $soapEnvelope);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    curl_setopt($ch, CURLOPT_SSLCERT_BLOB, $_ssl_cert);
    curl_setopt($ch, CURLOPT_SSLKEY_BLOB, $_ssl);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_errno($ch) ? curl_error($ch) : "";
    curl_close($ch);

    // Yksittäisen viitteen epäonnistuminen ei keskeytä muita -- ks. yllä -- joten jokainen
    // virhetapaus jatkaa seuraavaan viitteeseen "Virhe"-rivillä sen sijaan että koko
    // funktio palauttaisi falsen:
    if ($curlError !== "") {
      echo "cURL virhe: " . $curlError . PHP_EOL;
      echo "cURL vastaus: " . $response . PHP_EOL;

      $tiedostot[] = array(
        "file_type" => $file_type,
        "viite" => $viite,
        "status" => "Virhe",
        "data" => null
      );
      continue;
    }

    // HTTP 500 osoittautui silti sisältävän oikean ApplicationResponsen tälle pankille --
    // ks. sepa_download_file_list_crosskey():n "Sopimus ei voimassa" -tapaus. HTTP-status
    // ei siis ratkaise onnistumista, vaan se onko SOAP-vastauksessa ApplicationResponse
    // ylipäätään:
    $responseDoc = sepa_crosskey_extract_application_response($response);

    if ($responseDoc === null) {
      echo "Palvelinvirhe (HTTP-koodi: $httpCode)" . PHP_EOL;
      echo $response . PHP_EOL;

      $tiedostot[] = array(
        "file_type" => $file_type,
        "viite" => $viite,
        "status" => "Virhe",
        "data" => null
      );
      continue;
    }

    $xpath = new DOMXPath($responseDoc);
    $xpath->registerNamespace('bxd', 'http://bxd.fi/xmldata/');

    $responseCode = (string)$xpath->evaluate('string(//bxd:ResponseCode)');
    $responseText = (string)$xpath->evaluate('string(//bxd:ResponseText)');

    echo "Pankki vastasi: $responseCode $responseText" . PHP_EOL;

    if ($responseCode !== '00') {
      $tiedostot[] = array(
        "file_type" => $file_type,
        "viite" => $viite,
        "status" => "Virhe",
        "data" => null
      );
      continue;
    }

    // "data" pysyy Base64-muodossa palautuksessa, kuten vanhalla sepa_download_files():lla
    // -- kutsujat (esim. pankkiyhteys_tilioteviite.inc) purkavat sen itse. Pakkaus
    // puretaan tässä silti tarpeen vaatiessa, koska pyyntö kertoo lähettäneensä
    // Compression=false eikä kutsujilla ole syytä varautua koodaamaan sitä uudelleen:
    $content = (string)$xpath->evaluate('string(//bxd:Content)');
    $compressed = (string)$xpath->evaluate('string(//bxd:Compressed)') === 'true';

    if ($compressed) {
      $unzipped = @gzdecode(base64_decode($content));

      if ($unzipped !== false) $content = base64_encode($unzipped);
    }

    $tiedostot[] = array(
      "file_type" => $file_type,
      "viite" => $viite,
      "status" => "OK",
      "data" => $content
    );
  }

  return $tiedostot;
}
// ---

// ----------------------------------------------------------------------------------------
// Lähettää yhden tiedoston (käytännössä maksu- tai palkka-aineiston) Crosskeyhin.
//
// ApplicationRequestin oma allekirjoitus on kopioitu Crosskeyn omasta esimerkistä
// (uploadFile-application-request_1.xml), samoin kuin kahdessa yllä olevassa
// funktiossa -- sama plain C14N / SHA-1 / RSA-SHA1 -muoto, joka osoittautui kaikkien
// kolmen ApplicationRequestin yhteiseksi.
//
// SOAP-kuorta EI ole kopioitu uploadFile-soap-request_1.xml:stä yhtä tarkasti kuin
// latausfunktioiden kuoret omista esimerkeistään: kyseisessä esimerkissä on
// WS-Addressing-otsikot (wsa:Action/MessageID/ReplyTo/To) ja se osoittaa
// "stagepft.alandsbanken.fi":hin -- eri pankin staging-palvelimeen, ei POP
// Pankin/Crosskeyn tuotanto-osoitteeseen, jota jokainen muu esimerkki tässä (oikein)
// käyttää -- ja sen vastaus käyttää Crosskey-sisäistä nimiavaruutta eikä lainkaan
// WS-Securitya, toisin kuin kolme muuta vastausta. Tämä viittaa vahvasti siihen, että tämä
// yksi esimerkki on uudelleenkäytetty yleisestä SDK-näytteestä sen sijaan että se olisi
// generoitu tätä integraatiota varten kuten muut. Sen WS-Security-muoto (wsu:Timestamp,
// jossa sekä Timestamp että Body on allekirjoitettu) täsmää kuitenkin downloadFileListin
// oman, jo toimivaksi todetun pyynnön kanssa, joten tämä funktio rakentaa saman sen sijaan
// -- Timestamp allekirjoitettuna, ei WS-Addressingia. Jos lähetys silti tulee hylättynä
// takaisin kun tätä oikeasti kokeillaan Crosskeytä vasten, WS-Addressing-otsikot ovat
// ensimmäinen asia joka kannattaa lisätä takaisin:
function sepa_upload_file_crosskey(array $params): array | bool {
  global $kukarow, $yhtiorow, $sepa_pankkiyhteys_token;

  ini_set('display_errors', '1');
  ini_set('display_startup_errors', '1');
  error_reporting(E_ALL & ~E_DEPRECATED);

  $tunnus = parameter($params["pankkiyhteys_tunnus"]);
  $salasana = parameter($params["pankkiyhteys_salasana"]);
  $file_type = parameter($params["file_type"]);
  $target_id = parameter($params["target_id"] ?? "");
  $compress = !empty($params["compress"]);

  // "maksuaineisto", ei "content": sama parametrinimi ja samat Base64-tavut kuin vanhalla
  // Ahkio-reitteisellä sepa_upload_file():lla -- kutsujat (sepa.php, pankkiyhteys_send.inc)
  // tekevät jo base64_encode(file_get_contents(...)):n ennen kutsua:
  $maksuaineisto = parameter($params["maksuaineisto"] ?? "");
  $filename = parameter($params["filename"] ?? "");

  if (empty($file_type) || empty($maksuaineisto)) {
    return false;
  }
  if (empty($target_id)) $target_id = "NONE";

  // UserFilename on ApplicationRequestissa pakollinen, mutta vanha sepa_upload_file() ei
  // koskaan pyytänyt sitä kutsujalta -- Ahkio ilmeisesti keksi sen itse. Sama tässä, jotta
  // olemassa olevat kutsupaikat toimivat antamatta sitä:
  if (empty($filename)) {
    $filename = $file_type . "_" . date("YmdHis");
  }

  $pankkiyhteys = hae_pankkiyhteys($tunnus);

  if (!$pankkiyhteys) {
    return false;
  }

  // Tarkistetaan salasana ja haetaan salaamaton private key:
  $_c = pura_salaus($pankkiyhteys["signing_certificate"], $salasana);
  $_pk = pura_salaus($pankkiyhteys["signing_private_key"], $salasana);
  $_ssl = pura_salaus($pankkiyhteys["encryption_private_key"], $salasana);
  $_ssl_cert = pura_salaus($pankkiyhteys["encryption_certificate"], $salasana);

  if (!openssl_pkey_get_private($_pk) || !openssl_pkey_get_private($_ssl)) {
    return false;
  }

  // 1. ApplicationRequest. $maksuaineisto on jo Base64 -- puretaan välillä vain jos
  // pyydetään pakkaamaan se uudelleen. <CompressionMethod> ilmestyy vain silloin, samoin
  // kuin esimerkissä:
  $base64Content = $compress
    ? base64_encode(gzencode(base64_decode($maksuaineisto), 9))
    : $maksuaineisto;

  $xmlPayload = '<?xml version="1.0" encoding="UTF-8"?>' .
  '<ApplicationRequest xmlns="http://bxd.fi/xmldata/">' .
    '<CustomerId>' . $pankkiyhteys['customer_id'] . '</CustomerId>' .
    '<Command>UploadFile</Command>' .
    '<Timestamp>' . gmdate("c") . '</Timestamp>' .
    '<Environment>' . strtoupper(SEPA_ENVIRONMENT) . '</Environment>' .
    '<UserFilename>' . htmlspecialchars($filename, ENT_XML1, 'UTF-8') . '</UserFilename>' .
    '<TargetId>' . htmlspecialchars($target_id, ENT_XML1, 'UTF-8') . '</TargetId>' .
    '<Encryption>false</Encryption>' .
    '<Compression>' . ($compress ? 'true' : 'false') . '</Compression>' .
    ($compress ? '<CompressionMethod>GZIP</CompressionMethod>' : '') .
    '<SoftwareId>Pupesoft</SoftwareId>' .
    '<FileType>' . $file_type . '</FileType>' .
    '<Content>' . $base64Content . '</Content>' .
  '</ApplicationRequest>';

  $doc = new DOMDocument('1.0', 'UTF-8');
  $doc->preserveWhiteSpace = false;
  $doc->loadXML($xmlPayload);

  // 2. Allekirjoitus. Plain C14N (ei WithComments), SHA-1 digest, RSA-SHA1 -- Crosskeyn
  // oman uploadFile-application-request_1.xml-esimerkin mukaisesti, sama kuvio kuin
  // sepa_download_file_crosskey():ssä:
  $objDSig = new XMLSecurityDSig();
  $objDSig->setCanonicalMethod(XMLSecurityDSig::C14N);

  $objDSig->addReference(
    $doc,
    XMLSecurityDSig::SHA1,
    [
      'http://www.w3.org/2000/09/xmldsig#enveloped-signature'
    ],
    [
      'force_uri' => true
    ]
  );

  $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, ['type' => 'private']);
  $objKey->loadKey($_pk);
  $objDSig->sign($objKey);

  $objDSig->add509Cert($_c, true, false, ['issuerSerial' => true]);
  $objDSig->appendSignature($doc->documentElement);

  $requestBody = $doc->saveXML();
  $base64Payload = base64_encode(trim($requestBody));

  $requestId = time() . rand(100, 999);
  $timestamp = date('Y-m-d\TH:i:s.vP');

  // 3. SOAP-kuori. Operaatio on 'uploadFilein'. WS-Security: Timestamp + Body allekirjoitettu
  // -- ks. yläpuolen kommentti siitä miksi ei WS-Addressing-otsikoita:
  $soapEnvelopeXml = '<?xml version="1.0" encoding="UTF-8"?>' .
  '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cor="http://bxd.fi/CorporateFileService" xmlns:mod="http://model.bxd.fi">' .
    '<soapenv:Header/>' .
    '<soapenv:Body>' .
      '<cor:uploadFilein>' .
         '<mod:RequestHeader>' .
           '<mod:SenderId>' . $pankkiyhteys['customer_id'] . '</mod:SenderId>' .
            '<mod:RequestId>' . $requestId . '</mod:RequestId>' .
            '<mod:Timestamp>' . $timestamp . '</mod:Timestamp>' .
            '<mod:Language>FI</mod:Language>' .
            '<mod:UserAgent>Pupesoft</mod:UserAgent>' .
            '<mod:ReceiverId>POPFFI2</mod:ReceiverId>' .
         '</mod:RequestHeader>' .
         '<mod:ApplicationRequest>' . $base64Payload . '</mod:ApplicationRequest>' .
      '</cor:uploadFilein>' .
    '</soapenv:Body>' .
  '</soapenv:Envelope>';

  $soapDoc = new DOMDocument('1.0', 'UTF-8');
  $soapDoc->loadXML($soapEnvelopeXml);

  $wsse = new WSSESoap($soapDoc);
  $wsse->addTimestamp(3600);

  $objSoapKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, ['type' => 'private']);
  $objSoapKey->loadKey($_pk);
  $wsse->signSoapDoc($objSoapKey, ['algorithm' => XMLSecurityDSig::SHA1]);

  $soapToken = $wsse->addBinaryToken($_c);
  $wsse->attachTokentoSig($soapToken);

  $soapEnvelope = $wsse->saveXML();

  $endpointUrl = SEPA_ADDRESS_CROSSKEY;
  $ch = curl_init($endpointUrl);

  $headers = [
    'Content-Type: text/xml; charset=utf-8',
    'Content-Length: ' . strlen($soapEnvelope),
    'SOAPAction: "http://bxd.fi/CorporateFileService/uploadFilein"'
  ];

  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $soapEnvelope);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 60);

  curl_setopt($ch, CURLOPT_SSLCERT_BLOB, $_ssl_cert);
  curl_setopt($ch, CURLOPT_SSLKEY_BLOB, $_ssl);

  $response = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $curlError = curl_errno($ch) ? curl_error($ch) : "";
  curl_close($ch);

  if ($curlError !== "") {
    echo "cURL virhe: " . $curlError . PHP_EOL;
    echo "cURL vastaus: " . $response . PHP_EOL;
    return false;
  }

  // Sama päättely kuin sepa_download_file_crosskey():ssa: HTTP 500 ei ratkaise
  // onnistumista tälle pankille, vaan se onko SOAP-vastauksessa ApplicationResponse
  // ylipäätään.
  //
  // Toisin kuin kahden latausfunktion oma vahvistettu muoto (itsenäinen dokumentti,
  // Base64-koodattuna elementin tekstinä), uploadFilen esimerkki näyttää
  // ApplicationResponsen sisäkkäisinä oikeina lapsielementteinä ilman mitään purettavaa --
  // ks. kommentti tämän funktion omasta otsikosta siitä miksi tuo esimerkki on
  // epävarmempi kuin lataus-esimerkit. sepa_crosskey_extract_application_response()
  // alapuolella käsittelee kumman tahansa muodon tämä lopulta tekeekin:
  $responseDoc = sepa_crosskey_extract_application_response($response);

  if ($responseDoc === null) {
    echo "Palvelinvirhe (HTTP-koodi: $httpCode)" . PHP_EOL;
    echo $response . PHP_EOL;
    return false;
  }

  $xpath = new DOMXPath($responseDoc);
  $xpath->registerNamespace('bxd', 'http://bxd.fi/xmldata/');

  $responseCode = (string)$xpath->evaluate('string(//bxd:ResponseCode)');
  $responseText = (string)$xpath->evaluate('string(//bxd:ResponseText)');

  echo "Pankki vastasi: $responseCode $responseText" . PHP_EOL;

  // Sama palautusmuoto kuin vanhalla sepa_upload_file():lla: false jokaisessa
  // epäonnistumisessa (tässä myös pankin oma ResponseCode != 00, ei vain
  // yhteysvirheissä), onnistuessa "litteä" array. Ei "ok"-avainta, koska kutsujat
  // (sepa.php) käyvät koko arrayn läpi avain kerrallaan taulukkoon -- se näkyisi siinä
  // ylimääräisenä rivinä. "content" jää tyhjäksi: normaali UploadFile-vastaus ei sisällä
  // sitä (ks. uploadFile-soap-resp_1.xml), mutta esim. pankkiyhteys_functions.inc:n oma
  // OP-saldokysely (joka käyttää tätä samaa toimintoa väärinkäyttäen) lukee sen -- ks.
  // sen oma $response['content']:
  if ($responseCode !== '00') {
    return false;
  }

  return array(
    "response_code" => $responseCode,
    "response_text" => $responseText,
    "amount_total" => (string)$xpath->evaluate('string(//bxd:AmountTotal)'),
    "transaction_count" => (string)$xpath->evaluate('string(//bxd:TransactionCount)'),
    "content" => (string)$xpath->evaluate('string(//bxd:Content)')
  );
}
// ---

// ----------------------------------------------------------------------------------------
// Hakee/uusii Crosskeyn (PKI File Transfer, PFT Technical Description for software
// supplier 2.5, kohta 3.1 "GetCertificate") ITSE MYÖNTÄMÄN allekirjoitussertifikaatin
// CertificateServicen getCertificate-operaatiolla.
//
// PFT-spesifikaatio (kohta 3.1) kuvaa KAKSI eri tapaa, joita tämä yksi funktio molemmat
// hoitaa $transfer_key-parametrin perusteella:
//
//   - ENSIMMÄINEN kerta ($transfer_key annettuna): CertApplicationRequestia EI
//     allekirjoiteta ("CertApplicationRequest and SOAP don't need to be signed on the
//     registration-call") -- Crosskey tunnistaa pyynnön TransferKeyn perusteella.
//     TransferKey on 16-numeroinen, pankki toimittaa sen asiakkaalle KAHDESSA OSASSA, ja
//     viimeinen numero on Luhn-tarkistenumero. Tätä tarvitaan juuri nyt, koska meillä ei
//     ole yhtään Crosskeyn omaa, voimassa olevaa sertifikaattia allekirjoittamaan uusinta.
//
//   - UUSINTA ($transfer_key tyhjä, kohta 3.1.1): pyyntö allekirjoitetaan VANHALLA,
//     vanhenevalla mutta Crosskeyn omalla sertifikaatilla. Toimii vain uusintaikkunassa
//     (tuotannossa 60 vrk ennen vanhenemista) -- vanhentuneelle sertifikaatille pankilta
//     täytyy pyytää uusi TransferKey ja tehdä tämä samat kuin ensimmäisellä kerralla.
//
// Molemmissa tapauksissa generoidaan AINA uusi avainpari+CSR ("renewal rekeying", kohta
// 3.1.1: "New private key and csr should be generated in the renewal scenario and not
// only a new request with the old csr") -- generoi_private_key_ja_csr():n "pop"-tapaus
// asettaa CN:n ja SN:n oikein customer_id:ksi (ks. sen oma kommentti).
//
// Palauttaa onnistuessaan ["signing_certificate" => PEM, "signing_private_key" => PEM]:
// KUTSUJAN/käyttäjän pitää itse SALATA (salaa()) ja tallentaa ne pankkiyhteys-tauluun --
// tämä funktio ei kirjoita kantaan, samaan tapaan kuin sepa_renew_certificate() ei
// tee sitä nykyiselle Ahkio-reitille (ks. pankkiyhteysadmin.php, "uusi_sertifikaatti_hae").
// false virhetilanteissa, echottaen syyn (kuten muutkin crosskey-funktiot tässä
// tiedostossa).
function sepa_get_certificate_crosskey(array $params): array | bool {
  global $kukarow, $yhtiorow;

  ini_set('display_errors', '1');
  ini_set('display_startup_errors', '1');
  error_reporting(E_ALL & ~E_DEPRECATED);

  $tunnus = parameter($params["pankkiyhteys_tunnus"]);
  $salasana = parameter($params["pankkiyhteys_salasana"]);
  $transfer_key = trim((string)parameter($params["transfer_key"] ?? ""));

  $pankkiyhteys = hae_pankkiyhteys($tunnus);

  if (!$pankkiyhteys) {
    return false;
  }

  $customer_id = $pankkiyhteys['customer_id'];
  $is_renewal = ($transfer_key === "");

  // Ensimmäisellä kerralla ei tarvita/käytetä nykyistä (Samlink-peräistä) sertifikaattia
  // mihinkään -- vain salasana tarkistetaan private keyn purkamisella, kuten muissa
  // crosskey-funktioissa. Uusinnassa nykyinen VOIMASSA OLEVA Crosskey-sertifikaatti+avain
  // sen sijaan tarvitaan allekirjoitukseen:
  $_old_pk = pura_salaus($pankkiyhteys["signing_private_key"], $salasana);

  if (!openssl_pkey_get_private($_old_pk)) {
    echo "Salasana on väärä, tai signing_private_key ei purkaudu!" . PHP_EOL;
    return false;
  }

  $_old_cert = pura_salaus($pankkiyhteys["signing_certificate"], $salasana);

  if ($is_renewal) {
    if (empty($_old_cert)) {
      echo "Uusinta vaatii nykyisen Crosskey-sertifikaatin -- sitä ei ole, anna transfer_key ensimmäistä hakua varten." . PHP_EOL;
      return false;
    }
  }
  else {
    // Pelkkä paikallinen järkevyystarkistus (16 numeroa + Luhn-tarkiste) -- kiinni saa
    // väärin näpätyn TransferKeyn heti tässä, ei korvaa pankin omaa tarkistusta:
    if (!preg_match('/^\d{16}$/', $transfer_key) || !crosskey_luhn_ok($transfer_key)) {
      echo "TransferKey ei näytä oikealta (16 numeroa, viimeinen Luhn-tarkistenumero)." . PHP_EOL;
      return false;
    }
  }

  // 1. Uusi avainpari + CSR. CN=customer_id, PFT-spesifikaation ("CN=[CustomerId up to
  // 10 digits, no leading zeros]", kohta 3.1) ja generoi_private_key_ja_csr():n
  // "pop"-tapauksen mukaisesti -- AINA uusi, myös uusinnassa (kohta 3.1.1):
  $uusi = generoi_private_key_ja_csr([
    'pankki' => 'pop',
    'customer_id' => $customer_id,
  ]);

  if (empty($uusi['csr']) || empty($uusi['private_key'])) {
    echo "CSR:n generointi epäonnistui." . PHP_EOL;
    return false;
  }

  // 2. CertApplicationRequest. HUOM: OMA juurielementti ja nimiavaruus
  // (http://pft.ck.fi/xmldata/), eri kuin CorporateFileServicen ApplicationRequest
  // (http://bxd.fi/xmldata/) -- vahvistettu crosskey-examples/getCertificate-
  // application-request_*.xml -esimerkeistä. Content = CSR PEM-muodossa,
  // Base64-koodattuna kertaalleen (kohta 3.1.2: "Certificate request should be in PEM
  // form and then binary64-encoded"). Elementtijärjestys täsmää molempiin esimerkkeihin:
  $content = base64_encode($uusi['csr']);
  $timestamp = date('Y-m-d\TH:i:s.vP');

  $xmlPayload = '<?xml version="1.0" encoding="UTF-8"?>' .
  '<CertApplicationRequest xmlns="http://pft.ck.fi/xmldata/">' .
    '<CustomerId>' . $customer_id . '</CustomerId>' .
    '<Timestamp>' . $timestamp . '</Timestamp>' .
    '<Environment>' . strtoupper(SEPA_ENVIRONMENT) . '</Environment>' .
    '<SoftwareId>Pupesoft</SoftwareId>' .
    ($is_renewal ? '<Command>GetCertificate</Command>' : '') .
    '<Service>MATU</Service>' .
    '<Content>' . $content . '</Content>' .
    (!$is_renewal ? '<TransferKey>' . $transfer_key . '</TransferKey>' : '') .
  '</CertApplicationRequest>';

  $doc = new DOMDocument('1.0', 'UTF-8');
  $doc->preserveWhiteSpace = false;
  $doc->loadXML($xmlPayload);

  // 3. Allekirjoitus VAIN uusinnassa, VANHALLA (vanhenevalla) sertifikaatilla/avaimella --
  // "CertApplicationRequest and SOAP don't need to be signed on the registration-call"
  // (kohta 3.1), mutta "On certificate renewal, a signed CertApplicationRequest is used"
  // (kohta 3.1.1). Plain C14N + SHA-256 -- crosskey-examples/getCertificate-application-
  // request_Signed.xml käyttää rsa-sha256/sha256, TOISIN kuin muut kolme Crosskey-
  // operaatiota tässä tiedostossa (SHA-1) -- ei sekoiteta niihin:
  if ($is_renewal) {
    $objDSig = new XMLSecurityDSig();
    $objDSig->setCanonicalMethod(XMLSecurityDSig::C14N);

    $objDSig->addReference(
      $doc,
      XMLSecurityDSig::SHA256,
      [
        'http://www.w3.org/2000/09/xmldsig#enveloped-signature'
      ],
      [
        'force_uri' => true
      ]
    );

    $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
    $objKey->loadKey($_old_pk);
    $objDSig->sign($objKey);

    $objDSig->add509Cert($_old_cert, true, false, ['issuerSerial' => true]);
    $objDSig->appendSignature($doc->documentElement);
  }

  $requestBody = $doc->saveXML();
  $base64Payload = base64_encode(trim($requestBody));

  $requestId = time() . rand(100, 999);
  $soapTimestamp = date('Y-m-d\TH:i:s.vP');

  // 4. SOAP-kuori. Operaatio 'getCertificatein', OMA nimiavaruutensa
  // (http://schemas.ck.fi/xml/ns/services/CKCertificateService-v1.0) -- eri kuin
  // CorporateFileServicen (http://bxd.fi/CorporateFileService). RequestHeaderissä ei ole
  // Language/UserAgent/ReceiverId-kenttiä, toisin kuin muilla kolmella operaatiolla --
  // vahvistettu crosskey-examples/getCertificate-soap-request_1.xml -esimerkistä. Sen
  // WS-Addressing-otsikot (wsa:Action/MessageID/ReplyTo/To) on OTETTU mukaan tähän --
  // toisin kuin sepa_upload_file_crosskey() jätti omansa pois -- koska tämän esimerkin
  // wsa:To osoittaa oikeasti Crosskeyn omaan popstagepft.crosskey.fi-osoitteeseen (PFT-
  // spesifikaation kohdan 7 mukainen), ei jonkin toisen pankin osoitteeseen kuten
  // uploadFilen esimerkki -- ei siis syytä epäillä sitä väärästä kontekstista
  // uudelleenkäytetyksi. Ei WS-Security-allekirjoitusta SOAP-tasolla, vain wsu:Timestamp --
  // CertApplicationRequestin OMA allekirjoitus (uusinnassa) riittää:
  $messageId = sprintf(
    'urn:uuid:%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000,
    mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
  );

  $endpointUrl = SEPA_WSDL_CROSSKEY; // Nimestä huolimatta oikea CertificateService-osoite

  $soapEnvelopeXml = '<?xml version="1.0" encoding="UTF-8"?>' .
  '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://schemas.xmlsoap.org/ws/2004/08/addressing" xmlns:cer="http://schemas.ck.fi/xml/ns/services/CKCertificateService-v1.0">' .
    '<soapenv:Header>' .
      '<wsa:Action/>' .
      '<wsa:MessageID>' . $messageId . '</wsa:MessageID>' .
      '<wsa:ReplyTo>' .
        '<wsa:Address>http://schemas.xmlsoap.org/ws/2004/08/addressing/role/anonymous</wsa:Address>' .
      '</wsa:ReplyTo>' .
      '<wsa:To>' . $endpointUrl . '</wsa:To>' .
    '</soapenv:Header>' .
    '<soapenv:Body>' .
      '<cer:getCertificatein>' .
         '<cer:RequestHeader>' .
           '<cer:SenderId>' . $customer_id . '</cer:SenderId>' .
            '<cer:RequestId>' . $requestId . '</cer:RequestId>' .
            '<cer:Timestamp>' . $soapTimestamp . '</cer:Timestamp>' .
         '</cer:RequestHeader>' .
         '<cer:ApplicationRequest>' . $base64Payload . '</cer:ApplicationRequest>' .
      '</cer:getCertificatein>' .
    '</soapenv:Body>' .
  '</soapenv:Envelope>';

  $soapDoc = new DOMDocument('1.0', 'UTF-8');
  $soapDoc->loadXML($soapEnvelopeXml);

  $wsse = new WSSESoap($soapDoc);
  $wsse->addTimestamp(300);
  $soapEnvelope = $wsse->saveXML();

  // 5. cURL. Ei CURLOPT_SSLCERT_BLOB/SSLKEY_BLOB -mTLS-asetuksia: PFT-spesifikaatiossa ei
  // mainita erillistä mTLS-/encryption-sertifikaattia lainkaan (vain tämä sertifikaatti,
  // jota juuri haetaan) -- ja encryption_certificate on kannassa tyhjä tälle
  // pankkiyhteydelle jo nyt, siitä huolimatta muut kolme crosskey-funktiota saavat oikean,
  // jäsentyvän ApplicationResponsen takaisin. Jos pankki jatkossa vaatiikin mTLS:n, virhe
  // näkyy tässä cURL-tasolla (SSL-kättely epäonnistuu), ei sovellustason "07"-koodina:
  $ch = curl_init($endpointUrl);

  $headers = [
    'Content-Type: text/xml; charset=utf-8',
    'Content-Length: ' . strlen($soapEnvelope),
    'SOAPAction: "http://schemas.ck.fi/xml/ns/services/CKCertificateService-v1.0/getCertificatein"'
  ];

  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $soapEnvelope);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);

  $response = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $curlError = curl_errno($ch) ? curl_error($ch) : "";
  curl_close($ch);

  if ($curlError !== "") {
    echo "cURL virhe: " . $curlError . PHP_EOL;
    echo "cURL vastaus: " . $response . PHP_EOL;
    return false;
  }

  // Debug: koko SOAP-vastaus näkyviin p.php:ssä testattaessa, kuten muillakin
  // crosskey-funktioilla tässä tiedostossa on tapana palvelinvirheissä:
  echo $response . PHP_EOL;

  $responseDoc = sepa_crosskey_extract_application_response($response);

  if ($responseDoc === null) {
    echo "Palvelinvirhe (HTTP-koodi: $httpCode)" . PHP_EOL;
    return false;
  }

  $xpath = new DOMXPath($responseDoc);
  $xpath->registerNamespace('bxd', 'http://pft.ck.fi/xmldata/');

  $responseCode = (string)$xpath->evaluate('string(//bxd:ResponseCode)');
  $responseText = (string)$xpath->evaluate('string(//bxd:ResponseText)');

  echo "Pankki vastasi: $responseCode $responseText" . PHP_EOL;

  if ($responseCode !== '00') {
    return false;
  }

  // 6. Uusi sertifikaatti vastauksesta. crosskey-examples/getCertificate-application-
  // response.xml: bxd:Certificates/bxd:Certificate/bxd:Certificate on PEM, Base64-
  // koodattuna kertaalleen (sama suunta kuin Content pyynnössä, kohta 3.1.2) -- mutta
  // PEM-otsikot lisätään tässä varmuuden vuoksi, jos ne puuttuisivat:
  $certBase64 = (string)$xpath->evaluate('string(//bxd:Certificates/bxd:Certificate[1]/bxd:Certificate)');

  if ($certBase64 === '') {
    echo "Vastauksesta ei löytynyt sertifikaattia." . PHP_EOL;
    return false;
  }

  $certPem = base64_decode($certBase64);

  if (!str_contains($certPem, '-----BEGIN CERTIFICATE-----')) {
    $certPem = "-----BEGIN CERTIFICATE-----\n" .
      chunk_split(base64_encode($certPem), 64, "\n") .
      "-----END CERTIFICATE-----\n";
  }

  echo "Uusi Crosskey-sertifikaatti saatu onnistuneesti!" . PHP_EOL;

  return array(
    "signing_certificate" => $certPem,
    "signing_private_key" => $uusi['private_key'],
  );
}
// ----------------------------------------------------------------------------------------

// ----------------------------------------------------------------------------------------
// Luhn-tarkiste (mod 10) TransferKeylle -- PFT Technical Description 2.5, kohta 3.1:
// "Transfer key is 16 digits... The last digit is the checksum digit."
function crosskey_luhn_ok(string $numero): bool {
  $summa = 0;
  $pituus = strlen($numero);

  for ($i = 0; $i < $pituus; $i++) {
    $luku = (int)$numero[$pituus - 1 - $i];

    if ($i % 2 === 1) {
      $luku *= 2;
      if ($luku > 9) $luku -= 9;
    }
    $summa += $luku;
  }
  return ($summa % 10) === 0;
}
// ----------------------------------------------------------------------------------------

function sepa_download_files($params) {
  global $kukarow, $yhtiorow, $sepa_pankkiyhteys_token;

  $tunnus       = parameter($params["pankkiyhteys_tunnus"]);
  $salasana     = parameter($params["pankkiyhteys_salasana"]);
  $file_type    = parameter($params["file_type"]);
  $target_id    = parameter($params["target_id"]);
  $viitteet     = parameter($params["viitteet"]);
  $pankkiyhteys = hae_pankkiyhteys_ja_pura_salaus($tunnus, $salasana);

  if (!$pankkiyhteys or empty($file_type) or empty($viitteet)) {
    return false;
  }

  $viitteet = array_unique($viitteet);

  $bank = $pankkiyhteys['bank'];

  // Varmistetaan, että ei kutsuta väärällä filetypellä
  $file_type = sepa_file_type_check($bank, $file_type);

  // Nordealla, pitää hakea filetypen "target_id"
  // Haetaan vaan jos ei ole passattu parametrinä
  if ($bank == "nordea" and $target_id == "") {
    $params["file_type"] = $file_type;

    $target_id = sepa_target_id_for_file_type($params);

    if ($target_id === false) {
      return false;
    }
  }

  $tiedostot = array();

  foreach ($viitteet as $viite) {
    $parameters = array(
      "method"  => "POST",
      "data"    => array(
        "own_signing_certificate"     => $pankkiyhteys["signing_certificate"],
        "signing_private_key"         => $pankkiyhteys["signing_private_key"],
        "bank_encryption_certificate" => $pankkiyhteys["bank_encryption_certificate"],
        "encryption_private_key"      => $pankkiyhteys["encryption_private_key"],
        "customer_id"                 => $pankkiyhteys["customer_id"],
        "target_id"                   => $target_id,
        "file_type"                   => $file_type,
        "file_reference"              => $viite,
        "environment"                 => SEPA_ENVIRONMENT,
        "status"                      => 'NEW',
      ),
      "url" => SEPA_ADDRESS . "/" . bank_provider($bank) . "/download_file",
      "headers" => array(
        "Content-Type: application/json",
        "Authorization: Token token={$sepa_pankkiyhteys_token}"
      )
    );

    list($code, $response) = pupesoft_rest($parameters);

    if (vastaus_kunnossa($code)) {
      $tiedosto = array(
        "file_type" => $file_type,
        "viite"  => $viite,
        "status" => "OK",
        "data" => $response["data"]
      );
    }
    else {
      $tiedosto = array(
        "file_type" => $file_type,
        "viite"  => $viite,
        "status" => "Virhe",
        "data" => null
      );
    }

    array_push($tiedostot, $tiedosto);
  }

  return $tiedostot;
}

function sepa_upload_file($params) {
  global $kukarow, $yhtiorow, $sepa_pankkiyhteys_token;

  $tunnus        = parameter($params["pankkiyhteys_tunnus"]);
  $salasana      = parameter($params["pankkiyhteys_salasana"]);
  $file_type     = parameter($params["file_type"]);
  $maksuaineisto = parameter($params["maksuaineisto"]);
  $target_id     = parameter($params["target_id"]);
  $pankkiyhteys  = hae_pankkiyhteys_ja_pura_salaus($tunnus, $salasana);

  if (!$pankkiyhteys or empty($file_type) or empty($maksuaineisto)) {
    return false;
  }

  $bank = $pankkiyhteys['bank'];

  // Varmistetaan, että ei kutsuta väärällä filetypellä
  $file_type = sepa_file_type_check($bank, $file_type);

  // Nordealla, pitää hakea filetypen "target_id"
  // Haetaan vaan jos ei ole passattu parametrinä
  if ($bank == "nordea" and $target_id == "") {
    $params["file_type"] = $file_type;

    $target_id = sepa_target_id_for_file_type($params);

    if ($target_id === false) {
      return false;
    }
  }

  $parameters = array(
    "method"  => "POST",
    "data"    => array(
      "own_signing_certificate"     => $pankkiyhteys["signing_certificate"],
      "signing_private_key"         => $pankkiyhteys["signing_private_key"],
      "bank_encryption_certificate" => $pankkiyhteys["bank_encryption_certificate"],
      "encryption_private_key"      => $pankkiyhteys["encryption_private_key"],
      "customer_id"                 => $pankkiyhteys["customer_id"],
      "target_id"                   => $target_id,
      "file_type"                   => $file_type,
      "content"                     => $maksuaineisto,
      "environment"                 => SEPA_ENVIRONMENT,
    ),
    "url"     => SEPA_ADDRESS . "/" . bank_provider($bank) . "/upload_file",
    "headers" => array(
      "Content-Type: application/json",
      "Authorization: Token token={$sepa_pankkiyhteys_token}"
    )
  );

  list($code, $response) = pupesoft_rest($parameters);

  if (!vastaus_kunnossa($code)) {
    return false;
  }

  return $response;
}

function sepa_get_certificate($params) {
  global $kukarow, $yhtiorow, $sepa_pankkiyhteys_token;

  $bank           = parameter($params["bank"]);
  $customer_id    = parameter($params["customer_id"]);
  $pin            = parameter($params["pin"]);
  $signing_csr    = parameter($params["signing_csr"]);
  $encryption_csr = parameter($params["encryption_csr"]);

  if (empty($bank)
    or empty($customer_id)
    or empty($pin)
    or empty($signing_csr)) {
    return false;
  }

  $parameters = array(
    "method" => "POST",
    "data" => array(
      "pin" => $pin,
      "customer_id" => $customer_id,
      "environment" => SEPA_ENVIRONMENT,
      "signing_csr" => base64_encode($signing_csr),
      "encryption_csr" => base64_encode($encryption_csr),
    ),
    "url" => SEPA_ADDRESS . "/" . bank_provider($bank) . "/get_certificate",
    "headers" => array(
      "Content-Type: application/json",
      "Authorization: Token token={$sepa_pankkiyhteys_token}"
    )
  );

  list($code, $response) = pupesoft_rest($parameters);

  if (!vastaus_kunnossa($code)) {
    return false;
  }

  $return = array(
    "bank_encryption_certificate" => base64_decode($response["bankEncryptionCertificate"]),
    "bank_root_certificate"       => base64_decode($response["bankRootCertificate"]),
    "own_encryption_certificate"  => base64_decode($response["ownEncryptionCertificate"]),
    "own_signing_certificate"     => base64_decode($response["ownSigningCertificate"]),
    "ca_certificate"              => base64_decode($response["caCertificate"]),
  );

  return $return;
}

function sepa_renew_certificate($params) {
  global $kukarow, $yhtiorow, $sepa_pankkiyhteys_token;

  $tunnus   = parameter($params["pankkiyhteys_tunnus"]);
  $salasana = parameter($params["salasana"]);
  $bank     = parameter($params["bank"]);

  if (empty($tunnus) or empty($salasana) or empty($bank)) return false;

  $pankkiyhteys = hae_pankkiyhteys_ja_pura_salaus($tunnus, $salasana);

  $old_cert_info = openssl_x509_parse(base64_decode($pankkiyhteys['signing_certificate']));

  $csr_params = array(
    'pankki' => $bank,
  );

  switch ($bank) {
  case 'nordea':
    $customer_id = $old_cert_info['subject']['serialNumber'];

    $csr_params['customer_id']  = $customer_id;
    $csr_params['company_name'] = $old_cert_info['subject']['CN'];

    break;
  case 'op':
    $customer_id = $old_cert_info['subject']['CN'];

    $csr_params['customer_id']  = $customer_id;

    break;
  case 'danske':
    $customer_id = $pankkiyhteys['customer_id'];

    break;
  case 'aktia':
  case 'sp':
  case 'pop':
  case 'shb':
    $customer_id = $old_cert_info['subject']['SN'];

    $csr_params['company_name'] = $old_cert_info['subject']['CN'];
    $csr_params['customer_id']  = $customer_id;

    break;
  }

  $generoidut_tunnukset1 = generoi_private_key_ja_csr($csr_params);
  $generoidut_tunnukset2 = generoi_private_key_ja_csr($csr_params);

  $parameters = array(
    "method" => "POST",
    "data" => array(
      "customer_id"                 => $customer_id,
      "environment"                 => SEPA_ENVIRONMENT,
      "signing_csr"                 => base64_encode($generoidut_tunnukset1['csr']),
      "encryption_csr"              => base64_encode($generoidut_tunnukset2['csr']),
      "own_signing_certificate"     => $pankkiyhteys['signing_certificate'],
      "bank_encryption_certificate" => $pankkiyhteys['bank_encryption_certificate'],
      "signing_private_key"         => $pankkiyhteys['signing_private_key'],
      "encryption_private_key"      => $pankkiyhteys['encryption_private_key'],
    ),
    "url" => SEPA_ADDRESS . "/" . bank_provider($bank) . "/renew_certificate",
    "headers" => array(
      "Content-Type: application/json",
      "Authorization: Token token={$sepa_pankkiyhteys_token}"
    )
  );

  list($code, $response) = pupesoft_rest($parameters);

  if (!vastaus_kunnossa($code)) return false;

  return array(
    "own_signing_certificate"     => base64_decode($response["ownSigningCertificate"]),
    "own_encryption_certificate"  => base64_decode($response["ownEncryptionCertificate"]),
    "ca_certificate"              => base64_decode($response["caCertificate"]),
    "bank_encryption_certificate" => base64_decode($response["bankEncryptionCertificate"]),
    "bank_root_certificate"       => base64_decode($response["bankRootCertificate"]),
    "signing_private_key"         => $generoidut_tunnukset1['private_key'],
    "encryption_private_key"      => $generoidut_tunnukset2['private_key'],
  );
}

function sepa_get_bank_certificate($params) {
  global $kukarow, $yhtiorow, $sepa_pankkiyhteys_token;

  $bank        = parameter($params["bank"]);
  $customer_id = parameter($params["customer_id"]);

  if (empty($bank) or empty($customer_id)) {
    return false;
  }

  $parameters = array(
    "method" => "POST",
    "data" => array(
      "customer_id" => $customer_id,
      "environment" => SEPA_ENVIRONMENT,
    ),
    "url" => SEPA_ADDRESS . "/" . bank_provider($bank) . "/get_bank_certificate",
    "headers" => array(
      "Content-Type: application/json",
      "Authorization: Token token={$sepa_pankkiyhteys_token}"
    )
  );

  list($code, $response) = pupesoft_rest($parameters);

  if (!vastaus_kunnossa($code)) {
    return false;
  }

  $return = array(
    "bank_encryption_certificate" => base64_decode($response["bankEncryptionCertificate"]),
    "bank_root_certificate"       => base64_decode($response["bankRootCertificate"])
  );

  return $return;
}

function sepa_get_user_info($params) {
  global $kukarow, $yhtiorow, $sepa_pankkiyhteys_token;

  $tunnus       = parameter($params["pankkiyhteys_tunnus"]);
  $salasana     = parameter($params["pankkiyhteys_salasana"]);
  $pankkiyhteys = hae_pankkiyhteys_ja_pura_salaus($tunnus, $salasana);

  if (!$pankkiyhteys) {
    return false;
  }

  $parameters = array(
    "method"  => "POST",
    "data"    => array(
      "own_signing_certificate"     => $pankkiyhteys["signing_certificate"],
      "signing_private_key"         => $pankkiyhteys["signing_private_key"],
      "bank_encryption_certificate" => $pankkiyhteys["bank_encryption_certificate"],
      "encryption_private_key"      => $pankkiyhteys["encryption_private_key"],
      "customer_id"                 => $pankkiyhteys["customer_id"],
      "environment"                 => SEPA_ENVIRONMENT,
    ),
    "url" => SEPA_ADDRESS . "/" . bank_provider($pankkiyhteys["bank"]) . "/get_user_info",
    "headers" => array(
      "Content-Type: application/json",
      "Authorization: Token token={$sepa_pankkiyhteys_token}"
    )
  );

  list($code, $response) = pupesoft_rest($parameters);

  if (!vastaus_kunnossa($code)) {
    return false;
  }

  return $response;
}

function sepa_target_id_for_file_type($params) {

  $tunnus       = parameter($params["pankkiyhteys_tunnus"]);
  $salasana     = parameter($params["pankkiyhteys_salasana"]);
  $file_type    = parameter($params["file_type"]);
  $pankkiyhteys = hae_pankkiyhteys_ja_pura_salaus($tunnus, $salasana);

  if (!$pankkiyhteys or empty($file_type)) {
    return false;
  }

  // Tehdään pankille userinfo kysely
  $user_info = sepa_get_user_info($params);

  if (!$user_info) {
    return false;
  }

  // Haetaan filetypen target_id
  $value = search_array_key_for_value_recursive($user_info, "fileType", $file_type);
  $target_id = parameter($value[0]["targetId"]);

  // Jos filetypeä ei löydy listasta, ei voida hakea
  if ($target_id == "") {
    echo "Pankki '{$pankkiyhteys['bank']}' ei tue tiedostotyyppiä '{$file_type}'<br><br>";
    return false;
  }

  return $target_id;
}

function sepa_file_type_check($bank, $file_type) {
  // Varmistetaan, että ei kutsuta väärällä filetypellä

  // Nordeamuunnokset
  if ($bank == "nordea") {
    // Konekielinen tiliote
    if ($file_type == "KTON") {
      $file_type = "TITO";
    }
    // Konekielinen viiteaineisto
    if ($file_type == "VIPN") {
      $file_type = "KTL";
    }
    // XML maksuaineisto
    if ($file_type == "C2BL") {
      $file_type = "NDCORPAYS";
    }
    // Maksuaineiston palautesanoma
    if ($file_type == "C2BN") {
      $file_type = "NDCORPAYL";
    }
    // Saapuvat verkkolaskut
    if ($file_type == 'FILN') {
      $file_type = 'HAELASKUT';
    }
    // Lähtevät verkkolaskut
    if ($file_type == 'FILL') {
      $file_type = 'LAHLASKUT';
    }
    // Lähtevä factoringaineisto
    if ($file_type == 'LAHFACTORING') {
      $file_type = 'FACTORING';
    }
  }

  // Danskemuunnokset
  if ($bank == "danske") {
    // Konekielinen tiliote
    if ($file_type == "TITO") {
      $file_type = "KTON";
    }
    // Konekielinen viiteaineisto
    if ($file_type == "KTL") {
      $file_type = "VIPN";
    }
    // XML maksuaineisto
    if ($file_type == "NDCORPAYS") {
      $file_type = "C2BL";
    }
    // Maksuaineiston palautesanoma
    if ($file_type == "NDCORPAYL") {
      $file_type = "C2BN";
    }
    // Saapuvat verkkolaskut
    if ($file_type == 'HAELASKUT') {
      $file_type = 'FILN';
    }
    // Lähtevät verkkolaskut
    if ($file_type == 'LAHLASKUT') {
      $file_type = 'FILL';
    }
  }

  // OP-muunnokset
  if ($bank == "op") {
    // Konekielinen tiliote
    if ($file_type == "TITO") {
      $file_type = "TI";
    }
    // Konekielinen viiteaineisto
    if ($file_type == "KTL") {
      $file_type = "TL";
    }
    // XML maksuaineisto
    if ($file_type == "NDCORPAYS") {
      $file_type = "pain.001.001.02";
    }
    // Maksuaineiston palautesanoma
    if ($file_type == "C2BN") {
      $file_type = "pain.002.001.02";
    }
    // Saapuvat verkkolaskut
    if ($file_type == 'HAELASKUT') {
      $file_type = 'XR';
    }
    // Lähtevät verkkolaskut
    if ($file_type == 'LAHLASKUT') {
      //$file_type = 'XS';
      $file_type = 'pain.001.001.02'; // MUOKKAUS: kaytetaan manuaalisiin tilisiirtoihin
    }
  }

  // Aktia, Säästöpankki & POP Pankki-muunnokset
  if (in_array($bank, array("aktia", "sp", "pop"))) {
    switch ($file_type) {
    // Konekielinen tiliote
    case 'TITO':
      return "TO";
    // Konekielinen viiteaineisto
    case 'KTL':
      return "OP";
    // XML maksuaineisto
    case 'NDCORPAYS':
      return "XL";
    // Maksuaineiston palautesanoma
    case 'C2BN':
      return "XP";
    // Saapuvat verkkolaskut
    case 'HAELASKUT':
      return "VN";
    // Lähtevät verkkolaskut
    case 'LAHLASKUT':
      return "XL";
      //return "VL"; // HUOM! Palauta tämä käyttöön, jos "Pankki ei vastaa kyselyyn odotetulla tavalla" ja etsi XL:lle yrityskohtainen ratkaisu (sapu)
    // Lähtevä factoringaineisto
    case 'LAHFACTORING':
      return "FC";
    }
  }

  // Handelsbanken-muunnokset
  if ($bank == "shb") {
    switch ($file_type) {
    // Konekielinen tiliote
    case 'TITO':
      return "0700";
    // Konekielinen viiteaineisto
    case 'KTL':
      return "0250";
    // XML maksuaineisto
    case 'NDCORPAYS':
      return "0900";
    // Maksuaineiston palautesanoma
    case 'C2BN':
      return "0910";
    // Saapuvat verkkolaskut
    case 'HAELASKUT':
      return "0820";
    // Lähtevät verkkolaskut
    case 'LAHLASKUT':
      return "0800";
    }
  }

  return $file_type;
}
// Sertifikaatti / salausfunktiot


/**
 * Hakee pankkiyhteysrivin tietokannasta ja purkaa kenttien salauksen.
 * Palauttaa pankkiyhteys arrayn
 *
 * @param string  $pankkiyhteys_tunnus
 * @param string  $salasana
 *
 * @return array
 */
function hae_pankkiyhteys_ja_pura_salaus($pankkiyhteys_tunnus, $salasana) {
  global $kukarow, $yhtiorow;

  $tuetut_pankit = tuetut_pankit();
  $pankkiyhteys_tunnus = (int) $pankkiyhteys_tunnus;

  $pankki = hae_pankkiyhteys($pankkiyhteys_tunnus);

  if (empty($pankki)) return false;

  // Tarkistetaan salasana
  $_pk = pura_salaus($pankki["signing_private_key"], $salasana);

  // Jos salauksen purkaminen ei onnistu, lopetetaan tähä
  if (!openssl_pkey_get_private($_pk)) {
    return false;
  }

  // Listään resulttiin pankin lyhyt nimi
  $pankki["bank"] = $tuetut_pankit[$pankki["pankki"]]["lyhyt_nimi"];

  // Nämä kentät on salattuna kannassa
  $salatut = array(
    "signing_private_key",
    "signing_certificate",
    "encryption_certificate",
    "encryption_private_key",
    "bank_encryption_certificate",
    "bank_root_certificate",
    "ca_certificate",
  );

  // Puretaan salaus kaikista
  foreach ($salatut as $key) {
    // Puretaan salaus
    $_salaamaton = pura_salaus($pankki[$key], $salasana);

    // Base64 enkoodataan ja setataan takasin arrayseen
    $pankki[$key] = base64_encode(trim($_salaamaton));
  }

  // Lisätään arrayseen vielä certifikaattien expire datet
  $certit = array(
    "signing_certificate",
    "encryption_certificate",
    "bank_encryption_certificate",
    "bank_root_certificate",
    "ca_certificate",
  );

  foreach ($certit as $cert) {
    $_key = "{$cert}_valid_to";
    $_cert = parse_sertificate(base64_decode($pankki[$cert]));
    $pankki[$_key] = $_cert['valid_to'];
  }

  return $pankki;
}

function hae_pankkiyhteys($tunnus) {
  if ($tunnus < 0) return array();

  $query = "SELECT *
            FROM pankkiyhteys
            WHERE tunnus = {$tunnus}
            LIMIT 1";
  $result = pupe_query($query);

  return mysqli_fetch_assoc($result);
}

function pankkiyhteyden_paivamaarat($pankkiyhteys) {
  global $palvelin2;

  // Loopataan certifikaattien expire datet
  $certit = array(
    "signing_certificate_valid_to" => "Allekirjoitus-sertifikaatti",
    "encryption_certificate_valid_to" => "Salaus-sertifikaatti",
    "bank_encryption_certificate_valid_to" => "Pankin salaus-sertifikaatti",
    "bank_root_certificate_valid_to" => "Pankin juuri-sertifikaatti",
    "ca_certificate_valid_to" => "Pankin CA-sertifikaatti",
  );

  $_today = date("Y-m-d h:i:s");
  $_certit_ok = true;

  foreach ($certit as $valid => $nimi) {
    $_time = $pankkiyhteys[$valid];

    // Nollaa jos meillä ei ole tätä serttiä
    if ($_time == '') {
      continue;
    }

    // Jos expire date tulevaisuudessa, niin hyvä
    if ($_time > $_today) {
      continue;
    }

    $_nimi = t($nimi);
    $_time = tv1dateconv($_time);

    echo "<font class='error'>{$_nimi} on vanhentunut!";

    if (tarkista_oikeus('pankkiyhteysadmin.php', '', true)) {
      echo " Sertifikaatin voi uusia ";
      echo "<a href='{$palvelin2}/pankkiyhteysadmin.php'>" . t('täältä') . "</a>.";
    }

    echo "</font><br><br>";


    $_certit_ok = false;
  }

  return $_certit_ok;
}


/**
 * Laitaa kaikki tietyntyyppiset tiedostot pankista ja
 * palauttaa arrayn tiedostojen viitteista ja
 *
 * @param array   $params
 *
 * @return array|bool
 */
function sepa_lataa_kaikki_uudet_tiedostot($params) {
  global $kukarow, $yhtiorow;

  $tunnus    = parameter($params["pankkiyhteys_tunnus"]);
  $salasana  = parameter($params["pankkiyhteys_salasana"]);
  $file_type = parameter($params["file_type"]);

  if (empty($file_type)) {
    return false;
  }

  // Lisätään paramsiin status NEW, että haetaan vain uudet filet
  $params = array(
    "pankkiyhteys_tunnus" => $tunnus,
    "pankkiyhteys_salasana" => $salasana,
    "file_type" => $file_type,
    "status" => 'NEW'
  );

  // Haetaan filelista pankista
  $filelist = sepa_download_file_list($params);

  if (!$filelist) {
    return false;
  }

  $viitteet = array();

  // Lisätään paramssiin haettavat referencet
  foreach ($filelist['files'] as $tiedosto) {
    $viitteet[] = $tiedosto["fileReference"];
  }

  $params = array(
    "pankkiyhteys_tunnus" => $tunnus,
    "pankkiyhteys_salasana" => $salasana,
    "file_type" => $file_type,
    "target_id" => $filelist['target_id'],
    "viitteet" => $viitteet
  );

  $tiedostot = sepa_download_files($params);

  if (!$tiedostot) {
    return false;
  }

  return $tiedostot;
}


/**
 * Generoi private keyn ja certificate signing requestin. Osa signing requestin tiedoista haetaan
 * yhtiorowista. Palauttaa arrayn, jossa private_key ja csr.
 *
 * @param array   $params
 *
 * @return array
 */
function generoi_private_key_ja_csr($params = array()) {
  global $kukarow, $yhtiorow;

  $company_name = empty($params['company_name']) ? ""   : $params['company_name'];
  $customer_id  = empty($params['customer_id'])  ? ""   : $params['customer_id'];
  $pankki       = empty($params['pankki'])       ? ""   : $params['pankki'];

  $key_bits = 2048;

  $csr_info = array(
    "C={$yhtiorow["maa"]}",
  );

  switch ($pankki) {
  case "nordea":
    $key_bits = 1024;

    $csr_info[] = "CN={$company_name}";
    $csr_info[] = "serialNumber={$customer_id}";
    break;
  case "op":
    $csr_info[] = "CN={$customer_id}";
    break;
  case "danske":
    $csr_info[] = "CN={$yhtiorow['nimi']}";
    $csr_info[] = "emailAddress={$yhtiorow["email"]}";
    $csr_info[] = "L={$yhtiorow["kotipaikka"]}";
    $csr_info[] = "O={$yhtiorow['nimi']}";
    break;
  case "pop":
    // MUOKKAUS: Crosskeyn PFT-spesifikaatio (kohta 3.1) vaatii CustomerId:n nimenomaan
    // CN-kentässä ("CN=[CustomerId, korkeintaan 10 numeroa, ei alkunollia]"), ei SN:ssä
    // kuten alempi default-haara tekee -- se aiheutti "07 Sopimus ei voimassa"
    // -vastauksen, koska pankin myöntämän sertifikaatin CN oli yhtiön nimi eikä
    // customer_id. SN pidetään silti mukana, koska sepa_renew_certificate() lukee
    // customer_id:n seuraavalla uusintakerralla vanhan sertifikaatin SN-kentästä:
    $csr_info[] = "CN={$customer_id}";
    $csr_info[] = "SN={$customer_id}";
    $csr_info[] = "O=Aineistopalvelut-Samlink";
    break;
  default:
    $csr_info[] = "SN={$customer_id}";
    $csr_info[] = "CN={$company_name}";
    $csr_info[] = "O=Aineistopalvelut-Samlink";
  }

  $csr_dir    = "/tmp/" . uniqid('csr-', true);
  $key_path   = "{$csr_dir}/key.pem";
  $csr_path   = "{$csr_dir}/csr.pem";
  $subject    = "/" . implode("/", $csr_info);
  $passphrase = openssl_random_pseudo_bytes(100);
  $command    = escapeshellcmd("openssl req " .
    "-newkey rsa:{$key_bits} " .
    "-passout stdin " .
    "-keyout {$key_path} " .
    "-out {$csr_path} " .
    "-subj '{$subject}'");

  mkdir($csr_dir);

  $handle = popen($command, "w");

  fwrite($handle, $passphrase);
  pclose($handle);

  $command = escapeshellcmd("openssl rsa -in {$key_path} -passin stdin");
  $descriptorspec = array(
    0 => array("pipe", "r"),
    1 => array("pipe", "w")
  );
  $handle = proc_open($command, $descriptorspec, $pipes);

  if (is_resource($handle)) {
    fwrite($pipes[0], $passphrase);
    fclose($pipes[0]);

    $private_key =  stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    proc_close($handle);
  }

  $csr = file_get_contents($csr_path);

  system(escapeshellcmd("rm -rf {$csr_dir}"));

  return array(
    "private_key" => $private_key,
    "csr"         => $csr,
  );
}


/**
 * Salaa annetun datan annetulla salasanalla. Salaus on 256-bittinen AES-salaus.
 *
 * @param string  $salattava_data
 * @param string  $salasana
 *
 * @return string
 */
function salaa($salattava_data, $salasana) {

  if (empty($salattava_data)) {
    return null;
  }

  $avain = hash("SHA256", $salasana, true);

  $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
  $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

  $salattu_data = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $avain, $salattava_data, MCRYPT_MODE_CBC, $iv);
  $salattu_data = $iv . $salattu_data;

  return base64_encode($salattu_data);
}


/**
 * Purkaa 256-bittisen AES-salauksen annetusta datasta annetulla salasanalla
 *
 * @param string  $salattu_data
 * @param string  $salasana
 *
 * @return string
 */
function pura_salaus($salattu_data, $salasana) {

  if (empty($salattu_data)) {
    return null;
  }

  $avain = hash("SHA256", $salasana, true);

  $salattu_data_binaari = base64_decode($salattu_data);

  $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);

  // Tässä on "//NO_MB_OVERLOAD"-kommentti
  // jotta UTF8-konversio ei osu tähän riviin
  $iv = substr($salattu_data_binaari, 0, $iv_size); //NO_MB_OVERLOAD

  // Tässä on "//NO_MB_OVERLOAD"-kommentti
  // jotta UTF8-konversio ei osu tähän riviin
  $salattu_data_binaari = substr($salattu_data_binaari, $iv_size); //NO_MB_OVERLOAD

  return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $avain, $salattu_data_binaari, MCRYPT_MODE_CBC, $iv);
}

function parse_sertificate($cert) {
  // Otetaan sisään x509 sertificaatti

  // Palautetaan tällänen array
  $return = array(
    "valid_to" => "",
  );

  if (empty($cert)) {
    return $return;
  }

  // Parsitaan sertifikaatti
  $_cert = openssl_x509_parse($cert);

  // Otetaan valid to timestamp
  $_valid_to = date('Y-m-d H:i:s', $_cert['validTo_time_t']);
  $return["valid_to"] = $_valid_to;

  return $return;
}

function parameter($param) {
  return empty($param) ? '' : $param;
}


/**
 * Palauttaa tilin saldon. Toimii toistaiseksi ainoastaan OP:n kanssa
 *
 * @param array   $params
 *
 * @return array|bool Saldo ja valuutta tai false, jos jokin meni vikaan
 */
function hae_tilin_saldo($params) {
  global $kukarow;

  if (empty($params['pankkiyhteys_tunnus'])) return false;

  $query = "SELECT bic, tilino, hae_saldo
            FROM pankkiyhteys
            JOIN yriti ON (yriti.yhtio = pankkiyhteys.yhtio
              AND yriti.bic          = pankkiyhteys.pankki)
            WHERE pankkiyhteys.yhtio = '{$kukarow["yhtio"]}'
            AND pankkiyhteys.tunnus  = {$params['pankkiyhteys_tunnus']}
            LIMIT 1";
  $result = pupe_query($query);
  $pankkiyhteys = mysqli_fetch_assoc($result);

  if ($pankkiyhteys['hae_saldo'] != 1) return false;
  if ($pankkiyhteys['bic'] != 'OKOYFIHH') return false;

  $konttorinumero = substr($pankkiyhteys['tilino'], 0, 6);
  $tilinumero     = substr($pankkiyhteys['tilino'], -8);

  $params["file_type"]     = "TP1 1SS";
  $params["maksuaineisto"] = base64_encode("$\$TP1 1SS {$konttorinumero} {$tilinumero} X");

  $response         = sepa_upload_file($params);
  $saldo_vastaus    = $response['content'];

  if (empty($saldo_vastaus)) return false;

  $saldo_vastaus = base64_decode($saldo_vastaus);

  $saldo_kentat = array(
    "tietueen_jarjestysnumero"  => substr($saldo_vastaus, 0, 1),
    "vastaustyyppi"             => substr($saldo_vastaus, 1, 1),
    "varalla"                   => substr($saldo_vastaus, 2, 3),
    "tapahtumakonttorin_numero" => substr($saldo_vastaus, 5, 6),
    "paatenumero"               => substr($saldo_vastaus, 11, 2),
    "tapahtumanumero"           => substr($saldo_vastaus, 13, 4),
    "tilinomistajan_nimi"       => substr($saldo_vastaus, 17, 15),
    "konttorinumero"            => substr($saldo_vastaus, 32, 6),
    "tilinumero"                => substr($saldo_vastaus, 38, 8),
    "paivamaara"                => substr($saldo_vastaus, 46, 6),
    "saldo"                     => substr($saldo_vastaus, 52, 11),
    "saldon_etumerkki"          => substr($saldo_vastaus, 63, 1),
    "luottoraja"                => substr($saldo_vastaus, 64, 11),
    "luottorajan_etumerkki"     => substr($saldo_vastaus, 75, 1),
    "nostovara"                 => substr($saldo_vastaus, 76, 11),
    "nostovaran_etumerkki"      => substr($saldo_vastaus, 87, 1),
    "rahayksikon_koodi"         => substr($saldo_vastaus, 88, 1),
  );

  return array(
    "saldo"    => (float) ($saldo_kentat["saldo"] / 100),
    "valuutta" => $saldo_kentat["rahayksikon_koodi"] == 1 ? "EUR" : null
  );
}

function bank_provider($bank) {
  switch ($bank) {
  case 'aktia':
  case 'sp':
  case 'pop':
  case 'shb':
    return 'samlink';
  default:
    return $bank;
  }
}
