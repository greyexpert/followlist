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
 * @package followlist.classes
 */
class FOLLOWLIST_CLASS_SnippetsBridge
{
    
    const SNIPPET_NAME = "followers";
    
    /**
     * Class instance
     *
     * @var FOLLOWLIST_CLASS_SnippetsBridge
     */
    protected static $classInstance;

    /**
     * Returns class instance
     *
     * @return FOLLOWLIST_CLASS_SnippetsBridge
     */
    public static function getInstance()
    {
        if ( !isset(self::$classInstance) )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    protected function __construct()
    {
        
    }
    
    public function isActive()
    {
        return OW::getPluginManager()->isPluginActive("snippets");
    }
    
    public function collectSnippets( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();
        $params = $event->getParams();
        
        if ( $params["entityType"] != SNIPPETS_CLASS_EventHandler::ENTITY_TYPE_USER )
        {
            return;
        }

        $showEmpty = !$params["hideEmpty"];
        $userId = $params["entityId"];
        $preview = $params["preview"];
        
        $snippet = new SNIPPETS_CMP_Snippet(self::SNIPPET_NAME, $userId);
        
        if ( $preview )
        {
            $snippet->setLabel($language->text("followlist", "snippet_preview"));
            $snippet->setIconClass("ow_ic_friends");
            $event->add($snippet);
            
            return;
        }
        
        $eventParams =  array(
            'action' => 'followers_view',
            'ownerId' => $userId,
            'viewerId' => OW::getUser()->getId()
        );
        
        try
        {
            OW::getEventManager()->getInstance()->call('privacy_check_permission', $eventParams);
        }
        catch( RedirectException $exception )
        {
            return;
        }
        
        $newsfeedBridge = FOLLOWLIST_CLASS_NewsfeedBridge::getInstance();
        $users = $newsfeedBridge->getFollowingUsers(FOLLOWLIST_CLASS_NewsfeedBridge::FEED_TYPE_USER, $userId, array(0, 4));
        $total = $newsfeedBridge->getFollowingUsersCount(FOLLOWLIST_CLASS_NewsfeedBridge::FEED_TYPE_USER, $userId);

        $userName = BOL_UserService::getInstance()->getUserName($userId);
        $url = OW::getRouter()->urlForRoute('followlist-user-followers', array(
            'userName'=>$userName
        ));

        $snippet->setLabel($language->text("followlist", "snippet_label", array(
            "count" => '<span class="ow_txt_value">' . $total . '</span>'
        )));

        $snippet->setUrl($url);

        if ( !empty($users) )
        {
            $idList = array();

            foreach ( $users as $user )
            {
                $idList[] = $user->id;
            }

            $usersData = BOL_AvatarService::getInstance()->getDataForUserAvatars($idList, true, false, false, false);

            $images = array();
            foreach ( $usersData as $user )
            {
                $images[] = $user["src"];
            }

            $displayType = count($images) > 1 ? SNIPPETS_CMP_Snippet::DISPLAY_TYPE_4 : SNIPPETS_CMP_Snippet::DISPLAY_TYPE_1;
            $snippet->setDisplayType($displayType);

            $snippet->setImages($images);
        }

        if (!empty($users) || $showEmpty) {
            $event->add($snippet);
        }
    }
    
    public function init()
    {
        OW::getEventManager()->bind("snippets.collect_snippets", array($this, "collectSnippets"));
    }
}