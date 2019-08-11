<?php
/**
 * Created by PhpStorm.
 * author: Harris
 * Date: 8/10/2019
 * Time: 4:22 PM
 */

namespace App\models\Game\Lottery\Logics\SeriesLogic\WinningNumber;

trait Pk10BM
{
    /**
     * 分析中奖号码
     * Pk10系列
     * Pkconstituted
     * Pkdxds
     * Pkqual
     * Pksumsum
     * @param  string  $sWinningNumber
     * @return string | array
     */
    public function getWinningNumberPk10($sWinningNumber)
    {
        //#############################[Pk10系列开始]#####################################
        switch ($this->wn_function) {
            case 'Pkconstituted'://getWnNumberPkconstituted 获取不定位中奖号码
            case 'Pkqual'://获取直选的中奖号码 直选单式 直选复式 定位胆1星 定位胆10星 龙虎 getWnNumberPkqual
                $winNumber = implode($this->getPk10WinNumber($sWinningNumber, 1));
                $result = $winNumber;
                break;
            case 'Pkdxds'://大小单双 中奖号码 getWnNumberPkdxds
                $validNums = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
                $headSum = array_sum(array_slice($validNums, 0, $this->span));
                $tailSum = array_sum(array_slice($validNums, -$this->span));
                $midNum = intval(($headSum + $tailSum) / 2);
                $sWinningNumber = $this->getPk10WinNumber($sWinningNumber);
                $res = [];
                foreach ($sWinningNumber as $_number) {
                    $res[] = intval($_number > $midNum);       //大小
                    $res[] = $_number % 2 + 2; // 单双
                }
                $result = implode($this->splitChar, $res);
                break;
            case 'Pksumsum'://获取和值的中奖号码 getWnNumberPksumsum
                $result = $this->getPk10WinNumber($sWinningNumber);
                break;
            default:
                $result = false;
            //#############################[Pk10系列开始结束]#####################################
        }
        return $result; //返回合适的计算中奖号码的方法
    }

    /**
     * PK10 中奖号码 基础方法 1 - 10 变换到 0 - 9
     * @param $iFullWinningNumber
     * @param  int  $subtract
     * @return mixed
     */
    public function getPk10WinNumber($iFullWinningNumber, $subtract = 0)
    {
        if ($subtract !== 0) {
            foreach ($iFullWinningNumber as $i => $num) {
                $iFullWinningNumber[$i] -= $subtract;
            }
        }
        return $iFullWinningNumber;
    }
}