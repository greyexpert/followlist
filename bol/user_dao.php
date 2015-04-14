<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2015, Sergey Kambalin
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice, this list of conditions and
 *  the following disclaimer.
 *
 *  - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *  the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *  - Neither the name of the Oxwall Foundation nor the names of its contributors may be used to endorse or promote products
 *  derived from this software without specific prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package followlist.bol
 */
class FOLLOWLIST_BOL_UserDao extends BOL_UserDao
{
    /**
     * Singleton instance.
     *
     * @var FOLLOWLIST_BOL_UserDao
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return FOLLOWLIST_BOL_UserDao
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }
    
    public function findUserListByIdList( array $idList, array $limit )
    {
        if ( empty($idList) )
        {
            return array();
        }
        
        $queryParts = $this->getUserQueryFilter("u", "id", array(
            "method" => "FOLLOWLIST_BOL_UserDao::findUserListByIdList"
        ));

        $query = "SELECT `u`.* FROM `{$this->getTableName()}` AS `u` "
            . "WHERE u.id IN (" . $this->dbo->mergeInClause($idList) . ") AND {$queryParts["where"]} " 
            . "ORDER BY `u`.`activityStamp` DESC" . ( !empty($queryParts["order"]) ? ", " . $queryParts["order"] : "" )
            . "LIMIT ?,?";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), $limit);
    }
    
    public function findUserCountByIdList( $idList )
    {
        if ( empty($idList) )
        {
            return 0;
        }
        
        $queryParts = $this->getUserQueryFilter("u", "id", array(
            "method" => "FOLLOWLIST_BOL_UserDao::findUserCountByIdList"
        ));

        $query = "SELECT COUNT(DISTINCT `u`.id) FROM `{$this->getTableName()}` AS `u` "
            . "WHERE u.id IN (" . $this->dbo->mergeInClause($idList) . ") AND {$queryParts["where"]}";

        return $this->dbo->queryForColumn($query);
    }
}