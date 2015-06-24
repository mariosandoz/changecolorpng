<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

// cache to reduce server load
header("Cache-Control: public, max-age=31536000"); //
header("Last-Modified: Mon, 01 Jan 1980 00:00:00 GMT");
header("Content-type: image/png");

#################
#INIT
#################
ob_start();
require_once dirname(realpath(__DIR__)) . '/configs/config.global.php';
require_once dirname(realpath(__DIR__)) . '/configs/config.domain.php';
require_once ALTAMIDES_CONFIG_PATH . '/config.reference.php';
require_once ALTAMIDES_CONFIG_PATH . '/config.connections.php';

// prevent session locking
if (session_id() !== '') {
    session_write_close();
}

$cacheDir     = ALTAMIDES_CACHE_DIR . '/direction-finder-handheld-images';
$imgSrc = ALTAMIDES_BASE_PATH ."images/spottrax/mobile_df.png";

#################
#INPUT
#################

$validation = array(
    'red'     => FILTER_SANITIZE_NUMBER_INT,
    'green'   => FILTER_SANITIZE_NUMBER_INT,
    'blue'    => FILTER_SANITIZE_NUMBER_INT,
);

$input = filter_input_array(INPUT_GET, $validation);

$colorR = (int) $input['red'];
$colorG = (int) $input['green'];
$colorB = (int) $input['blue'];

#################
#PREPARE
#################
// Test cache

function directionfinder_output_image($image, $cacheFile)
{
    if (!directionfinder_cache_image($image, $cacheFile)) {
        ob_end_clean();
        imagepng($image);
    }
    directionfinder_from_cache($cacheFile);
}

function directionfinder_from_cache($cacheFile)
{
    $fileSize = filesize($cacheFile);
    header('Content-length: ' . $fileSize);
    ob_end_clean();
    readfile($cacheFile);
}

function directionfinder_cache_image($image, $cacheFile)
{
    $cacheDir = dirname($cacheFile);
    $dirMode  = 0777;
    if (!file_exists($cacheDir)) {
        trigger_error("Cache dir does not exist, attempt to create one", E_USER_NOTICE);
        if (!mkdir($cacheDir, $dirMode, true)) {
            trigger_error('Failed creating cache directory', E_USER_WARNING);
            return false;
        }
    }
    return imagepng($image, $cacheFile);
}

function directionfinder_hit_cache($cacheFile)
{
    return file_exists($cacheFile) && is_readable($cacheFile);
}

// Try to get from cache
$cacheFileName = md5("r={$colorR};g={$colorG};b={$colorB};");
$cacheFile     = $cacheDir . '/' . $cacheFileName;
if (directionfinder_hit_cache($cacheFile)) {
    directionfinder_from_cache($cacheFile);
    exit;
}

#################
#DRAW
#################
// Create the image handle, set the background to white
$im = imagecreatefrompng($imgSrc);

if ($im === false){
    trigger_error('Failed creating image from png');
    exit; //blank
}

imagealphablending($im, false);
imagesavealpha($im, true);

// Set the border and fill colors
$borderBlack = imagecolorallocate($im, 0, 0, 0);
if($borderBlack === false || $borderBlack === -1){
    trigger_error('Failed creating border color');
    exit; //blank
}

$fillColor = imagecolorallocate($im, $colorR, $colorG, $colorB);
if($fillColor === false || $fillColor === -1){
    trigger_error('Failed creating filling color');
    exit; //blank
}
// Fill the selection
$imgfillborder = imagefilltoborder($im, 0, 0, $borderBlack, $fillColor);
if($imgfillborder === false){
    trigger_error('Failed creating png');
    exit; //blank
}
#################
#OUTPUT
#################
directionfinder_output_image($im,$cacheFile);
directionfinder_output_image($im, $cacheFile);
imagecolordeallocate($im, $borderBlack);
imagecolordeallocate($im, $fillColor);
imagedestroy($im);