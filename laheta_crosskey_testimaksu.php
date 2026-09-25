<?php

// Lähettää yhden 1,00 EUR:n testimaksun (pain.001.001.03) POP Pankin/Crosskeyn TESTIympäristöön
// sepa_upload_file_crosskey():llä. Testiympäristössä maksu ei liiku minnekään; tarkoitus on
// nähdä että UploadFile hyväksytään (00 OK). Älä aja tätä tuotantoympäristöä vastaan!
//
// Käyttö:
//   php laheta_crosskey_testimaksu.php <pankkiyhteys_tunnus> <salasana> <maksajan IBAN> [maksajan nimi]
//
// Jälkeenpäin pankin palautesanoma (pain.002) haetaan tiedostolistalla:
//   sepa_download_file_list_crosskey(["file_type" => "C2BN", "status" => "ALL", ...])

if (php_sapi_name() != 'cli') {
  die("Tätä scriptiä voi ajaa vain komentoriviltä!\n");
}

if (trim($argv[1] ?? '') == '' or trim($argv[2] ?? '') == '' or trim($argv[3] ?? '') == '') {
  die("Käyttö: php laheta_crosskey_testimaksu.php <pankkiyhteys_tunnus> <salasana> <maksajan IBAN> [maksajan nimi]\n");
}

$iban = strtoupper(str_replace(" ", "", $argv[3]));
$nimi = htmlspecialchars(trim($argv[4] ?? 'PUUTYÖ OY TESTIASIAKAS'), ENT_XML1, 'UTF-8');

ini_set("include_path", ini_get("include_path") . PATH_SEPARATOR . dirname(__FILE__));
error_reporting(E_ALL);
ini_set("display_errors", 1);

require "/var/www/robrichards/vendor/autoload.php";

require "inc/connect.inc";
require "inc/functions.inc";
require "inc/pankkiyhteys_functions.inc";

// Seuraava arkipäivä eräpäiväksi
$pvm = new DateTime("tomorrow");
while ($pvm->format("N") >= 6) $pvm->modify("+1 day");
$eräpäivä = $pvm->format("Y-m-d");

$msgid = "PUPETEST" . date("YmdHis");

$xml = '<?xml version="1.0" encoding="UTF-8"?>' .
'<Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.03" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' .
  '<CstmrCdtTrfInitn>' .
    '<GrpHdr>' .
      '<MsgId>' . $msgid . '</MsgId>' .
      '<CreDtTm>' . date("Y-m-d\TH:i:s") . '</CreDtTm>' .
      '<NbOfTxs>1</NbOfTxs>' .
      '<CtrlSum>1.00</CtrlSum>' .
      '<InitgPty><Nm>' . $nimi . '</Nm></InitgPty>' .
    '</GrpHdr>' .
    '<PmtInf>' .
      '<PmtInfId>' . $msgid . '-1</PmtInfId>' .
      '<PmtMtd>TRF</PmtMtd>' .
      '<BtchBookg>true</BtchBookg>' .
      '<NbOfTxs>1</NbOfTxs>' .
      '<CtrlSum>1.00</CtrlSum>' .
      '<PmtTpInf><SvcLvl><Cd>SEPA</Cd></SvcLvl></PmtTpInf>' .
      '<ReqdExctnDt>' . $eräpäivä . '</ReqdExctnDt>' .
      '<Dbtr><Nm>' . $nimi . '</Nm></Dbtr>' .
      '<DbtrAcct><Id><IBAN>' . $iban . '</IBAN></Id></DbtrAcct>' .
      '<DbtrAgt><FinInstnId><BIC>POPFFI22</BIC></FinInstnId></DbtrAgt>' .
      '<ChrgBr>SLEV</ChrgBr>' .
      '<CdtTrfTxInf>' .
        '<PmtId><InstrId>1</InstrId><EndToEndId>PUPETEST-1</EndToEndId></PmtId>' .
        '<Amt><InstdAmt Ccy="EUR">1.00</InstdAmt></Amt>' .
        '<CdtrAgt><FinInstnId><BIC>NDEAFIHH</BIC></FinInstnId></CdtrAgt>' .
        '<Cdtr><Nm>Testivastaanottaja</Nm></Cdtr>' .
        '<CdtrAcct><Id><IBAN>FI2112345600000785</IBAN></Id></CdtrAcct>' .
        '<RmtInf><Ustrd>Pupesoft Crosskey testi</Ustrd></RmtInf>' .
      '</CdtTrfTxInf>' .
    '</PmtInf>' .
  '</CstmrCdtTrfInitn>' .
'</Document>';

echo "Lähetetään testimaksu {$msgid} (eräpäivä {$eräpäivä}, maksajan tili {$iban})...\n";

$vastaus = sepa_upload_file_crosskey(array(
  "pankkiyhteys_tunnus"   => (int) $argv[1],
  "pankkiyhteys_salasana" => $argv[2],
  "file_type"             => "C2BL",
  "maksuaineisto"         => base64_encode($xml),
  "filename"              => $msgid . ".xml",
));

echo "\n";
var_dump($vastaus);
