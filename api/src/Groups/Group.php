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
namespace FriendsWall\Groups;

use FriendsWall\Configs\DB;
use FriendsWall\Users\InvalidUserAttributeException;
use FriendsWall\Users\User;
use FriendsWall\Notifications\Notification;
use PDO;

/**
 * This class contains various methods for handling groups.
 * 
 * All the activities related to groups can be done with this class like
 * creating account, updating and deleting.
 * @author Aditya Nathwani <adityanathwani@gmail.com>
 */
class Group
{

    private $id;
    private $name;
    private $description = "";
    private $media = "";
    private $dp = "";
    private $isdeleted = 0;
    private $db;

    /**
     * The constructor accepts attributes associated with groups.
     * 
     * To create a new group, provide all information of the group.
     * To get existing group, leave all the arguments blank. after that
     * you can initialize the object using {@see Group::setId()},
     * and set argument $initObject to true.
     * 
     * @param string $email email of the user.
     * @param string $username username of the user.
     * @param string $firstName first name of the user.
     * @param string $lastName last name of the user.
     * @param string $gender gender of the user (male/female).
     * @param string $dob date of birth of the user (YYYY-MM-DD).
     * @param string $password password of the user.
     */
    public function __construct(
        string $name = null,
        string $description = null
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
        if ($name === null && $description === null) {
            return;
        }
        $this->setName($name);
        $this->setDescription($description);
    }

    /**
     * Sets id of the group.
     * 
     * You can also initialize the object using this method by setting
     * $initObject to true. This will change other attributes
     * (name, description) etc. corresponding to given $id.
     * 
     * @param int $id ID of the group.
     * @param bool $initObject set this to true to initialize object
     * according to given $id.
     * @return Group
     * @throws InvalidGroupAttributeException
     */
    public function setId(int $id, bool $initObject = false): Group
    {
        if ($initObject) {
            $stmt = $this->db->prepare('SELECT * FROM groups where id = ?');
            $flagGroupExists = false;
            if ($stmt->execute(array($id))) {
                while ($row = $stmt->fetch()) {
                    $this->id = $row['id'];
                    $this->name = $row['name'];
                    $this->description = $row['description'];
                    $this->media = $row['media'];
                    $this->dp = $row['dp'];
                    $this->isdeleted = $row['isdeleted'];
                    $flagGroupExists = true;
                }
                if (!$flagGroupExists) {
                    throw new InvalidGroupAttributeException("Group with given id doesnot exist");
                }
            }
        } else {
            $this->id = $id;
        }
        return $this;
    }

    /**
     * Returns the id of the user or null if not set in the object.
     * 
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }


    /**
     * Sets name of the group
     * 
     * @param string $name name of the group
     * @return Group
     * @throws InvalidGroupAttributeException
     */
    public function setName(string $name): Group
    {
        if (!preg_match("/^[ a-zA-Z0-9'-]{2,25}$/", $name)) {
            throw new InvalidUserAttributeException('Group name invalid');
        }
        $this->name = $name;
        return $this;
    }

    /**
     * Returns name of the group or null if not set in the object.
     * 
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }
    
    /**
     * Returns media directory of the group or empty string if not set in the object.
     * 
     * @return string
     */
    public function getMedia(): string
    {
        return $this->media;
    }
    /**
     * Sets DP name of the group
     * 
     * @param string $name name of DP without extension (9 characters)
     * @return Group
     * @throws InvalidGroupAttributeException
     */
    public function setDP(string $name): Group
    {
        if (!preg_match("/^[a-zA-Z0-9]{9}$/", $name)) {
            throw new InvalidUserAttributeException('DP Name invalid');
        }
        $this->dp = $name;
        return $this;
    }

    /**
     * Returns DP name of the group or empty string if not set in the object.
     * 
     * @return string
     */
    public function getDP(): string
    {
        return $this->dp;
    }

    /**
     * Sets Description of the group
     * 
     * @param string $description description text of the user (256 characters)
     * @return Group
     * @throws InvalidGroupAttributeException
     */
    public function setDescription(string $description): Group
    {
        if (strlen($description) > 256) {
            throw new InvalidGroupAttributeException('description must be less than 256 characters');
        }
        $this->description = $description;
        return $this;
    }
    
    public function setDeleted(bool $deleted): Group
    {
        $this->isdeleted = (int) $deleted;
        return $this;
    }
    
    public function isDeleted(): bool
    {
        return (bool) $this->isdeleted;
    }

    /**
     * Returns description of the user or empty string if not set in the object.
     * 
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }
    
    /**
     * Returns true if $id is a member of the group
     * 
     * @param int $id user's id to check membership
     * @return bool
     */
    public function hasMember(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT * FROM group_members WHERE gid = :gid AND uid = :uid");
        $stmt->bindParam(':gid', $this->id);
        $stmt->bindParam(':uid', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    }
    
    /**
     * Returns true if $id is an admin of the group
     * 
     * @param int $id user's id to check if the user is an admin
     * @return bool
     */
    public function hasAdmin(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT * FROM group_members WHERE gid = :gid AND uid = :uid AND isadmin = 1");
        $stmt->bindParam(':gid', $this->id);
        $stmt->bindParam(':uid', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    }
    
    public function addMember(int $id, bool $admin = false): Group
    {
        $uid = (new User())->setId($id, true)->getId();
        $isadmin = 0;
        if($admin === true) {
            $isadmin = 1;
        }
        $stmt = $this->db->prepare('INSERT INTO group_members (gid, uid, isadmin) VALUES (:gid, :uid, :isadmin)');
        $stmt->bindParam(':gid', $this->id);
        $stmt->bindParam(':uid', $uid);
        $stmt->bindParam(':isadmin', $isadmin);
        $stmt->execute();
        (new Notification())
            ->setParams($uid, Notification::TABLE_GROUP_MEMBERS, $this->db->lastInsertId())
            ->add();
        return $this;
    }
    
    public function removeMember(int $id): Group
    {
        $uid = (new User())->setId($id, true)->getId();
        $stmt = $this->db->prepare('DELETE FROM group_members WHERE gid = :gid AND uid = :uid');
        $stmt->bindParam(':gid', $this->id);
        $stmt->bindParam(':uid', $uid);
        $stmt->execute();
        return $this;
    }
    
    public function makeAdmin(int $id): Group
    {
        $uid = (new User())->setId($id, true)->getId();
        $stmt = $this->db->prepare('UPDATE group_members SET isadmin=1 WHERE gid = :gid AND uid = :uid');
        $stmt->bindParam(':gid', $this->id);
        $stmt->bindParam(':uid', $uid);
        $stmt->execute();
        return $this;
    }
    
    public function removeAdmin(int $id): Group
    {
        $uid = (new User())->setId($id, true)->getId();
        $stmt = $this->db->prepare('UPDATE group_members SET isadmin=0 WHERE gid = :gid AND uid = :uid');
        $stmt->bindParam(':gid', $this->id);
        $stmt->bindParam(':uid', $uid);
        $stmt->execute();
        return $this;
    }

    /**
     * Creates a new group.
     * 
     * all user attributes (all the parameters of constructor)
     * must be set either using constructor or by using corresponding
     * methods.
     * 
     * @return Group
     */
    public function create(int $uid): Group
    {
        $stmt = $this->db->prepare('INSERT INTO groups (name, media, description) VALUES (:name, :media, :description)');
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        do {
            $media = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"),0,8);
        } while (is_dir('../../media/' . $media));
        $stmt->bindParam(':media', $media);
        $stmt->execute();
        $this->id = $this->db->lastInsertId();
        $this->addMember($uid, true);
        return $this;
    }

    /**
     * Updates the group details.
     * 
     * all user attributes must be set to updated ones using
     * corresponding methods.
     * 
     * @return Group
     */
    public function update(): Group
    {
        $stmt = $this->db->prepare("UPDATE groups set name=:name, dp=:dp, description=:description, isdeleted=:isdeleted WHERE id=:id");
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':dp', $this->dp);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':isdeleted', $this->isdeleted);
        $stmt->execute();
        $stmt2 = $this->db->prepare('SELECT * FROM groups where id = ?');
        if ($stmt2->execute(array($this->id))) {
            while ($row = $stmt2->fetch()) {
                $this->id = $row['id'];
                $this->name = $row['name'];
                $this->description = $row['description'];
                $this->media = $row['media'];
                $this->dp = $row['dp'];
                $this->isdeleted = $row['isdeleted'];
            }
        }
        return $this;
    }
}
