<div class="block_anons uk-width-1-2 uk-width-large-1-1">
    <p class="uk-h2">Новости и акции</p>
    <ul class="uk-list uk-list-space">
        @foreach($data as $item)
            <li>
                {!! $item->short !!}
                {!! $item->description !!}
            </li>
        @endforeach
    </ul>
</div>