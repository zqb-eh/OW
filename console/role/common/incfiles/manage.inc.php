<?php
namespace jtbc;
class ui extends console\page {
  use console\fragment\util {
    doActionBatch as public moduleActionBatch;
    doActionDelete as public moduleActionDelete;
  }
  public static $batch = array('delete');

  protected static function ppGetPopedomJson($argPopedom)
  {
    $popedomJson = '';
    $popedom = $argPopedom;
    $popedomJsonArray = array();
    if (!base::isEmpty($popedom))
    {
      $popedomArray = explode('|', $popedom);
      foreach ($popedomArray as $key => $val)
      {
        if (!base::isEmpty($val))
        {
          $valArray = explode(':', $val);
          if (count($valArray) == 3)
          {
            $name = $valArray[0];
            $segment = $valArray[1];
            $category = $valArray[2];
            if (!base::isEmpty($segment)) $segment = base::getLRStr($segment, ',', 'leftr');
            if (!base::isEmpty($category)) $category = base::getLRStr($category, ',', 'leftr');
            $popedomJsonArray[$name] = array();
            $popedomJsonArray[$name]['segment'] = $segment;
            if (!base::isEmpty($category)) $popedomJsonArray[$name]['category'] = $category;
          }
        }
      }
      $popedomJson = json_encode($popedomJsonArray);
    }
    return $popedomJson;
  }

  protected static function ppGetSelectPopedomHTML($argPre = '', $argPopedom = '')
  {
    $has = false;
    $pre = $argPre;
    $popedom = $argPopedom;
    $popedomArray = array();
    $folder = route::getFolderByGuide();
    $folderAry = explode('|+|', $folder);
    $categoryAry = universal\category::getAllGenre();
    $tmpstr = tpl::take('manage.part-select-popedom', 'tpl');
    $tpl = new tpl($tmpstr);
    $loopString = $tpl -> getLoopString('{@}');
    if (!base::isEmpty($popedom)) $popedomArray = json_decode($popedom, true);
    foreach($folderAry as $key => $val)
    {
      if (!base::isEmpty($val))
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
          $checked = '';
          $guide = json_decode(tpl::take('global.' . $val . ':guide.guide', 'cfg'), true);
          $guidePopedom = tpl::take('global.' . $val . ':guide.popedom', 'cfg');
          $chindMenu = self::ppGetSelectPopedomHTML($pre . $myval . '/', $popedom);
          if (array_key_exists($val, $popedomArray)) $checked = ' checked="checked"';
          $loopLineString = $loopString;
          $loopLineString = str_replace('{$genre}', base::htmlEncode($val), $loopLineString);
          $loopLineString = str_replace('{$text}', base::htmlEncode($guide['text']), $loopLineString);
          $loopLineString = str_replace('{$-level}', base::htmlEncode(substr_count($val, '/') + 1), $loopLineString);
          $loopLineString = str_replace('{$-checked}', $checked, $loopLineString);
          if (base::isEmpty($guidePopedom)) $loopLineString = str_replace('{$-popedom}', '', $loopLineString);
          else
          {
            $popedomSelect = '';
            $guidePopedomArray = explode(',', $guidePopedom);
            foreach ($guidePopedomArray as $pkey => $pval)
            {
              $checkedp = '';
              if (array_key_exists($val, $popedomArray) && is_array($popedomArray[$val]))
              {
                if (base::checkInstr(@$popedomArray[$val]['segment'], $pval, ',')) $checkedp = ' checked="checked"';
              }
              $popedomSelect .= tpl::take('manage.part-select-popedom-option', 'tpl');
              $popedomSelect = str_replace('{$genre}', base::htmlEncode($val), $popedomSelect);
              $popedomSelect = str_replace('{$popedom}', base::htmlEncode($pval), $popedomSelect);
              $popedomSelect = str_replace('{$text}', base::htmlEncode(tpl::take('::console.text-popedom-' . $pval, 'lng')), $popedomSelect);
              $popedomSelect = str_replace('{$-checked}', $checkedp, $popedomSelect);
            }
            $loopLineString = str_replace('{$-popedom}', $popedomSelect, $loopLineString);
          }
          if (!in_array($val, $categoryAry) || !universal\category::isValidGenre($val)) $loopLineString = str_replace('{$-category}', '', $loopLineString);
          else
          {
            $categoryValue = '';
            if (array_key_exists($val, $popedomArray) && is_array($popedomArray[$val])) $categoryValue = @$popedomArray[$val]['category'];
            $loopLineString = str_replace('{$-category}', tpl::take('manage.part-select-popedom-category', 'tpl', 1, array('category' => base::htmlEncode($categoryValue))), $loopLineString);
          }
          $loopLineString = str_replace('{$-child}', $chindMenu, $loopLineString);
          $tpl -> insertLoopLine($loopLineString);
        }
      }
    }
    $tmpstr = $tpl -> getTpl();
    if ($has == false) $tmpstr = '';
    return $tmpstr;
  }

  public static function ppGetSelectCategoryHTML($argGenre, $argLang)
  {
    $genre = $argGenre;
    $lang = base::getNum($argLang, 0);
    $tmpstr = tpl::take('manage.part-select-category-li', 'tpl');
    $prefix = universal\category::getPrefix();
    $categoryAry = universal\category::getCategoryAryByGenre($genre, $lang);
    $getCategoryChild = function($argFid) use ($prefix, $categoryAry, &$getCategoryChild)
    {
      $afid = base::getNum($argFid, 0);
      $tmpstr = tpl::take('manage.part-select-category-dd', 'tpl');
      $tpl = new tpl($tmpstr);
      $loopString = $tpl -> getLoopString('{@}');
      foreach ($categoryAry as $myKey => $myVal)
      {
        if (is_array($myVal))
        {
          $rsid = base::getNum($myVal[$prefix . 'id'], 0);
          $rsfid = base::getNum($myVal[$prefix . 'fid'], -1);
          if ($rsfid == $afid)
          {
            $loopLineString = $loopString;
            foreach ($myVal as $key => $val)
            {
              $key = base::getLRStr($key, '_', 'rightr');
              $loopLineString = str_replace('{$' . $key . '}', base::htmlEncode($val), $loopLineString);
            }
            $loopLineString = str_replace('{$-child}', $getCategoryChild($rsid), $loopLineString);
            $tpl -> insertLoopLine($loopLineString);
          }
        }
      }
      $tmpstr = $tpl -> getTpl();
      $tmpstr = tpl::parse($tmpstr);
      return $tmpstr;
    };
    $tpl = new tpl($tmpstr);
    $loopString = $tpl -> getLoopString('{@}');
    foreach ($categoryAry as $myKey => $myVal)
    {
      if (is_array($myVal))
      {
        $rsid = base::getNum($myVal[$prefix . 'id'], 0);
        $rsfid = base::getNum($myVal[$prefix . 'fid'], -1);
        if ($rsfid == 0)
        {
          $loopLineString = $loopString;
          foreach ($myVal as $key => $val)
          {
            $key = base::getLRStr($key, '_', 'rightr');
            $loopLineString = str_replace('{$' . $key . '}', base::htmlEncode($val), $loopLineString);
          }
          $loopLineString = str_replace('{$-child}', $getCategoryChild($rsid), $loopLineString);
          $tpl -> insertLoopLine($loopLineString);
        }
      }
    }
    $tmpstr = $tpl -> getTpl();
    $tmpstr = tpl::parse($tmpstr);
    return $tmpstr;
  }

  public static function moduleAdd()
  {
    $status = 1;
    $tmpstr = '';
    $account = self::account();
    if ($account -> checkCurrentGenrePopedom('add'))
    {
      $vars['-select-popedom-html'] = self::ppGetSelectPopedomHTML();
      $tmpstr = tpl::takeAndAssign('manage.add', null, null, $vars);
      $tmpstr = $account -> replaceAccountTag($tmpstr);
    }
    $tmpstr = self::formatResult($status, $tmpstr);
    return $tmpstr;
  }

  public static function moduleEdit()
  {
    $status = 1;
    $tmpstr = '';
    $id = base::getNum(request::get('id'), 0);
    $account = self::account();
    if ($account -> checkCurrentGenrePopedom('edit'))
    {
      $dal = new dal();
      $dal -> id = $id;
      $rs = $dal -> select();
      if (is_array($rs))
      {
        $rsPopedom = base::getString($dal -> val('popedom'));
        $vars['-select-popedom-html'] = self::ppGetSelectPopedomHTML('', $rsPopedom);
        $tmpstr = tpl::takeAndAssign('manage.edit', $rs, null, $vars);
        $tmpstr = $account -> replaceAccountTag($tmpstr);
      }
    }
    $tmpstr = self::formatResult($status, $tmpstr);
    return $tmpstr;
  }

  public static function moduleList()
  {
    $status = 1;
    $tmpstr = '';
    $page = base::getNum(request::get('page'), 0);
    $pagesize = base::getNum(tpl::take('config.pagesize', 'cfg'), 0);
    $account = self::account();
    $batchAry = $account -> getCurrentGenreMySegmentAry(self::$batch);
    $variable['-batch-list'] = implode(',', $batchAry);
    $variable['-batch-show'] = empty($batchAry) ? 0 : 1;
    $dal = new dal();
    $dal -> orderBy('time', 'desc');
    $pagi = new pagi($dal);
    $rsAry = $pagi -> getDataAry($page, $pagesize);
    $variable = array_merge($variable, $pagi -> getVars());
    $tmpstr = tpl::takeAndAssign('manage.list', $rsAry, $variable);
    $tmpstr = $account -> replaceAccountTag($tmpstr);
    $tmpstr = self::formatResult($status, $tmpstr);
    return $tmpstr;
  }

  public static function moduleCategory()
  {
    $status = 1;
    $tmpstr = '';
    $genre = base::getString(request::get('genre'));
    $account = self::account();
    if ($account -> checkCurrentGenrePopedom('add') || $account -> checkCurrentGenrePopedom('edit'))
    {
      $langAry = tpl::take('::sel_lang.*', 'lng');
      $tmpstr = tpl::take('manage.category', 'tpl');
      $tpl = new tpl($tmpstr);
      $loopString = $tpl -> getLoopString('{@}');
      foreach ($langAry as $key => $val)
      {
        $loopLineString = $loopString;
        $loopLineString = str_replace('{$key}', base::htmlEncode($key), $loopLineString);
        $loopLineString = str_replace('{$val}', base::htmlEncode($val), $loopLineString);
        $loopLineString = str_replace('{$-select-category-html}', self::ppGetSelectCategoryHTML($genre, $key), $loopLineString);
        $tpl -> insertLoopLine($loopLineString);
      }
      $tmpstr = $tpl -> getTpl();
      $tmpstr = tpl::parse($tmpstr);
    }
    $tmpstr = self::formatResult($status, $tmpstr);
    return $tmpstr;
  }

  public static function moduleActionAdd()
  {
    $status = 0;
    $message = '';
    $error = array();
    $account = self::account();
    $popedom = request::getPost('popedom');
    $popedomJson = self::ppGetPopedomJson($popedom);
    if (!$account -> checkCurrentGenrePopedom('add'))
    {
      array_push($error, tpl::take('::console.text-tips-error-403', 'lng'));
    }
    else
    {
      auto::pushAutoRequestErrorByTable($error);
      if (count($error) == 0)
      {
        $preset = array();
        $preset['popedom'] = $popedomJson;
        $preset['time'] = base::getDateTime();
        $re = auto::autoInsertByRequest($preset);
        if (is_numeric($re))
        {
          $status = 1;
          $id = auto::$lastInsertId;
          $account -> creatCurrentGenreLog('manage.log-add-1', array('id' => $id));
        }
        else array_push($error, tpl::take('::console.text-tips-error-others', 'lng'));
      }
    }
    if (!empty($error)) $message = implode('|', $error);
    $tmpstr = self::formatMsgResult($status, $message);
    return $tmpstr;
  }

  public static function moduleActionEdit()
  {
    $status = 0;
    $message = '';
    $error = array();
    $account = self::account();
    $id = base::getNum(request::get('id'), 0);
    $popedom = request::getPost('popedom');
    $popedomJson = self::ppGetPopedomJson($popedom);
    if (!$account -> checkCurrentGenrePopedom('edit'))
    {
      array_push($error, tpl::take('::console.text-tips-error-403', 'lng'));
    }
    else
    {
      auto::pushAutoRequestErrorByTable($error);
      if (count($error) == 0)
      {
        $preset = array();
        $preset['popedom'] = $popedomJson;
        $re = auto::autoUpdateByRequest($id, $preset);
        if (is_numeric($re))
        {
          $status = 1;
          $message = tpl::take('manage.text-tips-edit-done', 'lng');
          $account -> creatCurrentGenreLog('manage.log-edit-1', array('id' => $id));
        }
        else array_push($error, tpl::take('::console.text-tips-error-others', 'lng'));
      }
    }
    if (!empty($error)) $message = implode('|', $error);
    $tmpstr = self::formatMsgResult($status, $message);
    return $tmpstr;
  }
}
?>