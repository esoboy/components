@blaze(fold: true)

{{-- <ui-data-table> checks every row from the select-all box and marks selected rows. --}}
<ui-data-table {{ $attributes->merge(['data-slot' => 'data-table']) }}>{{ $slot }}</ui-data-table>
