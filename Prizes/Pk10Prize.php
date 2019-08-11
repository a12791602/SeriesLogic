<?php
/**
 * Created by PhpStorm.
 * author: Harris
 * Date: 8/10/2019
 * Time: 4:35 PM
 */

namespace App\models\Game\Lottery\Logics\SeriesLogic\Prizes;

use App\Lib\Game\DigitalNumber;
use App\Lib\Game\Math;
use App\Models\Game\Lottery\LotterySeriesWay;
use Illuminate\Support\Facades\Log;

trait Pk10Prize
{
//##########################################################[Pk10系列开始 prize 计算]#########################################

    /**
     * Pk10 系列
     * prizeConstitutedPkconstituted
     * prizeEnumPkqual
     * prizePkBigSmallOddEvenPkdxds
     * prizePkconstitutedPkqual
     * prizePkDragonwithtigerPkqual
     * prizePkSeparatedConstitutedPkqual
     * prizeSumPksumsum
     * @param $sFunction
     * @param $sBetNumber
     * @param $sWnNumber
     * @param  LotterySeriesWay  $oSeriesWay
     * @return float|int
     */
    private function getPrizePk10($sFunction, $sBetNumber, $sWnNumber, LotterySeriesWay $oSeriesWay)
    {
        switch ($sFunction) {
            case 'prizeConstitutedPkconstituted'://不定位
                $winNumbers = str_split($sWnNumber);
                $aBetDigitals = array_unique(str_split($sBetNumber));
                $aBoth = array_intersect($winNumbers, $aBetDigitals);
                $iHitCount = count($aBoth);
                $result = $iHitCount >= $this->choose_count ? Math::combin($iHitCount, $this->choose_count) : 0;
                break;
            case 'prizeEnumPkqual'://pk10 单式直选 计奖 (前二 ～ 前十 ) 直选单式 定位胆1星 单式
                $aBetNumbers = explode($this->splitChar, $sBetNumber);
                $aKeys = array_keys($aBetNumbers, $sWnNumber);
                $result = count($aKeys);
                break;
            case 'prizePkBigSmallOddEvenPkdxds'://大小单双 计算中奖注数 中奖号码  1|3 投注号码　1|0|3|2 一星大小单双
                $aWnDigitals = explode($this->splitChar, $sWnNumber);// 中奖号码
                $aBetDigitals = explode($this->splitChar, $sBetNumber);// 投注号码
                $iWonCount = 0;
                foreach ($aWnDigitals as $i => $sWnDigitals) {
                    if (!in_array($sWnDigitals, $aBetDigitals, false)) {
                        continue;
                    }
                    ++$iWonCount;
                }
                $result = $iWonCount;
                break;
            case 'prizePkconstitutedPkqual'://定位胆 10星 定位胆1星 复式
                $betNumbers = explode($this->splitChar, $sBetNumber);
                $winNumbers = str_split($sWnNumber);
                $iCount = 0;
                for ($i = 0, $iMax = count($betNumbers); $i < $iMax; $i++) {
                    $bNumbers = str_split($betNumbers[$i]);
                    if (isset($winNumbers[$i]) && in_array((string)$winNumbers[$i], $bNumbers, true)) {
                        ++$iCount;
                    }
                }
                $result = $iCount;
                break;
            case 'prizePkDragonwithtigerPkqual'://龙虎　中奖注数
                $aWnDigitals = str_split($sWnNumber, 1);
                $aBetNumbers = explode($this->splitChar, $sBetNumber);
                $count = 0;
                foreach ($aBetNumbers as $row => $aBetNumber) {
                    if ($aBetNumber === '') {
                        continue;
                    }
                    $aBetNumber = str_split($aBetNumber);
                    foreach ($aBetNumber as $column) {
                        $dragon = $aWnDigitals[$row];
                        $tiger = $aWnDigitals[$column];
                        if ($dragon > $tiger) {
                            $count++;
                        }
                    }
                }
                $result = $count;
                break;
            case 'prizePkSeparatedConstitutedPkqual'://PK10 计算中奖注数 直选复式
                $winNumbers = str_split($sWnNumber);
                $p = [];
                foreach ($winNumbers as $iDigital) {
                    $p[] = '[\d]*'.$iDigital.'[\d]*';
                }
                $pattern = '/^'.implode('\|', $p).'$/';
                $result = preg_match($pattern, $sBetNumber);
                break;
            case 'prizeSumPksumsum'://PK10  计算和值中奖注 冠亚和 冠亚季和
                $iWinCount = 0;
                if ($sBetNumber) {
                    $sBetNumber = explode($this->splitChar, $sBetNumber);
                    [$iMin, $iMax] = explode('-', $this->valid_nums);
                    foreach ($sBetNumber as $betNumber) {
                        if (!is_numeric($betNumber) || ($betNumber < $iMin || $betNumber > $iMax)) {
                            continue;
                        }
                        if ($betNumber == $sWnNumber) {
                            ++$iWinCount;
                        }
                    }
                }
                $result = $iWinCount;
                break;
            default:
                Log::channel('issues')->info('需要添加Pk10系列方法:'.$sFunction.$oSeriesWay->toJson());
                $result = 0;
        }
        return $result;
    }
}