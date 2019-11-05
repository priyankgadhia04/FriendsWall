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

use FriendsWall\Posts\InvalidPostException;
use FriendsWall\Posts\Post;
use FriendsWall\Users\InvalidUserAttributeException;
use FriendsWall\Users\User;
use FriendsWall\Groups\InvalidGroupAttributeException;
use FriendsWall\Groups\Group;

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

if (empty($_POST['id'])) {
    $success = false;
    $error = 'Data incomplete.';
    goto output;
}

try {
    $user = new User();
    $user->setId($_SESSION['id'], true);
    session_write_close();
} catch (InvalidUserAttributeException $exc) {
    $success = false;
    $error = $exc->getMessage();
} catch (Exception $exc) {
    $success = false;
    $error = FriendsWall\Configs\Strings::UNKNOWN_ERROR;
}

try {
    $post = new Post();
    $post->setId($_POST['id']);
    if (($user->getId() !== $post->getUid()) && $post->getGid()) {
        $success = false;
        $error = 'Invalid post id';
        goto output;
    }
    if ($post->getGid() && !(new Group())->setId($post->getGid())->hasAdmin($user->getId())) {
        $success = false;
        $error = 'cannot delete post';
        goto output;
    }
    $post->delete();
    if (!empty($post->getMedia())) {
        unlink('../../media/' . $user->getMedia() . '/' . $post->getMedia());
    }
} catch (InvalidPostException $exc) {
    $success = false;
    $error = $exc->getMessage();
} catch (InvalidGroupAttributeException $exc) {
    $success = false;
    $error = $exc->getMessage();
} catch (Exception $exc) {
    $success = false;
    $error = FriendsWall\Configs\Strings::UNKNOWN_ERROR;
}

output:
echo json_encode($output);

