<?php

/*
 * Copyright (C) 2009 Mike Dabbs and Vinny Troia (www.curvve.com)
 *
 * Version 1.10
 *
 * This file is part of TTFGen.
 *
 * TTFGen is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * TTFGen is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with TTFGen.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * Many thanks go to authors of code that we're used or derived from
 * to create TTFGen.
 */    

/* @name  : ttfgen.php
 * 
 * @param String $file      : Textfile on server
 * @param String $text      : Text (New Line : "|").
 * @param String $font      : Font name (default: arial)
 * @param int $fsize        : Font size (default: 24)
 * @param int $pos          : Justify (0-Left, 1-Right, 2-Center) (default: 0)
 * @param array $rgb        : RGB color array for background & text color, respectively
 * @param bool $trans       : If set, background is transparent (default: false)
 * @param String $type      : png, gif or jpg (default: png)
 * @param bool $blank       : If set, image is used as background and rendered twice (default: false)
 * @param String $margin    : Optional comma separated margins in a format similar to CSS (top,right,bottom,left)
 * @param int $width        : Optional fixed width in pixels.  String will word-wrap if set
 * @param int $height       : Optional fixed height in pixels
 * @param int $leading      : Optional number of pixels to add between each line of text (default: 0)
 * @param int $lineHeight   : Optional height of each line of text (in pixels) (default: 0)
 * @param String $transform : Optional text transform to apply (uppercase, lowercase, capitalize)
 */

// User configurable variables.
require 'config.php'; 


// End of user configurable variables

$file = $_REQUEST['file'] ? $_REQUEST['file'] : "ttf_text.txt";
if ($_REQUEST['file']) {
	$fp = @fopen($file, "r");
	while (!feof($fp)) $text .= fgets($fp)." ";  fclose($fp);
}
else 
	$text = str_replace(array("_","|"),array(" "," \n"), $_REQUEST['text']);

$text = str_replace(array("\\'","\\\""),array("'","\""), $text);

$pos     = $_REQUEST['pos']   ? $_REQUEST['pos'] : 0;
$basefont= $_REQUEST['font']  ? $_REQUEST['font'] : 'arial';
if (substr($basefont, -4) == '.ttf') $basefont = substr($basefont, 0, -4);
$font    = $font_prefix . $basefont . '.ttf';
$fsize   = $_REQUEST['fsize'] ? $_REQUEST['fsize'] : 24;
$fsizepx = $fsize;
$fsize   = $fsize * 72 / 100;
$rgb     = $_REQUEST['rgb']   ? $_REQUEST['rgb'] : '#fff,#000';
$type    = $_REQUEST['type']  ? $_REQUEST['type'] : 'png';
$trans   = isset($_REQUEST['trans'])  ? true : false;
$blank   = isset($_REQUEST['blank'])  ? true : false;
if ($blank)
	$trans = true;
$margin  = $_REQUEST['margin']   ? $_REQUEST['margin'] : '0,0,0,0'; // '' . ($fsize/4) . ',0,1,0';

$width   = $_REQUEST['width']   ? $_REQUEST['width'] : 0;
$height  = $_REQUEST['height']  ? $_REQUEST['height'] : 0;
$leading = $_REQUEST['leading']   ? $_REQUEST['leading'] : 0;
if (isset($_REQUEST['lineHeight']))
	$lineHeight = $_REQUEST['lineHeight'];
else
	$lineHeight = $fsizepx * 1.125;
if ($lineHeight < $fsize)
	$lineHeight = 0;

if (!file_exists($font))
	$font = $font_prefix . strtolower($basefont) . '.ttf';

$transform = $_REQUEST['transform']  ? $_REQUEST['transform'] : 'none';
if ($transform == 'uppercase')
	$text = strtoupper($text);
else if($transform == 'lowercase')
	$text = strtolower($text);
else if($transform == 'capitalize')
	$text = ucwords($text);
	
$browser = new Browser;

// Force gif if browser is IE6
if ($type == 'png' && $browser->Name == 'msie' && substr($browser->Version, 0, 1) == '6')
	$type = 'gif';

$md5text = md5($text);

$key = "${md5text}-${pos}-${basefont}-${fsize}-${rgb}-${trans}-${width}-${height}-${leading}-${blank}-${margin}-${lineHeight}.${type}";

$margin = explode(',', $margin);

switch (count($margin)) {
	case 1: $tm = $bm = $lm = $rm = (int)$margin[0]; break;
	case 2: $tm = $bm = (int)$margin[0]; $lm = $rm = (int)$margin[1]; break;
	case 3: $tm = (int)$margin[0];  $bm = (int)$margin[2]; $lm = $rm = (int)$margin[1]; break;
	case 4: $tm = (int)$margin[0]; $bm = (int)$margin[2]; $lm = (int)$margin[3]; $rm = (int)$margin[1]; break;
	default: $tm = $bm = $lm = $rm = 0; break;
}

do {

	if (!file_exists($cache_prefix . $key)) {
		$filename = $cache_prefix . uniqid('', true) . '.' . $type;
		if((int)$fsize < 24) 
			$im = imagettfpostext_small($text, $font, $fsize, $pos, $rgb, $trans, $leading, $width, $height, $tm, $rm, $bm, $lm);
		else
			$im = imagettfpostext($text, $font, $fsize, $pos, $rgb, $trans, $leading, $width, $height, $tm, $rm, $bm, $lm);

		switch ($type) {
			case "gif" :
				imagegif($im, $filename, 100);
				break;

			case "jpg" :
				imagejpeg($im, $filename, 100);
				break;

			case "png" :
				imagepng($im, $filename, 9);
				break;
		}

		if (@rename($filename, $cache_prefix . $key) == false)
			unlink($filename);
	}

} while (!file_exists($cache_prefix . $key));

header("Content-type: image/${type}");
header("Content-Length: ".(filesize($cache_prefix . $key)+2));
readfile_chunked($cache_prefix . $key, false);

// Uncomment this line if you  do not wish to cache the images
// unlink($cache_prefix . $key);

function imagettfbboxextended($size, $angle, $fontfile, $text) {
	/* this function extends imagettfbbox and includes within the returned array
	 the actual text width and height as well as the x and y coordinates the
	 text should be drawn from to render correctly.  This currently only works
	 for an angle of zero and corrects the issue of hanging letters e.g. jpqg */
	
	$bbox = imagettfbbox($size, $angle, $fontfile, $text);

	//calculate x baseline<br />
	if ($bbox[0] >= -1) {
		$bbox['x'] = abs($bbox[0] + 1) * -1;
	} 
	else {
		$bbox['x'] = abs($bbox[0] + 2);
	}

	//calculate actual text width
	$bbox['width'] = abs($bbox[2] - $bbox[0]);
	if ($bbox[0] < -1) {
		$bbox['width'] = abs($bbox[2]) + abs($bbox[0]) - 1;
	}

	//caculate y baseline<br />
	$bbox['y'] = abs($bbox[5] + 1);

	//calculate actual text height<br />
	$bbox['height'] = abs($bbox[7]) - abs($bbox[1]);
	if ($bbox[3] > 0) {
		$bbox['height'] = abs($bbox[7] - $bbox[1]) - 1;
	}

	return $bbox;
}

function getImageHeight($str, $font, $fsize, $Leading=0, $tm=0, $bm=0)
{
	$nl = count($str);

	$bh = imagettfbboxextended($fsize, 0, $font, 'WHYQ0()pyjg');
	return ($bh['height'] * $nl) + ($Leading * ($nl - 1)) + $tm + $bm;
}

function getImageWidth($str, $font, $fsize, $lm=0, $rm=0)
{
	$nl = count($str);

	$w = 0;
	for ($i = 0; $i < $nl; $i++) {
		$bh = imagettfbboxextended($fsize, 0, $font, $str[$i]);
//		if (($bh['width'] + (-$bh['x'])) > $w)
//			$w = $bh['width'] + (-$bh['x']);
		if ($bh['width'] > $w)
			$w = $bh['width'];
	}
	return $w + $lm + $rm;
}

function wrap($fontSize, $angle, $fontFace, $string, $width)
{
	$ret = "";
	 
	$arr = explode(' ', trim($string));
	 
	foreach ( $arr as $word ) {
		 
		$teststring = $ret.' '.$word;
		$testbox = imagettfbboxextended($fontSize, $angle, $fontFace, $teststring);
		if ($testbox[2] > $width) {
			$ret .= ($ret == ""? "" : "\n") . $word;
		} 
		else {
			$ret .= ($ret == ""? "" : ' ') . $word;
		}
	}
	 
	return $ret;
}

function imagettfpostext($text, $font, $fsize, $pos, $rgb, $trans, $Leading=0, $W=0, $H=0, $tm=0, $rm=0, $bm=0, $lm=0)
{
	global $blank;
	global $lineHeight;
	
	$bbox = imagettfbboxextended($fsize, 0, $font, $text);
	$str  = split("[\n]+", trim($text));

	if ($W) {
		$nL = count($str);
		for ($i = 0; $i < $nL; $i++) {
			$ns = wrap($fsize, 0, $font, $str[$i], $W);
			$nsa = split("[\n]+", $ns);
			if (count($nsa) > 1) {
				// remove existing string and replace with array
				array_splice($str, $i, 1, $nsa);
			}
		}
		$newText = implode("\n", $str);
		$bbox   = imagettfbboxextended($fsize, 0, $font, $newText);
	}

	$nL = count($str);

	if ($lineHeight > 0 && $H == 0)
		$H = $nL * $lineHeight;
	
	$W  = ($W == 0) ? getImageWidth($str, $font, $fsize, $lm, $rm) : $W + $lm + $rm;
	$H  = ($H == 0) ? getImageHeight($str, $font, $fsize, $Leading, $tm, $bm) : $H + $tm + $bm;

	$rgb = explode(",", $rgb);
	if (count($rgb) > 2) 
		$H *= 2;
	
	$im = @imagecreatetruecolor($W, $H) or die("sorry ...");
	
	if ($trans) {
		imagealphablending($im, false);
		imagesavealpha($im, true);
	}

	$bc  = explode("x", rgb2hex($rgb[0]));
	$bc  = imagecolorallocatealpha($im, $bc[0], $bc[1], $bc[2], $trans? 127 : 0);
	imagefill($im, 0, 0, $bc);
	if ($trans) {
		imagealphablending($im, true);
	}
	$tc  = explode("x", rgb2hex($rgb[1]));
	$tc  = imagecolorallocatealpha($im, $tc[0], $tc[1], $tc[2], $blank? 127 : 0);

	$fm = imagettfbboxextended($fsize, 0, $font, 'WHYQ0()pyjg');

	if ($lineHeight)
		$y = $tm + $lineHeight - ($fm['height'] - $fm['y']) - 1;
	else
		$y = $tm + $fm['y'];
	for ($i = 0; $i < $nL; $i ++) {
		$b = imagettfbboxextended($fsize, 0, $font, $str[$i]);
		$_W = $b['width'];
		//$_W = $b['width'] - $b['x'];
		switch ($pos) {
			case 0 : $_X = $lm; break;                    // Left
			case 1 : $_X = $W - $_W - $rm; break;         // Right
			case 2 : $_X = abs($W/2) - abs($_W/2); break; // Centered
		}
		imagettftext($im, $fsize, 0, $_X + $b['x'], $y, $tc, $font, $str[$i]);
		if ($lineHeight) {
			$y += $lineHeight;
		}
		else {
			$y += $fm['height'];
			$y += $Leading;
		}
	}

	if (count($rgb) > 2) {
		if (count($rgb) > 3) {
			if ($trans) {
				imagealphablending($im, false);
				imagesavealpha($im, true);
			}
			$bc  = explode("x", rgb2hex($rgb[2]));
			$bc  = imagecolorallocatealpha($im, $bc[0], $bc[1], $bc[2], $trans? 127 : 0);
			imagefilledrectangle($im, 0, $H/2, $W, $H, $bc);
			if ($trans) {
				imagealphablending($im, true);
			}
			$tc  = explode("x", rgb2hex($rgb[3]));
			//$tc  = imagecolorallocate($im, $tc[0], $tc[1], $tc[2]);
			$tc  = imagecolorallocatealpha($im, $tc[0], $tc[1], $tc[2], $blank? 127 : 0);
		}
		else {
			$tc  = explode("x", rgb2hex($rgb[2]));
			$tc  = imagecolorallocatealpha($im, $tc[0], $tc[1], $tc[2], $blank? 127 : 0);
		}

		//$y = $H/2 + $tm;
		if ($lineHeight)
			$y = $H/2 + $tm + $lineHeight - ($fm['height'] - $fm['y']) - 1;
		else
			$y = $H/2 + $tm + $fm['y'];
		for ($i = 0; $i < $nL; $i ++) {
			$b = imagettfbboxextended($fsize, 0, $font, $str[$i]);
			$_W = $b['width'];
			//$_W = $b['width'] - $b['x'];
			switch ($pos) {
				case 0 : $_X = $lm; break;                    // Left
				case 1 : $_X = $W - $_W - $rm; break;         // Right
				case 2 : $_X = abs($W/2) - abs($_W/2); break; // Centered
			}
			imagettftext($im, $fsize, 0, $_X + $b['x'], $y, $tc, $font, $str[$i]);
			if ($lineHeight) {
				$y += $lineHeight;
			}
			else {
				$y += $fm['height'];
				$y += $Leading;
			}
		}
	} 
	
	if ($trans) {
		imagealphablending($im, false);
		imagesavealpha($im, true);
		$bg_color = imagecolorat($im,1,1); 
		imagecolortransparent($im, $bg_color);  
	}
	return $im;
}

function imagettfpostext_small($text, $font, $fsize, $pos, $rgb, $trans, $Leading=0, $W=0, $H=0, $tm=0, $rm=0, $bm, $lm)
{
	global $lineHeight;

	$lineHeight *= 8;
	
	$rgbe = explode(",", $rgb);
	if (count($rgbe) > 2)
		return imagettfpostext_small_background($text, $font, $fsize, $pos, $rgb, $trans, $Leading, $W, $H, $tm, $rm, $bm, $lm);
		
	$im = imagettfpostext($text, $font, $fsize * 8, $pos, $rgb, $trans, $Leading, $W*8, $H*8, $tm*8, $rm*8, $bm*8, $lm*8);
	
	$im2 = @imagecreatetruecolor(imagesx($im)/8, imagesy($im)/8) or die("sorry ...");
	if ($trans) {
		imagealphablending($im2, false);
		imagesavealpha($im2, true);
		imagealphablending($im, true);
	}
	imagecopyresampled($im2, $im, 0, 0, 0, 0, imagesx($im2), imagesy($im2), imagesx($im), imagesy($im));
	if ($trans) {  
		$bg_color = imagecolorat($im2, 1, 1); 
		imagecolortransparent($im2, $bg_color); 
	}
	return $im2;
}

function imagettfpostext_small_background($text, $font, $fsize, $pos, $rgb, $trans, $Leading=0, $W=0, $H=0, $tm=0, $rm=0, $bm, $lm)
{
	$rgbe = explode(",", $rgb);
	$rgbt = implode(',', array($rgbe[0], $rgbe[1]));
	
	$imt = imagettfpostext($text, $font, $fsize * 8, $pos, $rgbt, $trans, $Leading, $W*8, $H*8, $tm*8, $rm*8, $bm*8, $lm*8);
	
	if (count($rgbe) > 3)
		$rgbb = array($rgbe[2], $rgbe[3]);
	else
		$rgbb = array($rgbe[0], $rgbe[2]);
	$rgbb = implode(',', $rgbb);
	$imb = imagettfpostext($text, $font, $fsize * 8, $pos, $rgbb, $trans, $Leading, $W*8, $H*8, $tm*8, $rm*8, $bm*8, $lm*8);
	
	$h = floor(imagesy($imt)/8);
	$h *= 2;
	$im2 = @imagecreatetruecolor(imagesx($imt)/8, $h) or die("sorry ...");
	
	if ($trans) {
		imagealphablending($im2, false);
		imagesavealpha($im2, true);
		imagealphablending($imt, true);
		imagealphablending($imb, true);
	}
	imagecopyresampled($im2, $imt, 0, 0, 0, 0, imagesx($im2), imagesy($im2)/2, imagesx($imt), imagesy($imt));
	imagecopyresampled($im2, $imb, 0, imagesy($im2)/2, 0, 0, imagesx($im2), imagesy($im2)/2, imagesx($imb), imagesy($imb));
	if ($trans) {  
		$bg_color = imagecolorat($im2, 1, 1); 
		imagecolortransparent($im2, $bg_color); 
	}

	return $im2;
}

function rgb2hex($rgb) {
	$color = array("black"=>"#000", "white"=>"#fff", "red"=>"#f00", "green"=>"#008000", "blue"=>"#00f", 
	"aqua"=>"0ff", "fuchsia"=>"f0f", "gray"=>"808080", "lime"=>"0f0", "maroon"=>"#800000", "navy"=>"#000080", "olive"=>"#808000", 
	"purple"=>"#800080", "silver"=>"#C0C0C0", "teal"=>"#008080", "yellow"=>"ff0",
	);
	$trans = array("a"=>"10", "b"=>"11", "c"=>"12", "d"=>"13", "e"=>"14", "f"=>"15");
	$hex   = array();
	$rgb   = str_replace("#", "", str_replace(array_keys($color), array_values($color), strtolower($rgb)));
	for ($x = 0; $x < strlen($rgb); $x ++) $hex[] = strtr($rgb[$x], $trans);
	if (count($hex)==3) {
		$hex[5] = $hex[2]; $hex[4] = $hex[2];
		$hex[3] = $hex[1]; $hex[2] = $hex[1];
		$hex[1] = $hex[0];
	};
	$hex = ($hex[0]*16+$hex[1]) . "x" . ($hex[2]*16+$hex[3]) . "x" . ($hex[4]*16+$hex[5]);
	return $hex;
}

function readfile_chunked($filename,$retbytes=true) {
	$chunksize = 1*(1024*1024); // how many bytes per chunk
	$buffer = '';
	$cnt = 0;
	$handle = fopen($filename, 'rb');
	if ($handle === false) {
		return false;
	}
	while (!feof($handle)) {
		$buffer = fread($handle, $chunksize);
		echo $buffer;
		ob_flush();
		flush();
		if ($retbytes) {
			$cnt += strlen($buffer);
		}
	}
	$status = fclose($handle);
	if ($retbytes && $status) {
		return $cnt; // return num. bytes delivered like readfile() does.
	}
	return $status;
}

function isCapitalized($s)
{
	$c1 = substr($s, 0, 1);
	$c2 = substr($s, 1);
	return ((strtoupper($c1) == $c1) && (strtolower($c2) == $c2));
}

class Browser
{
	private $props    = array("Version" => "0.0.0",
                                "Name" => "unknown",
                                "Agent" => "unknown") ;

	public function __Construct()
	{
		$browsers = array("firefox", "msie", "opera", "chrome", "safari",
                            "mozilla", "seamonkey",    "konqueror", "netscape",
                            "gecko", "navigator", "mosaic", "lynx", "amaya",
                            "omniweb", "avant", "camino", "flock", "aol");

		$this->Agent = strtolower($_SERVER['HTTP_USER_AGENT']);
		foreach($browsers as $browser)
		{
			if (preg_match("#($browser)[/ ]?([0-9.]*)#", $this->Agent, $match))
			{
				$this->Name = $match[1] ;
				$this->Version = $match[2] ;
				break ;
			}
		}
	}

	public function __Get($name)
	{
		if (!array_key_exists($name, $this->props))
		{
			die("No such property or function");
		}
		return $this->props[$name] ;
	}

	public function __Set($name, $val)
	{
		if (!array_key_exists($name, $this->props))
		{
			SimpleError("No such property or function.", "Failed to set $name", $this->props) ;
			die ;
		}
		$this->props[$name] = $val ;
	}

}

?>


