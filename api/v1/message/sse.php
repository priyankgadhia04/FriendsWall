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

use FriendsWall\Informers\MessageInformer;
use FriendsWall\Users\InvalidUserAttributeException;
use FriendsWall\Users\User;
use Sse\SSE;

$messageInformer = new MessageInformer();
$sse = new SSE();

if (isset($_POST['sid'])) {
    session_id($_POST['sid']);
}
session_start();
if (!isset($_SESSION['id'])) {
    $messageInformer->setError('No user logged in. Please login to continue');
} elseif (!isset($_COOKIE['with_uid'], $_COOKIE['last_message_id'])) {
    $messageInformer->setError('Data incomplete');
} else {
    try {
        $user = new User();
        $user->setId($_SESSION['id'], true);
        session_write_close();
        $withUser = new User();
        $withUser->setId(intval($_COOKIE['with_uid']), true);
        if (!$user->isFriendWith($withUser->getId())) {
            $messageInformer->setError('You can chat with your friends only');
        } else {
            $messageInformer->setUser($user);
            $messageInformer->setWithUser($withUser);
            if (intval($_COOKIE['last_message_id']) !== 0) {
                $messageInformer->setLastMessageId(intval($_COOKIE['last_message_id']));
            }
            $messageInformer->setSSE($sse);
        }
    } catch (InvalidUserAttributeException $exc) {
        $messageInformer->setError($exc->getMessage());
    } catch (Exception $exc) {
        $messageInformer->setError(FriendsWall\Configs\Strings::UNKNOWN_ERROR);
    }
}

$sse->addEventListener('messageInfo', $messageInformer);
$sse->start();