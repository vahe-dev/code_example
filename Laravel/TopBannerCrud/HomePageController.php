<?php

namespace App\Http\Controllers\Admin;

use App\Models\HomeTopBanner;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\Datatables\Facades\Datatables;

class HomePageController extends BaseController {

    public function addTopBanner(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'title' => 'required',
            'link' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect('/admin/homePage#homeBannerTop')->withErrors($validator);
        }

        $home_top_banner = new HomeTopBanner();

        $home_top_banner->title = $request->title;
        $home_top_banner->link = $request->link;
        $home_top_banner->comment = $request->comment;
        $home_top_banner->save();

        return redirect('/admin/homePage#homeBannerTop')->with('success', 'Текст успешно добавлен.');
    }

    public function getTopBannerContent()
    {
        $home_top_banner = HomeTopBanner::get();

        return Datatables::of($home_top_banner)
            ->addColumn('action', function ($home_top_banner) {
                return '<a href="/admin/topBannerEditModal/' . $home_top_banner->id . '" data-remote="false" data-toggle="modal" data-target="#topBannerModal" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i> Редактировать</a>'
                    . '<a href="/admin/homepage/removeTopBanner/'.$home_top_banner->id.'" class="btn btn-xs btn-danger adminDeleteTopBanner"><i class="glyphicon glyphicon-remove"></i> Удалить</a>';
            })
            ->make(true);
    }

    public function removeTopBanner($id)
    {
        $topBanner = HomeTopBanner::find($id);
        $topBanner->delete();

        return redirect('/admin/homePage#homeBannerTop')->with('success', 'Текст успешно удален.');
    }

    public function topBannerEditModal($id)
    {
        $topBanner = HomeTopBanner::find($id);
        return view('admin.topBannerEditModal')->with(['topBanner'=>$topBanner]);
    }

    public function updateTopBanner(Request $request ,$id)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'title' => 'required',
            'link' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $top_banner_content = HomeTopBanner::find($id);
        $top_banner_content->title = $request->title;
        $top_banner_content->link = $request->link;
        $top_banner_content->comment = $request->comment;
        $top_banner_content->save();

        return redirect('/admin/homePage#homeBannerTop')->with('success', 'Текст успешно редактирован.');
    }
}
