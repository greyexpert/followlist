<?php

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