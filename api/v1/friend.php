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
include '../vendor/autoload.php';

use FriendsWall\Friends\FriendRequest;
use FriendsWall\Friends\InvalidFriendRequestException;

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
    empty($_POST['action']) ||
    empty($_POST['id'])
) {
    $success = false;
    $error = 'Data incomplete.';
    goto output;
}
$action = $_POST['action'];
if (!in_array($action, [
        'send',
        'accept',
        'reject',
        'delete',
        'cancel'
    ], true)) {
    $success = false;
    $error = 'action invalid.';
    goto output;
}

try {
    $request = new FriendRequest();
    $request
        ->from($_SESSION['id'])
        ->to($_POST['id'])
        ->$action();
    $data['friend_status'] = $request->getStatus();
} catch (InvalidFriendRequestException $exc) {
    $success = false;
    $error = $exc->getMessage();
} catch (Exception $exc) {
    $success = false;
    $error = FriendsWall\Configs\Strings::UNKNOWN_ERROR;
}

output:
echo json_encode($output);

