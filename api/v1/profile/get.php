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

use FriendsWall\Friends\FriendRequest;
use FriendsWall\Friends\InvalidFriendRequestException;
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

try {
    $user = new User();
    if (isset($_POST['username'])) {
        $user->setUsername($_POST['username'], true);
        if ($_SESSION['id'] !== $user->getId()) {
            try {
                $data["friend_status"] = (new FriendRequest())
                                            ->from($_SESSION['id'])
                                            ->to($user->getId())
                                            ->getStatus();
            } catch (InvalidFriendRequestException $ex) {
                $success = false;
                $error = $ex->getMessage();
                goto output;
            }
        }
    } else {
        $user->setId($_SESSION['id'], true);
    }
    session_write_close();
    $data["user"] = [
        'id' => $user->getId(),
        'first_name' => $user->getFirstName(),
        'last_name' => $user->getLastName(),
        'email' => $user->getEmail(),
        'username' => $user->getUsername(),
        'gender' => $user->getGender(),
        'dob' => $user->getDOB(),
        'media' => $user->getMedia(),
        'dp' => $user->getDP(),
        'about' => $user->getAbout()
    ];
} catch (InvalidUserAttributeException $exc) {
    $success = false;
    $error = $exc->getMessage();
} catch (Exception $exc) {
    $success = false;
    $error = FriendsWall\Configs\Strings::UNKNOWN_ERROR;
}

output:
echo json_encode($output);
