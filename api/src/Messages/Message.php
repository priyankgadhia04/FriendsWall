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
namespace FriendsWall\Messages;

use FriendsWall\Configs\DB;
use PDO;

class Message
{
    
    private $db;
    private $id;
    private $sender;
    private $receiver;
    private $text;
    private $isdeleted_sender;
    private $isdeleted_receiver;
    private $time;
    
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
    
//    public function delete(): Message
//    {
//        if ($this->id === null) {
//            throw new InvalidMessageException('Message not found');
//        }
//        $stmt = $this->db->prepare('DELETE FROM messages WHERE id = :id');
//        $stmt->bindParam(':id', $this->id);
//        $stmt->execute();
//        $this->id = null;
//        return $this;
//    }
    
    public function setId(int $id): Message
    {
        $stmt = $this->db->prepare('SELECT * FROM messages where id = :id');
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        if ($stmt->rowCount() !== 1) {
            throw new InvalidMessageException('Message with given id not found');
        }
        while ($row = $stmt->fetch()) {
            $this->id = intval($row['id']);
            $this->sender = intval($row['sender']);
            $this->receiver = intval($row['receiver']);
            $this->text = $row['text'];
            $this->isdeleted_sender = intval($row['isdeleted_sender']);
            $this->isdeleted_receiver = intval($row['isdeleted_receiver']);
            $this->time = $row['time'];
        }
        return $this;
    }
    
    public function setSender(int $id): Message
    {
        $this->sender = $id;
        return $this;
    }
    
    public function setReceiver(int $id): Message
    {
        $this->receiver = $id;
        return $this;
    }
    
    public function setText(string $text): Message
    {
        if (strlen($text) > 1024) {
            throw new InvalidMessageException('Message must be of less 1024 characers');
        }
        $this->text = $text;
        return $this;
    }
    
    public function getSender(): int
    {
        return $this->sender;
    }
    public function getReceiver(): int
    {
        return $this->receiver;
    }
    public function getText(): string
    {
        return $this->text;
    }
    public function isDeletedBySender(): bool
    {
        return (bool) $this->isdeleted_sender;
    }
    public function deleteBySender(): Message
    {
        $stmt = $this->db->prepare('UPDATE messages SET isdeleted_sender = 1 WHERE id = :id');
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        $this->setId($this->id);
        return $this;
    }
    public function deleteByReceiver(): Message
    {
        $stmt = $this->db->prepare('UPDATE messages SET isdeleted_receiver = 1 WHERE id = :id');
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        $this->setId($this->id);
        return $this;
    }
    public function isDeletedByReceiver(): bool
    {
        return (bool) $this->isdeleted_receiver;
    }
    
    public function send(): Message
    {
        if ($this->id !== null) {
            throw new InvalidMessageException("Message already exists");
        }
        $stmt = $this->db->prepare('INSERT INTO messages (sender, receiver, text) VALUES (:sender, :receiver, :text)');
        $stmt->bindParam(':sender', $this->sender);
        $stmt->bindParam(':receiver', $this->receiver);
        $stmt->bindParam(':text', $this->text);
        $stmt->execute();
        $this->id = $this->db->lastInsertId();
        $this->setId($this->id);
        return $this;
    }
    
    public function read(): Message
    {
        $stmt = $this->db->prepare('UPDATE messages SET isread = 1 WHERE id = :id');
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        $this->setId($this->id);
        return $this;
    }
}
