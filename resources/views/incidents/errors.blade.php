@if ($errors->any())
<div class="flash flash-error" role="alert"><strong>Review the report:</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
