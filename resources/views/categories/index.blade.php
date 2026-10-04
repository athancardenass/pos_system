@extends('layouts.app')

@section('title', 'Categories')

@section('content')
    <div class="page-head">
        <h1>Categories</h1>
        <x-ui.button :href="route('categories.create')">New category</x-ui.button>
    </div>
    <div class="card">
        @if ($categories->isEmpty())
            <p class="empty">No categories yet.</p>
        @else
<x-ui.table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr>
                            <td style="font-weight: 700;">{{ $category->category_name }}</td>
                            <td class="muted">{{ $category->description }}</td>
                            <td class="actions">
                                <x-ui.button variant="slate" size="compact" :href="route('categories.edit', $category)">Edit</x-ui.button>
                                <form class="inline-form" method="POST" action="{{ route('categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?')">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button variant="danger" type="submit" size="compact">Delete</x-ui.button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
</x-ui.table>
            {{ $categories->links('partials.pagination') }}
        @endif
    </div>
@endsection
