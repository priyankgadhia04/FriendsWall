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
namespace FriendsWall\Configs;

/**
 * This class provides credentials to connect database using constant variables.
 *
 * @author Aditya Nathwani <adityanathwani@gmail.com>
 */
class DB
{

    /** Host for database connection. */
    public const HOST = "localhost";
    /** Username for database connection. */
    public const USERNAME = "root";
    /** Password for database connection. */
    public const PASSWORD = "";
    /** Database name for database connection. */
    public const DB_NAME = "friends_wall";
    /** PDO connection string */
    public const PDO_CONNECTION_STRING = 'mysql:host=' . DB::HOST . ';dbname=' . DB::DB_NAME;

}
