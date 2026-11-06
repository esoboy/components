# PHP-first components

The visual contract is unchanged: existing styles, tokens, slots and `data-slot`
selectors remain the reference. The migration changes state ownership and the
Blade/Livewire interface, not the design.

- PHP renders content, initial values, labels and validation state.
- Livewire owns application values, selection rules and multi-step flows.
- Native HTML/CSS handles the interactions it already supports.
- Alpine owns only browser interaction: focus, positioning, dragging, preview and
  scrolling. Components include their own directives and initialization.
- There is no package-owned custom-element runtime or global DOM event bus.
- Use `value`, `open`, `checked`, `active`, `options` and `items` in Blade.
  `wire:model` takes precedence. Old `default-*` and `is-active` names are removed;
  there is intentionally no compatibility layer.
- Simple options render through the same parts as custom slots.

Runtime code belongs in Alpine providers. Build scripts and browser tests are
tooling, not runtime code. Third-party editing/drawing engines and the editor's
upload transport are dependencies; their adapters remain inside the corresponding
Alpine lifecycle. The lazy editor uses Axios for upload progress, while PHP still
validates and stores uploads through the configured uploader.

## Public interface

There is intentionally no support for the old API.

| Former API | Blade API |
| --- | --- |
| `default-value` | `value`, or `wire:model` |
| `default-open` | `open`, or `wire:model` on a bound disclosure |
| `default-pressed` | `checked`, or `wire:model` on Toggle |
| `default-month` | `month` |
| Resizable Panel `default-size` | `size` |
| `default-expanded` | `expanded` |
| `default-item` | `current` |
| `default-scroll-position` | `scroll-position` |
| `default-time` | `time-value` |
| `is-active` | `active` |
| Toggle Group `type="multiple"` | `multiple` |
| Command / Combobox `should-filter` | `filter` |
| `data-theme-value` at call sites | `theme` |
| Custom element `.value` or `.editor` access | `wire:model`, native form submission, or the `Ui` facade |

Checkbox, Switch and Toggle accept `checked`. Checkbox and Radio Group use native
inputs; there is no group value adapter to initialize. Radio Group, Select,
Combobox, Cascader and Transfer accept `:options`. Tree and Tree Select accept
`:items`. Use `name` for traditional form submission.

```blade
<x-ui::radio-group wire:model="plan" :options="['free' => 'Free', 'pro' => 'Pro']" />
<x-ui::select wire:model="status" :options="$statuses" />
<x-ui::combobox wire:model.live="assignees" :options="$people" multiple />
<x-ui::combobox wire:model.live="email" :options="$this->suggestions" autocomplete :filter="false" />
<x-ui::toggle wire:model="bold">Bold</x-ui::toggle>
<x-ui::toggle-group wire:model="formats" multiple>…</x-ui::toggle-group>
```

`wire:model` binds on the public control. The component forwards native modifiers
to its actual input; browser-only adapters read the existing Livewire property.
Do not add `x-data`, `x-ref`, `x-init`, or internal event bindings at call sites.
Application actions still use normal `wire:click`, `wire:submit`, and public
component events.

## Server components

Calendar, Date Picker, Cascader, Combobox, Emoji Picker, Questionnaire, Tags Input,
Transfer, Tree and Tree Select render through internal Livewire components.
Their Blade facades handle keys, parent binding and validation. A caller does not
need to register or mount those Livewire classes.

- Calendar and Date Picker: `Y-m-d`, a list in `multiple` mode, or
  `['from' => ?string, 'to' => ?string]` in `range` mode. `time` adds `H:i`
  (`H:i:s` with a seconds step). Constraints, ordering and presets run in PHP.
- Select and single Combobox: the selected option value or `null`.
- Multiple Combobox, Tags Input, Transfer and checkable Tree: a list of values.
- Autocomplete Combobox: the typed string; pass suggestions from PHP.
- Cascader: a path such as `['asia', 'china', 'beijing']`.
- Questionnaire: an associative array keyed by each question's `name`.

Tree's removed `tree.item` composition is replaced by `:items`. For lazy children,
mark a node `lazy` and pass `:load="App\Support\TreeChildren::class"`. That invokable
class receives the node value and returns its children. It runs on the server;
the browser cannot choose another loader. Dragging emits `wire:move` with
`value`, `target` and `position`; the parent applies `Options::move()`.

Combobox renders chips from `multiple` and `:options`. Supply a Blade
`option-view` for option content or a `selected-view` for chips and button-mode
labels. An `empty-state` slot replaces the empty message, and the default slot
adds supplementary controls below the results.

The internal Livewire controls support Blade slots for caller-owned regions.
Actions in those slots belong to the caller. Provide a view name for content
that needs data from the internal component. Those views render again with
the current data on each internal update.

| Component | Regions | Views and inputs |
| --- | --- | --- |
| Calendar | Header and default footer | `day-view` receives `$day` |
| Date Picker | Default footer in the popup | `day-view` receives `$day`, `selected-view` receives `$value` and `$label` |
| Cascader | Default supplementary region | `option-view` receives `$option` and `$depth` |
| Tree and Tree Select | Default region below the tree | `node-view` receives `$node`, `$level` and `$checked` |
| Transfer | Default region below the lists, optional `actions` slot | `option-view` receives `$option` and `$list`, `actions-view` can use internal move actions |
| Tags Input | Default supplementary region | `tag-view` receives `$tag` |
| Emoji Picker | Default supplementary region, optional `footer-content` slot | The `footer` prop still enables the default footer |
| Questionnaire | Default region before the controls, optional `actions` slot | `question-view` receives `$item` and `$active` |

Questionnaire takes `:items` rather than a client-side step registry:

```blade
<x-ui::questionnaire wire:model="answers" wire:finish="save($event.detail.value)"
    shortcuts="letters" :items="[
        ['name' => 'direction', 'title' => 'Choose a direction', 'required' => true,
         'options' => ['new' => 'New feature', 'fix' => 'Fix a problem']],
        ['name' => 'sources', 'title' => 'Choose sources', 'multiple' => true,
         'options' => ['files' => 'Source files', 'tests' => 'Tests']],
    ]" />
```

Optional question fields are `description`, `disabled`, `input`, `other`,
`inputLabel` and `placeholder`. `other` names an alternate text-answer key.
Required answers and explicit skipping are validated in PHP, including when
`submit` is invoked directly.

## Browser interaction

Select, Number Input, interactive Rating and Input OTP maintain their values in
internal Livewire components. Their Blade views render selected labels, numeric
limits, rating state and OTP characters. Input Group Count renders Unicode
counts and soft limits in Blade even when its input has a deferred binding.

Command filters in Livewire and preserves the caller's item actions. Use
`:filter="false"` when the caller supplies the result list. Mentions filters its
catalogue in Livewire. A `wire:mentions-search` handler opts into caller supplied
results without a second filter. Upload progress and pending attachment rows are
rendered by Livewire and Blade. Drag-and-drop and the local image preview remain
browser interactions.

Data Table rows use local expansion when `expanded` is omitted. A change to an
explicit `:expanded` value takes effect on the next render, including when the
row keeps the same ID.

Keep the existing component CSS and tokens. Native details, inputs, dialog,
popover and CSS positioning remain responsible for their supported behavior.
Alpine providers handle focus, hover timing, caret positioning, dragging,
scrolling and SDK lifecycles. They do not restore application values after a
Livewire morph or maintain separate selection stores.

Editor, Signature and syntax highlighting load their third-party engines on
demand inside Alpine. Keep using `Ui::editor()`, `Ui::sanitize()` and the existing
server upload contract. No DOM custom-element methods are public APIs.

Sidebar Provider initializes itself. `scope="admin"` isolates its persisted
preference, and shortcuts affect the focused provider. Overlay targets are
generated in PHP, including repeated components; callers do not pair them with
manual IDs or document-order event handlers.

## Verification

The existing 13 demo pages at desktop and mobile sizes are the visual baseline.
Component CSS is held fixed. Tests exercise the public Blade/Livewire interfaces,
form submission, state changes, keyboard interaction, navigation and multiple
instances. Architecture checks prevent reintroducing a custom-element runtime.
