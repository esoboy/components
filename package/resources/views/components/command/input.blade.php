@blaze

<div data-slot="command-input-wrapper">
    <div data-slot="input-group" role="group">
        <input {{ $attributes->merge(['type' => 'text', 'data-slot' => 'command-input', 'autocomplete' => 'off', 'spellcheck' => 'false', 'role' => 'combobox', 'aria-expanded' => 'true', 'aria-autocomplete' => 'list']) }}>
        <div data-slot="input-group-addon" data-align="inline-start" role="group">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21 21-4.34-4.34"/><circle cx="11" cy="11" r="8"/></svg>
        </div>
    </div>
</div>
