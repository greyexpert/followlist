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
class FOLLOWLIST_CLASS_HintBridge
{
    /**
     * Class instance
     *
     * @var FOLLOWLIST_CLASS_HintBridge
     */
    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return FOLLOWLIST_CLASS_HintBridge
     */
    public static function getInstance()
    {
        if ( !isset(self::$classInstance) )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function __construct()
    {

    }

    public function onCollectInfoConfigs( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();
        $params = $event->getParams();
        
        if ( $params["entityType"] != HINT_BOL_Service::ENTITY_TYPE_USER )
        {
            return;
        }
        
        $event->add(array(
            "key" => "followers-count",
            "label" => $language->text("followlist", "hint_info_label")
        ));
    }
    
    public function onInfoPreview( OW_Event $event )
    {
        $language = OW::getLanguage();
        $params = $event->getParams();
        
        if ( $params["entityType"] != HINT_BOL_Service::ENTITY_TYPE_USER )
        {
            return;
        }
        
        if ( $params["key"] == "followers-count" )
        {
            $event->setData($language->text("followlist", "hint_info_preview"));
        }
    }
    
    public function onInfoRender( OW_Event $event )
    {
        $language = OW::getLanguage();
        $params = $event->getParams();
        
        if ( $params["entityType"] != HINT_BOL_Service::ENTITY_TYPE_USER )
        {
            return;
        }
        
        $userId = $params["entityId"];
        
        if ( $params["key"] != "followers-count" )
        {
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
        
        $count = FOLLOWLIST_CLASS_NewsfeedBridge::getInstance()
                ->getFollowingUsersCount(FOLLOWLIST_CLASS_NewsfeedBridge::FEED_TYPE_USER, $userId);
        
        $userName = BOL_UserService::getInstance()->getUserName($userId);
        $url = OW::getRouter()->urlForRoute('followlist-user-followers', array(
            'userName'=>$userName
        ));
        
        $event->setData($language->text("followlist", "hint_info", array(
            "count" => $count,
            "url" => $url
        )));
    }

    public function init()
    {
        OW::getEventManager()->bind("hint.collect_info_config", array($this, 'onCollectInfoConfigs'));
        OW::getEventManager()->bind("hint.info_preview", array($this, 'onInfoPreview'));
        OW::getEventManager()->bind("hint.info_render", array($this, 'onInfoRender'));
    }
}