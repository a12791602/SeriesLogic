<?php
/**
 * Created by PhpStorm.
 * author: Harris
 * Date: 8/10/2019
 * Time: 4:22 PM
 */

namespace App\models\Game\Lottery\Logics\SeriesLogic\WinningNumber;

trait SslBM
{
    /**
     * 分析中奖号码
     * 时时乐系列
     * bsde
     * combin
     * contain
     * equal
     * @param  string  $sWinningNumber
     * @return string | array
     */
    public function getWinningNumberSsl($sWinningNumber)
    {
        //#############################[时时乐系列开始]#####################################
        switch ($this->wn_function) {
            case 'bsde'://getWnNumberBsde 返回大小单双中奖号码
                $validNums = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];//@todo 有可能需要参考一下之前的 series 的 validnum
                $minBigNumber = (int)(count($validNums) / 2);
                $aDigitals = str_split($sWinningNumber);
                $aWnNumbers = [];
                foreach ($aDigitals as $i => $iDigital) {
                    $sNumberOfPosition = (int)($iDigital >= $minBigNumber); // 大小
                    $sNumberOfPosition .= $iDigital % 2 + 2; // 单双
                    $aWnNumbers[$i] = $sNumberOfPosition;
                }
                $result = implode('|', $aWnNumbers);
                break;
            case 'combin'://检查组选单式号码是否合法 getWnNumberCombin checkCombinValid
                $aDigitals = str_split($sWinningNumber);
                $aDigitalCount = array_count_values($aDigitals);
                $iMaxRepeatCount = max($aDigitalCount);
                $iMinRepeatCount = min($aDigitalCount);
                $iUniqueCount = count($aDigitalCount);
                if ($iUniqueCount === $this->unique_count && $iMaxRepeatCount === $this->max_repeat_time && $iMinRepeatCount === $this->min_repeat_time) {
                    sort($aDigitals);
                    $result = implode($aDigitals);
                } else {
                    $result = false;
                }
                break;
            case 'contain'://返回不定位中奖号码 getWnNumberContain
                $aDigitals = str_split($sWinningNumber);
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
            case 'equal'://返回直选中奖号码 getWnNumberEqual
                if ($this->span !== null) {
                    $aDigitals = str_split($sWinningNumber);
                    $iSpan = max($aDigitals) - min($aDigitals);
                    if ((int)$iSpan === $this->span) {
                        if ($this->min_span) {
                            $iDigitalCount = count($aDigitals);
                            $aSpan = [];
                            for ($i = 1; $i < $iDigitalCount; $i++) {
                                $aSpan[] = abs($aDigitals[$i] - $aDigitals[$i++ - 1]);
                            }
                            $aDigitals[] = abs($aDigitals[0] - $aDigitals[$iDigitalCount - 1]);
                            min($aSpan) === $this->min_span or $sWinningNumber = '';
                        }
                    } else {
                        $sWinningNumber = '';
                    }
                }
                $result = $sWinningNumber;
                break;
            default:
                $result = false;
            //#############################[时时乐系列开始结束]#####################################
        }
        return $result; //返回合适的计算中奖号码的方法
    }
}
