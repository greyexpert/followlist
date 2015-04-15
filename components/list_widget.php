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
 * @package followlist.components
 */
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