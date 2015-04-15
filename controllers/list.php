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
 * @package followlist.controllers
 */
class FOLLOWLIST_CTRL_List extends OW_ActionController
{
    public function userFollowers( $params )
    {
        $userName = $params["userName"];
        $user = BOL_UserService::getInstance()->findByUsername($userName);
        
        if ( $user === null )
        {
            throw new Redirect404Exception;
        }
        
        $eventParams =  array(
            'action' => 'followers_view',
            'ownerId' => $user->id,
            'viewerId' => OW::getUser()->getId()
        );
        
        OW::getEventManager()->getInstance()->call('privacy_check_permission', $eventParams);
        
        OW::getNavigation()->activateMenuItem(BOL_NavigationService::MENU_TYPE_MAIN, "base", "users_main_menu_item");
        
        $language = OW::getLanguage();
        
        $assigns = array(
            "user" => BOL_UserService::getInstance()->getDisplayName($user->id)
        );
        
        OW::getDocument()->setTitle($language->text("followlist", "user_followers_page_title", $assigns));
        OW::getDocument()->setHeading($language->text("followlist", "user_followers_page_heading", $assigns));
        
        return $this->index(array(
            "feedType" => FOLLOWLIST_CLASS_NewsfeedBridge::FEED_TYPE_USER,
            "feedId" => $user->id
        ));
    }
    
    public function index( $params )
    {
        $feedType = $params["feedType"];
        $feedId = $params["feedId"];

        $tpl = OW::getPluginManager()->getPlugin("followlist")->getCtrlViewDir() . "list_index.html";
        $this->setTemplate($tpl);
        
        $page = (!empty($_GET['page']) && intval($_GET['page']) > 0 ) ? $_GET['page'] : 1;
        $perPage = 20;
        $first = ($page - 1) * $perPage;
        $count = $perPage;

        $newsfeedBridge = FOLLOWLIST_CLASS_NewsfeedBridge::getInstance();
        
        $dtoList = $newsfeedBridge->getFollowingUsers($feedType, $feedId, array($first, $count));
        $listCount = $newsfeedBridge->getFollowingUsersCount($feedType, $feedId);

        $listCmp = new FOLLOWLIST_UserList($dtoList, $listCount, 20);
        $this->addComponent('listCmp', $listCmp);
        
        $this->assign("feedType", $feedType);
        $this->assign("feedId", $feedId);
    }
}


class FOLLOWLIST_UserList extends BASE_CMP_Users
{
    public function __construct( array $list, $itemCount, $usersOnPage, $showOnline = true)
    {
        parent::__construct($list, $itemCount, $usersOnPage, $showOnline);
    }

    public function getFields( $userIdList )
    {
        $fields = array();

        $qs = array();

        $qBdate = BOL_QuestionService::getInstance()->findQuestionByName('birthdate');

        if ( $qBdate !== null && $qBdate->onView )
            $qs[] = 'birthdate';

        $qSex = BOL_QuestionService::getInstance()->findQuestionByName('sex');

        if ( $qSex !== null && $qSex->onView )
            $qs[] = 'sex';

        $questionList = BOL_QuestionService::getInstance()->getQuestionData($userIdList, $qs);

        foreach ( $questionList as $uid => $question )
        {

            $fields[$uid] = array();

            $age = '';

            if ( !empty($question['birthdate']) )
            {
                $date = UTIL_DateTime::parseDate($question['birthdate'], UTIL_DateTime::MYSQL_DATETIME_DATE_FORMAT);

                $age = UTIL_DateTime::getAge($date['year'], $date['month'], $date['day']);
            }

            $sexValue = '';
            if ( !empty($question['sex']) )
            {
                $sex = $question['sex'];

                for ( $i = 0; $i < 31; $i++ )
                {
                    $val = pow(2, $i);
                    if ( (int) $sex & $val )
                    {
                        $sexValue .= BOL_QuestionService::getInstance()->getQuestionValueLang('sex', $val) . ', ';
                    }
                }

                if ( !empty($sexValue) )
                {
                    $sexValue = substr($sexValue, 0, -2);
                }
            }

            if ( !empty($sexValue) && !empty($age) )
            {
                $fields[$uid][] = array(
                    'label' => '',
                    'value' => $sexValue . ' ' . $age
                );
            }
        }

        return $fields;
    }
}