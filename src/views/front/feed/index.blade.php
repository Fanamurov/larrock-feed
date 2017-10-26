@extends('larrock::front.main')
@section('title')
    @if($seo_midd['url'])
        {{ $seo_midd['url'] }}
    @else
        Feed
    @endif
@endsection

@section('content')
    <div class="pageBlogCategory">
        <div class="col-xs-24 row">
            {!! Breadcrumbs::render('feed.index', $data) !!}
        </div>
        <div class="clearfix"></div><br/>
        @foreach($data as $item)
            <div class="pageBlogCategory-item row">
                <div class="hidden-xs col-sm-6 col-md-8">
                    @if($item->getFirstMediaUrl('images', '250x250'))
                        <img class="all-width" src="{{ $item->getFirstMediaUrl('images', '250x250') }}" alt="{{ $item->title }}">
                    @endif
                </div>
                <div class="col-xs-24 col-sm-18 col-md-16 uk-position-relative">
                    @role('Админ|Модератор')
                    <a class="editAdmin" href="/admin/feed/{{ $item->id }}/edit">Редактировать</a>
                    @endrole
                    <h4><a href="/blog/{{ $item->get_category->url }}/{{ $item->url }}">{{ $item->title }}</a></h4>
                    <div class="pageBlogCategory-item_short">{!! $data->short_render !!}</div>
                </div>
            </div>
        @endforeach
    </div>
    {!! $data->render() !!}
@endsection