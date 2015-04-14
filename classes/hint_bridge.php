<?php

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