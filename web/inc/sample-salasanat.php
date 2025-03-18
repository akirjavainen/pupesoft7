<?php

date_default_timezone_set('Europe/Helsinki');

$dbhost   = 'localhost';
$dbuser   = 'root';
$dbpass   = 'MYSQL SALASANA';
$dbkanta  = 'pupesoft';

$unifaun_ps_host = 'ftp.apport.net';
$unifaun_ps_user = 'APPORT KAYTTAJA';
$unifaun_ps_pass = 'APPORT SALASANA';
$unifaun_ps_path = '/in';

// Pankki-integraatio:
$sepa_pankkiyhteys_token = "";
$sepa_pankkiyhteys_salasana = "";

$palvelin = 'http://192.168.1.2/pupesoft/';

if (isset($_SERVER['SERVER_PORT']) and $_SERVER['SERVER_PORT'] == '443') {
    $palvelin = 'https://192.168.1.2/pupesoft/';              
}

