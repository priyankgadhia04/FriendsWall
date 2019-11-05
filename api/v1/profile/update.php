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
    empty($_POST['email']) ||
    empty($_POST['username']) ||
    empty($_POST['first_name']) ||
    empty($_POST['last_name']) ||
    empty($_POST['gender']) ||
    empty($_POST['dob'])
) {
    $success = false;
    $error = 'Data incomplete.';
    goto output;
}

try {
    $user = new User();
    $user->setId($_SESSION['id'], true);
    session_write_close();
    $user
        ->setEmail($_POST['email'])
        ->setUsername($_POST['username'])
        ->setFirstName($_POST['first_name'])
        ->setLastName($_POST['last_name'])
        ->setGender($_POST['gender'])
        ->setDOB($_POST['dob'])
        ->setAbout($_POST['about']);
    $data['email'] = $user->getEmail();
    $data['username'] = $user->getUsername();
    $data['first_name'] = $user->getFirstName();
    $data['last_name'] = $user->getLastName();
    $data['gender'] = $user->getGender();
    $data['dob'] = $user->getDOB();
    $data['about'] = $user->getAbout();
    $user->update();
} catch (InvalidUserAttributeException $exc) {
    $success = false;
    $error = $exc->getMessage();
} catch (Exception $exc) {
    $success = false;
    $error = FriendsWall\Configs\Strings::UNKNOWN_ERROR;
}

output:
echo json_encode($output);

