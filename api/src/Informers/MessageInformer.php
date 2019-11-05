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
namespace FriendsWall\Informers;

use FriendsWall\Users\User;
use Sse\Event;
use Sse\SSE;
use FriendsWall\Configs\DB;
use PDO;

/**
 * This class is used for SSE to get message when new message arrives.
 * 
 * To use this class, create new object and pass it in the second argument
 * of {@see \Sse\SSE} method addEventListener. after that, set properties
 * using setUser(), setWithUser(), and setLastMessageId() methods and use setError() if any error occurs.
 * @author Aditya Nathwani <adityanathwani@gmail.com>
 */
class MessageInformer implements Event
{

    private $user;
    private $withUser;
    private $lastMessageId;
    private $message_array;
    private $error;
    private $db;
    private $sse;

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

    public function check(): bool
    {
        if ($this->user === null) {
            $this->error = "internal error occurd";
            return true;
        }
        try {
            $from = '';
            if ($this->lastMessageId !== null) {
                $from = ' AND id > ' . $this->lastMessageId;
            }
            $stmt = $this->db->prepare("SELECT id, sender, receiver, text, isread, time FROM messages WHERE (sender = :uid AND receiver = :withUid AND isdeleted_sender = 0$from) OR (sender = :withUid AND receiver = :uid AND isdeleted_receiver = 0$from) ORDER BY id ASC");
            $stmt->bindValue(':uid', $this->user->getId());
            $stmt->bindValue(':withUid', $this->withUser->getId());
            $stmt->execute();
            $tArray = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $tArray[] = $row;
                $this->lastMessageId = intval($row['id']);
            }
            if (count($tArray)) {
                $this->message_array = $tArray;
                return true;
            } else {
                return false;
            }
        } catch (\Exception $ex) {
            $this->error = "internal error occurd";
            return true;
        }
    }

    public function update(): string
    {
        if ($this->error !== null) {
            return '{"error": "' . $this->error . '"}';
        } elseif ($this->message_array !== null) {
            return json_encode($this->message_array);
        } else {
            return '{}';
        }
    }
    
    /**
     * Sets object of User class of current user.
     * 
     * @param User $user Initialized user object
     * @return void
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }
    
    /**
     * Sets object of User class of other user.
     * 
     * @param User $user Initialized user object
     * @return void
     */
    public function setWithUser(User $user): void
    {
        $this->withUser = $user;
    }
    
    /**
     * Sets id of the last message received.
     * 
     * @param int $id id of the last message received.
     * @return void
     */
    public function setLastMessageId(int $id): void
    {
        $this->lastMessageId = $id;
    }

    /**
     * Use this method if any error occurs.
     * 
     * @param string $error error message
     * @return void
     */
    public function setError(string $error): void
    {
        $this->error = $error;
    }
    
    /**
     * sets SSE object for future use.
     * 
     * @param SSE $sse SSE object in which the current class is bind
     * @return void
     */
    public function setSSE(SSE $sse) : void
    {
        $this->sse = $sse;
    }
}
