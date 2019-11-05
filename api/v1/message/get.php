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

use FriendsWall\Messages\Messages;
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

if (empty($_POST['username']) && empty($_POST['id'])) {
    $success = false;
    $error = 'Data incomplete';
    goto output;
}

try {
    $user = new User();
    if (!empty($_POST['username'])) {
        $user->setUsername($_POST['username'], true);
    } elseif (!empty($_POST['id'])) {
        $user->setId($_POST['id'], true);
    }
    if (!$user->isFriendWith($_SESSION['id'])) {
        $success = false;
        $error = 'You can chat only with your friends';
        goto output;
    }
    $from = null;
    if (!empty($_POST['from'])) {
        $from = intval($_POST['from']);
    } else {
        $data['uid'] = $user->getId();
        $data['user'] = array(
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'dp' => $user->getDP(),
            'media' => $user->getMedia(),
        );
    }
    $data['messages'] = Messages::getMessages($_SESSION['id'], $user->getId(), $from);
} catch (InvalidUserAttributeException $exc) {
    $success = false;
    $error = $exc->getMessage();
    goto output;
} catch (Exception $exc) {
    $success = false;
    $error = FriendsWall\Configs\Strings::UNKNOWN_ERROR;
}

output:
echo json_encode($output);
