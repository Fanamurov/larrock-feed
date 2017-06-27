@extends('larrock::front.main')
@section('title') {{ $data->title }} @endsection

@section('content')
    <div class="pageFeedCategory">
        <div class="col-xs-24 row">
            {!! Breadcrumbs::render('feed.category', $data) !!}
        </div>
        <div class="clearfix"></div><br/>
        @foreach($data->get_feedActive as $item)
            <div class="pageFeedCategory-item uk-grid">
                <div class="uk-width-1-1 uk-width-medium-2-10">
                    <p class="date uk-text-muted uk-text-right">{!! \Carbon\Carbon::parse($item->date)->format('d.m.Y') !!}г.</p>
                </div>
                <div class="uk-width-1-1 uk-width-medium-8-10">
                    @role('Админ|Модератор')
                    <a class="admin_edit" href="/admin/feed/{{ $item->id }}/edit">Редактировать</a>
                    @endrole
                    <h3 class="uk-margin-top-remove"><a href="{{ $item->full_url }}">{{ $item->title }}</a></h3>
                    <div class="pageFeedCategory-item_short">{!! $item->short !!}</div>
                </div>
            </div>
        @endforeach
    </div>
    {!! $data->get_feedActive->render() !!}
@endsection