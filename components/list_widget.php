<?php

class FOLLOWLIST_CMP_ListWidget extends BASE_CLASS_Widget
{

    /**
     * @return Constructor.
     */
    public function __construct( $feedType, $feedId, BASE_CLASS_WidgetParameter $params )
    {
        parent::__construct();

        $count = ( empty($params->customParamList['count']) ) ? 9 : (int) $params->customParamList['count'];

        if ( $this->assignList($feedType, $feedId, $count) )
        {
            $this->setSettingValue(self::SETTING_TOOLBAR, array(array(
                'label' => OW::getLanguage()->text('followlist', 'widget_view_all'),
                'href' => $this->getViewAllUrl($feedType, $feedId)
            )));
        }
        
        $tpl = OW::getPluginManager()->getPlugin("followlist")->getCmpViewDir() . "list_widget.html";
        $this->setTemplate($tpl);
    }
    
    protected function getViewAllUrl( $feedType, $feedId )
    {
        return OW::getRouter()->urlForRoute('followlist-list', array(
            'feedType' => $feedType,
            "feedId" => $feedId
        ));
    }

    private function assignList( $feedType, $feedId, $count )
    {
        $list = FOLLOWLIST_CLASS_NewsfeedBridge::getInstance()->getFollowingUsers($feedType, $feedId, array(0, $count));

        $idlist = array();
        foreach ( $list as $item )
        {
            $idlist[] = $item->id;
        }

        $data = array();

        if ( !empty($idlist) )
        {
            $data = BOL_AvatarService::getInstance()->getDataForUserAvatars($idlist);
        }

        $this->assign("userIdList", $idlist);
        $this->assign("data", $data);

        return !empty($idlist);
    }

    public static function getSettingList()
    {
        $settingList = array();
        $settingList['count'] = array(
            'presentation' => self::PRESENTATION_NUMBER,
            'label' => OW_Language::getInstance()->text('followlist', 'widget_settings_count'),
            'value' => 9
        );

        return $settingList;
    }

    public static function getStandardSettingValueList()
    {
        return array(
            self::SETTING_SHOW_TITLE => true,
            self::SETTING_WRAP_IN_BOX => true,
            self::SETTING_TITLE => OW_Language::getInstance()->text('followlist', 'widget_title'),
            self::SETTING_ICON => self::ICON_FRIENDS
        );
    }

    public static function getAccess()
    {
        return self::ACCESS_ALL;
    }
}