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
 * This class is used for SSE to pass user data when required.
 * 
 * To use this class, create new object and pass it in the second argument
 * of {@see \Sse\SSE} method addEventListener. after that, set the user
 * using setUser() method and use setError() if any error occurs.
 * @author Aditya Nathwani <adityanathwani@gmail.com>
 */
class MessagesInformer implements Event
{

    private $user;
    private $error;
    private $messages_array;
    private $users_array;
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
            $stmt = $this->db->prepare("SELECT id, sender, receiver, text, isread, time FROM messages WHERE id IN (SELECT MAX(id) FROM messages WHERE (sender = :uid AND isdeleted_sender = 0) OR (receiver = :uid AND isdeleted_receiver = 0) GROUP BY receiver,sender) ORDER BY id DESC limit 20;");
            $uid = $this->user->getId();
            $stmt->bindParam(':uid', $uid);
            $stmt->execute();
            $tArray = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $tArray[] = $row;
            }
            if ($tArray !== $this->messages_array) {
                $this->messages_array = $tArray;
                $this->users_array = [];
                foreach ($tArray as $row) {
                    if (!isset($this->users_array[intval($row['sender'])])) {
                        $user = new User();
                        $user->setId(intval($row['sender']), true);
                        $this->users_array[intval($row['sender'])] = [
                            'id' => $user->getId(),
                            'first_name' => $user->getFirstName(),
                            'last_name' => $user->getLastName(),
                            'username' => $user->getUsername(),
                            'media' => $user->getMedia(),
                            'dp' => $user->getDP(),
                        ];
                    }
                    if (!isset($this->users_array[intval($row['receiver'])])) {
                        $user = new User();
                        $user->setId(intval($row['receiver']), true);
                        $this->users_array[intval($row['receiver'])] = [
                            'id' => $user->getId(),
                            'first_name' => $user->getFirstName(),
                            'last_name' => $user->getLastName(),
                            'username' => $user->getUsername(),
                            'media' => $user->getMedia(),
                            'dp' => $user->getDP(),
                        ];
                    }
                }
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
        } elseif ($this->messages_array !== null) {
            return json_encode(['chats' => $this->messages_array, 'users' => $this->users_array]);
        } else {
            return '{}';
        }
    }
    
    /**
     * Sets user's object of which data is to be sent.
     * 
     * @param User $user Initialized user object
     * @return void
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
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
