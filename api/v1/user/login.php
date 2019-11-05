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

$output = ['success' => true, 'error' => '', 'data' => ''];
$success = &$output['success'];
$error = &$output['error'];
$data = &$output['data'];


if (
    empty($_POST['email']) ||
    empty($_POST['password'])
) {
    $success = false;
    $error = 'Data incomplete.';
    goto output;
}

try {
    $user = new User();
    $user->setEmail($_POST['email'], true);
    if (!$user->isPassword($_POST['password'])) {
        $success = false;
        $error = 'Email and password does not match.';
        goto output;
    }
    if (!$user->isActive()) {
        $success = false;
        $error = 'Your account is suspended. Please contact administrator.';
        goto output;
    }
    session_start();
    $_SESSION['id'] = $user->getId();
    if (isset($_POST['get_sid'])) {
        $data = session_id();
    }
    session_write_close();
} catch (InvalidUserAttributeException $exc) {
    $success = false;
    $error = $exc->getMessage();
} catch (Exception $exc) {
    $success = false;
    $error = "Unknown error occured. Please contact administrator.";
}

output:
echo json_encode($output);