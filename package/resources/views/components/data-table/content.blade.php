@blaze

{{-- The bordered frame around the table; put the header and body inside. --}}
<div {{ $attributes->merge(['data-slot' => 'data-table-content']) }}>
    <x-ui::table>{{ $slot }}</x-ui::table>
</div>
