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
namespace FriendsWall\Groups;

use FriendsWall\Configs\DB;
use PDO;

/**
 * This class is used to get list of groups of a user
 */
class Groups
{
    
    public static function getGroups(int $uid, int $page = 1): array
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
        $limit = $page * 20;
        $q = $dbh->prepare("SELECT id, name, description, media, dp FROM groups WHERE id IN (SELECT gid AS id FROM group_members WHERE uid = :uid AND isdeleted = 0) LIMIT $limit,20;");
        $q->bindValue(':uid', $uid);
        $q->execute();
        while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }
    
    public static function getMembers(int $gid): array
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
        $q = $dbh->prepare("SELECT id, username, first_name, last_name, media, dp FROM users WHERE id IN (SELECT uid AS id FROM group_members WHERE gid = :gid AND isadmin=1);");
        $q->bindValue(':gid', $gid);
        $q->execute();
        while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
            $row['isadmin'] = true;
            $result[] = $row;
        }
        $q2 = $dbh->prepare("SELECT id, username, first_name, last_name, media, dp FROM users WHERE id IN (SELECT uid AS id FROM group_members WHERE gid = :gid AND isadmin=0)");
        $q2->bindValue(':gid', $gid);
        $q2->execute();
        while (($row = $q2->fetch(PDO::FETCH_ASSOC))) {
            $row['isadmin'] = false;
            $result[] = $row;
        }
        return $result;
    }
}