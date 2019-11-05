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

use FriendsWall\Notifications\Notifications;
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
class NotificationInformer implements Event
{

    private $user;
    private $error;
    private $notifications_array;
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
            $tArray = Notifications::get($this->user->getId(), 1);
            if ($tArray !== $this->notifications_array) {
                $this->notifications_array = $tArray;
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
        } elseif ($this->notifications_array !== null) {
            return json_encode($this->notifications_array);
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
