<?php
/**
 * Created by PhpStorm.
 * author: Harris
 * Date: 8/10/2019
 * Time: 4:37 PM
 */

namespace App\models\Game\Lottery\Logics\SeriesLogic\Prizes;


use App\Lib\Game\Math;
use App\Models\Game\Lottery\LotterySeriesWay;
use Illuminate\Support\Facades\Log;

trait LottoPrize
{
//##########################################################[十一选五乐透系列 prize 计算]#########################################

    /**
     * 十一选五系列计算中奖
     * prizeLottoEqualLottoContain
     * prizeLottoEqualLottoCombin
     * prizeLottoEqualLottoEqual
     * prizeLottoConstitutedLottoContain
     * prizeLottoConstitutedLottoCombin
     * prizeLottoMultiOneLottoEqual
     * prizeLottoConstitutedLottoOddEven
     * prizeLottoConstitutedLottoMiddle
     * prizeLottoSeparatedConstitutedLottoEqual
     * prizeLottoNecessaryConstitutedLottoContain
     * prizeLottoNecessaryConstitutedLottoCombin
     * @param $sFunction
     * @param $sBetNumber
     * @param $sWnNumber
     * @param  LotterySeriesWay  $oSeriesWay
     * @return float|int
     */
    private function getPrizeLotto($sFunction, $sBetNumber, $sWnNumber, LotterySeriesWay $oSeriesWay)
    {

        switch ($sFunction) {
            case 'prizeLottoEqualLottoContain'://计算11选5任选1至任选5单式的中奖注数
                sort($sWnNumber);
                $aBets = explode($this->splitChar, $sBetNumber);
                $iCount = 0;
                foreach ($aBets as $sBet) {
                    $aBetBalls = explode($this->splitCharInArea, $sBet);
                    $aHits = array_intersect($aBetBalls, $sWnNumber);
                    $iCount += (int)(count($aHits) === $this->choose_count);
                }
                $result = $iCount;
                break;
            case 'prizeLottoEqualLottoCombin'://计算11选5任选6中五至任选8中5单式和组选单式的中奖注数
                $aWnBalls = explode($this->splitCharInArea, $sWnNumber);
                $aBets = explode($this->splitChar, $sBetNumber);
                $iCount = 0;
                foreach ($aBets as $sBet) {
                    $aTmpBalls = explode($this->splitCharInArea, $sBet);
                    $aHitBalls = array_intersect($aTmpBalls, $aWnBalls);
                    if ($bWon = (count($aHitBalls) === $this->wn_length)) {
                        $iCount++;
                    }
                }
                $result = $iCount;
                break;
            case 'prizeLottoEqualLottoEqual'://计算11选5直选单式的中奖注数
                $aBets = explode($this->splitChar, $sBetNumber);
                $result = (int)in_array($sWnNumber, $aBets, false);
                break;
            case 'prizeLottoConstitutedLottoContain': //计算11选5任选一至五复式的中奖注数
                $iHitCount = $this->_getHitNumbersOfLotto($sBetNumber, $sWnNumber, $iBetBallCount);
                $result = Math::combin($iHitCount, $this->choose_count);
                break;
            case 'prizeLottoConstitutedLottoCombin'://计算11选5任选五至八复式的中奖注数
                $iHitCount = $this->_getHitNumbersOfLotto($sBetNumber, $sWnNumber, $iBetBallCount);
                if ($iHitCount < $this->wn_length) {
                    $result = 0;
                    break;
                }
                $iNeedOtherBallCount = $this->buy_length - $this->wn_length;
                $iUnHitCount = $iBetBallCount - $iHitCount;
                $result = Math::combin($iUnHitCount, $iNeedOtherBallCount);
                break;
            case 'prizeLottoMultiOneLottoEqual': //计算11选5定位胆的中奖注数
            case 'prizeLottoConstitutedLottoOddEven'://计算11选5定单双的中奖数字
            case 'prizeLottoConstitutedLottoMiddle'://11选5猜中位的中奖注数
                $aBetBalls = explode($this->splitCharInArea, $sBetNumber);
                $result = (int)in_array($sWnNumber, $aBetBalls, false);
                break;
            case 'prizeLottoSeparatedConstitutedLottoEqual'://计算11选5任选五至八复式的中奖注数
                $aWnBalls = explode($this->splitCharInArea, $sWnNumber);
                $aBetBalls = explode($this->splitChar, $sBetNumber);
                $iHitPosCount = 0;
                if (count($aWnBalls) !== count($aBetBalls)) {
                    $result = 0;
                    break;
                }
                foreach ($aBetBalls as $i => $sBetNumberOfPos) {
                    $aBetBallsOfPos = explode($this->splitCharInArea, $sBetNumberOfPos);
                    if (!in_array($aWnBalls[$i], $aBetBallsOfPos, false)) {
                        break;
                    }
                    $iHitPosCount++;
                }
                $result = (int)($iHitPosCount === $this->wn_length);
                break;
            case 'prizeLottoNecessaryConstitutedLottoContain':
                [$sBetNecessaried, $sBetConstituted] = explode($this->splitChar, $sBetNumber);
                $aBetNecessaried = explode($this->splitCharInArea, $sBetNecessaried);
                $aHitNecessaried = array_intersect($aBetNecessaried, $sWnNumber);
                $iHitNessariedCount = count($aHitNecessaried);
                if ($iHitNessariedCount !== count($aBetNecessaried)) {
                    $result = 0;
                    break;
                }
                $iNeedOfNecessariedCount = $this->wn_length - $iHitNessariedCount;
                if ($iNeedOfNecessariedCount === 0) {
                    $result = 1;
                    break;
                }
                $aBetConstituted = explode($this->splitCharInArea, $sBetConstituted);
                $aHitConstituted = array_intersect($aBetConstituted, $sWnNumber);
                $iHitConstitutedCount = count($aHitConstituted);
                if ($iHitConstitutedCount < $iNeedOfNecessariedCount) {
                    $result = 0;
                    break;
                }
                $result = Math::combin($iHitConstitutedCount, $iNeedOfNecessariedCount);
                break;
            case 'prizeLottoNecessaryConstitutedLottoCombin':
                $aWnNumber = explode($this->splitCharInArea, $sWnNumber);
                [$sBetNecessaried, $sBetConstituted] = explode($this->splitChar, $sBetNumber);
                $aBetNecessaried = explode($this->splitCharInArea, $sBetNecessaried);
                $aHitNecessaried = array_intersect($aBetNecessaried, $aWnNumber);
                $iBetNecessariedCount = count($aBetNecessaried);
                $iHitNessariedCount = count($aHitNecessaried);
                $iNeedOfBetBallsCount = $this->buy_length - $iBetNecessariedCount;// 凑足一注投注码还需要的复式码个数
                $aBetConstituted = explode($this->splitCharInArea, $sBetConstituted);
                $iBetConstitutedCount = count($aBetConstituted);
                if ($iNeedOfBetBallsCount > $iBetConstitutedCount) {// 如果复式码个数不足, 则不中奖
                    $result = 0;
                    break;
                }
                $aHitConstituted = array_intersect($aBetConstituted, $aWnNumber);// 求出中得的复式码个数
                $iHitConstitutedCount = count($aHitConstituted);
                if ($iBetNecessariedCount + $iHitConstitutedCount > $this->buy_length) {// 如果胆码个数+中得的复式码个数,则不中奖
                    $result = 0;
                    break;
                }
                $iNonHitConstitutedCount = $iBetConstitutedCount - $iHitConstitutedCount;// 求出未中得的复式码个数
                if ($iHitConstitutedCount + $iHitNessariedCount < $this->wn_length) {
                    $result = 0;
                    break;
                }
                $iNeedNonHitCount = $iNeedOfBetBallsCount - $iHitConstitutedCount;
                $result = Math::combin($iNonHitConstitutedCount, $iNeedNonHitCount);
                break;
            default:
                Log::channel('issues')->info('需要添加十一选五系列方法:'.$sFunction.$oSeriesWay->toJson());
                $result = 0;
        }
        return $result;
    }

    /**
     * @param $sBetNumber
     * @param $sWnNumber
     * @param $iBetBallCount
     * @return int
     */
    private function _getHitNumbersOfLotto($sBetNumber, $sWnNumber, & $iBetBallCount): int
    {
        $aWnBalls = is_array($sWnNumber) ? $sWnNumber : explode($this->splitCharInArea, $sWnNumber);
        $aBetBalls = explode($this->splitCharInArea, $sBetNumber);
        $iBetBallCount = count($aBetBalls);
        return count(array_intersect($aBetBalls, $aWnBalls));
    }
}