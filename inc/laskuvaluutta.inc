<?php
echo "<font class='head'>".t("Laskut per valuutta")."</font><hr>";

$query = "SELECT valkoodi, sum(summa) summa, sum(summa * valuu.kurssi) kotisumma, count(*) kpl
          FROM lasku
          LEFT JOIN valuu ON valuu.yhtio = lasku.yhtio and lasku.valkoodi  = valuu.nimi
          WHERE lasku.yhtio = '$kukarow[yhtio]'
          and lasku.tila    in ('H','M','P','Q')
          GROUP BY valkoodi
          ORDER BY valkoodi";
$result = pupe_query($query);

echo "<table>";
echo "<tr><th>".t("Valkoodi")."</th><th>".t("Summa valuutassa")."</th><th>".t("Summa")."</th><th>".t("Kpl")."</th></tr>";

while ($trow=mysqli_fetch_array($result)) {
  echo "<tr>";

  echo "<td>$trow[valkoodi]</td>";
  echo "<td style='text-align: right;'>".sprintf("%.2f", $trow["summa"])."</td>";
  echo "<td style='text-align: right;'>".sprintf("%.2f", $trow["kotisumma"])."</td>";
  echo "<td style='text-align: right;'>$trow[kpl]</td>";
  echo "</tr>";
}
echo "</table><br>";
