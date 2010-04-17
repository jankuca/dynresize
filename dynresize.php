<?php
require_once('./dynresize/dynresize-config.php');
require_once('./dynresize/dynresize.class.php');

/*
	I. Generate by type
	@param string $_GET['type'] // Type
	
	II. Generate by dimensions
	@param int $_GET['w'] // Target width in pixels
	@param int $_GET['h'] // Target height in pixels
	
	
	(If the type AND the target dimensions are specified, the dimensions are ommited in favor of the type.)
*/

$dynresize = new DynResize();
?>