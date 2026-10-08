@extends('admin.pages.master')
@section('title', ($recipe->exists ? 'Edit' : 'Add') . ' Recipe')

@section('content')

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-9">
                <div class="card">
                    <div class="card-header"><h4 class="card-title mb-0">{{ $recipe->exists ? 'Edit' : 'Add New' }} Recipe</h4></div>
                    <div class="card-body">
                        <form method="POST" action="{{ $recipe->exists ? route('admin.recipes.update') : route('admin.recipes.store') }}">
                            @csrf
                            @if ($recipe->exists)<input type="hidden" name="id" value="{{ $recipe->id }}">@endif
                            <div class="row g-3 mb-4">
                                <div class="col-md-8">
                                    <label class="form-label">Title</label>
                                    <input type="text" name="title" class="form-control" required maxlength="255" value="{{ old('title', $recipe->title) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Servings</label>
                                    <input type="text" name="servings" class="form-control" maxlength="50" value="{{ old('servings', $recipe->servings) }}" placeholder="Serves 4">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Method</label>
                                    <textarea name="body" class="form-control" rows="5">{{ old('body', $recipe->body) }}</textarea>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Image URL</label>
                                    <input type="text" name="image" class="form-control" maxlength="255" value="{{ old('image', $recipe->image) }}" placeholder="https://…">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Visible</label>
                                    <div class="form-check form-switch" dir="ltr">
                                        <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ old('is_active', $recipe->status ?? true) ? 'checked' : '' }}>
                                    </div>
                                </div>
                            </div>

                            <h6 class="mb-3">Ingredients</h6>
                            <div id="ingRows">
                                @foreach (old('ingredients', $recipe->ingredients->map(fn ($i) => ['product_id' => $i->product_id, 'variant_id' => $i->product_variant_id, 'qty' => $i->qty])->all() ?? []) as $n => $row)
                                    <div class="row g-2 mb-2 ing-row">
                                        <div class="col-md-6">
                                            <select name="ingredients[{{ $n }}][product_id]" class="form-control ing-product" required>
                                                <option value="">Choose product…</option>
                                                @foreach ($products as $p)
                                                    <option value="{{ $p->id }}" @selected((string) ($row['product_id'] ?? '') === (string) $p->id)>{{ $p->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="text" name="ingredients[{{ $n }}][variant_id]" class="form-control" value="{{ $row['variant_id'] ?? '' }}" placeholder="Pack id (optional)">
                                        </div>
                                        <div class="col-md-2">
                                            <input type="number" name="ingredients[{{ $n }}][qty]" class="form-control" min="1" max="99" value="{{ $row['qty'] ?? 1 }}">
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-soft-danger ing-remove">Remove</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-soft-secondary btn-sm mb-4" id="ingAdd">+ Add ingredient</button>

                            <div>
                                <button type="submit" class="btn btn-primary">Save recipe</button>
                                <a href="{{ route('admin.recipes.index') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var box = document.getElementById('ingRows');
            var add = document.getElementById('ingAdd');
            var n = box ? box.children.length : 0;
            var first = box ? box.querySelector('.ing-row') : null;
            if (add) add.addEventListener('click', function () {
                var row = first ? first.cloneNode(true) : null;
                if (!row) return;
                row.querySelectorAll('select, input').forEach(function (el) {
                    el.name = el.name.replace(/\[\d+\]/, '[' + n + ']');
                    if (el.tagName === 'SELECT') el.selectedIndex = 0; else el.value = el.type === 'number' ? 1 : '';
                });
                box.appendChild(row);
                n++;
            });
            if (box) box.addEventListener('click', function (e) {
                if (e.target.classList.contains('ing-remove') && box.children.length > 1) e.target.closest('.ing-row').remove();
            });
        })();
    </script>

@endsection
