@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ url(config('app.logotipo_mail')) }}" class="logo" alt="{{ config('app.name') }}"> {{ $slot }}
</a>
</td>
</tr>
