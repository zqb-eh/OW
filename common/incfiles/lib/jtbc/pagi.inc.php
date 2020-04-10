<?php
//******************************//
// JTBC Powered by jtbc.cn      //
//******************************//
namespace jtbc {
    class pagi
    {
        protected $dal;
        protected $mode = 'sql';
        public $dbLink = 'any';
        public $rslimit = 0;
        public $pagesize = 0;
        public $rscount = 0;
        public $pagenum = 0;
        public $pagetotal = 0;
        public $sqlstr = null;

        protected function getRsCount()
        {
            $rscount = 0;
            $sqlstr = "select count(*) as count from " . base::getLRStr(base::getLRStr($this->sqlstr, 'from', 'rightr'), 'order by', 'leftr');
            $rs = dal::selectBySQL($sqlstr, $this->dbLink);
            if (is_array($rs)) $rscount = base::getNum($rs['count'], 0);
            return $rscount;
        }

        public function getDataAry($argPageNum, $argPageSize, $argSqlstr = null)
        {
            $dataAry = array();
            $this->pagenum = base::getNum($argPageNum, 0);
            $this->pagesize = base::getNum($argPageSize, 0);
            $this->sqlstr = $argSqlstr;
            if ($this->mode == 'sql') {
                $this->rscount = $this->getRsCount();
            } else if ($this->mode == 'dal') {
                $this->rscount = $this->dal->getRsCount();
            }
            if ($this->pagesize == 0) {
                $this->pagesize = 20;
            }
            if ($this->rslimit == 0) {
                $this->rslimit = $this->rscount;
            } else if ($this->rscount < $this->rslimit) {
                $this->rslimit = $this->rscount;
            }
            if ($this->rslimit % $this->pagesize == 0) {
                $this->pagetotal = floor($this->rslimit / $this->pagesize);
            } else {
                $this->pagetotal = floor($this->rslimit / $this->pagesize) + 1;
            }
            if ($this->pagenum == 0) {
                $this->pagenum = 1;
            }
            $pagesize = $this->pagesize;
            $rslimit = $this->pagesize * $this->pagenum;
            if ($rslimit > $this->rslimit) {
                $rslimit = $this->rslimit;
                $pagesize = $rslimit - ($this->pagesize * ($this->pagenum - 1));
            }
            if ($rslimit > 0 && $pagesize > 0) {
                if ($this->mode == 'sql') {
                    $sqlstr = $this->sqlstr;
                    if (!is_null($sqlstr)) {
                        $sqlstr .= ' limit ' . ($rslimit - $pagesize) . ',' . $pagesize;
                        $dataAry = dal::selectAllBySQL($sqlstr, $this->dbLink);
                    }
                } else if ($this->mode == 'dal') {
                    $this->dal->limit(($rslimit - $pagesize), $pagesize);
                    $dataAry = $this->dal->selectAll();
                }
            }
            return $dataAry;
        }

        public function getVars()
        {
            $var = array();
            $var['-pagi-rslimit'] = $this->rslimit;
            $var['-pagi-pagesize'] = $this->pagesize;
            $var['-pagi-rscount'] = $this->rscount;
            $var['-pagi-pagenum'] = $this->pagenum;
            $var['-pagi-pagetotal'] = $this->pagetotal;
            return $var;
        }

        public static function pagi($argNum1, $argNum2, $argBaseLink, $argTplId = 'pagi-1', $argPagiId = 'pagi', $argPagiLen = 5)
        {
            $tmpstr = '';
            $vlNum = 0;
            $num1 = base::getNum($argNum1, 0);
            $num2 = base::getNum($argNum2, 0);
            $pagilen = base::getNum($argPagiLen, 5);
            $baseLink = $argBaseLink;
            $tplId = $argTplId;
            $pagiId = $argPagiId;
            if (is_numeric(strpos($pagiId, 'pagi-ct'))) $vlNum = 1;
            if ($num2 > $vlNum) {
                if (strpos($tplId, '.')) $tmpstr = tpl::take($tplId, 'tpl');
                else $tmpstr = tpl::take('global.config.' . $tplId, 'tpl');
                $tpl = new tpl($tmpstr);
                $loopString = $tpl->getLoopString('{@}');
                if ($num1 < 1) $num1 = 1;
                if ($num1 > $num2) $num1 = $num2;
                $num1c = floor($num1 - floor($pagilen / 2));
                if ($num1c < 1) $num1c = 1;
                $num1s = $num1c + $pagilen - 1;
                if ($num1s > $num2) $num1s = $num2;
                if ($num1c <= $num1s) {
                    if (($num1s - $num1c) < ($pagilen - 1)) {
                        $num1c = $num1c - (($pagilen - 1) - ($num1s - $num1c));
                        if ($num1c < 1) $num1c = 1;
                    }
                    for ($ti = $num1c; $ti <= $num1s; $ti++) {
                        $loopLineString = $loopString;
                        $loopLineString = str_replace('{$-num}', $ti, $loopLineString);
                        $loopLineString = str_replace('{$-link}', base::htmlEncode(str_replace('[~page]', $ti, $baseLink)), $loopLineString);
                        if ($ti != $num1) $loopLineString = str_replace('{$-class}', '', $loopLineString);
                        else $loopLineString = str_replace('{$-class}', 'on', $loopLineString);
                        $tpl->insertLoopLine($loopLineString);
                    }
                }
                $tmpstr = $tpl->getTpl();
                $tmpstr = str_replace('{$-page1}', $num1, $tmpstr);
                $tmpstr = str_replace('{$-page2}', $num2, $tmpstr);
                $tmpstr = str_replace('{$-firstpagelink}', base::htmlEncode(str_replace('[~page]', '1', $baseLink)), $tmpstr);
                $tmpstr = str_replace('{$-lastpagelink}', base::htmlEncode(str_replace('[~page]', $num2, $baseLink)), $tmpstr);
                $tmpstr = str_replace('{$-pagiid}', base::htmlEncode($pagiId), $tmpstr);
                $tmpstr = str_replace('{$-baselink}', base::htmlEncode($baseLink), $tmpstr);
                $tmpstr = str_replace('{$-prev-page-num}', ($num1 == 1 ? $num1 : ($num1 - 1)), $tmpstr);
                $tmpstr = str_replace('{$-next-page-num}', ($num1 == $num1s ? $num1 : ($num1 + 1)), $tmpstr);
                $tmpstr = tpl::parse($tmpstr);
            }
            return $tmpstr;
        }

        function __construct($argDal = null)
        {
            $dal = $argDal;
            if ($dal instanceof dal) {
                $this->mode = 'dal';
                $this->dal = $dal;
            }
        }
    }
}
//******************************//
// JTBC Powered by jtbc.cn      //
//******************************//
?>