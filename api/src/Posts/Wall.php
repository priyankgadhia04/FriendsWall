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

use FriendsWall\Posts\Post;
use FriendsWall\Configs\DB;
use FriendsWall\Users\User;
use FriendsWall\Groups\Group;
use PDO;

/**
 * This class is used to get posts of user or group
 *
 * @author Aditya Nathwani <adityanathwani@gmail.com>
 */
class Wall
{

    public static function getPosts(int $uid, int $reqUid, int $page = 1, int $gid = null): array
    {
        $result = ['users' => null, 'groups' => null, 'posts' => null];
        $users = &$result['users'];
        $posts = &$result['posts'];
        $groups = &$result['groups'];
        $uids = [];
        $gids = [];

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
        $limit = $page * 20;
        if ($gid === null) {
            $postOption = 'uid';
            $postOption2 = 'AND gid IS NULL';
        } else {
            $postOption = 'gid';
            $postOption2 = '';
        }
        $q = $dbh->prepare("SELECT id, uid, gid, text, time, media FROM posts WHERE $postOption = :id $postOption2 AND isdeleted = 0 ORDER BY time DESC LIMIT $limit,20;");
        if ($gid === null) {
            $q->bindValue(':id', $uid);
        } else {
            $q->bindValue(':id', $gid);
        }
        $q->execute();
        while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
            $row['timeago'] = \FriendsWall\Utils\TimeAgo::getTimeAgo(strtotime($row['time']));
            $post = (new Post())->setId(intval($row['id']));
            $row['isLiked'] = $post->isLikedBy($reqUid);
            $row['totalLikes'] = $post->getTotalLikes();
            $row['totalComments'] = $post->getTotalComments();
            $posts[] = $row;
            $uids[$row['uid']] = true;
            if ($row['gid']) {
                $gids[$row['gid']] = true;
            }
        }
        foreach ($uids as $key => $value) {
            $user = new User();
            $user->setId($key, true);
            $users[$user->getId()] = [
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'username' => $user->getUsername(),
                'media' => $user->getMedia(),
                'dp' => $user->getDp(),
            ];
        }
        foreach ($gids as $key => $value) {
            $group = new Group();
            $group->setId($key, true);
            $groups[$group->getId()] = [
                'id', $group->getId(),
                'name', $group->getName(),
                'media', $group->getMedia(),
                'dp', $group->getDP(),
            ];
        }
        return $result;
    }
    
    public static function getWalls(int $uid, int $reqUid, $page = 1): array
    {
        $result = ['users' => null, 'groups' => null, 'posts' => null];
        $users = &$result['users'];
        $posts = &$result['posts'];
        $groups = &$result['groups'];
        $uids = [];
        $gids = [];

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
        $limit = $page * 20;
        $q = $dbh->prepare("SELECT id, uid, gid, text, time, media FROM posts WHERE id IN (SELECT pid FROM feeds WHERE uid = :uid) AND isdeleted = 0 ORDER BY time DESC LIMIT $limit,20;");
        $q->bindValue(':uid', $uid);
        $q->execute();
        $user = new User();
        $user->setId($uid);
        while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
            if (!$user->isFriendWith(intval($row['uid'])) && $row['uid'] != $user->getId() && !$row['gid']) {
                continue;
            }
            if ($row['gid'] && !(new Group())->setId(intval($row['gid']), true)->hasMember($user->getId())) {
                continue;
            }
            $row['timeago'] = \FriendsWall\Utils\TimeAgo::getTimeAgo(strtotime($row['time']));
            $post = (new Post())->setId(intval($row['id']));
            $row['isLiked'] = $post->isLikedBy($reqUid);
            $row['totalLikes'] = $post->getTotalLikes($reqUid);
            $row['totalComments'] = $post->getTotalComments($reqUid);
            $posts[] = $row;
            $uids[$row['uid']] = true;
            if ($row['gid']) {
                $gids[$row['gid']] = true;
            }
        }
        foreach ($uids as $key => $value) {
            $user = new User();
            $user->setId($key, true);
            $users[$user->getId()] = [
                'id' => $user->getId(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'username' => $user->getUsername(),
                'media' => $user->getMedia(),
                'dp' => $user->getDp(),
            ];
        }
        foreach ($gids as $key => $value) {
            $group = new Group();
            $group->setId($key, true);
            $groups[$group->getId()] = [
                'id' => $group->getId(),
                'name' => $group->getName(),
                'media' => $group->getMedia(),
                'dp' => $group->getDP(),
            ];
        }
        return $result;
    }
}