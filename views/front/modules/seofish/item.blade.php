<div class="uk-grid block-seofish">
    <h1 class="uk-width-1-1">{{ $seofish->first()->title }}</h1>
    <div class="uk-width-1-1 uk-width-medium-1-2 first-coloumn">
        @foreach($seofish as $key => $item)
            @if($key & 1)
                <div class="block-seofish-item uk-position-relative">
                    <h4>{{ $item->title }}</h4>
                    <div>
                        @level(2)
                        <a class="admin_edit" href="/admin/feed/{{ $item->id }}/edit">Edit element</a>
                        @endlevel
                        {!! $item->short !!}
                        {!! $item->description !!}
                    </div>
                </div>
            @endif
        @endforeach
    </div>
    <div class="uk-width-1-1 uk-width-medium-1-2 second-coloumn">
        @foreach($seofish as $key => $item)
            @if($key & 1 || $key === 0)
            @else
                <div class="block-seofish-item uk-position-relative">
                    <h4>{{ $item->title }}</h4>
                    <div>
                        @level(2)
                        <a class="admin_edit" href="/admin/feed/{{ $item->id }}/edit">Edit element</a>
                        @endlevel
                        {!! $item->short !!}
                        {!! $item->description !!}
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</div>