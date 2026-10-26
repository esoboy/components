@blaze(fold: true)

<fieldset {{ $attributes->merge(['data-slot' => 'field-set']) }}>{{ $slot }}</fieldset>
