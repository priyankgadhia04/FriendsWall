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
namespace FriendsWall\Likes;

use FriendsWall\Users\User;
use FriendsWall\Notifications\Notification;
use FriendsWall\Posts\Post;
use FriendsWall\Configs\DB;
use PDO;

class Like
{
    
    private $db;
    private $id;
    private $pid;
    private $uid;
    private $time;
    
    public function __construct(
        int $pid,
        int $uid
    )
    {
        $this->db = new PDO(
            DB::PDO_CONNECTION_STRING,
            DB::USERNAME, DB::PASSWORD,
            array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            )
        );
        $stmt = $this->db->prepare('SELECT uid FROM posts where id = :pid');
        $stmt->bindParam(':pid', $pid);
        $stmt->execute();
        $flagUserExists = false;
        while ($row = $stmt->fetch()) {
            if (!(new User())->setId(intval($row['uid']))->isFriendWith($uid) && intval($row['uid']) !== $uid) {
                throw new InvalidLikeException('You are not allowed to like this post');
            }
            $flagUserExists = true;
        }
        if (!$flagUserExists) {
            throw new InvalidLikeException('Post with given id doesnot exist');
        }
        $stmt2 = $this->db->prepare('SELECT id FROM likes where pid = :pid and uid = :uid');
        $stmt2->bindParam(':pid', $pid);
        $stmt2->bindParam(':uid', $uid);
        $stmt2->execute();
        while ($row = $stmt2->fetch()) {
            $this->id = $row['id'];
        }
        $this->pid = $pid;
        $this->uid = $uid;
    }
    
    public function like(): Like
    {
        if ($this->id !== null) {
            throw new InvalidLikeException("Already liked this post");
        }
        $stmt = $this->db->prepare('INSERT INTO likes (pid, uid) VALUES (:pid, :uid)');
        $stmt->bindParam(':pid', $this->pid);
        $stmt->bindParam(':uid', $this->uid);
        $stmt->execute();
        $this->id = $this->db->lastInsertId();
        $post = new Post();
        $post->setId($this->pid);
        if ($post->getUid() !== $_SESSION['id']) {
            (new Notification())
            ->setParams($post->getUid(), Notification::TABLE_LIKES, $this->id)
            ->add();
        }
        return $this;
    }
    
    public function unlike(): Like
    {
        if ($this->id === null) {
            throw new InvalidLikeException('Already unliked the post');
        }
        $stmt = $this->db->prepare('DELETE FROM likes WHERE id = :id');
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        $this->id = null;
        return $this;
    }
    
    public function toggleLike(): Like
    {
        if ($this->id !== null) {
            $this->unlike();
        } elseif ($this->id === null) {
            $this->like();
        }
        return $this;
    }
}
