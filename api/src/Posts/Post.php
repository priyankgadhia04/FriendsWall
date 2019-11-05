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
namespace FriendsWall\Posts;

use FriendsWall\Users\User;
use FriendsWall\Configs\DB;
use FriendsWall\Utils\TimeAgo;
use PDO;

class Post
{
    
    private $db;
    private $id;
    private $uid;
    private $gid;
    private $text;
    private $media;
    private $time;
    private $isdeleted;
    
    public function __construct(
        int $uid = null,
        string $text = null,
        string $media = null
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
        if ($uid === null && $text === null && $media === null) {
            return;
        }
        $this->setUid($uid);
        $this->setText($text);
        $this->setMedia($media);
    }
    
    public function setId($id): Post
    {
        $stmt = $this->db->prepare('SELECT * FROM posts where id = :id');
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $flagUserExists = false;
        while ($row = $stmt->fetch()) {
            $this->id = $row['id'];
            $this->uid = $row['uid'];
            $this->gid = $row['gid'];
            $this->text = $row['text'];
            $this->media = $row['media'];
            $this->time = $row['time'];
            $this->isdeleted = $row['isdeleted'];
            $flagUserExists = true;
        }
        if (!$flagUserExists) {
            throw new InvalidPostException("Post with given id doesnot exist");
        }
        return $this;
    }
    
    public function getId(): ?int
    {
        return intval($this->id);
    }
    
    public function setUid($id): Post
    {
        $this->checkID($id);
        $this->uid = $id;
        return $this;
    }
    
    public function getUid(): int
    {
        return $this->uid;
    }
    
    public function setGid(?int $id): Post
    {
        if ($id !== null) {
            $this->checkGID($id);
        }
        $this->gid = $id;
        return $this;
    }
    
    public function getGid(): ?int
    {
        return $this->gid;
    }
    
    public function setText($text): Post
    {
        if ($text !== null && strlen($text) > 2000) {
            throw new InvalidPostException('Text should be less than 2000 characters');
        }
        $this->text = $text;
        return $this;
    }
    
    public function getText(): ?string
    {
        return $this->text;
    }
    
    public function getTime(): ?string
    {
        return $this->time;
    }
    
    public function setMedia($media): Post
    {
        if ($media !== null) {
            if (strlen($media) !== 12 || (substr($media, -4) !== '.jpg' && substr($media, -4) !== '.mp4')) {
                throw new InvalidPostException('Invalid media name');
            }
        }
        $this->media = $media;
        return $this;
    }
    
    public function getMedia(): ?string
    {
        return $this->media;
    }
    
    public function getTimeAgo(): string
    {
        return TimeAgo::getTimeAgo(strtotime($this->time));
    }
    
    public function add(): void
    {
        $stmt = $this->db->prepare('INSERT INTO posts (uid, gid, text, media) VALUES (:uid, :gid, :text, :media)');
        $stmt->bindParam(':uid', $this->uid);
        $stmt->bindParam(':gid', $this->gid);
        $stmt->bindParam(':text', $this->text);
        $stmt->bindParam(':media', $this->media);
        $stmt->execute();
        $this->id = $this->db->lastInsertId();
        if ($this->gid === null) {
            $stmt2 = $this->db->prepare('SELECT * FROM friends WHERE (fromid = :id OR toid = :id) AND accepted = 1');
            $stmt2->bindParam(':id', $this->uid);
        } else {
            $stmt2 = $this->db->prepare('SELECT uid FROM group_members WHERE gid = :gid');
            $stmt2->bindParam(':gid', $this->gid);
        }
        $stmt2->execute();
        $stmt3 = $this->db->prepare('INSERT INTO feeds (uid, pid) VALUES (:uid, :pid)');
        if ($this->gid === null) {
            while ($row = $stmt2->fetch()) {
                $tid = $this->uid == $row['toid'] ? $row['fromid'] : $row['toid'];
                $stmt3->bindParam(':uid', $tid);
                $stmt3->bindParam(':pid', $this->id);
                $stmt3->execute();
            }
            $stmt3->bindParam(':uid', $this->uid);
            $stmt3->bindParam(':pid', $this->id);
            $stmt3->execute();
        } else {
            while ($row = $stmt2->fetch()) {
                $tid = $row['uid'];
                $stmt3->bindParam(':uid', $tid);
                $stmt3->bindParam(':pid', $this->id);
                $stmt3->execute();
            }
        }
    }
    
    public function delete(): void
    {
        
        $stmt = $this->db->prepare('UPDATE posts SET isdeleted = 1 WHERE id = :id');
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        $this->isdeleted = 1;
    }
    
    public function isLikedBy(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM likes WHERE pid = :pid and uid = :uid ORDER BY time DESC");
        $stmt->bindParam(':pid', $this->id);
        $stmt->bindParam(':uid', $id);
        $stmt->execute();
        if ($stmt->rowCount() === 1) {
            return true;
        }
        return false;
    }
    
    public function getLikes(): array
    {
        $stmt = $this->db->prepare("SELECT uid FROM likes WHERE pid = :pid ORDER BY time DESC");
        $stmt->bindParam(':pid', $this->id);
        $array = [];
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            $user = new User();
            $user->setId($row['uid'], true);
            $array[] = array(
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'username' => $user->getUsername(),
                'media' => $user->getMedia(),
                'dp' => $user->getDP()
            );
        }
        return $array;
    }
    
    public function getTotalLikes(): int
    {
        $stmt = $this->db->prepare("SELECT uid FROM likes WHERE pid = :pid ORDER BY time DESC");
        $stmt->bindParam(':pid', $this->id);
        $stmt->execute();
        return $stmt->rowCount();
    }
    
    public function getComments(): array
    {
        $stmt = $this->db->prepare("SELECT uid, text FROM comments WHERE pid = :pid ORDER BY time");
        $stmt->bindParam(':pid', $this->id);
        $array = [];
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            $user = new User();
            $user->setId($row['uid'], true);
            $array[] = array(
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'username' => $user->getUsername(),
                'media' => $user->getMedia(),
                'dp' => $user->getDP(),
                'text' => $row['text']
            );
        }
        return $array;
    }
    
    public function getTotalComments(): int
    {
        $stmt = $this->db->prepare("SELECT uid FROM comments WHERE pid = :pid");
        $stmt->bindParam(':pid', $this->id);
        $stmt->execute();
        return $stmt->rowCount();
    }
    
    public function getTotalCommenters(): int
    {
        $stmt = $this->db->prepare("SELECT DISTINCT uid FROM comments WHERE pid = :pid");
        $stmt->bindParam(':pid', $this->id);
        $stmt->execute();
        return $stmt->rowCount();
    }


    private function checkID(int $id): void
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            throw new InvalidPostException("User with given id does not exist");
        }
    }

    private function checkGID(int $id): void
    {
        $stmt = $this->db->prepare("SELECT isdeleted FROM groups WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            throw new InvalidPostException("Group with given id does not exist");
        }
        $row = $stmt->fetch();
        if ($row['isdeleted']) {
            throw new InvalidPostException("Group is deleted");
        }
    }
}