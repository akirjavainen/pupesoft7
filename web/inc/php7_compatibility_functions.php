<?php

function mysqli_field_len($result, $field_offset) {
	$properties = mysqli_fetch_field_direct($result, $field_offset);
	return is_object($properties) ? $properties->length : null;
}

function mysqli_field_name($result, $field_offset) {
	if (is_bool($result)) return false;
	$properties = mysqli_fetch_field_direct($result, $field_offset);
	return is_object($properties) ? $properties->name : false;
}

function mysqli_field_type($result, $field_offset ) {
	static $types;

	$type_id = mysqli_fetch_field_direct($result,$field_offset)->type;

	if (!isset($types)) {
		$types = array();
		$constants = get_defined_constants(true);
		foreach ($constants['mysqli'] as $c => $n) if (preg_match('/^MYSQLI_TYPE_(.*)/', $c, $m)) $types[$n] = $m[1];
	}

	return array_key_exists($type_id, $types)? $types[$type_id] : NULL;
}

function is_variable_empty($variable, $allow_zero_length = false) {
	if ($allow_zero_length && mb_strlen($variable) == 0) return false; // If "" is allowed, but other checks performed

	if ($variable == "0000-00-00 00:00:00") return true;
	if ($variable == "0000-00-00") return true;
	if ($variable == "0") return false;
	if (Empty($variable)) return true;
	if ($variable == "") return true;
	if ($variable == " ") return true;
	if (mb_strlen($variable) <= 0) return true;
	if ($variable == NULL) return true;

	return false;
}

function sanitize_string($string) {
	// Paranoid sanitize, but do NOT use for filesystem (slash allowed):
	if (is_variable_empty($string)) return "";
	$string = preg_replace_callback("/([^a-z0-9.,-_\/ ])/i", function($matches) { return ""; }, $string);
	//if (mb_strlen($string) > $allowedlength) $string = mb_substr($string, 0, $allowedlength);
	return $string;
}

