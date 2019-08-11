<?php
/**
 * Created by PhpStorm.
 * author: Harris
 * Date: 8/10/2019
 * Time: 4:27 PM
 */

namespace App\models\Game\Lottery\Logics\SeriesLogic\WinningNumber;


trait LottoBM
{
    /**
     * 分析中奖号码
     * 十一选五乐透彩系
     * LottoContain
     * LottoCombin
     * LottoEqual
     * LottoOddEven
     * LottoMiddle
     * @param  string  $sWinningNumber
     * @return string | array
     */
    public function getWinningNumberLotto($sWinningNumber)
    {
        //#############################[十一选五乐透系列]#####################################
        switch ($this->wn_function) {
            case 'LottoContain': //getWnNumberLottoContain 11选5不定位中奖号码
                $aDigitals = explode($this->splitCharInArea, $sWinningNumber);
                $aDigitalCount = array_count_values($aDigitals);
                $aUniqueDigitals = array_keys($aDigitalCount);
                $aWnNumber = [];
                if ($this->min_repeat_time) {
                    if (count($aDigitalCount) >= $this->choose_count && max($aDigitalCount) >= $this->min_repeat_time) {
                        foreach ($aDigitalCount as $iDigital => $iCount) {
                            $iCount < $this->min_repeat_time or $aWnNumber[] = $iDigital;
                        }
                    }
                } else {
                    (count($aDigitalCount) < $this->choose_count) or $aWnNumber = $aUniqueDigitals;
                }
                $result = $aWnNumber ?: false;
                break;
            case 'LottoCombin'://getWnNumberLottoCombin 任选六中五
            case 'LottoEqual'://getWnNumberLottoEqual 定位胆
                $result = $sWinningNumber;
                break;
            case 'LottoOddEven'://getWnNumberLottoOddEven 返回定单双
                $aBalls = explode($this->splitCharInArea, $sWinningNumber);
                $iOddCount = 0;
                foreach ($aBalls as $iBall) {
                    $iOddCount += $iBall % 2;
                }
                $result = $iOddCount;
                break;
            case 'LottoMiddle'://getWnNumberLottoMiddle 猜中位
                $aBalls = explode($this->splitCharInArea, $sWinningNumber);
                sort($aBalls);
                $result = $aBalls[2];
                break;
            default:
                $result = false;
            //#############################[十一选五乐透系列结束]#####################################
        }
        return $result; //返回合适的计算中奖号码的方法
    }
}