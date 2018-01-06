@extends('public/layouts/app')

@section('content')
    <div class="container main">

        <div class="item-page">
            {{-- Breadcrumbs include --}}
            @include('public.partials.breadcrumbs')

            <main class="catalog-categories">
                <h1>Каталог</h1>

                <div class="flex category-list">
                    @foreach($result as $category)
                        <a class="list-item" href="{{route('item.showCatalogCategory', ['category_slug' => $category['slug']])}}">
                            <div class="list-item-title">
                                {{$category['title']}}
                            </div>
                            <div class="wrap-image-list flex">
                                @if(isset($category['preview_img'][0]))
                                    <img class="list-item-img" src="{{$category['preview_img'][0]['MIDDLE']}}" alt="{{$category['title']}}" title="{{$category['title']}}"/>
                                @endif
                            </div>
                            <span class="order-button">Перейти в раздел</span>
                        </a>
                    @endforeach
                </div>
            </main>
        </div>
    </div>
@endsection
