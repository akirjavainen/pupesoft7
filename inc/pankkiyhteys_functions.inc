<?php

define('SEPA_ENVIRONMENT', 'production');
define('SEPA_ADDRESS', 'https://sepa.ahkio.com');
define('SEPA_ADDRESS_SAMLINK', 'https://ws.samlink.fi/services/CorporateFileService'); // MUOKKAUS: Lisatty
define('SEPA_WSDL_SAMLINK', 'https://ws.samlink.fi/wsdl/CertificateService.xml'); // MUOKKAUS: Lisatty varmennepalvelun osoite

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

// MUOKKAUS: Lisatty suora tuki POP Pankille:
// ---
function sepa_download_file_list_samlink(array $params): array | bool {
	global $kukarow, $yhtiorow, $sepa_pankkiyhteys_token;

	ini_set('display_errors', '1');
	ini_set('display_startup_errors', '1');
	error_reporting(E_ALL & ~E_DEPRECATED);

	// Noudetaan pankkiyhteyteen vaaditut tiedot tietokannasta:
	$tunnus = parameter($params["pankkiyhteys_tunnus"]);
	$salasana = parameter($params["pankkiyhteys_salasana"]);
	//$pankkiyhteys = hae_pankkiyhteys_ja_pura_salaus($tunnus, $salasana);
	$pankkiyhteys = hae_pankkiyhteys($tunnus);

	if (!$pankkiyhteys) {
		return false;
	}

	// Tarkistetaan salasana ja haetaan salaamaton private key:
	$_c = pura_salaus($pankkiyhteys["signing_certificate"], $salasana);
	$_pk = pura_salaus($pankkiyhteys["signing_private_key"], $salasana);
	$_ssl = pura_salaus($pankkiyhteys["encryption_private_key"], $salasana);

	// Jos salauksen purkaminen ei onnistu, lopetetaan tähän:
	if (!openssl_pkey_get_private($_pk)) {
		return false;
	}

	// SOAP Envelope:
	$soap_envelope = '<?xml version="1.0" encoding="utf-8" standalone="no"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cor="http://bxd.fi/CorporateFileService" xmlns:mod="http://model.bxd.fi">
   <soapenv:Header/>
   <soapenv:Body>
      <cor:ApplicationRequest></cor:ApplicationRequest>
   </soapenv:Body>
</soapenv:Envelope>';

	// DOM:
	$doc = new DOMDocument("1.0", "UTF-8");
	$doc->loadXML($soap_envelope);
	$application_request = $doc->createElement("ApplicationRequest");
	$application_request->setAttribute("xmlns", "http://bxd.fi/xmldata/");
	$application_request->appendChild($doc->createElement("CustomerID", $pankkiyhteys["customer_id"]));
	$application_request->appendChild($doc->createElement("TimeStamp", date("c")));
	$application_request->appendChild($doc->createElement("Environment", SEPA_ENVIRONMENT)); // production/test
	//$application_request->appendChild($doc->createElement("TargetID", "POPFFI22"));
	$application_request->appendChild($doc->createElement("TargetID", "POPPANKKI"));
	$application_request->appendChild($doc->createElement("Encryption", "false"));
	$application_request->appendChild($doc->createElement("Compression", "true"));
	$application_request->appendChild($doc->createElement("SoftwareID", "Pupesoft"));
	$application_request->appendChild($doc->createElement("FileType", "OP")); // OP (vai KTL) = viitemaksut, TITO = tiliotteet
	$application_request->appendChild($doc->createElement("Command", "DownloadFileList"));
	$application_request->appendChild($doc->createElement("Status", "ALL")); // NEW/ALL/DLD
	//$application_request->appendChild($doc->createElement("Content", ""));
	//$application_request->appendChild($doc->createElement("StartDate", $start_date));
	//$application_request->appendChild($doc->createElement("EndDate", $end_date));

	// Lisää $application_request placeholderin ApplicationRequest tilalle:
	//$doc->appendChild($application_request);	
	$placeholder = $doc->getElementsByTagName("ApplicationRequest")->item(0);
	$placeholder->value = base64_encode($application_request->saveXML());
	//$placeholder->parentNode->replaceChild($application_request, $placeholder);
	//$doc->saveXML();

	$wsse = new WSSESoap($doc); // robrichards/wse-php
	$wsse->addTimestamp(300);

	// Sign ApplicationRequest:
	$signed_application_request = sepa_sign_xml(
		doc: $doc,
		wsse: $wsse,
		certificate: $pankkiyhteys["signing_certificate"],
		token: $sepa_pankkiyhteys_token,
		private_key: $_pk
	);
/*
	$final_xml = '<?xml version="1.0" encoding="utf-8" standalone="no"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cor="http://bxd.fi/CorporateFileService" xmlns:mod="http://model.bxd.fi">
   <soapenv:Header/>
   <soapenv:Body>
      <cor:ApplicationRequest>' . $signed_application_request->saveXML() . '</cor:ApplicationRequest>
   </soapenv:Body>
</soapenv:Envelope>';
*/

	$final_xml = $signed_application_request->saveXML();
echo $final_xml . PHP_EOL . PHP_EOL;
exit;
	$response = sepa_request_samlink(
		command: "DownloadFileList",
		final_xml: $final_xml,
		token: $sepa_pankkiyhteys_token,
		certificate: $_c,
		private_key: $_pk
	);

	print_r($response);
	exit();

	// Tarkistus:
	if (!vastaus_kunnossa($response["code"])) {
		return false;
	}

	// Lisätään target id_responseen, niin ei tartte hakea uudestaan jos kutsutaan peräkkäin
	$response["target_id"] = $target_id;
	return $response;
}

function sepa_sign_xml(DOMDocument $doc, WSSESoap $wsse, string $certificate, string $token, string $private_key): DOMDocument {
	ini_set('display_errors', '1');
	ini_set('display_startup_errors', '1');
	error_reporting(E_ALL & ~E_DEPRECATED);

	// Security-objekti:
	$sig = new XMLSecurityDSig(); // robrichards/xmlseclibs
	$sig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);
	$sig->addReference(
		$doc,
		XMLSecurityDSig::SHA256,
		array("http://w3.org", XMLSecurityDSig::EXC_C14N),
		array("force_uri" => true)
	);

	// Avain:
	$key = new XMLSecurityKey(
		XMLSecurityKey::RSA_SHA256,
		array("type" => "private")
	);
	$key->passphrase = $token;
	$key->loadKey($private_key);

	// Sign XML:
	//$sig->sign($key);
	//$sig->add509Cert($certificate);
	//$sig->appendSignature($doc->documentElement);
	$wsse->signSoapDoc($key, ["insertBefore" => false]);
	$added_token = $wsse->addBinaryToken($token);
	$wsse->attachTokentoSig($added_token);

	// Valmis!
	return $doc;
}

function sepa_request_samlink(string $command, string $final_xml, string $token, string $certificate, string $private_key): string | bool {
	$ch = curl_init(SEPA_ADDRESS_SAMLINK);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    // SEPA XML vaatii oikean Content-Type -otsikon:
	$headers = [
        "Content-Type: text/xml; charset=utf-8",
        "SOAPAction: \"$command\""
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // XML-data:
	curl_setopt($ch, CURLOPT_POSTFIELDS, $final_xml);

	// Sertifikaatit:
	curl_setopt($ch, CURLOPT_SSLCERT_BLOB, $certificate);
	curl_setopt($ch, CURLOPT_SSLKEY_BLOB, $private_key);
	//curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $token);

    // Yhdistetaan:
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        echo "ERROR: $error";
        return false;
    }

    curl_close($ch);

    // Palautetaan vastaus (pankki vastaa yleensä XML-muodossa):
    return $response;
}
// ---

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
