<?php
/* 
 * Copyright (C) 2019 Aditya Nathwani <adityanathwani@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require '../../vendor/autoload.php';

use FriendsWall\Users\User;
use FriendsWall\Users\InvalidUserAttributeException;

header("Content-Type: application/json");
$output = ['success' => true, 'error' => '', 'data' => []];
$success = &$output['success'];
$error = &$output['error'];
$data = &$output['data'];

if (isset($_POST['sid'])) {
    session_id($_POST['sid']);
}
session_start();
if (!isset($_SESSION['id'])) {
    $success = false;
    $output['logout'] = true;
    $error = 'No user logged in. Please login to continue';
    goto output;
}

try {
    $user = new User();
    $user->setId($_SESSION['id'], true);
} catch (InvalidUserAttributeException $exc) {
    $success = false;
    $error = $exc->getMessage();
    goto output;
} catch (Exception $exc) {
    $success = false;
    $error = FriendsWall\Configs\Strings::UNKNOWN_ERROR;
    goto output;
}
if ($user->getMedia() === '') {
    $success = false;
    $error = FriendsWall\Configs\Strings::UNKNOWN_ERROR;
    goto output;
}
$resimg = '../../media/' . $user->getMedia() . '/';
$restmp = '../../res/tmp/';
if (!is_dir($restmp)) {
    mkdir($restmp, 0, true);
}
if (!is_dir($resimg)) {
    mkdir($resimg, 0, true);
}
if(isset($_FILES['file']))
{
	if(isset($_FILES['file']['error']))
	{
		switch ($_FILES['file']['error']) {
			case UPLOAD_ERR_OK:
				break;
			case UPLOAD_ERR_NO_FILE:
                $success = false;
                $error = 'No file received';
                goto output;
			case UPLOAD_ERR_INI_SIZE:
                $success = false;
                $error = 'File size limit is ' . ini_get('upload_max_filesize');
                goto output;
			case UPLOAD_ERR_FORM_SIZE:
                $success = false;
                $error = 'File size limit is ' . ini_get('upload_max_filesize');
                goto output;
			default:
                $success = false;
                $error = 'Unknown error orrured';
                goto output;
		}
	}
	$img = $restmp . md5($_FILES['file']['tmp_name']);
	if (!move_uploaded_file($_FILES['file']['tmp_name'] , $img) || !is_file($img)) {
        $success = false;
        $error = 'Internal server error. Server is unable to process image';
        goto output;
    }
	$dst = $img . '.jpg';

	if (($img_info = getimagesize($img)) === false) {
        $success = false;
        $error = 'File is not an image';
        goto output;
    }

    $width = $img_info[0];
	$height = $img_info[1];

	switch ($img_info[2]) {
        case IMAGETYPE_GIF: 
            $src = imagecreatefromgif($img);
            break;
        case IMAGETYPE_JPEG:
            $src = imagecreatefromjpeg($img);
            break;
        case IMAGETYPE_PNG:
            $src = imagecreatefrompng($img);
            break;
        default:
            $success = false;
            $error = 'File is not an image';
            goto output;
	}

	$tmp = imagecreatetruecolor($width, $height);
	imagecopyresampled($tmp, $src, 0, 0, 0, 0, $width, $height, $width, $height);
	imagejpeg($tmp, $dst);
	if(is_file($dst)) {
		@unlink($img);
		$_SESSION['dp'] = $dst;
	}
	else{
        $success = false;
        $error = 'Unknown error orrured';
	}
} elseif (isset ($_POST["imgdata"], $_POST["imgdata"]["x"], $_POST["imgdata"]["y"], $_POST["imgdata"]["width"], $_POST["imgdata"]["height"])) {    
	foreach(glob($restmp."*") as $file)
	{
		if(time() - filectime($file) > 3600)
		{
			@unlink($file);
		}
	}
    if (!isset($_SESSION['dp']) || !is_file($_SESSION['dp'])) {
        $success = false;
        $error = 'uploaded file not found';
        goto output;
    }
    $file = $_SESSION['dp'];
    $tmp = imagecreatetruecolor(512, 512);
	$src = imagecreatefromjpeg($file);
    imagecopyresampled($tmp, $src, 0, 0, $_POST["imgdata"]["x"], $_POST["imgdata"]["y"], 512, 512, $_POST["imgdata"]["width"], $_POST["imgdata"]["width"]);
    @unlink($file);
    $dp = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"),0,8);
    imagejpeg($tmp, $resimg . $dp . '.jpg');
    if (is_file($resimg . $dp . '.jpg')) {
        @unlink($resimg . $user->getDP() . '.jpg');
        try {
            $user->setDP($dp);
            $user->update();
            $data['dp'] = $dp;
        } catch (InvalidUserAttributeException $exc) {
            $success = false;
            $error = $exc->getMessage();
            goto output;
        } catch (Exception $exc) {
            $success = false;
            $error = FriendsWall\Configs\Strings::UNKNOWN_ERROR;
            goto output;
        }
    } else {
        $success = false;
        $error = 'Unable to change dp due to internal error';
    }
    
} else {
    $success = false;
    $error = 'No data received';
}
output:
echo json_encode($output);