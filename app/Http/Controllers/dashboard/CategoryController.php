<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class CategoryController extends Controller
{
    public function index(Request $request)
    {
        // إحصائيات التصنيفات
        $categoriesCount = Category::count();
        $categoriesWithImages = Category::whereNotNull('image')->count();
        $categoriesWithDescription = Category::whereNotNull('description')->count();

        // البحث
        $search = $request->input('search');
        $categories = Category::when($search, function ($query) use ($search) {
            $query->where('name', 'like', "%$search%")
                ->orWhere('description', 'like', "%$search%");
        })->latest()->paginate(10);

        return view('dashboard.categories.index', compact(
            'categories',
            'categoriesCount',
            'categoriesWithImages',
            'categoriesWithDescription'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $categoryData = $request->except('image');

        if ($request->hasFile('image')) {
            $categoryData['image'] = $request->file('image')->store('categories', 'public');
        }

        Category::create($categoryData);

        return redirect()->route('categories.index')->with('success', 'تم إنشاء التصنيف بنجاح');
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $categoryData = $request->except('image');

        if ($request->hasFile('image')) {
            // حذف الصورة القديمة إذا كانت موجودة
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $categoryData['image'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($categoryData);

        return redirect()->route('categories.index')->with('success', 'تم تحديث التصنيف بنجاح');
    }

    public function destroy(Category $category)
    {
        // حذف الصورة إذا كانت موجودة
        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'تم حذف التصنيف بنجاح');
    }
}
