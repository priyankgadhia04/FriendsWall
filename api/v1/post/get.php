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

use FriendsWall\Groups\Group;
use FriendsWall\Groups\InvalidGroupAttributeException;
use FriendsWall\Users\User;
use FriendsWall\Users\InvalidUserAttributeException;
use FriendsWall\Posts\Post;
use FriendsWall\Posts\InvalidPostException;

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

if (empty($_POST['pid'])) {
    $success = false;
    $error = 'Data incomplete.';
    goto output;
}

try {
    $post = (new Post())->setId(intval($_POST['pid']));
    $data['post'] = [
        'id' => $post->getId(),
        'uid' => $post->getUid(),
        'gid' => $post->getGid(),
        'text' => $post->getText(),
        'time' => $post->getTime(),
        'media' => $post->getMedia(),
        'timeago' => $post->getTimeAgo(),
        'isLiked' => $post->isLikedBy($_SESSION['id']),
        'totalLikes' => $post->getTotalLikes(),
        'totalComments' => $post->getTotalComments(),
    ];
    $user = new User();
    $user->setId($post->getUid(), true);
    $data['user'] = [
        'first_name' => $user->getFirstName(),
        'last_name' => $user->getLastName(),
        'username' => $user->getUsername(),
        'media' => $user->getMedia(),
        'dp' => $user->getDp(),
    ];
    $group = new Group();
    if ($post->getGid()) {
        $group->setId($post->getGid(), true);
        $data['group'] = [
            'id' => $group->getId(),
            'name' => $group->getName(),
            'media' => $group->getMedia(),
            'dp' => $group->getDP(),
        ];
    }
    if ($post->getUid() !== $_SESSION['id']) {
        if ($post->getGid()) {
            if (!$group->hasMember($_SESSION['id'])) {
                $success = false;
                $error = 'Access denied.';
                goto output;
            }
        } else {
            if (!$user->isFriendWith($_SESSION['id'])) {
                $success = false;
                $error = 'Access denied.';
                goto output;
            }
        }
    }
} catch (InvalidPostException $exc) {
    $success = false;
    $error = $exc->getMessage();
} catch (InvalidUserAttributeException $exc) {
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