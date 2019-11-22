<?php

function readBills()
{
	$output = shell_exec("bash /usr/local/bin/sendbills.sh");
	echo "<html>\n";
	echo "Aloitetaan verkkolaskujen luku ja l&auml;hetys...<br>\n";
	echo "$output<br>\n";
	echo "</html>\n";
	return true;
}

readBills();
