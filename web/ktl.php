<?php

function readPayments()
{
	$output = shell_exec("bash tiliote.sh /net/upload/Tiliotteet /tmp");
	echo "<html>\n";
	echo "Luetaan tiliotteet kansiosta /net/upload/Tiliotteet, jos kansiossa sellaisia on...<br>\n";
	echo "$output<br>\n";
	echo "Tiliotteet luettu!<br>\n";
	echo "</html>\n";
	return true;
}

readPayments();
