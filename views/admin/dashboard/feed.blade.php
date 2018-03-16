<div class="uk-margin-bottom uk-width-1-1 uk-width-1-4@m">
    <h4 class="panel-p-title"><a href="/admin/{{ $component->name }}">{{ $component->title }}</a></h4>
    <div class="uk-card uk-card-default uk-card-small">
        <div class="uk-card-body">
            @if(count($data) > 0)
                <ul class="uk-list">
                    @foreach($data as $value)
                        <li>
                            <a href="/admin/{{ $component->name }}/{{ $value->id }}/edit">{{ $value->title }}</a>
                            <a target="_blank" href="{{ $value->full_url }}"><span uk-icon="icon: link"></span></a>
                            @if($value->active !== 1)
                                <span class="uk-label uk-label-danger">Не опубликован</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p>Материалов еще нет</p>
            @endif
            <p>
                <a href="/admin/{{ $component->name }}/create" class="uk-button uk-button-default uk-width-1-1">Создать материал</a>
            </p>
        </div>
    </div>
</div>