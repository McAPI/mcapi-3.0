<?php

namespace App\Http\Controllers;

use App\Responses\BuycraftBanListing;
use App\Responses\BuycraftCommandOnlineQueueListing;
use App\Responses\BuycraftCommandQueueListing;
use App\Responses\BuycraftCouponListing;
use App\Responses\BuycraftCategoryListing;
use App\Responses\BuycraftGiftcardListing;
use App\Responses\BuycraftGiftcardShowCard;
use App\Responses\BuycraftInformation;
use App\Responses\BuycraftPaymentsList;
use App\Responses\BuycraftShowCoupon;
use Illuminate\Http\Request;

class BuycraftController extends Controller
{

    public function information(Request $request, string $secret)
    {
        $information = new BuycraftInformation($secret);
        $information->fetch($request->all());
        return $information;
    }

    public function commandQueueListing(Request $request, string $secret)
    {
        $commandQueue = new BuycraftCommandQueueListing($secret);
        $commandQueue->fetch($request->all());
        return $commandQueue;
    }

    public function commandOfflineQueueListing(Request $request, string $secret)
    {
        $commandOfflineQueue = new BuycraftCommandQueueListing($secret);
        $commandOfflineQueue->fetch($request->all());
        return $commandOfflineQueue;
    }

    public function commandOnlineQueueListing(Request $request, string $secret, string $playerID)
    {
        $commandOnlineQueue = new BuycraftCommandOnlineQueueListing($secret, $playerID);
        $commandOnlineQueue->fetch($request->all());
        return $commandOnlineQueue;
    }

    public function categoryListing(Request $request, string $secret)
    {
        $categoryList = new BuycraftCategoryListing($secret);
        $categoryList->fetch($request->all());
        return $categoryList;
    }

    public function paymentListing(Request $request, string $secret)
    {
        $paymentList = new BuycraftPaymentsList($secret);
        $paymentList->fetch($request->all());
        return $paymentList;
    }

    public function banListing(Request $request, string $secret)
    {
        $banList = new BuycraftBanListing($secret);
        $banList->fetch($request->all());
        return $banList;
    }

    public function couponListing(Request $request, string $secret)
    {
        $couponList = new BuycraftCouponListing($secret);
        $couponList->fetch($request->all());
        return $couponList;
    }

    public function couponShowCoupon(Request $request, string $secret, string $id)
    {
        $coupon = new BuycraftShowCoupon($secret, $id);
        $coupon->fetch($request->all());
        return $coupon;
    }

    public function giftcardListing(Request $request, string $secret)
    {
        $giftcardListing = new BuycraftGiftcardListing($secret);
        $giftcardListing->fetch($request->all());
        return $giftcardListing;
    }

    public function giftcardShowCard(Request $request, string $secret, string $id)
    {
        $giftcard = new BuycraftGiftcardShowCard($secret, $id);
        $giftcard->fetch($request->all());
        return $giftcard;
    }

}
