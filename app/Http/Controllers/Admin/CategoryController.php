<?php

namespace App\Http\Controllers\Admin;

use App\Category;
use Illuminate\Cookie\CookieJar;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $parentCategory = $request->cookie('parentCategory');
        $filterActive = $request->cookie('filterActive');
        $searchText = $request->get('searchText');

        $categories = Category::orderby('order', 'asc')->orderby('updated_at', 'desc');

        // Search
        if(!empty($searchText))
            $categories = $categories->where('title', 'like', "%{$searchText}%");

        // Filter by parent category
        if($parentCategory)
            $categories = $categories->where('parent_id', $parentCategory);

        // Filter by activity
        if($filterActive == 'Y')
            $categories = $categories->where('published', 1);
        elseif($filterActive == 'N')
            $categories = $categories->where('published', 0);

        $categories = $categories->paginate(20);

        return view('admin.categories.index', [
            'categories' => $categories,
            'parents' => Category::orderby('title', 'asc')->select(['id', 'title'])->get(),
            'filterCategory' => $parentCategory,
            'searchText' => $searchText,
            'filterActive' => $filterActive
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.categories.create', [
            'category' => [],
            'categories' => Category::with('children')->where('parent_id', '0')->get(),
            'delimiter' => '',
            'user' => Auth::user()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $category = Category::create($request->all());

        $request->session()->flash('success', 'Категория добавлена');
        return redirect()->route('admin.category.edit', $category);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function show(Category $category)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function edit(Category $category)
    {
        return view('admin.categories.edit', [
            'category' => $category,
            'categories' => Category::with('children')->where('parent_id', '0')->get(),
            'delimiter' => '-',
            'user' => Auth::user()
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Category $category)
    {
        $category->update($request->all());

        $request->session()->flash('success', 'Категория отредактирована');
        return redirect()->route('admin.category.edit', $category);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Category $category)
    {
        Category::destroy($category->id);

        $request->session()->flash('success', 'Категория удалена');
        return redirect()->route('admin.category.index');
    }

    /**
     * Set cookie for category filter
     *
     * @param CookieJar $cookieJar
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function filter(CookieJar $cookieJar, Request $request) {
        // Filter by parent category
        if($request->categorySelect > 0)
            $cookieJar->queue(cookie('parentCategory', $request->categorySelect, 60));
        elseif($request->categorySelect == 0)
            $cookieJar->queue($cookieJar->forget('parentCategory'));

        // Filter by activity
        if($request->activeSelect == "active")
            $cookieJar->queue(cookie('filterActive', 'Y', 60));
        elseif($request->activeSelect == "noactive")
            $cookieJar->queue(cookie('filterActive', 'N', 60));
        elseif($request->activeSelect == "all")
            $cookieJar->queue($cookieJar->forget('filterActive'));

        return redirect()->route('admin.category.index');
    }
}
