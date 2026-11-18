<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    @foreach($catalog as $cat => $items)
        <div>
            <div class="text-xs font-semibold text-slate-500 uppercase mb-1">{{ $cat }}</div>
            @foreach($items as $key => $label)
                <label class="flex items-start gap-2 text-sm text-slate-700 dark:text-slate-300 mb-1">
                    <input type="checkbox" name="permissions[{{ $key }}]" value="1" class="rounded mt-0.5" {{ in_array($key, $selected) ? 'checked' : '' }}>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>
    @endforeach
</div>
