<?php
/* 
 * Copyright (C) 2019 Aditya
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
namespace FriendsWall\Messages;

use FriendsWall\Configs\DB;
use FriendsWall\Users\User;
use PDO;

/**
 * This class is used to get messages of a users
 */
class Messages
{
    
    public static function getChats(int $uid, int $page = 1): array
    {
        $result = [
            'chats' => [],
            'users' => [],
        ];
        $dbh = new PDO(
            DB::PDO_CONNECTION_STRING,
            DB::USERNAME, DB::PASSWORD,
            array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            )
        );
        if ($page < 1) {
            $page = 1;
        }
        $page--;
        $limit = $page * 20;
        $stmt = $dbh->prepare("SELECT id, sender, receiver, text, isread, time FROM messages WHERE id IN (SELECT MAX(id) FROM messages WHERE (sender = :uid AND isdeleted_sender = 0) OR (receiver = :uid AND isdeleted_receiver = 0) GROUP BY receiver,sender) ORDER BY id DESC limit $limit,20;");
        $stmt->bindParam(':uid', $uid);
        $stmt->execute();
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            $result['chats'][] = $row;
            if (!isset($result['users'][intval($row['sender'])])) {
                $user = new User();
                $user->setId(intval($row['sender']), true);
                $result['users'][intval($row['sender'])] = [
                    'id' => $user->getId(),
                    'first_name' => $user->getFirstName(),
                    'last_name' => $user->getLastName(),
                    'username' => $user->getUsername(),
                    'media' => $user->getMedia(),
                    'dp' => $user->getDP(),
                ];
            }
            if (!isset($result['users'][intval($row['receiver'])])) {
                $user = new User();
                $user->setId(intval($row['receiver']), true);
                $result['users'][intval($row['receiver'])] = [
                    'id' => $user->getId(),
                    'first_name' => $user->getFirstName(),
                    'last_name' => $user->getLastName(),
                    'username' => $user->getUsername(),
                    'media' => $user->getMedia(),
                    'dp' => $user->getDP(),
                ];
            }
        }
        return $result;
    }
    
    public static function getMessages(int $uid, int $withUid, ?int $from): array
    {
        $result = [];
        $dbh = new PDO(
            DB::PDO_CONNECTION_STRING,
            DB::USERNAME, DB::PASSWORD,
            array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            )
        );
        if ($from === null) {
            $stmt = $dbh->prepare('SELECT id, sender, receiver, text, isread, time FROM messages WHERE (sender = :uid AND receiver = :withUid AND isdeleted_sender = 0) OR (sender = :withUid AND receiver = :uid AND isdeleted_receiver = 0) ORDER BY id DESC LIMIT 20;');
        } else {
            $stmt = $dbh->prepare('SELECT id, sender, receiver, text, isread, time FROM messages WHERE (sender = :uid AND receiver = :withUid AND isdeleted_sender = 0 AND id < :from) OR (sender = :withUid AND receiver = :uid AND isdeleted_receiver = 0 AND id < :from) ORDER BY id DESC limit 20;');
            $stmt->bindParam(':from', $from);
        }
        $stmt->bindParam(':uid', $uid);
        $stmt->bindParam(':withUid', $withUid);
        $stmt->execute();
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            $result[] = $row;
        }
        return $result;
    }
}