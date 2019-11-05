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
namespace FriendsWall\Notifications;

use FriendsWall\Configs\DB;
use PDO;

/**
 * This class is used to add notifications for users
 */
class Notification
{

    private $db;
    private $id;
    private $uid;
    private $table_name;
    private $entity_id;
    private $isread;
    
    public const TABLE_COMMENTS = 1;
    public const TABLE_FRIENDS = 2;
    public const TABLE_GROUP_MEMBERS = 3;
    public const TABLE_LIKES = 4;

    public function __construct()
    {
        $this->db = new PDO(
            DB::PDO_CONNECTION_STRING,
            DB::USERNAME, DB::PASSWORD,
            array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            )
        );
    }
    
    public function setParams(int $uid, int $table_name, int $entity_id): Notification
    {
        $this->uid = $uid;
        $this->table_name = $table_name;
        $this->entity_id = $entity_id;
        $this->isread = 0;
        $stmt = $this->db->prepare("SELECT id, uid, table_name+0 AS 'table_name', entity_id, isread FROM notifications WHERE uid = :uid AND table_name = :tablename AND entity_id = :entityid");
        $stmt->bindParam(':uid', $this->uid);
        $stmt->bindParam(':tablename', $this->table_name);
        $stmt->bindParam(':entityid', $this->entity_id);
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            $this->id = $row['id'];
            $this->uid = $row['uid'];
            $this->table_name = $row['table_name'];
            $this->entity_id = $row['entity_id'];
            $this->isread = $row['isread'];
        }
        return $this;
    }

    public function add(): Notification
    {
        if ($this->id !== null) {
            $stmt = $this->db->prepare('UPDATE notifications set isread = 0, time = NOW() WHERE id = :id');
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
        } else {
            $stmt = $this->db->prepare('INSERT INTO notifications (uid, table_name, entity_id) VALUES (:uid, :tablename, :entityid)');
            $stmt->bindParam(':uid', $this->uid);
            $stmt->bindParam(':tablename', $this->table_name);
            $stmt->bindParam(':entityid', $this->entity_id);
            $stmt->execute();
            $this->id = $this->db->lastInsertId();
        }
        return $this;
    }

    public static function read(int $id): void
    {
        $db = new PDO(
            DB::PDO_CONNECTION_STRING,
            DB::USERNAME, DB::PASSWORD,
            array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            )
        );
        $stmt = $db->prepare('UPDATE notifications set isread = 1 WHERE id = :id');
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }
    
    public static function isReceiver(int $uid, int $nid): bool
    {
        $db = new PDO(
            DB::PDO_CONNECTION_STRING,
            DB::USERNAME, DB::PASSWORD,
            array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            )
        );
        $stmt = $db->prepare('SELECT id FROM notifications WHERE id = :nid AND uid = :uid');
        $stmt->bindParam(':nid', $nid);
        $stmt->bindParam(':uid', $uid);
        $stmt->execute();
        if ($stmt->rowCount() !== 1) {
            return false;
        }
        return true;
    }
}
