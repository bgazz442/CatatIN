<?php

namespace App\Http\Controllers;

use App\Models\Catatan;
use App\Models\Category;
use App\Models\Goal;
use App\Models\Task;
use App\Services\AI\LearningMaterialIndexer;
use App\Services\AttachmentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class CatatanController extends Controller
{
    /** Menampilkan ringkasan produktivitas pengguna. */
    public function dashboard()
    {
        if (auth()->user()?->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        $userId = auth()->id();

        $taskCounts = $userId
            ? Task::query()->where('user_id', $userId)->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status')
            : collect();
        $upcomingTasks = $userId
            ? Task::query()->where('user_id', $userId)->whereNotIn('status', ['completed'])
                ->whereBetween('due_date', [today(), today()->addDays(7)])
                ->with('category')->orderBy('due_date')->limit(5)->get()
            : collect();
        $overdueTasks = $userId
            ? Task::query()->where('user_id', $userId)->whereNotIn('status', ['completed'])
                ->where('due_date', '<', today())->with('category')->orderBy('due_date')->limit(5)->get()
            : collect();
        $activeGoalQuery = $userId
            ? Goal::query()->where('user_id', $userId)->where('status', 'active')
            : Goal::query()->whereRaw('1 = 0');
        $activeGoalCount = (clone $activeGoalQuery)->count();
        $activeGoals = (clone $activeGoalQuery)->orderBy('deadline')->limit(5)->get();

        return view('dashboard', compact(
            'taskCounts',
            'upcomingTasks',
            'overdueTasks',
            'activeGoalCount',
            'activeGoals'
        ));
    }

    /**
     * Menampilkan daftar semua catatan dengan fitur pencarian, filter, dan pagination.
     */
    public function index(Request $request)
    {
        $kategoriList = Category::query()->orderBy('name')->pluck('name');
        $search = $request->input('search');
        $kategori = $request->input('kategori');
        $favorit = $request->input('favorit');
        $sort = $request->input('sort', 'terbaru');

        $query = $this->visibleNotesQuery();

        // Pencarian judul dan isi
        if (! empty($search)) {
            $query->search($search);
        }

        // Filter kategori
        if (! empty($kategori)) {
            $query->filterKategori($kategori);
        }

        // Filter favorit jika dipilih
        if ($favorit === '1' || $favorit === 'true') {
            $query->where('favorit', true);
        } elseif ($favorit === '0' || $favorit === 'false') {
            $query->where('favorit', false);
        }

        // Pengurutan
        if ($sort === 'terlama') {
            $query->oldest();
        } else {
            $query->latest();
        }

        $catatan = $query->paginate(9)->withQueryString();

        return view('catatan.index', compact(
            'catatan',
            'kategoriList',
            'search',
            'kategori',
            'favorit',
            'sort'
        ));
    }

    /**
     * Menampilkan halaman khusus catatan favorit.
     */
    public function favorit(Request $request)
    {
        $kategoriList = Category::query()->orderBy('name')->pluck('name');
        $search = $request->input('search');
        $kategori = $request->input('kategori');
        $sort = $request->input('sort', 'terbaru');

        $query = $this->visibleNotesQuery()->where('favorit', true);

        if (! empty($search)) {
            $query->search($search);
        }

        if (! empty($kategori)) {
            $query->filterKategori($kategori);
        }

        if ($sort === 'terlama') {
            $query->oldest();
        } else {
            $query->latest();
        }

        $catatan = $query->paginate(9)->withQueryString();

        return view('catatan.favorit', compact(
            'catatan',
            'kategoriList',
            'search',
            'kategori',
            'sort'
        ));
    }

    /**
     * Menampilkan form tambah catatan baru.
     */
    public function create()
    {
        $kategoriList = Category::query()->orderBy('name')->pluck('name');
        $catatan = new Catatan;

        return view('catatan.create', compact('kategoriList', 'catatan'));
    }

    /**
     * Menyimpan catatan baru ke database.
     */
    public function store(Request $request)
    {
        if (! $request->user()) {
            return redirect()->route('login', [
                'login_required' => 1,
                'redirect_to' => route('catatan.create', [], false),
            ]);
        }

        $files = array_values(array_filter($request->file('documents', [])));
        $validated = $this->validateNote($request, null, $files);
        $storedPaths = [];

        try {
            $catatan = DB::transaction(function () use (&$validated, &$storedPaths, $files, $request) {
                $validated['user_id'] = $request->user()?->id;
                $catatan = Catatan::create($validated);
                if ($files !== []) {
                    $storedPaths = app(AttachmentManager::class)->store($catatan, $files);
                }

                return $catatan;
            });
        } catch (Throwable $exception) {
            app(AttachmentManager::class)->deletePaths($storedPaths);
            throw $exception;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Catatan berhasil ditambahkan.',
                'data' => [
                    'id' => $catatan->id,
                    'judul' => $catatan->judul,
                    'kategori' => $catatan->kategori,
                    'favorit' => $catatan->favorit,
                    'attachment_count' => $catatan->attachments()->count(),
                ],
                'redirect_url' => route('catatan.show', $catatan->id),
            ], 201);
        }

        return redirect()
            ->route('catatan.index')
            ->with('success', 'Catatan berhasil ditambahkan.');
    }

    /**
     * Menampilkan detail catatan.
     */
    public function show($id)
    {
        $catatan = $this->findVisibleNote($id);

        return view('catatan.show', compact('catatan'));
    }

    /**
     * Menampilkan form edit catatan.
     */
    public function edit($id)
    {
        $catatan = $this->findVisibleNote($id);
        $kategoriList = Category::query()->orderBy('name')->pluck('name');

        return view('catatan.edit', compact('catatan', 'kategoriList'));
    }

    /**
     * Memperbarui catatan di database.
     */
    public function update(Request $request, $id)
    {
        $catatan = $this->findVisibleNote($id);
        if ($request->hasFile('documents') && ! $request->user()) {
            return redirect()->route('login', [
                'login_required' => 1,
                'redirect_to' => route('catatan.edit', $catatan, false),
            ]);
        }

        $files = array_values(array_filter($request->file('documents', [])));
        $validated = $this->validateNote($request, $catatan, $files);
        $storedPaths = [];

        try {
            DB::transaction(function () use ($catatan, $validated, $files, &$storedPaths) {
                if ($catatan->user_id) {
                    app(LearningMaterialIndexer::class)
                        ->forgetMaterial((int) $catatan->user_id, 'catatan', (int) $catatan->id);
                }
                $catatan->update($validated);
                if ($files !== []) {
                    $storedPaths = app(AttachmentManager::class)->store($catatan, $files);
                }
            });
        } catch (Throwable $exception) {
            app(AttachmentManager::class)->deletePaths($storedPaths);
            throw $exception;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Catatan belajar berhasil diperbarui!',
                'data' => [
                    'id' => $catatan->id,
                    'judul' => $catatan->judul,
                    'kategori' => $catatan->kategori,
                    'favorit' => $catatan->favorit,
                    'attachment_count' => $catatan->attachments()->count(),
                ],
                'redirect_url' => route('catatan.show', $catatan->id),
            ]);
        }

        return redirect()
            ->route('catatan.show', $catatan->id)
            ->with('success', 'Catatan belajar berhasil diperbarui!');
    }

    /**
     * Menghapus catatan dari database.
     */
    public function destroy(Request $request, $id)
    {
        $catatan = $this->findVisibleNote($id);
        $attachments = $catatan->attachments()->get();
        if ($attachments->isNotEmpty() && ! auth()->check()) {
            return redirect()->route('login', [
                'login_required' => 1,
                'redirect_to' => route('catatan.show', $catatan),
            ]);
        }

        DB::transaction(function () use ($catatan, $attachments) {
            if ($catatan->user_id) {
                $indexer = app(LearningMaterialIndexer::class);
                $indexer->forgetMaterial((int) $catatan->user_id, 'catatan', (int) $catatan->id);
                foreach ($attachments as $attachment) {
                    $indexer->forgetMaterial((int) $catatan->user_id, 'lampiran', (int) $attachment->id);
                }
            }
            $catatan->delete();
        });
        foreach ($attachments as $attachment) {
            try {
                app(AttachmentManager::class)->deleteAttachment($attachment);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Catatan belajar berhasil dihapus.',
                'data' => ['id' => $catatan->id],
                'redirect_url' => route('catatan.index'),
            ]);
        }

        return redirect()
            ->route('catatan.index')
            ->with('success', 'Catatan belajar berhasil dihapus.');
    }

    /**
     * Mengubah status favorit (toggle) catatan.
     */
    public function toggleFavorit(Request $request, $id)
    {
        $catatan = $this->findVisibleNote($id);
        $catatan->favorit = ! $catatan->favorit;
        $catatan->save();

        $pesan = $catatan->favorit
            ? 'Catatan ditambahkan ke daftar favorit.'
            : 'Catatan dihapus dari daftar favorit.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $pesan,
                'data' => ['id' => $catatan->id, 'favorit' => $catatan->favorit],
            ]);
        }

        return back()->with('success', $pesan);
    }

    private function validateNote(Request $request, ?Catatan $catatan, array $files): array
    {
        $extensions = implode(',', config('learning.uploads.extensions'));
        $mimeTypes = implode(',', config('learning.uploads.mime_types'));
        $validator = Validator::make($request->all(), [
            'judul' => ['required', 'string', 'max:150'],
            'kategori' => ['required', 'string', Rule::exists('categories', 'name')],
            'isi' => ['nullable', 'string'],
            'favorit' => ['nullable', 'boolean'],
            'documents' => ['sometimes', 'array', 'max:'.config('learning.uploads.max_files')],
            'documents.*' => [
                'file',
                'max:'.(int) (config('learning.uploads.max_file_bytes') / 1024),
                'extensions:'.$extensions,
                'mimetypes:'.$mimeTypes,
            ],
        ], [
            'judul.required' => 'Judul catatan wajib diisi.',
            'judul.max' => 'Judul catatan maksimal 150 karakter.',
            'kategori.required' => 'Kategori catatan wajib dipilih.',
            'kategori.exists' => 'Kategori yang dipilih tidak valid.',
            'favorit.boolean' => 'Format status favorit tidak valid.',
            'documents.max' => 'Maksimal 10 file untuk setiap materi.',
        ]);

        $validator->after(function ($validator) use ($request, $catatan, $files) {
            $hasExistingAttachments = $catatan?->attachments()->exists() ?? false;
            if (blank($request->input('isi')) && $files === [] && ! $hasExistingAttachments) {
                $validator->errors()->add('isi', 'Isi catatan atau minimal satu file lampiran wajib ditambahkan.');
            }
        });

        $validated = $validator->validate();
        app(AttachmentManager::class)->assertWithinLimits($catatan, $files);
        $category = Category::where('name', $validated['kategori'])->firstOrFail();
        $validated['category_id'] = $category->id;
        $validated['isi'] = $validated['isi'] ?? '';
        $validated['favorit'] = $request->boolean('favorit');
        unset($validated['documents']);

        return $validated;
    }

    private function visibleNotesQuery()
    {
        return Catatan::query()->where('user_id', auth()->id());
    }

    private function findVisibleNote(int|string $id): Catatan
    {
        return $this->visibleNotesQuery()->findOrFail($id);
    }
}
