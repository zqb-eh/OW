<?php
namespace jtbc;
class ui extends console\page {
  use universal\fragment\upload { doActionUpload as public moduleActionUpload; }

  public static function moduleEdit()
  {
    $status = 1;
    $tmpstr = '';
    $account = self::account();
    if ($account -> checkCurrentGenrePopedom())
    {
      $vars['-lang-text'] = $account -> getLangText();
      $tmpstr = tpl::takeAndAssign('manage.edit', null, null, $vars);
      $tmpstr = $account -> replaceAccountTag($tmpstr);
    }
    $tmpstr = self::formatResult($status, $tmpstr);
    return $tmpstr;
  }

  public static function moduleActionEdit()
  {
    $status = 0;
    $message = '';
    $error = array();
    $account = self::account();
    $id = base::getNum(request::get('id'), 0);
    $title = request::getPost('title');
    if (!$account -> checkCurrentGenrePopedom())
    {
      array_push($error, tpl::take('::console.text-tips-error-403', 'lng'));
    }
    else
    {
      if (base::isEmpty($title)) array_push($error, tpl::take('manage.text-tips-edit-error-1', 'lng'));
      if (count($error) == 0)
      {
        $langText = $account -> getLangText();
        $bool1 = tpl::bring('index.title', 'lng', request::getPost('title'), $langText);
        $bool2 = tpl::bring('index.content', 'lng', request::getPost('content'), $langText);
        $bool3 = tpl::bring('index.att', 'lng', request::getPost('att'), $langText);
        if ($bool1 && $bool2 && $bool3)
        {
          $status = 1;
          universal\upload::statusReset(self::getParam('genre'), 0);
          universal\upload::statusUpdate(self::getParam('genre'), 0, request::getPost('att'));
          $message = tpl::take('manage.text-tips-edit-done', 'lng');
          $account -> creatCurrentGenreLog('manage.log-edit-1');
        }
        else $message = tpl::take('manage.text-tips-edit-error-others', 'lng');
      }
    }
    if (!empty($error)) $message = implode('|', $error);
    $tmpstr = self::formatMsgResult($status, $message);
    return $tmpstr;
  }
}
?>