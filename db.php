<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require "inc/parametrit.inc";

echo "<table><tr><td valign='top' class='back'>";

$query  = "SHOW FULL TABLES FROM `$dbkanta` WHERE Table_Type = 'BASE TABLE'";
$result =  pupe_query($query);

while ($row=mysqli_fetch_array($result)) {
  echo "<a href='$PHP_SELF?table=$row[0]'>$row[0]</a><br>";
}

echo "</td><td class='back' valign='top'>";

if ($table!='') {
  $query  = "show columns from $table";
  $fields =  pupe_query($query);

  echo "<b>$table</b> (<a href='db.php?table=$table'>table</a> - <a href='db-index.php?table=$table'>index</a>)<br><br>";
  echo "<table>";
  echo "<tr><th>field</th><th>type</th><th>null</th><th>key</th><th>default</th><th>extra</th></tr>";

  $kala = array();

  while ($row=mysqli_fetch_array($fields)) {
    //tehdään array, että saadaan sortattua nimen mukaan..
    array_push($kala, "<tr><td>$row[0]</td><td>$row[1]</td><td>$row[2]</td><td>$row[3]</td><td>$row[4]</td><td>$row[5]</td></tr>");
  }

  sort($kala);

  foreach ($kala as $rivi) {
    echo "$rivi";
  }

  echo "</table>";
}

echo "</td></tr></table>";

require "inc/footer.inc";
