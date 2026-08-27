@if ($errors->any())
    <div class="flash flash-error" role="alert">
        @if ($errors->count() === 1)
            {{ $errors->first() }}
        @else
            <ul style="margin: 0; padding-left: 1.1rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
