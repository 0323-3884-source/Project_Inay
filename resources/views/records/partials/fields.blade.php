<table class="fields">
    <tbody>
    @foreach(array_chunk($fields, 2, true) as $pair)
        <tr>
        @foreach($pair as $heading => $content)
            <th>{{ $heading }}</th><td>{{ $value($content) }}</td>
        @endforeach
        @if(count($pair) === 1)<th></th><td></td>@endif
        </tr>
    @endforeach
    </tbody>
</table>
