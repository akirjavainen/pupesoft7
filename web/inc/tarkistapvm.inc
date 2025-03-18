<?php
$query = "SELECT TO_DAYS('$pvm')"; // huh huh mikä häkki!!!
$result = pupe_query($query);
$pvmrow=mysqli_fetch_array($result);
$pvm=$pvmrow[0] + 0;
$query = "SELECT FROM_DAYS($pvm)"; // huh huh mikä häkki!!!
$result = pupe_query($query);
$pvmrow=mysqli_fetch_array($result);
$pvm=$pvmrow[0];
