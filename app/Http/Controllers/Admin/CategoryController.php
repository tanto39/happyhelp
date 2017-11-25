<?php

namespace App\Http\Controllers\Admin;

use App\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    use \App\FilterController;
    use \App\SearchController;
    use \App\ImgController;
    use \App\HandlePropertyController;

    public $indexRoute = 'admin.category.index';
    public $prefix = 'Category';

    /**
     * Display a listing of the resource
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $categories = new Category();

        // Filter
        $categories = $this->filterExec($request, $categories);

        // Search
        $categories = $this->searchByTitle($request, $categories);

        $categories = $categories->paginate(20);

        return view('admin.categories.index', [
            'categories' => $categories,
            'parents' => Category::orderby('title', 'asc')->select(['id', 'title'])->get(),
            'searchText' => $this->searchText,
            'filter' => $this->arFilter,
            'sort' => $this->sortVal,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $category = new Category();

        // Get properties
        $this->getProperties($category, 'category_id', 'parent_id', PROP_KIND_CATEGORY);

        return view('admin.categories.create', [
            'category' => [],
            'categories' => Category::with('children')->where('parent_id', '0')->get(),
            'delimiter' => '',
            'user' => Auth::user(),
            'properties' => $this->arProps
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
        $requestData = $request->all();

        // Set properties array
        $requestData['properties'] = $this->setProperties($requestData['properties']);

        $category = Category::create($requestData);

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
        $preview_images = unserialize($category->preview_img);

        // Get properties
        $this->getProperties($category,  'category_id', 'parent_id', PROP_KIND_CATEGORY, $category->properties, $category->id);

        //dd($this->arProps);
        return view('admin.categories.edit', [
            'category' => $category,
            'categories' => Category::with('children')->where('parent_id', '0')->get(),
            'delimiter' => '-',
            'user' => Auth::user(),
            'preview_images' => $preview_images,
            'properties' => $this->arProps
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
        // Delete category
        if ($request->delete)
            return $this->destroy($request, $category);

        // Copy category
        if ($request->copy) {
            $request->preview_img = NULL;
            return  $this->store($request);
        }

        // Delete preview images
        if ($request->deleteImg) {
            $this->deleteMultipleImg($request, $category, 'preview_img', 'images/shares/previews/');
            return redirect()->route('admin.category.edit', $category);
        }

        $requestData = $request->all();

        // Set properties array
        if (isset($requestData['properties']))
            $requestData['properties'] = $this->setProperties($requestData['properties']);

        $category->update($requestData);

        $request->session()->flash('success', 'Категория отредактирована');

        if ($request->saveFromList)
            return redirect()->route('admin.category.index');

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
        // Delete preview images
        $this->deleteImgWithDestroy($category, 'preview_img', 'images/shares/previews/');

        // Delete category
        Category::destroy($category->id);
        $request->session()->flash('success', 'Категория удалена');
        return redirect()->route('admin.category.index');
    }
}
