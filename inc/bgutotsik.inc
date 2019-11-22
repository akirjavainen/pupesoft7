<?php
$ulos  = "0";
$ulos .= sprintf('%08.8d', $laskurow['masku_tili']);
$ulos .= date('ymd');
$ulos .= sprintf('%22.22s', mb_strtoupper($yhtiorow['nimi']));
$ulos .= sprintf('%35.35s', mb_strtoupper($yhtiorow['osoite']. " " . $yhtiorow['postino']. " " . $yhtiorow['postitp']));
$ulos .= '      '; // 6 tyhjää
$ulos .= 2;
$ulos .= ' '; // 1 tyhjä
fputs($toot, $ulos . "\r\n");
