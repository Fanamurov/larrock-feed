@extends('larrock::front.main')
@section('title')
    @if($seo_midd['url'])
        {{ $seo_midd['url'] }}
    @else
        {{ $data->title }} {{ $seo_midd['postfix_global'] }}
    @endif
@endsection

@section('content')
    <div class="pageFeedCategory uk-position-relative">
        {!! Breadcrumbs::render('feed.category', $data) !!}

        <div class="clearfix"></div><br/>
        @foreach($data->getFeedActive as $item)
            <div class="pageFeedCategory-item uk-grid">
                <div class="uk-width-1-1 uk-width-medium-2-10">
                    <p class="date uk-text-muted uk-text-right">{!! \Carbon\Carbon::parse($item->date)->format('d.m.Y') !!}г.</p>
                </div>
                <div class="uk-width-1-1 uk-width-medium-8-10 uk-position-relative">
                    @role('Админ|Модератор')
                    <a class="admin_edit" href="/admin/feed/{{ $item->id }}/edit">Редактировать</a>
                    @endrole
                    <h3 class="uk-margin-top-remove"><a href="{{ $item->full_url }}">{{ $item->title }}</a></h3>
                    <div class="pageFeedCategory-item_short">{!! $item->short_render !!}</div>
                </div>
            </div>
        @endforeach

        @foreach($data->getChildActive as $item)
            <div class="pageFeedCategory-item uk-grid">
                <div class="uk-width-1-1 uk-position-relative">
                    @role('Админ|Модератор')
                    <a class="admin_edit" href="/admin/category/{{ $item->id }}/edit">Редактировать</a>
                    @endrole
                    <h3 class="uk-margin-top-remove"><a href="{{ $item->full_url }}">{{ $item->title }}</a></h3>
                    <div class="pageFeedCategory-item_short">{!! $item->short_render !!}</div>
                </div>
            </div>
        @endforeach
    </div>
    {!! $data->getFeedActive->render() !!}
@endsection