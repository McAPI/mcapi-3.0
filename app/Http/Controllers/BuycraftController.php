<?php

namespace App\Http\Controllers;

use App\Responses\BuycraftBanListing;
use App\Responses\BuycraftCommandQueueListing;
use App\Responses\BuycraftCouponListing;
use App\Responses\BuycraftListing;
use App\Responses\BuycraftInformation;
use App\Responses\BuycraftPaymentsList;
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

    public function categoryListing(Request $request, string $secret)
    {
        $categoryList = new BuycraftListing($secret);
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

}
