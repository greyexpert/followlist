<?php

class FOLLOWLIST_CMP_UserFollowersWidget extends FOLLOWLIST_CMP_ListWidget
{
    public function __construct(BASE_CLASS_WidgetParameter $params) 
    {
        $feedId = $params->additionalParamList['entityId'];
        
        parent::__construct(FOLLOWLIST_CLASS_NewsfeedBridge::FEED_TYPE_USER, $feedId, $params);
        
        $eventParams =  array(
            'action' => 'followers_view',
            'ownerId' => $feedId,
            'viewerId' => OW::getUser()->getId()
        );
        
        try
        {
            OW::getEventManager()->getInstance()->call('privacy_check_permission', $eventParams);
        }
        catch( RedirectException $exception )
        {
            $this->setVisible(false);
            
            return;
        }
    }
    
    protected function getViewAllUrl($feedType, $feedId) 
    {
        $user = BOL_UserService::getInstance()->findUserById($feedId);
        
        if ( $user === null )
        {
            return null;
        }
        
        return OW::getRouter()->urlForRoute("followlist-user-followers", array(
            "userName" => $user->username
        ));
    }
}