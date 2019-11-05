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
header("Content-Type: application/json");
require '../../vendor/autoload.php';

use FriendsWall\Users\User;
use FriendsWall\Users\InvalidUserAttributeException;

$output = ['success' => true, 'error' => ''];
$success = &$output['success'];
$error = &$output['error'];


if (
    empty($_POST['email']) ||
    empty($_POST['username']) ||
    empty($_POST['first_name']) ||
    empty($_POST['last_name']) ||
    empty($_POST['gender']) ||
    empty($_POST['dob']) ||
    empty($_POST['password']) ||
    empty($_POST['confirm_password'])
) {
    $success = false;
    $error = 'Data incomplete.';
    goto output;
}

if($_POST['password'] !== $_POST['confirm_password']) {
    $success = false;
    $error = 'Password and Confirm password does not match.';
    goto output;
}

try {
    $user = new User($_POST['email'], $_POST['username'], $_POST['first_name'], $_POST['last_name'], $_POST['gender'], $_POST['dob'], $_POST['password']);
    $user->create();
} catch (InvalidUserAttributeException $exc) {
    $success = false;
    $error = $exc->getMessage();
} catch (Exception $exc) {
    $success = false;
    $error = FriendsWall\Configs\Strings::UNKNOWN_ERROR;
}

output:
echo json_encode($output);