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
namespace FriendsWall\Utils;

use FriendsWall\Configs\DB;
use PDO;

/**
 * This class is used to search users
 *
 * @author Aditya Nathwani <adityanathwani@gmail.com>
 */
class Search
{

    /**
     * 
     * @param string $str text to search. It can be first_name, last_name or
     * username
     * @param int $page page number of result
     * @return array
     */
    public static function search(string $str, int $page = 1, $isAdmin = false): array
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
        $q = $dbh->prepare("SELECT id, first_name, last_name, username, media, dp, isactive  FROM users WHERE CONCAT(first_name, ' ', last_name) LIKE :keyword OR CONCAT(last_name, ' ', first_name) LIKE :keyword OR first_name LIKE :keyword OR last_name LIKE :keyword OR username LIKE :keyword LIMIT $limit,10;");
        $q->bindValue(':keyword', '%' . $str . '%');
        $q->execute();
        while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
            if (!$isAdmin) {
                unset($row['isactive']);
            }
            $result[] = $row;
        }
        return $result;
    }
}
