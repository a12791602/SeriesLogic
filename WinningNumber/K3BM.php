<?php
/**
 * Created by PhpStorm.
 * author: Harris
 * Date: 8/10/2019
 * Time: 4:29 PM
 */

namespace App\models\Game\Lottery\Logics\SeriesLogic\WinningNumber;


use App\Lib\Game\DigitalNumber;

trait K3BM
{
    /**
     * 分析中奖号码
     * 十一选五乐透彩系
     * BigSmallOddEven
     * Enum
     * @param  string  $sWinningNumber
     * @return string | array
     */
    public function getWinningNumberK3($sWinningNumber)
    {
        //#############################[k3系列开始]#####################################
        switch ($this->wn_function) {
            case 'k3bsde': //getWnNumberK3bsde 快3大小单双的中奖号
                $iSum = DigitalNumber::getSum($sWinningNumber);
                if ($iSum < 3 || $iSum > 18) {
                    return '';
                }
                $sWnNumber = (int)($iSum >= 11); // 大小
                $sWnNumber .= $iSum % 2 + 2; // 单双
                $result = $sWnNumber;
                break;
            case 'k3combin'://getWnNumberK3combin 快3组选的中奖号
                $sWinningNumber = str_split($sWinningNumber, 1);
                sort($sWinningNumber);
                $sWinningNumber = implode($sWinningNumber);
                $result = $this->checkSpanK3($sWinningNumber) ? $sWinningNumber : '';
                break;
            case 'k3contain'://getWnNumberLottoEqual 定位胆
                $sWinningNumber = str_split($sWinningNumber, 1);
                sort($sWinningNumber);
                $result = implode($sWinningNumber);
                break;
            default:
                $result = false;
            //#############################[k3系列结束]#####################################
        }
        return $result; //返回合适的计算中奖号码的方法
    }

    /**
     * 检查跨度是否合法
     * @param $sNumber
     * @return bool
     */
    private function checkSpanK3(& $sNumber): bool
    {
        if (!is_null($this->span)) {
            $aDigitals = str_split($sNumber, 1);
            try {
                if ($this->min_span && (max($aDigitals) - min($aDigitals)) === $this->span) {
                    $aSpan = [];
                    $iMax = count($aDigitals);
                    for ($i = 1; $i < $iMax; $i++) {
                        $aSpan[] = abs($aDigitals[$i] - $aDigitals[$i - 1]);
                    }
                    min($aSpan) === $this->min_span or $sNumber = '';
                } else {
                    $sNumber = '';
                }
            } catch (Exception $e) {
                $sNumber = '';
            }
        }
        return $sNumber ? true : false;
    }
}