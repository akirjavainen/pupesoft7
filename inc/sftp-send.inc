<?php

if (@include_once "inc/functions.inc");
elseif (@include_once "functions.inc");
else exit;

// Parametrit:
// $ftphost --> FTP-palvelin
// $ftpuser --> Käyttäjätunnus
// $ftppass --> Salasana
// $ftppath --> Kansio FTP-palvelimella jonne failit työnnetään
// $ftpfile --> Siirrettävä tiedosto
// $ftpport --> Custom portti, ei pakollinen
// $ftpskey --> SSH avain, ei pakollinen

// $ftpfail --> Kansio jonne laitetaan failit jos siirto feilaa, ei pakollinen
// $ftpsucc --> Kansio jonne tallennetaan failit jos siirto onnistuu, ei pakollinen
// $ftptmpr --> Käytetäänkö .TMP päätettä siirron aikana.

// Palautetaan:
// $palautus

// Debug:
// echo "ftphost --> $ftphost<br>";
// echo "ftpuser --> $ftpuser<br>";
// echo "ftppass --> $ftppass<br>";
// echo "ftppath --> $ftppath<br>";
// echo "ftpfile --> $ftpfile<br>";
// echo "ftpport --> $ftpport<br>";
// echo "ftpfail --> $ftpfail<br>";
// echo "ftpsucc --> $ftpsucc<br>";

$palautus = "";
$tulos_ulos_ftp  = "";
$sftpport = !empty($ftpport) ? $ftpport : 22;
$filenimi = basename($ftpfile);

// jos viimeinen merkki pathissä ei ole kauttaviiva lisätään kauttaviiva...
if (substr($ftppath, -1) != "/") {
  $ftppath .= "/";
}

if (substr($ftppass, 0, 4) == "/CLI") {
  # Use command line sftp with ssh key auth
  $ftppass = substr($ftppass, 4);
  $cmd = "sftp -o \"IdentityFile={$ftppass}\" {$ftpuser}@{$ftphost}: <<< $'put {$ftpfile}'";
  exec($cmd, $devnull, $retval);

  if ($retval != 0) {
    $palautus = "SFTP VIRHE";
  }
}
else {
  # Use PHP sftp funtions
  $connection = ssh2_connect($ftphost, $sftpport);

  if (!$connection) {
    $palautus = "Could not connect to remote host. ($ftphost)";
  }

  if ($palautus == "") {
    if (!empty($ftpskey)) {
      @ssh2_auth_pubkey_file($connection, $ftpuser, $ftpskey.'.pub', $ftpskey);
    }

    if (!ssh2_auth_password($connection, $ftpuser, $ftppass)) {
      $palautus = "Could not login to remote host ($ftpuser, $ftppass)";
    }
  }

  if ($palautus == "") {
    $sftp = ssh2_sftp($connection);

    if (!$sftp) {
      $palautus = "Could not initialize SFTP subsystem";
    }
  }

  if ($palautus == "") {
    $remotefile = $ftppath.basename($ftpfile);

    $stream = fopen("ssh2.sftp://$sftp/$remotefile", "w");

    if (!$stream) {
      $palautus = "Could not open file ($remotefile)";
    }

    if ($palautus == "") {
      $data_to_send = file_get_contents($ftpfile);

      if (fwrite($stream, $data_to_send) === false) {
        $palautus = "Could not send data from file: $ftpfile";
      }

      fclose($stream);
    }
  }
}

// Siirto onnistui
if ($palautus == "" and isset($ftpsucc) and $ftpsucc != "") {
  // Siirretään faili talteen jos siirto onnasi ja ok failit halutaan tallentaa
  rename($ftpfile, $ftpsucc."/".$filenimi);
}
elseif ($palautus != "" and isset($ftpfail) and $ftpfail != "") {
  // Siirretään faili outboxiin odottamaan uutta yritystä jos siirto feilasi
  rename($ftpfile, $ftpfail."/".$filenimi);
}

// logia talteen
if ($palautus == "") {
  pupesoft_log('sftp_send', "Tiedoston '{$ftpfile}' lähetys palvelimelle '{$ftphost}' onnistui");
}
else {
  pupesoft_log('sftp_send', "Tiedoston '{$ftpfile}' lähetys palvelimelle '{$ftphost}' epäonnistui: {$palautus}");
}

// jos siirto epäonnistuu ja faildirri ei oo setattu nin lähetetään maili
// jos faildirri ON setattu niin ei ekoteta eikä meilata mittään
if ($palautus != "" and (!isset($ftpfail) or $ftpfail == "")) {

  $rivi  = "{$_SERVER['SCRIPT_NAME']}\n";
  $rivi .= "\n";
  $rivi .= t("Tiedoston")." '$ftpfile' ".t("lähetys epäonnistui")."!\n";
  $rivi .= "\n";
  $rivi .= "$cmd\n";
  $rivi .= "\n";
  $rivi .= "$palautus\n";

  $boob = mail($yhtiorow['alert_email'], mb_encode_mimeheader(t("Tiedostonsiirto epäonnistui")."!", "ISO-8859-1", "Q"), $rivi, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");

  if ($boob === FALSE) {
    $tulos_ulos_ftp .= "<font class='error'>\n";
    $tulos_ulos_ftp .= t("Tiedostonsiirto epäonnistui! Ja meilin lähettäminen")." $yhtiorow[alert_email] ".t("epäonnistui myös!")."\n";
    $tulos_ulos_ftp .= "</font><br>\n";

    $tulos_ulos_ftp .= "<br><pre>$rivi</pre><br>\n";
  }
  else {
    $tulos_ulos_ftp .= "<font class='error'>FAILED! (".t("lähetettiin sähköposti")." $yhtiorow[alert_email])</font><br>\n";
  }
}

//echotaan rudulle jos kyseessä ei ole batch-ajo
if (!isset($tulos_ulos) or $tulos_ulos == "") {
  echo $tulos_ulos_ftp;
}
elseif ($tulos_ulos == "edi") {
  $edi_ulos .= strip_tags($tulos_ulos_ftp);
}
