@blaze(fold: true)

<th {{ $attributes->merge(['data-slot' => 'table-head']) }}>{{ $slot }}</th>
