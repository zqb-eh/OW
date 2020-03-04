<?php
//******************************//
// JTBC Powered by jtbc.cn      //
//******************************//
namespace jtbc\console {
  use jtbc\auto;
  use jtbc\base;
  use jtbc\dal;
  use jtbc\request;
  use jtbc\route;
  use jtbc\tpl;
  class account
  {
    private $lang;
    public $currentGenre;
    public $cookiesUserName;
    public $cookiesAuthentication;
    public $myInfo;
    public $myRole;
    public $myPopedom;

    public function checkLogin()
    {
      $bool = false;
      if (is_array($this -> myInfo) && is_array($this -> myRole) && is_array($this -> myPopedom)) $bool = true;
      else
      {
        $cookiesUserName = $this -> cookiesUserName;
        $cookiesAuthentication = $this -> cookiesAuthentication;
        $bool = $this -> checkLoginInfo($cookiesUserName, 'null', $cookiesAuthentication);
      }
      return $bool;
    }

    public function checkLoginErrorMax($argUsername)
    {
      $bool = false;
      $username = $argUsername;
      if (!base::isEmpty($username))
      {
        $table = tpl::take(':/account:config.db_table', 'cfg');
        $prefix = tpl::take(':/account:config.db_prefix', 'cfg');
        $dal = new dal($table, $prefix);
        $dal -> username = $username;
        $rs = $dal -> select();
        if (is_array($rs))
        {
          $num = 0;
          $rsId = base::getNum($dal -> val('id'), 0);
          $todayDate = base::getNum(base::formatDate(base::getDateTime(), '10'), 0);
          $tableLogin = tpl::take(':/account:config.db_table_login', 'cfg');
          $prefixLogin = tpl::take(':/account:config.db_prefix_login', 'cfg');
          $dal = new dal($tableLogin, $prefixLogin);
          $dal -> status = 0;
          $dal -> date = $todayDate;
          $dal -> account_id = $rsId;
          $num = base::getNum($dal -> getRsCount());
          $numMax = base::getNum(tpl::take('::config.login-error-max', 'cfg'), 0);
          if ($numMax < $num) $bool = true;
        }
      }
      return $bool;
    }

    public function checkLoginInfo($argUsername, $argPassword, $argAuthentication = '')
    {
      $bool = false;
      $popedom = '';
      $username = $argUsername;
      $password = $argPassword;
      $authentication = $argAuthentication;
      if (!base::isEmpty($username) && !base::isEmpty($password))
      {
        $table = tpl::take(':/account:config.db_table', 'cfg');
        $prefix = tpl::take(':/account:config.db_prefix', 'cfg');
        $dal = new dal($table, $prefix);
        $dal -> username = $username;
        $dal -> lock = 0;
        $rs = $dal -> select();
        if (is_array($rs))
        {
          $rsId = base::getNum($dal -> val('id'), 0);
          $rsRole = base::getNum($dal -> val('role'), 0);
          $rsUsername = base::getString($dal -> val('username'));
          $rsPassword = base::getString($dal -> val('password'));
          if ($rsPassword == md5($password) || md5(WEBKEY . $rsPassword) == $authentication)
          {
            if (base::isEmpty($authentication))
            {
              $re = auto::autoUpdateByVars($rsId, array('lasttime' => base::getDateTime(), 'lastip' => request::getRemortIP()), $table, $prefix);
            }
            if ($rsRole == -1)
            {
              $rsr = array('super' => 1);
              $popedom = array('super' => 1);
            }
            else
            {
              $table = tpl::take(':/role:config.db_table', 'cfg');
              $prefix = tpl::take(':/role:config.db_prefix', 'cfg');
              $dal = new dal($table, $prefix);
              $dal -> id = $rsRole;
              $rsr = $dal -> select();
              if (is_array($rsr))
              {
                $rsPopedom = base::getString($dal -> val('popedom'));
                $popedom = json_decode($rsPopedom, true);
              }
              else
              {
                $rsr = array('super' => 0);
                $popedom = array('super' => 0);
              }
            }
            $this -> myInfo = $rs;
            $this -> myRole = $rsr;
            $this -> myPopedom = $popedom;
            $bool = true;
          }
          if (base::isEmpty($authentication))
          {
            $loginStatus = 0;
            if ($bool == true) $loginStatus = 1;
            $todayDate = base::getNum(base::formatDate(base::getDateTime(), '10'), 0);
            $tableLogin = tpl::take(':/account:config.db_table_login', 'cfg');
            $prefixLogin = tpl::take(':/account:config.db_prefix_login', 'cfg');
            $preset = array();
            $preset['account_id'] = $rsId;
            $preset['date'] = $todayDate;
            $preset['status'] = $loginStatus;
            $re = auto::autoInsertByVars($preset, $tableLogin, $prefixLogin);
          }
        }
      }
      return $bool;
    }

    public function checkIsSuper()
    {
      $bool = false;
      $popedom = $this -> myPopedom;
      if (base::getNum(@$popedom['super'], 0) == 1) $bool = true;
      return $bool;
    }

    public function checkPopedom($argGenre, $argSegment = '')
    {
      $bool = false;
      $genre = $argGenre;
      $segment = $argSegment;
      $popedom = $this -> myPopedom;
      if (is_array($popedom))
      {
        if ($this -> checkIsSuper()) $bool = true;
        else
        {
          if (array_key_exists($genre, $popedom))
          {
            if (base::isEmpty($segment)) $bool = true;
            else
            {
              $allSegment = $this -> getGenrePopedom($genre, 'segment');
              if (base::checkInstr($allSegment, $segment, ',')) $bool = true;
            }
          }
        }
      }
      return $bool;
    }

    public function checkPopedomByCategory($argGenre, $argCategory = 0)
    {
      $bool = false;
      $genre = $argGenre;
      $category = base::getNum($argCategory, 0);
      $popedom = $this -> myPopedom;
      if (is_array($popedom))
      {
        if ($this -> checkIsSuper()) $bool = true;
        else
        {
          if (array_key_exists($genre, $popedom))
          {
            $allCategory = $this -> getGenrePopedom($genre, 'category');
            if (base::isEmpty($allCategory) || $allCategory == ',') $bool = true;
            else
            {
              if (base::checkInstr($allCategory, $category, ',')) $bool = true;
            }
          }
        }
      }
      return $bool;
    }

    public function checkCurrentGenrePopedom($argSegment = '')
    {
      $segment = $argSegment;
      $bool = $this -> checkPopedom($this -> currentGenre, $segment);
      return $bool;
    }

    public function checkCurrentGenrePopedomByCategory($argCategory = 0)
    {
      $category = base::getNum($argCategory, 0);
      $bool = $this -> checkPopedomByCategory($this -> currentGenre, $category);
      return $bool;
    }

    public function creatLog($argGenre, $argContent, $argUserIp)
    {
      $bool = false;
      $genre = $argGenre;
      $content = $argContent;
      $userip = $argUserIp;
      $accountId = $this -> getMyInfo('id');
      $table = tpl::take(':/log:config.db_table', 'cfg');
      $prefix = tpl::take(':/log:config.db_prefix', 'cfg');
      if (!base::isEmpty($table) && !base::isEmpty($content))
      {
        $preset = array();
        $preset['genre'] = $genre;
        $preset['content'] = $content;
        $preset['userip'] = $userip;
        $preset['account_id'] = $accountId;
        $preset['time'] = base::getDateTime();
        $re = auto::autoInsertByVars($preset, $table, $prefix);
        if (is_numeric($re)) $bool = true;
      }
      return $bool;
    }

    public function creatCurrentGenreLog($argCodeName, $argAry = null)
    {
      $codeName = $argCodeName;
      $ary = $argAry;
      $logString = tpl::take($codeName, 'lng');
      $logString = tpl::replaceOriginalTagByAry($logString, $ary);
      $bool = $this -> creatLog($this -> currentGenre, $logString, request::getRemortIP());
      return $bool;
    }

    public function getGenrePopedom($argGenre, $argItem)
    {
      $tmpstr = '';
      $genre = $argGenre;
      $item = $argItem;
      if (!base::isEmpty($genre) && !base::isEmpty($item))
      {
        if (!$this -> checkIsSuper())
        {
          $popedom = $this -> myPopedom;
          $tmpstr = @$popedom[$genre][$item];
        }
      }
      return $tmpstr;
    }

    public function getCurrentGenrePopedom($argItem)
    {
      $item = $argItem;
      $tmpstr = $this -> getGenrePopedom($this -> currentGenre, $item);
      return $tmpstr;
    }

    public function getCurrentGenreMySegmentAry($argOriAry)
    {
      $ary = array();
      $oriAry = $argOriAry;
      if (is_array($oriAry))
      {
        foreach ($oriAry as $key => $val)
        {
          if ($this -> checkCurrentGenrePopedom($val)) array_push($ary, $val);
        }
      }
      return $ary;
    }

    public function getMyConsoleMenu($argTPL1, $argTPL2, $argPre = '')
    {
      $tmpstr = '';
      $has = false;
      $tpl1 = $argTPL1;
      $tpl2 = $argTPL2;
      $pre = $argPre;
      $folder = route::getFolderByGuide();
      $folderAry = explode('|+|', $folder);
      $tmpstr = $tpl1;
      if (!base::isEmpty($pre)) $tmpstr = $tpl2;
      $tpl = new tpl($tmpstr);
      $loopString = $tpl -> getLoopString('{@}');
      foreach($folderAry as $key => $val)
      {
        if (!base::isEmpty($val))
        {
          if ($this -> checkPopedom($val))
          {
            $myval = $val;
            if (!base::isEmpty($pre))
            {
              $mypos = strpos($myval, $pre);
              if (!is_numeric($mypos) || $mypos != 0) $myval = '';
              else $myval = base::getLRStr($myval, $pre, 'rightr');
            }
            if (!base::isEmpty($myval) && !is_numeric(strpos($myval, '/')))
            {
              $has = true;
              $d = '';
              $guide = json_decode(tpl::take('global.' . $val . ':guide.guide', 'cfg'), true);
              $chindMenu = '';
              $chindMenu = $this -> getMyConsoleMenu($tpl1, $tpl2, $pre . $myval . '/');
              if (base::isEmpty($chindMenu)) $d = 'hide';
              $loopLineString = $loopString;
              $loopLineString = str_replace('{$genre}', base::htmlEncode($val), $loopLineString);
              $loopLineString = str_replace('{$text}', base::htmlEncode($guide['text']), $loopLineString);
              $loopLineString = str_replace('{$icon}', base::htmlEncode($guide['icon']), $loopLineString);
              $loopLineString = str_replace('{$link}', base::htmlEncode($guide['link']), $loopLineString);
              $loopLineString = str_replace('{$-level}', base::htmlEncode(substr_count($val, '/') + 1), $loopLineString);
              $loopLineString = str_replace('{$-d}', $d, $loopLineString);
              $loopLineString = str_replace('{$-child}', $chindMenu, $loopLineString);
              $tpl -> insertLoopLine($loopLineString);
            }
          }
        }
      }
      $tmpstr = $tpl -> getTpl();
      if ($has == false) $tmpstr = '';
      return $tmpstr;
    }

    public function getMyInfo($argField, $argMode = 'info')
    {
      $tmpstr = '';
      $mode = $argMode;
      $field = $argField;
      $info = $this -> myInfo;
      $role = $this -> myRole;
      if ($mode == 'info' && is_array($info)) $tmpstr = base::getString(@$info[tpl::take(':/account:config.db_prefix', 'cfg') . $field]);
      else if ($mode == 'role' && is_array($role)) $tmpstr = base::getString(@$role[tpl::take(':/role:config.db_prefix', 'cfg') . $field]);
      return $tmpstr;
    }

    public function getRoleTopicById($argId)
    {
      $tmpstr = 'NULL';
      $id = base::getNum($argId, 0);
      if ($id == -1) $tmpstr = tpl::take(':/role:manage.text-super', 'lng');
      else
      {
        $table = tpl::take(':/role:config.db_table', 'cfg');
        $prefix = tpl::take(':/role:config.db_prefix', 'cfg');
        $dal = new dal($table, $prefix);
        $dal -> id = $id;
        $rs = $dal -> select();
        if (is_array($rs)) $tmpstr = base::getString($dal -> val('topic'));
      }
      return $tmpstr;
    }

    public function getLang()
    {
      $lang = $this -> lang;
      if (!$this -> checkIsSuper())
      {
        $myRoleLang = base::getString($this -> getMyInfo('lang', 'role'), 0);
        if (!base::checkInstr($myRoleLang, $lang))
        {
          $lang = base::getNum(base::getLRStr($myRoleLang, ',', 'left'), 0);
          $this -> setLang($lang);
        }
      }
      return $lang;
    }

    public function getLangText()
    {
      $lang = $this -> getLang();
      $langText = tpl::take('global.config.lang-' . $lang, 'cfg');
      return $langText;
    }

    public function logout()
    {
      setcookie(APPNAME . 'console[username]', '', 0, COOKIESPATH, null, null, true);
      setcookie(APPNAME . 'console[authentication]', '', 0, COOKIESPATH, null, null, true);
    }

    public function modifyPassword($argPassword, $argNewPassword)
    {
      $bool = false;
      $password = $argPassword;
      $newpassword = $argNewPassword;
      if (md5($password) == $this -> getMyInfo('password'))
      {
        $accountId = $this -> getMyInfo('id');
        $table = tpl::take(':/account:config.db_table', 'cfg');
        $prefix = tpl::take(':/account:config.db_prefix', 'cfg');
        $dal = new dal($table, $prefix);
        $dal -> id = $accountId;
        $re = $dal -> update(array('password' => md5($newpassword)));
        if (is_numeric($re))
        {
          $bool = true;
          setcookie(APPNAME . 'console[authentication]', md5(WEBKEY . md5($newpassword)), 0, COOKIESPATH, null, null, true);
        }
      }
      return $bool;
    }

    public function replaceAccountTag($argHTML)
    {
      $html = $argHTML;
      $genre = $this -> currentGenre;
      $baseNavHTML = tpl::take('::manage.part-nav', 'tpl');
      $baseNavArrowHTML = tpl::take('::manage.part-nav-arrow', 'tpl');
      $accountNavHTML = $baseNavHTML;
      $accountNavHTML = str_replace('{$genre}', base::htmlEncode('|self|'), $accountNavHTML);
      $accountNavHTML = str_replace('{$text}', base::htmlEncode(tpl::take('::manage.dashbord', 'lng')), $accountNavHTML);
      $accountNavHTML = str_replace('{$link}', base::htmlEncode(tpl::take('::guide.def', 'cfg')), $accountNavHTML);
      $accountNavHTML = str_replace('{$-class}', '', $accountNavHTML);
      if ($genre != CONSOLEDIR)
      {
        $baseGenre = '';
        $genreAry = explode('/', $genre);
        foreach ($genreAry as $key => $val)
        {
          if (!base::isEmpty($val))
          {
            $myClass = '';
            $currentGenre = $baseGenre . $val;
            $guide = json_decode(tpl::take('global.' . $currentGenre . ':guide.guide', 'cfg'), true);
            if (base::isEmpty($guide['link'])) $myClass = 'none';
            $accountNavHTML .= $baseNavArrowHTML . $baseNavHTML;
            $accountNavHTML = str_replace('{$genre}', base::htmlEncode($currentGenre), $accountNavHTML);
            $accountNavHTML = str_replace('{$text}', base::htmlEncode($guide['text']), $accountNavHTML);
            $accountNavHTML = str_replace('{$link}', base::htmlEncode($guide['link']), $accountNavHTML);
            $accountNavHTML = str_replace('{$-class}', base::htmlEncode($myClass), $accountNavHTML);
            $baseGenre = $currentGenre . '/';
          }
        }
      }
      $html = str_replace('{$-account-nav}', $accountNavHTML, $html);
      $guidePopedom = tpl::take('guide.popedom', 'cfg');
      if (!base::isEmpty($guidePopedom))
      {
        $guidePopedomArray = explode(',', $guidePopedom);
        foreach ($guidePopedomArray as $key => $val)
        {
          if (!base::isEmpty($val))
          {
            if ($this -> checkPopedom($genre, $val)) $html = str_replace('{$-account-popedom-' . $val . '}', 1, $html);
            else $html = str_replace('{$-account-popedom-' . $val . '}', 0, $html);
          }
        }
      }
      return $html;
    }

    public function setLang($argVal)
    {
      $bool = false;
      $val = base::getNum($argVal, 0);
      if ($this -> lang != $val)
      {
        if (!base::isEmpty(tpl::take('::sel_lang.' . $val, 'lng')))
        {
          $this -> lang = $val;
          setcookie(APPNAME . 'console[lang]', $val, time() + 31536000, COOKIESPATH, null, null, true);
          $bool = true;
        }
      }
      return $bool;
    }

    function __construct($argCurrentGenre = null)
    {
      $this -> currentGenre = $argCurrentGenre;
      $this -> cookiesUserName = request::getCookie('console', 'username');
      $this -> cookiesAuthentication = request::getCookie('console', 'authentication');
      $this -> lang = base::getNum(request::getCookie('console', 'lang'), 0);
    }
  }
}
//******************************//
// JTBC Powered by jtbc.cn      //
//******************************//
?>