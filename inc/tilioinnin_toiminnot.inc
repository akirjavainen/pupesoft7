<?php

if ($_POST['ajax_toiminto'] == 'tilin_oletusalv') {
  $tili = sanitize_string($_POST['tilino']);

  $query = "SELECT oletus_alv
            FROM tili
            WHERE tilino = '{$tili}'
            AND yhtio    = '{$kukarow['yhtio']}'
            AND oletus_alv IS NOT NULL;";

  $result = pupe_query($query);

  if (mysqli_num_rows($result) == 1) {
    $row = mysqli_fetch_assoc($result);

    echo json_encode(
      array('vero' => preg_replace('/\.?0*$/', '', $row['oletus_alv']))
    );
    exit;
  }

  echo json_encode(
    array('empty' => ' ')
  );

}

if ($_POST['ajax_toiminto'] == 'hae_ostolaskun_tyypin_oletustili') {
  $oletustili = hae_ostolaskun_tyypin_oletustili($_POST['vienti'], $_POST['toimittaja_tunnus']);

  echo json_encode($oletustili);
}

exit;
