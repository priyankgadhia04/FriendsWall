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
namespace FriendsWall\Friends;

use FriendsWall\Configs\DB;
use PDO;

/**
 * This class is used to get list of friends
 *
 * @author Aditya Nathwani <adityanathwani@gmail.com>
 */
class Friends
{

    public static function get(int $id, int $page = 1, string $searchString = ''): array
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
        if ($page < 1) {
            $page = 1;
        }
        $page--;
        $limit = $page * 10;
        $search = '';
        if ($searchString !== '') {
            $search = ' AND (CONCAT(first_name, \' \', last_name) LIKE :keyword OR CONCAT(last_name, \' \', first_name) LIKE :keyword OR first_name LIKE :keyword OR last_name LIKE :keyword OR username LIKE :keyword)';
        }
        $q = $dbh->prepare("SELECT id, first_name, last_name, username, media, dp FROM users WHERE id IN (SELECT fromid AS id FROM friends WHERE toid = :id AND accepted=1 UNION SELECT toid AS id FROM friends WHERE fromid = :id AND accepted=1)$search LIMIT $limit,10;");
        $q->bindValue(':id', $id);
        if ($searchString !== '') {
            $q->bindValue(':keyword', '%' . $searchString . '%');
        }
        $q->execute();
        while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }
    public static function getRequests(int $id, int $page = 1): array
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
        if ($page < 1) {
            $page = 1;
        }
        $page--;
        $limit = $page * 10;
        $q = $dbh->prepare("SELECT users.id, users.username, users.first_name, users.last_name, users.media, users.dp FROM users INNER JOIN friends ON friends.toid = :toid AND users.id = friends.fromid AND friends.accepted=0 ORDER BY friends.time DESC LIMIT $limit,10");
        $q->bindValue(':toid', $id);
        $q->execute();
        while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }
}
