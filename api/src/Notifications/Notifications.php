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
namespace FriendsWall\Notifications;

use FriendsWall\Configs\DB;
use FriendsWall\Groups\Group;
use FriendsWall\Notifications\Notification;
use FriendsWall\Posts\Post;
use FriendsWall\Users\User;
use PDO;

/**
 * This class is used to get notifications of a users
 *
 * @author Aditya
 */
class Notifications
{
    
    public static function get(int $id, int $page): array
    {
        $results = ['notifications' => []];
        $result = &$results['notifications'];
        $dbh = new PDO(
            DB::PDO_CONNECTION_STRING,
            DB::USERNAME, DB::PASSWORD,
            array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            )
        );
        if ($page < 1) {
            $page = 1;
        }
        $page--;
        $limit = $page * 10;
        $stmt = $dbh->prepare("SELECT id, uid, table_name+0 AS 'table_name', entity_id, isread, time FROM notifications WHERE uid = :id ORDER BY id DESC LIMIT $limit,10;");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            switch (intval($row['table_name'])) {
                case Notification::TABLE_COMMENTS:
                    $tResult = [];
                    $tResult['type'] = Notification::TABLE_COMMENTS;
                    $tResult['id'] = $row['id'];
                    $tResult['isread'] = $row['isread'];
                    $tResult['time'] = $row['time'];
                    $stmt1 = $dbh->prepare('SELECT * FROM comments WHERE id = :id');
                    $stmt1->bindValue(':id', $row['entity_id']);
                    $stmt1->execute();
                    while ($row1 = $stmt1->fetch()) {
                        $post = new Post();
                        $post->setId($row1['pid']);
                        if ($post->getGid()) {
                            $group = new Group();
                            $group->setId($post->getGid());
                            if ($group->hasMember($id)) {
                                $user = new User();
                                $user->setId(intval($row1['uid']), true);
                                $tResult['text'] = $row1['text'];
                                $tResult['pid'] = $row1['pid'];
                                $tResult['total_comments'] = $post->getTotalCommenters();
                                $tResult['user'] = [
                                    'username' => $user->getUsername(),
                                    'first_name' => $user->getFirstName(),
                                    'last_name' => $user->getLastName(),
                                    'media' => $user->getMedia(),
                                    'dp' => $user->getDP(),
                                ];
                                $result[] = $tResult;
                            }
                        } else {
                            $user = new User();
                            $user->setId(intval($row1['uid']), true);
                            if ($user->isFriendWith($id)) {
                                $tResult['text'] = $row1['text'];
                                $tResult['pid'] = $row1['pid'];
                                $tResult['total_comments'] = $post->getTotalCommenters();
                                $tResult['user'] = [
                                    'username' => $user->getUsername(),
                                    'first_name' => $user->getFirstName(),
                                    'last_name' => $user->getLastName(),
                                    'media' => $user->getMedia(),
                                    'dp' => $user->getDP(),
                                ];
                                $result[] = $tResult;
                            }
                        }
                    }
                    break;
                case Notification::TABLE_FRIENDS:
                    $tResult = [];
                    $tResult['type'] = Notification::TABLE_FRIENDS;
                    $tResult['id'] = $row['id'];
                    $tResult['isread'] = $row['isread'];
                    $tResult['time'] = $row['time'];
                    $stmt1 = $dbh->prepare("SELECT * FROM friends WHERE id = :id;");
                    $stmt1->bindValue(':id', $row['entity_id']);
                    $stmt1->execute();
                    while ($row1 = $stmt1->fetch()) {
                        $user = new User();
                        $user->setId(intval($row1['toid']), true);
                        $tResult['user'] = [
                            'username' => $user->getUsername(),
                            'first_name' => $user->getFirstName(),
                            'last_name' => $user->getLastName(),
                            'media' => $user->getMedia(),
                            'dp' => $user->getDP(),
                        ];
                        $result[] = $tResult;
                    }
                    break;
                case Notification::TABLE_GROUP_MEMBERS:
                    $tResult = [];
                    $tResult['type'] = Notification::TABLE_GROUP_MEMBERS;
                    $tResult['id'] = $row['id'];
                    $tResult['isread'] = $row['isread'];
                    $tResult['time'] = $row['time'];
                    $stmt1 = $dbh->prepare("SELECT * FROM group_members WHERE id = :id;");
                    $stmt1->bindValue(':id', $row['entity_id']);
                    $stmt1->execute();
                    while ($row1 = $stmt1->fetch()) {
                        $group = new Group();
                        $group->setId(intval($row1['gid']), true);
                        $tResult['group'] = [
                            'id' => $group->getId(),
                            'name' => $group->getName(),
                            'media' => $group->getMedia(),
                            'dp' => $group->getDP(),
                        ];
                        $result[] = $tResult;
                    }
                    break;
                case Notification::TABLE_LIKES:
                    $tResult = [];
                    $tResult['type'] = Notification::TABLE_LIKES;
                    $tResult['id'] = $row['id'];
                    $tResult['isread'] = $row['isread'];
                    $tResult['time'] = $row['time'];
                    $stmt1 = $dbh->prepare("SELECT * FROM likes WHERE id = :id;");
                    $stmt1->bindValue(':id', $row['entity_id']);
                    $stmt1->execute();
                    while ($row1 = $stmt1->fetch()) {
                        $post = new Post();
                        $post->setId($row1['pid']);
                        if ($post->getGid()) {
                            $group = new Group();
                            $group->setId($post->getGid());
                            if ($group->hasMember($id)) {
                                $user = new User();
                                $user->setId(intval($row1['uid']), true);
                                $tResult['pid'] = $row1['pid'];
                                $tResult['total_likes'] = $post->getTotalLikes();
                                $tResult['user'] = [
                                    'username' => $user->getUsername(),
                                    'first_name' => $user->getFirstName(),
                                    'last_name' => $user->getLastName(),
                                    'media' => $user->getMedia(),
                                    'dp' => $user->getDP(),
                                ];
                                $result[] = $tResult;
                            }
                        } else {
                            $user = new User();
                            $user->setId(intval($row1['uid']), true);
                            if ($user->isFriendWith($id)) {
                                $tResult['pid'] = $row1['pid'];
                                $tResult['total_likes'] = $post->getTotalLikes();
                                $tResult['user'] = [
                                    'username' => $user->getUsername(),
                                    'first_name' => $user->getFirstName(),
                                    'last_name' => $user->getLastName(),
                                    'media' => $user->getMedia(),
                                    'dp' => $user->getDP(),
                                ];
                                $result[] = $tResult;
                            }
                        }
                    }
                    break;
                default:
                    break;
            }
        }
        return $results;
    }
}
