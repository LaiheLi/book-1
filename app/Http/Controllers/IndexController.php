<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Order;
use App\Models\School;
use App\Models\Search;
use Illuminate\Http\Request;
use Auth;

class IndexController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth', ['except'=>[
            'index', 'setSchool', 'search'
        ]]);
    }

    //首页
    public function index(){
        if(session()->has('school_id')){
            $sessionSchool = School::find(session('school_id'));
        }
        $books = Book::ofSchool()
            ->forUser()
            ->where('is_recommend', 1)
            ->get();
        return view('index.index', compact('sessionSchool', 'books'));
    }

    //会员中心首页
    public function memberIndex(Order $order){
        $user = Auth::user();
        $statuses = config('custom.order.status');

        //统计买书卖书数量
        $userOrderCount = $order->where('user_id', $user->id)->where('status', 5)->count();
        $sellerOrderCount = $order->where('seller_id', $user->id)->where('status', 5)->count();

        //统计订单数量
        $orderStatusCount[1] = $order->where('user_id', $user->id)->where('status', 1)->count();
        $orderStatusCount[4] = $order->where('user_id', $user->id)->where('status', 4)->count();

        //卖家
        $orderStatusCount[2] = $order->where('seller_id', $user->id)->where('status', 2)->count();
        $orderStatusCount[3] = $order->where('seller_id', $user->id)->where('status', 3)->count();
        $hasSell = $order->where('seller_id', $user->id)->count();


        return view('index.member_index', compact('user', 'statuses', 'userOrderCount', 'sellerOrderCount', 'orderStatusCount', 'hasSell'));
    }

    public function search(Request $request){
        $books = [];
        $searches = [];
        $keywords = $request->keywords;
        if($request->has('keywords')){

            $books = Book::search($request->keywords)
                ->where('school_id', session('school_id', 0))
                ->where('is_show', 1)
                ->where('status', 2)
                ->get();
            if(Auth::check()){
                Auth::user()->searches()->save(new Search(['keywords'=>$request->keywords]));
            }

        }else{

            if(Auth::check()){
                $searches = Auth::user()->searches()
                    ->orderBy('created_at', 'desc')
                    ->limit(6)
                    ->get();
            }

        }

        return view('index.search', compact('books', 'searches', 'keywords'));
    }

    //选择学校页面
    public function setSchool($school_id){
        session(['school_id'=>$school_id]);
        if(Auth::check()){
            $user = Auth::user();
            if(!$user->school_id){
                $user->school_id = $school_id;
                $user->save();
            }
        }
        return redirect()->intended(route('index'));
    }
    
}
