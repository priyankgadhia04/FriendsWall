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
namespace FriendsWall\Comments;

use FriendsWall\Posts\Post;
use FriendsWall\Notifications\Notification;
use FriendsWall\Users\User;
use FriendsWall\Configs\DB;
use PDO;

class Comment
{
    
    private $db;
    private $id;
    private $pid;
    private $uid;
    private $text;
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
                throw new InvalidCommentException('You are not allowed to comment on this post');
            }
            $flagUserExists = true;
        }
        if (!$flagUserExists) {
            throw new InvalidCommentException('Post with given id doesnot exist');
        }
        $this->pid = $pid;
        $this->uid = $uid;
    }
    
    public function comment(): Comment
    {
        if ($this->id !== null) {
            throw new InvalidCommentException("Comment already exists");
        }
        $stmt = $this->db->prepare('INSERT INTO comments (pid, uid, text) VALUES (:pid, :uid, :text)');
        $stmt->bindParam(':pid', $this->pid);
        $stmt->bindParam(':uid', $this->uid);
        $stmt->bindParam(':text', $this->text);
        $stmt->execute();
        $this->id = $this->db->lastInsertId();
        $post = new Post();
        $post->setId($this->pid);
        if ($post->getUid() !== $_SESSION['id']) {
            (new Notification())
            ->setParams($post->getUid(), Notification::TABLE_COMMENTS, $this->id)
            ->add();
        }
        return $this;
    }
    
    public function delete(): Comment
    {
        if ($this->id === null) {
            throw new InvalidCommentException('Comment not found');
        }
        $stmt = $this->db->prepare('DELETE FROM comments WHERE id = :id');
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        $this->id = null;
        return $this;
    }
    
    public function setId(int $id): Comment
    {
        $stmt = $this->db->prepare('SELECT id FROM comments where pid = :pid AND uid = :uid AND id = :id');
        $stmt->bindParam(':pid', $this->pid);
        $stmt->bindParam(':uid', $this->uid);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        if ($stmt->rowCount() !== 1) {
            throw new InvalidCommentException('Comment with given id not found');
        }
        while ($row = $stmt->fetch()) {
            $this->id = $row['id'];
        }
        return $this;
    }
    
    public function setText(string $text): Comment
    {
        if (strlen($text) > 128) {
            throw new InvalidCommentException('Comment must be of less 128 characers');
        }
        $this->text = $text;
        return $this;
    }
}
