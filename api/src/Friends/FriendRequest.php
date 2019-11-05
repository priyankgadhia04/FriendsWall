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

use FriendsWall\Notifications\Notification;
use FriendsWall\Configs\DB;
use PDO;

/**
 * This class can be used to send, accept and reject friend requests.
 * This class can also be used to remove friend from friend list.
 *
 * @author Aditya Nathwani <adityanathwani@gmail.com>
 */
class FriendRequest
{

    private $db;
    private $id;
    private $fromid;
    private $toid;
    private $status;

    public const ENTRY_NOT_FOUND = 1;
    public const REQUEST_SENT = 2;
    public const REQUEST_RECEIVED = 3;
    public const FRIENDS = 4;

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
        $this->init = false;
    }

    public function from(int $id): FriendRequest
    {
        if (!empty($this->fromid)) {
            throw new InvalidFriendRequestException("From id already set");
        }
        $this->checkID($id);
        $this->fromid = $id;
        $this->init();
        return $this;
    }

    public function to(int $id): FriendRequest
    {
        if (!empty($this->toid)) {
            throw new InvalidFriendRequestException("To id already set");
        }
        $this->checkID($id);
        $this->toid = $id;
        $this->init();
        return $this;
    }

    public function accept(): void
    {
        $this->checkFields();
        if ($this->status !== FriendRequest::REQUEST_RECEIVED) {
            throw new InvalidFriendRequestException('No request made');
        }
        $stmt = $this->db->prepare("UPDATE friends set accepted=1 WHERE id=:id");
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        $this->status = FriendRequest::FRIENDS;
        (new Notification())
            ->setParams($this->toid, Notification::TABLE_FRIENDS, $this->id)
            ->add();
    }

    public function reject(): void
    {
        $this->checkFields();
        if ($this->status !== FriendRequest::REQUEST_RECEIVED) {
            throw new InvalidFriendRequestException('No request made');
        }
        $stmt = $this->db->prepare("DELETE FROM friends WHERE id=:id");
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        $this->id = null;
        $this->status = FriendRequest::ENTRY_NOT_FOUND;
    }

    public function send(): void
    {
        $this->checkFields();
        if ($this->status !== FriendRequest::ENTRY_NOT_FOUND) {
            throw new InvalidFriendRequestException('Unable to send request');
        }
        $stmt = $this->db->prepare("INSERT INTO friends (fromid, toid) VALUES (:fromid, :toid)");
        $stmt->bindParam(':fromid', $this->fromid);
        $stmt->bindParam(':toid', $this->toid);
        $stmt->execute();
        $this->id = $this->db->lastInsertId();
        $this->status = FriendRequest::REQUEST_SENT;
    }

    public function delete(): void
    {
        $this->checkFields();
        if ($this->status !== FriendRequest::FRIENDS) {
            throw new InvalidFriendRequestException('Users are not friends');
        }
        $stmt = $this->db->prepare("DELETE FROM friends WHERE id=:id");
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        $this->id = null;
        $this->status = FriendRequest::ENTRY_NOT_FOUND;
    }

    public function cancel(): void
    {
        $this->checkFields();
        if ($this->status !== FriendRequest::REQUEST_SENT) {
            throw new InvalidFriendRequestException('Request not sent');
        }
        $stmt = $this->db->prepare("DELETE FROM friends WHERE id=:id");
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        $this->id = null;
        $this->status = FriendRequest::ENTRY_NOT_FOUND;
    }
    
    public function getStatus(): int
    {
        $this->checkFields();
        return $this->status;
    }

    private function checkID(int $id): void
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            throw new InvalidFriendRequestException("User with given id does not exist");
        }
    }
    
    private function init(): void
    {
        if (empty($this->fromid) || empty($this->toid)) {
            return;
        }
        if ($this->fromid === $this->toid) {
            throw new InvalidFriendRequestException("From id and To id same");
        }
        $stmt = $this->db->prepare("SELECT * FROM friends WHERE (toid = :toid AND fromid = :fromid) OR (toid = :fromid AND fromid = :toid)");
        $stmt->bindParam(':toid', $this->toid);
        $stmt->bindParam(':fromid', $this->fromid);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            $this->status = FriendRequest::ENTRY_NOT_FOUND;
        } else {
            $row = $stmt->fetch();
            $this->id = $row['id'];
            if ($row['fromid'] == $this->fromid) {
                if ($row['accepted'] == 0) {
                    $this->status = FriendRequest::REQUEST_SENT;
                } else {
                    $this->status = FriendRequest::FRIENDS;
                }
            } else {
                if ($row['accepted'] == 0) {
                    $this->status = FriendRequest::REQUEST_RECEIVED;
                } else {
                    $this->status = FriendRequest::FRIENDS;
                }
            }
        }
    }


    private function checkFields() {
        if ($this->status === null || ($this->status !== FriendRequest::ENTRY_NOT_FOUND && $this->id === null)) {
            throw new InvalidFriendRequestException("Please set fromid and to id");
        }
    }
}
