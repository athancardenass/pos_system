@extends('layouts.app')

@section('title', 'Categories')

@section('content')
    <div class="page-head">
        <h1>Categories</h1>
        <a class="btn" href="{{ route('categories.create') }}">New category</a>
    </div>
    <div class="card">
        @if ($categories->isEmpty())
            <p class="empty">No categories yet.</p>
        @else
            <table>
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
                            <td>{{ $category->category_name }}</td>
                            <td class="muted">{{ $category->description }}</td>
                            <td class="actions">
                                <a href="{{ route('categories.edit', $category) }}">Edit</a>
                                <form class="inline-form" method="POST" action="{{ route('categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn-ghost btn-danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="pagination">{{ $categories->links() }}</div>
        @endif
    </div>
@endsection
