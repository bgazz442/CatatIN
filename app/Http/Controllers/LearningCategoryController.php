<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LearningCategoryController extends Controller
{
    public function index(): View
    {
        return view('categories.index', [
            'categories' => Category::withCount(['catatan', 'mediaFavorites', 'tasks', 'dailyActivities'])->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('categories.form', ['category' => new Category]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $this->validatedName($request);
        $category = Category::create(['name' => $validated['name']]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Kategori berhasil ditambahkan.',
                'data' => ['id' => $category->id, 'name' => $category->name],
                'redirect_url' => route('categories.index'),
            ], 201);
        }

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Category $category): View
    {
        return view('categories.form', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse|JsonResponse
    {
        $validated = $this->validatedName($request, $category);

        DB::transaction(function () use ($category, $validated) {
            $category->update(['name' => $validated['name']]);
            $category->catatan()->update(['kategori' => $validated['name']]);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Kategori berhasil diperbarui.',
                'data' => ['id' => $category->id, 'name' => $category->name],
                'redirect_url' => route('categories.index'),
            ]);
        }

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Request $request, Category $category): RedirectResponse|JsonResponse
    {
        if ($category->catatan()->exists()
            || $category->mediaFavorites()->exists()
            || $category->tasks()->exists()
            || $category->dailyActivities()->exists()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Kategori masih digunakan. Pindahkan data terkait ke kategori lain sebelum menghapusnya.',
                    'errors' => ['category' => ['Kategori masih digunakan. Pindahkan data terkait ke kategori lain sebelum menghapusnya.']],
                ], 422);
            }

            return back()->withErrors([
                'category' => 'Kategori masih digunakan. Pindahkan data terkait ke kategori lain sebelum menghapusnya.',
            ]);
        }

        $category->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Kategori berhasil dihapus.', 'data' => ['id' => $category->id]]);
        }

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil dihapus.');
    }

    private function validatedName(Request $request, ?Category $category = null): array
    {
        $request->merge(['name' => preg_replace('/\s+/', ' ', trim((string) $request->input('name')))]);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $duplicate = Category::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($validated['name'])])
            ->when($category, fn ($query) => $query->where('id', '<>', $category->id))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'name' => 'Nama kategori tersebut sudah tersedia.',
            ]);
        }

        return $validated;
    }
}
