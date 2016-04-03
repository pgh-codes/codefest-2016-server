<?php

class db {
	function __construct() {
		$this->link = mysqli_connect("127.0.0.1", "trash", "trash", "trash") or die(mysqli_error($link));
	}
	
	function query($query) {
		$result = mysqli_query($this->link, $query) or die(mysqli_error($this->link));
		return $result;
	}
	
	function get_one($query) {
		$result = query($query);
		$row = mysqli_fetch_row($result);
		return $row[0];
	}
	
	function get_row($query) {
		$result = query($query);
		return mysqli_fetch_assoc($result);
	}
	
	function get_col($query) {
		$result = query($query);
		$return_array = array();
		while($row = mysqli_fetch_row($result)) {
			$return_array[] = $row[0];
		}
		return $return_array;
	}
	
	function get_all($query) {
		$result = query($query);
		$return_array = array();
		while($row = mysqli_fetch_assoc($result)) {
			$return_array[] = $row;
		}
		return $return_array;
	}
	
	function clean($string) {
		return mysqli_real_escape_string($this->link, $string);
	}
}

?>