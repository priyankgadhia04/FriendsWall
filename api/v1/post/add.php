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

include '../../vendor/autoload.php';

use FriendsWall\Groups\Group;
use FriendsWall\Groups\InvalidGroupAttributeException;
use FriendsWall\Posts\InvalidPostException;
use FriendsWall\Posts\Post;
use FriendsWall\Users\InvalidUserAttributeException;
use FriendsWall\Users\User;

header('Content-Type: application/json');
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
    $output["logout"] = true;
    $error = 'No user logged in. Please login to continue';
    goto output;
}

if (
    (empty($_POST['text']) && empty(trim($_POST['text']))) &&
    empty($_FILES['file']) 
) {
    $success = false;
    $error = 'Please enter text.';
    goto output;
}

try {
    $user = new User();
    $user->setId($_SESSION['id'], true);
    session_write_close();
    $gid = null;
    if (!empty($_POST['gid'])) {
        $group = new Group();
        $group->setId($_POST['gid'], true);
        if ($group->isDeleted()) {
            $success = false;
            $error = 'Group deleted';
            goto output;
        }
        if (!$group->hasMember($user->getId())) {
            $success = false;
            $error = 'You are not a member of this group';
            goto output;
        }
        $gid = intval($_POST['gid']);
    }
} catch (InvalidUserAttributeException $exc) {
    $success = false;
    $error = $exc->getMessage();
} catch (Exception $exc) {
} catch (InvalidGroupAttributeException $exc) {
    $success = false;
    $error = $exc->getMessage();
} catch (Exception $exc) {
    $success = false;
    $error = FriendsWall\Configs\Strings::UNKNOWN_ERROR;
}

$media = null;
if(!empty($_FILES['file'])) {
    $resimg = '../../media/' . $user->getMedia() . '/';
    if (!is_dir($resimg)) {
        mkdir($resimg, 0, true);
    }
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
    $type = mime_content_type($_FILES['file']['tmp_name']);
    if (explode('/', $type)[0] === 'image') {
        if (($img_info = getimagesize($_FILES['file']['tmp_name'])) === false) {
            $success = false;
            $error = 'File is not an image';
            goto output;
        }
        $width = $img_info[0];
        $height = $img_info[1];

        switch ($img_info[2]) {
            case IMAGETYPE_GIF: 
                $src = imagecreatefromgif($_FILES['file']['tmp_name']);
                break;
            case IMAGETYPE_JPEG:
                $src = imagecreatefromjpeg($_FILES['file']['tmp_name']);
                break;
            case IMAGETYPE_PNG:
                $src = imagecreatefrompng($_FILES['file']['tmp_name']);
                break;
            default:
                $success = false;
                $error = 'File is not an image';
                goto output;
        }
        $tmp = imagecreatetruecolor($width, $height);
        imagecopyresampled($tmp, $src, 0, 0, 0, 0, $width, $height, $width, $height);
        $media = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"),0,8) . '.jpg';
        imagejpeg($tmp, $resimg . $media);
        if (!is_file($resimg . $media)) {
            $success = false;
            $error = 'Unable to process image.';
            goto output;
        }
    } elseif (explode('/', $type)[0] === 'video') {
        if ($type !== 'video/mp4') {
            $success = false;
            $error = 'Only mp4 video supported.';
            goto output;
        }
        $media = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"),0,8) . '.mp4';
        move_uploaded_file($_FILES['file']['tmp_name'], $resimg . $media);
        if (!is_file($resimg . $media)) {
            $success = false;
            $error = 'Unable to process video.';
            goto output;
        }
    } else {
        $success = false;
        $error = 'Unsupported file type.';
        goto output;
    }
}

try {
    $post = new Post();
    $post
        ->setUid($user->getId())
        ->setText(trim($_POST['text']))
        ->setMedia($media)
        ->setGid($gid)
        ->add();
} catch (InvalidPostException $exc) {
    $success = false;
    $error = $exc->getMessage();
} catch (Exception $exc) {
    $success = false;
    $error = FriendsWall\Configs\Strings::UNKNOWN_ERROR;
}

output:
echo json_encode($output);

