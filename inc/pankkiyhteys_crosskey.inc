<?php

// POP Pankin (BIC POPFFI22) Crosskey PKI File Transfer -yhteys. Tämä tiedosto ladataan vain
// funktiolla sepa_crosskey_lataa() (inc/pankkiyhteys_functions.inc), joka varmistaa ensin että
// PHP-versio ja xmlseclibs/wse-php-kirjasto ovat käytettävissä -- älä lataa suoraan.
//
// Ympäristö tulee SEPA_ENVIRONMENT-vakiosta: 'production' = tuotanto, muu = Crosskeyn stage.

use RobRichards\XMLSecLibs\XMLSecurityKey;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\WsePhp\WSSESoap;

if (SEPA_ENVIRONMENT == 'production') {
  define('SEPA_ADDRESS_CROSSKEY', 'https://pft.poppankki.fi/CorporateFileService-v1');
  define('SEPA_WSDL_CROSSKEY', 'https://pft.poppankki.fi/CertificateService-v1');
} else {
  define('SEPA_ADDRESS_CROSSKEY', 'https://popstagepft.crosskey.fi/CorporateFileService-v1');
  define('SEPA_WSDL_CROSSKEY', 'https://popstagepft.crosskey.fi/CertificateService-v1');
}

// ----------------------------------------------------------------------------------------
// Varoittaa jos pankkiyhteys-rivin customer_id ei ole sama kuin sertifikaatin CN. Crosskey
// myöntää sertifikaatin CN=[CustomerId] ja tunnistaa asiakkaan siitä, joten epäsuhta antaa
// vastauksen "07 Sopimus ei voimassa" vaikka kaikki muu olisi kunnossa:
// ----------------------------------------------------------------------------------------
function sepa_crosskey_tarkista_cert($pankkiyhteys, $cert_pem) {
  $parsed = openssl_x509_parse($cert_pem);

  if ($parsed === false) {
    echo "VAROITUS: signing_certificate ei ole luettavissa oleva X509-sertifikaatti." . PHP_EOL;
    return;
  }

  $cn = $parsed["subject"]["CN"] ?? "";
  $issuer = $parsed["issuer"]["CN"] ?? "";

  if (trim($cn) !== trim($pankkiyhteys["customer_id"])) {
    echo "VAROITUS: pankkiyhteys.customer_id='{$pankkiyhteys['customer_id']}', mutta sertifikaatin CN='{$cn}' (Issuer: {$issuer}). "
      . "Pankki tunnistaa asiakkaan näiden pitää olla samat." . PHP_EOL;
  }

  if ($parsed["validTo_time_t"] < time()) {
    echo "VAROITUS: sertifikaatti on vanhentunut " . date("Y-m-d H:i:s", $parsed["validTo_time_t"]) . "." . PHP_EOL;
  }
}
// ----------------------------------------------------------------------------------------

// ----------------------------------------------------------------------------------------
// Crosskey/POP Pankki PFT: FileType on nelinumeroinen koodi (PFT Technical Description 2.5,
// luku 6). Pupesoftin muualla käyttämät tyyppinimet (TITO, KTL, C2BN jne.) käännetään
// tässä; valmis nelinumeroinen koodi (esim. 1700 KTOX, 1250 REFX) kelpaa sellaisenaan:
// ----------------------------------------------------------------------------------------
function sepa_crosskey_file_type($file_type) {
  $muunnokset = array(
    "TITO" => "0700", // KTOF, konekielinen tiliote
    "KTON" => "0700",
    "KTL"  => "0250", // REFG, saapuvat viitemaksut
    "VIPN" => "0250",
    "C2BL" => "0100", // SCCT, maksuaineisto (pain.001)
    "C2BN" => "0130", // SPSR, maksuaineiston palautesanoma (pain.002)
    "NDCORPAYS" => "0100", // sepa.php / pankkiyhteys_send.inc lähettävät maksuaineiston näillä
    "NDCORPAYL" => "0130",
    // "LAHLASKUT" on harhaanjohtava nimi (Nordean "lähtevät verkkolaskut" -koodi, EFAK/0800):
    // pankkiyhteys_send.inc:n "LAHLASKUT"-haara ei lähetä verkkolaskuja ollenkaan, vaan
    // noutaa dataout/{pankki}_error/laskutus*-kansiosta tiedostot, joita YKSIN raportit/
    // tilisiirto.php kirjoittaa sinne ("laskutus_tilisiirto_*.xml") -- ja ne ovat tavallisia
    // yksittäisiä SEPA-maksuja (pain.001.001.02, ks. raportit/includes/class-sepa.php),
    // esim. palkanmaksu. Oikea koodi on siis sama SCCT/0100 kuin C2BL/NDCORPAYS:llä:
    "LAHLASKUT" => "0100",
    // HAELASKUT on sen sijaan oikea verkkolasku: pankkiyhteys_tilioteviite.inc noutaa sillä
    // oikeita saapuvia Finvoice-aineistoja pankista (DownloadFile, ei tämä upload-reitti):
    "HAELASKUT" => "0820", // EFAH, saapuvat verkkolaskut
  );

  $file_type = trim($file_type);

  return $muunnokset[$file_type] ?? $file_type;
}
// ----------------------------------------------------------------------------------------

// ----------------------------------------------------------------------------------------
// Aineistolistaus Crosskeyltä:
// ----------------------------------------------------------------------------------------
function sepa_download_file_list_crosskey(array $params): array | bool {
  global $kukarow, $yhtiorow, $sepa_pankkiyhteys_token;

  ini_set('display_errors', '1');
  ini_set('display_startup_errors', '1');
  error_reporting(E_ALL & ~E_DEPRECATED);

  // Noudetaan pankkiyhteyteen vaaditut tiedot tietokannasta:
  $tunnus = parameter($params["pankkiyhteys_tunnus"]);
  $salasana = parameter($params["pankkiyhteys_salasana"]);
  // file_type: Pupesoftin nimi (TITO, KTL, C2BN...) tai suoraan Crosskeyn nelinumeroinen koodi.
  // status: NEW (oletus, vain uudet), DLD (jo haetut) tai ALL.
  $file_type = sepa_crosskey_file_type(parameter($params["file_type"] ?? "") ?: "0700");
  $status = strtoupper(parameter($params["status"] ?? "") ?: "NEW");
  $pankkiyhteys = hae_pankkiyhteys($tunnus);

  if (!$pankkiyhteys) {
    echo "Pankkiyhteyttä tunnuksella $tunnus ei löytynyt (tarkista pankkiyhteys_tunnus)." . PHP_EOL;
    return false;
  }

  // Tarkistetaan salasana ja haetaan salaamaton private key:
  $_c = pura_salaus($pankkiyhteys["signing_certificate"], $salasana);
  $_pk = pura_salaus($pankkiyhteys["signing_private_key"], $salasana);

  // Jos salauksen purkaminen ei onnistu, lopetetaan tähän:
  if (!openssl_pkey_get_private($_pk)) {
    echo "Sertifikaatin salauksen purkaminen ei onnistunut." . PHP_EOL;
    return false;
  }

  sepa_crosskey_tarkista_cert($pankkiyhteys, $_c);

  // 1. Luodaan POP Pankin vaatima ApplicationRequest-rakenne. Ei Id-attribuuttia juurella --
  // Crosskeyn oma esimerkkipyyntö ei sellaista käytä, ks. alla:
  $xmlPayload = '<?xml version="1.0" encoding="UTF-8"?>' .
  '<ApplicationRequest xmlns="http://bxd.fi/xmldata/">' .
    '<CustomerId>' . $pankkiyhteys['customer_id'] . '</CustomerId>' .
    '<Command>DownloadFileList</Command>' .
    '<Timestamp>' . gmdate("c") . '</Timestamp>' .
    '<Status>' . $status . '</Status>' .
    '<Environment>' . strtoupper(SEPA_ENVIRONMENT) . '</Environment>' .
    '<SoftwareId>Pupesoft</SoftwareId>' .
    '<FileType>' . $file_type . '</FileType>' .
  '</ApplicationRequest>';

  $doc = new DOMDocument('1.0', 'UTF-8');
  $doc->preserveWhiteSpace = false;
  $doc->loadXML($xmlPayload);

  // 2. Alustetaan allekirjoitus. Nämä kolme (kanonisointi, digest, allekirjoitusalgoritmi)
  // on asetettu vastaamaan tarkasti Crosskeyn omaa esimerkkipyyntöä
  // (dowloadFileList-application-request_1.xml): C14N WithComments, SHA-1 digest,
  // RSA-SHA1 allekirjoitus -- ei Exclusive C14N eikä SHA-256:
  $objDSig = new XMLSecurityDSig('');
  $objDSig->setCanonicalMethod(XMLSecurityDSig::C14N_COMMENTS);

  // 2.1. $doc (ei $rootElement) addReference():n ensimmäisenä parametrina: xmlseclibs
  // kirjoittaa URI-attribuutin tyhjäksi (URI="") vain kun kohde on DOMDocument, ja force_uri
  // on tosi. $rootElement-kohteella se sen sijaan aina generoi ja liittää oman Id:n
  // (URI="#..."), mitä esimerkkipyynnössä ei ole -- sen <Reference URI=""> allekirjoittaa
  // koko dokumentin ilman erillistä Id:tä:
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
  // WS-Security-osuus alla on tarkistettu Crosskeyn omasta esimerkkipyynnöstä
  // (dowloadFileList-SOAP-request_1.xml): Header ei saa jäädä tyhjäksi
  // ('<soapenv:Header/>', kuten se oli täällä ennen) -- pankki vaatii wsse:Security-lohkon,
  // jossa on wsu:Timestamp ja erillinen ds:Signature Timestampin ja Bodyn ylle (eri
  // allekirjoitus kuin ApplicationRequestin oma, yllä):
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
            '<mod:ReceiverId>POPFFI22</mod:ReceiverId>' . // POP Pankin BIC, kuten Crosskeyn esimerkkipyynnöissä (POPFFI22)
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
  // allekirjoitettuna vain Timestamp ja Body -- ei BinarySecurityTokenia itseään:
  $wsse = new WSSESoap($soapDoc);
  $wsse->addTimestamp(3600);

  $objSoapKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, ['type' => 'private']);
  $objSoapKey->loadKey($_pk);
  $wsse->signSoapDoc($objSoapKey, ['algorithm' => XMLSecurityDSig::SHA1]);

  $soapToken = $wsse->addBinaryToken($_c);
  $wsse->attachTokentoSig($soapToken);

  // Crosskeyn esimerkkipyynnössä Security-elementillä ei ole mustUnderstand-attribuuttia
  // (vain pankin vastauksessa on). Poistetaan allekirjoituksen jälkeen: Security-elementti ei
  // ole itse allekirjoitettu, joten allekirjoitus ei muutu:
  foreach ($soapDoc->getElementsByTagNameNS(WSSESoap::WSSENS, 'Security') as $securityNode) {
    $securityNode->removeAttributeNS('http://schemas.xmlsoap.org/soap/envelope/', 'mustUnderstand');
  }

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

  // Ei TLS-asiakasvarmennetta: Crosskey PFT tunnistaa asiakkaan WS-Security-allekirjoituksesta
  // (signing_certificate), eikä erillistä salaussertifikaattia myönnetä:

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
// ----------------------------------------------------------------------------------------

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
// ----------------------------------------------------------------------------------------
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
// ----------------------------------------------------------------------------------------
function sepa_download_files_crosskey(array $params): array | bool {
  global $kukarow, $yhtiorow, $sepa_pankkiyhteys_token;

  ini_set('display_errors', '1');
  ini_set('display_startup_errors', '1');
  error_reporting(E_ALL & ~E_DEPRECATED);

  $tunnus = parameter($params["pankkiyhteys_tunnus"]);
  $salasana = parameter($params["pankkiyhteys_salasana"]);
  $file_type = sepa_crosskey_file_type(parameter($params["file_type"]));
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
    echo "Pankkiyhteyttä tunnuksella $tunnus ei löytynyt (tarkista pankkiyhteys_tunnus)." . PHP_EOL;
    return false;
  }

  // Tarkistetaan salasana ja haetaan salaamaton private key:
  $_c = pura_salaus($pankkiyhteys["signing_certificate"], $salasana);
  $_pk = pura_salaus($pankkiyhteys["signing_private_key"], $salasana);

  if (!openssl_pkey_get_private($_pk)) {
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
    $objDSig = new XMLSecurityDSig('');
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
              '<mod:ReceiverId>POPFFI22</mod:ReceiverId>' .
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

    // Crosskeyn esimerkkipyynnössä Security-elementillä ei ole mustUnderstand-attribuuttia
    // (vain pankin vastauksessa on). Poistetaan allekirjoituksen jälkeen: Security-elementti ei
    // ole itse allekirjoitettu, joten allekirjoitus ei muutu.
    foreach ($soapDoc->getElementsByTagNameNS(WSSESoap::WSSENS, 'Security') as $securityNode) {
      $securityNode->removeAttributeNS('http://schemas.xmlsoap.org/soap/envelope/', 'mustUnderstand');
    }

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
// ----------------------------------------------------------------------------------------

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
// ----------------------------------------------------------------------------------------
function sepa_upload_file_crosskey(array $params): array | bool {
  global $kukarow, $yhtiorow, $sepa_pankkiyhteys_token;

  ini_set('display_errors', '1');
  ini_set('display_startup_errors', '1');
  error_reporting(E_ALL & ~E_DEPRECATED);

  $tunnus = parameter($params["pankkiyhteys_tunnus"]);
  $salasana = parameter($params["pankkiyhteys_salasana"]);
  $file_type = sepa_crosskey_file_type(parameter($params["file_type"]));
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
    echo "Pankkiyhteyttä tunnuksella $tunnus ei löytynyt (tarkista pankkiyhteys_tunnus)." . PHP_EOL;
    return false;
  }

  // Tarkistetaan salasana ja haetaan salaamaton private key:
  $_c = pura_salaus($pankkiyhteys["signing_certificate"], $salasana);
  $_pk = pura_salaus($pankkiyhteys["signing_private_key"], $salasana);

  if (!openssl_pkey_get_private($_pk)) {
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
  $objDSig = new XMLSecurityDSig('');
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
            '<mod:ReceiverId>POPFFI22</mod:ReceiverId>' .
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

  // Crosskeyn esimerkkipyynnössä Security-elementillä ei ole mustUnderstand-attribuuttia
  // (vain pankin vastauksessa on). Poistetaan allekirjoituksen jälkeen: Security-elementti ei
  // ole itse allekirjoitettu, joten allekirjoitus ei muutu.
  foreach ($soapDoc->getElementsByTagNameNS(WSSESoap::WSSENS, 'Security') as $securityNode) {
    $securityNode->removeAttributeNS('http://schemas.xmlsoap.org/soap/envelope/', 'mustUnderstand');
  }

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
// ----------------------------------------------------------------------------------------

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
// ----------------------------------------------------------------------------------------
function sepa_get_certificate_crosskey(array $params): array | bool {
  global $kukarow, $yhtiorow;

  ini_set('display_errors', '1');
  ini_set('display_startup_errors', '1');
  error_reporting(E_ALL & ~E_DEPRECATED);

  $tunnus = parameter($params["pankkiyhteys_tunnus"]);
  $salasana = parameter($params["pankkiyhteys_salasana"]);
  $transfer_key = trim((string)parameter($params["transfer_key"] ?? ""));

  $is_renewal = ($transfer_key === "");

  // Ensimmäinen haku voidaan tehdä myös ilman pankkiyhteys-riviä (uutta pankkiyhteyttä
  // luotaessa, pankkiyhteysadmin.php): silloin asiakastunnus annetaan "customer_id"-
  // parametrina eikä salasanaa/vanhaa avainta tarvita, koska riviä ei vielä ole.
  $uusi_yhteys = (!$is_renewal && empty($tunnus) && !empty($params["customer_id"]));

  if ($uusi_yhteys) {
    $pankkiyhteys = array("customer_id" => $params["customer_id"]);
  }
  else {
    $pankkiyhteys = hae_pankkiyhteys($tunnus);

    if (!$pankkiyhteys) {
      echo "Pankkiyhteyttä tunnuksella $tunnus ei löytynyt (tarkista pankkiyhteys_tunnus)." . PHP_EOL;
      return false;
    }
  }

  $customer_id = $pankkiyhteys['customer_id'];

  // Ensimmäisellä kerralla ei tarvita/käytetä nykyistä (Samlink-peräistä) sertifikaattia
  // mihinkään -- vain salasana tarkistetaan private keyn purkamisella, kuten muissa
  // crosskey-funktioissa. Uusinnassa nykyinen VOIMASSA OLEVA Crosskey-sertifikaatti+avain
  // sen sijaan tarvitaan allekirjoitukseen:
  $_old_pk = $uusi_yhteys ? null : pura_salaus($pankkiyhteys["signing_private_key"], $salasana);

  if (!$uusi_yhteys && !openssl_pkey_get_private($_old_pk)) {
    echo "Salasana on väärä, tai signing_private_key ei purkaudu!" . PHP_EOL;
    return false;
  }

  $_old_cert = $uusi_yhteys ? null : pura_salaus($pankkiyhteys["signing_certificate"], $salasana);

  if ($is_renewal) {
    if (empty($_old_cert)) {
      echo "Uusinta vaatii nykyisen Crosskey-sertifikaatin -- sitä ei ole, anna transfer_key ensimmäistä hakua varten." . PHP_EOL;
      return false;
    }
  }
  else {
    // 16 numeroa on spesifikaation (kohta 3.1) kiistaton vaatimus -- tämä pysäyttää.
    // Luhn-tarkiste sen sijaan vain VAROITTAA eikä pysäytä: se nojaa oletukseen että
    // pankin kahdessa osassa toimittama TransferKey muodostetaan yksinkertaisesti
    // liittämällä osat peräkkäin, mitä spesifikaatio ei sano suoraan eikä ole
    // vahvistettu -- oma paikallinen arvaus ei saa olla tiukempi portti kuin pankin
    // oma tarkistus:
    if (!preg_match('/^\d{16}$/', $transfer_key)) {
      echo "TransferKey ei näytä oikealta (pitäisi olla 16 numeroa)." . PHP_EOL;
      return false;
    }
    if (!crosskey_luhn_ok($transfer_key)) {
      echo "HUOM: TransferKeyn Luhn-tarkistenumero ei täsmää -- lähetetään silti, pankki päättää." . PHP_EOL;
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
    // Ilman ds:-etuliitetta, kuten esimerkissä (<Signature xmlns="...xmldsig#">)
    $objDSig = new XMLSecurityDSig('');
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

  // Ensimmäisen (allekirjoittamattoman) pyynnön kohdalla: WSSESoap::addTimestamp() lisää
  // AINA soapenv:mustUnderstand="1" Security-elementtiin (locateSecurityHeader()in
  // oletusarvo), mutta Crosskeyn omassa esimerkissä (getCertificate-soap-request_1.xml)
  // sitä ei ole -- vain paljas wsu:Timestamp, ei allekirjoitusta, ei mustUnderstandia.
  // Ensimmäinen yritys tällä koodilla sai pankilta "02 Signature verification failed",
  // mikä täsmää siihen että WS-Security-pino tulkitsee mustUnderstand="1" ilman
  // allekirjoitusta EPÄONNISTUNEEKSI tarkistukseksi sen sijaan että Security-lohko
  // olisi vain tarpeeton -- poistetaan attribuutti, jotta rakenne täsmää esimerkkiin
  // täsmälleen. (Uusinnassa jätetään mustUnderstand paikoilleen: sitä ei vielä ole
  // testattu Crosskeytä vasten, koska tähän ei ole voimassa olevaa Crosskey-
  // sertifikaattia allekirjoittamaan sitä.)
  //
  // Uusinnassa SOAP allekirjoitetaan myös: PFT-spesifikaatio sanoo että "CertApplication-
  // Request and SOAP don't need to be signed on the registration-call" -- eli uusinnassa
  // molemmat allekirjoitetaan, ja pankin vastauskoodi 02 tarkoittaa nimenomaan
  // "SOAP signature error: Soap-level signature verification failed". Sama Timestamp+Body-
  // allekirjoitus (SHA-1, exc-c14n, BinarySecurityToken) kuin muissa kolmessa toimivassa
  // operaatiossa, vanhalla (vanhenevalla) sertifikaatilla ja avaimella:
  if ($is_renewal) {
    $objSoapKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, ['type' => 'private']);
    $objSoapKey->loadKey($_old_pk);
    $wsse->signSoapDoc($objSoapKey, ['algorithm' => XMLSecurityDSig::SHA1]);

    $soapToken = $wsse->addBinaryToken($_old_cert);
    $wsse->attachTokentoSig($soapToken);
  }

  // mustUnderstand pois muissa kolmessa toimivassa operaatiossa, kuten Crosskeyn esimerkeissä:
  $securityNodes = $soapDoc->getElementsByTagNameNS(WSSESoap::WSSENS, 'Security');

  foreach ($securityNodes as $securityNode) {
    $securityNode->removeAttributeNS('http://schemas.xmlsoap.org/soap/envelope/', 'mustUnderstand');
  }

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
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);

  // Sertifikaatin myöntäminen on hidas operaatio pankin päässä: 30 s ei riittänyt
  // testiympäristössä (0 tavua vastaanotettu), vaikka pyyntö oli jo perillä ja
  // käsiteltävänä -- ks. timeout-haara alla.
  curl_setopt($ch, CURLOPT_TIMEOUT, 120);

  $response = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $curlErrno = curl_errno($ch);
  $curlError = $curlErrno ? curl_error($ch) : "";
  curl_close($ch);

  if ($curlError !== "") {
    echo "cURL virhe: " . $curlError . PHP_EOL;
    echo "cURL vastaus: " . $response . PHP_EOL;

    // Aikakatkaisu jättää lopputuloksen epäselväksi: pankki on voinut myöntää
    // sertifikaatin ja käyttää TransferKeyn, vaikka vastaus ei koskaan ehtinyt
    // perille -- ja tässä generoitu private key on silloin ainoa kappale, joka
    // sertifikaattiin sopii. Talletetaan se (vain omistajan luettavissa), jotta
    // pankilta pyydetty sertifikaatti on jälkikäteen käyttökelpoinen, äläkä yritä
    // samalla TransferKeylla uudestaan ennen kuin pankki on kertonut tilanteen:
    if ($curlErrno === CURLE_OPERATION_TIMEDOUT && !$is_renewal) {
      $edellinen_umask = umask(0077);
      $avain_tiedosto = sys_get_temp_dir() . "/crosskey_" . preg_replace('/\D/', '', (string)$customer_id)
        . "_" . date("Ymd_His") . ".key.pem";
      $csr_tiedosto = preg_replace('/\.key\.pem$/', '.csr.pem', $avain_tiedosto);

      file_put_contents($avain_tiedosto, $uusi['private_key']);
      file_put_contents($csr_tiedosto, $uusi['csr']);
      umask($edellinen_umask);

      echo "HUOM: pankki on voinut käsitellä pyynnön ja myöntää sertifikaatin ennen aikakatkaisua." . PHP_EOL;
      echo "Tässä ajossa generoitu private key on talletettu: $avain_tiedosto" . PHP_EOL;
      echo "(CSR: $csr_tiedosto). Älä yritä samaa TransferKeytä uudelleen ennen kuin pankki" . PHP_EOL;
      echo "on kertonut, myönnettiinkö sertifikaatti -- pyydä tarvittaessa sen kopio tai uusi TransferKey." . PHP_EOL;
    }
    return false;
  }

  // Debug: koko SOAP-vastaus näkyviin testattaessa, kuten muillakin
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

  if ($responseCode === '201') {
    // Ei PFT-spesifikaation vastauskoodilistassa (kohta 5), mutta tekstin mukaan pankki
    // pitää tätä uusintana: asiakkaalla on jo voimassa oleva sertifikaatti, ja se on
    // vielä uusintaikkunan (60 vrk ennen vanhenemista) ulkopuolella. Ensimmäisellä
    // hakukerralla se tarkoittaa yleensä, että sertifikaatti on jo myönnetty --
    // esim. aiemmalla, aikakatkaisuun kaatuneella pyynnöllä, jonka vastaus hukkui:
    echo "HUOM: pankin mukaan tälle asiakkaalle on jo sertifikaatti eikä sitä voi vielä uusia." . PHP_EOL;
    echo "Jos aiempi haku kaatui aikakatkaisuun, sertifikaatti on voitu myöntää ja avain jäi" . PHP_EOL;
    echo "tallentamatta -- pyydä pankilta sertifikaatin kopio tai uusi TransferKey." . PHP_EOL;
  }

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

  $result = array(
    "signing_certificate" => $certPem,
    "signing_private_key" => $uusi['private_key'],
  );

  // Tulostetaan sertifikaatti ruudulle siltä varalta, että tietokantaan tallennus epäonnistuu
  // (jotta ei tarvitse kysellä pankista avaimen nollauksia):
  echo "Sertifikaatti:" . PHP_EOL;
  print_r($result);
  echo PHP_EOL;

  return $result;
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

// ----------------------------------------------------------------------------------------
/**
 * Palauttaa tilin saldon POP Pankilta (Crosskey) 0710 (SALD, Balances) -tiedostolla.
 *
 * PFT Technical Description 2.5, luku 4: 0710 voidaan ladata suoraan DownloadFilella antamalla
 * <FileReference>:ksi tilin IBAN ilman edeltävää DownloadFileListiä; pankki muodostaa
 * tiedoston pyynnön aikana. Tiedosto on samaa T-tietuemuotoa kuin konekielinen tiliote
 * (Crosskeyn esimerkkivastauksen sisältö alkaa "T00"), joten saldo luetaan saldotietueesta
 * T40 samoista kohdista kuin inc/tiliote.inc lukee sen.
 *
 * @param array $params pankkiyhteys_tunnus, pankkiyhteys_salasana, iban, valinnainen debug
 *
 * @return array|bool ["saldo", "kaytettavissa", "valuutta"] tai false jos jokin meni vikaan
 */
// ----------------------------------------------------------------------------------------
function hae_tilin_saldo_crosskey($params) {
  $iban = strtoupper(str_replace(" ", "", parameter($params["iban"] ?? "")));

  if (empty($params['pankkiyhteys_tunnus']) or $iban == "") return false;

  $tiedostot = sepa_download_files_crosskey(array(
    "pankkiyhteys_tunnus"   => $params["pankkiyhteys_tunnus"],
    "pankkiyhteys_salasana" => $params["pankkiyhteys_salasana"],
    "file_type"             => "0710",
    "viitteet"              => array($iban),
    "start_date"            => date("Y-m-d"),
  ));

  if (empty($tiedostot) or $tiedostot[0]["status"] != "OK" or empty($tiedostot[0]["data"])) {
    return false;
  }

  $sisalto = base64_decode($tiedostot[0]["data"]);

  if (!empty($params["debug"])) {
    echo "0710-vastaus ({$iban}):" . PHP_EOL . $sisalto . PHP_EOL;
  }

  // Tietueet erotetaan rivinvaihdoilla; jos pankki ei niitä käytä, etsitään T40 suoraan:
  foreach (preg_split('/\r\n|\r|\n/', $sisalto) as $tietue) {
    if (substr($tietue, 0, 3) != "T40") continue;

    // Etumerkki + 18 numeroa (16 kok + 2 desi), kuten inc/tiliote.inc:ssä:
    $saldo = substr($tietue, 12, 19);
    $kaytettavissa = substr($tietue, 31, 19);

    if (!preg_match('/^[+-]\d{18}$/', $saldo)) continue;

    return array(
      "saldo"         => (float) ($saldo / 100),
      "kaytettavissa" => preg_match('/^[+-]\d{18}$/', $kaytettavissa) ? (float) ($kaytettavissa / 100) : null,
      "valuutta"      => "EUR",
    );
  }

  echo "0710-vastauksesta ei löytynyt saldotietuetta (T40) tilille {$iban}." . PHP_EOL;

  return false;
}
// ----------------------------------------------------------------------------------------

