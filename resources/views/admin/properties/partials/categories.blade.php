@foreach($categories as $category_list)
    <option value="{{$category_list->id or ""}}"
        @isset($property->id)
            @if($property->category_id == $category_list->id)
                selected=""
            @endif
        @endisset
    >
    {!! $delimiter or "" !!}{{$category_list->title or ""}}
    </option>

    @if(count($category_list->children) > 0)
        @include('admin.properties.partials.categories', [
            'categories' => $category_list->children,
            'delimiter' => ' - ' . $delimiter
        ])
    @endif
@endforeach