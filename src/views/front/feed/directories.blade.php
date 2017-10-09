@extends('larrocl::front.main')

@section('title')
    @if($seo_midd['url'])
        {{ $seo_midd['url'] }}
    @else
        {{ $data->title }} {{ $seo_midd['postfix_global'] }}
    @endif
@endsection
@section('description') {!! strip_tags($data->short) !!} @endsection
@section('share_image')http://santa-avia.ru{{ $data->first_image }}@endsection

@section('content')
    <div class="pageFeedDirectories">
        @role('Админ|Модератор')
            <a class="editAdmin" href="/admin/category/{{ $data->id }}/edit">Редактировать элемент</a>
        @endrole
        @foreach($data->get_child as $data_value)
            <h3>{{ $data_value->title }}</h3>
            @if(count($data_value->get_child) > 0)
                <ul>
                @foreach($data_value->get_feedActive as $feed_value)
                    <li><a href="{{ $feed_value->full_url }}">{{ $feed_value->title }}</a></li>
                @endforeach
                @foreach($data_value->get_child as $child_value)
                    <li><h4>{{ $child_value->title }}</h4></li>
                    @if(count($child_value->get_feedActive) > 0)
                        <ul>
                            @foreach($child_value->get_feedActive as $feed_value)
                                <li><a href="{{ $feed_value->full_url }}">{{ $feed_value->title }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                @endforeach
                </ul>
            @endif
        @endforeach
    </div>
@endsection

@section('contentBottom')
    <div class="col-xs-24">
        <a class="btn btn-default" href="/feed/{{ $data->url }}">Назад к блогу</a>
    </div>
    <div class="clearfix"></div>
@endsection