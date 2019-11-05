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
namespace FriendsWall\Informers;

use FriendsWall\Users\User;
use Sse\Event;
use Sse\SSE;
use FriendsWall\Configs\DB;
use PDO;

/**
 * This class is used for SSE to get read receipt for range of messages.
 * 
 * To use this class, create new object and pass it in the second argument
 * of {@see \Sse\SSE} method addEventListener. after that, set properties
 * using setUser(), setWithUser(), and setRange() methods and use setError() if any error occurs.
 * @author Aditya Nathwani <adityanathwani@gmail.com>
 */
class ReadInformer implements Event
{

    private $user;
    private $withUser;
    private $range_from;
    private $range_to;
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
        if ($this->range_from === 0|| $this->range_to === 0) {
            return false;
        }
        try {
            $stmt = $this->db->prepare('SELECT id, isread FROM messages WHERE id <= :from AND id >= :to AND isdeleted_sender = 0 AND sender = :sender AND receiver = :receiver ORDER BY id DESC');
            $stmt->bindValue(':from', $this->range_from);
            $stmt->bindValue(':to', $this->range_to);
            $stmt->bindValue(':sender', $this->user->getId());
            $stmt->bindValue(':receiver', $this->withUser->getId());
            $stmt->execute();
            $tArray = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $tArray[] = $row;
            }
            if ($tArray !== $this->message_array) {
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
     * Sets id of range of messages to get read receipt.
     * 
     * @param int $from upper limit message id.
     * @param int $to lower limit message id.
     * @return void
     */
    public function setRange(int $from, int $to): void
    {
        $this->range_from = $from;
        $this->range_to = $to;
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
